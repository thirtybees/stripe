<?php

namespace ThirtyBeesStripe;

class CollectionTest extends TestCase
{
    private function pageableModelResponse($ids, $hasMore)
    {
        $data = array();
        foreach ($ids as $id) {
            array_push($data, array(
                'id' => $id,
                'object' => 'pageablemodel'
            ));
        }
        return array(
            'object' => 'list',
            'url' => '/v1/pageablemodels',
            'data' => $data,
            'has_more' => $hasMore
        );
    }

    public function testAutoPagingOnePage()
    {
        $collection = Collection::constructFrom(
            $this->pageableModelResponse(array('pm_123', 'pm_124'), false),
            new Util\RequestOptions()
        );

        $seen = array();
        foreach ($collection->autoPagingIterator() as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame($seen, array('pm_123', 'pm_124'));
    }
}
