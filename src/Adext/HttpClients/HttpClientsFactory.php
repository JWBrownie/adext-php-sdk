<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 1:03 PM
 */

namespace Adext\HttpClients;


use Exception;
use MongoDB\Driver\Exception\InvalidArgumentException;

class HttpClientsFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }
    /**
     * HTTP client generation.
     *
     * @param AdextHttpClientInterface|Client|string|null $handler
     *
     * @throws \Exception If the cURL extension or the Guzzle client aren't available (if required).
     * @throws \InvalidArgumentException If the http client handler isn't "curl", "stream", "guzzle", or an instance of Adext\HttpClients\AdextHttpClientInterface.
     *
     * @return AdextHttpClientInterface
     */
    public static function createHttpClient($handler)
    {
        if (!$handler) {
            return self::detectDefaultClient();
        }
        if ($handler instanceof AdextHttpClientInterface) {
            return $handler;
        }
        if ('stream' === $handler) {
            return new AdextStreamHttpClient();
        }
        if ('curl' === $handler) {
            if (!extension_loaded('curl')) {
                throw new Exception('The cURL extension must be loaded in order to use the "curl" handler.');
            }
            return new AdextCurlHttpClient();
        }
        if ('guzzle' === $handler && !class_exists('GuzzleHttp\Client')) {
            throw new Exception('The Guzzle HTTP client must be included in order to use the "guzzle" handler.');
        }
        if ($handler instanceof Client) {
            return new AdextGuzzleHttpClient($handler);
        }
        if ('guzzle' === $handler) {
            return new AdextGuzzleHttpClient();
        }
        throw new InvalidArgumentException('The http client handler must be set to "curl", "stream", "guzzle", be an instance of GuzzleHttp\Client or an instance of Adext\HttpClients\AdextHttpClientInterface');
    }
    /**
     * Detect default HTTP client.
     *
     * @return AdextHttpClientInterface
     */
    private static function detectDefaultClient()
    {
        if (extension_loaded('curl')) {
            return new AdextCurlHttpClient();
        }
        if (class_exists('GuzzleHttp\Client')) {
            return new AdextGuzzleHttpClient();
        }
        return new AdextStreamHttpClient();
    }
}