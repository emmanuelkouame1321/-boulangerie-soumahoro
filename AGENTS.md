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

**Phase 6 — pertes et péremption : terminée.**

- Pertes : `loss_records` + `loss_items` (`LossReason` : expiry / unsold /
  production_defect / damage / other ; `LossOrigin` : manual / expiry),
  référence `LOSS-{Ym}-{NNNN}`, quantités BCMath, valeur en FCFA entiers
- `App\Domain\Inventory\LossService::record()` : transaction, une ligne
  par produit (`unit_value` = `loss.valuation_field`, `line_value` =
  `Decimal::toFrancs`), sortie de stock `LossOut` via
  `StockMovementService` (clé `loss:{recordId}:item:{itemId}`), alerte
  `SignificantLoss` au-delà de `loss.significant_threshold`,
  `EmptyLossException` sans ligne
- Lots FEFO : `App\Domain\Inventory\LotService` — `sellFromLots()`
  (premier expiré, premier sorti, **exclut les lots expirés**),
  `attributeToLots()` (imputation automatique d'une perte sans lot
  désigné, expirés compris), `deriveStatus()`. Un lot expiré est refusé à
  la vente (`LotExpiredException`, cahier §41) ; un lot désigné insuffisant
  lève `InsufficientLotException`
- Péremption : `App\Domain\Inventory\ExpiryService` — `refresh()`
  (statuts dérivés + alertes `Expired` / `ExpirySoon` auto-résolues),
  `convertExpired()` (lots expirés → une perte `LossOrigin::Expiry`,
  **idempotente** via les clés `batch-expiry:{lot}:{date}`),
  `expiredLotsQuery()` partagé écran / lot
- Règle de péremption configurable : `expiry.soon_days` (défaut 1),
  `expiry.conversion` (`on_expiry_date` | `day_after_expiry_date`),
  `loss.valuation_field` (`purchase_price`), `loss.significant_threshold`,
  `loss_reasons`
- Job planifié §40 : `App\Jobs\CheckBatchExpiry` (refresh + convertExpired
  par site) dispatché par `boulangerie:expiry-check`
  (`App\Console\Commands\ExpiryCheckCommand`), planifié
  `->hourlyAt(5)` dans `routes/console.php`
- Centre d'alertes : `StockAlertType` étendu (`ExpirySoon`, `Expired`,
  `SignificantLoss`, `ProductionGap`) ; `StockAlertService` expose
  `open()` / `resolve()` (les alertes conditionnelles Low/Critical/Cumulées
  sont recalculées, les alertes événementielles sont auto-résolues)
- Écrans `/losses` (liste, recherche, filtres motif/date),
  `/losses/create`, `/losses/{loss}`, `/notifications` (filtres
  statut/type, acquittement)
- Policies `LossRecordPolicy` (`loss.view` / `loss.create`),
  `StockAlertPolicy` (`alert.view` / `alert.acknowledge`) ; 174 tests
  verts (624 assertions), Larastan, Pint et charte propres

**Phase 7 — grossistes et ristournes : terminée.**

- Grossistes : `wholesalers` — fiche §12.1
  (raison sociale, responsable, zone, grille de prix, quantité habituelle,
  conditions de paiement, date d'adhésion) isolée par site, reliée à un
  compte client (`customer_id`) pour rattacher l'historique de ventes
- Règles administrables : `wholesaler_rules` — base `RebateBasis`
  (quantité / CA / famille / combinaison), famille optionnelle, seuil bas
  et haut, taux en pourcentage ; aucun seuil codé en dur
- `App\Domain\Customers\WholesalerRebateService` :
  `aggregate()` (quantités + CA des ventes `Wholesale` validées du compte
  client, filtrable par famille), `tierFor()` / `matches()` (palier), et
  `compute()` → `validate()` → `settle()`. Ristourne = base éligible ×
  taux (`Decimal::applyPercent`), unique par grossiste et par exercice ;
  une ristourne validée ou versée n'est plus recalculée
  (`RebateAlreadyValidatedException`, §53-11)
- Enums `RebateBasis`, `RebateStatus` (projected / validated / paid)
- Écrans `/wholesalers` (liste, création, fiche avec statistiques,
  modification) et `/rebates` (paliers administrables, calcul, validation,
  versement, synthèse de l'exercice)
- Composants `business/` : `wholesaler-summary`, `rebate-progress`
- Policies `WholesalerPolicy` (`wholesaler.view` / `wholesaler.manage`),
  `WholesalerRebatePolicy` (`rebate.view` / `rebate.manage`, ajoutée aux
  permissions et au rôle sales-manager) ; seed `WholesalerSeeder`
- 188 tests verts (665 assertions), Larastan, Pint et charte propres

**Phase 8 — employés et paie : terminée.**

- Employés : `employees` (fiche §16.1), `employee_contracts`,
  isolés par site, poste et type de contrat issus de la configuration
  (`boulangerie.positions`, `boulangerie.contract_types`) ; enums
  `EmployeeStatus`, `ContractType`
- Paie : `payroll_periods` (unique par site et par mois),
  `payroll_items` (unique par période et employé), `payroll_payments`
- `App\Domain\Payroll\PayrollService` : `openPeriod()`,
  `generate()` (lignes depuis les employés actifs, salaire de base),
  `updateItem()` (primes / avances / retenues), `refreshTotals()`,
  `validate()` puis `pay()`. Net = base + primes − avances − retenues,
  **jamais négatif** ; enum `PayrollStatus` (draft / validated / paid)
- Historique figé : une période validée lève
  `PayrollPeriodLockedException` à toute modification ; le versement exige
  une validation (`PayrollPeriodNotValidatedException`), une période sans
  ligne ne peut être validée (`EmptyPayrollPeriodException`)
- Écrans `/employees` (liste, fiche avec historique, création,
  modification) et `/payroll` (périodes, détail, ajustement des lignes,
  validation, versement)
- Policies `EmployeePolicy` (`employee.view` / `employee.manage`),
  `PayrollPeriodPolicy` (`payroll.view` / `payroll.manage`, ajoutées aux
  permissions) ; seed `EmployeeSeeder`
- 204 tests verts (703 assertions), Larastan, Pint et charte propres

**Phase 9 — charges, rapports et dashboards : terminée.**

- Charges : `expense_categories` (12 entrées depuis
  `config('boulangerie.expense_categories')`, désactivables, jamais
  supprimées) et `expenses` (référence `EXP-{Ym}-{NNNN}`, montant FCFA,
  `spent_on`, mode de paiement, pièce jointe, commentaire)
- `App\Domain\Expenses\ExpenseService` : `record()` (transaction +
  référence), `createCategory()`, `deactivateCategory()`
- `App\Domain\Reporting\ReportingService` : agrégats SQL pour les 8
  rapports §33 (ventes, production, stock, achats, pertes, grossistes,
  charges, paie) + `questions()` → **17 réponses du cahier §55** ;
  `$bakeryId = null` = consolidation tous sites (Super-Admin)
- Exports **sans dépendance** (ADR-020) : CSV natif (BOM UTF-8,
  séparateur `;`), SpreadsheetML 2003 (`.xls`, `<Workbook>`), vue
  imprimable `reports.print` (« Imprimer → PDF ») via
  `App\Support\ReportExport`
- Écrans `/expenses` (liste, recherche, période, catégorie, totaux ;
  catégories administrables), `/expenses/create`,
  `/expenses/{expense}/edit`, `/reports` (sélecteur de période + site
  Super-Admin, résumé, 17 questions §55, tendance 14 j),
  `/reports/{kind}` (8 kinds) avec exports
- Dashboards réels `/dashboard` : mode par rôle (Super-Admin consolidé
  avec CA par site, Production avec alertes, Vente avec top produits),
  tendance 7 j (`x-ui.bar-chart`)
- Export du grand livre `/inventory/movements/export` (CSV / Excel,
  mêmes filtres que l'écran, permission `inventory.view`) — remplacé en
  Phase 10 par la file d'attente `/exports` (kind `movements`)
- Permissions : `expense.manage` et `report.export` ajoutées au
  catalogue ; `expense.view` et `report.view` (déjà déclarées) câblées
  sur les routes via middleware `permission:` — aucune vue n'est
  accessible aux rôles standard par défaut ; policy `ExpensePolicy` ;
  seed `ExpenseCategorySeeder`
- 218 tests verts (790 assertions), Larastan, Pint et charte propres

**Phase 10 — optimisation, sécurité et qualité : en cours.**

- Audit : `audit_logs` (insertion seule, `user_name` figé, objet conservé
  après suppression, `bakery_id` nullable sans clé étrangère, index
  `[bakery_id, created_at]`) ; `App\Domain\Audit\AuditLogger` est le
  **seul** point d'écriture (`record()`, `created()`, `updated()`, le
  `status` modifié nomme l'action, `SENSITIVE` exclut mot de passe et
  jeton de session). Trait `AuditsChanges` (opt-in) posé sur 11 modèles
  critiques ; les changements de permissions d'un rôle sont journalisés
  depuis `RoleController`. Écran `/settings/audit` (`audit.view`) :
  recherche, action, période, pagination, détail anciennes/nouvelles
  valeurs
- Notifications : `notification_preferences` (canaux `internal` /`email`
  par site et par type), défauts dans
  `config('boulangerie.notification_channels')`,
  `App\Domain\Notifications\AlertNotifier` (`alertOpened()`,
  `wants()`, `recipients()` = utilisateurs du site ayant `alert.view`),
  `StockAlertMail` (ShouldQueue) ; `StockAlertService` notifie à chaque
  ouverture d'alerte ; écran `/settings/notifications`
  (`settings.view`) ; compteur d'alertes ouvertes sur la cloche de la
  topbar (`View::composer`)
- Queue : `GenerateExport` (ShouldQueue) produit le fichier dans
  `storage/app/private/exports/` puis marque la ligne `ready` (7 jours)
  ou `failed` avec le message d'erreur ; écran `/exports` (« Téléchargements »)
  avec statuts, relance automatique et téléchargement réservé au
  demandeur (410 si expiré) ; commande `boulangerie:exports-prune`
  planifiée à 03:15
- `app/Domain/Export/ExportContent` centralise le contenu des trois
  familles d'exports (8 rapports, grand livre, audit) ;
  `ReportExport::writeCsv()` / `writeXls()` écrivent sur disque (les
  `csv()` / `xls()` en flux restent pour l'affichage direct)
- Index de recherche : `[bakery_id, occurred_at]` sur `stock_movements`,
  `[bakery_id, created_at]` sur `audit_logs`
- Cache des données de configuration (cahier §48) :
  `App\Support\Navigation::tree()` mémorise l'arbre de menu résolu (les
  entrées sans route sont écartées une fois, plus de parcours de la table
  des routes à chaque requête) et `App\Support\Configuration` mémorise les
  structures dérivées (catalogue de permissions, groupement par module,
  libellés et permissions des rôles — `App\Enums\Role` les lit). Les clés
  sont indexées par le **contenu** (empreinte de la configuration et, pour
  la navigation, de la table des routes) : aucune purge manuelle, jamais
  de valeur périmée ; TTL 24 h. Ne sont jamais mis en cache le filtre par
  permission, l'état « actif », l'URL absolue ni aucun agrégat métier
- N+1 : mode de paiement des ventes chargé avec la liste
  (`with('payments')`), chiffre d'affaires du jour par site et tendance
  7 j en requêtes groupées ; garde-fou
  `tests/Feature/Performance/QueryBudgetTest.php` (le nombre de requêtes
  d'un écran doit être le même avec 1 puis 15 lignes)
- Sécurité du compte (cahier §31, ADR-022) :
  `App\Support\Totp` implémente TOTP (RFC 6238, validé par les vecteurs de
  la RFC) et `App\Domain\Security\TwoFactorService` porte le cycle de vie
  (begin / confirm / verify / disable). Écran `/settings/security` en
  libre-service (mot de passe courant exigé pour toute modification), défi
  à la connexion (`/two-factor/challenge`), 8 codes de secours hachés et
  consommés à l'usage, secret et codes chiffrés au repos (`encrypted`),
  `EnsureTwoFactorIsVerified` ferme toutes les pages applicatives. Lien
  « Sécurité du compte » dans le menu profil de la topbar
- Limitation de débit nommée dans `AppServiceProvider` : `login`,
  `two-factor` (5/min par compte), `password-reset` (demande **et**
  réinitialisation), `exports` (10/min par compte)
- Stratégie de sauvegarde documentée : `docs/SAUVEGARDES.md` (script
  `mysqldump` + `tar` + `rsync`, contrôle d'intégrité, rétention
  7 / 4 / 12, journal CSV, restauration testée trimestriellement, procédure
  de reprise après incident, `APP_KEY` à sauvegarder avec la base)
- Transferts inter-sites (cahier §19.3, ADR-023) : `stock_transfers`
  (référence `TRF-{Ym}-{NNNN}`, deux sites, produit, lot facultatif,
  motif, date métier, statut `draft → sent → received`, `cancelled` en
  brouillon) ; `App\Domain\Inventory\StockTransferService` orchestre
  `create()` / `send()` / `receive()` / `cancel()`. L'expédition crée un
  mouvement `transfer_out` chez l'expéditeur (et sort le lot, compteur
  `transferred_quantity`), la réception un `transfer_in` chez le
  destinataire (et recrée le lot avec la même date limite via
  `LotService::createFromTransfer()`). Idempotence
  `transfer:{id}:out|in`, ordre « lots puis stock ». Pas de
  `BelongsToBakery` : isolation par scope `involvingAny()` + 
  `Route::bind('transfer', …)` (404 hors sites accessibles) et
  `StockTransferPolicy` (expéditeur / destinataire distincts). Permissions
  `transfer.view` / `transfer.create` / `transfer.receive` ; écrans
  `/inventory/transfers` (liste, création, détail avec Expédier /
  Réceptionner / Annuler) ; entrée de navigation « Transferts »
- Recherche globale (cahier §10, ADR-024) : `App\Livewire\Search\GlobalSearch`
  (palette `Ctrl K` / `Échap`, montée à la racine de la coquille, résultats
  recalculés à la frappe via `wire:model.live.debounce.300ms`) et
  `App\Domain\Search\GlobalSearchService` (dix groupes, scopes `search()`
  existants, filtrage **serveur** par permission de module et par site —
  `BakeryScope` pour les modèles rattachés à un site, `involvingAny()`
  explicite pour `StockTransfer` ; bornes 2/64 caractères, 5 résultats par
  groupe ; le service ne renvoie que des chaînes et des URL, jamais de
  modèle ni d'identifiant). Déclencheur dans la topbar du gabarit applicatif
- Correctif du menu burger mobile : la coquille applicative
  (`x-layout.app-shell`) est désormais la **racine Alpine** (`x-data`) ;
  garde-fou `tests/Feature/Design/AlpineRootTest.php` (toute directive
  Alpine du rendu doit avoir un ancêtre `x-data`/`wire:id`)
- Création et modification de recettes (ADR-025) : `RecipeController`
  (`create/store/edit/update`), `StoreRecipeRequest`/`UpdateRecipeRequest`
  (`permission:recipe.manage`), formulaire partagé
  `x-production.recipe-form` à lignes dynamiques (Alpine `x-data` + `x-for`) ;
  duplication par pré-remplissage `?from={id}`, boutons « Modifier » et
  « Dupliquer » sur l'index ; nomenclature remplacée en bloc à l'édition
- Écran Stocks : les compteurs « Stock faible » et « Critique » sont des
  liens vers la liste filtrée (`inventory.index?state=low|critical`) ;
  `x-ui.stat-card` accepte désormais un `href` (carte cliquable, charte
  respectée), recherche en cours conservée
- Catalogue (`/products`) scindé en deux familles (ADR-026) : onglets
  « Produits » (produit fini) et « Matières premières » (tout le reste),
  via `ProductType::group()` (dérivé du type, rien en base) ; filtre `type`
  scopé à la famille, compteurs par famille, retour sur la bonne famille
  après création
- Formulaire produit : la famille (Produits / Matières premières) est
  demandée en premier (ADR-026) ; la nature précise n'apparaît que pour les
  matières premières, le `type` enregistré en découle (champ caché)
- Catalogue : « Nouveau produit » ouvre une modale de choix de famille
  (`x-ui.modal` + `$dispatch('open-modal')`) qui mène à
  `products.create?group=products|materials`
- Code produit (SKU) : bouton « Générer » (ADR-027) via
  `GET products.generate-sku` (`ProductType::skuPrefix()` + nom normalisé,
  ex. `PF-BAGUETTE`, suffixe `-2` en cas de collision) ; l'unicité reste
  vérifiée à l'enregistrement
- Caisse (POS) mobile (ADR-028) : panier extrait dans
  `livewire/sales/partials/cart.blade.php`, rendu en colonne (`lg+`) et en
  tiroir bas (mobile, racine Alpine `cartOpen`) ; catégories en défilement
  horizontal, barre d'action fixe (total / Panier / Payer)
- Tableaux de données responsives (ADR-029) : sous `md`, `x-ui.data-table`
  empile chaque ligne en carte (première cellule = titre, `td::before` = libellé
  via `--dt-label-N`) ; styles dans `app.css`, prop `stack` pour désactiver
- Photos de produit (ADR-030) : `photo` sur le formulaire produit, disque
  `public` / dossier `products/`, chemin dans `products.photo_path`,
  `Product::photoUrl()`. **`php artisan storage:link` est indispensable** (le
  dossier `/public/storage` est ignoré par git) — déjà dans `composer setup`.
  Sans photo : icône `package` au catalogue, emoji en caisse
- Carte produit de la caisse (ADR-031) : conteneur `rounded-card border
  border-line bg-surface shadow-card` en `overflow-hidden`, visuel
  `aspect-square` plein cadre (photo `size-full object-cover`, emoji en repli),
  prix et état du stock dans un corps aéré — plus de chevauchement du visuel
- Identité du site (ADR-032) : le nom de la boulangerie se modifie dans
  `/settings/bakeries` (Super-Admin) et un **logo** y est téléversé
  (`bakeries.logo_path`, disque `public`, dossier `bakeries/`, remplacement et
  suppression du fichier précédent après enregistrement réussi). Le logo
  sert de **favicon** : `App\Support\Branding::faviconUrl()` prend celui du
  site actif, sinon celui du premier site accessible, sinon `favicon.ico`.
  Logo affiché sur la liste des sites, la barre latérale et un bandeau
  « Identité du site » du tableau de bord (repli : `Bakery::initial()`)
- 372 tests (1535 assertions) — Larastan, Pint et charte propres ; les seuls
  échecs locaux (2, `ProductPhotoTest`) viennent de l'extension GD absente
  de `php.ini`, jamais du code

**Prochaine tâche : finir la Phase 10 — revue responsive des formulaires et
rapports (§89 : 1440 / 1280 / 1024 / 768 / 390), accessibilité WCAG 2.2 AA,
scénarios de concurrence et documentation d'exploitation.**
Voir `ROADMAP.md`, ADR-021, ADR-022 et ADR-023.

### Repères utiles

- Le contexte de site est un **singleton** (`AppServiceProvider::register`).
  Le middleware le renseigne à chaque requête ; tout consommateur doit le
  lire via `app(BakeryContext::class)`.
- Les modèles porteurs d'un `bakery_id` doivent utiliser le trait
  `BelongsToBakery` pour bénéficier de l'isolation par site.
- **Le lien `public/storage` est absolu** : il casse si le dossier du projet
  change de nom ou d'emplacement (ou après un clone), et **toutes les images
  renvoient un 404 sans aucune erreur** (photos de produit, logos de site).
  Symptôme « les images ne s'affichent pas » → `rm public/storage &&
  php artisan storage:link`, puis vérifier
  `curl -o /dev/null -w "%{http_code}" "$APP_URL/storage/bakeries/<fichier>"`
  (attendu : 200).
- **Alpine n'initialise que les sous-arbres dont la racine porte `x-data`**
  (ou un composant Livewire `wire:id`). Une directive `@click`/`x-…` écrite
  ailleurs n'est jamais liée, et **sans aucune erreur** : vérifier qu'un
  `x-data` existe plus haut, sinon la coquille applicative le fournit.
- Éviter `x-show` quand le code doit agir **dès** l'affichage (focus d'un
  champ, mesure de taille) : Alpine diffère le passage à l'état visible par
  `setTimeout`. Préférer une bascule de classe (`:class="… ? 'flex' : 'hidden'"`).
- Toute nouvelle route web passe par le groupe `web`, donc par
  `EnsureTwoFactorIsVerified` : ne jamais écrire une page « de secours »
  hors de la liste des routes autorisées sans second facteur, sinon un
  compte protégé est enfermé. Les routes qui modifient la 2FA vivent dans
  `settings.security.*` et exigent le mot de passe courant.
- Un parcours sensible nouveau (envoi d'email, traitement lourd, action
  irréversible) doit recevoir un limiteur nommé dans `AppServiceProvider`
  plutôt qu'un `throttle:60,1` anonyme : le seuil se documente alors à un
  seul endroit.
