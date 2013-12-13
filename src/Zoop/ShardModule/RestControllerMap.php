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
class RestControllerMap implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $config;

    protected $modelManager;

    protected $modelManagerMap = [];

    protected $optionsMap = [];

    protected function getConfig()
    {
        if (!isset($this->config)) {
            $this->config = $this->serviceLocator->get('config')['zoop']['shard']['rest'];
        }

        return $this->config;
    }

    protected function getModelManager($manifest)
    {
        if (!isset($this->modelManagerMap[$manifest])) {
            $this->modelManagerMap[$manifest] =
                $this->serviceLocator->get('shard.' . $manifest . '.manifest')
                    ->getServiceManager()
                    ->get('modelmanager');
        }

        return $this->modelManagerMap[$manifest];
    }

    public function getOptionsFromEndpoint($endpoint)
    {
        if (!isset($this->optionsMap[$endpoint])) {

            $options = $this->getConfig();
            $options['endpoint'] = $endpoint;
            $options['service_locator'] = $this->serviceLocator;

            if ($endpoint != '') {
                $pieces = explode('.', $endpoint);
                $root = array_shift($pieces);

                if (!isset($options['rest'][$root])) {
                    $options = null;
                } else {
                    $options = array_merge($options, $options['rest'][$root]);

                    while ($piece = array_shift($pieces)) {
                        $metadata = $this->getModelManager($options['manifest'])->getClassMetadata($options['class']);
                        $field = $piece;
                        if (isset($metadata->fieldMappings[$field]['reference'])) {
                            return $this->getOptionsFromClass($metadata->fieldMappings[$field]['targetDocument']);
                        } elseif ($metadata->fieldMappings[$field]['targetDocument']) {
                            $options['class'] = $metadata->fieldMappings[$field]['targetDocument'];
                        }
                        if (isset($options['rest'][$field])) {
                            $options = array_merge($options, $options['rest'][$field]);
                        }
                    }
                    if (isset($metadata) &&
                        isset($metadata->associationMappings[$field]) &&
                        ($metadata->associationMappings[$field]['type'] == 'one' ||$metadata->associationMappings[$field]['strategy'] == 'set')
                    ) {
                        unset($options['property']);
                    }
                }
            }

            unset($options['rest']);

            if (isset($options)) {
                $optionsClass = $options['options_class'];
                unset($options['options_class']);
                $optionsObject = new $optionsClass($options);
            } else {
                $optionsObject = null;
            }

            $this->optionsMap[$endpoint] = $optionsObject;
        }

        return $this->optionsMap[$endpoint];
    }

    protected function getOptionsFromClass($class)
    {
        foreach ($this->getConfig()['rest'] as $endpoint => $options) {
            if (isset($options['class']) && $options['class'] == $class) {
                return $this->getOptionsFromEndpoint($endpoint);
            }
        }
    }
}
