<?php

/*
|--------------------------------------------------------------------------
| Navigation — menu applicatif
|--------------------------------------------------------------------------
| Structure reprise de « BakeryOS – Prototype (charte caisse).html ».
| Les groupes et entrées non autorisés ne sont jamais affichés : la
| permission est évaluée côté serveur par App\Support\Navigation, et une
| entrée dont la route n'existe pas encore est masquée (pas de lien mort).
*/

return [

    'groups' => [

        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'path' => 'dashboard', 'icon' => 'layout-dashboard', 'permission' => null],
            ],
        ],

        [
            'label' => 'Opérations',
            'items' => [
                ['label' => 'Production', 'path' => 'production', 'icon' => 'factory', 'permission' => 'production.view'],
                ['label' => 'Stocks', 'path' => 'inventory', 'icon' => 'boxes', 'permission' => 'inventory.view'],
                ['label' => 'Produits', 'path' => 'products', 'icon' => 'package', 'permission' => 'product.view'],
                ['label' => 'Achats', 'path' => 'purchasing/orders', 'icon' => 'shopping-cart', 'permission' => 'purchasing.view'],
                ['label' => 'Ventes', 'path' => 'sales', 'icon' => 'receipt', 'permission' => 'sales.view'],
                ['label' => 'Pertes', 'path' => 'losses', 'icon' => 'trash-2', 'permission' => 'loss.view'],
            ],
        ],

        [
            'label' => 'Commercial',
            'items' => [
                ['label' => 'Clients', 'path' => 'customers', 'icon' => 'users', 'permission' => 'customer.view'],
                ['label' => 'Grossistes', 'path' => 'wholesalers', 'icon' => 'store', 'permission' => 'wholesaler.view'],
                ['label' => 'Ristournes', 'path' => 'rebates', 'icon' => 'badge-percent', 'permission' => 'rebate.view'],
            ],
        ],

        [
            'label' => 'Ressources',
            'items' => [
                ['label' => 'Fournisseurs', 'path' => 'suppliers', 'icon' => 'truck', 'permission' => 'supplier.view'],
                ['label' => 'Employés', 'path' => 'employees', 'icon' => 'user-round', 'permission' => 'employee.view'],
                ['label' => 'Paie', 'path' => 'payroll', 'icon' => 'wallet-cards', 'permission' => 'payroll.view'],
                ['label' => 'Charges', 'path' => 'expenses', 'icon' => 'receipt-text', 'permission' => 'expense.view'],
            ],
        ],

        [
            'label' => 'Analyse',
            'items' => [
                ['label' => 'Rapports', 'path' => 'reports', 'icon' => 'chart-no-axes-combined', 'permission' => 'report.view'],
                ['label' => 'Notifications', 'path' => 'notifications', 'icon' => 'bell', 'permission' => 'alert.view'],
                ['label' => 'Audit', 'path' => 'settings/audit', 'icon' => 'file-clock', 'permission' => 'audit.view'],
            ],
        ],

        [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Utilisateurs', 'path' => 'settings/users', 'icon' => 'users-round', 'permission' => 'user.view'],
                ['label' => 'Rôles & permissions', 'path' => 'settings/roles', 'icon' => 'shield-check', 'permission' => 'role.view'],
                ['label' => 'Boulangeries', 'path' => 'settings/bakeries', 'icon' => 'building-2', 'permission' => 'bakery.view'],
                ['label' => 'Unités', 'path' => 'settings/units', 'icon' => 'scale', 'permission' => 'unit.view'],
                ['label' => 'Paramètres', 'path' => 'settings', 'icon' => 'settings', 'permission' => 'settings.view'],
            ],
        ],
    ],
];
