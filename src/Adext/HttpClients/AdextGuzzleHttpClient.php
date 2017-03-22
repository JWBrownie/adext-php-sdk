<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 1:00 PM
 */

namespace Adext\HttpClients;


class AdextGuzzleHttpClient implements AdextHttpClientInterface
{

    /**
     * Sends a request to the server and returns the raw response.
     *
     * @param string $url The endpoint to send the request to.
     * @param string $method The request method.
     * @param string $body The body of the request.
     * @param array $headers The request headers.
     * @param int $timeOut The timeout in seconds for the request.
     *
     * @return \Adext\Http\RawResponse Raw response from the server.
     *
     * @throws \Adext\Exceptions\AdextSDKException
     */
    public function send($url, $method, $body, array $headers, $timeOut)
    {
        // TODO: Implement send() method.
    }
}