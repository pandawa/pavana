<?php

declare(strict_types=1);

namespace Pandawa\Pavana\HttpClient\Plugin;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class JsonDecodePlugin implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $promise = $next($request);

        return $promise->then(function (ResponseInterface $response) {
            if (!empty($contentType = $response->getHeader('Content-Type'))) {
                if (false !== array_search('application/json', $contentType)) {
                    return json_decode($response->getBody()->getContents(), true);
                }
            }

            return $response;
        });
    }
}
