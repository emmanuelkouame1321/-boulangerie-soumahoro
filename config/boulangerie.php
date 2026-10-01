<?php

/*
|--------------------------------------------------------------------------
| Configuration métier — Boulangerie Soumahoro
|--------------------------------------------------------------------------
|
| Principe d'architecture n° 7 du cahier des charges :
| « Les paramètres métier ne doivent pas être codés en dur. »
|
| Toute valeur métier par défaut vit ici. Les valeurs réellement
| opérationnelles sont administrables par le Super-Admin via le module
| Paramètres (tables de configuration). Ce fichier ne porte que les
| valeurs d'amorçage, les libellés et les listes fermées.
|
*/

return [

    'currency' => [
        'code' => 'XOF',
        'label' => 'FCFA',
        'decimals' => 0,
        'symbol_position' => 'suffix',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rôles et permissions (plan de contrôle)
    |--------------------------------------------------------------------------
    | Source de vérité unique des rôles applicatifs. Les seeders et les
    | Policies s'appuient dessus. Ne jamais énumérer un rôle ailleurs.
    */
    'roles' => [
        'super-admin' => [
            'label' => 'Super-Admin',
            'description' => 'Accès complet, vision consolidée multi-sites.',
            'permissions' => ['*'],
        ],
        'production-manager' => [
            'label' => 'Responsable production',
            'description' => 'Stocks, recettes, ordres de production, alertes.',
            'permissions' => [
                'inventory.view',
                'inventory.adjust',
                'recipe.view',
                'recipe.manage',
                'production.view',
                'production.create',
                'production.validate',
                'purchasing.view',
                'purchasing.create',
                'purchasing.manage',
                'goods-receipt.create',
                'supplier.view',
                'supplier.manage',
                'alert.view',
                'alert.acknowledge',
            ],
        ],
        'sales-manager' => [
            'label' => 'Responsable vente',
            'description' => 'Ventes, grossistes, particuliers, clôture, pertes.',
            'permissions' => [
                'finished-goods.view',
                'sales.view',
                'sales.create',
                'sales.close-day',
                'customer.view',
                'customer.manage',
                'wholesaler.view',
                'wholesaler.manage',
                'loss.view',
                'loss.create',
                'alert.view',
                'report.view-commercial',
            ],
        ],
        'cashier' => [
            'label' => 'Caissier',
            'description' => 'Caisse, ventes au comptoir, clients, mémo.',
            'permissions' => [
                'pos.open',
                'sales.view',
                'sales.create',
                'customer.view',
                'alert.view',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogue des permissions
    |--------------------------------------------------------------------------
    | Union de toutes les permissions déclarées par les rôles ci-dessus et
    | des permissions de module utilisées par la navigation et le middleware
    | de route. Le seeder crée cette liste en base : sans elle, une route
    | protégée par `permission:` échouerait faute de permission existante.
    | Le Super-Admin reste couvert par Gate::before (wildcard).
    */
    'permissions' => [
        'pos.open',
        'inventory.view',
        'inventory.adjust',
        'recipe.view',
        'recipe.manage',
        'production.view',
        'production.create',
        'production.validate',
        'purchasing.view',
        'purchasing.create',
        'purchasing.manage',
        'goods-receipt.create',
        'supplier.view',
        'supplier.manage',
        'sales.view',
        'sales.create',
        'sales.close-day',
        'customer.view',
        'customer.manage',
        'wholesaler.view',
        'wholesaler.manage',
        'rebate.view',
        'loss.view',
        'loss.create',
        'employee.view',
        'payroll.view',
        'expense.view',
        'finished-goods.view',
        'report.view',
        'report.view-commercial',
        'alert.view',
        'alert.acknowledge',
        'audit.view',
        'user.view',
        'user.manage',
        'role.view',
        'role.manage',
        'settings.view',
        'settings.manage',
        'bakery.view',
        'bakery.create',
        'bakery.update',
        'unit.view',
        'unit.manage',
        'product.view',
        'product.create',
        'product.update',
        'product.delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Compte d'amorçage local
    |--------------------------------------------------------------------------
    | Utilisé uniquement par SuperAdminSeeder en environnement local/testing.
    | Aucun compte n'est créé automatiquement en production.
    */
    'super_admin_seed' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@soumahoro.test'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Garde-fous métier
    |--------------------------------------------------------------------------
    */
    'guards' => [
        // Cahier §28 : jamais de stock négatif sauf permission administrative.
        'allow_negative_stock' => false,
        // Cahier §29 : idempotence des opérations critiques.
        'require_idempotency_key' => true,
        // Cahier §27 : toute opération critique est transactionnelle.
        'lock_stock_rows' => true,
        // Cahier §7.4 : un écart de rendement doit être motivé.
        'require_production_gap_reason' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Production (cahier §5 à §8)
    |--------------------------------------------------------------------------
    */
    'production' => [
        // Durée limite de vente par défaut, si ni la recette ni le produit
        // n'en déclarent (cahier §8.1).
        'default_shelf_life_days' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Clôture journalière (cahier §15)
    |--------------------------------------------------------------------------
    */
    'day_closing' => [
        // Écart absolu par produit, en unités principales, au-delà duquel
        // une observation (note) devient obligatoire. Une différence
        // importante « peut demander une justification » (cahier §15).
        // Mettre 0 pour exiger une observation dès le premier écart.
        'gap_justification_threshold' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | États métier (libellés + sémantique visuelle)
    |--------------------------------------------------------------------------
    | Le composant <x-ui.status-badge /> consomme exclusivement cette table.
    | Une couleur d'état n'est jamais utilisée seule : un libellé est
    | toujours affiché (Design System §18 et §53).
    */
    'statuses' => [
        'stock' => [
            'available' => ['label' => 'Disponible', 'tone' => 'success'],
            'low' => ['label' => 'Stock faible', 'tone' => 'warning'],
            'critical' => ['label' => 'Critique', 'tone' => 'danger'],
        ],
        'production' => [
            'draft' => ['label' => 'Brouillon', 'tone' => 'neutral'],
            'in_progress' => ['label' => 'En cours', 'tone' => 'info'],
            'pending' => ['label' => 'En attente', 'tone' => 'warning'],
            'validated' => ['label' => 'Validé', 'tone' => 'success'],
            'cancelled' => ['label' => 'Annulé', 'tone' => 'danger'],
        ],
        'order' => [
            'draft' => ['label' => 'Brouillon', 'tone' => 'neutral'],
            'pending' => ['label' => 'En attente', 'tone' => 'warning'],
            'ordered' => ['label' => 'Commandée', 'tone' => 'info'],
            'partially_received' => ['label' => 'Partiellement livrée', 'tone' => 'warning'],
            'received' => ['label' => 'Livrée', 'tone' => 'success'],
            'validated' => ['label' => 'Validée', 'tone' => 'success'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'danger'],
        ],
        'receipt' => [
            'draft' => ['label' => 'Brouillon', 'tone' => 'neutral'],
            'validated' => ['label' => 'Validée', 'tone' => 'success'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'danger'],
        ],
        'sale' => [
            'validated' => ['label' => 'Validée', 'tone' => 'success'],
            'cancelled' => ['label' => 'Annulée', 'tone' => 'danger'],
        ],
        'day_closing' => [
            'validated' => ['label' => 'Clôturée', 'tone' => 'success'],
        ],
        'bakery' => [
            'active' => ['label' => 'Active', 'tone' => 'success'],
            'inactive' => ['label' => 'Inactive', 'tone' => 'neutral'],
        ],
        'batch' => [
            'fresh' => ['label' => 'Frais', 'tone' => 'success'],
            'to_sell_soon' => ['label' => 'À écouler', 'tone' => 'warning'],
            'expired' => ['label' => 'Expiré', 'tone' => 'danger'],
            'depleted' => ['label' => 'Épuisé', 'tone' => 'neutral'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grand livre de stock (cahier §3.2)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Modes de déduction des ressources d'une recette (cahier §5.3)
    |--------------------------------------------------------------------------
    */
    'recipe_deduction_modes' => [
        'quantity' => 'Déduction de stock',
        'monetary' => 'Consommation monétaire',
        'cumulated' => 'Consommation cumulée',
        'manual' => 'Quantité réelle saisie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Motifs d'écart de production (cahier §7.4)
    |--------------------------------------------------------------------------
    */
    'production_gap_reasons' => [
        'production_issue' => 'Problème de production',
        'technical_loss' => 'Perte technique',
        'defect' => 'Défaut',
        'entry_error' => 'Erreur de saisie',
        'other' => 'Autre',
    ],

    'stock_movement_types' => [
        'supplier_in' => 'Entrée fournisseur',
        'production_out' => 'Sortie production',
        'production_in' => 'Entrée production',
        'sale_out' => 'Vente',
        'loss_out' => 'Perte',
        'adjustment_in' => 'Ajustement positif',
        'adjustment_out' => 'Ajustement négatif',
        'transfer_out' => 'Transfert sortant',
        'transfer_in' => 'Transfert entrant',
        'supplier_return' => 'Retour fournisseur',
        'customer_return' => 'Retour client',
        'admin_correction' => 'Correction administrative',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unités de mesure (cahier §4.3)
    |--------------------------------------------------------------------------
    */
    'units' => [
        'kg' => ['label' => 'Kilogramme', 'symbol' => 'kg', 'kind' => 'weight', 'position' => 10],
        'g' => ['label' => 'Gramme', 'symbol' => 'g', 'kind' => 'weight', 'position' => 20],
        'l' => ['label' => 'Litre', 'symbol' => 'L', 'kind' => 'volume', 'position' => 30],
        'ml' => ['label' => 'Millilitre', 'symbol' => 'ml', 'kind' => 'volume', 'position' => 40],
        'sack' => ['label' => 'Sac', 'symbol' => 'sac', 'kind' => 'container', 'position' => 50],
        'pack' => ['label' => 'Paquet', 'symbol' => 'paquet', 'kind' => 'container', 'position' => 60],
        'carton' => ['label' => 'Carton', 'symbol' => 'carton', 'kind' => 'container', 'position' => 70],
        'piece' => ['label' => 'Pièce', 'symbol' => 'pcs', 'kind' => 'count', 'position' => 80],
        'bundle' => ['label' => 'Fagot', 'symbol' => 'fagot', 'kind' => 'count', 'position' => 90],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversions d'unités amorcées
    |--------------------------------------------------------------------------
    | Exemple du cahier §4.3 : 1 sac de farine = 50 kg.
    | Administrables ensuite par le Super-Admin (Phase 1.3).
    */
    'unit_conversions' => [
        ['from' => 'sack', 'to' => 'kg', 'factor' => 50],
        ['from' => 'kg', 'to' => 'g', 'factor' => 1000],
        ['from' => 'l', 'to' => 'ml', 'factor' => 1000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catégories de produits (cahier §4.2)
    |--------------------------------------------------------------------------
    | Amorçage du catalogue. Administrables ensuite par le Super-Admin.
    */
    'product_categories' => [
        ['code' => 'bread', 'name' => 'Pains', 'position' => 10],
        ['code' => 'pastry', 'name' => 'Viennoiseries', 'position' => 20],
        ['code' => 'cake', 'name' => 'Pâtisserie', 'position' => 30],
        ['code' => 'sandwich', 'name' => 'Sandwichs', 'position' => 40],
        ['code' => 'drink', 'name' => 'Boissons', 'position' => 50],
        ['code' => 'raw', 'name' => 'Matières premières', 'position' => 60],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modes de paiement (cahier §14)
    |--------------------------------------------------------------------------
    */
    'payment_methods' => [
        'cash' => 'Espèces',
        'card' => 'Carte',
        'mobile_money' => 'Mobile Money',
        'transfer' => 'Virement',
        'credit' => 'À crédit',
        'other' => 'Autre',
    ],

    /*
    |--------------------------------------------------------------------------
    | Types de vente (cahier §12 et §14)
    |--------------------------------------------------------------------------
    */
    'sale_types' => [
        'retail' => 'Détail',
        'wholesale' => 'Grossiste',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertes (cahier §9.3 et §22)
    |--------------------------------------------------------------------------
    */
    'alert_modes' => [
        'stock_threshold' => 'Seuil de stock',
        'cumulated_threshold' => 'Seuil cumulé',
    ],

    'cumulation_periods' => [
        'day' => 'Journée',
        'week' => 'Semaine',
        'month' => 'Mois',
        'since_last_restock' => 'Depuis dernier réapprovisionnement',
        'custom' => 'Période personnalisée',
    ],
];
