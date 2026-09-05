<?php

return [
    'company' => [
        'name' => env('TIKSHOP_COMPANY_NAME', 'Tik Shop'),
    ],

    'main_branch' => [
        'name' => env('TIKSHOP_MAIN_BRANCH_NAME', 'Tik Shop - Principal'),
    ],

    'initial_owner' => [
        'name' => env('TIKSHOP_OWNER_NAME', 'Super Administrador Tik Shop'),
        'email' => env('TIKSHOP_OWNER_EMAIL', 'admin@tikshop.local'),
        'password' => env('TIKSHOP_OWNER_PASSWORD'),
    ],
];
