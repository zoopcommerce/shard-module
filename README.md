Zoop shard-module
=================

[![Build Status](https://api.shippable.com/projects/540e7ac73479c5ea8f9eb9ff/badge?branchName=master)](https://app.shippable.com/projects/540e7ac73479c5ea8f9eb9ff/builds/latest)

A zf2 module for shard, behavioral extensions to Doctrine Mongo ODM.

For full documentation see:

* http://zoopcommerce.github.io/shard-module
* http://zoopcommerce.github.io/shard

Installation Note
-----------------

The development of shard has resulted in significant contributions to other open source repositories which shard depends on.
Some of these contributions are in development versions, to release versions at present. As a result, composer install requires a
few extra lines to make sure some dev repos are used:

    "require": {
        "doctrine/doctrine-module"           : "1.0.x-dev as 1.0",
        "doctrine/mongodb-odm"               : "dev-master as 1.0.0-BETA9",
        "zoopcommerce/shard-module"          : "~2.0"
    }
