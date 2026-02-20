<?php $edit = isset($item['id']) ?>
<script>
    domainModal = currentModal;
    modalTitle(domainModal, 'Domain <?= $edit ? 'edit' : 'create' ?>');
    modalResize(domainModal, 'lg');
</script>
<div class="modal-body">
    <form id="domain-form">
        <?= csrf() . ($edit ? inputMethod('PATCH') : NULL) ?>

        <div class="form-group row align-items-center mb-2">
            <div for="name" class="col-4 fw-bold">Domain</div>
            <div class="col-8">
                <input type="text" class="form-control" name="domain" id="domain" value="<?= @$item['domain'] ?>" placeholder="Domain Name" required>
            </div>
        </div>

        <div class="form-group row align-items-center mb-2">
            <div for="public_dir" class="col-4 fw-bold">Public Dir</div>
            <div class="col-8">
                <input type="text" class="form-control" name="public_dir" id="public_dir" value="<?= @$item['public_dir'] ?>" placeholder="Public Dir" required>
            </div>
        </div>

        <h4>cPanel API</h4>
        <?php $item['cpanel'] = json_decode($item['cpanel'] ?? '[]', true) ?>
        <div class="form-group row align-items-center mb-2">
            <div for="cpanel-username" class="col-4 fw-bold">cPanel Username <i class="fa fa-info-circle text-info" data-toggle="tooltip" title="If you using a shared hosting you must wrote your hosting username."></i></div>
            <div class="col-8">
                <input type="text" class="form-control" name="cpanel[username]" id="cpanel-username" value="<?= @$item['cpanel']['username'] ?>" placeholder="cPanel Username">
            </div>
        </div>
        <div class="form-group row align-items-center mb-2">
            <div for="cpanel-api-token" class="col-4 fw-bold">cPanel API TOKEN <i class="fa fa-info-circle text-info" data-toggle="tooltip" title="From cPanel->Manage API Tokens"></i></div>
            <div class="col-8">
                <input type="text" class="form-control" name="cpanel[api-token]" id="cpanel-api-token" value="<?= @$item['cpanel']['api-token'] ?>" placeholder="cPanel API TOKEN">
            </div>
        </div>

        <div class="form-group clearfix mt-4">
            <?php if (!$edit) : ?>
                <div class="float-end">
                    <button type="submit" class="btn btn-cst btn-outline-success"><i class="fa fa-save"></i> Create</button>
                </div>
            <?php else : ?>
                <div class="float-start">
                    <button type="button" class="btn btn-outline-danger" delete-account><i class="fa fa-trash-alt"></i> Delete</button>
                </div>
                <div class="float-end">
                    <button type="submit" class="btn btn-cst btn-outline-warning"><i class="fa fa-save"></i> Update</button>
                </div>
            <?php endif ?>
        </div>
    </form>
</div>


<?php if ($edit) : ?>
    <script>
        $('[delete-account]').on('click', function() {
            let btn = this;

            $.ask.do({
                onAccept: () => {
                    $.post('<?= route('domains.delete', ['id' => $item['id']]) ?>', {
                        _token: $('[name="_token"]').val(),
                        _method: 'DELETE'
                    }, e => {
                        if (e.status) return location.reload();
                        $.showAlerts(e.alerts);
                        $.core.btn.unset(btn);
                        $.ask.modal.modal('hide');
                    });
                }
            });
        });
    </script>
<?php endif ?>

<script>
    $('#domain-form').sbmt((form, btn) => {
        $.core.btn.spin(btn);
        $.post('<?= route('domains.' . ($edit ? 'update' : 'store'), ['id' => @$item['id']]) ?>', $.core.SToA(form), e => {
            if (e.status) return location.reload();
            $.core.btn.unset(btn);
        });
    });
</script>