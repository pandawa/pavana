<?php

return [

    // Set default http request timeout
    'timeout' => 5,

    // Set default http headers
    'headers' => [],

    // Default http handler, when left null the Pavana http handler will be used
    'http_handler' => null,

    // Set default http proxy
    'http_proxy' => null,

    // Set default request factory, when left null the Pavana request factory will be used
    'request_factory' => null,

    // Register default http plugins
    'plugins' => [],

    /**
     * Register scoped clients
     *
     *  github => [
     *      'base_uri' => 'https://api.github.com',
     *      'timeout' => 10,
     *      'headers' => [
     *          'Accept' => 'application/json',
     *      ],
     *      'plugins' => [
     *          'pavana.plugins.json_decode',
     *      ]
     * ]
     */
    'clients' => [],

];
