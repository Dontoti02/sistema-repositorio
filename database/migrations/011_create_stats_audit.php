<?php

declare(strict_types=1);

/**
 * Migración 011 — Actividad, estadísticas agregadas y bitácora de auditoría.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS item_view_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            visitor_hash CHAR(64) NULL,
            user_id BIGINT UNSIGNED NULL,
            event_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_view_item (item_id, event_at),
            KEY idx_view_inst_date (institution_id, event_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS download_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            file_id BIGINT UNSIGNED NOT NULL,
            user_type VARCHAR(10) NOT NULL DEFAULT 'anon',
            user_id BIGINT UNSIGNED NULL,
            visitor_hash CHAR(64) NULL,
            event_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_dl_item (item_id, event_at),
            KEY idx_dl_inst_date (institution_id, event_at),
            CONSTRAINT fk_dl_file FOREIGN KEY (file_id) REFERENCES files (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS aggregate_daily_stats (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            metric_date DATE NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            collection_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            metric VARCHAR(40) NOT NULL,
            metric_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_aggregate (institution_id, metric_date, item_id, collection_id, metric),
            KEY idx_agg_inst_date (institution_id, metric_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS search_aggregates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            term_hash CHAR(64) NOT NULL,
            term VARCHAR(190) NOT NULL,
            searches INT NOT NULL DEFAULT 0,
            last_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_search_term (institution_id, term_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            actor_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(60) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            before_summary TEXT NULL,
            after_summary TEXT NULL,
            ip_masked VARCHAR(60) NULL,
            user_agent VARCHAR(300) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_inst_date (institution_id, created_at),
            KEY idx_audit_actor (actor_id, created_at),
            KEY idx_audit_entity (entity_type, entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['audit_logs', 'search_aggregates', 'aggregate_daily_stats', 'download_events', 'item_view_events'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
