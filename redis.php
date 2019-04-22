<?php
class redisClass
{
    private $redis;

    public function __construct()
    {
        try {
            $this->redis = new Redis();
            $this->redis->open('127.0.0.1');
        } catch (\RedisException $e) {
            var_dump($e);
            die;
        }
    }

    public function set($openID)
    {
        return $this->redis->rpush('open-list', $openID);
    }

    public function get()
    {
        return $this->redis->lrange('open-list', 0, -1); 
    }
    
    public function __destruct()
    {
        $this->redis->close();
    }
}
