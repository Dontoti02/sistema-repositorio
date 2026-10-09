<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Acceso: inicio de sesión, cierre y recuperación de contraseña.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', [
            'pageTitle' => 'Iniciar sesión',
            'metaDescription' => 'Acceso al panel del repositorio institucional.',
        ]);
    }

    public function login(Request $request): Response
    {
        // Limita intentos por sesión/IP (rate limiting simple).
        $attempts = (int) Session::get('login_attempts', 0);
        if ($attempts >= 10) {
            $this->flash('error', 'Demasiados intentos. Espere unos minutos antes de volver a intentar.');
            return $this->redirect('/login');
        }

        $data = Validator::make($request->all(), [
            'email' => 'required|email|max:190',
            'password' => 'required|max:100',
        ], ['email' => 'Correo electrónico', 'password' => 'Contraseña']);

        if (!Auth::attempt($data['email'], $data['password'])) {
            Session::put('login_attempts', $attempts + 1);
            $this->flash('error', 'Correo o contraseña incorrectos.');
            return $this->redirect('/login');
        }

        Session::forget('login_attempts');
        $user = Auth::user();
        if ($user === null || $user['role_slugs'] === [] && empty($user['membership'])) {
            Auth::logout();
            $this->flash('error', 'Su cuenta no tiene una membresía activa en este instituto.');
            return $this->redirect('/login');
        }
        // Auditoría del acceso.
        $this->audit('auth.login', 'user', (int) $user['id'], null, ['resultado' => 'exitoso']);
        $this->flash('success', 'Bienvenido(a), ' . $user['full_name'] . '.');
        return $this->redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        $user = Auth::user();
        if ($user !== null) {
            $this->audit('auth.logout', 'user', (int) $user['id'], null, ['resultado' => 'cierre']);
        }
        Auth::logout();
        $this->flash('success', 'Sesión cerrada correctamente.');
        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('auth/forgot', [
            'pageTitle' => 'Recuperar contraseña',
            'metaDescription' => 'Solicite un enlace de restablecimiento de contraseña.',
        ]);
    }

    public function forgot(Request $request): Response
    {
        $data = Validator::make($request->all(), [
            'email' => 'required|email|max:190',
        ], ['email' => 'Correo electrónico']);

        $email = mb_strtolower($data['email']);
        $user = Database::selectOne('SELECT id, email, full_name FROM users WHERE email = ? AND status = 1 AND deleted_at IS NULL', [$email]);

        // Respuesta idéntica exista o no la cuenta (evita enumeración).
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            Database::statement(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, ip_masked, created_at)
                 VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 60 MINUTE), ?, UTC_TIMESTAMP())',
                [(int) $user['id'], $hash, $request->ipMasked()]
            );
            $link = url('/recuperar?token=' . $token);
            Database::statement(
                'INSERT INTO email_outbox (institution_id, to_email, subject, body, status, created_at)
                 VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    \App\Core\TenantContext::institutionId(),
                    $user['email'],
                    'Restablecimiento de contraseña',
                    "Hola {$user['full_name']},\n\nRecibimos una solicitud para restablecer su contraseña.\n" .
                    "Use este enlace (válido por 60 minutos):\n{$link}\n\nSi no fue usted, ignore este mensaje.",
                    'pending',
                ]
            );
        }
        $this->flash('success', 'Si el correo existe en el sistema, recibirá instrucciones de recuperación.');
        return $this->redirect('/login');
    }

    public function showReset(Request $request): Response
    {
        $token = (string) $request->query('token', '');
        $row = $this->validToken($token);
        if ($row === null) {
            $this->flash('error', 'El enlace de recuperación no es válido o ya expiró.');
            return $this->redirect('/login');
        }
        return $this->view('auth/reset', [
            'pageTitle' => 'Nueva contraseña',
            'token' => $token,
        ]);
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->input('token', '');
        $row = $this->validToken($token);
        if ($row === null) {
            $this->flash('error', 'El enlace de recuperación no es válido o ya expiró.');
            return $this->redirect('/login');
        }
        $data = Validator::make($request->all(), [
            'password' => 'required|min:10|max:100|confirmed',
        ], ['password' => 'Nueva contraseña']);

        Database::transaction(function () use ($row, $data): void {
            Database::statement(
                'UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE id = ? AND used_at IS NULL',
                [(int) $row['id']]
            );
            Database::statement(
                'UPDATE users SET password_hash = ?, must_change_password = 0, failed_attempts = 0, locked_until = NULL WHERE id = ?',
                [password_hash($data['password'], PASSWORD_DEFAULT), (int) $row['user_id']]
            );
        });
        $this->audit('auth.password_reset', 'user', (int) $row['user_id'], null, ['resultado' => 'exitoso']);
        $this->flash('success', 'Contraseña actualizada. Inicie sesión con su nueva contraseña.');
        return $this->redirect('/login');
    }

    /** @return array<string,mixed>|null */
    private function validToken(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::selectOne(
            'SELECT id, user_id FROM password_reset_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()
             LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    /**
     * Registra en la bitácora de auditoría.
     *
     * @param array<string,mixed>|null $before
     * @param array<string,mixed>|null $after
     */
    private function audit(string $action, string $entityType, ?int $entityId, ?array $before, ?array $after): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $parts = explode('.', $ip);
                $parts[3] = 'x';
                $ip = implode('.', $parts);
            } else {
                $ip = substr($ip, 0, 10) . '::';
            }
            $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
            Database::statement(
                'INSERT INTO audit_logs (institution_id, actor_id, action, entity_type, entity_id, before_summary, after_summary, ip_masked, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())',
                [
                    \App\Core\TenantContext::isReady() ? \App\Core\TenantContext::institutionId() : null,
                    Auth::id(),
                    $action,
                    $entityType,
                    $entityId,
                    $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                    $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
                    $ip,
                    $ua,
                ]
            );
        } catch (\Throwable) {
            // La auditoría no debe interrumpir el flujo principal.
        }
    }
}
