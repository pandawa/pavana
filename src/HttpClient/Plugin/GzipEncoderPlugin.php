<?php

declare(strict_types=1);

namespace Pandawa\Pavana\HttpClient\Plugin;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class GzipEncoderPlugin implements Plugin
{
    public function __construct(private readonly StreamFactoryInterface $streamFactory)
    {
        if (!extension_loaded('zlib')) {
            throw new RuntimeException('The "zlib" extension must be enabled to use this plugin.');
        }
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $requestBody = $request->getBody();

        if ($requestBody->isSeekable()) {
            $requestBody->rewind();
        }

        if (false === $encodedBody = gzcompress($requestBody->getContents(), -1, ZLIB_ENCODING_GZIP)) {
            throw new RuntimeException('Failed to GZIP-encode the request body.');
        }

        $request = $request
            ->withHeader('Content-Encoding', 'gzip')
            ->withBody($this->streamFactory->createStream($encodedBody))
        ;

        return $next($request);
    }
}
