<?php

namespace FM\Cache;

use Predis\Client;

/**
 * @deprecated The financial-media/cache library is deprecated. Use treehouselabs/cache instead
 */
class PredisCache implements CacheInterface
{
    /**
     * @var Client
     */
    protected $predis;

    /**
     * Extra in-memory cache to prevent double calls to Redis
     */
    protected $localCache = array();

    /**
     * @param Client $predis
     */
    public function __construct(Client $predis)
    {
        trigger_error('The financial-media/cache library is deprecated. Use treehouselabs/cache instead', E_USER_DEPRECATED);

        $this->predis = $predis;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return array_key_exists($key, $this->localCache) || $this->predis->exists($key);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->localCache)) {
            $this->localCache[$key] = json_decode($this->predis->get($key), true);
        }

        return $this->localCache[$key];
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = 0)
    {
        $res = $this->predis->set($key, json_encode($value));
        if ($ttl > 0) {
            $this->predis->expire($key, $ttl);
        }

        $this->localCache[$key] = $value;

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        $res = $this->predis->del($key);

        if ($res && array_key_exists($key, $this->localCache)) {
            unset($this->localCache[$key]);
        }

        return (boolean) $res;
    }

    public function clear()
    {
        $res = $this->predis->flushdb();

        if ($res) {
            $this->localCache = array();
        }

        return $res;
    }

    public function appendToList($list, $value)
    {
        return $this->predis->sadd($list, $value);
    }

    public function getListItems($list)
    {
        return $this->predis->smembers($list);
    }

    public function removeFromList($list, $value)
    {
        return $this->predis->srem($list, $value);
    }
}
