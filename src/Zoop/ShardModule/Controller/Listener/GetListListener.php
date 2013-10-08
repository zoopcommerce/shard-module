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

        $criteria = $this->getCriteria($event);

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
            $total = $this->addCriteriaToQuery($totalQuery, $criteria, $metadata, $documentManager)
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
        } else {
            $resultsQuery = $documentManager->createQueryBuilder()
                ->find($metadata->name);
            $this->addCriteriaToQuery($resultsQuery, $criteria, $metadata, $documentManager);
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

    protected function getCriteria($event)
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

    protected function addCriteriaToQuery($query, $criteria, $metadata, $documentManager)
    {
        foreach ($criteria as $field => $value) {
            $pieces = explode('.', $field);
            $targetMetadata = $metadata;
            $mapping = null;
            foreach ($pieces as $piece) {
                if (isset($mapping)) {
                    $targetMetadata = $documentManager->getClassMetadata($mapping['targetDocument']);
                }
                $mapping = $targetMetadata->getFieldMapping($piece);
            }
            if ($mapping['type'] == 'collection') {
                if (!is_array($value)) {
                    $value = [$value];
                }
                $query->field($field)->in($value);
            } elseif (is_array($value)) {
                $query->field($field)->in($value);
            } elseif ($mapping['type'] == 'int') {
                $query->field($field)->equals((integer) $value);
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
                    if (!is_array($fieldValue)) {
                        $fieldValue = [$fieldValue];
                    }
                    if (!is_array($criteriaValue)) {
                        $criteriaValue = [$criteriaValue];
                    }

                    return (count(array_intersect($criteriaValue, $fieldValue)) > 0);
                }
            }
        );
    }

    protected function applySortToList(&$list, $sort, $metadata)
    {
        usort(
            $list,
            function ($aa, $bb) use ($sort, $metadata) {
                foreach ($sort as $ss) {
                    if ($ss['direction'] == 'asc') {
                        $direction = 1;
                    } else {
                        $direction = -1;
                    }

                    if ($metadata->getFieldValue($aa, $ss['field']) < $metadata->getFieldValue($bb, $ss['field'])) {
                        return -1 * $direction;
                    }
                    if ($metadata->getFieldValue($aa, $ss['field']) > $metadata->getFieldValue($bb, $ss['field'])) {
                        return 1 * $direction;
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
