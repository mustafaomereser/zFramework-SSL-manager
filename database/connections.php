<?php
return [
    'local' => ['mysql:host=localhost;dbname=ssl_manager;charset=utf8mb4', 'root', '', 'options' => [
        [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION],
        [\PDO::ATTR_EMULATE_PREPARES, true] # for performance and PDO lastInsertId method.
    ]],
];
