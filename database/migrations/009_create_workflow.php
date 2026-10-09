<?php

declare(strict_types=1);

/**
 * Migración 009 — Flujo editorial: asignaciones, decisiones, historial, comentarios.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS review_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            reviewer_id BIGINT UNSIGNED NOT NULL,
            assigned_by BIGINT UNSIGNED NULL,
            stage VARCHAR(60) NOT NULL DEFAULT 'review',
            status VARCHAR(20) NOT NULL DEFAULT 'assigned',
            due_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY idx_ra_reviewer (institution_id, reviewer_id, status),
            KEY idx_ra_item (item_id),
            CONSTRAINT fk_ra_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_ra_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_ra_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS review_decisions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            assignment_id BIGINT UNSIGNED NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            decision VARCHAR(30) NOT NULL,
            comment TEXT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rd_item (item_id),
            CONSTRAINT fk_rdec_assignment FOREIGN KEY (assignment_id) REFERENCES review_assignments (id) ON DELETE SET NULL,
            CONSTRAINT fk_rdec_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS review_comments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            assignment_id BIGINT UNSIGNED NULL,
            field_key VARCHAR(80) NULL,
            comment TEXT NOT NULL,
            visibility VARCHAR(20) NOT NULL DEFAULT 'author',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rc_item (item_id),
            CONSTRAINT fk_rc_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_rc_assignment FOREIGN KEY (assignment_id) REFERENCES review_assignments (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_status_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            from_status VARCHAR(30) NULL,
            to_status VARCHAR(30) NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            reason VARCHAR(500) NULL,
            version_number INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ish_item (item_id, created_at),
            KEY idx_ish_inst (institution_id, created_at),
            CONSTRAINT fk_ish_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['item_status_history', 'review_comments', 'review_decisions', 'review_assignments'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
