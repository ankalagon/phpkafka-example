<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

date_default_timezone_set('Europe/London');

use Garden\Cli\Cli;
use Garden\Cli\LogFormatter;

require 'vendor/autoload.php';

define('BROKER', '10.42.218.84,10.42.245.142');

$log = new LogFormatter();
$cli = new Cli();

$cli->description('Tool to test Kafka Brokers/Zookeeper from PHP')
    ->command('producer')
    ->description('Initialize producer')
    ->opt('credentials', 'IP of Kafka Broker ie. X.X.X.X:9092,Y.Y.Y.Y:6667, Ip of Redis server etc.', true, 'string')
    ->opt('message', 'Message to put to queue', true, 'string')
    ->opt('count', 'Number of messages to put to queue', false, 'integer')
    ->opt('vendor', 'Vendor of queue - kafka, redis', true, 'string')
    ->opt('topic', 'Topic', true, 'string')

    ->command('consumer')
    ->description('Initialize consumer')
    ->opt('credentials', 'IP of Kafka Broker ie. X.X.X.X:9092,Y.Y.Y.Y:6667, Ip of Redis server etc.', true, 'string')
    ->opt('output', 'Type of output - stdout, file, raw, none', true, 'string')
    ->opt('file', 'Name of file to write output', false, 'string')
    ->opt('wait', 'Wait for new messages, don\'t kill script after reading all messages', false, 'boolean')
    ->opt('frombeginning', 'Add --from-beginning flag to consumer', false, 'string')
    ->opt('vendor', 'Vendor of queue - kafka, redis', true, 'string')
    ->opt('topic', 'Topic', true, 'string');

$args = $cli->parse($argv, true);

$command = $args->getCommand();
$broker = $args->getOpt('broker');
$vendor = $args->getOpt('vendor');
$config = $args->getOpts();

$queue = new Queue($vendor, $config);

if ($command == 'producer') {
    $count = $args->getOpt('count', 1);

    $log->message('starting to produce messages to queue');



    $log->begin('starting to insert messages to queue');
    for ($i = 0; $i < $count; $i++) {
        $queue->push($args->getOpt('message').' '.$i);
    }
    $log->end();
    $log->success('inserted '.$count.' to topic '.$args->getOpt('topic'));
} elseif ($command == 'consumer') {
    $topic = $args->getOpt('topic');
    $output = $args->getOpt('output');
    $wait = $args->getOpt('wait', 0);
    $frombeginning = $args->getOpt('frombeginning', 0);

    if (in_array($output, ['stdout', 'file', 'raw', 'none']) == false) {
        $log->error('Wrong type of output parameter, possible values: file, stdout');
        die();
    }

    $log->message('starting to consume messages from queue');

    $counter = 0;
    $tmpCount = 0;
    $log->begin('starting consuming messages from topic');
    while (true) {
        $msg = $queue->pop();
        if ($msg !== false) {
            if ($output == 'stdout') {
                echo $msg.PHP_EOL;
            } elseif ($output == 'file') {
                file_put_contents($args->getOpt('file'), $msg.PHP_EOL, FILE_APPEND);
            } elseif ($output == 'raw') {
                print_r($msg);
            }

            $counter++;
            $tmpCount++;
        }

        if ($msg === false && $wait && $tmpCount) {
            $log->success('retrieve '.$tmpCount.' messages from topic');
            $tmpCount = 0;
        }

        if ($msg === false && $wait == false) {
            break;
        }
    }
    $log->end();

    $log->message('end of messages in topic');
    $log->success('Consumed '.$counter.' messaged from topic '.$topic);
}

class Queue
{
    private $_vendors = ['kafka', 'redis'];
    private $_engine = false;

    public function __construct($vendor, $config)
    {
        if (in_array($vendor, $this->_vendors) == false) {
            throw new \Exception('Invalid queue vendor, availables vendors: '.implode(' ', $this->_vendors));
        }

        $vendor = 'Engine\\'. ucfirst($vendor);
        $this->_engine = new $vendor($config);
    }

    public function push($message)
    {
        return $this->_engine->push($message);
    }

    public function pop()
    {
        return $this->_engine->pop();
    }
}