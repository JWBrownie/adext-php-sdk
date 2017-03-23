<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 12:30 PM
 */

namespace Adext;


use Adext\AdextNodes\AdextEdge;
use Adext\Authentication\AccessToken;
use Adext\Authentication\OAuth2Client;
use Adext\Exceptions\AdextSDKException;
use Adext\HttpClients\HttpClientsFactory;
use Adext\PersistentData\PersistentDataInterface;
use Adext\PseudoRandomString\PseudoRandomStringGeneratorInterface;
use Adext\Url\AdextUrlDetectionHandler;
use Adext\PseudoRandomString\PseudoRandomStringGeneratorFactory;
use Adext\Url\UrlDetectionInterface;

class Adext
{
    /**
     * @const string Version number of the Adext PHP SDK.
     */
    const VERSION = '1.0.0';
    /**
     * @const string Default Adext API version for requests.
     */
    const DEFAULT_VERSION = 'v1.0';
    /**
     * @const string The name of the environment variable that contains the app ID.
     */
    const APP_ID_ENV_NAME = 'ADEXT_APP_ID';
    /**
     * @const string The name of the environment variable that contains the app secret.
     */
    const APP_SECRET_ENV_NAME = 'ADEXT_APP_SECRET';
    /**
     * @var AdextApp The AdextApp entity.
     */
    protected $app;
    /**
     * @var AdextClient The Adext client service.
     */
    protected $client;
    /**
     * @var OAuth2Client The OAuth 2.0 client service.
     */
    protected $oAuth2Client;
    /**
     * @var UrlDetectionInterface|null The URL detection handler.
     */
    protected $urlDetectionHandler;
    /**
     * @var PseudoRandomStringGeneratorInterface|null The cryptographically secure pseudo-random string generator.
     */
    protected $pseudoRandomStringGenerator;
    /**
     * @var AccessToken|null The default access token to use with requests.
     */
    protected $defaultAccessToken;
    /**
     * @var string|null The default Adext version we want to use.
     */
    protected $defaultAdextVersion;
    /**
     * @var PersistentDataInterface|null The persistent data handler.
     */
    protected $persistentDataHandler;
    /**
     * @var AdextResponse|AdextBatchResponse|null Stores the last request made to Adext.
     */
    protected $lastResponse;
    /**
     * Instantiates a new Adext super-class object.
     *
     * @param array $config
     *
     * @throws AdextSDKException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'app_id' => getenv(static::APP_ID_ENV_NAME),
            'app_secret' => getenv(static::APP_SECRET_ENV_NAME),
            'default_version' => static::DEFAULT_VERSION,
            'enable_beta_mode' => false,
            'http_client_handler' => null,
            'persistent_data_handler' => null,
            'pseudo_random_string_generator' => null,
            'url_detection_handler' => null,
        ], $config);
        if (!$config['app_id']) {
            throw new AdextSDKException('Required "app_id" key not supplied in config and could not find fallback environment variable "' . static::APP_ID_ENV_NAME . '"');
        }
        if (!$config['app_secret']) {
            throw new AdextSDKException('Required "app_secret" key not supplied in config and could not find fallback environment variable "' . static::APP_SECRET_ENV_NAME . '"');
        }
        $this->app = new AdextApp($config['app_id'], $config['app_secret']);
        $this->client = new AdextClient(
            HttpClientsFactory::createHttpClient($config['http_client_handler']),
            $config['enable_beta_mode']
        );
        $this->pseudoRandomStringGenerator = PseudoRandomStringGeneratorFactory::createPseudoRandomStringGenerator(
            $config['pseudo_random_string_generator']
        );
        $this->setUrlDetectionHandler($config['url_detection_handler'] ?: new AdextUrlDetectionHandler());
        $this->persistentDataHandler = PersistentDataFactory::createPersistentDataHandler(
            $config['persistent_data_handler']
        );
        if (isset($config['default_access_token'])) {
            $this->setDefaultAccessToken($config['default_access_token']);
        }
        // @todo v6: Throw an InvalidArgumentException if "default_version" is not set
        $this->defaultAdextVersion = $config['default_version'];
    }
    /**
     * Returns the AdextApp entity.
     *
     * @return AdextApp
     */
    public function getApp()
    {
        return $this->app;
    }
    /**
     * Returns the AdextClient service.
     *
     * @return AdextClient
     */
    public function getClient()
    {
        return $this->client;
    }
    /**
     * Returns the OAuth 2.0 client service.
     *
     * @return OAuth2Client
     */
    public function getOAuth2Client()
    {
        if (!$this->oAuth2Client instanceof OAuth2Client) {
            $app = $this->getApp();
            $client = $this->getClient();
            $this->oAuth2Client = new OAuth2Client($app, $client, $this->defaultAdextVersion);
        }
        return $this->oAuth2Client;
    }
    /**
     * Returns the last response returned from Adext.
     *
     * @return AdextResponse|AdextBatchResponse|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
    /**
     * Returns the URL detection handler.
     *
     * @return UrlDetectionInterface
     */
    public function getUrlDetectionHandler()
    {
        return $this->urlDetectionHandler;
    }
    /**
     * Changes the URL detection handler.
     *
     * @param UrlDetectionInterface $urlDetectionHandler
     */
    private function setUrlDetectionHandler(UrlDetectionInterface $urlDetectionHandler)
    {
        $this->urlDetectionHandler = $urlDetectionHandler;
    }
    /**
     * Returns the default AccessToken entity.
     *
     * @return AccessToken|null
     */
    public function getDefaultAccessToken()
    {
        return $this->defaultAccessToken;
    }
    /**
     * Sets the default access token to use with requests.
     *
     * @param AccessToken|string $accessToken The access token to save.
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultAccessToken($accessToken)
    {
        if (is_string($accessToken)) {
            $this->defaultAccessToken = new AccessToken($accessToken);
            return;
        }
        if ($accessToken instanceof AccessToken) {
            $this->defaultAccessToken = $accessToken;
            return;
        }
        throw new \InvalidArgumentException('The default access token must be of type "string" or Adext\AccessToken');
    }
    /**
     * Returns the default Adext version.
     *
     * @return string
     */
    public function getDefaultAdextVersion()
    {
        return $this->defaultAdextVersion;
    }
    /**
     * Sends a GET request to Adext and returns the result.
     *
     * @param string                  $endpoint
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return AdextResponse
     *
     * @throws AdextSDKException
     */
    public function get($endpoint, $accessToken = null, $eTag = null, $graphVersion = null)
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params = [],
            $accessToken,
            $eTag,
            $graphVersion
        );
    }
    /**
     * Sends a POST request to Adext and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return AdextResponse
     *
     * @throws AdextSDKException
     */
    public function post($endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params,
            $accessToken,
            $eTag,
            $graphVersion
        );
    }
    /**
     * Sends a DELETE request to Adext and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return AdextResponse
     *
     * @throws AdextSDKException
     */
    public function delete($endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params,
            $accessToken,
            $eTag,
            $graphVersion
        );
    }
    /**
     * Sends a request to Adext for the next page of results.
     *
     * @param AdextEdge $graphEdge The AdextEdge to paginate over.
     *
     * @return AdextEdge|null
     *
     * @throws AdextSDKException
     */
    public function next(AdextEdge $graphEdge)
    {
        return $this->getPaginationResults($graphEdge, 'next');
    }
    /**
     * Sends a request to Adext for the previous page of results.
     *
     * @param AdextEdge $graphEdge The AdextEdge to paginate over.
     *
     * @return AdextEdge|null
     *
     * @throws AdextSDKException
     */
    public function previous(AdextEdge $graphEdge)
    {
        return $this->getPaginationResults($graphEdge, 'previous');
    }
    /**
     * Sends a request to Adext for the next page of results.
     *
     * @param AdextEdge $graphEdge The AdextEdge to paginate over.
     * @param string    $direction The direction of the pagination: next|previous.
     *
     * @return AdextEdge|null
     *
     * @throws AdextSDKException
     */
    public function getPaginationResults(AdextEdge $graphEdge, $direction)
    {
        $paginationRequest = $graphEdge->getPaginationRequest($direction);
        if (!$paginationRequest) {
            return null;
        }
        $this->lastResponse = $this->client->sendRequest($paginationRequest);
        // Keep the same AdextNode subclass
        $subClassName = $graphEdge->getSubClassName();
        $graphEdge = $this->lastResponse->getAdextEdge($subClassName, false);
        return count($graphEdge) > 0 ? $graphEdge : null;
    }
    /**
     * Sends a request to Adext and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return AdextResponse
     *
     * @throws AdextSDKException
     */
    public function sendRequest($method, $endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultAdextVersion;
        $request = $this->request($method, $endpoint, $params, $accessToken, $eTag, $graphVersion);
        return $this->lastResponse = $this->client->sendRequest($request);
    }
    /**
     * Sends a batched request to Adext and returns the result.
     *
     * @param array                   $requests
     * @param AccessToken|string|null $accessToken
     * @param string|null             $graphVersion
     *
     * @return AdextBatchResponse
     *
     * @throws AdextSDKException
     */
    public function sendBatchRequest(array $requests, $accessToken = null, $graphVersion = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultAdextVersion;
        $batchRequest = new AdextBatchRequest(
            $this->app,
            $requests,
            $accessToken,
            $graphVersion
        );
        return $this->lastResponse = $this->client->sendBatchRequest($batchRequest);
    }
    /**
     * Instantiates a new AdextRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return AdextRequest
     *
     * @throws AdextSDKException
     */
    public function request($method, $endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultAdextVersion;
        return new AdextRequest(
            $this->app,
            $accessToken,
            $method,
            $endpoint,
            $params,
            $eTag,
            $graphVersion
        );
    }
}