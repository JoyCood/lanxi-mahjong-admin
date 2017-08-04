<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit113b40025cde488a2779f2e286281be7
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MaxMind\\Db\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MaxMind\\Db\\' => 
        array (
            0 => __DIR__ . '/..' . '/maxmind-db/reader/src/MaxMind/Db',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit113b40025cde488a2779f2e286281be7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit113b40025cde488a2779f2e286281be7::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
