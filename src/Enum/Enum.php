<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Enum;

use ReflectionClass;

/**
 * Class Enum
 * @package WildPHP\Core\Enum
 *
 * Based on code found in: https://stackoverflow.com/questions/254514/php-and-enumerations/254543#254543
 */
abstract class Enum
{
    /**
     * @var null|array
     */
    private static $cacheArray = null;

    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function toArray(): array
    {
        if (self::$cacheArray == null) {
            self::$cacheArray = [];
        }

        $calledClass = get_called_class();

        if (!array_key_exists($calledClass, self::$cacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$cacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$cacheArray[$calledClass];
    }
}