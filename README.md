# phpkafka-example
Script to show how kafka works

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
php index.php consumer --topic test77 --output stdout --wait
```
