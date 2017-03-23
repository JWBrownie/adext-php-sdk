<?php
namespace Adext\PersistentData;

/**
 * Class AdextMemoryPersistentDataHandler
 *
 * @package Adext
 */
class AdextMemoryPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var array The session data to keep in memory.
     */
    protected $sessionData = [];

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return isset($this->sessionData[$key]) ? $this->sessionData[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        $this->sessionData[$key] = $value;
    }
}
