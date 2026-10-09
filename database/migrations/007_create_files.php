<?php

declare(strict_types=1);

/**
 * Migración 007 — Archivos privados, versiones, integridad y análisis.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS storage_disks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            disk_name VARCHAR(60) NOT NULL,
            driver VARCHAR(30) NOT NULL DEFAULT 'local_private',
            root_path VARCHAR(255) NOT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 1,
            quota_bytes BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_disk_name (disk_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS files (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            item_version_id BIGINT UNSIGNED NULL,
            original_filename VARCHAR(255) NOT NULL,
            storage_key VARCHAR(300) NOT NULL,
            disk_id BIGINT UNSIGNED NULL,
            mime_type VARCHAR(120) NOT NULL,
            extension VARCHAR(20) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            sha256 CHAR(64) NOT NULL,
            visibility VARCHAR(20) NOT NULL DEFAULT 'private',
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            scan_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            scan_detail VARCHAR(255) NULL,
            thumbnail_key VARCHAR(300) NULL,
            download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_file_uuid (uuid),
            KEY idx_file_item (item_id),
            KEY idx_file_sha (sha256),
            KEY idx_file_inst (institution_id, deleted_at),
            CONSTRAINT fk_file_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_file_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_file_version FOREIGN KEY (item_version_id) REFERENCES item_versions (id) ON DELETE SET NULL,
            CONSTRAINT fk_file_disk FOREIGN KEY (disk_id) REFERENCES storage_disks (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS file_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id BIGINT UNSIGNED NOT NULL,
            version_no INT NOT NULL DEFAULT 1,
            storage_key VARCHAR(300) NOT NULL,
            sha256 CHAR(64) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            change_reason VARCHAR(255) NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_file_version (file_id, version_no),
            CONSTRAINT fk_fv_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS file_integrity_checks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id BIGINT UNSIGNED NOT NULL,
            expected_sha256 CHAR(64) NOT NULL,
            computed_sha256 CHAR(64) NULL,
            status VARCHAR(20) NOT NULL,
            detail VARCHAR(255) NULL,
            checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_integrity_file (file_id, checked_at),
            CONSTRAINT fk_fic_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS file_access_policies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id BIGINT UNSIGNED NOT NULL,
            allowed_roles JSON NULL,
            require_login TINYINT(1) NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_fap_file (file_id),
            CONSTRAINT fk_fap_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS virus_scan_results (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_id BIGINT UNSIGNED NOT NULL,
            engine VARCHAR(60) NOT NULL DEFAULT 'none',
            status VARCHAR(20) NOT NULL,
            detail VARCHAR(255) NULL,
            scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_scan_file (file_id),
            CONSTRAINT fk_vsr_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['virus_scan_results', 'file_access_policies', 'file_integrity_checks', 'file_versions', 'files', 'storage_disks'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
