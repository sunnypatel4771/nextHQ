<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Nexhq_pipelines extends App_migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // tbl_pipelines — NexHQ Pipeline System
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'pipelines')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'pipelines` (
                `id`         INT(11) NOT NULL AUTO_INCREMENT,
                `name`       VARCHAR(191) NOT NULL,
                `company_id` INT(11) NOT NULL DEFAULT 0,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

            // Insert a default pipeline
            $this->db->insert(db_prefix() . 'pipelines', [
                'name'       => 'Sales Pipeline',
                'company_id' => 0,
                'is_default' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // ---------------------------------------------------------------
        // tbl_pipeline_stages — Stages within each pipeline
        // ---------------------------------------------------------------
        if (!$this->db->table_exists(db_prefix() . 'pipeline_stages')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'pipeline_stages` (
                `id`          INT(11) NOT NULL AUTO_INCREMENT,
                `pipeline_id` INT(11) NOT NULL,
                `name`        VARCHAR(191) NOT NULL,
                `stage_order` INT(11) NOT NULL DEFAULT 0,
                `color`       VARCHAR(20) NOT NULL DEFAULT "#4F46E5",
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `pipeline_id` (`pipeline_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

            // Insert default stages for the default pipeline
            $pipeline_id = $this->db->insert_id();
            // Get the first pipeline id
            $pipeline = $this->db->select('id')->limit(1)->get(db_prefix() . 'pipelines')->row();
            $pid = $pipeline ? $pipeline->id : 1;

            $default_stages = [
                ['name' => 'New Lead',    'stage_order' => 1, 'color' => '#94A3B8'],
                ['name' => 'Contacted',   'stage_order' => 2, 'color' => '#3B82F6'],
                ['name' => 'Qualified',   'stage_order' => 3, 'color' => '#8B5CF6'],
                ['name' => 'Proposal',    'stage_order' => 4, 'color' => '#F59E0B'],
                ['name' => 'Won',         'stage_order' => 5, 'color' => '#10B981'],
                ['name' => 'Lost',        'stage_order' => 6, 'color' => '#EF4444'],
            ];

            foreach ($default_stages as $stage) {
                $this->db->insert(db_prefix() . 'pipeline_stages', array_merge($stage, [
                    'pipeline_id' => $pid,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]));
            }
        }

        // ---------------------------------------------------------------
        // Modify tblleads — add pipeline_id, stage_id, lead_value
        // ---------------------------------------------------------------
        if ($this->db->table_exists(db_prefix() . 'leads')) {
            if (!$this->db->field_exists('pipeline_id', db_prefix() . 'leads')) {
                $this->db->query('ALTER TABLE `' . db_prefix() . 'leads`
                    ADD COLUMN `pipeline_id` INT(11) NULL DEFAULT NULL AFTER `status`,
                    ADD COLUMN `stage_id`    INT(11) NULL DEFAULT NULL AFTER `pipeline_id`,
                    ADD COLUMN `lead_value`  DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `stage_id`,
                    ADD COLUMN `last_activity` DATETIME NULL DEFAULT NULL AFTER `lead_value`');
            }
        }
    }
}
