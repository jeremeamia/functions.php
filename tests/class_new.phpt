--TEST--
Can instantiate a class with class_new

--FILE--
<?php

require __DIR__ . '/../vendor/autoload.php';

$prefix = 'Spl';
$class = 'Queue';
$instance = class_new($prefix . $class);
echo get_class($instance);

?>

--EXPECT--
SplQueue
