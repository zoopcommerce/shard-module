<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zoop\Shard\Manifest;

trait ManifestAwareTrait
{
    protected $manifest;

    public function setManifest(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function getManifest()
    {
        return $this->manifest;
    }
}
