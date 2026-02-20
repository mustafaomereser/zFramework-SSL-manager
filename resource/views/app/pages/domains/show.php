@extends('app.main')
@section('body')
<div class="domain-panel mb-4">
    <div class="domain-panel-head">
        <span class="status-dot <?= $domain_status['status'] ?>" style="width:8px;height:8px"></span>
        <span class="domain-panel-name"><?= $item['fulldomain'] ?></span>
        <span class="pill <?= $domain_status['status'] ?> ms-1"><?= $domain_status['label'] ?></span>

        <div class="ms-auto d-flex gap-2">
            <div class="expiry-bar align-items-center" style="flex-direction: unset">
                <div class="bar-track">
                    <div class="bar-fill <?= $domain_status['status'] ?>" style="width:<?= $domain_status['days_left'] ?>%"></div>
                </div>
                <div class="bar-txt"><?= $domain_status['days_left'] ?> days left</div>
            </div>

            <div class="d-flex gap-1 ms-2">
                <button data-domain-issue="<?= $item['id'] ?>" class="btn btn-row issue btn-sm">Issue SSL</button>
            </div>

            <button data-modal="<?= route('domains.create') ?>?main=<?= $item['id'] ?>" class="btn btn-accent btn-sm">
                <i class="fas fa-plus me-1"></i>Add Subdomain
            </button>
            <button data-modal="<?= route('domains.edit', ['id' => $item['id']]) ?>" class="btn btn-ghost btn-sm">
                <i class="fas fa-pen me-1"></i>Edit
            </button>
        </div>
    </div>

    <?php if (count($item_subdomains)): ?>
        <div class="sub-list">
            <div class="sub-list-title">Subdomains</div>
            <?php foreach ($item_subdomains as $subdomain): $domain_status = App\Helpers\API::getSSLStatus($subdomain['fulldomain']) ?>
                <div class="sub-item">
                    <div class="sub-item-icon"><i class="fas fa-globe"></i></div>
                    <div class="flex-fill overflow-hidden">
                        <div class="sub-item-name"><?= $subdomain['fulldomain'] ?></div>
                        <div class="sub-item-path mt-2">
                            <?php if ($domain_status['days_left']): ?>
                                <div class="expiry-bar">
                                    <div class="bar-track">
                                        <div class="bar-fill <?= $domain_status['status'] ?>" style="width:<?= $domain_status['days_left'] ?>%"></div>
                                    </div>
                                    <div class="bar-txt"><?= $domain_status['days_left'] ?> days left</div>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                    <span class="pill <?= $domain_status['status'] ?>"><?= $domain_status['label'] ?></span>
                    <div class="d-flex gap-1 ms-2">
                        <button data-domain-issue="<?= $subdomain['id'] ?>" class="btn btn-row issue btn-sm">Issue SSL</button>
                        <button data-modal="<?= route('domains.edit', ['id' => $subdomain['id']]) ?>" class="btn btn-ghost btn-sm">
                            <i class="fas fa-pen me-1"></i>Edit
                        </button>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="empty-state" id="noSubsState" style="padding:28px">
            <div class="empty-icon" style="width:44px;height:44px;font-size:16px">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="empty-title" style="font-size:13px">No subdomains</div>
            <div class="empty-sub" style="font-size:11px">Click "Add Subdomain" to get started</div>
        </div>
    <?php endif ?>
</div>
@endsection
@section('footer')
<script>
    $('[data-domain="<?= $item['fulldomain'] ?>"]').trigger('active');
</script>
@endsection