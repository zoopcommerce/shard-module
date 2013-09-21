<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\AbstractOptions;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchRestfulControllerOptions extends AbstractOptions
{
    protected $serviceLocator;

    protected $acceptCriteria = [
        'Zend\View\Model\JsonModel' => [
            'application/json',
        ],
        'Zend\View\Model\ViewModel' => [
            '*/*',
        ],
    ];

    protected $exceptionViewModelPreparer = 'Zoop\MaggottModule\JsonExceptionStrategy';

    /**
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getAcceptCriteria()
    {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria)
    {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function getExceptionViewModelPreparer()
    {
        if (is_string($this->exceptionViewModelPreparer)) {
            $this->exceptionViewModelPreparer = $this->serviceLocator->get($this->exceptionViewModelPreparer);
        }

        return $this->exceptionViewModelPreparer;
    }

    public function setExceptionViewModelPreparer($exceptionViewModelPreparer)
    {
        $this->exceptionViewModelPreparer = $exceptionViewModelPreparer;
    }
}
