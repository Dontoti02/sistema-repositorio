<?php

declare(strict_types=1);

/**
 * Migración 001 — Instituciones (entidad raíz multi-institución).
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS institutions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            name VARCHAR(200) NOT NULL,
            short_name VARCHAR(120) NULL,
            institution_type VARCHAR(40) NULL,
            institution_code VARCHAR(40) NULL,
            ruc VARCHAR(15) NULL,
            slug VARCHAR(120) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            timezone VARCHAR(60) NOT NULL DEFAULT 'America/Lima',
            locale VARCHAR(20) NOT NULL DEFAULT 'es_PE',
            region_id BIGINT UNSIGNED NULL,
            province_id BIGINT UNSIGNED NULL,
            district_id BIGINT UNSIGNED NULL,
            ubigeo CHAR(6) NULL,
            address VARCHAR(255) NULL,
            phone VARCHAR(60) NULL,
            email VARCHAR(190) NULL,
            website VARCHAR(255) NULL,
            dre_gre VARCHAR(160) NULL,
            founded_year SMALLINT NULL,
            description TEXT NULL,
            mission TEXT NULL,
            vision TEXT NULL,
            coordinator_name VARCHAR(190) NULL,
            coordinator_email VARCHAR(190) NULL,
            logo_path VARCHAR(255) NULL,
            favicon_path VARCHAR(255) NULL,
            hero_image_path VARCHAR(255) NULL,
            primary_color VARCHAR(20) NULL DEFAULT '#0b3d6b',
            secondary_color VARCHAR(20) NULL DEFAULT '#12634a',
            accent_color VARCHAR(20) NULL DEFAULT '#c9922b',
            support_email VARCHAR(190) NULL,
            about_text TEXT NULL,
            deposit_policy TEXT NULL,
            privacy_policy TEXT NULL,
            faq_text TEXT NULL,
            storage_quota_bytes BIGINT UNSIGNED NULL,
            installed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_institutions_uuid (uuid),
            UNIQUE KEY uq_institutions_slug (slug),
            KEY idx_institutions_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_domains (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            domain VARCHAR(190) NOT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_institution_domain (domain),
            KEY idx_domain_inst (institution_id),
            CONSTRAINT fk_domain_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_inst_setting (institution_id, setting_key),
            CONSTRAINT fk_setting_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_media (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            kind VARCHAR(40) NOT NULL,
            storage_key VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            sha256 CHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_inst_media_kind (institution_id, kind),
            CONSTRAINT fk_media_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS institution_social_links (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            platform VARCHAR(60) NOT NULL,
            url VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_social_inst (institution_id),
            CONSTRAINT fk_social_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['institution_social_links', 'institution_media', 'institution_settings', 'institution_domains', 'institutions'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
