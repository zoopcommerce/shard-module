<?php

$loaderPath = 'vendor/zoopcommerce/slipstream/autoload.php';

// Root if testing independently
$applicationRoot = __DIR__ . '/../../';

if ( ! file_exists($applicationRoot . $loaderPath )) {
    // Root if testing as part of a larger app
    $applicationRoot = __DIR__ . '/../../../../../';
}

chdir($applicationRoot);

$loader = require_once($loaderPath);
$loader->add('Zoop\\ShardModule\\Test', __DIR__ . '/../');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    //use juggernaut cache for GET requests
    $pageTtl = 1; //1 seconds, very low cache time, but helps page speed massively with concurrent requests
    $cacheDirectory = __DIR__ . '/cache/fullpage';

    $adapter = new Zoop\Juggernaut\Adapter\FileSystem($cacheDirectory);
    $pageCache = new Zoop\Juggernaut\Helper\FullPage($adapter, $pageTtl, true, false);
    $pageCache->start();
}

// Run the application!
Zend\Mvc\Application::init(require __DIR__ . '/performance.application.config.php')->run();
