@extends('app.main')

@section('body')
<?php

use App\Helpers\API;
use zFramework\Core\Facades\Cookie;
?>
<div class="row">
    <div class="col-3">
        <div class="card">
            <div class="card-header">
                <div class="clerfix">
                    <div class="float-start">
                        Domains
                    </div>
                    <div class="float-end">
                        <a data-modal="<?= route('domains.create') ?>" class="text-success"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div domains>
                    <?php foreach ($domains as $domain):
                        $checkSSL = null;
                        try {
                            error_reporting(0);
                            $checkSSL = API::$autoSSL->checkSSL($domain['domain']);
                        } catch (\Throwable $e) {
                            error_reporting(E_ALL);
                        }
                    ?>
                        <div class="card mb-2 border-2" data-domain-key="<?= $domain['id'] ?>">
                            <div class="card-body p-2">
                                <a data-modal="<?= route('domains.edit', ['id' => $domain['id']]) ?>" class="text-warning float-end"><i class="fa fa-pencil"></i></a>
                                <div><?= $domain['domain'] ?></div>
                                <?php if ($checkSSL): ?>
                                    <small class="text-muted"><?= \zFramework\Core\Helpers\Date::format($checkSSL['last_date'], 'd.m.Y H:i:s') ?> last date. <?= $checkSSL['days_left'] ?> days left.</small>
                                <?php else: ?>
                                    <small class="text-danger">no SSL.</small>
                                <?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-9">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <button class="btn btn-secondary" data-load="/api/certificates" certificates-page>Certificates</button>
                </div>

                <div class="container-fluid" style="overflow: auto">
                    <div load-content></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
    function parseQuery(query) {
        const params = new URLSearchParams(query);
        const obj = {};
        for (const [key, value] of params.entries()) assignNested(obj, key, value);
        return obj;
    }

    function assignNested(obj, key, value) {
        // "user[name]" → ["user", "name"]
        const parts = key
            .replace(/\]/g, '')
            .split(/\[/)
            .filter(Boolean);

        let current = obj;

        for (let i = 0; i < parts.length; i++) {
            const part = parts[i];
            const next = parts[i + 1];

            if (next === undefined) {
                // Son eleman
                if (part === '') {
                    // boş key array push için: tags[]=
                    if (!Array.isArray(current)) current = [];
                    current.push(value);
                } else if (Array.isArray(current[part])) {
                    current[part].push(value);
                } else if (current[part] !== undefined) {
                    current[part] = [current[part], value];
                } else {
                    current[part] = value;
                }
            } else {
                // Eğer yoksa, bir obje veya array oluştur
                if (!current[part]) {
                    current[part] = /^\d+$/.test(next) ? [] : {};
                }
                current = current[part];
            }
        }
    }


    function setLOAD() {
        $('[data-load]').off('click').on('click', function() {
            $('[load-content]').html(`<i class="fa fa-spin fa-spinner me-2"></i> Yükleniyor...`);
            if ($(this).hasAttr('data-post')) {
                $.post($(this).attr('data-load'), parseQuery($(this).attr('data-post')), e => $('[load-content]').html(e));
            } else {
                $('[load-content]').load($(this).attr('data-load'));
            }
        });
    }

    $(() => {
        setLOAD();
        let domains = $('[data-domain-key]');
        domains.on('click', function() {
            domains.removeClass('border-success');
            $(this).addClass('border-success');
            $.get('<?= route('domains.index') ?>?key=' + $(this).attr('data-domain-key'));
            $('[load-content]').html(null);
        });

        $('[data-domain-key="<?= Cookie::get('domain') ?? 0 ?>"]').click();
    });
</script>
@endsection