<?php

/*
|--------------------------------------------------------------------------
| Navigation — menu applicatif
|--------------------------------------------------------------------------
| Source unique du menu. Design System §10 et §52 : les groupes non
| autorisés ne sont jamais affichés. La permission est évaluée côté
| serveur par App\Support\Navigation.
*/

return [

    'groups' => [

        [
            'label' => null,
            'items' => [
                ['label' => 'Vue d’ensemble', 'path' => 'dashboard', 'icon' => 'home', 'permission' => null],
            ],
        ],

        [
            'label' => 'Opérations',
            'items' => [
                ['label' => 'Production', 'path' => 'production', 'icon' => 'factory', 'permission' => 'production.view'],
                ['label' => 'Stocks', 'path' => 'inventory', 'icon' => 'package', 'permission' => 'inventory.view'],
                ['label' => 'Achats', 'path' => 'purchasing/orders', 'icon' => 'shopping-cart', 'permission' => 'purchasing.view'],
                ['label' => 'Ventes', 'path' => 'sales', 'icon' => 'receipt', 'permission' => 'sales.view'],
            ],
        ],

        [
            'label' => 'Clients',
            'items' => [
                ['label' => 'Grossistes', 'path' => 'wholesalers', 'icon' => 'users', 'permission' => 'wholesaler.view'],
                ['label' => 'Particuliers', 'path' => 'customers', 'icon' => 'user-round', 'permission' => 'sales.view'],
            ],
        ],

        [
            'label' => 'Gestion',
            'items' => [
                ['label' => 'Pertes', 'path' => 'losses', 'icon' => 'trending-down', 'permission' => 'loss.view'],
                ['label' => 'Charges', 'path' => 'expenses', 'icon' => 'coins', 'permission' => 'expense.view'],
                ['label' => 'Employés', 'path' => 'employees', 'icon' => 'user-round', 'permission' => 'employee.view'],
                ['label' => 'Paie', 'path' => 'payroll', 'icon' => 'wallet', 'permission' => 'payroll.view'],
            ],
        ],

        [
            'label' => 'Analyse',
            'items' => [
                ['label' => 'Rapports', 'path' => 'reports', 'icon' => 'trending-up', 'permission' => 'report.view'],
            ],
        ],

        [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Boulangeries', 'path' => 'settings/bakeries', 'icon' => 'building-2', 'permission' => 'bakery.view'],
                ['label' => 'Utilisateurs', 'path' => 'settings/users', 'icon' => 'users', 'permission' => 'user.view'],
                ['label' => 'Paramètres', 'path' => 'settings', 'icon' => 'settings', 'permission' => 'settings.view'],
                ['label' => 'Audit', 'path' => 'settings/audit', 'icon' => 'clipboard-list', 'permission' => 'audit.view'],
            ],
        ],
    ],
];
