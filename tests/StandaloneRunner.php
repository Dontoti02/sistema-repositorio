<?php

declare(strict_types=1);

namespace Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Runner autónomo de pruebas para el repositorio institucional.
 * Ejecuta suites de pruebas en tests/ sin necesidad de dependencias externas.
 */
final class StandaloneRunner
{
    private string $testDir;

    public function __construct(?string $testDir = null)
    {
        $this->testDir = $testDir ?? dirname(__DIR__) . '/tests';
    }

    public function run(): int
    {
        $startTime = microtime(true);
        $testFiles = $this->discoverTestFiles();

        if (empty($testFiles)) {
            echo "No se encontraron archivos de prueba en {$this->testDir}\n";
            return 0;
        }

        $passed = 0;
        $failed = 0;
        $totalAssertions = 0;
        $failures = [];

        echo "\n=== BATERÍA DE PRUEBAS — REPOSITORIO INSTITUCIONAL ===\n\n";

        foreach ($testFiles as $file) {
            require_once $file;
            $classes = $this->getClassesFromFile($file);

            foreach ($classes as $className) {
                if (!class_exists($className)) {
                    continue;
                }

                $refClass = new ReflectionClass($className);
                if ($refClass->isAbstract() || !$refClass->isSubclassOf(TestCase::class)) {
                    continue;
                }

                $methods = $refClass->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $methodName = $method->getName();
                    if (!str_starts_with($methodName, 'test')) {
                        continue;
                    }

                    /** @var TestCase $testInstance */
                    $testInstance = new $className();
                    $initialAssertions = $testInstance->getAssertionCount();

                    try {
                        // setUp
                        $testInstance->setUp();

                        $expectedEx = null;
                        try {
                            $testInstance->{$methodName}();
                            $expectedEx = $testInstance->getExpectedException();
                            if ($expectedEx !== null) {
                                throw new \AssertionError("Se esperaba la excepción {$expectedEx}, pero no fue lanzada.");
                            }
                        } catch (Throwable $e) {
                            $expectedEx = $testInstance->getExpectedException();
                            if ($expectedEx === null || !($e instanceof $expectedEx)) {
                                throw $e;
                            }
                        }

                        // tearDown
                        $testInstance->tearDown();

                        $assertionsRan = $testInstance->getAssertionCount() - $initialAssertions;
                        $totalAssertions += max(1, $assertionsRan);
                        $passed++;
                        echo "  [PASS] {$refClass->getShortName()}::{$methodName}\n";
                    } catch (Throwable $e) {
                        try {
                            $testInstance->tearDown();
                        } catch (Throwable) {
                            // Ignorar errores secundarios en tearDown tras falla
                        }
                        $failed++;
                        echo "  [FAIL] {$refClass->getShortName()}::{$methodName}\n";
                        $failures[] = [
                            'class' => $refClass->getShortName(),
                            'method' => $methodName,
                            'message' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ];
                    }
                }
            }
        }

        $elapsed = round(microtime(true) - $startTime, 3);
        $total = $passed + $failed;

        echo "\n------------------------------------------------------\n";
        if ($failed > 0) {
            echo "FALLOS REGISTRADOS ({$failed}):\n\n";
            foreach ($failures as $f) {
                echo "  • {$f['class']}::{$f['method']}\n";
                echo "    {$f['message']}\n";
                echo "    en {$f['file']}:{$f['line']}\n\n";
            }
            printf("RESULTADO: %d fallos, %d aprobadas de %d pruebas (%d aserciones) en %s seg.\n\n", $failed, $passed, $total, $totalAssertions, $elapsed);
            return 1;
        }

        printf("RESULTADO: Todas las pruebas pasaron (%d pruebas, %d aserciones) en %s seg.\n\n", $passed, $totalAssertions, $elapsed);
        return 0;
    }

    /**
     * @return array<int,string>
     */
    private function discoverTestFiles(): array
    {
        $files = [];
        if (!is_dir($this->testDir)) {
            return [];
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->testDir));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * @return array<int,string>
     */
    private function getClassesFromFile(string $file): array
    {
        $tokens = token_get_all(file_get_contents($file) ?: '');
        $classes = [];
        $namespace = '';
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $ns = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                    if (is_array($tokens[$j])) {
                        $ns .= $tokens[$j][1];
                    }
                }
                $namespace = trim($ns);
            }
            if ($tokens[$i][0] === T_CLASS && isset($tokens[$i + 2]) && $tokens[$i + 2][0] === T_STRING) {
                $className = $tokens[$i + 2][1];
                $classes[] = $namespace !== '' ? $namespace . '\\' . $className : $className;
            }
        }

        return $classes;
    }
}
