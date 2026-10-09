<?php

declare(strict_types=1);

/**
 * Migración 006 — Contenido: personas, ítems, versiones, autores,
 * palabras clave, relaciones y borradores de depósito.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS persons (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            full_name VARCHAR(190) NOT NULL,
            given_name VARCHAR(120) NULL,
            family_name VARCHAR(120) NULL,
            orcid CHAR(19) NULL,
            email_public VARCHAR(190) NULL,
            affiliation VARCHAR(190) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_person_inst (institution_id),
            KEY idx_person_name (full_name),
            CONSTRAINT fk_person_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS person_identifiers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            person_id BIGINT UNSIGNED NOT NULL,
            id_type VARCHAR(30) NOT NULL,
            id_value VARCHAR(190) NOT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_person_id (person_id, id_type, id_value),
            CONSTRAINT fk_pi_person FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            institution_id BIGINT UNSIGNED NOT NULL,
            collection_id BIGINT UNSIGNED NOT NULL,
            document_type_id BIGINT UNSIGNED NULL,
            program_id BIGINT UNSIGNED NULL,
            public_id VARCHAR(40) NOT NULL,
            current_version_id BIGINT UNSIGNED NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
            access_level VARCHAR(20) NOT NULL DEFAULT 'open',
            embargo_until DATETIME NULL,
            slug VARCHAR(255) NULL,
            quality_score TINYINT NOT NULL DEFAULT 0,
            submitted_at DATETIME NULL,
            published_at DATETIME NULL,
            withdrawn_at DATETIME NULL,
            archived_at DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            updated_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_uuid (uuid),
            UNIQUE KEY uq_item_public_id (institution_id, public_id),
            KEY idx_item_inst_status (institution_id, status, published_at),
            KEY idx_item_collection (institution_id, collection_id, status),
            KEY idx_item_updated (institution_id, updated_at),
            CONSTRAINT fk_item_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE,
            CONSTRAINT fk_item_collection FOREIGN KEY (collection_id) REFERENCES collections (id),
            CONSTRAINT fk_item_doctype FOREIGN KEY (document_type_id) REFERENCES document_types (id) ON DELETE SET NULL,
            CONSTRAINT fk_item_program FOREIGN KEY (program_id) REFERENCES programs (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            version_number INT NOT NULL DEFAULT 1,
            title VARCHAR(500) NOT NULL,
            abstract TEXT NULL,
            language_code VARCHAR(12) NOT NULL DEFAULT 'es-PE',
            issued_at DATE NULL,
            change_reason VARCHAR(255) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'DRAFT',
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_version (item_id, version_number),
            KEY idx_version_item (item_id),
            FULLTEXT KEY ft_version_text (title, abstract),
            CONSTRAINT fk_iv_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("ALTER TABLE items
            ADD CONSTRAINT fk_item_current_version FOREIGN KEY (current_version_id) REFERENCES item_versions (id) ON DELETE SET NULL");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_authors (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            person_id BIGINT UNSIGNED NULL,
            full_name VARCHAR(190) NOT NULL,
            author_role VARCHAR(40) NOT NULL DEFAULT 'author',
            affiliation VARCHAR(190) NULL,
            orcid CHAR(19) NULL,
            email_internal VARCHAR(190) NULL,
            position INT NOT NULL DEFAULT 1,
            is_corresponding TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_author_position (item_id, position),
            KEY idx_author_person (person_id),
            KEY idx_author_name (institution_id, full_name),
            CONSTRAINT fk_ia_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_ia_person FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS keywords (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            term VARCHAR(140) NOT NULL,
            slug VARCHAR(160) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_keyword (institution_id, slug),
            KEY idx_keyword_term (term)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_keywords (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            keyword_id BIGINT UNSIGNED NOT NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_keyword (item_id, keyword_id),
            KEY idx_ik_keyword (keyword_id),
            CONSTRAINT fk_ik_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_ik_keyword FOREIGN KEY (keyword_id) REFERENCES keywords (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS item_relations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id BIGINT UNSIGNED NOT NULL,
            related_item_id BIGINT UNSIGNED NOT NULL,
            relation_type VARCHAR(40) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_relation (item_id, related_item_id, relation_type),
            CONSTRAINT fk_ir_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
            CONSTRAINT fk_ir_related FOREIGN KEY (related_item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS metadata_values (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_version_id BIGINT UNSIGNED NOT NULL,
            field_id BIGINT UNSIGNED NOT NULL,
            value_text TEXT NULL,
            value_uri VARCHAR(500) NULL,
            language_code VARCHAR(12) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_mv_version_field (item_version_id, field_id),
            KEY idx_mv_inst (institution_id),
            CONSTRAINT fk_mv_version FOREIGN KEY (item_version_id) REFERENCES item_versions (id) ON DELETE CASCADE,
            CONSTRAINT fk_mv_field FOREIGN KEY (field_id) REFERENCES metadata_fields (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS deposits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            step_no INT NOT NULL DEFAULT 1,
            data_json TEXT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            updated_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_deposit_item_step (item_id, step_no),
            CONSTRAINT fk_deposit_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['deposits', 'metadata_values', 'item_relations', 'item_keywords', 'keywords', 'item_authors', 'item_versions', 'items', 'person_identifiers', 'persons'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
