<?php

declare(strict_types=1);

namespace Pandawa\Pavana;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class BatchRequest
{
    private array $requests = [];

    public function __construct(array $requests)
    {
        $resolver = new OptionsResolver();

        $this->configureDefaults($resolver);

        foreach ($requests as $key => $request) {
            $this->requests[$key] = array_merge($resolver->resolve($request), ['response_key' => $key]);
        }
    }

    public function reduce(callable $callback): array
    {
        return array_reduce($this->requests, $callback, []);
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method'  => 'GET',
            'uri'     => '',
            'options' => [],
        ]);
    }
}
