<?php

declare(strict_types=1);

/**
 * Migración 005 — Sistema de metadatos: diccionario, perfiles,
 * vocabularios controlados y valores normalizados.
 */
return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS controlled_vocabularies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NULL,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(140) NOT NULL,
            description VARCHAR(255) NULL,
            version VARCHAR(30) NOT NULL DEFAULT '1.0',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_vocab (institution_id, slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS vocabulary_terms (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            vocabulary_id BIGINT UNSIGNED NOT NULL,
            parent_id BIGINT UNSIGNED NULL,
            term VARCHAR(190) NOT NULL,
            term_value VARCHAR(140) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_term (vocabulary_id, term_value),
            KEY idx_term_parent (parent_id),
            CONSTRAINT fk_term_vocab FOREIGN KEY (vocabulary_id) REFERENCES controlled_vocabularies (id) ON DELETE CASCADE,
            CONSTRAINT fk_term_parent FOREIGN KEY (parent_id) REFERENCES vocabulary_terms (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS metadata_fields (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            field_key VARCHAR(80) NOT NULL,
            namespace VARCHAR(60) NOT NULL DEFAULT 'local',
            label VARCHAR(140) NOT NULL,
            description VARCHAR(255) NULL,
            datatype VARCHAR(30) NOT NULL DEFAULT 'text',
            cardinality VARCHAR(10) NOT NULL DEFAULT 'single',
            validation_rule VARCHAR(190) NULL,
            vocabulary_id BIGINT UNSIGNED NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            is_internal TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            example_text VARCHAR(190) NULL,
            help_text VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_field_key (field_key),
            KEY idx_field_public (is_public),
            CONSTRAINT fk_field_vocab FOREIGN KEY (vocabulary_id) REFERENCES controlled_vocabularies (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS metadata_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            institution_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(140) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            version VARCHAR(30) NOT NULL DEFAULT '1.0',
            description VARCHAR(255) NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_profile_inst_slug (institution_id, slug),
            CONSTRAINT fk_profile_inst FOREIGN KEY (institution_id) REFERENCES institutions (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS metadata_profile_fields (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id BIGINT UNSIGNED NOT NULL,
            field_id BIGINT UNSIGNED NOT NULL,
            label_override VARCHAR(140) NULL,
            help_text VARCHAR(255) NULL,
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            is_required_if TEXT NULL,
            show_in_public TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_profile_field (profile_id, field_id),
            CONSTRAINT fk_ppf_profile FOREIGN KEY (profile_id) REFERENCES metadata_profiles (id) ON DELETE CASCADE,
            CONSTRAINT fk_ppf_field FOREIGN KEY (field_id) REFERENCES metadata_fields (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS metadata_mappings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            field_id BIGINT UNSIGNED NOT NULL,
            standard VARCHAR(40) NOT NULL,
            target_element VARCHAR(100) NOT NULL,
            qualifier VARCHAR(100) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_mapping (field_id, standard, target_element),
            CONSTRAINT fk_map_field FOREIGN KEY (field_id) REFERENCES metadata_fields (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['metadata_mappings', 'metadata_profile_fields', 'metadata_profiles', 'metadata_fields', 'vocabulary_terms', 'controlled_vocabularies'] as $t) {
            $pdo->exec("DROP TABLE IF EXISTS {$t}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    },
];
