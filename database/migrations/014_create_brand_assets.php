<?php

declare(strict_types=1);

/**
 * Migración 014 — Activos de marca institucional (logos, favicons, banners, portadas).
 * Registra y versiona los archivos gráficos asociados a la identidad de cada instituto.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_brand_assets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            kind VARCHAR(40) NOT NULL,
            storage_key VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            width INT UNSIGNED NULL,
            height INT UNSIGNED NULL,
            size_bytes BIGINT UNSIGNED NOT NULL,
            sha256 CHAR(64) NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_brand_assets_inst_kind (institution_id, kind),
            CONSTRAINT fk_brand_assets_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS institution_brand_assets");
    },
];
