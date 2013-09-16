<?php
/**
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\ShardModule\Controller\Listener;

use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Header\CacheControl;
use Zend\Http\Header\LastModified;
use Zend\Mvc\MvcEvent;
use Zoop\ShardModule\Exception;
use Zoop\ShardModule\Controller\Event;

/**
 *
 * @since   1.0
 * @version $Revision$
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class GetListListener implements ListenerAggregateInterface
{
    use SelectTrait;

    protected $listeners = array();

    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(Event::GET_LIST, [$this, 'onGetList']);
    }

    /**
     * Detach all our listeners from the event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function onGetList(MvcEvent $event)
    {
        $options = $event->getTarget()->getOptions();
        $documentManager = $options->getObjectManager();

        if (! ($endpoint = $event->getParam('endpoint'))) {
            $endpoint = $options->getEndpoint();
        }

        if ($document = $event->getParam('document')) {
            $metadata = $documentManager->getClassMetadata(get_class($document));
        } else {
            $metadata = $documentManager->getClassMetadata($options->getDocumentClass());
        }

        unset($this->range);

        if ($list = $event->getParam('list')) {
            $list = $list->getValues();
        }

        $criteria = $this->getCriteria($metadata);

        //filter list on criteria
        if (count($criteria) > 0 && $list) {
            $list = $this->applyCriteriaToList($list, $criteria);
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
            return [];
        }

        $offset = $this->getOffset();
        if ($offset > $total - 1) {
            throw new Exception\BadRangeException();
        }
        $sort = $this->getSort();

        if ($list) {
            //apply any required sort to the result
            if (count($sort) > 0) {
                $this->applySortToList($list, $sort);
            }
            $list = array_slice($list, $offset, $this->getLimit());
            $event->setParam('list') = $list;
            $items = $event->getTarget()->trigger(Event::SERIALIZE_LIST, $event)->last();
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
            $event->setParam('list') = $resultsCursor;
            $items = $event->getTarget()->trigger(Event::SERIALIZE_LIST, $event)->last();
        }

        //apply any select
        if ($select = $this->getSelect()) {
            $select = array_fill_keys($select, 0);
            foreach ($items as $key => $item) {
                $items[$key] = array_intersect_key($item, $select);
            }
        }

        $max = $offset + count($items) - 1;
        $event->getResponse()->getHeaders()->addHeader(ContentRange::fromString("Content-Range: $offset-$max/$total"));

        return $items;
    }

    protected function getLimit()
    {
        list($lower, $upper) = $this->getRange();

        return $upper - $lower + 1;
    }

    protected function getOffset()
    {
        return $this->getRange()[0];
    }

    protected function getRange()
    {
        if (isset($this->range)) {
            return $this->range;
        }

        $header = $this->controller->getRequest()->getHeader('Range');
        $limit = $this->options->getLimit();
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

    protected function getCriteria($metadata)
    {
        $result = [];
        $dotPlaceholder = $this->controller->getOptions()->getQueryDotPlaceholder();
        foreach ($this->controller->getRequest()->getQuery() as $key => $value) {
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

    protected function applyCriteriaToList($list, $criteria)
    {
        $metadata = $this->options->getDocumentManager()->getClassMetadata(get_class($list[0]));

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

    protected function applySortToList(&$list, $sort)
    {
        $metadata = $this->options->getDocumentManager()->getClassMetadata(get_class($list[0]));

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

    protected function getSort()
    {
        foreach ($this->controller->getRequest()->getQuery() as $key => $value) {
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
