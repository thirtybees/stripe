<?php

namespace Stripe;

class ErrorTest extends TestCase
{
    public function testCreation()
    {
        try {
            throw new Error\Api(
                "hello",
                500,
                "{'foo':'bar'}",
                ['foo' => 'bar']
            );
            $this->fail("Did not raise error");
        } catch (Error\Api $e) {
            $this->assertSame("hello", $e->getMessage());
            $this->assertSame(500, $e->getHttpStatus());
            $this->assertSame("{'foo':'bar'}", $e->getHttpBody());
            $this->assertSame(['foo' => 'bar'], $e->getJsonBody());
            $this->assertSame(null, $e->getHttpHeaders());
            $this->assertSame(null, $e->getRequestId());
        }
    }

    public function testResponseHeaders()
    {
        try {
            throw new Error\Api(
                "hello",
                500,
                "{'foo':'bar'}",
                ['foo' => 'bar'],
                ['Request-Id' => 'req_bar']
            );
            $this->fail("Did not raise error");
        } catch (Error\Api $e) {
            $this->assertSame(['Request-Id' => 'req_bar'], $e->getHttpHeaders());
            $this->assertSame('req_bar', $e->getRequestId());
        }
    }

    public function testCode()
    {
        try {
            throw new Error\Card(
                "hello",
                "some_param",
                "some_code",
                400,
                "{'foo':'bar'}",
                ['foo' => 'bar']
            );
            $this->fail("Did not raise error");
        } catch (Error\Card $e) {
            $this->assertSame("some_param", $e->getStripeParam());
            $this->assertSame('some_code', $e->getStripeCode());
        }
    }
}
