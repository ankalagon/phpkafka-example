<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

use Garden\Cli\Cli;
use Garden\Cli\LogFormatter;

require 'vendor/autoload.php';

define('BROKER', '10.42.218.84,10.42.245.142');

$log = new LogFormatter();
$cli = new Cli();

$cli->description('Tool to test Kafka Brokers/Zookeeper from PHP')
    ->command('producer')
    ->description('Initialize producer')
    ->opt('message', 'Message to put to queue', true, 'string')
    ->opt('count', 'Number of messages to put to queue', false, 'integer')
    ->opt('topic', 'Topic', true, 'string')

    ->command('consumer')
    ->description('Initialize consumer')
    ->opt('output', 'Type of output - stdout, file, none', true, 'string')
    ->opt('file', 'Name of file to write output', false, 'string')
    ->opt('wait', 'Wait for new messages, don\'t kill script after reading all messages', false, 'boolean')
    ->opt('frombeginning', 'Add --from-beginning flag to consumer', false, 'string')
    ->opt('topic', 'Topic', true, 'string')
;

$args = $cli->parse($argv, true);

$command = $args->getCommand();


if ($command == 'producer') {
    $count = $args->getOpt('count', 1);

    $log->message('starting to produce messages to queue');

    $rk = new RdKafka\Producer();
    $rk->setLogLevel(LOG_DEBUG);
    $rk->addBrokers(BROKER);
    $topic = $rk->newTopic($args->getOpt('topic'));

    $log->begin('starting to insert messages to queue');
    for ($i = 0; $i < $count; $i++) {
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $args->getOpt('message').' '.$i);
    }
    $log->end();

    $log->success('inserted '.$count.' to topic '.$args->getOpt('topic'));
} elseif ($command == 'consumer') {
    $topic = $args->getOpt('topic');
    $output = $args->getOpt('output');
    $wait = $args->getOpt('wait', 0);
    $frombeginning = $args->getOpt('frombeginning', 0);

    if (in_array($output, ['stdout', 'file', 'none']) == false) {
        $log->error('Wrong type of output parameter, possible values: file, stdout');
        die();
    }

    $log->message('starting to consume messages from queue');

    $topicConf = new RdKafka\TopicConf();
    $topicConf->set("auto.commit.interval.ms", 1e3);
    $topicConf->set("auto.commit.enable", 1);
    $topicConf->set("offset.store.sync.interval.ms", 1e3);

    $rk = new RdKafka\Consumer();
    $rk->setLogLevel(LOG_DEBUG);
    $rk->addBrokers(BROKER);
    $consumer = $rk->newTopic($topic, $topicConf);

    $counter = 0;
    if ($frombeginning) {
        $log->message('--from-beginning flag active');
        $consumer->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);
    } else {
        $consumer->consumeStart(0, RD_KAFKA_OFFSET_END);
    }

    $tmpCount = 0;
    $log->begin('starting consuming messages from topic');
    while (true) {
        $msg = $consumer->consume(0, 1000);
        if ((is_bool($msg) || is_null($msg)) && $wait == false) {
            break;
        }

        if ((is_bool($msg) == false && is_null($msg) == false) &&
            $msg->err == RD_KAFKA_RESP_ERR_NO_ERROR) {
            $counter++;
            $tmpCount++;
            if ($output == 'stdout') {
                echo $msg->payload.PHP_EOL;
            } elseif ($output == 'file') {
                file_put_contents($args->getOpt('file'), $msg->payload.PHP_EOL, FILE_APPEND);
            }
        }

        if ((is_bool($msg) || is_null($msg)) && $wait && $tmpCount) {
            $log->success('retrieve '.$tmpCount.' messages from topic');
            $tmpCount = 0;
        }
    }
    $log->end();

    $log->message('end of messages in topic');
    $log->success('Consumed '.$counter.' messaged from topic '.$topic);


}