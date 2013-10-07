<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
trait RestControllerMapTrait
{
    protected $restControllerMap;

    protected function getRestControllerMap($event)
    {
        if (!isset($this->restControllerMap)) {
            $this->restControllerMap =
                $event->getTarget()->getOptions()->getServiceLocator()->get('zoop.shardmodule.restcontrollermap');
        }

        return $this->restControllerMap;
    }
}
