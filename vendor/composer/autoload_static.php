<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3ed46ff09931a8a710f4e1b70ad510a8
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Pingpp\\' => 7,
        ),
        'A' => 
        array (
            'Application\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Pingpp\\' => 
        array (
            0 => __DIR__ . '/..' . '/pingplusplus/pingpp-php/lib',
        ),
        'Application\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3ed46ff09931a8a710f4e1b70ad510a8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3ed46ff09931a8a710f4e1b70ad510a8::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
