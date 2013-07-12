<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zoop\Shard\Manifest;

interface ManifestAwareInterface
{

    public function setManifestName($manifestName);

    public function getManifestName();

    public function setManifest(Manifest $manifest);

    public function getManifest();
}
