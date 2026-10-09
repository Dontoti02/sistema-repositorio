<?php

declare(strict_types=1);

/**
 * Migración 008 — Licencias, embargos, declaraciones y concesiones de acceso.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS licenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            code VARCHAR(60) NOT NULL,
            name VARCHAR(140) NOT NULL,
            url VARCHAR(255) NULL,
            description VARCHAR(500) NULL,
            requires_attribution TINYINT(1) NOT NULL DEFAULT 0,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_license_code (institution_id, code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_licenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            license_id BIGINT UNSIGNED NOT NULL,
            statement VARCHAR(500) NULL,
            granted_by VARCHAR(190) NULL,
            declared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_license (item_id, license_id),
            CONSTRAINT fk_il_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_il_license FOREIGN KEY (license_id) REFERENCES licenses (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS embargoes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            file_id BIGINT UNSIGNED NULL,
            embargo_until DATETIME NOT NULL,
            reason VARCHAR(500) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            released_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_embargo_status (status, embargo_until),
            KEY idx_embargo_item (item_id),
            CONSTRAINT fk_embargo_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_embargo_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_embargo_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS rights_declarations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            declaration_type VARCHAR(40) NOT NULL DEFAULT 'originality',
            statement TEXT NOT NULL,
            declared_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rd_item (item_id),
            CONSTRAINT fk_rd_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS access_grants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            subject_type VARCHAR(20) NOT NULL,
            subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            permission VARCHAR(30) NOT NULL DEFAULT 'download',
            granted_by BIGINT UNSIGNED NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_grant (item_id, subject_type, subject_id, permission),
            CONSTRAINT fk_ag_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_ag_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['access_grants', 'rights_declarations', 'embargoes', 'item_licenses', 'licenses'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
