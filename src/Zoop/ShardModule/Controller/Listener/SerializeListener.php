<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class SerializeListener
{
    use SelectTrait;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __call($name, $args)
    {
        return $this->serialize($args[0]);
    }

    public function serialize(MvcEvent $event)
    {
        $result = $event->getResult();
        if (!($result instanceof Result) || $result->getSerializedModel() || !($model = $result->getModel())) {
            return $result;
        }

        $serializer = $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('serializer');

        $serializedModel = $serializer->toArray($model);
        if ($select = $this->getSelect($event)) {
            $serializedModel = array_intersect_key($serializedModel, array_fill_keys($select, 0));
        }
        $result->setSerializedModel($serializedModel);

        return $result;
    }

    public function getList(MvcEvent $event)
    {
        $result = $event->getResult();
        if (!($result instanceof Result) || !($model = $result->getModel())) {
            return;
        }

        $serializer = $event->getTarget()->getOptions()->getManifest()->getServiceManager()->get('serializer');

        $items = [];
        foreach ($model as $item) {
            $items[] = $serializer->toArray($item);
        }
        if ($select = $this->getSelect($event)) {
            foreach ($items as $key => $item) {
                $items[$key] = array_intersect_key($item, array_fill_keys($select, 0));
            }
        }

        $result->setSerializedModel($items);
        return $result;
    }
}
