<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
            <div>
                <h4 class="tw-text-xl tw-font-bold tw-text-neutral-800 tw-mb-1">
                    <?= e($pipeline->name); ?>
                </h4>
                <p class="tw-text-sm tw-text-neutral-500">Drag & drop leads between stages</p>
            </div>
            <div class="tw-flex tw-items-center tw-gap-3">
                <!-- Pipeline switcher -->
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-exchange"></i> Switch Pipeline
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <?php foreach ($pipelines as $p) { ?>
                        <li <?= $p->id == $pipeline->id ? 'class="active"' : ''; ?>>
                            <a href="<?= admin_url('pipelines/kanban/' . $p->id); ?>">
                                <?= e($p->name); ?>
                                <?php if ($p->is_default) { ?><span class="text-muted"> (default)</span><?php } ?>
                            </a>
                        </li>
                        <?php } ?>
                        <li class="divider"></li>
                        <li><a href="<?= admin_url('pipelines'); ?>">Manage Pipelines</a></li>
                    </ul>
                </div>
                <a href="<?= admin_url('leads/create'); ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Lead
                </a>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="nexhq-kanban" id="kanban-board"
             style="display:flex; gap:16px; overflow-x:auto; padding-bottom:20px; align-items:flex-start; min-height:70vh;">

            <?php foreach ($stages as $stage) { ?>
            <div class="nexhq-kanban-column"
                 data-stage-id="<?= e($stage->id); ?>"
                 data-pipeline-id="<?= e($pipeline->id); ?>"
                 style="min-width:280px; max-width:280px; flex-shrink:0;">

                <!-- Stage Header -->
                <div class="nexhq-stage-header tw-rounded-t-lg tw-px-4 tw-py-3 tw-flex tw-items-center tw-justify-between"
                     style="background-color:<?= e($stage->color); ?>15; border-left:4px solid <?= e($stage->color); ?>;">
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <span class="tw-h-2.5 tw-w-2.5 tw-rounded-full" style="background-color:<?= e($stage->color); ?>;"></span>
                        <span class="tw-font-semibold tw-text-sm tw-text-neutral-800"><?= e($stage->name); ?></span>
                    </div>
                    <span class="badge stage-lead-count" style="background-color:<?= e($stage->color); ?>; color:#fff;">
                        <?= count($this->nexhq_pipelines_model->get_leads_by_stage($stage->id, $pipeline->id)); ?>
                    </span>
                </div>

                <!-- Lead Cards Drop Zone -->
                <div class="nexhq-stage-leads sortable-leads"
                     style="background:#F8FAFC; border:1px solid #E2E8F0; border-top:none;
                            border-radius:0 0 8px 8px; padding:8px; min-height:200px;">

                    <?php
                    $leads = $this->nexhq_pipelines_model->get_leads_by_stage($stage->id, $pipeline->id);
                    foreach ($leads as $lead) {
                    ?>
                    <div class="nexhq-lead-card"
                         data-lead-id="<?= e($lead->id); ?>"
                         style="background:#fff; border:1px solid #E2E8F0; border-radius:8px;
                                padding:12px; margin-bottom:8px; cursor:grab;
                                box-shadow:0 1px 2px rgba(0,0,0,0.04);">

                        <div class="tw-flex tw-items-start tw-justify-between tw-mb-2">
                            <a href="<?= admin_url('leads/index/' . $lead->id); ?>"
                               class="tw-text-sm tw-font-semibold tw-text-neutral-800 hover:tw-text-indigo-600"
                               style="line-height:1.3;">
                                <?= e($lead->name); ?>
                            </a>
                            <?php if ($lead->lead_value > 0) { ?>
                            <span class="tw-text-xs tw-font-medium tw-text-emerald-700 tw-bg-emerald-50 tw-rounded-full tw-px-2 tw-py-0.5 tw-shrink-0 tw-ml-2">
                                <?= app_format_money($lead->lead_value, get_base_currency()); ?>
                            </span>
                            <?php } ?>
                        </div>

                        <?php if ($lead->email) { ?>
                        <p class="tw-text-xs tw-text-neutral-500 tw-mb-1 tw-truncate">
                            <i class="fa fa-envelope-o tw-mr-1"></i><?= e($lead->email); ?>
                        </p>
                        <?php } ?>

                        <?php if ($lead->phonenumber) { ?>
                        <p class="tw-text-xs tw-text-neutral-500 tw-mb-2 tw-truncate">
                            <i class="fa fa-phone tw-mr-1"></i><?= e($lead->phonenumber); ?>
                        </p>
                        <?php } ?>

                        <div class="tw-flex tw-items-center tw-justify-between tw-mt-2 tw-pt-2" style="border-top:1px solid #F1F5F9;">
                            <span class="tw-text-xs tw-text-neutral-400">
                                <?= time_ago($lead->dateadded); ?>
                            </span>
                            <a href="<?= admin_url('leads/index/' . $lead->id); ?>"
                               class="tw-text-xs tw-text-indigo-600 hover:tw-text-indigo-800">
                                View →
                            </a>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Drop placeholder when empty -->
                    <?php if (empty($leads)) { ?>
                    <div class="nexhq-empty-stage tw-text-center tw-py-8 tw-text-neutral-400 tw-text-xs">
                        <i class="fa fa-inbox fa-2x tw-mb-2 tw-block tw-opacity-30"></i>
                        No leads in this stage
                    </div>
                    <?php } ?>

                </div>
            </div>
            <?php } ?>

        </div>

    </div>
</div>

<?php init_tail(); ?>

<!-- SortableJS for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<style>
.nexhq-lead-card:active { cursor: grabbing; }
.nexhq-lead-card.sortable-ghost {
    opacity: 0.4;
    background: #EEF2FF !important;
    border: 2px dashed #4F46E5 !important;
}
.nexhq-lead-card.sortable-chosen {
    box-shadow: 0 8px 25px rgba(79,70,229,0.2) !important;
    transform: rotate(1deg);
}
.nexhq-stage-leads.sortable-over {
    background: #EEF2FF !important;
}
</style>

<script>
$(function() {
    var pipeline_id = <?= (int)$pipeline->id; ?>;

    // Initialize SortableJS on each stage column
    document.querySelectorAll('.sortable-leads').forEach(function(el) {
        Sortable.create(el, {
            group: 'nexhq-leads',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onOver: function(evt) {
                evt.to.classList.add('sortable-over');
            },
            onEnd: function(evt) {
                // Remove all over classes
                document.querySelectorAll('.sortable-leads').forEach(function(z) {
                    z.classList.remove('sortable-over');
                });

                var lead_id  = evt.item.dataset.leadId;
                var stage_id = evt.to.closest('.nexhq-kanban-column').dataset.stageId;

                // Update lead count badges
                updateStageCounts();

                // Save to server
                $.ajax({
                    url: '<?= admin_url('pipelines/update_stage'); ?>',
                    method: 'POST',
                    data: {
                        lead_id:     lead_id,
                        stage_id:    stage_id,
                        pipeline_id: pipeline_id
                    },
                    success: function(resp) {
                        var res = JSON.parse(resp);
                        if (!res.success) {
                            alert_float('danger', 'Failed to update lead stage.');
                        } else {
                            alert_float('success', 'Lead moved successfully.');
                        }
                    }
                });
            }
        });
    });

    function updateStageCounts() {
        document.querySelectorAll('.nexhq-kanban-column').forEach(function(col) {
            var count = col.querySelectorAll('.nexhq-lead-card').length;
            var badge = col.querySelector('.stage-lead-count');
            if (badge) badge.textContent = count;

            // Show/hide empty state
            var emptyEl = col.querySelector('.nexhq-empty-stage');
            if (emptyEl) {
                emptyEl.style.display = count === 0 ? 'block' : 'none';
            }
        });
    }
});
</script>

</body>
</html>
