@extends('app.main')

@section('body')
<ul class="nav nav-tabs" id="certTabs">
    <li class="nav-item">
        <a class="nav-link active" data-tab="all" href="#">
            All Certificates
            <span class="tab-count"><?= count($certificates) ?></span>
        </a>
    </li>
</ul>

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
                    <th>Expires</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="certBody">
                <?php foreach ($certificates as $certificate):
                    $order         = json_decode($certificate['order_data'], true) ?? [];
                    $challenges    = json_decode($certificate['challenge_data'], true) ?? [];
                    $challengeType = $certificate['challenge_type']
                                     ?? (!empty($challenges) && isset($challenges[0]) ? ($challenges[0]['type'] ?? 'http-01') : 'http-01');
                    $daysLeft      = $certificate['last_date']
                                     ? max(0, (int) ceil((strtotime($certificate['last_date']) - time()) / 86400))
                                     : null;
                    $daysPercent   = $daysLeft !== null ? min(100, (int) ($daysLeft / 90 * 100)) : 0;
                    $certStatus    = match(true) {
                        $daysLeft === null  => ['status' => 'none', 'label' => 'Pending'],
                        $daysLeft <= 0     => ['status' => 'err',  'label' => 'Expired'],
                        $daysLeft <= 30    => ['status' => 'warn', 'label' => 'Expiring'],
                        default            => ['status' => 'ok',   'label' => 'OK'],
                    };
                    // Detect staging vs prod from order URL
                    $isStaging = str_contains(json_encode($order), 'staging');
                    // Primary domain for display
                    $primaryDomain = $certificate['domain'];
                ?>
                <tr data-domain="<?= htmlspecialchars($certificate['domain']) ?>" data-cert-id="<?= $certificate['id'] ?>">
                    <td data-label="Domain">
                        <div class="fw-semibold font-mono"><?= htmlspecialchars($primaryDomain) ?></div>
                        <?php if ($challengeType === 'dns-01' && !$certificate['cert'] && !empty($challenges)): ?>
                            <?php foreach ($challenges as $ch): if (($ch['type'] ?? '') === 'dns-01'): ?>
                                <div class="txt-record-info mt-1">
                                    <div class="cell-hint">TXT: <?= htmlspecialchars($ch['record'] ?? '') ?></div>
                                    <div class="txt-value"><?= htmlspecialchars($ch['value'] ?? '') ?></div>
                                </div>
                            <?php endif; endforeach ?>
                        <?php endif ?>
                    </td>
                    <td data-label="Type">
                        <?= $isStaging
                            ? '<span class="badge bg-warning text-dark">Staging</span>'
                            : '<span class="badge bg-success">Prod</span>' ?>
                        <span class="ms-1 badge <?= $challengeType === 'dns-01' ? 'bg-info text-dark' : 'bg-secondary' ?>">
                            <?= htmlspecialchars($challengeType) ?>
                        </span>
                    </td>
                    <td data-label="Expires">
                        <?php if ($certificate['last_date']): ?>
                            <?= zFramework\Core\Helpers\Date::format($certificate['last_date'], 'd M Y') ?>
                            <div class="expiry-bar">
                                <div class="bar-track">
                                    <div class="bar-fill <?= $certStatus['status'] ?>" style="width:<?= $daysPercent ?>%"></div>
                                </div>
                                <div class="bar-txt"><?= $daysLeft ?> days left</div>
                            </div>
                        <?php else: ?>
                            <span class="cell-hint">—</span>
                        <?php endif ?>
                    </td>
                    <td data-label="Status">
                        <span class="pill <?= $certStatus['status'] ?>"><?= $certStatus['label'] ?></span>
                    </td>
                    <td data-label="Actions">
                        <div class="action-btns">
                            <?php if (!$certificate['notifyChallenge_data']): ?>
                                <?php if ($challengeType === 'dns-01'): ?>
                                    <?php if (!$certificate['upload_challenge_data']): ?>
                                        <button class="btn btn-row btn-sm" data-cert-step="dns-add" data-cert-id="<?= $certificate['id'] ?>">
                                            <i class="fas fa-plus me-1"></i>Add TXT
                                        </button>
                                    <?php else: ?>
                                        <span class="pill pend">TXT Added</span>
                                    <?php endif ?>
                                    <button class="btn btn-row issue btn-sm" data-cert-step="2" data-cert-id="<?= $certificate['id'] ?>">
                                        <i class="fas fa-bolt me-1"></i>Verify DNS
                                    </button>
                                <?php else: ?>
                                    <?php if (!$certificate['upload_challenge_data']): ?>
                                        <button class="btn btn-row btn-sm" data-cert-step="1" data-cert-id="<?= $certificate['id'] ?>">
                                            <i class="fas fa-upload me-1"></i>Upload
                                        </button>
                                    <?php else: ?>
                                        <span class="pill ok">Uploaded</span>
                                    <?php endif ?>
                                    <button class="btn btn-row issue btn-sm" data-cert-step="2" data-cert-id="<?= $certificate['id'] ?>">
                                        <i class="fas fa-bolt me-1"></i>Verify
                                    </button>
                                <?php endif ?>
                            <?php else: ?>
                                <span class="pill pend">Verified</span>
                            <?php endif ?>

                            <?php if ($certificate['cert']): ?>
                                <?php if ($certificate['install_ssl_data']): ?>
                                    <span class="pill ok">Installed</span>
                                <?php else: ?>
                                    <button class="btn btn-row issue btn-sm" data-cert-step="3" data-cert-id="<?= $certificate['id'] ?>">
                                        <i class="fas fa-shield me-1"></i>Install
                                    </button>
                                <?php endif ?>
                                <a class="btn btn-row btn-sm" href="<?= route('certificates.download', ['id' => $certificate['id']]) ?>" download>
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            <?php endif ?>

                            <button class="btn btn-row danger btn-sm" data-delete-cert="<?= $certificate['id'] ?>">
                                <i class="fas fa-trash-alt"></i>
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
