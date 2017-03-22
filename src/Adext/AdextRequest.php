<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 12:31 PM
 */

namespace Adext;


use Adext\Exceptions\AdextResponseException;
use Adext\Exceptions\AdextSDKException;

class AdextRequest
{
    /**
     * @var int The HTTP status code response from Api.
     */
    protected $httpStatusCode;

    /**
     * @var array The headers returned from Api.
     */
    protected $headers;

    /**
     * @var string The raw body of the response from Api.
     */
    protected $body;

    /**
     * @var array The decoded body of the Api response.
     */
    protected $decodedBody = [];

    /**
     * @var AdextRequest The original request that returned this response.
     */
    protected $request;

    /**
     * @var AdextSDKException The exception thrown by this request.
     */
    protected $thrownException;

    /**
     * Creates a new Response entity.
     *
     * @param AdextRequest $request
     * @param string|null     $body
     * @param int|null        $httpStatusCode
     * @param array|null      $headers
     */
    public function __construct(AdextRequest $request, $body = null, $httpStatusCode = null, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;

        $this->decodeBody();
    }

    /**
     * Return the original request that returned this response.
     *
     * @return AdextRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return the AdextApp entity used for this response.
     *
     * @return AdextApp
     */
    public function getApp()
    {
        return $this->request->getApp();
    }

    /**
     * Return the access token that was used for this response.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->request->getAccessToken();
    }

    /**
     * Return the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Return the HTTP headers for this response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the raw body response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return the decoded body response.
     *
     * @return array
     */
    public function getDecodedBody()
    {
        return $this->decodedBody;
    }

    /**
     * Get the app secret proof that was used for this response.
     *
     * @return string|null
     */
    public function getAppSecretProof()
    {
        return $this->request->getAppSecretProof();
    }

    /**
     * Get the ETag associated with the response.
     *
     * @return string|null
     */
    public function getETag()
    {
        return isset($this->headers['ETag']) ? $this->headers['ETag'] : null;
    }

    /**
     * Get the version of Api that returned this response.
     *
     * @return string|null
     */
    public function getApiVersion()
    {
        return isset($this->headers['Adext-API-Version']) ? $this->headers['Adext-API-Version'] : null;
    }

    /**
     * Returns true if Api returned an error message.
     *
     * @return boolean
     */
    public function isError()
    {
        return isset($this->decodedBody['error']);
    }

    /**
     * Throws the exception.
     *
     * @throws AdextSDKException
     */
    public function throwException()
    {
        throw $this->thrownException;
    }

    /**
     * Instantiates an exception to be thrown later.
     */
    public function makeException()
    {
        $this->thrownException = AdextResponseException::create($this);
    }

    /**
     * Returns the exception that was thrown for this request.
     *
     * @return AdextSDKException|null
     */
    public function getThrownException()
    {
        return $this->thrownException;
    }

    /**
     * Convert the raw response into an array if possible.
     *
     * Api will return 2 types of responses:
     * - JSON(P)
     *    Most responses from Api are JSON(P)
     * - application/x-www-form-urlencoded key/value pairs
     *    Happens on the `/oauth/access_token` endpoint when exchanging
     *    a short-lived access token for a long-lived access token
     * - And sometimes nothing :/ but that'd be a bug.
     */
    public function decodeBody()
    {
        $this->decodedBody = json_decode($this->body, true);

        if ($this->decodedBody === null) {
            $this->decodedBody = [];
            parse_str($this->body, $this->decodedBody);
        } elseif (is_numeric($this->decodedBody)) {
            $this->decodedBody = ['id' => $this->decodedBody];
        }

        if (!is_array($this->decodedBody)) {
            $this->decodedBody = [];
        }

        if ($this->isError()) {
            $this->makeException();
        }
    }

    /**
     * Instantiate a new ApiNodes from response.
     *
     * @param string|null $subclassName The ApiNodes subclass to cast to.
     *
     * @return \Adext\ApiNodes\ApiNodes
     *
     * @throws AdextSDKException
     */
    public function getApiNode($subclassName = null)
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiNode($subclassName);
    }

    /**
     * Convenience method for creating a ApiAlbum collection.
     *
     * @return \Adext\ApiNodes\ApiAlbum
     *
     * @throws AdextSDKException
     */
    public function getApiAlbum()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiAlbum();
    }

    /**
     * Convenience method for creating a ApiPage collection.
     *
     * @return \Adext\ApiNodes\ApiPage
     *
     * @throws AdextSDKException
     */
    public function getApiPage()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiPage();
    }

    /**
     * Convenience method for creating a ApiSessionInfo collection.
     *
     * @return \Adext\ApiNodes\ApiSessionInfo
     *
     * @throws AdextSDKException
     */
    public function getApiSessionInfo()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiSessionInfo();
    }

    /**
     * Convenience method for creating a ApiUser collection.
     *
     * @return \Adext\ApiNodes\ApiUser
     *
     * @throws AdextSDKException
     */
    public function getApiUser()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiUser();
    }

    /**
     * Convenience method for creating a ApiEvent collection.
     *
     * @return \Adext\ApiNodes\ApiEvent
     *
     * @throws AdextSDKException
     */
    public function getApiEvent()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiEvent();
    }

    /**
     * Convenience method for creating a ApiGroup collection.
     *
     * @return \Adext\ApiNodes\ApiGroup
     *
     * @throws AdextSDKException
     */
    public function getApiGroup()
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiGroup();
    }

    /**
     * Instantiate a new ApiList from response.
     *
     * @param string|null $subclassName The ApiNodes subclass to cast list items to.
     * @param boolean     $auto_prefix  Toggle to auto-prefix the subclass name.
     *
     * @return \Adext\ApiNodes\ApiList
     *
     * @throws AdextSDKException
     *
     * @deprecated 5.0.0 getApiList() has been renamed to getApiEdge()
     * @todo v6: Remove this method
     */
    public function getApiList($subclassName = null, $auto_prefix = true)
    {
        return $this->getApiEdge($subclassName, $auto_prefix);
    }

    /**
     * Instantiate a new ApiEdge from response.
     *
     * @param string|null $subclassName The ApiNodes subclass to cast list items to.
     * @param boolean     $auto_prefix  Toggle to auto-prefix the subclass name.
     *
     * @return \Adext\ApiNodes\ApiEdge
     *
     * @throws AdextSDKException
     */
    public function getApiEdge($subclassName = null, $auto_prefix = true)
    {
        $factory = new ApiNodeFactory($this);

        return $factory->makeApiEdge($subclassName, $auto_prefix);
    }
}