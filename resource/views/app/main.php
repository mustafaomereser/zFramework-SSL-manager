<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoSSL — Let's Encrypt Manager</title>

    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/assets/libs/notify/style.css') ?>" />
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>" />
    @yield('header')
</head>

<body>

<!-- Mobile sidebar backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="logo-wrap" href="/">
            <div class="logo-icon"><i class="fas fa-lock"></i></div>
            <div>
                <div class="logo-name">AutoSSL</div>
                <div class="logo-tagline">Let's Encrypt Manager</div>
            </div>
        </div>

        <div class="env-section">
            <div class="env-label">Environment</div>
            <div class="env-toggle">
                <a class="env-opt" data-mode="staging">Staging</a>
                <a class="env-opt" data-mode="prod">Production</a>
            </div>
        </div>

        <div class="d-flex flex-column" style="flex:1;overflow:hidden;">
            <div class="domains-header">
                <span class="section-title">Domains</span>
                <a class="icon-btn" data-modal="<?= route('domains.create') ?>">
                    <i class="fas fa-plus"></i>
                </a>
            </div>

            <div class="domain-list">
                <?php foreach ($domains as $domain):
                    $subdomains = $domain['subdomains']();
                ?>
                    <div class="domain-item"
                         data-load="<?= route('domains.show', ['id' => $domain['id']]) ?>"
                         data-domain="<?= $domain['fulldomain'] ?>"
                         data-ssl-id="<?= $domain['id'] ?>">
                        <div class="domain-item-head">
                            <span class="status-dot status-loading" data-ssl-dot></span>
                            <span class="domain-name"><?= $domain['domain'] ?></span>
                            <span class="sub-badge" data-ssl-badge style="display:none"></span>
                        </div>
                        <?php if (count($subdomains)): ?>
                            <div class="sub-badges">
                                <?php foreach ($subdomains as $subdomain): ?>
                                    <span class="sub-badge status-loading" data-ssl-id="<?= $subdomain['id'] ?>">
                                        <span class="dot"></span><?= $subdomain['domain'] ?>
                                    </span>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <!-- ACME Account -->
        <div class="sidebar-footer">
            <div class="section-title mb-2">ACME Account</div>
            <div class="acct-box">
                <div class="acct-box-label">Account ID</div>
                <div class="acct-box-val" account-id><?= @end(explode('/', App\Helpers\API::$autoSSL->ensureAccount())) ?></div>
            </div>
            <div class="acct-row">
                <div class="acct-avatar"><i class="fas fa-user" style="font-size:11px"></i></div>
                <div class="acct-meta flex-fill">
                    <div class="acct-meta-line">ACME v2 · Let's Encrypt</div>
                </div>
                <button class="btn-renew" id="renewBtn">
                    <i class="fas fa-sync"></i> Renew
                </button>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <!-- Hamburger (mobile only) -->
            <button class="topbar-ham" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <span class="topbar-title">Certificates</span>
            <div class="ms-auto">
                <span class="env-chip" id="envChip">
                    <span class="blink-dot"></span>
                    <span id="envLabel"></span>
                </span>
            </div>
        </div>

        <div class="content-body">
            @yield('body')
        </div>
    </div>
</div><!-- /shell -->

<!-- Modal: Add/Edit Domain -->
<div class="modal fade" id="modalDomain" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="domainForm">
                    <div class="row align-items-center mb-3">
                        <label class="col-sm-4 modal-form-label">Domain</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="domain" placeholder="yourdomain.com" required>
                        </div>
                    </div>
                    <div class="divider-label my-3"><span>cPanel API</span></div>
                    <div class="row align-items-center mb-3">
                        <label class="col-sm-4 modal-form-label">cPanel Username</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="cpanel[username]" placeholder="cpanelusername">
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <label class="col-sm-4 modal-form-label">API Token</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="cpanel[api-token]" placeholder="cPanel API Token">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex gap-2 justify-content-end">
                <button class="btn btn-danger-o" data-bs-dismiss="modal"><i class="fas fa-xmark me-1"></i>Cancel</button>
                <button type="submit" form="domainForm" class="btn btn-success-o"><i class="fas fa-floppy-disk me-1"></i>Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Subdomain -->
<div class="modal fade" id="modalSubdomain" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subdomain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="subdomainForm">
                    <div class="row align-items-center mb-3">
                        <label class="col-sm-4 modal-form-label">Subdomain</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="subInput" name="domain" placeholder="api" required>
                        </div>
                    </div>
                    <div class="row align-items-center mb-3">
                        <label class="col-sm-4 modal-form-label">Full Domain</label>
                        <div class="col-sm-8">
                            <span id="subPreview" style="font-size:13px;font-weight:700;color:var(--indigo-2);font-family:'DM Mono',monospace">sub.example.com</span>
                        </div>
                    </div>
                    <div class="info-banner">
                        <i class="fas fa-circle-info"></i>
                        cPanel API settings are inherited from the main domain.
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex gap-2 justify-content-end">
                <button class="btn btn-danger-o" data-bs-dismiss="modal"><i class="fas fa-xmark me-1"></i>Cancel</button>
                <button type="submit" form="subdomainForm" class="btn btn-success-o"><i class="fas fa-floppy-disk me-1"></i>Create</button>
            </div>
        </div>
    </div>
</div>

<div id="load-modals"></div>

<!-- Modal: Confirm action -->
<div class="modal fade" id="ask-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button class="btn btn-cst btn-success" accept-btn></button>
                <button class="btn btn-cst btn-danger" data-bs-dismiss="modal" decline-btn></button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
<script src="/assets/libs/notify/script.js"></script>

<script>
$.showAlerts(<?= json_encode(\zFramework\Core\Facades\Alerts::get()) ?>);
</script>

<script>
$(() => {
    /* ── Mobile sidebar ── */
    $('#sidebarToggle, #sidebarBackdrop').on('click', function () {
        $('body').toggleClass('sidebar-open');
    });

    /* ── Domain load ── */
    $(document).on('click', '[data-load]', function () {
        $('body').removeClass('sidebar-open');
        $('.content-body').html('<div class="d-flex align-items-center justify-content-center" style="height:200px"><i class="fas fa-spinner fa-spin me-2"></i> Yükleniyor...</div>');
        $.get($(this).data('load'), e => { $('.content-body').html(e); init(); });
    });

    /* ── Domain select (active state) ── */
    $(document).on('active', '.domain-item', function (e) {
        e.preventDefault();
        $('.domain-item').removeClass('active');
        $(this).addClass('active');
        activeDomain = $(this).data('domain');
        if (activeTab === 'domain') filterTable();
    });

    /* ── Async SSL status ── */
    function loadSslStatuses() {
        $.get('/api/sslStatusBatch', function (data) {
            Object.entries(data).forEach(([id, s]) => {
                const intId = parseInt(id);

                // Main domain item dot + badge
                const $item = $('[data-ssl-id="' + intId + '"].domain-item');
                if ($item.length) {
                    $item.find('[data-ssl-dot]')
                         .removeClass('status-loading')
                         .addClass(s.status);
                    if (s.label && s.label !== 'No SSL') {
                        $item.find('[data-ssl-badge]')
                             .addClass(s.status)
                             .text(s.label)
                             .show();
                    }
                }

                // Subdomain badges inside domain items
                const $badge = $('[data-ssl-id="' + intId + '"].sub-badge');
                if ($badge.length) {
                    $badge.removeClass('status-loading').addClass(s.status);
                }
            });
        });
    }
    loadSslStatuses();

    /* ── Certificate step callbacks ── */
    let certCallbacks = [
        (cb, id)      => $.get('<?= route('certificates.create') ?>?id=' + id, e => cb(e, e.cert ? e.cert.id : null)),
        (cb, cert_id) => $.get('<?= route('certificates.upload-challenge') ?>'.replace('{id}', cert_id), e => cb(e)),
        (cb, cert_id) => $.get('<?= route('certificates.challenge') ?>'.replace('{id}', cert_id), e => cb(e)),
        (cb, cert_id) => $.get('<?= route('certificates.install') ?>'.replace('{id}', cert_id), e => cb(e)),
    ];

    /* Individual step button */
    $(document).on('click', '[data-cert-step]', function () {
        const step   = $(this).attr('data-cert-step');
        const certId = $(this).attr('data-cert-id');
        const btn    = this;

        if (step === 'dns-add') {
            $.core.btn.spin(btn);
            $.get('<?= route('certificates.add-dns-txt') ?>'.replace('{id}', certId), e => {
                $.showAlerts(e.alerts);
                $.core.btn.unset(btn);
            });
            return;
        }

        $.core.btn.spin(btn);
        certCallbacks[parseInt(step)]((e) => {
            $.showAlerts(e.alerts);
            $.core.btn.unset(btn);
        }, certId);
    });

    /* Delete certificate */
    $(document).on('click', '[data-delete-cert]', function () {
        const certId = $(this).attr('data-delete-cert');
        $.ask.do({
            onAccept: () => {
                $.post('<?= route('certificates.delete') ?>'.replace('{id}', certId), {
                    _token: '<?= zFramework\Core\Csrf::get() ?>',
                    _method: 'DELETE'
                }, e => {
                    $.showAlerts(e.alerts);
                    $('[data-cert-id="' + certId + '"]').closest('tr').remove();
                    $('#certSearch').trigger('input');
                    $.ask.hide();
                });
            }
        });
    });

    /* HTTP-01 auto-issue (all 4 steps) */
    $(document).on('click', '[data-domain-issue]', function () {
        const btn      = $(this);
        const domainId = $(this).attr('data-domain-issue');
        let step = 0, cert_id = null;

        const donecallback = (e, id = null) => {
            if (id) cert_id = id;
            $.showAlerts(e.alerts);
            $.core.btn.unset(btn);
            step++;
            if (step < certCallbacks.length) setTimeout(() => selectstep(step));
        };

        const selectstep = s => {
            $.core.btn.spin(btn);
            certCallbacks[s](donecallback, cert_id || domainId);
        };

        selectstep(0);
    });

    /* DNS-01 issue (only step 0, then reload to show TXT info) */
    $(document).on('click', '[data-domain-issue-dns]', function () {
        const btn      = $(this);
        const domainId = $(this).attr('data-domain-issue-dns');
        $.core.btn.spin(btn);
        $.get('<?= route('certificates.create') ?>?id=' + domainId + '&challenge_type=dns-01', e => {
            $.showAlerts(e.alerts);
            $.core.btn.unset(btn);
            if (e.status) location.reload();
        });
    });

    /* ── Tabs ── */
    activeTab = 'all';
    $('#certTabs .nav-link').on('click', function (e) {
        e.preventDefault();
        $('#certTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        activeTab = $(this).data('tab');
        filterTable();
    });

    /* ── Search ── */
    $('#certSearch').on('input', filterTable).trigger('input');

    function filterTable() {
        const q = $('#certSearch').val().toLowerCase().trim();
        let visible = 0;
        $('#certBody tr').each(function () {
            const domain     = ($(this).data('domain') || '').toLowerCase();
            const matchSearch = !q || $(this).text().toLowerCase().includes(q);
            const matchTab   = activeTab === 'all' || domain === (activeDomain || '').toLowerCase();
            $(this).toggle(matchSearch && matchTab);
            if (matchSearch && matchTab) visible++;
        });
        $('#noResults').toggleClass('d-none', visible > 0);
    }

    /* ── Mode toggle ── */
    function switchmode(mode, cb) {
        $.get('<?= route('switch') ?>'.replace('{mode}', mode), e => {
            $('[account-id]').html(e.token);
            if (cb) cb();
        });
    }

    $(document).on('active', '.env-opt', function (e) {
        e.preventDefault();
        $('.env-opt').removeClass('is-staging is-prod selected');
        const isProd = $(this).data('mode') === 'prod';
        $(this).addClass(isProd ? 'is-prod selected' : 'is-staging selected');
        $('#envChip').removeClass('staging prod').addClass(isProd ? 'prod' : 'staging');
        $('#envLabel').text(isProd ? 'Production' : 'Staging');
    }).on('click', '.env-opt', function () {
        switchmode($(this).attr('data-mode'), () => $(this).trigger('active'));
    });

    $(document).on('click', '#renewBtn', function () {
        const $i = $(this).find('i').addClass('spinning');
        switchmode($('.env-opt.selected').data('mode'), () => $i.removeClass('spinning'));
    });

    $('[data-mode="<?= config('autossl.mode') ?? 'staging' ?>"]').trigger('active');
});
</script>
@yield('footer')
</body>
</html>
