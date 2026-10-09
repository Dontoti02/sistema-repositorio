<?php

declare(strict_types=1);

/**
 * Migración 003 — Organización académica: ubigeo, programas, unidades, líneas.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS regions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code CHAR(2) NOT NULL,
            name VARCHAR(120) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_region_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS provinces (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            region_id BIGINT UNSIGNED NOT NULL,
            code CHAR(4) NOT NULL,
            name VARCHAR(120) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_province_code (code),
            KEY idx_province_region (region_id),
            CONSTRAINT fk_province_region FOREIGN KEY (region_id) REFERENCES regions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS districts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            province_id BIGINT UNSIGNED NOT NULL,
            code CHAR(6) NOT NULL,
            name VARCHAR(120) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_district_code (code),
            KEY idx_district_province (province_id),
            CONSTRAINT fk_district_province FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS academic_units (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_unit_inst_slug (institution_id, slug),
            CONSTRAINT fk_unit_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS programs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            academic_unit_id BIGINT UNSIGNED NULL,
            name VARCHAR(190) NOT NULL,
            code VARCHAR(40) NULL,
            slug VARCHAR(140) NOT NULL,
            level VARCHAR(40) NULL,
            description TEXT NULL,
            coordinator_user_id BIGINT UNSIGNED NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_program_inst_slug (institution_id, slug),
            KEY idx_program_status (institution_id, status),
            CONSTRAINT fk_program_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_program_unit FOREIGN KEY (academic_unit_id) REFERENCES academic_units (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS research_lines (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_line_inst (institution_id),
            CONSTRAINT fk_line_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['research_lines', 'programs', 'academic_units', 'districts', 'provinces', 'regions'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
