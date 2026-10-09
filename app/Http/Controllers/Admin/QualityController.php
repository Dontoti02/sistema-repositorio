<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;
use App\Services\AuditLogger;

/**
 * Análisis de calidad de recursos y metadatos (6.17).
 * Validadores dinámicos por recurso, con severidad y propuesta de corrección.
 * La puntuación (0-100) se persiste opcionalmente en items.quality_score.
 */
final class QualityController extends AdminController
{
    private const SEVERITIES = ['alta', 'media', 'baja'];

    public function index(Request $request): Response
    {
        $this->authorize('items.read_all');
        $statusFilter = $request->str('estado');
        $severityFilter = $request->str('severidad');

        $report = $this->analyze();

        $totals = ['alta' => 0, 'media' => 0, 'baja' => 0];
        foreach ($report as $row) {
            foreach ($row['findings'] as $f) {
                $totals[$f['sev']]++;
            }
        }

        if ($severityFilter !== '' && in_array($severityFilter, self::SEVERITIES, true)) {
            $keep = $severityFilter;
            $report = array_values(array_map(static function (array $row) use ($keep): array {
                $row['findings'] = array_values(array_filter(
                    $row['findings'],
                    static fn (array $f): bool => $f['sev'] === $keep
                ));
                return $row;
            }, $report));
        } else {
            $keep = null;
        }
        if ($statusFilter !== '') {
            $report = array_values(array_filter(
                $report,
                static fn (array $row): bool => $row['status'] === $statusFilter
            ));
        }
        if ($keep !== null) {
            $report = array_values(array_filter($report, static fn (array $row): bool => $row['findings'] !== []));
        }

        $withIssues = count(array_filter($report, static fn (array $r): bool => $r['findings'] !== []));

        return $this->view('admin/quality/index', [
            'pageTitle' => 'Calidad de recursos',
            'report' => $report,
            'totals' => $totals,
            'withIssues' => $withIssues,
            'statusFilter' => $statusFilter,
            'severityFilter' => $severityFilter,
            'totalItems' => count($report),
        ]);
    }

    /** POST /admin/calidad/recalcular — guarda items.quality_score. */
    public function recalculate(Request $request): Response
    {
        $this->authorize('items.read_all');
        $report = $this->analyze();
        $updated = 0;
        foreach ($report as $row) {
            Database::statement('UPDATE items SET quality_score = ? WHERE id = ?', [$row['score'], $row['item_id']]);
            $updated++;
        }
        AuditLogger::log('quality.recalculate', 'items', 0, null, "$updated recurso(s) puntuado(s)");
        $this->flash('success', "Puntuaciones recalculadas para $updated recurso(s).");
        return $this->redirect('/admin/calidad');
    }

    /**
     * Ejecuta los validadores sobre los recursos activos de la institución.
     *
     * @return array<int,array<string,mixed>>
     */
    private function analyze(): array
    {
        $instId = TenantContext::institutionId();

        $items = Database::select(
            'SELECT i.id, i.public_id, i.status, i.current_version_id, i.quality_score, i.embargo_until, i.slug
             FROM items i
             WHERE i.institution_id = ? AND i.deleted_at IS NULL
             ORDER BY i.id',
            [$instId]
        );
        if ($items === []) {
            return [];
        }

        $versions = [];
        foreach (Database::select(
            "SELECT id, item_id, title, abstract, language_code, issued_at FROM item_versions
             WHERE institution_id = ? AND id IN (
                 SELECT current_version_id FROM items WHERE institution_id = ? AND deleted_at IS NULL AND current_version_id IS NOT NULL
             )",
            [$instId, $instId]
        ) as $v) {
            $versions[(int) $v['id']] = $v;
        }

        $dupTitles = [];
        foreach (Database::select(
            "SELECT LOWER(v.title) AS t, COUNT(*) AS c
             FROM item_versions v
             JOIN items i ON i.current_version_id = v.id
             WHERE i.institution_id = ? AND i.deleted_at IS NULL
             GROUP BY LOWER(v.title) HAVING COUNT(*) > 1",
            [$instId]
        ) as $d) {
            $dupTitles[(string) $d['t']] = (int) $d['c'];
        }

        $authors = [];
        foreach (Database::select(
            "SELECT ia.item_id, ia.full_name, ia.orcid FROM item_authors ia
             JOIN items i ON i.id = ia.item_id AND i.deleted_at IS NULL
             WHERE ia.institution_id = ?",
            [$instId]
        ) as $a) {
            $authors[(int) $a['item_id']][] = $a;
        }

        $files = [];
        foreach (Database::select(
            "SELECT f.item_id, f.is_primary, f.scan_status FROM files f
             JOIN items i ON i.id = f.item_id AND i.deleted_at IS NULL
             WHERE f.institution_id = ? AND f.deleted_at IS NULL",
            [$instId]
        ) as $f) {
            $files[(int) $f['item_id']][] = $f;
        }

        $embargoes = [];
        foreach (Database::select(
            "SELECT e.item_id, e.embargo_until, e.status FROM embargoes e
             JOIN items i ON i.id = e.item_id AND i.deleted_at IS NULL
             WHERE e.institution_id = ?",
            [$instId]
        ) as $e) {
            $embargoes[(int) $e['item_id']][] = $e;
        }

        $identifiers = [];
        foreach (Database::select(
            "SELECT ii.item_id, ii.identifier_type, ii.identifier_value FROM item_identifiers ii
             JOIN items i ON i.id = ii.item_id AND i.deleted_at IS NULL
             WHERE ii.institution_id = ?",
            [$instId]
        ) as $idRow) {
            $identifiers[(int) $idRow['item_id']][] = $idRow;
        }

        $keywordCounts = [];
        foreach (Database::select(
            "SELECT ik.item_id, COUNT(*) AS c FROM item_keywords ik
             JOIN items i ON i.id = ik.item_id AND i.deleted_at IS NULL
             WHERE i.institution_id = ? GROUP BY ik.item_id",
            [$instId]
        ) as $k) {
            $keywordCounts[(int) $k['item_id']] = (int) $k['c'];
        }

        $langTerms = [];
        foreach (Database::select(
            "SELECT vt.term_value FROM vocabulary_terms vt
             JOIN controlled_vocabularies cv ON cv.id = vt.vocabulary_id
             WHERE cv.slug = 'languages' AND vt.is_active = 1",
            []
        ) as $lt) {
            $langTerms[(string) $lt['term_value']] = true;
        }

        $report = [];
        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $version = $versions[(int) $item['current_version_id']] ?? null;
            $findings = [];

            $title = trim((string) ($version['title'] ?? ''));
            if ($title === '') {
                $findings[] = $this->finding('title.empty', 'alta', 'El título está vacío.', 'Complete el título con un descriptor claro del recurso.');
            } else {
                $tLower = mb_strtolower($title);
                if (isset($dupTitles[$tLower])) {
                    $findings[] = $this->finding('title.duplicate', 'alta', 'Existe otro recurso de la institución con el mismo título (' . $dupTitles[$tLower] . ' coincidencias).', 'Verifique si es un duplicado: corrija el título o fusione los recursos.');
                }
            }

            $abstract = trim((string) ($version['abstract'] ?? ''));
            if ($abstract === '') {
                $findings[] = $this->finding('abstract.missing', 'media', 'El recurso no tiene resumen.', 'Redacte un resumen de al menos 50 caracteres.');
            } elseif (mb_strlen($abstract) < 50) {
                $findings[] = $this->finding('abstract.short', 'baja', 'El resumen es muy corto (' . mb_strlen($abstract) . ' caracteres).', 'Amplíe el resumen a al menos 50 caracteres.');
            }

            $lang = trim((string) ($version['language_code'] ?? ''));
            if ($lang === '') {
                $findings[] = $this->finding('language.missing', 'alta', 'No se indicó el idioma.', 'Asigne el idioma del recurso (ej. es o en).');
            } else {
                $base = strtolower(explode('-', $lang)[0]);
                if (!isset($langTerms[$lang]) && !isset($langTerms[$base])) {
                    $findings[] = $this->finding('language.invalid', 'alta', "Idioma «{$lang}» no está en el vocabulario controlado.", 'Use un código del vocabulario de idiomas (ej. es, en, qu, ay).');
                }
            }

            $issued = (string) ($version['issued_at'] ?? '');
            if ($issued === '') {
                $findings[] = $this->finding('issued.missing', 'media', 'Falta la fecha de emisión.', 'Indique la fecha de emisión (AAAA-MM-DD).');
            } else {
                if ($issued > gmdate('Y-m-d')) {
                    $findings[] = $this->finding('issued.future', 'alta', "La fecha de emisión ($issued) es futura.", 'Corrija la fecha: no puede ser posterior a hoy.');
                }
                if ($issued < '1900-01-01') {
                    $findings[] = $this->finding('issued.old', 'baja', "La fecha de emisión ($issued) es anterior a 1900.", 'Verifique la fecha informada.');
                }
            }

            $itemAuthors = $authors[$itemId] ?? [];
            if ($itemAuthors === []) {
                $findings[] = $this->finding('authors.missing', 'media', 'El recurso no tiene autores.', 'Agregue al menos un autor con su nombre completo.');
            } else {
                $seen = [];
                foreach ($itemAuthors as $a) {
                    $nKey = mb_strtolower(trim((string) $a['full_name']));
                    if ($nKey !== '') {
                        if (isset($seen[$nKey])) {
                            $findings[] = $this->finding('authors.duplicate', 'alta', 'Autor duplicado: «' . trim((string) $a['full_name']) . '».', 'Elimine la entrada duplicada de autores.');
                            break;
                        }
                        $seen[$nKey] = true;
                    }
                    $orcid = trim((string) ($a['orcid'] ?? ''));
                    if ($orcid !== '' && !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
                        $findings[] = $this->finding('authors.orcid.invalid', 'media', "ORCID mal formado: «{$orcid}».", 'Use el formato 0000-0000-0000-0000.');
                        break;
                    }
                }
            }

            $itemFiles = $files[$itemId] ?? [];
            if ($itemFiles === []) {
                $findings[] = $this->finding('files.missing', 'alta', 'El recurso no tiene archivos.', 'Adjunte el archivo principal del documento.');
            } else {
                $hasPrimary = false;
                foreach ($itemFiles as $f) {
                    if ((int) $f['is_primary'] === 1) {
                        $hasPrimary = true;
                    }
                    if (!in_array((string) $f['scan_status'], ['clean', 'ok'], true)) {
                        $findings[] = $this->finding('files.scan', 'media', 'Archivo pendiente de análisis antivirus (estado: ' . $f['scan_status'] . ').', 'Ejecute el análisis antivirus antes de publicar.');
                        break;
                    }
                }
                if (!$hasPrimary) {
                    $findings[] = $this->finding('files.primary.missing', 'baja', 'Ningún archivo está marcado como principal.', 'Marque un archivo como principal para las descargas.');
                }
            }

            $embargoUntil = $item['embargo_until'] !== null ? (string) $item['embargo_until'] : '';
            $activeEmbargoes = array_filter($embargoes[$itemId] ?? [], static fn (array $e): bool => $e['status'] === 'active');
            if ($embargoUntil !== '' && $embargoUntil < gmdate('Y-m-d H:i:s')) {
                if ((string) $item['status'] !== 'PUBLISHED') {
                    $findings[] = $this->finding('embargo.expired', 'alta', "El embargo venció el $embargoUntil pero el recurso no está publicado.", 'Publique el recurso o extienda el embargo.');
                } else {
                    $findings[] = $this->finding('embargo.expired', 'media', "El embargo venció el $embargoUntil.", 'Libere el embargo para dejar el recurso en acceso abierto.');
                }
            }
            if ($embargoUntil === '' && $activeEmbargoes !== []) {
                $findings[] = $this->finding('embargo.inconsistent', 'media', 'Hay un embargo activo sin fecha en el recurso.', 'Sincronice la fecha de embargo en la ficha del recurso.');
            }
            if ($embargoUntil !== '' && $activeEmbargoes === []) {
                $findings[] = $this->finding('embargo.inconsistent', 'media', 'El recurso declara embargo sin registro activo en la tabla de embargos.', 'Registre el embargo o quite la fecha de la ficha.');
            }

            foreach ($identifiers[$itemId] ?? [] as $ident) {
                if ($ident['identifier_type'] === 'doi') {
                    $value = trim((string) $ident['identifier_value']);
                    if (!preg_match('/^10\.\d{4,9}\/\S{1,80}$/i', $value)) {
                        $findings[] = $this->finding('doi.invalid', 'media', "DOI mal formado: «{$value}».", 'Corrija el DOI con el formato 10.xxxx/xxxxx.');
                        break;
                    }
                }
            }

            if (($keywordCounts[$itemId] ?? 0) === 0) {
                $findings[] = $this->finding('keywords.missing', 'baja', 'El recurso no tiene palabras clave.', 'Agregue entre 3 y 10 palabras clave.');
            }

            if ((string) $item['status'] === 'PUBLISHED' && (string) ($item['slug'] ?? '') === '') {
                $findings[] = $this->finding('slug.missing', 'media', 'Recurso publicado sin URL canónica (slug).', 'Guarde el recurso nuevamente para regenerar la URL.');
            }

            $score = 100;
            foreach ($findings as $f) {
                $score -= $f['sev'] === 'alta' ? 15 : ($f['sev'] === 'media' ? 8 : 3);
            }
            $score = max(0, min(100, $score));

            $report[] = [
                'item_id' => $itemId,
                'public_id' => (string) $item['public_id'],
                'title' => $title !== '' ? $title : '(sin título)',
                'status' => (string) $item['status'],
                'score' => $score,
                'stored_score' => (int) $item['quality_score'],
                'findings' => $findings,
            ];
        }

        return $report;
    }

    /** @return array{code:string,sev:string,msg:string,fix:string} */
    private function finding(string $code, string $sev, string $msg, string $fix): array
    {
        return ['code' => $code, 'sev' => $sev, 'msg' => $msg, 'fix' => $fix];
    }
}
