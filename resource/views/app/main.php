<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>zFramework AutoSSL — Let's Encrypt Manager</title>

    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('/assets/libs/notify/style.css') ?>" />
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>" />
    @yield('header')
</head>

<body>
    <div class="shell">
        <aside class="sidebar">
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

            <div class="d-flex flex-column" style="flex:1; overflow:hidden;">
                <div class="domains-header">
                    <span class="section-title">Domains</span>
                    <a class="icon-btn" data-modal="<?= route('domains.create') ?>">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>

                <div class="domain-list">
                    <?php foreach ($domains as $domain):
                        $subdomains    = $domain['subdomains']();
                        $domain_status = App\Helpers\API::getSSLStatus($domain['domain']);
                    ?>
                        <div class="domain-item" href="<?= route('domains.show', ['id' => $domain['id']]) ?>" data-domain="<?= $domain['fulldomain'] ?>">
                            <div class="domain-item-head">
                                <span class="status-dot <?= $domain_status['status'] ?>"></span>
                                <span class="domain-name"><?= $domain['domain'] ?></span>
                                <span class="sub-badge <?= $domain_status['status'] ?>"><?= $domain_status['label'] ?></span>
                            </div>
                            <?php if (count($subdomains)): ?>
                                <div class="sub-badges">
                                    <?php foreach ($subdomains as $subdomain): $domain_status = App\Helpers\API::getSSLStatus($subdomain['fulldomain']) ?>
                                        <span class="sub-badge <?= $domain_status['status'] ?>"><span class="dot"></span><?= $subdomain['domain'] ?></span>
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
            </div><!-- /content-body -->
        </div><!-- /main-content -->

    </div><!-- /shell -->

    <!-- ══════════ MODAL: Add Domain ══════════ -->
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
                            <label class="col-4 modal-form-label">Domain</label>
                            <div class="col-8">
                                <input type="text" class="form-control" name="domain" placeholder="yourdomain.com" required>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <label class="col-4 modal-form-label">Public Dir</label>
                            <div class="col-8">
                                <input type="text" class="form-control" name="public_dir" placeholder="/home/user/public_html">
                            </div>
                        </div>

                        <div class="divider-label my-3"><span>cPanel API</span></div>

                        <div class="row align-items-center mb-3">
                            <label class="col-4 modal-form-label">
                                cPanel Username
                                <i class="fas fa-circle-info" style="color:var(--sky);cursor:help" title="Your shared hosting username"></i>
                            </label>
                            <div class="col-8">
                                <input type="text" class="form-control" name="cpanel[username]" placeholder="cpanelusername">
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <label class="col-4 modal-form-label">
                                API Token
                                <i class="fas fa-circle-info" style="color:var(--sky);cursor:help" title="cPanel → Manage API Tokens"></i>
                            </label>
                            <div class="col-8">
                                <input type="text" class="form-control" name="cpanel[api-token]" placeholder="cPanel API Token">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex gap-2 justify-content-end">
                    <button class="btn btn-danger-o" data-bs-dismiss="modal">
                        <i class="fas fa-xmark me-1"></i>Cancel
                    </button>
                    <button type="submit" form="domainForm" class="btn btn-success-o">
                        <i class="fas fa-floppy-disk me-1"></i>Create
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                            <label class="col-4 modal-form-label">Subdomain</label>
                            <div class="col-8">
                                <input type="text" class="form-control" id="subInput" name="domain"
                                    placeholder="api (before the dot)" required>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <label class="col-4 modal-form-label">Full Domain</label>
                            <div class="col-8">
                                <span id="subPreview" style="font-size:13px;font-weight:700;color:var(--indigo-2);font-family:'DM Mono',monospace">
                                    sub.example.com
                                </span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <label class="col-4 modal-form-label">Public Dir</label>
                            <div class="col-8">
                                <input type="text" class="form-control" name="public_dir"
                                    placeholder="/home/user/public_html/api" required>
                            </div>
                        </div>
                        <div class="info-banner">
                            <i class="fas fa-circle-info"></i>
                            cPanel API settings are inherited from the main domain.
                        </div>
                    </form>
                </div>
                <div class="modal-footer d-flex gap-2 justify-content-end">
                    <button class="btn btn-danger-o" data-bs-dismiss="modal">
                        <i class="fas fa-xmark me-1"></i>Cancel
                    </button>
                    <button type="submit" form="subdomainForm" class="btn btn-success-o">
                        <i class="fas fa-floppy-disk me-1"></i>Create
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="load-modals"></div>

    <div class="modal fade" id="ask-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/libs/notify/script.js"></script>
    <script>
        $.showAlerts(<?= json_encode(\zFramework\Core\Facades\Alerts::get()) ?>);
    </script>

    <script>
        $(() => {
            function percentage(value, total) {
                if (total <= 0) return 0;
                return Math.round((value / total) * 100 * 100) / 100;
            }

            $('[data-delete-cert]').on('click', function() {
                $.ask.do({
                    onAccept: () => {
                        $.post('<?= route('certificates.delete') ?>'.replace('{id}', $(this).attr('data-delete-cert')), {
                            _token: '<?= zFramework\Core\Csrf::get() ?>',
                            _method: 'DELETE'
                        }, e => {
                            $.showAlerts(e.alerts);
                            $(`[data-cert-id="${$(this).attr('data-delete-cert')}"]`).remove();
                            $('#certSearch').trigger('input');
                            $.ask.hide();
                        });
                    }
                })
            });


            let certCallbacks = [
                (donecallback, id) => $.get(`<?= route('certificates.create') ?>?id=${id}`, e => donecallback(e, e.cert.id)),
                (donecallback, cert_id) => $.get('<?= route('certificates.upload-challenge') ?>'.replace('{id}', cert_id), e => donecallback(e)),
                (donecallback, cert_id) => $.get('<?= route('certificates.challenge') ?>'.replace('{id}', cert_id), e => donecallback(e)),
                (donecallback, cert_id) => $.get('<?= route('certificates.install') ?>'.replace('{id}', cert_id), e => donecallback(e))
            ];


            $('[data-cert-step]').on('click', function() {
                $.core.btn.spin(this);
                certCallbacks[$(this).attr('data-cert-step')](e => ($.showAlerts(e.alerts), $.core.btn.unset(this)), $(this).attr('data-cert-id'));
            });

            $('[data-domain-issue]').on('click', function() {
                let btn = $(this);

                let step = 0;
                let cert_id = null;

                let donecallback = (e, id = null) => {
                    if (id) cert_id = id;
                    $.showAlerts(e.alerts);
                    $.core.btn.unset(btn);
                    step++;
                    if (step == certCallbacks.length) return;
                    selectstep(step);
                };

                let selectstep = step => {
                    setTimeout(() => $.core.btn.spin(btn));
                    certCallbacks[step](donecallback, cert_id || $(this).attr('data-domain-issue'));
                };

                selectstep(0);
            });

            activeTab = 'all';
            // Domain select
            $(document).on('active', '.domain-item', function(e) {
                e.preventDefault();
                $('.domain-item').removeClass('active');
                $(this).addClass('active');
                activeDomain = $(this).data('domain');
                $('#topbarDomain, #panelDomain').text(activeDomain);
                if (activeTab === 'domain') filterTable();
            });

            // Tabs
            $('#certTabs .nav-link').on('click', function(e) {
                e.preventDefault();
                $('#certTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                activeTab = $(this).data('tab');
                filterTable();
            });

            // Search
            $('#certSearch').on('input', filterTable).trigger('input');

            function filterTable() {
                const q = $('#certSearch').val().toLowerCase().trim();
                let visible = 0;
                $('#certBody tr').each(function() {
                    const domain = ($(this).data('domain') || '').toLowerCase();
                    const matchSearch = !q || $(this).text().toLowerCase().includes(q);
                    const matchTab = activeTab === 'all' || domain === activeDomain.toLowerCase();
                    $(this).toggle(matchSearch && matchTab);
                    if (matchSearch && matchTab) visible++;
                });
                $('#noResults').toggleClass('d-none', visible > 0);
            }

            function switchmode(mode, cb = null) {
                $.get('<?= route('switch') ?>'.replace('{mode}', mode), e => {
                    $('[account-id]').html(e.token);
                    if (cb) cb();
                });
            }

            // Mode toggle
            $('.env-opt').on('active', function(e) {
                e.preventDefault();
                $('.env-opt').removeClass('is-staging is-prod selected');
                const isProd = $(this).data('mode') === 'prod';
                $(this).addClass(isProd ? 'is-prod selected' : 'is-staging selected');
                $('#envChip').removeClass('staging prod').addClass(isProd ? 'prod' : 'staging');
                $('#envLabel').text(isProd ? 'Production' : 'Staging');
            }).on('click', function() {
                switchmode($(this).data('mode'), () => $(this).trigger('active'));
            });

            // Renew spin
            $('#renewBtn').on('click', function() {
                const $i = $(this).find('i').addClass('spinning');
                switchmode($('.env-opt.selected').data('mode'), () => $i.removeClass('spinning'));
            });

            $('[data-mode="<?= config('autossl.mode') ?? 'staging' ?>"]').trigger('active');
        });
    </script>
    @yield('footer')
</body>

</html>