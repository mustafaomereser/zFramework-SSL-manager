<?php

namespace Database\Migrations;

#[\AllowDynamicProperties]
class Domains
{
    static $storageEngine = "InnoDB";
    static $charset       = "utf8mb4_general_ci";
    static $table         = "domains";
    static $db            = "local";
    static $prefix        = "";

    public static function columns()
    {
        return [
            'id'           => ['primary'],
            'main_domain'  => ['int', 'nullable'],
            'domain'       => ['varchar'],
            'public_dir'   => ['varchar'],
            'ftp'          => ['json'],
            'cpanel'       => ['json'],
            'timestamps'
        ];
    }

    # e.g. a self seeder 
    # public static function oncreateSeeder()
    # {
    #     $user = new User;
    #     $user->insert([
    #         'username'  => 'admin',
    #         'password'  => Crypter::encode('admin'),
    #         'email'     => Str::rand(15) . '@localhost.com',
    #         'api_token' => Str::rand(60)
    #     ]);
    # }
}
