<?php

declare(strict_types=1);

namespace Pandawa\Pavana\Contract;

use Http\Client\HttpAsyncClient;
use Pandawa\Pavana\HttpClient\Options;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpHandlerFactoryInterface
{
    public function create(Options $options): HttpAsyncClient;
}
