<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Nexhq_activities_conversations extends App_migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // tbl_lead_activities — Activity Timeline per Lead
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'lead_activities')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'lead_activities` (
                `id`          INT(11) NOT NULL AUTO_INCREMENT,
                `lead_id`     INT(11) NOT NULL,
                `staff_id`    INT(11) NOT NULL DEFAULT 0,
                `type`        ENUM("call","sms","email","whatsapp","note","stage_change","status_change","lead_created","appointment","automation") NOT NULL DEFAULT "note",
                `description` TEXT NOT NULL,
                `additional_data` TEXT NULL,
                `company_id`  INT(11) NOT NULL DEFAULT 0,
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // ---------------------------------------------------------------
        // tbl_conversations — Omnichannel Messaging (WhatsApp, Email, SMS)
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'conversations')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'conversations` (
                `id`           INT(11) NOT NULL AUTO_INCREMENT,
                `lead_id`      INT(11) NOT NULL,
                `channel`      ENUM("whatsapp","email","sms","chat") NOT NULL DEFAULT "whatsapp",
                `direction`    ENUM("inbound","outbound") NOT NULL DEFAULT "outbound",
                `message`      TEXT NOT NULL,
                `message_id`   VARCHAR(255) NULL DEFAULT NULL COMMENT "External message ID from provider",
                `sender`       VARCHAR(191) NULL DEFAULT NULL,
                `receiver`     VARCHAR(191) NULL DEFAULT NULL,
                `message_type` ENUM("text","image","video","audio","document","template") NOT NULL DEFAULT "text",
                `media_url`    VARCHAR(500) NULL DEFAULT NULL,
                `status`       ENUM("sent","delivered","read","failed") NOT NULL DEFAULT "sent",
                `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
                `staff_id`     INT(11) NOT NULL DEFAULT 0,
                `company_id`   INT(11) NOT NULL DEFAULT 0,
                `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `company_id` (`company_id`),
                KEY `channel` (`channel`),
                KEY `is_read` (`is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // ---------------------------------------------------------------
        // tbl_workflows — Automation Workflows (for Step 2)
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'workflows')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'workflows` (
                `id`          INT(11) NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(191) NOT NULL,
                `trigger`     VARCHAR(100) NOT NULL COMMENT "e.g. lead_created, stage_changed, message_received",
                `json_data`   LONGTEXT NULL COMMENT "Full workflow JSON config",
                `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
                `company_id`  INT(11) NOT NULL DEFAULT 0,
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // ---------------------------------------------------------------
        // tbl_logs — System Debug Logging
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'nexhq_logs')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'nexhq_logs` (
                `id`         INT(11) NOT NULL AUTO_INCREMENT,
                `type`       VARCHAR(100) NOT NULL COMMENT "e.g. automation, whatsapp, webhook",
                `message`    TEXT NOT NULL,
                `data`       LONGTEXT NULL COMMENT "JSON payload",
                `company_id` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `type` (`type`),
                KEY `company_id` (`company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }
    }
}
