<?php

namespace Database\Migrations;

#[\AllowDynamicProperties]
class Certificates
{
    static $storageEngine = "InnoDB";
    static $charset       = "utf8mb4_general_ci";
    static $table         = "certificates";
    static $db            = "local";
    static $prefix        = "";

    public static function columns()
    {
        return [
            'id'                    => ['primary'],
            'domain'                => ['text'],
            'cert'                  => ['text'],
            'ca_bundle'             => ['text'],
            'private'               => ['text'],
            'last_date'             => ['datetime'],
 
            'order_data'            => ['json'],
            'challenge_data'        => ['json'],
            'notifyChallenge_data'  => ['json'],
            'challengeAuth_data'    => ['json'],
            'finalize_data'         => ['json'],
            'getCertificate_data'   => ['json'],
            'install_ssl_data'      => ['json'],
            'upload_challenge_data' => ['json'],

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
