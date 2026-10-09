<?php

declare(strict_types=1);

namespace App\Http\Controllers\Oai;

use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\TenantContext;

/**
 * Proveedor OAI-PMH 2.0 (7.4): verbos, oai_dc, sets jerárquicos por
 * colección, paginación con resumptionToken firmado y temporal,
 * solo recursos PUBLISHED habilitados para cosecha.
 */
final class OaiController extends Controller
{
    private const VERBS = ['Identify', 'ListMetadataFormats', 'ListSets', 'ListIdentifiers', 'ListRecords', 'GetRecord'];

    private const GRANULARITY = 'YYYY-MM-DDThh:mm:ssZ';

    private const BATCH = 50;

    private const TOKEN_TTL = 3600;

    public function handle(Request $request): Response
    {
        if (!(bool) config('repository.oai_enabled', true)) {
            throw new HttpException(404, 'Proveedor OAI-PMH deshabilitado.');
        }

        $args = [];
        foreach (['verb', 'identifier', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'] as $name) {
            $value = trim((string) $request->query($name, ''));
            if ($value !== '') {
                $args[$name] = $value;
            }
        }

        $verb = $args['verb'] ?? '';
        if ($verb === '' || !in_array($verb, self::VERBS, true)) {
            return $this->error('badVerb', 'El parámetro verb no es válido o falta.', $args);
        }

        foreach (array_keys($request->queryAll()) as $key) {
            if (!is_string($key) || !in_array($key, ['verb', 'identifier', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'], true)) {
                return $this->error('badArgument', 'Parámetro no reconocido.', $args);
            }
        }

        $allowed = [
            'Identify' => ['verb'],
            'ListMetadataFormats' => ['verb', 'identifier'],
            'ListSets' => ['verb'],
            'GetRecord' => ['verb', 'identifier', 'metadataPrefix'],
            'ListIdentifiers' => ['verb', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'],
            'ListRecords' => ['verb', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'],
        ];
        foreach (array_keys($args) as $key) {
            if (!in_array($key, $allowed[$verb], true)) {
                return $this->error('badArgument', 'El parámetro no es válido para el verbo ' . $verb . '.', $args);
            }
        }

        $result = match ($verb) {
            'Identify' => $this->identify($args),
            'ListMetadataFormats' => $this->listMetadataFormats($args),
            'ListSets' => $this->listSets($args),
            'ListIdentifiers' => $this->listRecords($args, false),
            'ListRecords' => $this->listRecords($args, true),
            'GetRecord' => $this->getRecord($args),
            default => ['error' => ['code' => 'badVerb', 'message' => 'Verbo no soportado.']],
        };

        if (isset($result['error'])) {
            return $this->error((string) $result['error']['code'], (string) $result['error']['message'], $args);
        }

        return Response::xml($this->wrap($args, (string) $result['xml']));
    }

    /** @param array<string,string> $args */
    private function identify(array $args): array
    {
        if (count($args) > 1) {
            return ['error' => ['code' => 'badArgument', 'message' => 'Identify no acepta argumentos adicionales.']];
        }
        $earliest = (string) (Database::selectOne(
            "SELECT COALESCE(MIN(i.updated_at), UTC_TIMESTAMP()) AS d FROM items i
             WHERE i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL",
            [TenantContext::institutionId()]
        )['d'] ?? gmdate('Y-m-d H:i:s'));

        $xml = '<Identify>'
            . '<repositoryName>' . $this->h($this->repoName()) . '</repositoryName>'
            . '<baseURL>' . $this->h(url('/oai')) . '</baseURL>'
            . '<protocolVersion>2.0</protocolVersion>'
            . '<adminEmail>' . $this->h($this->adminEmail()) . '</adminEmail>'
            . '<earliestDatestamp>' . $this->h($this->datestamp($earliest)) . '</earliestDatestamp>'
            . '<deletedRecord>transient</deletedRecord>'
            . '<granularity>' . self::GRANULARITY . '</granularity>'
            . '<compression>none</compression>'
            . '</Identify>';

        return ['xml' => $xml];
    }

    /** @param array<string,string> $args */
    private function listMetadataFormats(array $args): array
    {
        if (isset($args['identifier'])) {
            $row = $this->recordByIdentifier($args['identifier']);
            if ($row === null) {
                return ['error' => ['code' => 'idDoesNotExist', 'message' => 'Identificador no reconocido.']];
            }
        }
        if (isset($args['identifier']) && count($args) > 2) {
            return ['error' => ['code' => 'badArgument', 'message' => 'Argumentos no soportados.']];
        }
        if (!isset($args['identifier']) && count($args) > 1) {
            return ['error' => ['code' => 'badArgument', 'message' => 'Argumentos no soportados.']];
        }

        $xml = '<ListMetadataFormats>'
            . '<metadataFormat><metadataPrefix>oai_dc</metadataPrefix>'
            . '<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>'
            . '<namespace>http://www.openarchives.org/OAI/2.0/oai_dc/</namespace>'
            . '</metadataFormat>'
            . '</ListMetadataFormats>';

        return ['xml' => $xml];
    }

    /** @param array<string,string> $args */
    private function listSets(array $args): array
    {
        if (count($args) > 1) {
            return ['error' => ['code' => 'badArgument', 'message' => 'ListSets no acepta argumentos adicionales.']];
        }
        $sets = $this->sets();
        if ($sets === []) {
            return ['error' => ['code' => 'noSetHierarchy', 'message' => 'El repositorio no ofrece conjuntos.']];
        }
        $xml = '<ListSets>';
        foreach ($sets as $set) {
            $xml .= '<set><setSpec>' . $this->h($set['spec']) . '</setSpec>'
                . '<setName>' . $this->h($set['name']) . '</setName>'
                . '<setDescription><oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"'
                . ' xmlns:dc="http://purl.org/dc/elements/1.1/">'
                . '<dc:description>' . $this->h($set['description']) . '</dc:description>'
                . '</oai_dc:dc></setDescription>'
                . '</set>';
        }
        $xml .= '</ListSets>';

        return ['xml' => $xml];
    }

    /** @param array<string,string> $args */
    private function listRecords(array $args, bool $withMetadata): array
    {
        $verb = $withMetadata ? 'ListRecords' : 'ListIdentifiers';
        $token = $args['resumptionToken'] ?? null;
        $offset = 0;
        $filters = [];

        if ($token !== null) {
            if (count($args) > 2) {
                return ['error' => ['code' => 'badArgument', 'message' => 'resumptionToken no admite otros parámetros.']];
            }
            $decoded = $this->decodeToken($token);
            if ($decoded === null) {
                return ['error' => ['code' => 'badResumptionToken', 'message' => 'Token inválido o vencido.']];
            }
            if (($decoded['v'] ?? '') !== '1') {
                return ['error' => ['code' => 'badResumptionToken', 'message' => 'Token inválido o vencido.']];
            }
            $offset = max(0, (int) ($decoded['o'] ?? 0));
            if (($decoded['p'] ?? '') !== 'oai_dc') {
                return ['error' => ['code' => 'badResumptionToken', 'message' => 'Token inválido o vencido.']];
            }
            foreach (['f' => 'from', 'u' => 'until', 's' => 'set'] as $key => $name) {
                if (($decoded[$key] ?? '') !== '') {
                    $filters[$name] = (string) $decoded[$key];
                }
            }
        } else {
            if (count($args) > 5) {
                return ['error' => ['code' => 'badArgument', 'message' => 'Argumentos no soportados.']];
            }
            $prefix = $args['metadataPrefix'] ?? '';
            if ($prefix === '') {
                return ['error' => ['code' => 'badArgument', 'message' => 'Falta metadataPrefix.']];
            }
            if ($prefix !== 'oai_dc') {
                return ['error' => ['code' => 'cannotDisseminateFormat', 'message' => 'metadataPrefix no soportado; use oai_dc.']];
            }
            foreach (['from' => 'from', 'until' => 'until'] as $name) {
                if (isset($args[$name])) {
                    if (!$this->validTimestamp($args[$name])) {
                        return ['error' => ['code' => 'badArgument', 'message' => "Parámetro «{$name}» inválido: use AAAA-MM-DD o AAAA-MM-DDThh:mm:ssZ."]];
                    }
                    $filters[$name] = $args[$name];
                }
            }
            if (isset($args['set'])) {
                if ($this->findSet($args['set']) === null) {
                    return ['error' => ['code' => 'noSetHierarchy', 'message' => 'Conjunto no reconocido.']];
                }
                $filters['set'] = $args['set'];
            }
        }

        $instId = TenantContext::institutionId();
        $where = "i.institution_id = ? AND i.status = 'PUBLISHED' AND i.deleted_at IS NULL
                  AND c.deleted_at IS NULL AND cm.deleted_at IS NULL
                  AND c.is_public = 1 AND cm.is_public = 1";
        $params = [$instId];
        if (isset($filters['from'])) {
            $where .= ' AND i.updated_at >= ?';
            $params[] = $this->toSqlDate($filters['from']);
        }
        if (isset($filters['until'])) {
            $where .= ' AND i.updated_at <= ?';
            $params[] = str_contains($filters['until'], 'T')
                ? $this->toSqlDate($filters['until'])
                : $filters['until'] . ' 23:59:59';
        }
        if (isset($filters['set'])) {
            $where .= ' AND c.slug = ?';
            $params[] = $this->setCollectionSlug($filters['set']);
        }

        $total = (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM items i
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             WHERE ' . $where,
            $params
        )['c'] ?? 0);

        if ($total === 0) {
            return ['error' => ['code' => 'noRecordsMatch', 'message' => 'Sin registros que coincidan con los criterios.']];
        }

        $rows = Database::select(
            'SELECT i.id AS item_id, i.public_id, i.updated_at, v.title, v.abstract, v.language_code, v.issued_at,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             WHERE ' . $where . '
             ORDER BY i.updated_at ASC, i.id ASC
             LIMIT ' . (int) $offset . ', ' . (int) (self::BATCH + 1),
            $params
        );

        $hasMore = count($rows) > self::BATCH;
        if ($hasMore) {
            $rows = array_slice($rows, 0, self::BATCH);
        }

        $xml = '<' . $verb . '>';
        foreach ($rows as $row) {
            $xml .= $withMetadata ? $this->recordXml($row) : $this->headerXml($row);
        }
        if ($hasMore) {
            $tokenOut = $this->encodeToken([
                'v' => '1',
                'o' => $offset + self::BATCH,
                'p' => 'oai_dc',
                'f' => $filters['from'] ?? '',
                'u' => $filters['until'] ?? '',
                's' => $filters['set'] ?? '',
                'e' => time() + self::TOKEN_TTL,
            ]);
            $xml .= '<resumptionToken cursor="' . $offset . '" completeListSize="' . $total . '"'
                . ' expirationDate="' . $this->h(gmdate('Y-m-d\TH:i:s\Z', time() + self::TOKEN_TTL)) . '">'
                . $this->h($tokenOut) . '</resumptionToken>';
        }
        $xml .= '</' . $verb . '>';

        return ['xml' => $xml];
    }

    /** @param array<string,string> $args */
    private function getRecord(array $args): array
    {
        if (!isset($args['identifier'], $args['metadataPrefix'])) {
            return ['error' => ['code' => 'badArgument', 'message' => 'GetRecord requiere identifier y metadataPrefix.']];
        }
        if (count($args) > 3) {
            return ['error' => ['code' => 'badArgument', 'message' => 'Argumentos no soportados.']];
        }
        if ($args['metadataPrefix'] !== 'oai_dc') {
            return ['error' => ['code' => 'cannotDisseminateFormat', 'message' => 'metadataPrefix no soportado; use oai_dc.']];
        }
        $row = $this->recordByIdentifier($args['identifier']);
        if ($row === null) {
            return ['error' => ['code' => 'idDoesNotExist', 'message' => 'Identificador no reconocido.']];
        }

        return ['xml' => '<GetRecord>' . $this->recordXml($row) . '</GetRecord>'];
    }

    /** @return array<string,mixed>|null */
    private function recordByIdentifier(string $identifier): ?array
    {
        $prefix = 'oai:' . $this->repoId() . ':';
        if (!str_starts_with($identifier, $prefix)) {
            return null;
        }
        $publicId = substr($identifier, strlen($prefix));
        if ($publicId === '' || str_contains($publicId, ':')) {
            return null;
        }
        $row = Database::selectOne(
            'SELECT i.id AS item_id, i.public_id, i.updated_at, v.title, v.abstract, v.language_code, v.issued_at,
                    c.name AS collection_name, c.slug AS collection_slug,
                    cm.name AS community_name, cm.slug AS community_slug,
                    dt.name AS document_type
             FROM items i
             JOIN item_versions v ON v.id = i.current_version_id
             JOIN collections c ON c.id = i.collection_id
             JOIN communities cm ON cm.id = c.community_id
             LEFT JOIN document_types dt ON dt.id = i.document_type_id
             WHERE i.institution_id = ? AND i.public_id = ? AND i.status = \'PUBLISHED\' AND i.deleted_at IS NULL
               AND c.deleted_at IS NULL AND cm.deleted_at IS NULL AND c.is_public = 1 AND cm.is_public = 1',
            [TenantContext::institutionId(), $publicId]
        );
        return $row;
    }

    /** @param array<string,mixed> $row */
    private function headerXml(array $row): string
    {
        return '<header><identifier>' . $this->h($this->oaiIdentifier((string) $row['public_id'])) . '</identifier>'
            . '<datestamp>' . $this->h($this->datestamp((string) $row['updated_at'])) . '</datestamp>'
            . '<setSpec>' . $this->h((string) $row['community_slug'] . ':' . (string) $row['collection_slug']) . '</setSpec>'
            . '</header>';
    }

    /** @param array<string,mixed> $row */
    private function recordXml(array $row): string
    {
        $dc = '<metadata><oai_dc:dc'
            . ' xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">';

        $dc .= '<dc:title>' . $this->h((string) $row['title']) . '</dc:title>';

        $authors = Database::select(
            'SELECT full_name FROM item_authors WHERE item_id = ? ORDER BY position',
            [(int) $row['item_id']]
        );
        foreach ($authors as $author) {
            $dc .= '<dc:creator>' . $this->h((string) $author['full_name']) . '</dc:creator>';
        }

        if ((string) ($row['abstract'] ?? '') !== '') {
            $dc .= '<dc:description>' . $this->h((string) $row['abstract']) . '</dc:description>';
        }
        if ((string) ($row['issued_at'] ?? '') !== '') {
            $dc .= '<dc:date>' . $this->h((string) $row['issued_at']) . '</dc:date>';
        }
        $dc .= '<dc:language>' . $this->h((string) $row['language_code']) . '</dc:language>';
        if ((string) ($row['document_type'] ?? '') !== '') {
            $dc .= '<dc:type>' . $this->h((string) $row['document_type']) . '</dc:type>';
        }
        $dc .= '<dc:publisher>' . $this->h($this->repoName()) . '</dc:publisher>';
        $dc .= '<dc:relation>' . $this->h((string) $row['community_name']) . '</dc:relation>';
        $dc .= '<dc:relation>' . $this->h((string) $row['collection_name']) . '</dc:relation>';
        $dc .= '<dc:identifier>' . $this->h(url('/recursos/' . $row['public_id'])) . '</dc:identifier>';
        $dc .= '<dc:identifier>' . $this->h($this->oaiIdentifier((string) $row['public_id'])) . '</dc:identifier>';
        $dc .= '<dc:rights>' . $this->h($this->rightsText((string) $row['public_id'])) . '</dc:rights>';

        $keywords = Database::select(
            'SELECT k.term FROM item_keywords ik
             JOIN keywords k ON k.id = ik.keyword_id
             WHERE ik.item_id = ? ORDER BY ik.position',
            [(int) $row['item_id']]
        );
        foreach ($keywords as $keyword) {
            $dc .= '<dc:subject>' . $this->h((string) $keyword['term']) . '</dc:subject>';
        }

        $dc .= '</oai_dc:dc></metadata>';

        return '<record>' . $this->headerXml($row) . $dc . '</record>';
    }

    private function rightsText(string $publicId): string
    {
        $item = Database::selectOne(
            'SELECT access_level, embargo_until FROM items WHERE public_id = ? AND institution_id = ?',
            [$publicId, TenantContext::institutionId()]
        );
        if ($item === null) {
            return 'Acceso abierto';
        }
        $level = (string) $item['access_level'];
        $text = match ($level) {
            'open' => 'Acceso abierto',
            'restricted' => 'Acceso restringido',
            'metadata_only' => 'Solo metadatos',
            default => 'Acceso: ' . $level,
        };
        if ($item['embargo_until'] !== null && (string) $item['embargo_until'] > gmdate('Y-m-d H:i:s')) {
            $text .= ' — embargo hasta ' . $this->datestamp((string) $item['embargo_until']);
        }
        return $text;
    }

    /** @return array<int,array{spec:string,name:string,description:string}> */
    private function sets(): array
    {
        $sets = [];
        foreach (Database::select(
            'SELECT cm.slug AS community_slug, cm.name AS community_name,
                    c.slug AS collection_slug, c.name AS collection_name, c.description
             FROM collections c
             JOIN communities cm ON cm.id = c.community_id
             WHERE c.institution_id = ? AND c.deleted_at IS NULL AND c.is_public = 1
               AND cm.deleted_at IS NULL AND cm.is_public = 1
             ORDER BY cm.sort_order, cm.name, c.sort_order, c.name',
            [TenantContext::institutionId()]
        ) as $row) {
            $sets[] = [
                'spec' => (string) $row['community_slug'] . ':' . (string) $row['collection_slug'],
                'name' => (string) $row['community_name'] . ' / ' . (string) $row['collection_name'],
                'description' => (string) ($row['description'] ?? ''),
            ];
        }
        return $sets;
    }

    /** @return array{spec:string,name:string,description:string}|null */
    private function findSet(string $spec): ?array
    {
        foreach ($this->sets() as $set) {
            if ($set['spec'] === $spec) {
                return $set;
            }
        }
        return null;
    }

    private function setCollectionSlug(string $spec): string
    {
        $parts = explode(':', $spec);
        return (string) (end($parts) ?: $spec);
    }

    /** @param array<string,string> $args */
    private function wrap(array $args, string $inner): string
    {
        $requestXml = '<request baseURL="' . $this->h(url('/oai')) . '"';
        foreach (['verb', 'identifier', 'metadataPrefix', 'from', 'until', 'set', 'resumptionToken'] as $name) {
            if (isset($args[$name])) {
                $requestXml .= ' ' . $name . '="' . $this->h($args[$name]) . '"';
            }
        }
        $requestXml .= '>' . $this->h(url('/oai')) . '</request>';

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">' . "\n"
            . '<responseDate>' . $this->h(gmdate('Y-m-d\TH:i:s\Z')) . '</responseDate>' . "\n"
            . $requestXml . "\n"
            . $inner . "\n"
            . '</OAI-PMH>';
    }

    /** @param array<string,string> $args */
    private function error(string $code, string $message, array $args): Response
    {
        if ($code === 'badVerb' || !in_array($args['verb'] ?? '', self::VERBS, true)) {
            unset($args['verb']);
        }
        $inner = '<error code="' . $this->h($code) . '">' . $this->h($message) . '</error>';
        return Response::xml($this->wrap($args, $inner));
    }

    /** @param array<string,mixed> $token */
    private function encodeToken(array $token): string
    {
        $json = json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $payload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $sig = substr(hash_hmac('sha256', $payload, $this->secret()), 0, 24);
        return $payload . '.' . $sig;
    }

    /** @return array<string,mixed>|null */
    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$payload, $sig] = $parts;
        if (!hash_equals(substr(hash_hmac('sha256', $payload, $this->secret()), 0, 24), $sig)) {
            return null;
        }
        $json = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        if (($data['e'] ?? 0) < time()) {
            return null;
        }
        return $data;
    }

    private function secret(): string
    {
        $key = (string) config('app.key', '');
        if ($key !== '') {
            return $key;
        }
        return 'oai-' . $this->repoId();
    }

    private function repoId(): string
    {
        $configured = trim((string) config('repository.oai_repository_identifier', ''));
        if ($configured !== '') {
            return $configured;
        }
        return preg_replace('/[^a-z0-9]+/i', '-', (string) (TenantContext::institution()['name'] ?? 'repositorio')) ?: 'repositorio';
    }

    private function repoName(): string
    {
        return (string) (TenantContext::institution()['name'] ?? config('app.name'));
    }

    private function adminEmail(): string
    {
        $email = trim((string) config('repository.oai_admin_email', ''));
        if ($email !== '') {
            return $email;
        }
        return 'soporte@repositorio.gob.pe';
    }

    private function oaiIdentifier(string $publicId): string
    {
        return 'oai:' . $this->repoId() . ':' . $publicId;
    }

    private function datestamp(string $datetime): string
    {
        $ts = strtotime($datetime . (str_contains($datetime, 'T') || str_contains($datetime, '+00:00') ? '' : ' UTC'));
        if ($ts === false) {
            $ts = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }

    private function validTimestamp(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            [$y, $m, $d] = array_map('intval', explode('-', $value));
            return checkdate($m, $d, $y);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
            [$date, $time] = explode('T', $value);
            [$y, $m, $d] = array_map('intval', explode('-', $date));
            [$hh, $mm, $ss] = array_map('intval', explode(':', rtrim($time, 'Z')));
            return checkdate($m, $d, $y) && $hh <= 23 && $mm <= 59 && $ss <= 59;
        }
        return false;
    }

    private function toSqlDate(string $value): string
    {
        if (str_contains($value, 'T')) {
            return str_replace('T', ' ', rtrim($value, 'Z'));
        }
        return $value . ' 00:00:00';
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
