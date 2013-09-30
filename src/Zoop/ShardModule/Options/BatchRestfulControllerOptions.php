<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Options;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class BatchRestfulControllerOptions extends RestfulControllerOptions
{
    protected $listeners = [
        'create' => [
            'zoop.shardmodule.listener.unserialize',
            'zoop.shardmodule.listener.create',
            'zoop.shardmodule.listener.flush',
            'zoop.shardmodule.listener.prepareviewmodel'
        ],
        'delete'      => [],
        'deleteList'  => [],
        'get'         => [],
        'getList'     => [],
        'patch'       => [],
        'patchList'   => [],
        'update'      => [],
        'replaceList' => [],
    ];

    protected $exceptionViewModelPreparer = 'Zoop\MaggottModule\JsonExceptionStrategy';

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
