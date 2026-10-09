<?php

declare(strict_types=1);

/**
 * Migración 013 — Personalización y apariencia institucional (Design System).
 * Soporta versionado de temas por institución: borrador, publicado e histórico.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_themes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            tokens_json JSON NOT NULL,
            layout_json JSON NOT NULL,
            texts_json JSON NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            published_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            published_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inst_themes_status (institution_id, status),
            CONSTRAINT fk_inst_themes_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS institution_themes");
    },
];
