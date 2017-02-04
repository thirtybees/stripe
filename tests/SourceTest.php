<?php

namespace ThirtybeesStripe;

class SourceTest extends TestCase
{
    public function testRetrieve()
    {
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            [],
            [
                'id' => 'src_foo',
                'object' => 'source',
            ]
        );
        $source = Source::retrieve('src_foo');
        $this->assertSame($source->id, 'src_foo');
    }

    public function testCreate()
    {
        $this->mockRequest(
            'POST',
            '/v1/sources',
            [
                'type' => 'bitcoin',
                'amount' => 1000,
                'currency' => 'usd',
                'owner' => ['email' => 'jenny.rosen@example.com'],
            ],
            [
                'id' => 'src_foo',
                'object' => 'source'
            ]
        );
        $source = Source::create(
            [
                'type' => 'bitcoin',
                'amount' => 1000,
                'currency' => 'usd',
                'owner' => ['email' => 'jenny.rosen@example.com'],
            ]
        );
        $this->assertSame($source->id, 'src_foo');
    }

    public function testSave()
    {
        $response = [
            'id' => 'src_foo',
            'object' => 'source',
            'metadata' => [],
        ];
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            [],
            $response
        );

        $response['metadata'] = ['foo' => 'bar'];
        $this->mockRequest(
            'POST',
            '/v1/sources/src_foo',
            [
                'metadata' => ['foo' => 'bar'],
            ],
            $response
        );

        $source = Source::retrieve('src_foo');
        $source->metadata['foo'] = 'bar';
        $source->save();
        $this->assertSame($source->metadata['foo'], 'bar');
    }

    public function testVerify()
    {
        $response = [
            'id' => 'src_foo',
            'object' => 'source',
            'verification' => ['status' => 'pending'],
        ];
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            [],
            $response
        );

        $response['verification']['status'] = 'succeeded';
        $this->mockRequest(
            'POST',
            '/v1/sources/src_foo/verify',
            [
                'values' => [32, 45],
            ],
            $response
        );

        $source = Source::retrieve('src_foo');
        $this->assertSame($source->verification->status, 'pending');
        $source->verify(
            [
                'values' => [32, 45],
            ]
        );
        $this->assertSame($source->verification->status, 'succeeded');
    }
}
