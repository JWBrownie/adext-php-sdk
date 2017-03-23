<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 12:31 PM
 */

namespace Adext;

use Adext\Authentication\AccessToken;
use Adext\Exceptions\AdextSDKException;
use Adext\Http\RequestBodyUrlEncoded;
use Adext\Url\AdextUrlManipulator;

class AdextResponse extends AdextRequest
{
    /**
     * @var AdextApp The Adext app entity.
     */
    protected $app;
    /**
     * @var string|null The access token to use for this request.
     */
    protected $accessToken;
    /**
     * @var string The HTTP method for this request.
     */
    protected $method;
    /**
     * @var string The Graph endpoint for this request.
     */
    protected $endpoint;
    /**
     * @var array The headers to send with this request.
     */
    protected $headers = [];
    /**
     * @var array The parameters to send with this request.
     */
    protected $params = [];
    /**
     * @var array The files to send with this request.
     */
    protected $files = [];
    /**
     * @var string ETag to send with this request.
     */
    protected $eTag;
    /**
     * @var string Graph version to use for this request.
     */
    protected $graphVersion;
    /**
     * Creates a new Request entity.
     *
     * @param AdextApp|null        $app
     * @param AccessToken|string|null $accessToken
     * @param string|null             $method
     * @param string|null             $endpoint
     * @param array|null              $params
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     */
    public function __construct(AdextApp $app = null, AccessToken $accessToken = null, $method = null, $endpoint = null, array $params = [], $eTag = null, $graphVersion = null)
    {
        $this->setApp($app);
        $this->setAccessToken($accessToken);
        $this->setMethod($method);
        $this->setEndpoint($endpoint);
        $this->setParams($params);
        $this->setETag($eTag);
        $this->graphVersion = $graphVersion ?: Adext::DEFAULT_VERSION;
    }
    /**
     * Set the access token for this request.
     *
     * @param AccessToken|string|null
     *
     * @return AdextRequest
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        if ($accessToken instanceof AccessToken) {
            $this->accessToken = $accessToken->getValue();
        }
        return $this;
    }
    /**
     * Sets the access token with one harvested from a URL or POST params.
     *
     * @param string $accessToken The access token.
     *
     * @return AdextRequest
     *
     * @throws AdextSDKException
     */
    public function setAccessTokenFromParams($accessToken)
    {
        $existingAccessToken = $this->getAccessToken();
        if (!$existingAccessToken) {
            $this->setAccessToken($accessToken);
        } elseif ($accessToken !== $existingAccessToken) {
            throw new AdextSDKException('Access token mismatch. The access token provided in the AdextRequest and the one provided in the URL or POST params do not match.');
        }
        return $this;
    }
    /**
     * Return the access token for this request.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    /**
     * Return the access token for this request as an AccessToken entity.
     *
     * @return AccessToken|null
     */
    public function getAccessTokenEntity()
    {
        return $this->accessToken ? new AccessToken($this->accessToken) : null;
    }
    /**
     * Set the AdextApp entity used for this request.
     *
     * @param AdextApp|null $app
     */
    public function setApp(AdextApp $app = null)
    {
        $this->app = $app;
    }
    /**
     * Return the AdextApp entity used for this request.
     *
     * @return AdextApp
     */
    public function getApp()
    {
        return $this->app;
    }
    /**
     * Generate an app secret proof to sign this request.
     *
     * @return string|null
     */
    public function getAppSecretProof()
    {
        if (!$accessTokenEntity = $this->getAccessTokenEntity()) {
            return null;
        }
        return $accessTokenEntity->getAppSecretProof($this->app->getSecret());
    }
    /**
     * Validate that an access token exists for this request.
     *
     * @throws AdextSDKException
     */
    public function validateAccessToken()
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            throw new AdextSDKException('You must provide an access token.');
        }
    }
    /**
     * Set the HTTP method for this request.
     *
     * @param string
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }
    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * Validate that the HTTP method is set.
     *
     * @throws AdextSDKException
     */
    public function validateMethod()
    {
        if (!$this->method) {
            throw new AdextSDKException('HTTP method not specified.');
        }
        if (!in_array($this->method, ['GET', 'POST', 'DELETE'])) {
            throw new AdextSDKException('Invalid HTTP method specified.');
        }
    }
    /**
     * Set the endpoint for this request.
     *
     * @param string
     *
     * @return AdextRequest
     *
     * @throws AdextSDKException
     */
    public function setEndpoint($endpoint)
    {
        // Harvest the access token from the endpoint to keep things in sync
        $params = AdextUrlManipulator::getParamsAsArray($endpoint);
        if (isset($params['access_token'])) {
            $this->setAccessTokenFromParams($params['access_token']);
        }
        // Clean the token & app secret proof from the endpoint.
        $filterParams = ['access_token', 'appsecret_proof'];
        $this->endpoint = AdextUrlManipulator::removeParamsFromUrl($endpoint, $filterParams);
        return $this;
    }
    /**
     * Return the endpoint for this request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        // For batch requests, this will be empty
        return $this->endpoint;
    }
    /**
     * Generate and return the headers for this request.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = static::getDefaultHeaders();
        if ($this->eTag) {
            $headers['If-None-Match'] = $this->eTag;
        }
        return array_merge($this->headers, $headers);
    }
    /**
     * Set the headers for this request.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }
    /**
     * Sets the eTag value.
     *
     * @param string $eTag
     */
    public function setETag($eTag)
    {
        $this->eTag = $eTag;
    }
    /**
     * Set the params for this request.
     *
     * @param array $params
     *
     * @return AdextRequest
     *
     * @throws AdextSDKException
     */
    public function setParams(array $params = [])
    {
        if (isset($params['access_token'])) {
            $this->setAccessTokenFromParams($params['access_token']);
        }
        // Don't let these buggers slip in.
        unset($params['access_token'], $params['appsecret_proof']);
        // @TODO Refactor code above with this
        //$params = $this->sanitizeAuthenticationParams($params);
        $this->dangerouslySetParams($params);
        return $this;
    }
    /**
     * Set the params for this request without filtering them first.
     *
     * @param array $params
     *
     * @return AdextRequest
     */
    public function dangerouslySetParams(array $params = [])
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
    /**
     * Returns the body of the request as URL-encoded.
     *
     * @return RequestBodyUrlEncoded
     */
    public function getUrlEncodedBody()
    {
        $params = $this->getPostParams();
        return new RequestBodyUrlEncoded($params);
    }
    /**
     * Generate and return the params for this request.
     *
     * @return array
     */
    public function getParams()
    {
        $params = $this->params;
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $params['access_token'] = $accessToken;
            $params['appsecret_proof'] = $this->getAppSecretProof();
        }
        return $params;
    }
    /**
     * Only return params on POST requests.
     *
     * @return array
     */
    public function getPostParams()
    {
        if ($this->getMethod() === 'POST') {
            return $this->getParams();
        }
        return [];
    }
    /**
     * The graph version used for this request.
     *
     * @return string
     */
    public function getGraphVersion()
    {
        return $this->graphVersion;
    }
    /**
     * Generate and return the URL for this request.
     *
     * @return string
     */
    public function getUrl()
    {
        $this->validateMethod();
        $graphVersion = AdextUrlManipulator::forceSlashPrefix($this->graphVersion);
        $endpoint = AdextUrlManipulator::forceSlashPrefix($this->getEndpoint());
        $url = $graphVersion . $endpoint;
        if ($this->getMethod() !== 'POST') {
            $params = $this->getParams();
            $url = AdextUrlManipulator::appendParamsToUrl($url, $params);
        }
        return $url;
    }
    /**
     * Return the default headers that every request should use.
     *
     * @return array
     */
    public static function getDefaultHeaders()
    {
        return [
            'User-Agent' => 'fb-php-' . Adext::VERSION,
            'Accept-Encoding' => '*',
        ];
    }
}