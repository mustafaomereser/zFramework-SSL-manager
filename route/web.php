<?php

use App\Controllers\CertificatesController;
use App\Controllers\DomainsController;
use zFramework\Core\Route;
use App\Controllers\HomeController;
use App\Helpers\API;
use zFramework\Core\Facades\Alerts;
use zFramework\Core\Facades\Config;
use zFramework\Core\Facades\Response;

Route::noCSRF(true)->group(function () {
    Route::any('/api/{method}', fn($method) => API::{$method}());
    Route::resource('/domains', DomainsController::class);

    Route::pre('/certificates')->group(function () {
        Route::get('/challenge/{id}', [CertificatesController::class, 'challenge'])->name('challenge');
        Route::get('/upload-challenge/{id}', [CertificatesController::class, 'uploadChallenge'])->name('upload-challenge');
        Route::get('/download/{id}', [CertificatesController::class, 'download'])->name('download');
        Route::get('/install/{id}', [CertificatesController::class, 'install'])->name('install');
        Route::resource('/', CertificatesController::class);
    });

    Route::get('/switch/{mode}', function ($mode) {
        Config::set('autossl', ['mode' => $mode]);
        API::$autoSSL->unlinkAccount();
        Alerts::success("Mode is switched to $mode");
        return Response::json(['token' => @end(explode('/', API::$autoSSL->ensureAccount()))]);
    })->name('switch');

    Route::resource('/', HomeController::class);
});
