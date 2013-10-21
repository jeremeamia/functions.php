--TEST--
Can instantiate a class with class_new_args using multiple parameter counts

--FILE--
<?php

require __DIR__ . '/../vendor/autoload.php';

class Test
{
    public function __construct()
    {
        echo func_num_args();
    }
}

for ($i = 0; $i < 6; $i++) {
    $args = $i ? array_fill(0, $i, 1) : array();
    $object = class_new_args('Test', $args);
}

echo PHP_EOL . get_class($object);

?>

--EXPECT--
012345
Test
