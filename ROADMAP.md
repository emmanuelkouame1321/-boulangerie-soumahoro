# ROADMAP — Développement autonome de Boulangerie Soumahoro

> **Comment utiliser ce fichier :** prendre la première case non cochée,
> la traiter en respectant `AGENTS.md`, la cocher, mettre à jour l'en-tête
> de statut, puis passer à la suivante. Ne jamais sauter une phase.

**Statut global : Phase 0 terminée → Phase 1 en cours**

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

## Phase 1 — Socle métier (MVP 1 à 3)

### 1.1 Authentification

- [ ] Installer l'authentification (Breeze Livewire ou implémentation
      maison avec le layout existant)
- [ ] Écran de connexion conforme au Design System §57
- [ ] Écran de déconnexion, mot de passe oublié, expiration de session
- [ ] Redirection par rôle après connexion
- [ ] Envelopper les routes métier dans le middleware `auth`

**Critère :** un visiteur non authentifié est redirigé vers `/login` ;
un utilisateur connecté voit son nom dans la topbar.

### 1.2 Boulangeries (multi-sites)

- [ ] Migration `bakeries` (nom, code, adresse, téléphone, responsable,
      statut, paramètres)
- [ ] Migration pivot `bakery_user`
- [ ] Migration `bakery_settings`
- [ ] Modèle `Bakery` + relations + scope de site
- [ ] `App\Domain\Sites\BakeryContext` : résolution du site courant
      (session), isolation par défaut, Super-Admin = vision consolidée
- [ ] Composant `SiteSwitcher` (Design System §12) — jamais de changement
      silencieux pendant une saisie
- [ ] CRUD boulangeries réservé au Super-Admin
- [ ] Tests : isolation des données entre sites

**Critère :** deux sites ont des stocks indépendants ; les données d'un
site ne fuient jamais vers l'autre.

### 1.3 Unités de mesure

- [ ] Migration `units` (code, libellé, symbole) amorcée depuis
      `config/boulangerie.php`
- [ ] Migration `unit_conversions` (facteur, unité source/cible)
- [ ] Service de conversion d'unités + tests (`1 sac = 50 kg`)

**Critère :** `1 sac de farine` se convertit en `50 kg` dans le calcul.

### 1.4 Produits et matières premières

- [ ] Migration `product_categories`
- [ ] Migration `products` (SKU, type, unité principale, prix d'achat,
      prix de vente, prix grossiste, seuil d'alerte, mode d'alerte, durée
      limite de vente, actif, description)
- [ ] Migration `product_prices`, `product_suppliers`
- [ ] Modèle `Product` avec casts et enum `ProductType`
- [ ] Policy `ProductPolicy`
- [ ] Écrans : liste (recherche, filtres, tri, pagination), création,
      édition, fiche produit (Design System §24, §62)
- [ ] Composants `DataTable`, `TextInput`, `Select`, `FormField`,
      `NumberInput`, `Pagination` (Design System §59)
- [ ] Tests : politique d'accès, création, seuils

**Critère :** le Super-Admin crée une matière première et un produit fini
avec seuil et durée limite de vente, sans toucher au code.

### 1.5 Utilisateurs, rôles et permissions

- [ ] Écran de gestion des utilisateurs (affectation multi-sites)
- [ ] Écran de gestion des rôles et permissions (Design System §74)
- [ ] `UserPolicy`, `RolePolicy`
- [ ] Journal des changements de permission (prépare l'audit Phase 10)
- [ ] Menu latéral réellement filtré par permission (retirer le mode
      dégradé de `App\Support\Navigation`)
- [ ] Tests : un Responsable Vente ne peut pas atteindre une route
      Paie même par requête HTTP directe

**Critère :** aucune permission ne peut être contournée via l'interface
ou une requête directe.

**Critère de sortie de Phase 1 :** `composer ci` vert ; les 3 rôles
voient 3 menus différents ; les données sont isolées par site.

---

## Phase 2 — Stocks (MVP 5)

- [ ] Migration `stocks` (produit, site, quantité, réservé)
- [ ] Migration `stock_movements` (grand livre complet, jamais supprimé)
- [ ] Migration `stock_alert_rules` et `stock_alerts`
- [ ] Migration `stock_reservations`
- [ ] `App\Domain\Inventory\StockMovementService` : unique point d'écriture
      du stock, transactionnel, idempotent, `lockForUpdate`
- [ ] `App\Domain\Inventory\StockService` : stock disponible =
      physique − réservé
- [ ] Recalcul d'alerte à chaque mouvement (mode seuil + mode cumulé,
      périodes configurables)
- [ ] Écran Stocks : stock temps réel, seuils, statuts (Design System §23, §44)
- [ ] Écran Mouvements : historique filtrable, paginé, exportable
- [ ] Composants `StockStatus`, `StockMovementBadge`, `ExpiryIndicator`
- [ ] Tests : aucun stock négatif ; deux mouvements concurrents sur le
      même stock ne se perdent pas ; idempotence

**Critère (cahier §50) :** chaque mouvement est historisé, le stock est
toujours dérivable des mouvements.

---

## Phase 3 — Achats (MVP 4 et 6)

- [ ] Migration `suppliers`, `product_suppliers`
- [ ] Migration `purchase_orders`, `purchase_order_items` (statuts §11.1)
- [ ] Migration `goods_receipts`, `goods_receipt_items`
- [ ] `App\Domain\Purchasing\PurchaseOrderService`
- [ ] `App\Domain\Purchasing\GoodsReceiptService` : validation
      transactionnelle, entrée stock automatique, **non rejouable**
- [ ] CRUD fournisseurs
- [ ] Écrans commandes, réception, contrôle des écarts (Design System §47, §70)
- [ ] Tests d'intégration : commande → réception → stock incrémenté
      exactement une fois (double validation refusée)

**Critère :** une réception validée augmente le stock automatiquement ;
elle ne peut pas être validée deux fois.

---

## Phase 4 — Production (MVP 7, 8, 9)

- [ ] Migration `recipes`, `recipe_items` (ressource, quantité, unité, mode
      de déduction : quantité / coût cumulé / saisie / monétaire)
- [ ] Migration `production_orders`, `production_order_items`
- [ ] Migration `production_batches`, `production_outputs`
- [ ] `App\Domain\Production\RecipeCalculator` (matières nécessaires,
      ressources cumulées fagot/glace/sel, rendement théorique)
- [ ] `App\Domain\Production\ProductionService` : le bloc transactionnel
      du cahier §27 (vérifier → verrouiller → créer → consommer → lots →
      entrées → coûts → audit)
- [ ] Calcul d'écart théorique/réel + motif obligatoire (cahier §7.4)
- [ ] Création de lot + date limite de vente calculée
- [ ] Assistant de production en 4 étapes (Design System §22, §43)
- [ ] Composants `RecipePreview`, `ProductionSummary`, `QuantityInput`,
      `ProductSelector`, `Stepper`, `ConfirmDialog` (impact affiché)
- [ ] Tests unitaires : recette, rendement, écart, coût
- [ ] Tests : production insuffisante bloquée avec message clair

**Critère (cahier §50) :** recette configurable, matières calculées,
rendement configurable, écarts calculés et motivés.

---

## Phase 5 — Ventes (MVP 10)

- [ ] Migration `customers`, `sales`, `sale_items`, `payments`,
      `payment_methods`
- [ ] `App\Domain\Sales\SalesService` : déduction du stock produits finis,
      idempotent
- [ ] Ventes particulières (produit, quantité, prix, remise, mode de
      paiement, caissier)
- [ ] Ventes grossistes (`wholesaler_sales`) avec prix applicable
- [ ] `App\Domain\Sales\DayClosingService` : clôture journalière
      (produite / vendue / restante / perdue / retournée / écart)
- [ ] Écrans Ventes, clôture du jour (Design System §67, §72)
- [ ] Tests : une vente ne peut pas rendre le stock négatif

**Critère :** la clôture expose les écarts et déclenche une justification
au-delà du seuil configuré.

---

## Phase 6 — Pertes et péremption (MVP 11, 12)

- [ ] Migration `loss_records`, `loss_items`
- [ ] `App\Domain\Inventory\ExpiryService` + job planifié (scheduler §40)
- [ ] Détection des lots à écouler et expirés
- [ ] Passage automatique du reste en perte à la date limite,
      idempotent, avec valeur de perte
- [ ] Générateur d'alertes (stock faible, critique, cumul atteint,
      expiration proche, expiré, écart de rendement, perte importante)
- [ ] Écran Pertes + écran Alertes (Design System §46, §69)
- [ ] Tests : un lot expiré ne peut plus être vendu ; le job est
      idempotent (deux exécutions = une seule perte)

**Critère (cahier §50) :** les dates limites sont calculées, les expirés
identifiés, les invendus transformés en pertes selon la règle configurée.

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
