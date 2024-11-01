<?php
/**
 * @license LGPL-3.0-or-later
 *
 * Modified by __root__ on 28-March-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

/**
 * PSR-4 autoloader implementation for the DeviceDetector namespace.
 * First we define the 'dd_autoload' function, and then we register
 * it with 'spl_autoload_register' so that PHP knows to use it.
 */

/**
 * Automatically include the file that defines <code>class</code>.
 *
 * @param string $class
 *     the name of the class to load
 *
 * @return void
 */
function dd_autoload(string $class): void
{
    if (false === strpos($class, 'Blucube\\SWSTATS\\DeviceDetector\\')) {
        return;
    }

    $namespaceMap = ['Blucube\\SWSTATS\\DeviceDetector\\' => __DIR__ . '/'];

    foreach ($namespaceMap as $prefix => $dir) {
        /* First swap out the namespace prefix with a directory... */
        $path = str_replace($prefix, $dir, $class);
        /* replace the namespace separator with a directory separator... */
        $path = str_replace('\\', '/', $path);
        /* and finally, add the PHP file extension to the result. */
        $path .= '.php';
        /* $path should now contain the path to a PHP file defining $class */
        @include $path;
    }
}

spl_autoload_register('dd_autoload');
