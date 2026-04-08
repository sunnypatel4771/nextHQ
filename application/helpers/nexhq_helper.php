<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| NexHQ — Core Helper Functions
|--------------------------------------------------------------------------
| Automation, logging, pipeline helpers used across the whole system.
*/

// -----------------------------------------------------------------------
// SYSTEM LOGGING
// -----------------------------------------------------------------------

/**
 * Log a system event to tbl_nexhq_logs
 *
 * @param string $type    e.g. 'automation', 'whatsapp', 'webhook', 'error'
 * @param string $message Human-readable description
 * @param mixed  $data    Any additional payload (will be JSON encoded)
 */
function nexhq_log($type, $message, $data = [])
{
    $CI = &get_instance();
    $CI->db->insert(db_prefix() . 'nexhq_logs', [
        'type'       => $type,
        'message'    => $message,
        'data'       => !empty($data) ? json_encode($data) : null,
        'company_id' => function_exists('get_company_id') ? get_company_id() : 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// -----------------------------------------------------------------------
// AUTOMATION — WEBHOOK TRIGGER (Sends payload to n8n)
// -----------------------------------------------------------------------

/**
 * Fire a NexHQ automation event.
 * Sends a webhook POST to n8n (or any configured automation URL).
 *
 * @param string $event  Event name e.g. 'lead_created', 'stage_changed', 'message_received'
 * @param array  $data   Payload data related to the event
 */
function trigger_nexhq_event($event, $data = [])
{
    $webhook_url = get_option('nexhq_n8n_webhook_url');

    if (empty($webhook_url)) {
        nexhq_log('webhook', 'Webhook URL not configured. Event: ' . $event, $data);
        return false;
    }

    $payload = json_encode([
        'event'      => $event,
        'timestamp'  => date('Y-m-d H:i:s'),
        'company_id' => function_exists('get_company_id') ? get_company_id() : 0,
        'data'       => $data,
    ]);

    // Fire webhook asynchronously using cURL
    $ch = curl_init($webhook_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'X-NexHQ-Event: ' . $event,
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($error) {
        nexhq_log('webhook_error', 'cURL error for event: ' . $event, [
            'error'   => $error,
            'payload' => $data,
        ]);
        return false;
    }

    nexhq_log('webhook', 'Event fired: ' . $event, [
        'http_code' => $http_code,
        'response'  => substr($response, 0, 500),
        'payload'   => $data,
    ]);

    return $http_code >= 200 && $http_code < 300;
}

// -----------------------------------------------------------------------
// PIPELINE HELPERS
// -----------------------------------------------------------------------

/**
 * Check if a lead is an opportunity (assigned to a pipeline)
 *
 * @param int $lead_id
 * @return bool
 */
function is_opportunity($lead_id)
{
    $CI = &get_instance();
    $lead = $CI->db->select('pipeline_id')->where('id', $lead_id)
        ->get(db_prefix() . 'leads')->row();

    return $lead && !empty($lead->pipeline_id);
}

/**
 * Assign a lead to a pipeline stage (and log the activity)
 *
 * @param int $lead_id
 * @param int $pipeline_id
 * @param int $stage_id
 */
function assign_lead_to_pipeline($lead_id, $pipeline_id, $stage_id)
{
    $CI = &get_instance();
    $CI->db->where('id', $lead_id)->update(db_prefix() . 'leads', [
        'pipeline_id'   => $pipeline_id,
        'stage_id'      => $stage_id,
        'last_activity' => date('Y-m-d H:i:s'),
    ]);

    // Log
    $CI->db->insert(db_prefix() . 'lead_activities', [
        'lead_id'     => $lead_id,
        'staff_id'    => get_staff_user_id() ?: 0,
        'type'        => 'stage_change',
        'description' => 'Lead assigned to pipeline',
        'additional_data' => json_encode(['pipeline_id' => $pipeline_id, 'stage_id' => $stage_id]),
        'company_id'  => 0,
        'created_at'  => date('Y-m-d H:i:s'),
    ]);

    // Fire automation
    trigger_nexhq_event('lead_assigned_to_pipeline', [
        'lead_id'     => $lead_id,
        'pipeline_id' => $pipeline_id,
        'stage_id'    => $stage_id,
    ]);
}

// -----------------------------------------------------------------------
// MULTI-TENANT (STEP 6 PLACEHOLDER)
// -----------------------------------------------------------------------

/**
 * Get current company ID (for SaaS multi-tenant mode)
 * Will be fully implemented in Step 6.
 *
 * @return int
 */
if (!function_exists('get_company_id')) {
    function get_company_id()
    {
        $CI = &get_instance();
        if ($CI->session->has_userdata('company_id')) {
            return (int) $CI->session->userdata('company_id');
        }
        return 0; // 0 = default / single tenant mode
    }
}

// -----------------------------------------------------------------------
// WHATSAPP HELPERS (STEP 3 PLACEHOLDER)
// -----------------------------------------------------------------------

/**
 * Send a WhatsApp message via Meta Cloud API
 * Full implementation in Step 3.
 *
 * @param string $to      Phone number with country code e.g. +919876543210
 * @param string $message Message text
 * @return bool
 */
function nexhq_send_whatsapp($to, $message)
{
    $access_token   = get_option('nexhq_whatsapp_token');
    $phone_id       = get_option('nexhq_whatsapp_phone_id');

    if (empty($access_token) || empty($phone_id)) {
        nexhq_log('whatsapp', 'WhatsApp not configured.', ['to' => $to]);
        return false;
    }

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => preg_replace('/[^0-9]/', '', $to),
        'type'              => 'text',
        'text'              => ['body' => $message],
    ]);

    $ch = curl_init('https://graph.facebook.com/v17.0/' . $phone_id . '/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ],
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code === 200 && isset($result['messages'])) {
        nexhq_log('whatsapp', 'Message sent to ' . $to, ['message_id' => $result['messages'][0]['id'] ?? '']);
        return true;
    }

    nexhq_log('whatsapp_error', 'Failed to send to ' . $to, ['response' => $result]);
    return false;
}
