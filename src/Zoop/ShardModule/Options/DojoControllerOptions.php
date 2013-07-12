<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DojoControllerOptions extends AbstractControllerOptions
{
    protected $resourceMap;

    public function getResourceMap() {
        if (is_string($this->resourceMap)) {
            $this->resourceMap = $this->serviceLocator->get($this->resourceMap);
        }
        return $this->resourceMap;
    }

    public function setResourceMap($resourceMap) {
        $this->resourceMap = $resourceMap;
    }
}
