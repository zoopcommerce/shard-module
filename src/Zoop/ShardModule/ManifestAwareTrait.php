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

    protected $manifestName;

    protected $manifest;

    public function setManifestName($manifestName)
    {
        $this->manifestName = $manifestName;
    }

    public function getManifestName()
    {
        return $this->manifestName;
    }

    public function setManifest(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function getManifest()
    {
        return $this->manifest;
    }
}
