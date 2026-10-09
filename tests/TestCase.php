<?php

declare(strict_types=1);

namespace Tests;

abstract class TestCase
{
    private int $assertionCount = 0;
    private ?string $expectedException = null;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function getAssertionCount(): int
    {
        return $this->assertionCount;
    }

    public function expectException(string $exceptionClass): void
    {
        $this->expectedException = $exceptionClass;
    }

    public function getExpectedException(): ?string
    {
        return $this->expectedException;
    }

    protected function assertTrue(mixed $condition, string $message = ''): void
    {
        $this->assertionCount++;
        if ($condition !== true) {
            throw new \AssertionError($message !== '' ? $message : 'Falló assertTrue: el valor no es true');
        }
    }

    protected function assertFalse(mixed $condition, string $message = ''): void
    {
        $this->assertionCount++;
        if ($condition !== false) {
            throw new \AssertionError($message !== '' ? $message : 'Falló assertFalse: el valor no es false');
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($expected != $actual) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                'Esperado: ' . var_export($expected, true) . PHP_EOL .
                'Obtenido: ' . var_export($actual, true)
            );
        }
    }

    protected function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($expected == $actual) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                'Esperado que NO fuera igual: ' . var_export($expected, true) . PHP_EOL .
                'Obtenido: ' . var_export($actual, true)
            );
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($expected !== $actual) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                'Esperado (idéntico): ' . var_export($expected, true) . PHP_EOL .
                'Obtenido: ' . var_export($actual, true)
            );
        }
    }

    protected function assertNull(mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($actual !== null) {
            throw new \AssertionError($message !== '' ? $message : 'Esperado null, obtenido: ' . var_export($actual, true));
        }
    }

    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($actual === null) {
            throw new \AssertionError($message !== '' ? $message : 'Esperado valor no nulo.');
        }
    }

    protected function assertNotEmpty(mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if (empty($actual)) {
            throw new \AssertionError($message !== '' ? $message : 'Esperado valor no vacío.');
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertionCount++;
        if (!str_contains($haystack, $needle)) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                "No se encontró '{$needle}' dentro de la cadena."
            );
        }
    }

    protected function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertionCount++;
        if (str_contains($haystack, $needle)) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                "Se encontró '{$needle}' dentro de la cadena, pero no debía estar presente."
            );
        }
    }

    protected function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        $this->assertionCount++;
        if (!preg_match($pattern, $string)) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                "La cadena no coincide con el patrón '{$pattern}'."
            );
        }
    }

    protected function assertCount(int $expectedCount, mixed $countable, string $message = ''): void
    {
        $this->assertionCount++;
        $actual = is_countable($countable) ? count($countable) : 0;
        if ($expectedCount !== $actual) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                "Conteo esperado: {$expectedCount}, obtenido: {$actual}."
            );
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertionCount++;
        if (!array_key_exists($key, $array)) {
            throw new \AssertionError(
                ($message !== '' ? $message . PHP_EOL : '') .
                "Clave '{$key}' no existe en el array."
            );
        }
    }
}
