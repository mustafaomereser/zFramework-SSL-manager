<?php

namespace App\Providers;

use App\Models\Domains;
use App\Models\User;
use zFramework\Core\View;

class ViewProvider
{
    public function __construct()
    {
        View::bind('app.main', function () {
            return [
                'domains' => (new Domains)->whereRaw('main_domain IS NULL')->get()
            ];
        });
    }
}
