<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\Shard\Serializer\Unserializer;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class UnserializeListener
{
    public function create(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0 || $result = $event->getResult()) {
            return $event->getResult();
        }

        $result = new Result(
            $event->getTarget()
                ->getOptions()
                ->getManifest()
                ->getServiceManager()
                ->get('unserializer')
                ->fromArray(
                    $event->getParam('data'),
                    $event->getTarget()->getOptions()->getClass(),
                    null,
                    Unserializer::UNSERIALIZE_PATCH
                )
        );
        $event->setResult($result);
        return $result;
    }

    public function update(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0 || $result = $event->getResult()) {
            return $event->getResult();
        }

        $data = $event->getParam('data');
        $id = $event->getParam('id');
        $options = $event->getTarget()->getOptions();

        if ($property = $options->getProperty()) {
            $data[$property] = $id;
        }

        $result = new Result(
            $event->getTarget()
                ->getOptions()
                ->getManifest()
                ->getServiceManager()
                ->get('unserializer')
                ->fromArray(
                    $data,
                    $event->getTarget()->getOptions()->getClass(),
                    $event->getParam('document'),
                    Unserializer::UNSERIALIZE_UPDATE
                )
        );
        $event->setResult($result);
        return $result;
    }

    public function replaceList(MvcEvent $event)
    {
        if (count($event->getParam('deeperResource')) > 0 || $result = $event->getResult()) {
            return $event->getResult();
        }

        $list = [];
        $unserializer = $event->getTarget()
            ->getOptions()
            ->getManifest()
            ->getServiceManager()
            ->get('unserializer');

        foreach ($event->getParam('data') as $item) {
             $list[] = $unserializer->fromArray(
                 $item,
                 $event->getTarget()->getOptions()->getClass(),
                 null,
                 Unserializer::UNSERIALIZE_UPDATE
             );
        }

        $result = new Result($list);
        $event->setResult($result);
        return $result;
    }
}
