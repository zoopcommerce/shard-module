<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
trait SelectTrait
{
    protected function getSelect(MvcEvent $event)
    {
        foreach ($event->getRequest()->getQuery() as $key => $value) {
            if (substr($key, 0, 6) == 'select' && (! isset($value) || $value == '')) {
                $select = $key;
                break;
            }
        }

        if (! isset($select)) {
            return;
        }

        return explode(',', str_replace(')', '', str_replace('select(', '', $select)));
    }
}
