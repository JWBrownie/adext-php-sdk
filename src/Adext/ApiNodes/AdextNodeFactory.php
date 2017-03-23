<?php
/**
 * Created by PhpStorm.
 * User: Joe
 * Date: 3/22/17
 * Time: 1:46 PM
 */

namespace Adext\ApiNode;

use Adext\AdextResponse;
use Adext\Exceptions\AdextSDKException;
/**
 * Class AdextNodeFactory
 *
 * @package Adext
 *
 * ## Assumptions ##
 * AdextEdge - is ALWAYS a numeric array
 * AdextEdge - is ALWAYS an array of AdextNode types
 * AdextNode - is ALWAYS an associative array
 * AdextNode - MAY contain AdextNode's "recurrable"
 * AdextNode - MAY contain AdextEdge's "recurrable"
 * AdextNode - MAY contain DateTime's "primitives"
 * AdextNode - MAY contain string's "primitives"
 */
class AdextNodeFactory
{
    /**
     * @const string The base graph object class.
     */
    const BASE_NODE_CLASS = '\Adext\AdextNodes\AdextNode';
    /**
     * @const string The base graph edge class.
     */
    const BASE_EDGE_CLASS = '\Adext\AdextNodes\AdextEdge';
    /**
     * @const string The graph object prefix.
     */
    const BASE_OBJECT_PREFIX = '\Adext\AdextNodes\\';
    /**
     * @var AdextResponse The response entity from Adext.
     */
    protected $response;
    /**
     * @var array The decoded body of the AdextResponse entity from Adext.
     */
    protected $decodedBody;
    /**
     * Init this Adext object.
     *
     * @param AdextResponse $response The response entity from Adext.
     */
    public function __construct(AdextResponse $response)
    {
        $this->response = $response;
        $this->decodedBody = $response->getDecodedBody();
    }
    /**
     * Tries to convert a AdextResponse entity into a AdextNode.
     *
     * @param string|null $subclassName The AdextNode sub class to cast to.
     *
     * @return AdextNode
     *
     * @throws AdextSDKException
     */
    public function makeAdextNode($subclassName = null)
    {
        $this->validateResponseAsArray();
        $this->validateResponseCastableAsAdextNode();
        return $this->castAsAdextNodeOrAdextEdge($this->decodedBody, $subclassName);
    }
    /**
     * Convenience method for creating a AdextAchievement collection.
     *
     * @return AdextAchievement
     *
     * @throws AdextSDKException
     */
    public function makeAdextAchievement()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextAchievement');
    }
    /**
     * Convenience method for creating a AdextAlbum collection.
     *
     * @return AdextAlbum
     *
     * @throws AdextSDKException
     */
    public function makeAdextAlbum()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextAlbum');
    }
    /**
     * Convenience method for creating a AdextPage collection.
     *
     * @return AdextPage
     *
     * @throws AdextSDKException
     */
    public function makeAdextPage()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextPage');
    }
    /**
     * Convenience method for creating a AdextSessionInfo collection.
     *
     * @return AdextSessionInfo
     *
     * @throws AdextSDKException
     */
    public function makeAdextSessionInfo()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextSessionInfo');
    }
    /**
     * Convenience method for creating a AdextUser collection.
     *
     * @return AdextUser
     *
     * @throws AdextSDKException
     */
    public function makeAdextUser()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextUser');
    }
    /**
     * Convenience method for creating a AdextEvent collection.
     *
     * @return AdextEvent
     *
     * @throws AdextSDKException
     */
    public function makeAdextEvent()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextEvent');
    }
    /**
     * Convenience method for creating a AdextGroup collection.
     *
     * @return AdextGroup
     *
     * @throws AdextSDKException
     */
    public function makeAdextGroup()
    {
        return $this->makeAdextNode(static::BASE_OBJECT_PREFIX . 'AdextGroup');
    }
    /**
     * Tries to convert a AdextResponse entity into a AdextEdge.
     *
     * @param string|null $subclassName The AdextNode sub class to cast the list items to.
     * @param boolean     $auto_prefix  Toggle to auto-prefix the subclass name.
     *
     * @return AdextEdge
     *
     * @throws AdextSDKException
     */
    public function makeAdextEdge($subclassName = null, $auto_prefix = true)
    {
        $this->validateResponseAsArray();
        $this->validateResponseCastableAsAdextEdge();
        if ($subclassName && $auto_prefix) {
            $subclassName = static::BASE_OBJECT_PREFIX . $subclassName;
        }
        return $this->castAsAdextNodeOrAdextEdge($this->decodedBody, $subclassName);
    }
    /**
     * Validates the decoded body.
     *
     * @throws AdextSDKException
     */
    public function validateResponseAsArray()
    {
        if (!is_array($this->decodedBody)) {
            throw new AdextSDKException('Unable to get response from Adext as array.', 620);
        }
    }
    /**
     * Validates that the return data can be cast as a AdextNode.
     *
     * @throws AdextSDKException
     */
    public function validateResponseCastableAsAdextNode()
    {
        if (isset($this->decodedBody['data']) && static::isCastableAsAdextEdge($this->decodedBody['data'])) {
            throw new AdextSDKException(
                'Unable to convert response from Adext to a AdextNode because the response looks like a AdextEdge. Try using AdextNodeFactory::makeAdextEdge() instead.',
                620
            );
        }
    }
    /**
     * Validates that the return data can be cast as a AdextEdge.
     *
     * @throws AdextSDKException
     */
    public function validateResponseCastableAsAdextEdge()
    {
        if (!(isset($this->decodedBody['data']) && static::isCastableAsAdextEdge($this->decodedBody['data']))) {
            throw new AdextSDKException(
                'Unable to convert response from Adext to a AdextEdge because the response does not look like a AdextEdge. Try using AdextNodeFactory::makeAdextNode() instead.',
                620
            );
        }
    }
    /**
     * Safely instantiates a AdextNode of $subclassName.
     *
     * @param array       $data         The array of data to iterate over.
     * @param string|null $subclassName The subclass to cast this collection to.
     *
     * @return AdextNode
     *
     * @throws AdextSDKException
     */
    public function safelyMakeAdextNode(array $data, $subclassName = null)
    {
        $subclassName = $subclassName ?: static::BASE_NODE_CLASS;
        static::validateSubclass($subclassName);
        // Remember the parent node ID
        $parentNodeId = isset($data['id']) ? $data['id'] : null;
        $items = [];
        foreach ($data as $k => $v) {
            // Array means could be recurable
            if (is_array($v)) {
                // Detect any smart-casting from the $graphObjectMap array.
                // This is always empty on the AdextNode collection, but subclasses can define
                // their own array of smart-casting types.
                $graphObjectMap = $subclassName::getObjectMap();
                $objectSubClass = isset($graphObjectMap[$k])
                    ? $graphObjectMap[$k]
                    : null;
                // Could be a AdextEdge or AdextNode
                $items[$k] = $this->castAsAdextNodeOrAdextEdge($v, $objectSubClass, $k, $parentNodeId);
            } else {
                $items[$k] = $v;
            }
        }
        return new $subclassName($items);
    }
    /**
     * Takes an array of values and determines how to cast each node.
     *
     * @param array       $data         The array of data to iterate over.
     * @param string|null $subclassName The subclass to cast this collection to.
     * @param string|null $parentKey    The key of this data (Adext edge).
     * @param string|null $parentNodeId The parent Adext node ID.
     *
     * @return AdextNode|AdextEdge
     *
     * @throws AdextSDKException
     */
    public function castAsAdextNodeOrAdextEdge(array $data, $subclassName = null, $parentKey = null, $parentNodeId = null)
    {
        if (isset($data['data'])) {
            // Create AdextEdge
            if (static::isCastableAsAdextEdge($data['data'])) {
                return $this->safelyMakeAdextEdge($data, $subclassName, $parentKey, $parentNodeId);
            }
            // Sometimes Adext is a weirdo and returns a AdextNode under the "data" key
            $data = $data['data'];
        }
        // Create AdextNode
        return $this->safelyMakeAdextNode($data, $subclassName);
    }
    /**
     * Return an array of AdextNode's.
     *
     * @param array       $data         The array of data to iterate over.
     * @param string|null $subclassName The AdextNode subclass to cast each item in the list to.
     * @param string|null $parentKey    The key of this data (Adext edge).
     * @param string|null $parentNodeId The parent Adext node ID.
     *
     * @return AdextEdge
     *
     * @throws AdextSDKException
     */
    public function safelyMakeAdextEdge(array $data, $subclassName = null, $parentKey = null, $parentNodeId = null)
    {
        if (!isset($data['data'])) {
            throw new AdextSDKException('Cannot cast data to AdextEdge. Expected a "data" key.', 620);
        }
        $dataList = [];
        foreach ($data['data'] as $graphNode) {
            $dataList[] = $this->safelyMakeAdextNode($graphNode, $subclassName);
        }
        $metaData = $this->getMetaData($data);
        // We'll need to make an edge endpoint for this in case it's a AdextEdge (for cursor pagination)
        $parentAdextEdgeEndpoint = $parentNodeId && $parentKey ? '/' . $parentNodeId . '/' . $parentKey : null;
        $className = static::BASE_EDGE_CLASS;
        return new $className($this->response->getRequest(), $dataList, $metaData, $parentAdextEdgeEndpoint, $subclassName);
    }
    /**
     * Get the meta data from a list in a Adext response.
     *
     * @param array $data The Adext response.
     *
     * @return array
     */
    public function getMetaData(array $data)
    {
        unset($data['data']);
        return $data;
    }
    /**
     * Determines whether or not the data should be cast as a AdextEdge.
     *
     * @param array $data
     *
     * @return boolean
     */
    public static function isCastableAsAdextEdge(array $data)
    {
        if ($data === []) {
            return true;
        }
        // Checks for a sequential numeric array which would be a AdextEdge
        return array_keys($data) === range(0, count($data) - 1);
    }
    /**
     * Ensures that the subclass in question is valid.
     *
     * @param string $subclassName The AdextNode subclass to validate.
     *
     * @throws AdextSDKException
     */
    public static function validateSubclass($subclassName)
    {
        if ($subclassName == static::BASE_NODE_CLASS || is_subclass_of($subclassName, static::BASE_NODE_CLASS)) {
            return;
        }
        throw new AdextSDKException('The given subclass "' . $subclassName . '" is not valid. Cannot cast to an object that is not a AdextNode subclass.', 620);
    }
}