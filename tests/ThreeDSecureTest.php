<?php

namespace Stripe;

class ThreeDSecureTest extends TestCase
{
    public function testRetrieve()
    {
        $this->mockRequest(
            'GET',
            '/v1/3d_secure/tdsrc_test',
            [],
            [
                'id' => 'tdsrc_test',
                'object' => 'three_d_secure'
            ]
        );
        $three_d_secure = ThreeDSecure::retrieve('tdsrc_test');
        $this->assertSame($three_d_secure->id, 'tdsrc_test');
    }

    public function testCreate()
    {
        $this->mockRequest(
            'POST',
            '/v1/3d_secure',
            [
                'card' => 'tok_test',
                'amount' => 1500,
                'currency' => 'usd',
                'return_url' => 'https://example.org/3d-secure-result'
            ],
            [
                'id' => 'tdsrc_test',
                'object' => 'three_d_secure'
            ]
        );
        $three_d_secure = ThreeDSecure::create(
            [
                'card' => 'tok_test',
                'amount' => 1500,
                'currency' => 'usd',
                'return_url' => 'https://example.org/3d-secure-result'
            ]
        );
        $this->assertSame($three_d_secure->id, 'tdsrc_test');
    }
}
