<?php

return [
    'municipalities' => [
        'tagoloan' => [
            'label' => 'Tagoloan',
            'code' => '104324000',
            'endpoint' => 'municipalities',
        ],
        'cagayan-de-oro-city' => [
            'label' => 'Cagayan de Oro City',
            'code' => '104305000',
            'endpoint' => 'cities',
        ],
    ],

    'default_puroks' => [
        'Purok 1',
        'Purok 2',
        'Purok 3',
        'Purok 4',
        'Purok 5',
    ],

    'puroks' => [
        'Tagoloan' => [
            'Poblacion' => ['Zone 1', 'Zone 2', 'Zone 3'],
        ],
        'Cagayan de Oro City' => [
            'Agusan' => ['Zone 1', 'Zone 2', 'Zone 3'],
        ],
    ],
];
