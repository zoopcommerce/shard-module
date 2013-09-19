<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Zend\Http\Header\ContentRange;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Result;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GetListListener
{
    use SelectTrait;

    public function getList(MvcEvent $event)
    {
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getModelManager();
        $metadata = $documentManager->getClassMetadata($options->getClass());
        if ($list = $event->getParam('list')) {
            $list = $list->getValues();
        }

        unset($this->range);

        $criteria = $this->getCriteria($metadata, $event);

        //filter list on criteria
        if (count($criteria) > 0 && $list) {
            $list = $this->applyCriteriaToList($list, $criteria, $metadata);
        }

        if ($list) {
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

        if ($total == 0) {
            $result = new Result([]);
            $result->setStatusCode(204);
            $event->setResult($result);
            return $result;
        }

        $offset = $this->getOffset($event);
        if ($offset > $total - 1) {
            throw new Exception\BadRangeException();
        }
        $sort = $this->getSort($event);

        if ($list) {
            //apply any required sort to the result
            if (count($sort) > 0) {
                $this->applySortToList($list, $sort, $metadata);
            }
            $list = array_slice($list, $offset, $this->getLimit($event));
//            $event->setParam('list', $list);
//            $items = $event->getTarget()->trigger(Event::SERIALIZE_LIST, $event)->last();
        } else {
            $resultsQuery = $documentManager->createQueryBuilder()
                ->find($metadata->name);
            $this->addCriteriaToQuery($resultsQuery, $criteria);
            $resultsQuery
                ->limit($this->getLimit($event))
                ->skip($offset);
            $list = $this->addSortToQuery($resultsQuery, $sort)
                ->eagerCursor(true)
                ->getQuery()
                ->execute();
        }

        $max = $offset + count($list) - 1;

        $result = new Result($list);
        $result->addHeader(ContentRange::fromString("Content-Range: $offset-$max/$total"));

        $event->setResult($result);
        return $result;
    }

    protected function getLimit($event)
    {
        list($lower, $upper) = $this->getRange($event);
        return $upper - $lower + 1;
    }

    protected function getOffset($event)
    {
        return $this->getRange($event)[0];
    }

    protected function getRange($event)
    {
        if (isset($this->range)) {
            return $this->range;
        }

        $header = $event->getTarget()->getRequest()->getHeader('Range');
        $limit = $event->getTarget()->getOptions()->getLimit();
        if ($header) {
            list($lower, $upper) = array_map(
                function ($item) {
                    return intval($item);
                },
                explode('-', explode('=', $header->getFieldValue())[1])
            );
            if ($lower > $upper) {
                throw new Exception\BadRangeException();
            }
            if ($upper - $lower + 1 > $limit) {
                $upper = $limit - 1;
            }
            $this->range = [$lower, $upper];
        } else {
            $this->range = [0, $limit - 1];
        }

        return $this->range;
    }

    protected function getCriteria($metadata, $event)
    {
        $result = [];
        $dotPlaceholder = $event->getTarget()->getOptions()->getQueryDotPlaceholder();
        foreach ($event->getTarget()->getRequest()->getQuery() as $key => $value) {
            //ignore criteria that are null
            if (isset($value) && $value !== '') {
                if (substr($value, 0, 1) == '[') {
                    $value = explode(',', substr($value, 1, -1));
                }
                $result[str_replace($dotPlaceholder, '.', $key)] = $value;
            }
        }

        return $result;
    }

    protected function addCriteriaToQuery($query, $criteria)
    {
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->field($field)->in($value);
            } else {
                $query->field($field)->equals($value);
            }
        }

        return $query;
    }

    protected function addSortToQuery($query, $sort)
    {
        foreach ($sort as $s) {
            $query->sort($s['field'], $s['direction']);
        }

        return $query;
    }

    protected function applyCriteriaToList($list, $criteria, $metadata)
    {
        return array_filter(
            $list,
            function ($item) use ($criteria, $metadata) {
                foreach ($criteria as $field => $criteriaValue) {
                    $pieces = explode('.', $field);
                    $fieldValue = $metadata->getFieldValue($item, $pieces[0]);
                    array_shift($pieces);
                    foreach ($pieces as $piece) {
                        $fieldValue = $fieldValue[$piece];
                    }
                    switch (true) {
                        case is_array($fieldValue && is_array($criteriaValue)):
                            foreach ($criteriaValue as $value) {
                                if (in_array($value, $fieldValue)) {
                                    return true;
                                }
                            }

                            return false;
                        case is_array($fieldValue):
                            if (in_array($criteriaValue, $fieldValue)) {
                                return true;
                            }

                            return false;
                        case is_array($criteriaValue):
                            if (in_array($fieldValue, $criteriaValue)) {
                                return true;
                            }

                            return false;
                        default:
                            if ($fieldValue == $criteriaValue) {
                                return true;
                            }

                            return false;
                    }
                }
            }
        );
    }

    protected function applySortToList(&$list, $sort, $metadata)
    {
        usort(
            $list,
            function ($a, $b) use ($sort, $metadata) {
                foreach ($sort as $s) {
                    if ($s['direction'] == 'asc') {
                        if ($metadata->getFieldValue($a, $s['field']) < $metadata->getFieldValue($b, $s['field'])) {
                            return -1;
                        } elseif ($metadata->getFieldValue($a, $s['field']) > $metadata->getFieldValue($b, $s['field'])) {
                            return 1;
                        }
                    } else {
                        if ($metadata->getFieldValue($a, $s['field']) > $metadata->getFieldValue($b, $s['field'])) {
                            return -1;
                        } elseif ($metadata->getFieldValue($a, $s['field']) < $metadata->getFieldValue($b, $s['field'])) {
                            return 1;
                        }
                    }
                }

                return 0;
            }
        );
    }

    protected function getSort($event)
    {
        foreach ($event->getTarget()->getRequest()->getQuery() as $key => $value) {
            if (substr($key, 0, 4) == 'sort' && (! isset($value) || $value == '')) {
                $sort = $key;
                break;
            }
        }

        if (! isset($sort)) {
            return [];
        }

        $sortFields = explode(',', str_replace(')', '', str_replace('sort(', '', $sort)));
        $return = [];

        foreach ($sortFields as $value) {
            $return[] = [
                'field' => substr($value, 1),
                'direction' => substr($value, 0, 1) == '+' ? 'asc' : 'desc'
            ];
        }

        return $return;
    }
}
