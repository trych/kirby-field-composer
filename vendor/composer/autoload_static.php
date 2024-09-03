<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3253e95ee6771c970b26c90d5e179b09
{
    public static $prefixLengthsPsr4 = array (
        't' => 
        array (
            'trych\\FieldComposer\\' => 20,
        ),
        'K' => 
        array (
            'Kirby\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'trych\\FieldComposer\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
        'Kirby\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkirby/composer-installer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Kirby\\ComposerInstaller\\CmsInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/CmsInstaller.php',
        'Kirby\\ComposerInstaller\\Installer' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Installer.php',
        'Kirby\\ComposerInstaller\\Plugin' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/Plugin.php',
        'Kirby\\ComposerInstaller\\PluginInstaller' => __DIR__ . '/..' . '/getkirby/composer-installer/src/ComposerInstaller/PluginInstaller.php',
        'trych\\FieldComposer\\FieldComposer' => __DIR__ . '/../..' . '/classes/FieldComposer.php',
        'trych\\FieldComposer\\FieldMethods' => __DIR__ . '/../..' . '/classes/FieldMethods.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3253e95ee6771c970b26c90d5e179b09::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3253e95ee6771c970b26c90d5e179b09::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3253e95ee6771c970b26c90d5e179b09::$classMap;

        }, null, ClassLoader::class);
    }
}
