<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchJsonRestfulControllerOptions extends AbstractControllerOptions
{

    protected $acceptCriteria = [
        'Zend\View\Model\JsonModel' => [
            'application/json',
        ],
        'Zend\View\Model\ViewModel' => [
            '*/*',
        ],
    ];

    protected $exceptionViewModelPreparer = 'Zoop\MaggottModule\JsonExceptionStrategy';

    public function getAcceptCriteria() {
        return $this->acceptCriteria;
    }

    public function setAcceptCriteria(array $acceptCriteria) {
        $this->acceptCriteria = $acceptCriteria;
    }

    public function getExceptionViewModelPreparer() {
        if (is_string($this->exceptionViewModelPreparer)) {
            $this->exceptionViewModelPreparer = $this->serviceLocator->get($this->exceptionViewModelPreparer);
        }
        return $this->exceptionViewModelPreparer;
    }

    public function setExceptionViewModelPreparer($exceptionViewModelPreparer) {
        $this->exceptionViewModelPreparer = $exceptionViewModelPreparer;
    }
}
