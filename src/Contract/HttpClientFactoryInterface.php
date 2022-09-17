<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpClientFactoryInterface
{
    public function create(array $options = []): HttpClientInterface;
}
