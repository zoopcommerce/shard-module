<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Zoop\Shard\Rest\Endpoint;
use Zoop\ShardModule\Controller\JsonRestfulController;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
abstract class AbstractAssistant
{
    protected $metadata;

    protected $endpoint;

    protected $controller;

    protected $pluginManager;

    protected $options;

    public function getMetadata() {
        return $this->metadata;
    }

    public function getEndpoint() {
        return $this->endpoint;
    }

    public function setController(JsonRestfulController $controller){
        $this->controller = $controller;
        $this->pluginManager = $controller->getServiceLocator()->get('ControllerPluginManager');
        $this->options = $controller->getOptions();
        $this->metadata = $this->options->getDocumentManager()->getClassMetadata($this->options->getDocumentClass());
        $this->endpoint = $this->options->getEndpoint();
    }

    public function __call($method, $params)
    {
        $plugin = $this->pluginManager->get($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }

        return $plugin;
    }

    protected function getSelect()
    {
        foreach ($this->controller->getRequest()->getQuery() as $key => $value){
            if (substr($key, 0, 6) == 'select' && (! isset($value) || $value == '')){
                $select = $key;
                break;
            }
        }

        if ( ! isset($select)){
            return;
        }

        return explode(',', str_replace(')', '', str_replace('select(', '', $select)));
    }

    protected function unserialize(array $data, $document, ClassMetadata $metadata, $mode = null){

        if (is_string($document)){
            $data[$this->endpoint->getProperty()] = $document;
            $document = null;
        } elseif (is_object($document) && isset($metadata->identifier)) {
            $data[$metadata->identifier] = $metadata->reflFields[$metadata->identifier]->getValue($document);
        }

        $serializer = $this->options->getSerializer();
        $document = $serializer->fromArray($data, $metadata->name, $mode, $document);
        return $document;
    }
}
