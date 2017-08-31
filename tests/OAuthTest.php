<?php

namespace ThirtyBeesStripe;

class OAuthTest extends TestCase
{
    /**
     * @before
     */
    public function setUpClientId()
    {
        Stripe::setClientId('ca_test');
    }

    /**
     * @after
     */
    public function tearDownClientId()
    {
        Stripe::setClientId(null);
    }

    public function testAuthorizeUrl()
    {
        $uriStr = OAuth::authorizeUrl(array(
            'scope' => 'read_write',
            'state' => 'csrf_token',
            'stripe_user' => array(
                'email' => 'test@example.com',
                'url' => 'https://example.com/profile/test',
                'country' => 'US',
            ),
        ));

        $uri = parse_url($uriStr);
        parse_str($uri['query'], $params);

        $this->assertSame('https', $uri['scheme']);
        $this->assertSame('connect.stripe.com', $uri['host']);
        $this->assertSame('/oauth/authorize', $uri['path']);

        $this->assertSame('ca_test', $params['client_id']);
        $this->assertSame('read_write', $params['scope']);
        $this->assertSame('test@example.com', $params['stripe_user']['email']);
        $this->assertSame('https://example.com/profile/test', $params['stripe_user']['url']);
        $this->assertSame('US', $params['stripe_user']['country']);
    }
}
