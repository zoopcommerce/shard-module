<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Zoop\Shard\AccessControl\Events as AccessControlEvents;
use Zoop\Shard\AccessControl\EventArgs as AccessControlEventArgs;
use Zoop\Shard\Freeze\Events as FreezeEvents;
use Zoop\Shard\SoftDelete\Events as SoftDeleteEvents;
use Zoop\Shard\State\Events as StateEvents;
use Zoop\Shard\Validator\Events as ValidatorEvents;
use Zoop\Shard\Validator\EventArgs as ValidatorEventArgs;
use Zoop\ShardModule\Exception;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DoctrineSubscriber implements EventSubscriber
{
    protected $flushExceptions;

    public function getSubscribedEvents()
    {
        return [
            AccessControlEvents::CREATE_DENIED,
            AccessControlEvents::UPDATE_DENIED,
            AccessControlEvents::DELETE_DENIED,
            FreezeEvents::FREEZE_DENIED,
            FreezeEvents::THAW_DENIED,
            FreezeEvents::FROZEN_UPDATE_DENIED,
            FreezeEvents::FROZEN_DELETE_DENIED,
            SoftDeleteEvents::RESTORE_DENIED,
            SoftDeleteEvents::SOFT_DELETE_DENIED,
            SoftDeleteEvents::SOFT_DELETED_UPDATE_DENIED,
            StateEvents::TRANSITION_DENIED,
            StateEvents::BAD_STATE,
            ValidatorEvents::INVALID_MODEL,
        ];
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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __call($name, $args)
    {
        if ($args[0] instanceof AccessControlEventArgs) {
            $this->accessControlEvent($args[0]);
        }
    }
}
