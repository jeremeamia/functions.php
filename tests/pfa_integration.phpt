--TEST--
Can compose a new function using partial function applications and concatentations

--FILE--
<?php

require __DIR__ . '/../vendor/autoload.php';

$getNamesList = func_concat(
    func_apply('trim', FUNC_ARG_ANY, ', '),
    func_apply('explode', ','),
    func_map(func_concat('trim', 'ucwords'))
);

$input = ' marshal, barney, lily , robin, ted mosby,';
$output = $getNamesList($input);

print_r($output);

?>

--EXPECT--
Array
(
    [0] => Marshal
    [1] => Barney
    [2] => Lily
    [3] => Robin
    [4] => Ted Mosby
)
