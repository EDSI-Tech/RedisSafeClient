<?php
/**
 * (c) EDSI-Tech Sarl - All rights reserved.
 * This file cannot be copied and/or distributed without express permission of EDSI-Tech Sarl and all its content remains the property of EDSI-Tech Sarl.
 *
 * @author      Kevin ROBATEL <k.robatel@edsi-tech.com>
 * @author      Philippe BONVIN <p.bonvin@edsi-tech.com>
 * @version     1.2
 * @since       2015-03-31
 */

namespace EdsiTech\RedisSafeClientBundle;

use Predis\Client as PredisClient;
use Snc\RedisBundle\Client\Phpredis\Client as PhpRedisClient;

class SafeRedisClient
{

    /**
     * @var mixed
     */
    protected $redis;


    /**
     * @var array
     */
    static protected $localCache = [];


    public function __construct($redisClient)
    {
        
        if((!$redisClient instanceof PredisClient) && (!$redisClient instanceof PhpRedisClient)) {
            throw new \Exception("Invalid client type provided.");
        }
        
        $this->redis = $redisClient;
    }

    /**
     * @param string $key => key of the redis entry
     * @param mixed $default to return if not found
     * @param boolean $fromCache allow to take value from local cache (no call to redis)
     * @return null|mixed
     */
    public function get($key, $default = null, $fromCache = false)
    {
        try {
            $value = null;

            // From local cache
            if ($fromCache) {
                if (isset(self::$localCache[$key])) {
                    $value = self::$localCache[$key];
                }
            }

            // From redis cache
            if ($value == null) {
                $value = $this->redis->get($key);

                // Store into local cache
                if ($value != null)
                    self::$localCache[$key] = $value;
            }

            return $value !== null ? unserialize($value) : $default;
        } catch (\Exception $ex) {
            return $default;
        }
    }

    /**
     * @param string $key string
     * @param integer $expiration int in seconds
     * @param mixed $value
     * @return bool true if ok
     */
    public function setex($key, $expiration, $value)
    {
        $value = serialize($value);

        try {
            $this->redis->setex($key, $expiration, $value);
            return true;
        } catch (\Exception $ignored) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool true if ok
     */
    public function set($key, $value)
    {
        $value = serialize($value);

        try {
            $this->redis->set($key, $value);

            // add to local cache
            self::$localCache[$key] = $value;

            return true;
        } catch (\Exception $ignored) {
            return false;
        }
    }

    /**
     * @param string $key
     * @return bool true if exists
     */
    public function exists($key)
    {
        try {
            $value = $this->redis->exists($key);
            return (bool)$value;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param string $key
     * @return bool true if exists
     */
    public function del($key)
    {
        try {
            $value = $this->redis->del($key);
            return $value > 0;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @return bool true if ok
     */
    public function hset($key, $field, $value)
    {
        $value = serialize($value);

        try {
            $this->redis->hmset($key, $field, $value);
            return true;
        } catch (\Exception $ignored) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param integer $expiration in seconds
     * @return bool true if ok
     */
    public function expire($key, $expiration)
    {
        try {
            $this->redis->expire($key, $expiration);
            return true;
        } catch (\Exception $ignored) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed|null $default
     * @return null|mixed
     */
    public function hget($key, $field, $default = null)
    {
        try {
            $value = $this->redis->hget($key, $field);
            return $value !== null ? unserialize($value) : $default;
        } catch (\Exception $ex) {
            return $default;
        }
    }

    /**
     * @param string $key
     * @param string $field
     * @return bool true if exists
     */
    public function hexists($key, $field)
    {
        try {
            $value = $this->redis->hexists($key, $field);
            return (bool)$value;
        } catch (\Exception $ex) {
            return false;
        }
    }

}

