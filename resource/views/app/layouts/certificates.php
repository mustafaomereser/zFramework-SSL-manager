<?php

use App\Helpers\API;
use zFramework\Core\Facades\Cookie;
?>

<div class="row">
    <div class="col-6"></div>
    <div class="col-6">
        <div class="d-flex align-items-center gap-2 justify-content-end">
            <button data-modal="<?= route('certificates.create') ?>" class="btn btn-success">Create Certificate</button>
        </div>
    </div>
</div>

<?= API::makeTable(json_decode(json_encode(API::$domain['certificates']() ?? [], JSON_UNESCAPED_UNICODE), true)) ?>

<script>
    init();

    $(() => {
        $('tr[data-key]').each(function() {
            let id = $(this).find('[data-key="id"]');
            let challengeAuth = $(this).find('[data-key="notifyChallenge_data"]').html();
            let cert = $(this).find('[data-key="cert"]').html();
            id.html(`
                ${id.html()}
                ${challengeAuth.length ? '<span class="badge">Challenged</span>' : `<button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.challenge') ?>'.replace('{id}', id.html())}">Challenge</button> <button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.upload-challenge') ?>'.replace('{id}', id.html())}">Upload Challenge</button>`}
                ${cert.length ? `<button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.install') ?>'.replace('{id}', id.html())}">Install SSL</button> <a class="btn btn-sm btn-secondary ms-2" href="${'<?= route('certificates.download') ?>'.replace('{id}', id.html())}" download>Download</a>` : ``}
            `);

        });

        setLOAD();
    });
</script>