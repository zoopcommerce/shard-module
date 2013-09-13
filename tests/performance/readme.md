This folder contains files for performance testing shard and shard-module.

1. In this folder run `php index.php create`. This will load test data into a mongodb to test against. You may also need to run `php index.php odm:generate:proxies` and `php index.php odm:generate:hydrators`.

2. Make index.php is accessable through your local webserver.

3. Run one of the test scripts which use apache test bench:

    test-simple-get.sh
    test-nested-embed-get.sh
    test-nested-ref-get.sh
    test-nested-ref-list-get.sh
    test-simple-update.sh
