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
class GetListAssistant extends AbstractAssistant
{

    protected $range;

    /**
     * If list array is supplied, it will be filtered and sorted in php.
     * If list is empty, it will be loaded from the db, (filter and sort will be applied by the db).
     *
     * If metadata is not suppled, it will be retrieved using $this->options->getDocumentClass()
     *
     * @param array $list
     * @return type
     */
    public function doGetList($list = null){

        unset($this->range);
        
        $response = $this->controller->getResponse();
        $documentManager = $this->options->getDocumentManager();
        $serializer = $this->options->getSerializer();
        $metadata = $this->metadata;

        $criteria = $this->getCriteria($metadata);

        //filter list on criteria
        if (count($criteria) > 0 && $list){
            $list = $this->applyCriteriaToList($list, $criteria);
        }

        if ($list){
            $total = count($list);
        } else {
            //load the total from the db
            $totalQuery = $documentManager->createQueryBuilder()
                ->find($metadata->name);
            $total = $this->addCriteriaToQuery($totalQuery, $criteria)
                ->getQuery()
                ->execute()
                ->count();
        }

        if ($total == 0){
            $response->setStatusCode(204);
            return [];
        }

        $offset = $this->getOffset();
        if ($offset > $total - 1){
            throw new Exception\BadRangeException();
        }
        $sort = $this->getSort();

        if ($list){
            //apply any required sort to the result
            if (count($sort) > 0){
                $this->applySortToList($list, $sort);
            }
            $list = array_slice($list, $offset, $this->getLimit());
            foreach ($list as $item){
                $items[] = $serializer->applySerializeMetadataToArray($item, $metadata->name);
            }
        } else {
            $resultsQuery = $documentManager->createQueryBuilder()
                ->find($metadata->name);
            $this->addCriteriaToQuery($resultsQuery, $criteria);
            $resultsQuery
                ->limit($this->getLimit())
                ->skip($offset);
            $resultsCursor = $this->addSortToQuery($resultsQuery, $sort)
                ->eagerCursor(true)
                ->getQuery()
                ->execute();
            foreach ($resultsCursor as $result){
                $items[] = $serializer->toArray($result, $metadata->name);
            }
        }

        //apply any select
        if ($select = $this->getSelect()){
            $select = array_fill_keys($select, 0);
            foreach ($items as $key => $item){
                $items[$key] = array_intersect_key($item, $select);
            }
        }

        $max = $offset + count($items) - 1;
        $response->getHeaders()->addHeader(ContentRange::fromString("Content-Range: $offset-$max/$total"));

        return $items;
    }

    protected function getLimit(){

        list($lower, $upper) = $this->getRange();
        return $upper - $lower + 1;
    }

    protected function getOffset(){

        return $this->getRange()[0];
    }

    protected function getRange(){

        if (isset($this->range)){
            return $this->range;
        }

        $header = $this->controller->getRequest()->getHeader('Range');
        $limit = $this->options->getLimit();
        if ($header) {
            list($lower, $upper) = array_map(
                function($item){return intval($item);},
                explode('-', explode('=', $header->getFieldValue())[1])
            );
            if ($lower > $upper){
                throw new Exception\BadRangeException();
            }
            if ($upper - $lower + 1 > $limit){
                $upper = $limit - 1;
            }
            $this->range = [$lower, $upper];
        } else {
            $this->range = [0, $limit - 1];
        }
        return $this->range;
    }

    protected function getCriteria($metadata){

        $result = [];
        foreach ($this->controller->getRequest()->getQuery() as $key => $value){
            //ignore criteria that null and for fields that don't exist
            if (isset($value) && array_key_exists($key, $metadata->reflFields)){
                if (substr($value, 0, 1) == '['){
                    $value = explode(',', substr($value, 1, -1));
                }
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function addCriteriaToQuery($query, $criteria){
        foreach($criteria as $field => $value){
            if (is_array($value)){
                $query->field($field)->in($value);
            } else {
                $query->field($field)->equals($value);
            }
        }
        return $query;
    }

    protected function addSortToQuery($query, $sort){
        foreach($sort as $s){
            $query->sort($s['field'], $s['direction']);
        }
        return $query;
    }

    protected function applyCriteriaToList($list, $criteria){
        return array_filter($list, function($item) use ($criteria){
            foreach ($criteria as $field => $criteriaValue){
                $pieces = explode('.', $field);
                $fieldValue = $item[$pieces[0]];
                array_shift($pieces);
                foreach ($pieces as $piece){
                    $fieldValue = $fieldValue[$piece];
                }
                switch (true){
                    case is_array($fieldValue && is_array($criteriaValue)):
                        foreach ($criteriaValue as $value){
                            if (in_array($value, $fieldValue)){
                                return true;
                            }
                        }
                        return false;
                    case is_array($fieldValue):
                        if (in_array($criteriaValue, $fieldValue)){
                            return true;
                        }
                        return false;
                    case is_array($criteriaValue):
                        if (in_array($fieldValue, $criteriaValue)){
                            return true;
                        }
                        return false;
                    default:
                        if ($fieldValue == $criteriaValue){
                            return true;
                        }
                        return false;
                }
            }
        });
    }

    protected function applySortToList(&$list, $sort){
        usort($list, function($a, $b) use ($sort){
            foreach ($sort as $s){
                if ($s['direction'] == 'asc'){
                    if ($a[$s['field']] < $b[$s['field']]){
                       return -1;
                    } else if ($a[$s['field']] > $b[$s['field']]) {
                        return 1;
                    }
                } else {
                    if ($a[$s['field']] > $b[$s['field']]){
                       return -1;
                    } else if ($a[$s['field']] < $b[$s['field']]) {
                        return 1;
                    }
                }
            }
            return 0;
        });
    }

    protected function getSort(){

        foreach ($this->controller->getRequest()->getQuery() as $key => $value){
            if (substr($key, 0, 4) == 'sort' && (! isset($value) || $value == '')){
                $sort = $key;
                break;
            }
        }

        if ( ! isset($sort)){
            return [];
        }

        $sortFields = explode(',', str_replace(')', '', str_replace('sort(', '', $sort)));
        $return = [];

        foreach ($sortFields as $value)
        {
            $return[] = [
                'field' => substr($value, 1),
                'direction' => substr($value, 0, 1) == '+' ? 'asc' : 'desc'
            ];
        }

        return $return;
    }
}
