<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd40028f524d575d4003b33a40d9e4e90
{
    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'RetailCrm\\' => 
            array (
                0 => __DIR__ . '/..' . '/retailcrm/api-client-php/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInitd40028f524d575d4003b33a40d9e4e90::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}