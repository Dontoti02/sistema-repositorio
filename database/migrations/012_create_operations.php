<?php

declare(strict_types=1);

/**
 * Migración 012 — Operación: notificaciones, cola, correo, trabajos,
 * importaciones, exportaciones, respaldos, ajustes y tareas programadas.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            data_json TEXT NULL,
            link VARCHAR(255) NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notif_user (user_id, read_at),
            KEY idx_notif_inst (institution_id, created_at),
            CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS notification_preferences (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            in_app TINYINT(1) NOT NULL DEFAULT 1,
            email TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_pref (user_id, type),
            CONSTRAINT fk_np_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS email_outbox (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            to_email VARCHAR(190) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attempts INT NOT NULL DEFAULT 0,
            last_error VARCHAR(500) NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_outbox_status (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            queue VARCHAR(60) NOT NULL DEFAULT 'default',
            job_type VARCHAR(80) NOT NULL,
            payload_json TEXT NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            max_attempts INT NOT NULL DEFAULT 3,
            available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reserved_at DATETIME NULL,
            failed_at DATETIME NULL,
            last_error VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_jobs_available (queue, available_at, reserved_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS failed_jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_id BIGINT UNSIGNED NULL,
            job_type VARCHAR(80) NOT NULL,
            payload_json TEXT NOT NULL,
            exception TEXT NULL,
            failed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS import_batches (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            filename VARCHAR(255) NOT NULL,
            mapping_json TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_rows INT NOT NULL DEFAULT 0,
            ok_rows INT NOT NULL DEFAULT 0,
            error_rows INT NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_import_inst (institution_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS import_rows (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id BIGINT UNSIGNED NOT NULL,
            row_no INT NOT NULL,
            raw_json TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            errors TEXT NULL,
            item_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_import_row (batch_id, row_no),
            CONSTRAINT fk_ir_batch FOREIGN KEY (batch_id) REFERENCES import_batches (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS export_jobs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            format VARCHAR(20) NOT NULL,
            filters_json TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            file_path VARCHAR(300) NULL,
            row_count INT NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_export_inst (institution_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS backups (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            kind VARCHAR(30) NOT NULL DEFAULT 'full',
            file_path VARCHAR(300) NOT NULL,
            size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
            sha256 CHAR(64) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'created',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            verified_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_backup_inst (institution_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            setting_key VARCHAR(120) NOT NULL,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_setting (institution_id, setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS scheduled_task_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_name VARCHAR(80) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'running',
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            output TEXT NULL,
            PRIMARY KEY (id),
            KEY idx_task_runs (task_name, started_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['scheduled_task_runs', 'settings', 'backups', 'export_jobs', 'import_rows', 'import_batches', 'failed_jobs', 'jobs', 'email_outbox', 'notification_preferences', 'notifications'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
