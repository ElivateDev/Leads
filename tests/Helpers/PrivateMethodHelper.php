<?php

namespace Tests\Helpers;

use ReflectionClass;

trait PrivateMethodHelper
{
    /**
     * Call a private or protected method on an object
     */
    protected function callPrivateMethod($object, string $methodName, ...$args)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object, ...$args);
    }
}
