<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Factory;

use GuzzleHttp\Psr7;
use Pandawa\Pavana\Contract\RequestFactoryInterface as RequestFactoryContract;
use Pandawa\Pavana\HttpClient\Options;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RequestFactory implements RequestFactoryContract
{
    public function __construct(private readonly Options $defaults)
    {
    }

    public function create(string $method, string $uri = '', array $options = []): RequestInterface
    {
        $options = $this->prepareDefaults($options);
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $version = $options['version'] ?? '1.1';
        $uri = $this->buildUri(Psr7\Utils::uriFor($uri));

        unset($options['headers'], $options['body'], $options['version']);

        return $this->applyOptions(new Psr7\Request($method, $uri, $headers, $body, $version), $options);
    }

    private function applyOptions(RequestInterface $request, array &$options): RequestInterface
    {
        $modify = [
            'set_headers' => [],
        ];

        if ($headers = $options[Options::HEADERS] ?? null) {
            $modify['set_headers'] = $headers;
            unset($options[Options::HEADERS]);
        }

        if ($formParams = $options[Options::FORM_PARAMS] ?? null) {
            if ($options[Options::MULTIPART] ?? null) {
                throw new \InvalidArgumentException('You cannot use '
                    . 'form_params and multipart at the same time. Use the '
                    . 'form_params option if you want to send application/'
                    . 'x-www-form-urlencoded requests, and the multipart '
                    . 'option to send multipart/form-data requests.');
            }

            $options[Options::BODY] = http_build_query($formParams, '', '&');
            unset($options[Options::FORM_PARAMS]);

            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        if ($multiPart = $options[Options::MULTIPART] ?? null) {
            $options[Options::BODY] = new Psr7\MultipartStream($multiPart);
            unset($options[Options::MULTIPART]);
        }

        if ($json = $options[Options::JSON] ?? null) {
            $options[Options::BODY] = json_encode($json);
            unset($options[Options::JSON]);

            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'application/json';
        }

        if (!empty($options['decode_content']) && $options['decode_content'] !== true) {
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Accept-Encoding'], $options['_conditional']);
            $modify['set_headers']['Accept-Encoding'] = $options['decode_content'];
        }

        if ($body = $options[Options::BODY] ?? null) {
            if (is_array($body)) {
                throw new \InvalidArgumentException('Body should cannot be an array.');
            }

            $modify[Options::BODY] = Psr7\Utils::streamFor($body);
            unset($options[Options::BODY]);
        }

        if ($value = $options[Options::QUERY] ?? null) {
            if (is_array($value)) {
                $value = http_build_query($value, '', '&', \PHP_QUERY_RFC3986);
            }

            if (!is_string($value)) {
                throw new \InvalidArgumentException('query must be a string or array');
            }

            $modify[Options::QUERY] = $value;
            unset($options[Options::QUERY]);
        }

        $request = Psr7\Utils::modifyRequest($request, $modify);
        if ($request->getBody() instanceof Psr7\MultipartStream) {
            $options['_conditional'] = Psr7\Utils::caselessRemove(['Content-Type'], $options['_conditional']);
            $options['_conditional']['Content-Type'] = 'multipart/form-data; boundary='
                . $request->getBody()->getBoundary();
        }

        if ($conditional = $options['_conditional'] ?? null) {
            $modify = [];
            foreach ($conditional as $k => $v) {
                if (!$request->hasHeader($k)) {
                    $modify['set_headers'][$k] = $v;
                }
            }
            $request = Psr7\Utils::modifyRequest($request, $modify);
            // Don't pass this internal value along to middleware/handlers.
            unset($options['_conditional']);
        }

        return $request;
    }

    private function prepareDefaults(array $options): array
    {
        $defaults = $this->defaults->toArray();
        $headers = array_merge($this->defaults->getHeaders(), ['User-Agent' => $this->defaults->getUserAgent()]);

        if (!empty($headers)) {
            $defaults['_conditional'] = $headers;
            unset($defaults['headers']);
        }

        if (array_key_exists('headers', $options)) {
            if (null === $options['headers']) {
                $defaults['_conditional'] = [];
                unset($options['headers']);
            }
        }

        return array_filter($options + $defaults, function ($item) {
            return null !== $item;
        });
    }

    private function buildUri(UriInterface $uri): UriInterface
    {
        if (null !== $baseUri = $this->defaults->getBaseUri()) {
            $uri = Psr7\UriResolver::resolve($baseUri, $uri);
        }

        return '' === $uri->getScheme() && '' !== $uri->getHost() ? $uri->withScheme('http') : $uri;
    }
}
