<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lets Encrypt</title>

    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="<?= asset('/assets/libs/notify/style.css') ?>" />
    <link rel="stylesheet" href="<?= asset('/assets/css/style.css') ?>" />
    @yield('header')
</head>

<body>
    <div class="container-fluid mt-3">
        @yield('body')
    </div>

    <div id="load-modals"></div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/libs/notify/script.js"></script>
    <script>
        $.showAlerts(<?= json_encode(\zFramework\Core\Facades\Alerts::get()) ?>);
    </script>
    @yield('footer')
</body>

</html>