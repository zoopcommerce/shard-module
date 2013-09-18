<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class FlushListener extends AbstractListener
{
    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::FLUSH, [$this, 'onFlush']);
    }

    public function onFlush(MvcEvent $event)
    {
        $options = $event->getTarget()->getOptions();

        $options->getModelManager()->flush();

        $flushExceptions = $options->getDoctrineSubscriber()->getFlushExceptions();

        if (count($flushExceptions) == 1) {
            throw $flushExceptions[0];
        } elseif (count($flushExceptions) > 1) {
            $flushException = new Exception\FlushException;
            $exceptionSerializer = $options->getExceptionSerializer();
            $identicalStatusCodes = true;
            $exceptions = [];
            $statusCode = null;
            foreach ($flushExceptions as $exception) {
                $exception = $exceptionSerializer->serializeException($exception);
                if (! isset($statusCode) && isset($exception['statusCode'])) {
                    $statusCode = $exception['statusCode'];
                } elseif (isset($exception['statusCode']) && $statusCode != $exception['statusCode']) {
                    $identicalStatusCodes = false;
                }
                $exceptions[] = $exception;
            }
            if (isset($statusCode) && $identicalStatusCodes) {
                $flushException->setStatusCode($statusCode);
            }
            $flushException->setInnerExceptions($exceptions);
            throw $flushException;
        }
    }
}
