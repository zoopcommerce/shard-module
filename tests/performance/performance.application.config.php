<?php
return array(
    'modules' => array(
        'Zoop\MaggottModule',
        'DoctrineModule',
        'DoctrineMongoODMModule',
        'Zoop\ShardModule'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            __DIR__ . '/../test.module.config.php',
            __DIR__ . '/performance.module.config.php',
        ),
        'config_cache_enabled' => true,
        //'config_cache_enabled' => false,
        'cache_dir' => __DIR__ . '/cache',
        'check_dependencies' => false
    ),
);
