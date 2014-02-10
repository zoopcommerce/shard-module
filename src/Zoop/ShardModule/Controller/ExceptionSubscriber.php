<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Zoop\Shard\Core\Events as CoreEvents;
use Zoop\Shard\Core\ExceptionEventArgs;
use Zoop\Shard\AccessControl\EventArgs as AccessControlEventArgs;
use Zoop\Shard\Validator\EventArgs as ValidatorEventArgs;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class ExceptionSubscriber implements EventSubscriber
{
    protected $flushExceptions;

    public function getSubscribedEvents()
    {
        return [CoreEvents::EXCEPTION];
    }

    public function getFlushExceptions()
    {
        return $this->flushExceptions;
    }

    public function invalidModel(ValidatorEventArgs $eventArgs)
    {
        $exception = new Exception\InvalidDocumentException;
        $exception->setValidatorMessages($eventArgs->getResult()->getMessages());
        $exception->setDocument($eventArgs->getDocument());
        $this->flushExceptions[] = $exception;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function badStateEvent(OnFlushEventArgs $eventArgs)
    {
        $exception = new Exception\InvalidArgumentException('Bad state');
        $this->flushExceptions[] = $exception;
    }

    public function accessControlEvent(AccessControlEventArgs $eventArgs)
    {
        $exception = new Exception\AccessControlException;
        $exception->setAction($eventArgs->getAction());
        $exception->setDocument($eventArgs->getDocument());
        $exception->setDocumentClass(get_class($eventArgs->getDocument()));
        $this->flushExceptions[] = $exception;
    }

    public function genericException($eventName)
    {
        $exception = new Exception\GenericShardExceptionExceptionn;
        $exception->setEventName($eventName);
        $this->flushExceptions[] = $exception;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __call($name, $args)
    {
        if ($args[0] instanceof ExceptionEventArgs) {
            $innerEvent = $args[0]->getInnerEvent();
            if ($innerEvent instanceof AccessControlEventArgs) {
                $this->accessControlEvent($innerEvent);
                return;
            }

            $eventName = $args[0]->getName();
            if (method_exists($this, $eventName)) {
                $this->$eventName($innerEvent);
                return;
            }

            $this->genericException($eventName);
        }
    }
}
