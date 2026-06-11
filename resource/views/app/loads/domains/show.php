<div class="domain-panel mb-4">
    <div class="domain-panel-head">
        <span class="status-dot <?= $domain_status['status'] ?>" style="width:8px;height:8px"></span>
        <span class="domain-panel-name"><?= htmlspecialchars($item['fulldomain']) ?></span>
        <span class="pill <?= $domain_status['status'] ?> ms-1"><?= $domain_status['label'] ?></span>

        <div class="ms-auto domain-panel-actions">
            <?php if ($domain_status['days_left'] !== null): ?>
                <div class="expiry-bar align-items-center" style="flex-direction:row;gap:8px">
                    <div class="bar-track" style="width:60px">
                        <?php $pct = min(100, (int) ($domain_status['days_left'] / 90 * 100)); ?>
                        <div class="bar-fill <?= $domain_status['status'] ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="bar-txt"><?= $domain_status['days_left'] ?>d</div>
                </div>
            <?php endif ?>

            <!-- HTTP-01 auto-issue -->
            <button data-domain-issue="<?= $item['id'] ?>" class="btn btn-row issue btn-sm">
                <i class="fas fa-bolt me-1"></i>HTTP-01
            </button>

            <!-- DNS-01 issue (creates order, shows TXT in table) -->
            <button data-domain-issue-dns="<?= $item['id'] ?>" class="btn btn-row btn-sm">
                <i class="fas fa-globe me-1"></i>DNS-01
            </button>

            <button data-modal="<?= route('domains.create') ?>?main=<?= $item['id'] ?>" class="btn btn-accent btn-sm">
                <i class="fas fa-plus me-1"></i><span class="d-none d-md-inline">Add Subdomain</span><span class="d-md-none">Sub</span>
            </button>
            <button data-modal="<?= route('domains.edit', ['id' => $item['id']]) ?>" class="btn btn-ghost btn-sm">
                <i class="fas fa-pen"></i>
            </button>
        </div>
    </div>

    <?php if (count($item_subdomains)): ?>
        <div class="sub-list">
            <div class="sub-list-title">Subdomains</div>
            <?php foreach ($item_subdomains as $subdomain):
                $sd_status = App\Helpers\API::getSSLStatus($subdomain['fulldomain']);
                $sd_pct    = $sd_status['days_left'] !== null ? min(100, (int) ($sd_status['days_left'] / 90 * 100)) : 0;
            ?>
                <div class="sub-item">
                    <div class="sub-item-icon"><i class="fas fa-globe"></i></div>
                    <div class="flex-fill overflow-hidden">
                        <div class="sub-item-name"><?= htmlspecialchars($subdomain['fulldomain']) ?></div>
                        <?php if ($sd_status['days_left'] !== null): ?>
                            <div class="sub-item-path mt-1">
                                <div class="expiry-bar">
                                    <div class="bar-track">
                                        <div class="bar-fill <?= $sd_status['status'] ?>" style="width:<?= $sd_pct ?>%"></div>
                                    </div>
                                    <div class="bar-txt"><?= $sd_status['days_left'] ?> days left</div>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                    <span class="pill <?= $sd_status['status'] ?>"><?= $sd_status['label'] ?></span>
                    <div class="d-flex gap-1 ms-2 flex-wrap">
                        <button data-domain-issue="<?= $subdomain['id'] ?>" class="btn btn-row issue btn-sm">
                            <i class="fas fa-bolt me-1"></i><span class="d-none d-sm-inline">HTTP-01</span>
                        </button>
                        <button data-domain-issue-dns="<?= $subdomain['id'] ?>" class="btn btn-row btn-sm">
                            <i class="fas fa-globe me-1"></i><span class="d-none d-sm-inline">DNS-01</span>
                        </button>
                        <button data-modal="<?= route('domains.edit', ['id' => $subdomain['id']]) ?>" class="btn btn-ghost btn-sm">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:28px">
            <div class="empty-icon" style="width:44px;height:44px;font-size:16px"><i class="fas fa-sitemap"></i></div>
            <div class="empty-title" style="font-size:13px">No subdomains</div>
            <div class="empty-sub" style="font-size:11px">Click "Add Subdomain" to get started</div>
        </div>
    <?php endif ?>
</div>

<script>
    $('[data-domain="<?= $item['fulldomain'] ?>"]').trigger('active');
</script>
