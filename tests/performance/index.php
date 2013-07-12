<?php

$loaderPath = 'vendor/autoload.php';

// Root if testing independently
$applicationRoot = __DIR__ . '/../../';

if ( ! file_exists($applicationRoot . $loaderPath )) {
    // Root if testing as part of a larger app
    $applicationRoot = __DIR__ . '/../../../../../';
}

chdir($applicationRoot);

$loader = require_once($loaderPath);
$loader->add('Zoop\\ShardModule\\Test', __DIR__ . '/../');

// Run the application!
Zend\Mvc\Application::init(require __DIR__ . '/performance.application.config.php')->run();
