@extends('app.main')

@section('body')
<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-3">
        <div class="stat-card c-indigo">
            <div class="stat-icon"><i class="fas fa-certificate"></i></div>
            <div class="stat-num">12</div>
            <div class="stat-lbl">Total Certificates</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card c-emerald">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-num">8</div>
            <div class="stat-lbl">Valid & Active</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card c-amber">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-num">2</div>
            <div class="stat-lbl">Expiring Soon</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card c-rose">
            <div class="stat-icon"><i class="fas fa-times"></i></div>
            <div class="stat-num">2</div>
            <div class="stat-lbl">Expired</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs" id="certTabs">
    <li class="nav-item">
        <a class="nav-link active" data-tab="all" href="#">
            All Certificates
            <span class="tab-count"><?= count($certificates) ?></span>
        </a>
    </li>
    <?php /*
    <li class="nav-item">
        <a class="nav-link" data-tab="domain" href="#">
            Domain's Certificates
            <span class="tab-count">4</span>
        </a>
    </li>
     */
    ?>
</ul>

<!-- Table -->
<div class="table-card">
    <div class="table-toolbar">
        <div class="search-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" id="certSearch" placeholder="Search domain...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-borderless mb-0">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Type</th>
                    <th>SANs</th>
                    <th>Issued</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="certBody">
                <?php foreach ($certificates as $certificate):
                    $order    = json_decode($certificate['order_data'], true);
                    $daysLeft = @max(0, (int) ceil((strtotime($certificate['last_date']) - strtotime($certificate['created_at'])) / 86400));
                ?>
                    <tr data-domain="<?= $certificate['domain'] ?>" data-cert-id="<?= $certificate['id'] ?>">
                        <td>
                            <div class="fw-semibold"><?= $order['body']['identifier']['value'] ?></div>
                            <div class="cell-hint"><?= implode(', ', array_diff([$order['body']['identifier']['value']], array_column($order['order']['identifiers'] ?? [], 'value'))) ?></div>
                        </td>
                        <td><?= strstr($certificate['challenge_data'], 'staging') ? '<span class="badge bg-warning text-dark ms-2">Staging SSL</span>' : '<span class="badge bg-success ms-2">Prod SSL</span>' ?></td>
                        <td style="color:var(--txt-3);font-size:12px"><?= count($order['order']['identifiers']) ?> domains</td>
                        <td style="color:var(--txt-3);font-size:12px"><?= zFramework\Core\Helpers\Date::format($certificate['created_at'], 'd M Y') ?></td>
                        <td>
                            <?= zFramework\Core\Helpers\Date::format($certificate['last_date'], 'd M Y') ?>
                            <div class="expiry-bar">
                                <div class="bar-track">
                                    <div class="bar-fill ok" style="width:<?= $daysLeft ?>%"></div>
                                </div>
                                <div class="bar-txt"><?= $daysLeft ?> days left</div>
                            </div>
                        </td>
                        <td><span class="pill <?= $domain_status['status'] ?>"><?= $domain_status['label'] ?></span></td>
                        <td>
                            <div class="d-flex justify-content-end gap-1">
                                <?php if ($certificate['notifyChallenge_data']): ?>
                                    <span class="pill pend">Challenged</span>
                                <?php else: ?>
                                    <?php if ($certificate['upload_challenge_data']): ?>
                                        <span class="pill ok">Challenge Uploaded</span>
                                    <?php else: ?>
                                        <button class="btn btn-row btn-sm" data-cert-step="1" data-cert-id="<?= $certificate['id'] ?>">
                                            <i class="fas fa-upload me-1"></i>Upload Challenge
                                        </button>
                                    <?php endif ?>
                                    <button class="btn btn-row issue btn-sm" data-cert-step="2" data-cert-id="<?= $certificate['id'] ?>">
                                        <i class="fas fa-bolt me-1"></i>Try Challenge
                                    </button>
                                <?php endif ?>

                                <?php if ($certificate['cert']): ?>
                                    <?php if ($certificate['install_ssl_data']): ?>
                                        <span class="pill ok">SSL Installed</span>
                                    <?php else: ?>
                                        <button class="btn btn-row issue btn-sm" data-cert-step="3" data-cert-id="<?= $certificate['id'] ?>">
                                            <i class="fas fa-shield me-1"></i>Install SSL
                                        </button>
                                    <?php endif ?>
                                    <a class="btn btn-row btn-sm" href="<?= route('certificates.download', ['id' => $certificate['id']]) ?>" download>
                                        <i class="fas fa-download me-1"></i>Download CERT
                                    </a>
                                <?php endif ?>

                                <button class="btn btn-row danger btn-sm" data-delete-cert="<?= $certificate['id'] ?>">
                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <div class="d-none" id="noResults">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <div class="empty-title">No results</div>
            <div class="empty-sub">Try a different search term</div>
        </div>
    </div>
</div>
@endsection