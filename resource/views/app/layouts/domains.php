<?php if (!count($domains)) die('Not registered any domain.') ?>
<?php foreach ($domains as $domain):
    $checkSSL = null;
    try {
        error_reporting(0);
        $checkSSL = App\Helpers\API::$autoSSL->checkSSL($domain['domain']);
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
<script>
    $(() => {
        setLOAD();
        let domains = $('[data-domain-key]');
        domains.on('click', function() {
            domains.removeClass('border-success');
            $(this).addClass('border-success');
            $.get('<?= route('domains.index') ?>?key=' + $(this).attr('data-domain-key'));
            if ($(this).attr('data-domain-key') != lastdomain) $('[load-content]').html(null);
            lastdomain = $(this).attr('data-domain-key');
        });

        $('[data-domain-key="<?= zFramework\Core\Facades\Cookie::get('domain') ?? 0 ?>"]').click();
        init();
    });
</script>