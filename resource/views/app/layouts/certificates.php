<?php

use App\Helpers\API;
use zFramework\Core\Csrf;

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
            let item = $(this).find('[data-key="id"]');
            let id = item.html();

            let challengeAuth = $(this).find('[data-key="notifyChallenge_data"]').html();
            let challenge_data = $(this).find('[data-key="challenge_data"]').html();
            let cert = $(this).find('[data-key="cert"]').html();
            let install_ssl_data = $(this).find('[data-key="install_ssl_data"]').html();

            item.append(challenge_data.indexOf('staging') > -1 ? `<span class="badge bg-warning text-dark ms-2">Staging SSL</span>` : `<span class="badge bg-success ms-2">Prod SSL</span>`);
            if (challengeAuth.length) {
                item.append(`<span class="badge">Challenged</span>`);
            } else {
                item.append(`
                    <button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.upload-challenge') ?>'.replace('{id}', id)}">Upload Challenge (with cPanel API)</button>
                    <button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.challenge') ?>'.replace('{id}', id)}">Try Challenge</button>
                `);
            }
            if (cert.length) {
                item.append(`
                    ${
                        (install_ssl_data.length ? 
                            '<span class="badge">SSL Installed</span>' : 
                            `<button class="btn btn-sm btn-secondary ms-2" data-load="${'<?= route('certificates.install') ?>'.replace('{id}', id)}">Install SSL</button>`
                        )
                    }
                    <a class="btn btn-sm btn-secondary ms-2" href="${'<?= route('certificates.download') ?>'.replace('{id}', id)}" download>Download CERT</a>
                `);
            }

            item.append(`<button class="btn btn-sm btn-danger ms-2" data-delete-cert="${id}">Delete SSL</button>`);
        });

        $('[data-delete-cert]').on('click', function() {
            $.ask.do({
                onAccept: () => {
                    $.post('<?= route('certificates.delete') ?>'.replace('{id}', $(this).attr('data-delete-cert')), {
                        _token: '<?= Csrf::get() ?>',
                        _method: 'DELETE'
                    }, e => {
                        $.showAlerts(e.alerts);
                        $('[certificates-page]').click();
                        $.ask.hide();
                    });
                }
            })
        });

        setLOAD();
    });
</script>