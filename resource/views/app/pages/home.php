@extends('app.main')

@section('body')
<div class="row">
    <div class="col-3">
        <div class="card mb-2">
            <div class="card-header">
                Acme Account
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="fw-bold">Modes</div>
                    <div class="d-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-secondary flex-fill" data-mode="staging" href="<?= route('switch', ['mode' => 'staging']) ?>" class="text-warning">Stage</a>
                        <a class="btn btn-sm btn-secondary flex-fill" data-mode="prod" href="<?= route('switch', ['mode' => 'prod']) ?>" class="text-success">PROD</a>
                    </div>
                </div>

                <div>
                    <div class="fw-bold">Account ID<button class="btn btn-sm btn-warning text-dark ms-2" onclick="location.href = $('[data-mode].btn-primary').attr('href')">renew</button></div>
                    <div><?= App\Helpers\API::$autoSSL->ensureAccount() ?></div>
                </div>
            </div>
        </div>

        <div class="card mb-2">
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
                    <i class="fa fa-spinner fa-spin me-2"></i> loading
                </div>
            </div>
        </div>
    </div>
    <div class="col-9">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <button class="btn btn-secondary" data-load="/api/allcertificates" all-certificates-page>All Certificates</button>
                    <button class="btn btn-secondary" data-load="/api/certificates" certificates-page>Domain's Certificates</button>
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
    let lastdomain = null;

    function loadDomains() {
        $('[domains]').load(`<?= route('load-domains') ?>`);
    }
    loadDomains();

    $('[data-mode="<?= config('autossl.mode') ?? 'staging' ?>"]').removeClass('btn-secondary').addClass('btn-primary');

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
            $('[load-content]').html(`<i class="fa fa-spin fa-spinner me-2"></i> Loading...`);
            if ($(this).hasAttr('data-post')) {
                $.post($(this).attr('data-load'), parseQuery($(this).attr('data-post')), e => $('[load-content]').html(e));
            } else {
                $('[load-content]').load($(this).attr('data-load'));
            }
        });
    }
</script>
@endsection