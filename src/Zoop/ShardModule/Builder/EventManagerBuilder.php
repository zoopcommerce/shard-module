<?php

namespace Zoop\ShardModule\Builder;

use InvalidArgumentException;
use Doctrine\Common\EventSubscriber;
use DoctrineModule\Builder\BuilderInterface;
use DoctrineModule\Exception;
use DoctrineModule\Options\EventManagerOptions;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zoop\Shard\Core\EventManager;

/**
 * Builder responsible for creating EventManager instances
 */
class EventManagerBuilder implements BuilderInterface, ServiceLocatorAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function build($options)
    {
        if (is_array($options) || $options instanceof \Traversable) {
            $options = new EventManagerOptions($options);
        } elseif (! $options instanceof EventManagerOptions) {
            throw new Exception\InvalidArgumentException();
        }

        $eventManager = new EventManager();

        foreach ($options->getSubscribers() as $subscriberName) {
            $subscriber = $subscriberName;

            if (is_string($subscriber)) {
                if ($this->serviceLocator->has($subscriber)) {
                    $subscriber = $this->serviceLocator->get($subscriber);
                } elseif (class_exists($subscriber)) {
                    $subscriber = new $subscriber();
                }
            }

            if ($subscriber instanceof EventSubscriber) {
                $eventManager->addEventSubscriber($subscriber);
                continue;
            }

            $subscriberType = is_object($subscriberName) ? get_class($subscriberName) : $subscriberName;
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid event subscriber "%s" given, must be a service name, '
                    . 'class name or an instance implementing Doctrine\Common\EventSubscriber',
                    is_string($subscriberType) ? $subscriberType : gettype($subscriberType)
                )
            );
        }

        return $eventManager;
    }
}
