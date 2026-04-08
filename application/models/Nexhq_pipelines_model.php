<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * NexHQ — Pipelines Model
 */
class Nexhq_pipelines_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // -------------------------------------------------------------------
    // PIPELINES
    // -------------------------------------------------------------------

    public function get_all_pipelines()
    {
        return $this->db->get(db_prefix() . 'pipelines')->result();
    }

    public function get_pipeline($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . 'pipelines')->row();
    }

    public function get_default_pipeline()
    {
        return $this->db->where('is_default', 1)->limit(1)
            ->get(db_prefix() . 'pipelines')->row();
    }

    public function create_pipeline($data)
    {
        // If setting as default, unset all others first
        if (!empty($data['is_default'])) {
            $this->db->update(db_prefix() . 'pipelines', ['is_default' => 0]);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'pipelines', $data);
        return $this->db->insert_id();
    }

    public function delete_pipeline($id)
    {
        $pipeline = $this->get_pipeline($id);
        if ($pipeline && $pipeline->is_default == 1) {
            return false; // Protect default pipeline
        }
        $this->db->delete(db_prefix() . 'pipelines', ['id' => $id]);
        $this->db->delete(db_prefix() . 'pipeline_stages', ['pipeline_id' => $id]);
        return true;
    }

    // -------------------------------------------------------------------
    // STAGES
    // -------------------------------------------------------------------

    public function get_stages($pipeline_id)
    {
        return $this->db->where('pipeline_id', $pipeline_id)
            ->order_by('stage_order', 'ASC')
            ->get(db_prefix() . 'pipeline_stages')->result();
    }

    public function get_stage($id)
    {
        return $this->db->where('id', $id)
            ->get(db_prefix() . 'pipeline_stages')->row();
    }

    public function create_stage($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'pipeline_stages', $data);
        return $this->db->insert_id();
    }

    // -------------------------------------------------------------------
    // LEADS IN PIPELINE (KANBAN)
    // -------------------------------------------------------------------

    public function get_leads_by_stage($stage_id, $pipeline_id)
    {
        return $this->db
            ->select(db_prefix() . 'leads.id, ' . db_prefix() . 'leads.name, ' .
                     db_prefix() . 'leads.email, ' . db_prefix() . 'leads.phonenumber, ' .
                     db_prefix() . 'leads.lead_value, ' . db_prefix() . 'leads.status, ' .
                     db_prefix() . 'leads.last_activity, ' . db_prefix() . 'leads.dateadded')
            ->where(db_prefix() . 'leads.pipeline_id', $pipeline_id)
            ->where(db_prefix() . 'leads.stage_id', $stage_id)
            ->order_by(db_prefix() . 'leads.last_activity', 'DESC')
            ->get(db_prefix() . 'leads')
            ->result();
    }

    public function get_all_pipeline_leads($pipeline_id)
    {
        $stages = $this->get_stages($pipeline_id);
        $result = [];

        foreach ($stages as $stage) {
            $result[$stage->id] = [
                'stage'  => $stage,
                'leads'  => $this->get_leads_by_stage($stage->id, $pipeline_id),
            ];
        }

        return $result;
    }

    public function update_lead_stage($lead_id, $stage_id, $pipeline_id)
    {
        $this->db->where('id', $lead_id);
        return $this->db->update(db_prefix() . 'leads', [
            'stage_id'      => $stage_id,
            'pipeline_id'   => $pipeline_id,
            'last_activity' => date('Y-m-d H:i:s'),
        ]);
    }

    // -------------------------------------------------------------------
    // ACTIVITY LOGGING
    // -------------------------------------------------------------------

    public function log_activity($lead_id, $type, $description, $additional_data = [])
    {
        $this->db->insert(db_prefix() . 'lead_activities', [
            'lead_id'         => $lead_id,
            'staff_id'        => get_staff_user_id() ?: 0,
            'type'            => $type,
            'description'     => $description,
            'additional_data' => !empty($additional_data) ? json_encode($additional_data) : null,
            'company_id'      => 0,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function get_lead_activities($lead_id, $limit = 50)
    {
        return $this->db
            ->where('lead_id', $lead_id)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get(db_prefix() . 'lead_activities')
            ->result();
    }
}
