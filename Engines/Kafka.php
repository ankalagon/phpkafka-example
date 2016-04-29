<?php

namespace Engine;

class Kafka {

    public function __construct($config)
    {
        $rk = new \RdKafka\Producer();
        $rk->addBrokers($config['credentials']);
        $this->producer = $rk->newTopic($config['topic']);

        $rk = new \RdKafka\Consumer();
        $rk->addBrokers($config['credentials']);
        $this->consumer = $rk->newTopic($config['topic']);

        if (!empty($config['frombeginning'])) {
            $this->consumer->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);
        } else {
            $this->consumer->consumeStart(0, RD_KAFKA_OFFSET_END);
        }
    }

    public function push($message)
    {
        return $this->producer->produce(RD_KAFKA_PARTITION_UA, 0, $message);
    }

    public function pop()
    {
        $message = $this->consumer->consume(0, 1000);
        if ((is_bool($message) || is_null($message)) || $message->err != 0) {
            return false;
        }

        $this->error = $message->err;
        return $message->payload;
    }

    public function wasError()
    {
        if ($this->error == RD_KAFKA_RESP_ERR_NO_ERROR) {
            return false;
        }

        return true;
    }

}