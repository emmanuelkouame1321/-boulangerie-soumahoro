# Architecture

## 1. Principe directeur

```
Requête HTTP
    ↓
Middleware (auth, permission, résolution du site)
    ↓
Livewire Component / Controller   ← mince : validation + appel + rendu
    ↓
Action / Service métier           ← toute la règle métier
    ↓
Model Eloquent                    ← données, relations, casts
    ↓
PostgreSQL / MySQL
```

Règle absolue (cahier §26 et §53) : **la logique métier n'est jamais
placée directement dans un contrôleur, un composant Livewire ou une vue.**

## 2. Découpage des responsabilités

| Couche | Emplacement | Responsabilité | Interdit |
|---|---|---|---|
| Contrôleur | `app/Http/Controllers` | Valider, appeler, rediriger | Calcul, requête métier |
| Composant Livewire | `app/Livewire/<Module>` | État de formulaire, appels, rendu | Règle métier |
| Service / Action | `app/Domain/<Module>` | Règles, transactions, idempotence | Rendu, HTML |
| Model | `app/Models` | Relations, casts, accesseurs | Règles métier complexes |
| Policy | `app/Policies` | Autorisation | Métier |
| Support | `app/Support` | Utilitaires transverses | Règles métier |
| Vue Blade | `resources/views` | Présentation | Requête, calcul métier |

## 3. Services à créer (liste de référence du cahier §26)

```
App\Domain\Inventory\StockMovementService    ← unique point d'écriture du stock
App\Domain\Inventory\StockService            ← stock disponible, seuils, alertes
App\Domain\Inventory\ExpiryService           ← péremption et pertes automatiques
App\Domain\Production\RecipeCalculator       ← besoins matières, rendement théorique
App\Domain\Production\ProductionService      ← ordre de production transactionnel
App\Domain\Purchasing\PurchaseOrderService
App\Domain\Purchasing\GoodsReceiptService    ← réception non rejouable
App\Domain\Sales\SalesService                ← ventes particuliers et grossistes
App\Domain\Sales\DayClosingService           ← clôture journalière, écarts
App\Domain\Customers\WholesalerRebateService ← ristournes par palier
App\Domain\Payroll\PayrollService
App\Domain\Reporting\ReportingService
```

## 3 bis. Convention Livewire

Livewire 4 génère par défaut des composants **mono-fichier** dans
`resources/views/components/`. Le projet utilise volontairement les
composants **classiques**, plus lisibles, plus testables avec
`Livewire::test()` et cohérents avec le découpage `app/` :

```bash
php artisan make:livewire <Module>/<Nom> --class
```

```
app/Livewire/<Module>/<Nom>.php            ← composant, sans logique métier
resources/views/livewire/<module>/<nom>.blade.php
```

Usage dans une vue : `<livewire:module.nom />`.

Un composant Livewire valide, appelle un service, affiche. Toute règle
métier reste dans `app/Domain/`.

## 4. Motif transactionnel obligatoire

Toute opération critique suit le schéma du cahier §27 :

```php
DB::transaction(function () use ($commande): void {
    // 1. Vérifier les disponibilités (et lever une erreur métier claire)
    // 2. Verrouiller les lignes concernées  -> lockForUpdate()
    // 3. Créer l'ordre et ses lignes
    // 4. Créer les mouvements de stock (sorties puis entrées)
    // 5. Créer les lots et calculer les dates limites
    // 6. Calculer les coûts
    // 7. Journaliser l'audit
});
```

En cas d'exception : rollback complet. Aucune opération partiellement
validée ne doit rester en base.

## 5. Idempotence

Les opérations sensibles reçoivent une clé d'idempotence
(`config('boulangerie.guards.require_idempotency_key')`).

Le motif : la clé est persistée en base avec un index unique. Une seconde
soumission avec la même clé détecte l'opération déjà traitée et retourne
le résultat existant au lieu de la rejouer.

Cela couvre : double clic, rafraîchissement, retry réseau, job rejoué.

## 6. Concurrence

- `lockForUpdate()` sur les lignes de stock dans toute transaction qui les
  modifie.
- Contrôle de disponibilité **dans** la transaction, jamais avant.
- Objectif : jamais de stock négatif sauf
  `config('boulangerie.guards.allow_negative_stock')` explicitement activé.

## 7. Multi-sites

- Chaque opération métier porte un `bakery_id`.
- Le site courant est résolu par `BakeryContext` (session) et posé par un
  middleware.
- Les requêtes de site sont filtrées par défaut par un scope global.
- Le Super-Admin désactive le scope pour obtenir la vision consolidée.
- Le changement de site est explicite et confirmé (Design System §12).

## 8. Événements et synchronisation (cahier §39)

```
GoodsReceiptValidated → StockInCreated → StockUpdated → LowStockAlertRecalculated
ProductionValidated   → RawMaterialsConsumed → FinishedGoodsCreated → BatchCreated
                      → ExpiryScheduled → StockUpdated
SaleValidated         → FinishedGoodsConsumed → StockUpdated → WholesalerStatisticsUpdated
BatchExpired          → RemainingQuantityDetected → LossCreated → StockUpdated
```

Les listeners lourds passent par la queue (`ShouldQueue`).

## 9. Erreurs métier

Créer des exceptions métier dédiées dans `app/Exceptions`, avec un message
utilisateur clair (cahier §41) :

```
Production impossible : il manque 3 sacs de farine.
Cette réception a déjà été intégrée au stock.
Ce lot ne peut plus être vendu car sa date limite est dépassée.
```

Jamais de stack trace ni de message technique affiché à l'utilisateur.

## 10. Performance (cahier §48)

- Pages principales < 2 s.
- Pagination obligatoire sur toutes les grandes listes.
- Index sur les colonnes de recherche et les clés étrangères fréquentes.
- Cache des données de configuration.
- Attention aux N+1 (`with()`, `withCount()`).
- Tâches lourdes en queue.

## 11. UI (Design System §91)

Ordre de construction imposé :

```
tokens → primitives → composants → patterns métier → layouts → pages → dashboards
```

Ne jamais dessiner un écran isolé avant que ses composants existent.

Charte : `BakeryOS – Prototype (charte caisse).html`. Tokens dans
`resources/css/app.css` (palette `brand` verte + `context` bleue,
Roboto, rayons 4–8 px). Composants partagés disponibles :

```
components/ui/       button, status-badge, stat-card, card, data-table,
                     search-input, progress, bar-chart, modal, toast,
                     alert, empty-state, form-field, select, icon
components/layout/   app-shell, sidebar, topbar, page-header
components/layouts/  app (gabarit applicatif), auth, pos (caisse plein écran)
```

Le module Caisse (`App\Livewire\Sales\PointOfSale`) est un composant
Livewire classique : il porte l'état du panier et appelle, à terme, un
service de `app/Domain/Sales/` pour toute règle de vente.
