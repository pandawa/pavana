<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Contract;

use Psr\Http\Message\RequestInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface RequestFactoryInterface
{
    public function create(string $method, string $uri = '', array $options = []): RequestInterface;
}
