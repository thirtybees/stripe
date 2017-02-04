<?php

namespace Stripe;

class CollectionTest extends TestCase
{
    private function pageableModelResponse($ids, $hasMore)
    {
        $data = [];
        foreach ($ids as $id) {
            array_push($data, [
                'id' => $id,
                'object' => 'pageablemodel'
            ]
            );
        }
        return [
            'object' => 'list',
            'url' => '/v1/pageablemodels',
            'data' => $data,
            'has_more' => $hasMore
        ];
    }

    public function testAutoPagingOnePage()
    {
        $collection = Collection::constructFrom(
            $this->pageableModelResponse(['pm_123', 'pm_124'], false),
            new Util\RequestOptions()
        );

        $seen = [];
        foreach ($collection->autoPagingIterator() as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame($seen, ['pm_123', 'pm_124']);
    }

    public function testAutoPagingThreePages()
    {
        $collection = Collection::constructFrom(
            $this->pageableModelResponse(['pm_123', 'pm_124'], true),
            new Util\RequestOptions()
        );
        $collection->setRequestParams(['foo' => 'bar']);

        $this->mockRequest(
            'GET',
            '/v1/pageablemodels',
            [
                  'foo' => 'bar',
                  'starting_after' => 'pm_124'
            ],
            $this->pageableModelResponse(['pm_125', 'pm_126'], true)
        );
        $this->mockRequest(
            'GET',
            '/v1/pageablemodels',
            [
                  'foo' => 'bar',
                  'starting_after' => 'pm_126'
            ],
            $this->pageableModelResponse(['pm_127'], false)
        );

        $seen = [];
        foreach ($collection->autoPagingIterator() as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame($seen, ['pm_123', 'pm_124', 'pm_125', 'pm_126', 'pm_127']);
    }

    public function testIteratorToArray()
    {
        $collection = Collection::constructFrom(
            $this->pageableModelResponse(['pm_123', 'pm_124'], true),
            new Util\RequestOptions()
        );

        $this->mockRequest(
            'GET',
            '/v1/pageablemodels',
            [
                  'starting_after' => 'pm_124'
            ],
            $this->pageableModelResponse(['pm_125', 'pm_126'], true)
        );
        $this->mockRequest(
            'GET',
            '/v1/pageablemodels',
            [
                  'starting_after' => 'pm_126'
            ],
            $this->pageableModelResponse(['pm_127'], false)
        );

        $seen = [];
        foreach (iterator_to_array($collection->autoPagingIterator()) as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame($seen, ['pm_123', 'pm_124', 'pm_125', 'pm_126', 'pm_127']);
    }
}
