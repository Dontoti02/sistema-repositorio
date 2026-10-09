<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TenantContext;

/**
 * Exportación de citas: APA, BibTeX y RIS.
 * Escapa correctamente el contenido; advierte si faltan campos.
 */
final class CitationService
{
    /**
     * @param array<string,mixed> $item Fila con title, authors, issued_at, public_id...
     * @return array{format:string, content:string, warnings:array<int,string>}
     */
    public function build(string $format, array $item): array
    {
        $authors = $this->authors((int) $item['id']);
        $warnings = [];
        if ($authors === []) {
            $warnings[] = 'Faltan autores: la cita puede estar incompleta.';
        }
        if (empty($item['issued_at'])) {
            $warnings[] = 'Falta la fecha de publicación.';
        }
        if (empty($item['abstract'])) {
            $warnings[] = 'Falta el resumen.';
        }
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            $warnings[] = 'Falta el título.';
        }
        $year = !empty($item['issued_at']) ? substr((string) $item['issued_at'], 0, 4) : 's. f.';
        $uri = url('/recursos/' . $item['public_id']);

        return match ($format) {
            'bibtex' => ['format' => 'BibTeX', 'content' => $this->bibtex($item, $authors, $year, $uri), 'warnings' => $warnings],
            'ris' => ['format' => 'RIS', 'content' => $this->ris($item, $authors, $year), 'warnings' => $warnings],
            default => ['format' => 'APA 7', 'content' => $this->apa($item, $authors, $year, $uri), 'warnings' => $warnings],
        };
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function authors(int $itemId): array
    {
        return Database::select(
            'SELECT full_name, author_role, orcid FROM item_authors WHERE item_id = ? ORDER BY position ASC',
            [$itemId]
        );
    }

    private function institutionName(): string
    {
        return (string) (TenantContext::institution()['name'] ?? '');
    }

    /**
     * @param array<int,array<string,string>> $authors
     * @param array<string,mixed> $item
     */
    private function apa(array $item, array $authors, string $year, string $uri): string
    {
        $names = [];
        foreach ($authors as $a) {
            $names[] = $this->apaName((string) $a['full_name']);
        }
        $authorStr = $names === []
            ? '[Sin autores]'
            : (count($names) === 1
                ? $names[0]
                : (count($names) <= 20
                    ? implode(', ', array_slice($names, 0, -1)) . ', & ' . end($names)
                    : implode(', ', array_slice($names, 0, 19)) . ', ... & ' . end($names)));
        $type = (string) ($item['document_type_name'] ?? 'Recurso');
        $line = "{$authorStr} ({$year}). {$item['title']}. {$type}. {$this->institutionName()}. {$uri}";
        return trim(preg_replace('/\s+/', ' ', $line) ?? $line);
    }

    private function apaName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        if (count($parts) < 2) {
            return $fullName;
        }
        $apellidos = array_pop($parts);
        $iniciales = array_map(static fn (string $n): string => mb_strtoupper(mb_substr($n, 0, 1)) . '.', $parts);
        return $apellidos . ', ' . implode(' ', $iniciales);
    }

    /**
     * @param array<string,mixed> $item
     * @param array<int,array<string,string>> $authors
     */
    private function bibtex(array $item, array $authors, string $year, string $uri): string
    {
        $key = preg_replace('/[^a-z0-9]/i', '', (string) ($authors[0]['full_name'] ?? 'sinautor')) . $year . substr((string) $item['public_id'], -3);
        $fields = [
            'title' => '{' . $this->texEscape((string) $item['title']) . '}',
            'author' => '{' . implode(' and ', array_map(static fn (array $a): string => str_replace(' ', ', ', $a['full_name']), $authors)) . '}',
            'year' => '{' . $year . '}',
            'type' => '{' . (string) ($item['document_type_name'] ?? 'Recurso') . '}',
            'url' => '{' . $uri . '}',
        ];
        if (!empty($item['abstract'])) {
            $fields['abstract'] = '{' . $this->texEscape((string) $item['abstract']) . '}';
        }
        if (!empty($item['language_code'])) {
            $fields['language'] = '{' . (string) $item['language_code'] . '}';
        }
        $body = '';
        foreach ($fields as $k => $v) {
            $body .= "  {$k} = {$v},\n";
        }
        return '@misc{' . $key . ",\n" . rtrim($body, ",\n") . "\n}";
    }

    /**
     * @param array<string,mixed> $item
     * @param array<int,array<string,string>> $authors
     */
    private function ris(array $item, array $authors, string $year): string
    {
        $lines = ['TY  - GEN'];
        foreach ($authors as $a) {
            $lines[] = 'AU  - ' . $a['full_name'];
        }
        $lines[] = 'TI  - ' . (string) $item['title'];
        if (!empty($item['abstract'])) {
            $lines[] = 'AB  - ' . str_replace(["\r\n", "\n"], ' ', (string) $item['abstract']);
        }
        $lines[] = 'PY  - ' . $year;
        $lines[] = 'PB  - ' . $this->institutionName();
        $lines[] = 'UR  - ' . url('/recursos/' . $item['public_id']);
        if (!empty($item['language_code'])) {
            $lines[] = 'LA  - ' . (string) $item['language_code'];
        }
        $lines[] = 'ER  - ';
        return implode("\n", $lines) . "\n";
    }

    private function texEscape(string $text): string
    {
        $search = ['\\', '&', '%', '$', '#', '_', '{', '}', '~', '^'];
        $replace = ['\\textbackslash{}', '\&', '\%', '\$', '\#', '\_', '\{', '\}', '\textasciitilde{}', '\textasciicircum{}'];
        return str_replace($search, $replace, $text);
    }
}
