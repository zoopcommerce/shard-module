<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ReferenceMap implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $modelManagerMap = [];

    protected $cacheId = 'Zoop\Shard\Reference\ReferenceMap';

    protected $map = null;

    public function getCacheId()
    {
        return $this->cacheId;
    }

    public function setCacheId($cacheId)
    {
        $this->cacheId = $cacheId;
    }

    protected function getModelManager($manifest){
        if (!isset($this->modelManagerMap[$manifest])) {
            $this->modelManagerMap[$manifest] = $this->serviceLocator->get('shard.' . $manifest . '.manifest')->getServiceManager()->get('modelmanager');
        }
        return $this->modelManagerMap[$manifest];
    }

    public function has($target)
    {
        return array_key_exists($target, $this->getMap());
    }

    public function get($target)
    {
        return $this->getMap()[$target];
    }

    public function getMap($manifest)
    {
        if (isset($this->map)) {
            return $this->map;
        }

        $modelManager = $this->getModelManager($manifest);
        $cacheDriver = $modelManager->getConfiguration()->getMetadataCacheImpl();

        if ($cacheDriver->contains($this->cacheId)) {
            $this->map = $cacheDriver->fetch($this->cacheId);
        } else {
            $this->map = [];
            foreach ($modelManager->getMetadataFactory()->getAllMetadata() as $metadata) {
                foreach ($metadata->associationMappings as $mapping) {
                    if (isset($mapping['reference']) && $mapping['reference'] && $mapping['isOwningSide']) {
                        $this->map[$mapping['targetDocument']][] = [
                            'class' => $metadata->name,
                            'field'    => $mapping['name'],
                            'type'     => $mapping['type']
                        ];
                    }
                }
            }
            $cacheDriver->save($this->cacheId, $this->map);
        }

        return $this->map;
    }
}
