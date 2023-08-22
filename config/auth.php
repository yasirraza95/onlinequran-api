<?php 

return [
    'defaults' => [
        'guard' => 'user',
        'passwords' => 'users',
    ],
    
    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'local_sites_info',
        ],
        'user' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ]
    ],

    'providers' => [
        'local_sites_info' => [
            'driver' => 'eloquent',
            'model' => \App\Models\LocalSitesInfo::class
        ],
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ]
    ]
];