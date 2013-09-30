<?php
/**
 * @link       http://zoopcommerce.github.io/shard-module
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zoop\ShardModule\Options\RestfulControllerOptions;

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

    protected function getConfig(){
        if (!isset($this->config)) {
            $this->config = $this->serviceLocator->get('config')['zoop']['shard']['rest'];
        }
        return $this->config;
    }

    protected function getModelManager($manifest){
        if (!isset($this->modelManagerMap[$manifest])) {
            $this->modelManagerMap[$manifest] = $this->serviceLocator->get('shard.' . $manifest . '.manifest')->getServiceManager()->get('modelmanager');
        }
        return $this->modelManagerMap[$manifest];
    }

    public function getOptionsFromEndpoint($endpoint)
    {
        if (!isset($this->optionsMap[$endpoint])) {

            $options = $this->getConfig();
            $options['endpoint'] = $endpoint;
            $options['service_locator'] = $this->serviceLocator;

            if ($endpoint == '') {
                unset($options['rest']);
            } else {
                $pieces = explode('.', $endpoint);
                $root = array_shift($pieces);

                if (!isset($options['rest'][$root])) {
                    $options = null;
                } else {
                    $options = array_merge($options, $options['rest'][$root]);

                    foreach ($pieces as $piece) {
                        $metadata = $this->getModelManager($options['manifest'])->getClassMetadata($options['class']);
                        if ($metadata->fieldMappings[$piece]['targetDocument']) {
                            $options['class'] = $metadata->fieldMappings[$piece]['targetDocument'];
                        }
                        unset($options['property']);
                        if (isset($options['rest'][$piece])) {
                            $options = array_merge($options, $options['rest'][$piece]);
                        } else {
                            unset($options['rest']);
                        }
                    }
                }
            }

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

    public function getOptionsFromClass($class)
    {
        foreach ($this->getConfig()['rest'] as $endpoint => $options) {
            if ($options['class'] == $class) {
                return $this->getOptionsFromEndpoint($endpoint);
            }
        }
    }

}
