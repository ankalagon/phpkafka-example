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
php index.php 
usage: index.php <command> [<options>]

COMMANDS
  producer   Initialize producer
  consumer   Initialize consumer
```

Examples
-------------
```
php index.php producer --message 'message payload' --topic test_topic  --count 100000
php index.php consumer --topic test_topic --output stdout --wait
php index.php consumer --topic test_topic --output file --file /tmp/kafka-test.log
```
