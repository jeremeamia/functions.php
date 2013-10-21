<?php

///////////////////////////////////////////////////////////////////////////////
// CLASS FUNCTIONS
///////////////////////////////////////////////////////////////////////////////

if (!function_exists('class_new')) {
    /**
     * Instantiates a class by its fully qualified class name (FQCN) and
     * accepts a variable number of arguments. Comparable to call_user_func but
     * for classes
     *
     *     $object = class_new(__NAMESPACE . '\\Contact', 'John', 'Smith');
     *
     * @param string $fqcn The fully qualified class name to instantiate
     *
     * @return object
     */
    function class_new($fqcn)
    {
        $args = func_get_args();
        array_shift($args);

        return class_new_args($fqcn, $args);
    }
}

if (!function_exists('class_new_args')) {
    /**
     * Instantiates a class by its fully qualified class name (FQCN) and
     * accepts an array of arguments. Comparable to call_user_func_array but
     * for classes
     *
     *     $args = array('John', 'Smith');
     *     $object = class_new_args(__NAMESPACE . '\\Contact', $args);
     *
     * @param string $fqcn The fully qualified class name to instantiate
     * @param array  $args An array of args to pass to the class's constructor
     *
     * @return object
     */
    function class_new_args($fqcn, array $args)
    {
        if (($numArgs = count($args)) === 0) {
            return new $fqcn;
        } elseif ($numArgs === 1) {
            return new $fqcn($args[0]);
        } elseif ($numArgs === 2) {
            return new $fqcn($args[0], $args[1]);
        } elseif ($numArgs === 3) {
            return new $fqcn($args[0], $args[1], $args[2]);
        } elseif ($numArgs === 4) {
            return new $fqcn($args[0], $args[1], $args[2], $args[3]);
        } else {
            $class = new \ReflectionClass($fqcn);
            return $class->newInstanceArgs($args);
        }
    }
}

///////////////////////////////////////////////////////////////////////////////
// FUNC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////

if (!defined('FUNC_ARG_ANY')) {
    // A constant representing an argument to leave a placeholder for when
    // creating a partial function application
    define('FUNC_ARG_ANY', "\0*\0");
}

if (!function_exists('func_apply')) {
    /**
     * Creates a partial function application of the provided callable
     *
     *     $divideOnComma = func_apply('explode', ',', FUNC_ARG_ANY, 2);
     *     $list = '1,2,3,4';
     *     print_r($divideOnComma($list));
     *     #> Array
     *     #> (
     *     #>     [0] => 1
     *     #>     [1] => 2,3,4
     *     #> )
     *
     * @param callable $callable
     *
     * @return callable
     * @throws InvalidArgumentException if the argument is not callable
     */
    function func_apply($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException();
        }

        $fixedArgs = func_get_args();
        array_shift($fixedArgs);
        return function () use ($callable, $fixedArgs) {
            $args = func_get_args();
            foreach ($fixedArgs as $index => $value) {
                if ($value !== FUNC_ARG_ANY && is_int($index)) {
                    array_splice($args, $index, 0, array($value));
                }
            }

            return call_user_func_array($callable, $args);
        };
    }
}

if (!function_exists('func_map')) {
    /**
     * Creates a function from another callable that accepts an array as input
     * and applies the original callback to every item in the array. It's
     * essentially a partially applied array_map function
     *
     *     $capitalizeList = func_map('ucfirst');
     *     $names = array('john', 'mary', 'nick');
     *     print_r($capitalizeList($names));
     *     #> Array
     *     #> (
     *     #>     [0] => John
     *     #>     [1] => Mary
     *     #>     [2] => Nick
     *     #> )
     *
     * @param callable $callable
     *
     * @return callable
     * @throws InvalidArgumentException if the argument is not callable
     */
    function func_map($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException();
        }

        return function (array $input) use ($callable) {
            return array_map($callable, $input);
        };
    }
}

if (!function_exists('func_reduce')) {
    /**
     * Creates a function from another callable that accepts an array as input
     * and applies the original callback to every item in the array as reducer.
     * It's essentially a partially applied array_reduce function
     *
     *     $concatList = func_reduce(function ($a, $b) { return $a . $b; });
     *     $words = array('a', 'b', 'c', 'd', 'e');
     *     echo $concatList($words);
     *     #> abcde
     *
     * @param callable $callable
     * @param null     $defaultInitial
     *
     * @return callable
     * @throws InvalidArgumentException if the argument is not callable
     */
    function func_reduce($callable, $defaultInitial = null)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException();
        }

        return function (array $input, $userInitial = null) use (
            $callable,
            $defaultInitial
        ) {
            return array_reduce(
                $input,
                $callable,
                $userInitial ?: $defaultInitial);
        };
    }
}

if (!function_exists('func_concat')) {
    /**
     * Combines or concatenates two callables into a new function so that when
     * input is provided, it will call the first function on the input and pass
     * the result into the second function
     *
     *     $slugify = func_concat('strtolower', func_apply('strtr', ' ', '-'));
     *     $title = 'There Be Dragons Here';
     *     echo $slugify($title);
     *     #> there-be-dragons-here
     *
     * @return callable
     */
    function func_concat()
    {
        return array_reduce(func_get_args(), function ($func1, $func2) {
            if (!is_callable($func1) || !is_callable($func2)) {
                throw new InvalidArgumentException();
            }

            return function ($value) use ($func1, $func2) {
                return $func2($func1($value));
            };
        }, function ($value) {
            return $value;
        });
    }
}

if (!function_exists('func_reflect')) {
    /**
     * Creates a reflection of any callable function
     *
     *     $reflected1 = func_reflect('Closure', 'bind');
     *     $reflected2 = func_reflect(array('Closure', 'bind'));
     *     $reflected3 = func_reflect('Closure::bind'));
     *     $reflected4 = func_reflect('strtolower');
     *     $reflected5 = func_reflect(function ($foo) { return $foo; });
     *
     * @param string|callable $function
     * @param string          $methodName
     *
     * @return ReflectionFunction|ReflectionMethod
     */
    function func_reflect($function, $methodName = null)
    {
        if (is_string($methodName)) {
            $className = $function;
        } elseif (is_string($function) && strpos($function, '::')) {
            list($className, $methodName) = explode('::', $function, 2);
        } elseif (is_array($function)) {
            list($className, $methodName) = $function;
        }

        return isset($className)
            ? new \ReflectionMethod($className, $methodName)
            : new \ReflectionFunction($function);
    }
}

if (!function_exists('func_arity')) {
    /**
     * Determines the arity (number of parameters) of a callable function
     *
     *     echo func_arity('explode');
     *     #> 3
     *     echo func_arity(function ($foo, $bar) {});
     *     #> 2
     *
     * @param callable $function
     *
     * @return int
     */
    function func_arity($function)
    {
        return func_reflect($function)->getNumberOfParameters();
    }
}

///////////////////////////////////////////////////////////////////////////////
// ARRAY FUNCTIONS
///////////////////////////////////////////////////////////////////////////////

if (!function_exists('arrayval')) {
    /**
     * Coerces a value into an array
     *
     *     $value = new ArrayObject(array('a', 'b', 'c')):
     *     print_r(arrayval($value));
     *     #> Array
     *     #> (
     *     #>     [0] => a
     *     #>     [1] => b
     *     #>     [2] => c
     *     #> )
     *
     *     $value = new stcClass;
     *     $value->foo = 'bar';
     *     $value->fizz = 'buzz';
     *     print_r(arrayval($value, true));
     *     #> Array
     *     #> (
     *     #>     [foo] => bar
     *     #>     [fizz] => buzz
     *     #> )
     *
     * @param mixed      $var          A value to be converted to an array
     * @param bool|array $preserveKeys Whether or not to preserve the keys of
     *                                 the keys of the array, or, if an array
     *                                 of keys is specified, only those will
     *                                 be preserved
     *
     * @return array
     * @throws InvalidArgumentException if value can't be coerced into an array
     */
    function arrayval($var, $preserveKeys = false)
    {
        if (is_array($var)) {
            // Use $var as is
        } elseif ($var instanceof \ArrayObject) {
            $var = $var->getArrayCopy();
        } elseif ($var instanceof \Traversable) {
            $var = iterator_to_array($var, (bool) $preserveKeys);
        } elseif (is_array($preserveKeys) && $var instanceof \ArrayAccess) {
            // Use $var as is
        } elseif (is_object($var)) {
            $var = get_object_vars($var);
        } elseif (is_scalar($var)) {
            $var = (array) $var;
        } else {
            throw new \InvalidArgumentException(
                'The provided variable could not be coerced into an array.'
            );
        }

        if (is_array($preserveKeys)) {
            $hash = array();
            foreach ($preserveKeys as $key) {
                if (isset($var[$key])) {
                    $hash[$key] = $var[$key];
                }
            }
            $var = $hash;
        } elseif (!$preserveKeys) {
            $var = array_values($var);
        }

        return $var;
    }
}
