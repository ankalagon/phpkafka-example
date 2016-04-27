# phpkafka-example
Script to show how kafka works.

Required to install [php-rdkafka](https://github.com/arnaud-lb/php-rdkafka)

Instalation
------------
```
git clone git@github.com:ankalagon/phpkafka-example.git
cd phpkafka-example
composer install
```

Usage
------------
```
usage: index.php <command> [<options>]

COMMANDS
  producer   Initialize producer
  consumer   Initialize consumer


$ php index.php producer --help
usage: index.php producer [<options>]

Initialize producer

OPTIONS
  --count      Number of messages to put to queue
  --help, -?   Display this help.
  --message    Message to put to queue
  --topic      Topic


$ php index.php consumer --help
usage: index.php consumer [<options>]

Initialize consumer

OPTIONS
  --file            Name of file to write output
  --frombeginning   Add --from-beginning flag to consumer
  --help, -?        Display this help.
  --output          Type of output - stdout, file, none
  --topic           Topic
  --wait            Wait for new messages, don't kill script after reading all
                    messages
```

Examples
-------------
```
php index.php producer --message 'message payload' --topic test_topic  --count 100000
php index.php consumer --topic test_topic --output stdout --wait
php index.php consumer --topic test_topic --output file --file /tmp/kafka-test.log
```
