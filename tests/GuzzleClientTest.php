<?php

namespace ThirtybeesStripe;

use ThirtybeesStripe\HttpClient\GuzzleClient;

class GuzzleClientTest extends TestCase
{
    public function testTimeout()
    {
        $curl = new GuzzleClient();
        $this->assertSame(GuzzleClient::DEFAULT_TIMEOUT, $curl->getTimeout());
        $this->assertSame(GuzzleClient::DEFAULT_CONNECT_TIMEOUT, $curl->getConnectTimeout());

        // implicitly tests whether we're returning the GuzzleClient instance
        $curl = $curl->setConnectTimeout(1)->setTimeout(10);
        $this->assertSame(1, $curl->getConnectTimeout());
        $this->assertSame(10, $curl->getTimeout());

        $curl->setTimeout(-1);
        $curl->setConnectTimeout(-999);
        $this->assertSame(0, $curl->getTimeout());
        $this->assertSame(0, $curl->getConnectTimeout());
    }

    public function testEncode()
    {
        $a = [
            'my' => 'value',
            'that' => ['your' => 'example'],
            'bar' => 1,
            'baz' => null
        ];

        $enc = GuzzleClient::encode($a);
        $this->assertSame('my=value&that%5Byour%5D=example&bar=1', $enc);

        $a = ['that' => ['your' => 'example', 'foo' => null]];
        $enc = GuzzleClient::encode($a);
        $this->assertSame('that%5Byour%5D=example', $enc);

        $a = ['that' => 'example', 'foo' => ['bar', 'baz']];
        $enc = GuzzleClient::encode($a);
        $this->assertSame('that=example&foo%5B%5D=bar&foo%5B%5D=baz', $enc);

        $a = [
            'my' => 'value',
            'that' => ['your' => ['cheese', 'whiz', null]],
            'bar' => 1,
            'baz' => null
        ];

        $enc = GuzzleClient::encode($a);
        $expected = 'my=value&that%5Byour%5D%5B%5D=cheese'
              . '&that%5Byour%5D%5B%5D=whiz&bar=1';
        $this->assertSame($expected, $enc);

        // Ignores an empty array
        $enc = GuzzleClient::encode(['foo' => [], 'bar' => 'baz']);
        $expected = 'bar=baz';
        $this->assertSame($expected, $enc);

        $a = ['foo' => [['bar' => 'baz'], ['bar' => 'bin']]];
        $enc = GuzzleClient::encode($a);
        $this->assertSame('foo%5B0%5D%5Bbar%5D=baz&foo%5B1%5D%5Bbar%5D=bin', $enc);
    }

    public function testSslOption()
    {
        // make sure options array loads/saves properly
        $optionsArray = [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1];
        $withOptionsArray = new GuzzleClient($optionsArray);
        $this->assertSame($withOptionsArray->getDefaultOptions(), $optionsArray);
    }
}
