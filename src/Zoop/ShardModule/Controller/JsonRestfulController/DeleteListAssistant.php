<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\JsonRestfulController;

use Zoop\ShardModule\Exception;
use Zend\Http\Header\ContentRange;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DeleteListAssistant extends AbstractAssistant
{

    /**
     * If list array is supplied, it will be filtered and sorted in php.
     * If list is empty, it will be loaded from the db, (filter and sort will be applied by the db).
     *
     * If metadata is not suppled, it will be retrieved using $this->options->getDocumentClass()
     *
     * @param array $list
     * @return type
     */
    public function doDeleteList($list = null){

        if ($list){
            foreach ($list as $key => $item){
                $list->remove($key);
            }
        } else {
            $this->options->getDocumentManager()
                ->createQueryBuilder($this->metadata->name)
                ->remove()
                ->getQuery()
                ->execute();
        }
    }
}
