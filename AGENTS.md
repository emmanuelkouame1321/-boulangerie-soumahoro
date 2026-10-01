# AGENTS.md — Manuel d'exploitation pour agents de codage

> **Lis ce fichier en entier avant d'écrire la moindre ligne de code.**
> Ce dépôt est conçu pour être développé de façon **autonome, phase par
> phase**. Chaque session d'agent doit pouvoir reprendre le travail sans
> contexte préalable.

---

## 1. Le projet en une phrase

ERP métier multi-sites pour **Boulangerie Soumahoro** : achats → stock
matières → recettes → production → stock produits finis/lots → ventes →
restes/pertes → statistiques → grossistes/ristournes, avec utilisateurs,
rôles, permissions, sites, fournisseurs, employés, paie, charges, audit
et reporting.

---

## 2. Sources de vérité (ordre de priorité absolu)

| Priorité | Fichier | Portée |
|---|---|---|
| 1 | `cahier_des_charges_gestion_boulangerie.md` | Règles métier, permissions, données, workflows, architecture |
| 2 | `BakeryOS – Prototype (charte caisse).html` | Charte visuelle de référence : tokens, composants, écrans, caisse |
| 3 | `docs/CHARTE.md` | Contrat visuel : tokens, composants, règles vérifiées automatiquement |
| 4 | `ROADMAP.md` | Ordre d'exécution des travaux |
| 5 | `docs/ARCHITECTURE.md` | Règles de code et de découpage |
| 6 | `docs/DECISIONS.md` | Décisions prises et questions métier ouvertes |

**Le prototype n'est pas la source de vérité métier.** Il matérialise des
parcours ; les règles viennent du cahier des charges.

En cas de contradiction : cahier > design system > prototype.

---

## 3. Stack figée (ne pas changer sans décision explicite)

- **PHP 8.2+** / **Laravel 12.x**
- **Blade + Livewire 4** (pas d'Inertia, pas de SPA)
- **Tailwind CSS 4** (tokens dans `resources/css/app.css`, `@theme`)
- **MySQL / MariaDB** en développement (XAMPP), PostgreSQL visé en production
- **spatie/laravel-permission 6** pour rôles et permissions
- **PHPUnit 11** pour les tests
- **Larastan 3** (PHPStan) pour l'analyse statique
- **Laravel Pint** pour le style de code

---

## 4. Commandes

```bash
composer setup          # install complet : deps, .env, clé, migrate --seed, build
composer dev            # serveur + queue + logs + vite en parallèle
composer test           # configuration + suite de tests
composer lint           # Pint en mode vérification (CI)
composer format         # Pint en mode correction
composer analyse        # Larastan
composer charte         # vérifie que les vues respectent la charte (tokens)
composer ci             # analyse + lint + charte + tests (à lancer avant de conclure)

php artisan migrate --seed   # après chaque nouvelle migration
npm run build                # assets de production
npm run dev                  # vite en watch
```

---

## 5. Boucle de travail obligatoire

Pour **chaque** tâche de la ROADMAP :

1. **Lire** la phase active dans `ROADMAP.md` et cocher son identifiant.
2. **Vérifier** la section correspondante du cahier des charges.
   Ne jamais inventer une règle métier.
3. **Écrire la migration** si le modèle de données change (jamais de
   modification destructive d'une table existante).
4. **Écrire le service métier** dans `app/Domain/<Module>/` — pas dans le
   contrôleur, pas dans le composant Livewire.
5. **Écrire les tests** :
   - unitaire pour tout calcul (recettes, rendement, stock, ristourne,
     perte, coût) ;
   - fonctionnel pour tout parcours (achat, réception, production, vente,
     perte, transfert, permissions).
6. **Construire l'UI** dans l'ordre imposé par le Design System §91 :
   tokens → primitives → composants → patterns métier → layouts → pages.
7. **Lancer `composer ci`** et corriger jusqu'au vert.
8. **Mettre à jour** `ROADMAP.md` (case cochée) et `docs/DECISIONS.md` si
   une décision structurante a été prise.
9. **Passer à la tâche suivante.**

Ne jamais livrer un écran sans son test de rendu. Ne jamais livrer un
service sans son test de calcul.

---

## 6. Definition of Done

Une tâche est terminée uniquement si :

- [ ] la règle métier vient du cahier des charges et est respectée ;
- [ ] la logique métier est dans un service de `app/Domain/`, pas dans un
      contrôleur ni dans une vue ;
- [ ] la base est modifiée par migration réversible ;
- [ ] les tests couvrant la tâche passent ;
- [ ] `composer ci` est vert (analyse + lint + tests) ;
- [ ] l'UI utilise les tokens et composants existants (aucune couleur,
      rayon ou ombre codés en dur) ;
- [ ] le contexte de boulangerie est visible sur l'écran ;
- [ ] les états loading / empty / error sont gérés ;
- [ ] les permissions sont vérifiées **côté serveur** ;
- [ ] `ROADMAP.md` est à jour.

---

## 7. Règles métier non négociables

Reprises des §3, §27 à §29 et §53 du cahier des charges :

1. **Une seule source de vérité pour le stock.** On ne modifie jamais
   `quantity` directement : tout passe par un mouvement de stock.
2. **Toute variation de stock est traçable** (utilisateur, site, date,
   produit, quantité, unité, type, document source, motif, référence).
3. **Aucune suppression physique** d'une opération métier critique. Une
   correction est une opération compensatoire auditée.
4. **Les opérations critiques sont transactionnelles** (`DB::transaction`).
5. **Verrouillage des lignes de stock** (`lockForUpdate`) pour éviter les
   courses. Objectif : jamais de stock négatif sauf autorisation
   administrative explicite.
6. **Idempotence obligatoire** : réception, production, vente, transfert,
   paie et perte ne doivent jamais s'appliquer deux fois (double clic,
   retry réseau, rafraîchissement).
7. **Permissions contrôlées côté serveur** (Policies/Gates + middleware).
   Masquer un bouton ne suffit jamais.
8. **Isolation par site par défaut.** Le Super-Admin dispose d'une vision
   consolidée.
9. **Les paramètres métier ne sont jamais codés en dur.** Ils vivent dans
   `config/boulangerie.php` puis deviennent administrables en base.
10. **Les traitements lourds passent par les queues.**
11. **La logique métier est testable sans interface.**

---

## 8. Où mettre quoi

```
app/
├── Domain/            ← règles métier, un dossier par module
│   ├── Inventory/     (StockMovementService, StockService)
│   ├── Production/    (RecipeCalculator, ProductionService)
│   ├── Purchasing/    (PurchaseOrderService, GoodsReceiptService)
│   ├── Sales/         (SalesService, DayClosingService)
│   ├── Customers/     (WholesalerRebateService)
│   ├── Payroll/
│   └── Reporting/
├── Enums/             ← types fermés (Role, ProductType, StockMovementType…)
├── Http/Controllers/  ← contrôleurs minces
├── Livewire/<Module>/ ← composants Livewire, sans logique métier
├── Models/            ← Eloquent uniquement, pas de règles métier
├── Policies/          ← autorisation
├── Support/           ← utilitaires transverses (Format, Navigation)
├── Notifications/ · Jobs/ · Listeners/ · Events/ · Services/
```

Vues :

```
resources/views/
├── components/ui/         ← primitives (button, badge, alert, icon…)
├── components/layout/     ← AppShell, sidebar, topbar, page-header
├── components/business/   ← StockStatus, ExpiryIndicator, RebateProgress…
├── components/layouts/    ← gabarit HTML de page
└── <module>/              ← une page par écran
```

Tout composant UI est un composant anonyme Blade : `<x-ui.status-badge />`.
**Aucune logique métier dans un composant visuel** (Design System §60.6).

---

## 9. Conventions

- **Nommage** : classes en anglais (`StockMovementService`), libellés UI en
  français. Les libellés ne sont jamais écrits en dur dans une vue quand
  ils viennent de `config/boulangerie.php`.
- **Argent et quantités** : toujours via `App\Support\Format`
  (`Format::money()`, `Format::number()`, `Format::quantity()`,
  `Format::signed()`). Jamais de `number_format()` dans une vue.
  Jamais `125000` affiché : toujours `125 000 FCFA`.
- **Statuts** : toujours via `<x-ui.status-badge :status="..." />` et la
  table `config('boulangerie.statuses')`. Jamais de couleur d'état seule.
- **Icônes** : uniquement Lucide, via `<x-ui.icon name="..." />`.
  Ne pas ajouter une seconde famille d'icônes.
- **Couleurs / rayons / ombres** : uniquement les tokens de
  `resources/css/app.css`. Interdit : `#A96325`, `bg-amber-600`,
  `rounded-[13px]`, `shadow-md`, `bg-white`. Le détail des tokens et des
  composants est dans `docs/CHARTE.md` ; `composer charte` (joué par
  `composer ci`) échoue à la moindre dérive.
- **Types** : `declare(strict_types=1);` en tête de chaque nouveau fichier
  PHP applicatif. Types de retour explicites. `list<>` = tableau séquentiel.
- **Argent en base** : entiers (FCFA sans décimale) ou `decimal(15,2)`,
  jamais `float` pour un montant.
- **Noms de tables et colonnes en anglais**, comme la liste du cahier §24.
- **Langue de l'interface** : français. `APP_LOCALE=fr`.
- **Fuseau** : `Africa/Abidjan` (`APP_TIMEZONE`).

---

## 10. Interdits

- ❌ Modifier une décision de stack (Laravel/Blade-Livewire/MySQL)
  sans décision explicite consignée dans `docs/DECISIONS.md`.
- ❌ Créer un style, un composant ou une couleur concurrents du Design
  System.
- ❌ Mettre une requête métier ou un calcul dans un composant Livewire ou
  une vue Blade.
- ❌ Utiliser `float` pour un montant.
- ❌ Supprimer une ligne d'historique métier.
- ❌ Exposer une erreur technique brute à l'utilisateur (cahier §41) :
  message clair + code, jamais la stack trace.
- ❌ Écrire une couleur, un rayon ou une ombre en dur : utiliser un token
  de `resources/css/app.css` (voir `docs/CHARTE.md`).
- ❌ Utiliser une palette Tailwind hors charte (`bg-red-500`), `bg-white`,
  `shadow-sm` ou une seconde famille d'icônes.
- ❌ Exécuter une opération critique sans transaction ni contrôle de
  permission serveur.
- ❌ Marquer une tâche terminée sans `composer ci` vert (charte incluse).

---

## 11. État actuel du projet

**Phase 0 — socle technique : terminée.**

- Laravel 12.69 + Livewire 4 + Spatie Permission installés et configurés
- Base MySQL `boulangerie_soumahoro` créée et migrée
- `.env` configuré (fr, Africa/Abidjan, FCFA)
- Tokens du Design System câblés dans `resources/css/app.css`
- `config/boulangerie.php` (source de vérité des valeurs métier)
- `config/navigation.php` + `App\Support\Navigation`
- Enums `Role`, `ProductType`, `StockMovementType`, `BakeryStatus`
- `App\Support\Format` (montants, quantités, écarts)
- Primitives UI : `icon`, `button`, `status-badge`, `stat-card`, `alert`,
  `empty-state`, `page-header`, `form-field`, `select`
- Layout complet : AppShell, sidebar responsive, topbar, `/dashboard`
- Rôles et permissions semés, compte Super-Admin local
- CI, Larastan et Pint configurés

**Phase 1 — socle métier : terminée.**

- Authentification maison (connexion, déconnexion, mot de passe oublié et
  réinitialisation), limitation des tentatives, `lang/fr/`
- Multi-sites : `bakeries`, `bakery_user`, `bakery_settings`,
  `BakeryContext`, `ResolveBakeryContext`, `BakeryScope`,
  trait `BelongsToBakery`
- `SiteSwitcher` (Livewire) avec vérification serveur de l'affectation
- CRUD Boulangeries réservé au Super-Admin (`BakeryPolicy`)
- Unités et conversions : `UnitsSeeder` +
  `App\Domain\Measurement\UnitConversionService` (BCMath, chaîne directe /
  inverse) + écran `/settings/units`
- Produits et matières premières : `product_categories`, `products`,
  `product_prices`, `Product`, `ProductPolicy`, écrans `/products`
- Utilisateurs, rôles et permissions : écrans `/settings/users` et
  `/settings/roles`, `UserPolicy`, `RolePolicy` ; le rôle Super-Admin n'est
  pas modifiable (`Gate::before` le contourne donc le contrôle est
  posé au contrôleur)
- Mode dégradé de `App\Support\Navigation` retiré : fail-closed

**Charte BakeryOS intégrée (charte caisse).**

- Tokens vert/bleu contextuel, Roboto, rayons 4–8 px, barre d'accent —
  `resources/css/app.css`
- Shell : sidebar (bandeau vert + bloc site bleu), topbar (recherche,
  sélecteur de site, alertes, profil), `page-header`
- Composants : `button`, `status-badge`, `stat-card`, `card`,
  `data-table`, `search-input`, `progress`, `bar-chart`, `modal`, `toast`
- Module **Caisse (POS)** : `App\Livewire\Sales\PointOfSale` + écran
  plein écran (`/caisse`), panier et modale de paiement
- Tous les écrans du prototype livrés (production, stocks, achats,
  ventes, pertes, clients, grossistes, ristournes, fournisseurs,
  employés, paie, charges, rapports, notifications, audit, utilisateurs,
  rôles, paramètres), chacun protégé par permission côté serveur
- 67 tests verts, Larastan et Pint propres

**Phase 2 — stocks : terminée.**

- Grand livre : `stock_movements` (jamais supprimé, `quantity_after`,
  document source, `idempotency_key` unique) + stock matérialisé
  `stocks` (physique, réservé) isolé par site (`BelongsToBakery`)
- `App\Domain\Inventory\StockMovementService` : **seul** point d'écriture
  du stock — transaction, `lockForUpdate`, idempotence par clé, refus du
  stock négatif (`guards.allow_negative_stock`), recalcul des alertes
- `StockService` (disponible = physique − réservé, statut, cumul) et
  `StockAlertService` (seuil + cumulé, site prioritaire)
- `StockReservationService` (réserver / libérer / consommer, idempotent)
- Écrans `/inventory` (stock temps réel + ajustement motivé) et
  `/inventory/movements` (grand livre filtrable et paginé)
- Composants `business/` : `stock-status`, `stock-movement-badge`,
  `expiry-indicator`
- `Decimal` (BCMath) centralise le calcul décimal exact
- 92 tests verts, Larastan et Pint propres

**Phase 3 — achats : terminée.**

- Fournisseurs : `suppliers` + pivot `product_suppliers` (prix négocié,
  délai, référence, fournisseur principal), CRUD `/suppliers`
- Commandes : `purchase_orders` + `purchase_order_items` (statuts §11.1
  portés par `PurchaseOrderStatus`), `PurchaseOrderService` (référence
  `PO-{Ym}-{NNNN}`, total FCFA recalculé, statut dérivé des réceptions)
- Réception : `goods_receipts` + `goods_receipt_items`,
  `GoodsReceiptService` (référence `GR-{Ym}-{NNNN}`) — la validation
  écrit le stock via `StockMovementService` (`SupplierIn`), verrouille la
  réception et **refuse une seconde validation**
- Écrans `/purchasing/orders` (liste, création, détail avec écart et
  formulaire de réception) et `/suppliers` (liste, création, édition)
- Permissions ajoutées : `purchasing.create`, `purchasing.manage`,
  `supplier.manage` ; rôle `production-manager` étendu
- 112 tests verts (384 assertions), Larastan, Pint et charte propres

**Phase 4 — production : terminée.**

- Recettes : `recipes` + `recipe_items` (`RecipeDeductionMode` :
  quantité / monétaire / cumulée / saisie), catalogue partagé entre sites
- `RecipeCalculator` : besoins matières, ressources cumulées (fagot,
  glace, sel), rendement théorique, coût théorique et écart, en BCMath
- `ProductionService` : bloc transactionnel §27 — consommation des
  matières (`ProductionOut`), sortie de produit fini (`ProductionIn`),
  création de lot et calcul de la date limite, coût réel, écart motivé,
  **non rejouable** (garde d'état + clés d'idempotence)
- Lots : `production_batches` + `production_outputs`, statut `BatchStatus`
  et `expiry-indicator` prêt pour la Phase 6
- Écrans `/production` (liste), `/production/create` (assistant 4 étapes,
  calculs serveur), `/production/{order}` (détail),
  `/production/recipes` (nomenclatures)
- Composants `business/` : `stepper`, `recipe-preview`,
  `production-summary`, `quantity-input`, `product-selector`,
  `confirm-dialog`
- Permissions : `recipe.manage` ; rôle `production-manager` étendu
- 129 tests verts (445 assertions), Larastan, Pint et charte propres

**Phase 5 — ventes : terminée.**

- Ventes : `sales` + `sale_items` (`SaleType` retail/wholesale,
  `SaleStatus`), référence `SALE-{Ym}-{NNNN}` générée côté serveur,
  montants en FCFA entiers, quantités en BCMath
- `App\Domain\Sales\SalesService::record()` : transaction, prix par
  défaut (`sale_price` ou `wholesale_price` selon le type), sortie de
  stock `SaleOut` via `StockMovementService` avec clé
  `sale:{saleId}:item:{itemId}`, `Payment` si mode de paiement,
  `EmptySaleException` sans ligne, rejeu géré par l'index unique
  `idempotency_key` (retourne la vente existante)
- Ventes grossistes : type `SaleType::Wholesale` porté par la vente
  (pas de table `wholesaler_sales` — voir ADR-016)
- Clients : `customers` (isolé par site, soft deletes), CRUD `/customers`,
  permission ajoutée `customer.manage` (rôles sales-manager et cashier)
- Modes de paiement : table `payment_methods` amorcée depuis
  `config('boulangerie.payment_methods')` (espèces, mobile money,
  virement, crédit, autre)
- Clôture : `App\Domain\Sales\DayClosingService` — snapshot
  produit/vendu/attendu par date, **unique par site et par date**
  (`DayClosingAlreadyExistsException`), écart par produit
  (`day_closings.gap_total`), observation obligatoire au-delà de
  `day_closing.gap_justification_threshold`
  (`DayClosingGapJustificationRequiredException`, cahier §15)
- Écrans `/sales` (liste, recherche, date), `/sales/create`,
  `/sales/{sale}`, `/sales/closings{,/create,/{closing}}`, `/customers`
- Caisse (`/caisse`) câblée : catalogue réel `FinishedGood`, stock temps
  réel, `validatePayment()` appelle `SalesService` (insuffisance et vente
  vide rendues en message clair)
- Policies `SalePolicy`, `CustomerPolicy`, `DayClosingPolicy` ; 152 tests
  verts (514 assertions), Larastan, Pint et charte propres

**Prochaine tâche : Phase 6 — pertes et péremption.** Lots expirés,
 `ExpiryService` + job planifié, transformation des invendus en pertes,
 alertes. Voir `ROADMAP.md`.

### Repères utiles

- Le contexte de site est un **singleton** (`AppServiceProvider::register`).
  Le middleware le renseigne à chaque requête ; tout consommateur doit le
  lire via `app(BakeryContext::class)`.
- Les modèles porteurs d'un `bakery_id` doivent utiliser le trait
  `BelongsToBakery` pour bénéficier de l'isolation par site.
