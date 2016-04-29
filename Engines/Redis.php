<?php

namespace Engine;

class Redis {

    public function __construct($config)
    {
        $this->redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => $config['credentials'],
            'port'   => 6379,
        ]);

        $this->queueName = $config['topic'];
    }

    public function push($message)
    {
        return $this->redis->lpush($this->queueName, $message);
    }

    public function pop()
    {
        $message = $this->redis->lpop($this->queueName);

        if (is_null($message)) {
            return false;
        }

        return $message;
    }
}