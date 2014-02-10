<?php
return [
    'modules' => [
        'Zoop\MaggottModule',
        'DoctrineModule',
        'DoctrineMongoODMModule',
        'Zoop\ShardModule'
    ],
    'module_listener_options' => [
        'config_glob_paths'    => [
            __DIR__ . '/test.module.config.php',
        ],
    ],
];
