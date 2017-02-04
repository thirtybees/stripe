<?php

namespace ThirtybeesStripe;

class ApplePayDomainTest extends TestCase
{
    public function testCreation()
    {
        $this->mockRequest(
            'POST',
            '/v1/apple_pay/domains',
            ['domain_name' => 'test.com'],
            [
                'id' => 'apwc_create',
                'object' => 'apple_pay_domain'
            ]
        );
        $d = ApplePayDomain::create(
            [
            'domain_name' => 'test.com'
            ]
        );
        $this->assertSame('apwc_create', $d->id);
        $this->assertInstanceOf('ThirtybeesStripe\\ApplePayDomain', $d);
    }

    public function testRetrieve()
    {
        $this->mockRequest(
            'GET',
            '/v1/apple_pay/domains/apwc_retrieve',
            [],
            [
                'id' => 'apwc_retrieve',
                'object' => 'apple_pay_domain'
            ]
        );
        $d = ApplePayDomain::retrieve('apwc_retrieve');
        $this->assertSame('apwc_retrieve', $d->id);
        $this->assertInstanceOf('ThirtybeesStripe\\ApplePayDomain', $d);
    }

    public function testDeletion()
    {
        self::authorizeFromEnv();
        $d = ApplePayDomain::create(
            [
            'domain_name' => 'jackshack.website'
            ]
        );
        $this->assertInstanceOf('ThirtybeesStripe\\ApplePayDomain', $d);
        $this->mockRequest(
            'DELETE',
            '/v1/apple_pay/domains/' . $d->id,
            [],
            ['deleted' => true]
        );
        $d->delete();
        $this->assertTrue($d->deleted);
    }

    public function testList()
    {
        $this->mockRequest(
            'GET',
            '/v1/apple_pay/domains',
            [],
            [
                'url' => '/v1/apple_pay/domains',
                'object' => 'list'
            ]
        );
        $all = ApplePayDomain::all();
        $this->assertSame($all->url, '/v1/apple_pay/domains');
    }
}
