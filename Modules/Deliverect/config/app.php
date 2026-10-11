<?php


return [

    'providers' => [
        Berkayk\OneSignal\OneSignalServiceProvider::class
    ],

    'aliases' => [
        'OneSignal' => Berkayk\OneSignal\OneSignalFacade::class,
    ],
];
