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
class OptionsListener
{
    public function options(MvcEvent $event)
    {
        if (!($result = $event->getResult())) {
            $result = new Result;
            $event->setResult($result);
        }

        $result->setStatusCode(405);

        return $result;
    }
}
