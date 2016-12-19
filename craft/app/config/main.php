<?php

$config = [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0',
    'build' => '2948',
    'schemaVersion' => '3.0.15',
    'releaseDate' => '1475184141',
    'minBuildRequired' => '2788',
    'minBuildUrl' => 'https://download.craftcdn.com/craft/2.6/2.6.2788/Craft-2.6.2788.zip',
    'track' => 'dev',
    'basePath' => '@craft/app',          // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\app\controllers',
];

return $config;
