<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * NexHQ — Pipelines Controller
 *
 * Handles Pipeline management and Kanban board view.
 * Route: /admin/pipelines
 */
class Pipelines extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('nexhq_pipelines_model');
    }

    /**
     * List all pipelines
     */
    public function index()
    {
        $data['pipelines'] = $this->nexhq_pipelines_model->get_all_pipelines();
        $data['title']     = 'Pipelines';
        $this->load->view('admin/nexhq/pipelines/index', $data);
    }

    /**
     * Kanban board view for a specific pipeline
     * /admin/pipelines/kanban/{pipeline_id}
     */
    public function kanban($pipeline_id = null)
    {
        if (!$pipeline_id) {
            // Load default pipeline
            $default = $this->nexhq_pipelines_model->get_default_pipeline();
            $pipeline_id = $default ? $default->id : null;
        }

        if (!$pipeline_id) {
            set_alert('warning', 'No pipeline found. Please create one first.');
            redirect(admin_url('pipelines'));
        }

        $data['pipeline']  = $this->nexhq_pipelines_model->get_pipeline($pipeline_id);
        $data['stages']    = $this->nexhq_pipelines_model->get_stages($pipeline_id);
        $data['pipelines'] = $this->nexhq_pipelines_model->get_all_pipelines();
        $data['title']     = 'Pipeline — ' . $data['pipeline']->name;

        $this->load->view('admin/nexhq/pipelines/kanban', $data);
    }

    /**
     * API: Get leads for a stage (Kanban columns)
     * POST /admin/pipelines/get_stage_leads
     */
    public function get_stage_leads()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $stage_id    = (int) $this->input->post('stage_id');
        $pipeline_id = (int) $this->input->post('pipeline_id');

        $leads = $this->nexhq_pipelines_model->get_leads_by_stage($stage_id, $pipeline_id);

        echo json_encode(['success' => true, 'leads' => $leads]);
    }

    /**
     * API: Update lead stage (drag & drop)
     * POST /admin/pipelines/update_stage
     */
    public function update_stage()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $lead_id     = (int) $this->input->post('lead_id');
        $stage_id    = (int) $this->input->post('stage_id');
        $pipeline_id = (int) $this->input->post('pipeline_id');

        if (!$lead_id || !$stage_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            return;
        }

        $updated = $this->nexhq_pipelines_model->update_lead_stage($lead_id, $stage_id, $pipeline_id);

        if ($updated) {
            // Fire automation hook
            trigger_nexhq_event('stage_changed', [
                'lead_id'     => $lead_id,
                'stage_id'    => $stage_id,
                'pipeline_id' => $pipeline_id,
            ]);

            // Log activity
            $this->nexhq_pipelines_model->log_activity($lead_id, 'stage_change',
                'Lead moved to new stage', ['stage_id' => $stage_id]);
        }

        echo json_encode(['success' => $updated]);
    }

    /**
     * Create a new pipeline
     * POST /admin/pipelines/create
     */
    public function create()
    {
        if ($this->input->post()) {
            $data = [
                'name'       => $this->input->post('name'),
                'is_default' => (int) $this->input->post('is_default'),
                'company_id' => 0,
            ];

            $id = $this->nexhq_pipelines_model->create_pipeline($data);

            if ($id) {
                set_alert('success', 'Pipeline created successfully.');
            } else {
                set_alert('danger', 'Failed to create pipeline.');
            }
        }
        redirect(admin_url('pipelines'));
    }

    /**
     * Create a new stage for a pipeline
     * POST /admin/pipelines/create_stage
     */
    public function create_stage()
    {
        if ($this->input->post()) {
            $data = [
                'pipeline_id' => (int) $this->input->post('pipeline_id'),
                'name'        => $this->input->post('name'),
                'color'       => $this->input->post('color') ?: '#4F46E5',
                'stage_order' => (int) $this->input->post('stage_order') ?: 99,
            ];

            $id = $this->nexhq_pipelines_model->create_stage($data);
            echo json_encode(['success' => (bool) $id, 'id' => $id]);
            return;
        }
        show_404();
    }

    /**
     * Delete a pipeline
     */
    public function delete($id)
    {
        if ($this->nexhq_pipelines_model->delete_pipeline($id)) {
            set_alert('success', 'Pipeline deleted.');
        } else {
            set_alert('danger', 'Cannot delete the default pipeline.');
        }
        redirect(admin_url('pipelines'));
    }
}
