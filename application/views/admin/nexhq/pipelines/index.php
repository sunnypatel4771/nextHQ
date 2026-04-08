<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">

        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
            <div>
                <h4 class="tw-text-xl tw-font-bold tw-text-neutral-800 tw-mb-1">Pipelines</h4>
                <p class="tw-text-sm tw-text-neutral-500">Manage your sales pipelines and stages</p>
            </div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#create-pipeline-modal">
                <i class="fa fa-plus"></i> New Pipeline
            </button>
        </div>

        <div class="row">
            <?php foreach ($pipelines as $p) { ?>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                            <h5 class="tw-font-semibold tw-text-neutral-800 tw-m-0">
                                <?= e($p->name); ?>
                                <?php if ($p->is_default) { ?>
                                <span class="badge bg-primary tw-ml-2">Default</span>
                                <?php } ?>
                            </h5>
                        </div>
                        <p class="tw-text-xs tw-text-neutral-500 tw-mb-4">
                            Created <?= time_ago($p->created_at); ?>
                        </p>
                        <div class="tw-flex tw-gap-2">
                            <a href="<?= admin_url('pipelines/kanban/' . $p->id); ?>"
                               class="btn btn-primary btn-sm">
                                <i class="fa fa-columns"></i> Open Kanban
                            </a>
                            <?php if (!$p->is_default) { ?>
                            <a href="<?= admin_url('pipelines/delete/' . $p->id); ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this pipeline?')">
                                <i class="fa fa-trash"></i>
                            </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>

    </div>
</div>

<!-- Create Pipeline Modal -->
<div class="modal fade" id="create-pipeline-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title tw-font-bold">Create New Pipeline</h4>
            </div>
            <?= form_open(admin_url('pipelines/create')); ?>
            <div class="modal-body">
                <?= render_input('name', 'Pipeline Name', '', 'text', ['placeholder' => 'e.g. Sales Pipeline', 'required' => true]); ?>
                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="is_default" id="is_default" value="1">
                    <label for="is_default">Set as default pipeline</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Pipeline</button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>
