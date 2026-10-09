<?php

declare(strict_types=1);

/**
 * Migración 010 — Identificadores (local URI, DOI/Handle solo con proveedor real).
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS identifier_providers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            provider_type VARCHAR(30) NOT NULL,
            name VARCHAR(120) NOT NULL,
            base_url VARCHAR(255) NULL,
            username VARCHAR(120) NULL,
            secret_enc TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'disabled',
            last_check_at DATETIME NULL,
            last_error VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_provider (institution_id, provider_type),
            CONSTRAINT fk_ip_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_identifiers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            identifier_type VARCHAR(30) NOT NULL,
            identifier_value VARCHAR(255) NOT NULL,
            provider VARCHAR(60) NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_identifier (institution_id, identifier_type, identifier_value),
            KEY idx_identifier_item (item_id),
            CONSTRAINT fk_itemid_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_itemid_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS identifier_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED NULL,
            action VARCHAR(60) NOT NULL,
            request_summary VARCHAR(500) NULL,
            response_summary VARCHAR(500) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'failed',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_item (item_id),
            CONSTRAINT fk_ie_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_ie_provider FOREIGN KEY (provider_id) REFERENCES identifier_providers (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['identifier_events', 'item_identifiers', 'identifier_providers'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
