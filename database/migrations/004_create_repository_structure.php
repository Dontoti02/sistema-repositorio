<?php

declare(strict_types=1);

/**
 * Migración 004 — Estructura del repositorio: comunidades, colecciones,
 * miembros, políticas, tipos documentales y flujos de revisión base.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS communities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            parent_id BIGINT UNSIGNED NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            description TEXT NULL,
            banner_path VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_community_inst_slug (institution_id, slug),
            KEY idx_community_parent (parent_id),
            CONSTRAINT fk_community_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_community_parent FOREIGN KEY (parent_id) REFERENCES communities (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS review_workflows (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(140) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_workflow_inst_slug (institution_id, slug),
            CONSTRAINT fk_workflow_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS workflow_steps (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            workflow_id BIGINT UNSIGNED NOT NULL,
            step_name VARCHAR(120) NOT NULL,
            step_slug VARCHAR(60) NOT NULL,
            order_no INT NOT NULL DEFAULT 1,
            action VARCHAR(30) NOT NULL DEFAULT 'review',
            required_permission VARCHAR(100) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_wf_step (workflow_id, step_slug),
            CONSTRAINT fk_wf_step_workflow FOREIGN KEY (workflow_id) REFERENCES review_workflows (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS collections (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            community_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            description TEXT NULL,
            banner_path VARCHAR(255) NULL,
            deposit_enabled TINYINT(1) NOT NULL DEFAULT 1,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            requires_approval TINYINT(1) NOT NULL DEFAULT 1,
            workflow_id BIGINT UNSIGNED NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_collection_inst_slug (institution_id, slug),
            KEY idx_collection_community (community_id),
            KEY idx_collection_public (institution_id, is_public, deleted_at),
            CONSTRAINT fk_collection_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_collection_community FOREIGN KEY (community_id) REFERENCES communities (id) ON DELETE CASCADE,
            CONSTRAINT fk_collection_workflow FOREIGN KEY (workflow_id) REFERENCES review_workflows (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS collection_members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            collection_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role_in_collection VARCHAR(30) NOT NULL DEFAULT 'reviewer',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_coll_member (collection_id, user_id, role_in_collection),
            KEY idx_coll_member_user (user_id),
            CONSTRAINT fk_cm_collection FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE,
            CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS collection_policies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            collection_id BIGINT UNSIGNED NOT NULL,
            allowed_extensions VARCHAR(255) NOT NULL DEFAULT 'pdf',
            max_file_mb INT NOT NULL DEFAULT 50,
            max_files INT NOT NULL DEFAULT 5,
            allowed_document_types JSON NULL,
            allow_embargo TINYINT(1) NOT NULL DEFAULT 0,
            max_embargo_days INT NOT NULL DEFAULT 365,
            require_rights_declaration TINYINT(1) NOT NULL DEFAULT 1,
            submission_statement TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_policy_collection (collection_id),
            CONSTRAINT fk_cp_collection FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS document_types (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(140) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            requires_degree_fields TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_doctype_inst_slug (institution_id, slug),
            CONSTRAINT fk_doctype_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['document_types', 'collection_policies', 'collection_members', 'collections', 'workflow_steps', 'review_workflows', 'communities'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
