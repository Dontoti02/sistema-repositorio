<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Database;
use App\Core\TenantContext;
use App\Services\BackupService;
use App\Services\HealthService;
use App\Services\IntegrityService;
use App\Services\NotificationService;
use App\Services\SchedulerService;
use App\Support\Migrator;

/**
 * Kernel de comandos CLI. Solo registra comandos realmente implementados.
 */
final class Kernel
{
    /** @var array<string,string> */
    private array $commands = [
        'list' => 'Lista los comandos disponibles',
        'migrate' => 'Ejecuta las migraciones pendientes',
        'migrate:status' => 'Muestra el estado de las migraciones',
        'db:seed' => 'Instala catálogos base (roles, permisos, licencias) de forma idempotente',
        'db:seed --class=DemoSeeder' => 'Carga datos de demostración ficticios (solo desarrollo)',
        'user:create' => 'Crea un usuario: user:create email="..." password="..." name="..." role=institution_admin',
        'queue:work --once' => 'Procesa un trabajo pendiente de la cola',
        'queue:work' => 'Procesa la cola hasta agotar trabajos (con límite)',
        'scheduled:run' => 'Ejecuta tareas programadas (embargos, cola, agregados)',
        'repository:reindex' => 'Regenera agregados de búsqueda diarios',
        'repository:check-integrity' => 'Verifica checksums SHA-256 de archivos',
        'backups:create' => 'Crea un respaldo (BD + manifiesto)',
        'backups:verify' => 'Verifica la integridad del último respaldo',
        'health:check' => 'Informe de salud del sistema',
        'test' => 'Ejecuta la batería de pruebas del proyecto',
    ];

    /**
     * @param array<int,string> $argv
     */
    public function handle(array $argv): int
    {
        $name = $argv[1] ?? 'list';
        $options = $this->parseOptions(array_slice($argv, 2));

        try {
            return match ($name) {
                'list' => $this->list(),
                'migrate' => $this->migrate(),
                'migrate:status' => $this->migrateStatus(),
                'db:seed' => $this->seed($options),
                'user:create' => $this->createUser($options),
                'queue:work' => $this->queueWork($options),
                'queue:work --once' => $this->queueWork(['once' => true]),
                'scheduled:run' => $this->scheduledRun(),
                'repository:reindex' => $this->reindex(),
                'repository:check-integrity' => $this->checkIntegrity(),
                'backups:create' => $this->backupsCreate(),
                'backups:verify' => $this->backupsVerify(),
                'health:check' => $this->health(),
                'test' => $this->test(),
                default => $this->unknown($name),
            };
        } catch (\Throwable $e) {
            fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
            return 1;
        }
    }

    /** @return array<string,mixed> */
    private function parseOptions(array $args): array
    {
        $options = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$k, $v] = explode('=', $arg, 2);
                    $options[$k] = $v;
                } else {
                    $options[$arg] = true;
                }
            } elseif (str_contains($arg, '=')) {
                [$k, $v] = explode('=', $arg, 2);
                $options[$k] = $v;
            }
        }
        return $options;
    }

    private function list(): int
    {
        echo "Comandos disponibles:\n\n";
        foreach ($this->commands as $cmd => $desc) {
            printf("  %-40s %s\n", $cmd, $desc);
        }
        echo "\nUso: php bin/console <comando>\n";
        return 0;
    }

    private function unknown(string $name): int
    {
        fwrite(STDERR, "Comando desconocido: {$name}. Ejecute: php bin/console list\n");
        return 1;
    }

    private function migrate(): int
    {
        $migrator = new Migrator();
        $applied = $migrator->migrate();
        if ($applied === []) {
            echo "Sin migraciones pendientes. Versión: " . $migrator->version() . "\n";
        } else {
            foreach ($applied as $m) {
                echo "Migrada: {$m}\n";
            }
            echo count($applied) . " migración(es) aplicadas. Versión: " . $migrator->version() . "\n";
        }
        return 0;
    }

    private function migrateStatus(): int
    {
        $migrator = new Migrator();
        $ran = $migrator->ran();
        $files = glob(base_path('database/migrations/*.php')) ?: [];
        sort($files);
        foreach ($files as $file) {
            $name = basename($file, '.php');
            printf("  [%s] %s\n", in_array($name, $ran, true) ? 'OK' : '  ', $name);
        }
        echo "Versión actual: " . $migrator->version() . "\n";
        return 0;
    }

    /** @param array<string,mixed> $options */
    private function seed(array $options): int
    {
        $class = (string) ($options['class'] ?? 'BaseCatalogsSeeder');
        $file = base_path('database/seeders/' . $class . '.php');
        if (!is_file($file)) {
            fwrite(STDERR, "Seeder no encontrado: {$class}\n");
            return 1;
        }
        $seeder = require $file;
        if (!is_callable($seeder)) {
            fwrite(STDERR, "Seeder inválido: {$class} (debe retornar callable)\n");
            return 1;
        }
        $result = $seeder();
        echo $class . ': ' . (is_string($result) ? $result : 'completado') . "\n";
        return 0;
    }

    /** @param array<string,mixed> $options */
    private function createUser(array $options): int
    {
        $email = (string) ($options['email'] ?? '');
        $password = (string) ($options['password'] ?? '');
        $name = (string) ($options['name'] ?? '');
        $role = (string) ($options['role'] ?? 'institution_admin');
        if ($email === '' || $password === '' || $name === '') {
            fwrite(STDERR, "Uso: user:create email=\"a@b.c\" password=\"...\" name=\"...\" role=institution_admin\n");
            return 1;
        }
        if (strlen($password) < 10) {
            fwrite(STDERR, "La contraseña debe tener al menos 10 caracteres.\n");
            return 1;
        }
        $service = new \App\Services\UserService();
        $user = $service->create($name, $email, $password, $role, TenantContext::institutionId(), force: true);
        echo "Usuario #{$user['id']} creado: {$user['email']}\n";
        return 0;
    }

    /** @param array<string,mixed> $options */
    private function queueWork(array $options): int
    {
        $service = new NotificationService();
        $once = isset($options['once']);
        $max = $once ? 1 : 50;
        $processed = $service->processQueue($max);
        echo "Trabajos procesados: {$processed}\n";
        return 0;
    }

    private function scheduledRun(): int
    {
        $service = new SchedulerService();
        $report = $service->runAll();
        foreach ($report as $task => $result) {
            echo "{$task}: {$result}\n";
        }
        return 0;
    }

    private function reindex(): int
    {
        $service = new \App\Services\StatsService();
        $n = $service->rebuildDailyAggregates();
        echo "Agregados diarios reconstruidos: {$n}\n";
        return 0;
    }

    private function checkIntegrity(): int
    {
        $service = new IntegrityService();
        $report = $service->checkAll();
        echo "Archivos verificados: {$report['checked']}\n";
        echo "Correctos: {$report['ok']} | Alterados: {$report['mismatch']} | Faltantes: {$report['missing']}\n";
        return ($report['mismatch'] + $report['missing']) > 0 ? 2 : 0;
    }

    private function backupsCreate(): int
    {
        $service = new BackupService();
        $result = $service->create();
        echo "Respaldo: {$result['path']} ({$result['size_bytes']} bytes)\n";
        return 0;
    }

    private function backupsVerify(): int
    {
        $service = new BackupService();
        $result = $service->verifyLatest();
        echo ($result['ok'] ? 'VERIFICADO' : 'FALLÓ') . ": {$result['path']}\n";
        foreach ($result['details'] as $line) {
            echo "  - {$line}\n";
        }
        return $result['ok'] ? 0 : 1;
    }

    private function health(): int
    {
        $service = new HealthService();
        $report = $service->report();
        echo "SALUD DEL SISTEMA — " . $report['status'] . "\n";
        foreach ($report['checks'] as $name => $check) {
            printf("  %-28s %-10s %s\n", $name, $check['status'], $check['detail']);
        }
        return $report['status'] === 'ok' ? 0 : 2;
    }

    private function test(): int
    {
        $phpunit = base_path('vendor/bin/phpunit');
        if (is_file($phpunit)) {
            passthru(PHP_BINARY . ' ' . escapeshellarg($phpunit), $code);
            return (int) $code;
        }
        // Runner propio sin dependencias.
        $runnerFile = base_path('tests/StandaloneRunner.php');
        if (is_file($runnerFile)) {
            if (is_file(base_path('tests/TestCase.php'))) {
                require_once base_path('tests/TestCase.php');
            }
            require_once $runnerFile;
        }
        $runner = new \Tests\StandaloneRunner();
        return $runner->run();
    }
}
