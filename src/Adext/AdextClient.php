<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 1:27 PM
 */

namespace Adext;


use Adext\Exceptions\AdextSDKException;
use Adext\HttpClients\AdextCurlHttpClient;
use Adext\HttpClients\AdextHttpClientInterface;
use Adext\HttpClients\AdextStreamHttpClient;

class AdextClient
{
    /**
     * @const string Production Graph API URL.
     */
    const BASE_URL = 'https://adext.com';
    
    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;
    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;
    /**
     * @var bool Toggle to use Graph beta url.
     */
    protected $enableBetaMode = false;
    /**
     * @var AdextHttpClientInterface HTTP client handler.
     */
    protected $httpClientHandler;
    /**
     * @var int The number of calls that have been made to Graph.
     */
    public static $requestCount = 0;
    /**
     * Instantiates a new AdextClient object.
     *
     * @param AdextHttpClientInterface|null $httpClientHandler
     * @param boolean                          $enableBeta
     */
    public function __construct(AdextHttpClientInterface $httpClientHandler = null, $enableBeta = false)
    {
        $this->httpClientHandler = $httpClientHandler ?: $this->detectHttpClientHandler();
        $this->enableBetaMode = $enableBeta;
    }
    /**
     * Sets the HTTP client handler.
     *
     * @param AdextHttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(AdextHttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }
    /**
     * Returns the HTTP client handler.
     *
     * @return AdextHttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }
    /**
     * Detects which HTTP client handler to use.
     *
     * @return AdextHttpClientInterface
     */
    public function detectHttpClientHandler()
    {
        return extension_loaded('curl') ? new AdextCurlHttpClient() : new AdextStreamHttpClient();
    }
    /**
     * Toggle beta mode.
     *
     * @param boolean $betaMode
     */
    public function enableBetaMode($betaMode = true)
    {
        $this->enableBetaMode = $betaMode;
    }
    /**
     * Returns the base URL.
     * @return string
     */
    public function getBaseUrl()
    {
        return static::BASE_URL;
    }
    /**
     * Prepares the request for sending to the client handler.
     *
     * @param AdextRequest $request
     *
     * @return array
     */
    public function prepareRequestMessage(AdextRequest $request)
    {
        $url = $this->getBaseUrl() . $request->getUrl();
        // If we're sending files they should be sent as multipart/form-data
        if ($request->containsFileUploads()) {
            $requestBody = $request->getMultipartBody();
            $request->setHeaders([
                'Content-Type' => 'multipart/form-data; boundary=' . $requestBody->getBoundary(),
            ]);
        } else {
            $requestBody = $request->getUrlEncodedBody();
            $request->setHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);
        }
        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $requestBody->getBody(),
        ];
    }
    /**
     * Makes the request to Graph and returns the result.
     *
     * @param AdextRequest $request
     *
     * @return AdextResponse
     *
     * @throws AdextSDKException
     */
    public function sendRequest(AdextRequest $request)
    {
        if (get_class($request) === 'Adext\AdextRequest') {
            $request->validateAccessToken();
        }
        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request);
        // Since file uploads can take a while, we need to give more time for uploads
        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;
        if ($request->containsFileUploads()) {
            $timeOut = static::DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT;
        }
        // Should throw `AdextSDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClientHandler->send($url, $method, $body, $headers, $timeOut);
        static::$requestCount++;
        $returnResponse = new AdextResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );
        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }
        return $returnResponse;
    }
    /**
     * Makes a batched request to Graph and returns the result.
     *
     * @param AdextBatchRequest $request
     *
     * @return AdextBatchResponse
     *
     * @throws AdextSDKException
     */
    public function sendBatchRequest(AdextBatchRequest $request)
    {
        $request->prepareRequestsForBatch();
        $facebookResponse = $this->sendRequest($request);
        return new AdextBatchResponse($request, $facebookResponse);
    }
}