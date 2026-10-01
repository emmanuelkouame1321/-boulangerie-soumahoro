# ROADMAP — Développement autonome de Boulangerie Soumahoro

> **Comment utiliser ce fichier :** prendre la première case non cochée,
> la traiter en respectant `AGENTS.md`, la cocher, mettre à jour l'en-tête
> de statut, puis passer à la suivante. Ne jamais sauter une phase.

**Statut global : Phase 0 terminée → Charte BakeryOS intégrée ✅ → Phase 1 terminée ✅ → Phase 2 terminée ✅ → Phase 3 terminée ✅ → Phase 4 terminée ✅ → Phase 5 terminée ✅ → Phase 6 terminée ✅**

Légende : `[ ]` à faire · `[~]` en cours · `[x]` terminé

---

## Phase 0 — Socle technique ✅

- [x] Projet Laravel 12 + PHP 8.2
- [x] Livewire 4 installé et câblé
- [x] Tailwind 4 + tokens du Design System
- [x] Spatie Permission installé, middleware alias déclarés
- [x] Configuration MySQL `boulangerie_soumahoro` + migrations
- [x] `config/boulangerie.php` (valeurs métier configurables)
- [x] `config/navigation.php` + `App\Support\Navigation`
- [x] Enums `Role`, `ProductType`, `StockMovementType`
- [x] `App\Support\Format`
- [x] Primitives UI et layout AppShell/Sidebar/Topbar
- [x] Seeders rôles/permissions + Super-Admin local
- [x] Larastan, Pint, CI GitHub Actions, scripts composer
- [x] Tests de fumée verts

**Critère de sortie atteint :** `composer ci` vert, `/dashboard` se rend.

---

## Phase 0.5 — Intégration du prototype BakeryOS ✅

- [x] Charte caisse appliquée aux tokens (`resources/css/app.css`) :
      vert primaire + bleu contextuel, Roboto, rayons 4–8 px, barre d'accent
- [x] Shell : sidebar (bandeau vert, bloc site bleu, menu en majuscules),
      topbar (recherche, sélecteur de site, alertes, profil), `page-header`
- [x] Primitives et composants : `button`, `status-badge`, `stat-card`,
      `card`, `data-table`, `search-input`, `progress`, `bar-chart`,
      `modal`, `toast`, `alert`, `empty-state`, `form-field`, `select`
- [x] Module **Caisse (POS)** : `App\Livewire\Sales\PointOfSale` et écran
      plein écran `/caisse` (catégories, produits, panier, paiement)
- [x] Tous les écrans du prototype (`/production`, `/inventory`,
      `/purchasing/orders`, `/sales`, `/losses`, `/customers`,
      `/wholesalers`, `/rebates`, `/suppliers`, `/employees`, `/payroll`,
      `/expenses`, `/reports`, `/notifications`, `/settings/audit`,
      `/settings/users`, `/settings/roles`, `/settings`)
- [x] Navigation et permissions alignées sur la charte (`config/navigation.php`,
      catalogue `permissions`, rôle `cashier`)
- [x] Tests de rendu de tous les écrans + tests du composant caisse

**Critère atteint :** `composer ci` vert ; chaque écran est protégé par une
permission vérifiée côté serveur ; le Super-Admin accède à tout.

---

## Phase 1 — Socle métier (MVP 1 à 3)

### 1.1 Authentification ✅

- [x] Authentification maison (contrôleurs minces) réutilisant le layout
      et les composants du Design System — pas de Breeze, dont le
      scaffolding entrerait en conflit avec `components/layouts/app`
- [x] Écran de connexion conforme au Design System §57
- [x] Déconnexion (session invalidée + jeton CSRF régénéré)
- [x] Mot de passe oublié et réinitialisation
- [x] Messages d'erreur en français (`lang/fr/`) sans fuite d'information
- [x] Limitation des tentatives : 5 par compte + 20/min par IP
- [~] Redirection par rôle après connexion — tout le monde arrive sur
      `/dashboard` pour l'instant ; les tableaux de bord dédiés
      (Super-Admin / Production / Vente) sont livrés en Phase 9
- [x] Routes métier derrière le middleware `auth`

**Critère atteint :** un visiteur non authentifié est redirigé vers
`/login` ; un utilisateur connecté voit son nom et son rôle dans la topbar.

### 1.2 Boulangeries (multi-sites) ✅

- [x] Migration `bakeries` (nom, code, adresse, téléphone, responsable,
      statut, observations) + `softDeletes`
- [x] Migration pivot `bakery_user` (affectation multi-sites, `is_default`)
- [x] Migration `bakery_settings` (paramètres par site, valeur JSON)
- [x] Modèle `Bakery` + relations + `BakeryStatus`
- [x] `App\Domain\Sites\BakeryContext` : résolution du site courant,
      Super-Admin = vision consolidée, **fail-closed** sans site
- [x] `App\Models\Scopes\BakeryScope` + trait `BelongsToBakery`
      (isolation par site, prêt pour les modèles de Phase 2)
- [x] `ResolveBakeryContext` : middleware qui alimente le contexte à
      chaque requête
- [x] Composant `SiteSwitcher` (Design System §12) avec vérification
      serveur de l'affectation — un identifiant venu du navigateur n'est
      jamais fait confiance
- [x] CRUD boulangeries réservé au Super-Admin (`BakeryPolicy`)
- [x] Tests : contexte de site, bascule autorisée/refusée, permissions
- [x] Tests d'isolation de données entre sites — le contexte et le scope
      sont testés ; l'isolation de bout en bout est vérifiée depuis la
      Phase 2 (`StockMovementServiceTest::test_the_stock_is_isolated_per_site`)

**Critère atteint :** le contexte de site est résolu à chaque requête, un
responsable est cantonné à son site, seul le Super-Admin accède à la vue
consolidée.

### 1.3 Unités de mesure

- [x] Migration `units` (code, libellé, symbole, nature) amorcée depuis
      `config/boulangerie.php`
- [x] Migration `unit_conversions` (facteur `decimal(18,8)`)
- [x] Seeders idempotents (`UnitsSeeder`), conversions par défaut
      (1 sac = 50 kg, 1 kg = 1 000 g, 1 L = 1 000 ml)
- [x] Service de conversion d'unités + tests (`1 sac = 50 kg`)
      `App\Domain\Measurement\UnitConversionService` : conversion
      directe, inverse et chaînée (parcours de graphe borné), en
      décimal exact via BCMath
- [x] Écran d'administration des unités et conversions
      (`/settings/units` — CRUD unités + gestion des conversions)

**Critère :** `1 sac de farine` se convertit en `50 kg` dans le calcul. ✅

### 1.4 Produits et matières premières ✅

- [x] Migration `product_categories`
- [x] Migration `products` (SKU, type, unité principale, prix d'achat,
      prix de vente, prix grossiste, seuil d'alerte, mode d'alerte, durée
      limite de vente, actif, description)
- [x] Migration `product_prices` (historique des prix)
- [~] Migration `product_suppliers` — reportée en **Phase 3** : la table
      `suppliers` n'existe pas encore et en dépend
- [x] Modèle `Product` avec casts et enum `ProductType`
- [x] `ProductCategory`, `ProductPrice` + factories + seeder démo
- [x] Policy `ProductPolicy` (jamais de suppression physique)
- [x] Écrans : liste (recherche, filtres, pagination), création, édition
- [~] Fiche produit en lecture seule — reportée (la liste suffit au MVP)
- [x] Composants `data-table`, `search-input`, `select`, `form-field`,
      `pagination` (formulaire en `components/products/form`)
- [x] Tests : politique d'accès, création, unicité du SKU

**Critère :** le Super-Admin crée une matière première et un produit fini
avec seuil et durée limite de vente, sans toucher au code. ✅

### 1.5 Utilisateurs, rôles et permissions ✅

- [x] Écran de gestion des utilisateurs (affectation multi-sites + site
      par défaut, roles de spatie)
- [x] Écran de gestion des rôles et permissions (catalogue groupé par
      module, création et modification de rôle)
- [x] `UserPolicy`, `RolePolicy`
- [x] Le rôle Super-Admin n'est pas modifiable (wildcard porté par
      `Gate::before`)
- [~] Journal des changements de permission — reporté en **Phase 10**
      (table `audit_logs`)
- [x] Menu latéral réellement filtré par permission : le mode dégradé
      de `App\Support\Navigation` a été retiré (fail-closed)
- [x] Tests : un rôle sans permission est renvoyé en 403 même par
      requête HTTP directe (paie, utilisateurs, rôles, produits, unités)

**Critère :** aucune permission ne peut être contournée via l'interface
ou une requête directe. ✅

**Critère de sortie de Phase 1 :** `composer ci` vert ✅ ; les rôles
voient des menus différents ✅ ; les données sont isolées par site
(contexte + scope testés).

---

## Phase 2 — Stocks (MVP 5) ✅

- [x] Migration `stocks` (produit, site, quantité, réservé)
- [x] Migration `stock_movements` (grand livre complet, jamais supprimé —
      clé étrangère en `restrictOnDelete`, `quantity_after`, `idempotency_key`
      unique, document source, motif, référence)
- [x] Migration `stock_alert_rules` et `stock_alerts`
- [x] Migration `stock_reservations`
- [x] `App\Domain\Inventory\StockMovementService` : unique point d'écriture
      du stock, transactionnel, idempotent, `lockForUpdate`
- [x] `App\Domain\Inventory\StockService` : stock disponible =
      physique − réservé ; statut (seuil) et consommation cumulée
- [x] `App\Domain\Inventory\StockAlertService` : recalcul à chaque
      mouvement (mode seuil + mode cumulé, périodes configurables,
      règle du site prioritaire sur la règle globale)
- [x] `App\Domain\Inventory\StockReservationService` : réserver / libérer /
      consommer (idempotent)
- [x] Écran Stocks : stock temps réel, seuils, statuts, ajustement motivé
      (Design System §23, §44)
- [x] Écran Mouvements : historique filtrable, paginé (Design System §34) —
      export différé en Phase 9
- [x] Composants `StockStatus`, `StockMovementBadge`, `ExpiryIndicator`
      (dossier `components/business/`)
- [x] Tests : aucun stock négatif ; deux mouvements concurrents sur le
      même stock ne se perdent pas ; idempotence ; isolation par site de
      bout en bout

**Critère (cahier §50) :** chaque mouvement est historisé, le stock est
toujours dérivable des mouvements. ✅ Pour chaque mouvement, la variation
`quantity_after` du grand livre reconstitue le stock physique ;
`StockMovementService` est le seul écrivain de `stocks.quantity`.

---

## Phase 3 — Achats (MVP 4 et 6) ✅

- [x] Migration `suppliers`, `product_suppliers`
- [x] Migration `purchase_orders`, `purchase_order_items` (statuts §11.1)
- [x] Migration `goods_receipts`, `goods_receipt_items`
- [x] `App\Domain\Purchasing\PurchaseOrderService`
- [x] `App\Domain\Purchasing\GoodsReceiptService` : validation
      transactionnelle, entrée stock automatique, **non rejouable**
- [x] CRUD fournisseurs
- [x] Écrans commandes, réception, contrôle des écarts (Design System §47, §70)
- [x] Tests d'intégration : commande → réception → stock incrémenté
      exactement une fois (double validation refusée)

**Critère atteint :** une réception validée augmente le stock automatiquement ;
elle ne peut pas être validée deux fois. ✅

---

## Phase 4 — Production (MVP 7, 8, 9) ✅

- [x] Migration `recipes`, `recipe_items` (ressource, quantité, unité, mode
      de déduction : quantité / coût cumulé / saisie / monétaire)
- [x] Migration `production_orders`, `production_order_items`
- [x] Migration `production_batches`, `production_outputs`
- [x] `App\Domain\Production\RecipeCalculator` (matières nécessaires,
      ressources cumulées fagot/glace/sel, rendement théorique)
- [x] `App\Domain\Production\ProductionService` : le bloc transactionnel
      du cahier §27 (vérifier → verrouiller → créer → consommer → lots →
      entrées → coûts → audit)
- [x] Calcul d'écart théorique/réel + motif obligatoire (cahier §7.4)
- [x] Création de lot + date limite de vente calculée
- [x] Assistant de production en 4 étapes (Design System §22, §43)
- [x] Composants `RecipePreview`, `ProductionSummary`, `QuantityInput`,
      `ProductSelector`, `Stepper`, `ConfirmDialog` (impact affiché)
- [x] Tests unitaires : recette, rendement, écart, coût
- [x] Tests : production insuffisante bloquée avec message clair

**Critère atteint (cahier §50) :** recette configurable, matières calculées,
rendement configurable, écarts calculés et motivés. ✅

---

## Phase 5 — Ventes (MVP 10) ✅

- [x] Migration `customers`, `sales`, `sale_items`, `payments`,
      `payment_methods`, `day_closings`, `day_closing_items`
- [x] `App\Domain\Sales\SalesService` : déduction du stock produits finis,
      idempotent (clé `sale:{saleId}:item:{itemId}`), référence
      `SALE-{Ym}-{NNNN}`, gestion du rejeu par contrainte unique
- [x] Ventes particulières (produit, quantité, prix par défaut
      `sale_price`, remise, mode de paiement, caissier, `Payment`)
- [x] Ventes grossistes : type `SaleType::Wholesale` sur la vente avec
      prix applicable (`wholesale_price`) — voir ADR-016
- [x] CRUD clients (`/customers`, permission `customer.manage`, soft deletes)
- [x] `App\Domain\Sales\DayClosingService` : clôture journalière
      (produite / vendue / restante / perdue / retournée / écart), unique
      par site et par date (cahier §29)
- [x] Seuil de justification d'écart de clôture configurable
      (`day_closing.gap_justification_threshold`, cahier §15)
- [x] Écrans Ventes, clôture du jour, clients (Design System §67, §72)
- [x] Caisse (`/caisse`) câblée au `SalesService` — catalogue réel
      `FinishedGood` + stock temps réel
- [x] Tests : une vente ne peut pas rendre le stock négatif ; clôture
      unique, écart calculé, observation obligatoire au-delà du seuil

**Critère :** la clôture expose les écarts et déclenche une justification
au-delà du seuil configuré. ✅ (`day_closing.gap_justification_threshold`,
`DayClosingGapJustificationRequiredException`)

---

## Phase 6 — Pertes et péremption (MVP 11, 12) ✅

- [x] Migration `loss_records`, `loss_items` (motif, origine, référence
      `LOSS-{Ym}-{NNNN}`, valorisation FCFA figée, lot tracé)
- [x] `App\Domain\Inventory\ExpiryService` + job planifié
      (`CheckBatchExpiry`, commande `boulangerie:expiry-check` cadencée
      par le scheduler §40)
- [x] Détection des lots à écouler et expirés : statuts `BatchStatus`
      dérivés + alertes `expiry_soon` / `expired` (auto-résolues)
- [x] Passage automatique du reste en perte à la date limite, idempotent
      (`batch-expiry:{lot}:{date}` + garde sous verrou), avec valeur de
      perte (valorisation configurable, §8.2)
- [x] Consommation FEFO des lots à la vente : le lot le plus proche de
      sa date limite part en premier et **un lot expiré ne peut plus
      être vendu** (`LotExpiredException`, message §41)
- [x] Générateur d'alertes : stock faible, critique, cumul atteint,
      expiration proche, expiré, écart de rendement, perte importante
- [x] Écran Pertes (liste filtrable, création, détail) et écran
      Alertes réel avec acquittement (Design System §46, §69)
- [x] Tests : lot expiré invendable, FEFO, job idempotent (deux
      exécutions = une seule perte), règles configurables, permissions

**Critère (cahier §50) :** les dates limites sont calculées, les expirés
identifiés, les invendus transformés en pertes selon la règle configurée.
✅ (`boulangerie.expiry.conversion` : `on_expiry_date` /
`day_after_expiry_date`)

---

## Phase 7 — Grossistes et ristournes (MVP 13)

- [ ] Migration `wholesalers`, `wholesaler_rules`, `wholesaler_rebates`
- [ ] Fiche grossiste complète (zone, prix, quantité habituelle,
      conditions de paiement, date d'adhésion)
- [ ] `App\Domain\Customers\WholesalerRebateService` : paliers
      configurables (quantité / CA / famille / combinaison)
- [ ] Statistiques par grossiste (journalier, mensuel, annuel, évolution,
      taux de régularité)
- [ ] Écrans fiche grossiste + ristournes (Design System §43, §44, §73)
- [ ] Composants `WholesalerSummary`, `RebateProgress`
- [ ] Tests : calcul de ristourne par palier, seuils modifiables

**Critère :** la ristourne est calculée depuis une règle administrable,
sans modification de code.

---

## Phase 8 — Employés et paie

- [ ] Migration `employees`, `employee_contracts`
- [ ] Migration `payroll_periods`, `payroll_items`, `payroll_payments`
- [ ] Postes configurables (cahier §16.2)
- [ ] `App\Domain\Payroll\PayrollService` : salaire de base, primes,
      avances, retenues, net, historique par période
- [ ] Écrans Employés et Paie (Design System §48, §49)
- [ ] Tests : calcul du net, historique non écrasé

**Critère :** un historique de paie par employé et par période existe.

---

## Phase 9 — Charges, rapports, dashboards

- [ ] Migration `expense_categories`, `expenses`
- [ ] Écran Charges avec catégories configurables
- [ ] `App\Domain\Reporting\ReportingService`
- [ ] Rapports : production, stock, achats, ventes, pertes, grossistes,
      charges, paie (cahier §33)
- [ ] Exports PDF / XLSX / CSV (Design System §84)
- [ ] Les 3 dashboards réels (Super-Admin, Production, Vente —
      Design System §15, §16, §17)
- [ ] Sélecteur de période (Design System §83)
- [ ] Graphiques (Design System §39)
- [ ] Tests : chaque rapport répond aux questions du cahier §55

**Critère :** le Super-Admin répond aux 17 questions du cahier §55 sans
intervention technique.

---

## Phase 10 — Optimisation, sécurité, qualité

- [ ] Notifications internes + email (canaux configurables)
- [ ] Queue pour les traitements lourds (exports, recalculs, rapports)
- [ ] Cache de configuration, élimination des N+1, index sur les colonnes
      de recherche
- [ ] Audit avancé : `audit_logs` (utilisateur, action, objet, ancienne et
      nouvelle valeur, IP, date, site) et écran Audit
- [ ] 2FA optionnelle, rate limiting, sauvegardes documentées
- [ ] Transferts inter-sites (cahier §19.3)
- [ ] Revue responsive complète (1440 / 1280 / 1024 / 768 / 390 — §89)
- [ ] Audit d'accessibilité WCAG 2.2 AA
- [ ] Couverture de tests complète (unitaire, fonctionnel, intégration,
      concurrence)
- [ ] Documentation d'exploitation

**Critère de sortie :** `composer ci` vert, checklist §89 entièrement
cochée, critère final §90 satisfait.

---

## Questions métier bloquantes (cahier §54)

Ces points doivent être tranchés avant la phase concernée. Tant qu'ils ne
le sont pas, utiliser la valeur par défaut de `config/boulangerie.php` et
consigner l'hypothèse dans `docs/DECISIONS.md`.

| # | Question | Bloque |
|---|---|---|
| 1 | Définition exacte de « glace » (physique ou coût) | Phase 4 |
| 2 | Définition exacte du « fagot » (stock, coût ou cumul) | Phase 4 |
| 3 | Unité réelle de la levure et de l'améliorant | Phase 4 |
| 4 | Quantité exacte de sel par sac | Phase 4 |
| 5 | Durée limite de vente du pain | Phase 4 / 6 |
| 6 | Règles exactes de péremption | Phase 6 |
| 7 | Traitement des produits partiellement vendus | Phase 6 |
| 8 | Traitement des retours grossistes | Phase 5 / 7 |
| 9 | Invendus retournés par les grossistes | Phase 5 / 7 |
| 10 | Politique de prix particuliers / grossistes | Phase 5 |
| 11 | Règles exactes de ristourne | Phase 7 |
| 12 | Mode de calcul de la masse salariale | Phase 8 |
| 13 | Règles de transfert entre boulangeries | Phase 10 |
| 14 | Stock négatif autorisé ou non | Phase 2 |
| 15 | Modes de paiement à supporter | Phase 5 |
| 16 | Règles fiscales / comptables | Phase 9 |
| 17 | Nécessité d'une caisse POS | Phase 5 |
| 18 | Impression de factures / tickets | Phase 5 / 9 |
| 19 | Intégrations Mobile Money | Phase 10 |
| 20 | Application mobile future | Phase 10 |
