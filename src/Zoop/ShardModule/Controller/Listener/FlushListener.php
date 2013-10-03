<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class FlushListener
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __call($name, $args) {
        return $this->flush($args[0]);
    }

    public function flush(MvcEvent $event)
    {
        if ($event->getTarget()->forward()->getNumNestedForwards() > 0) {
            return $event->getResult();
        }

        $options = $event->getTarget()->getOptions();
        $options->getModelManager()->flush();

        if (!($flushExceptions = $options->getDoctrineSubscriber()->getFlushExceptions())) {
            return $event->getResult();
        } else {
            return $this->prepareExceptions($flushExceptions, $options->getExceptionSerializer());
        }
    }

    protected function prepareExceptions(array $flushExceptions, $exceptionSerializer)
    {
        if (count($flushExceptions) == 1) {
            throw $flushExceptions[0];
        } else {
            $flushException = new Exception\FlushException;
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
