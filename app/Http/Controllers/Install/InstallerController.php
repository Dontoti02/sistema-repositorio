<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Support\Migrator;

/**
 * Asistente de instalación inicial. Solo accesible mientras
 * no exista storage/installed.lock (bloqueo irreversible).
 */
final class InstallerController extends Controller
{
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'json'];

    public function __construct()
    {
        if (is_file(storage_path('installed.lock'))) {
            throw new HttpException(404, 'El instalador está bloqueado: la instalación ya fue completada.');
        }
    }

    public function show(Request $request): Response
    {
        return $this->view('install/index', [
            'title' => 'Instalación del repositorio',
            'requirements' => $this->requirements(),
            'dbStep' => $this->databaseConfigured(),
            'steps' => Session::get('install', []),
        ], 200, 'layouts/install');
    }

    public function configure(Request $request): Response
    {
        $data = Validator::make($request->all(), [
            'db_host' => 'required|max:100',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|alpha_dash|max:64',
            'db_username' => 'required|max:100',
            'db_password' => 'max:190',
            'app_url' => 'required|url|max:255',
            'app_name' => 'required|max:120',
        ], [
            'db_host' => 'Servidor de base de datos',
            'db_port' => 'Puerto',
            'db_database' => 'Nombre de la base de datos',
            'db_username' => 'Usuario',
            'db_password' => 'Contraseña',
            'app_url' => 'URL del sitio',
            'app_name' => 'Nombre del repositorio',
        ]);

        // Probar conexión ANTES de escribir nada.
        try {
            $dsn = sprintf('mysql:host=%s;port=%d', $data['db_host'], $data['db_port']);
            $pdo = new \PDO($dsn, $data['db_username'], $data['db_password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            throw new HttpException(422, 'No se pudo conectar a MySQL: ' . $e->getMessage());
        }

        $this->writeEnv($data);
        \App\Core\Env::flush();
        \App\Core\Config::flush();
        \App\Core\Config::boot(base_path('config'));
        \App\Core\Database::setConnection(null);

        // Migraciones + catálogos globales.
        $migrator = new Migrator();
        $applied = $migrator->migrate();
        $seeder = require base_path('database/seeders/BaseCatalogsSeeder.php');
        $seeder();

        Session::put('install', ['db' => true, 'migrations' => count($applied)]);
        $this->flash('success', 'Base de datos configurada. ' . count($applied) . ' migraciones aplicadas.');
        return $this->redirect('/instalar');
    }

    public function finish(Request $request): Response
    {
        if (!$this->databaseConfigured()) {
            $this->flash('error', 'Primero configure la base de datos.');
            return $this->redirect('/instalar');
        }

        $data = Validator::make($request->all(), [
            'name' => 'required|max:200',
            'short_name' => 'max:120',
            'institution_type' => 'max:40',
            'slug' => 'required|slug|max:120',
            'email' => 'required|email|max:190',
            'website' => 'url|max:255',
            'description' => 'max:4000',
            'admin_name' => 'required|max:190',
            'admin_email' => 'required|email|max:190',
            'admin_password' => 'required|min:10|max:100|confirmed',
        ], [
            'name' => 'Nombre oficial de la institución',
            'short_name' => 'Nombre corto',
            'institution_type' => 'Tipo de institución',
            'slug' => 'Identificador web',
            'email' => 'Correo institucional',
            'website' => 'Sitio web',
            'description' => 'Descripción',
            'admin_name' => 'Nombre del administrador',
            'admin_email' => 'Correo del administrador',
            'admin_password' => 'Contraseña del administrador',
        ]);

        $result = Database::transaction(function () use ($data): array {
            // Institución.
            Database::statement(
                'INSERT INTO institutions (uuid, name, short_name, institution_type, slug, status, email, website, description,
                    primary_color, support_email, installed_at, created_at, updated_at)
                 VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [
                    $data['name'],
                    $data['short_name'] ?? null,
                    $data['institution_type'] ?? null,
                    $data['slug'],
                    'active',
                    $data['email'],
                    $data['website'] ?? null,
                    $data['description'] ?? null,
                    '#0b3d6b',
                    $data['email'],
                ]
            );
            $institutionId = (int) Database::lastInsertId();

            Database::statement(
                'INSERT INTO institution_settings (institution_id, setting_key, setting_value, updated_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$institutionId, 'demo_mode', '0']
            );

            // Administrador institucional.
            Database::statement(
                'INSERT INTO users (uuid, full_name, email, password_hash, email_verified_at, status, created_at, updated_at)
                 VALUES (UUID(), ?, ?, ?, UTC_TIMESTAMP(), 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [$data['admin_name'], mb_strtolower($data['admin_email']), password_hash($data['admin_password'], PASSWORD_DEFAULT)]
            );
            $userId = (int) Database::lastInsertId();

            Database::statement(
                'INSERT INTO user_institution_memberships (institution_id, user_id, status, created_at, updated_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
                [$institutionId, $userId, 'active']
            );
            $membershipId = (int) Database::lastInsertId();

            $role = Database::selectOne('SELECT id FROM roles WHERE institution_id = ? AND slug = ?', [$institutionId, 'institution_admin']);
            if ($role === null) {
                $seeder = require base_path('database/seeders/InstitutionSeeder.php');
                $seeder($institutionId);
                $role = Database::selectOne('SELECT id FROM roles WHERE institution_id = ? AND slug = ?', [$institutionId, 'institution_admin']);
            }
            Database::statement(
                'INSERT IGNORE INTO membership_roles (membership_id, role_id, created_at) VALUES (?, ?, UTC_TIMESTAMP())',
                [$membershipId, (int) $role['id']]
            );

            // Disco de almacenamiento por defecto.
            Database::statement(
                'INSERT INTO storage_disks (disk_name, driver, root_path, is_default, created_at)
                 VALUES (?, ?, ?, 1, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE root_path = VALUES(root_path)',
                ['local_private', 'local_private', storage_path('private')]
            );

            return ['institution_id' => $institutionId];
        });

        // Seeder institucional (fuera de la transacción principal por idempotencia).
        $seeder = require base_path('database/seeders/InstitutionSeeder.php');
        $seeder((int) $result['institution_id']);

        // Bloqueo irreversible del instalador.
        file_put_contents(
            storage_path('installed.lock'),
            json_encode(['installed_at' => gmdate('c'), 'institution_id' => $result['institution_id']], JSON_PRETTY_PRINT)
        );
        @chmod(storage_path('installed.lock'), 0644);

        Session::forget('install');
        $this->flash('success', 'Instalación completada. Inicie sesión con la cuenta administradora.');
        return $this->redirect('/login');
    }

    /** @return array<string,array{ok:bool,detail:string}> */
    private function requirements(): array
    {
        $checks = [];
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $checks['Extensión ' . $ext] = ['ok' => extension_loaded($ext), 'detail' => extension_loaded($ext) ? 'Disponible' : 'Falta'];
        }
        $checks['PHP >= 8.2'] = ['ok' => PHP_VERSION_ID >= 80200, 'detail' => PHP_VERSION];
        foreach (['storage/private', 'storage/logs', 'storage/cache', 'storage/tmp'] as $dir) {
            $path = storage_path(substr($dir, 8));
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $writable = is_dir($path) && is_writable($path);
            $checks['Escritura ' . $dir] = ['ok' => $writable, 'detail' => $writable ? 'Permitida' : 'Sin permiso'];
        }
        $checks['Archivo .env'] = ['ok' => is_file(base_path('.env')), 'detail' => is_file(base_path('.env')) ? 'Presente' : 'Debe existir (copie .env.example)'];
        return $checks;
    }

    private function databaseConfigured(): bool
    {
        $ping = Database::ping();
        if (!$ping['ok']) {
            return false;
        }
        try {
            Database::selectOne('SELECT id FROM institutions LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function writeEnv(array $data): void
    {
        $path = base_path('.env');
        $content = is_file($path) ? (string) file_get_contents($path) : (string) file_get_contents(base_path('.env.example'));
        $replacements = [
            'APP_URL' => $data['app_url'],
            'APP_NAME' => $data['app_name'],
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'],
        ];
        if (!preg_match('/^APP_KEY=.+$/m', $content)) {
            $replacements['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));
        }
        foreach ($replacements as $key => $value) {
            $escaped = str_replace('"', '', (string) $value);
            if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
                $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $escaped, $content);
            } else {
                $content .= "\n{$key}={$escaped}";
            }
        }
        file_put_contents($path, rtrim($content) . "\n");
        @chmod($path, 0640);
    }
}
