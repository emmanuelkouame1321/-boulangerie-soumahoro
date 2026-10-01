# Journal des décisions

Chaque décision structurante est consignée ici (format ADR court).
Une décision consignée ne peut être modifiée qu'en ajoutant une nouvelle
entrée qui la remplace.

---

## ADR-001 — Architecture frontend : Blade + Livewire

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier des charges laisse ouvert le choix entre
  Blade + Livewire et Inertia.
- **Décision :** Blade + Livewire 4. Pas de SPA, pas d'Inertia.
- **Justification :** ERP métier à formulaires denses et workflows
  transactionnels ; SSR, moins de JS à maintenir, toute la logique reste
  côté serveur — cohérent avec le principe « permissions contrôlées côté
  serveur » et « logique métier hors des composants UI ».
- **Conséquence :** les échanges riches passent par des composants
  Livewire, jamais par une API JS ad hoc.

---

## ADR-002 — Base de données : MySQL en développement

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier recommande PostgreSQL ; XAMPP (MySQL/MariaDB)
  est déjà installé sur le poste de développement.
- **Décision :** MySQL/MariaDB en développement
  (`boulangerie_soumahoro`), PostgreSQL visé en production.
- **Justification :** démarrer sans installation supplémentaire, tout en
  gardant un bloc PostgreSQL prêt à l'emploi dans `.env.example`.
- **Conséquence :** aucune requête SQL propriétaire PostgreSQL ; rester
  sur Eloquent et le Query Builder pour garantir la portabilité. Les
  tests tournent sur SQLite en mémoire (`phpunit.xml`).

---

## ADR-003 — Localisation : français, FCFA, Africa/Abidjan

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `APP_LOCALE=fr`, `APP_FAKER_LOCALE=fr_FR`,
  `APP_TIMEZONE=Africa/Abidjan` (surcharge de `config/app.php`),
  devise unique FCFA sans décimale.
- **Conséquence :** tous les montants de l'interface passent par
  `App\Support\Format::money()`. Séparateur de milliers = espace
  insécable étroite, séparateur décimal = virgule.

---

## ADR-004 — Rôles et permissions : spatie/laravel-permission

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `spatie/laravel-permission` 6. Trois rôles de départ
  (`super-admin`, `production-manager`, `sales-manager`) déclarés dans
  `config/boulangerie.php`.
- **Justification :** permissions fines configurables par le Super-Admin
  (cahier §20.4) sans réinventer un système maison.
- **Conséquence :** le wildcard du Super-Admin est porté par
  `Gate::before` dans `AppServiceProvider`, pas par une permission en
  base. Les alias de middleware `role`, `permission` et
  `role_or_permission` sont déclarés dans `bootstrap/app.php`.

---

## ADR-005 — Tokens du Design System portés par Tailwind 4 `@theme`

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** la palette, les rayons et les ombres du design system
  sont déclarés une seule fois dans `resources/css/app.css` via `@theme`.
  La convention `tailwind.config.js` du §58 du design system est adaptée
  à la génération de thème de Tailwind 4.
- **Conséquence :** interdiction absolue d'écrire une couleur, un rayon ou
  une ombre en dur dans une vue. Ajouter le token puis l'utiliser.

---

## ADR-006 — Configuration métier centralisée

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Décision :** `config/boulangerie.php` porte les valeurs d'amorçage,
  les listes fermées (types de mouvement, statuts, unités, modes de
  paiement) et le plan de rôles. `config/navigation.php` porte le menu.
- **Justification :** cahier §53.7 — les paramètres métier ne doivent pas
  être codés en dur.
- **Conséquence :** ces valeurs deviendront progressivement
  administrables en base (Phase 1 et 9) ; le fichier de configuration
  restera la source des valeurs d'amorçage et des libellés.

---

---

## ADR-007 — Authentification maison plutôt que Breeze

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier des charges laisse le choix ; Breeze fournit un
  scaffolding d'authentification clé en main.
- **Décision :** authentification écrite à la main — `LoginController`,
  `LogoutController`, `PasswordResetLinkController`,
  `NewPasswordController` — sur le layout et les composants existants.
- **Justification :** Breeze installe son propre layout, ses propres vues
  et ses propres classes Tailwind, en contradiction avec le Design System
  (« ne pas créer de styles concurrents »). Le coût d'écriture est faible
  et le contrôle total.
- **Conséquence :** messages d'erreur en français dans `lang/fr/`. La
  limitation des tentatives est double : 5 par couple email + IP dans
  `LoginRequest`, 20/min par IP via le limiteur nommé `login`.

---

## ADR-008 — Isolation par site via un scope global Eloquent

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** le cahier impose « les sites sont isolés par défaut »
  (principe §53.8) sans préciser le mécanisme.
- **Décision :** trait `App\Models\Concerns\BelongsToBakery` + scope
  global `App\Models\Scopes\BakeryScope`, adossés à
  `App\Domain\Sites\BakeryContext` (singleton).
- **Comportement :**
  - Super-Admin sans site sélectionné → vision consolidée, pas de filtre ;
  - Super-Admin avec site sélectionné → filtré sur ce site ;
  - autre rôle → toujours filtré ; **si aucun site n'est résolu, le scope
    ferme l'accès (`1 = 0`) plutôt que de l'ouvrir** ;
  - hors requête HTTP authentifiée (CLI, queue, seeders) → pas de filtre,
    les traitements devant poser explicitement leur contexte.
- **Justification :** le repli fail-closed évite qu'une erreur de
  configuration expose les données d'un autre site. C'est le point le plus
  sensible du multi-site.
- **Conséquence :** `App\Support\Navigation` conserve temporairement un
  mode dégradé (menu complet sans utilisateur) tant que l'appelant n'est
  pas authentifié ; à retirer en Phase 1.5.

---

## ADR-009 — Composants Livewire classiques (pas mono-fichier)

- **Date :** 30 septembre 2026
- **Statut :** acceptée
- **Contexte :** Livewire 4 génère par défaut des composants mono-fichier
  dans `resources/views/components/`.
- **Décision :** utiliser `php artisan make:livewire <Module>/<Nom> --class`.
- **Justification :** composants testables avec `Livewire::test()`,
  cohérents avec le découpage `app/Livewire/<Module>/` déjà documenté, et
  sans ambiguïté avec les composants Blade anonymes qui occupent déjà
  `resources/views/components/`.
- **Conséquence :** ne pas laisser un `make:livewire` sans `--class`
  créer un fichier `⚡*.blade.php` dans `components/`.

---

## ADR-010 — Charte visuelle : BakeryOS (charte caisse)

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** l'ancien design system (`design_system_boulangerie_soumahoro.md`)
  et l'ancien prototype ont été retirés du dépôt au profit de
  `BakeryOS – Prototype (charte caisse).html`. Cette charte impose une
  palette verte (primaire) + bleue (contextuelle), Roboto, des boutons en
  majuscules à rayon 4 px, des cartes à rayon 8 px et une barre d'accent
  supérieure.
- **Décision :** la charte BakeryOS devient la source de vérité visuelle.
  Les tokens sont portés par `resources/css/app.css` (`@theme`), les
  composants par `resources/views/components/ui|layout`. La logique métier
  reste dans `app/Domain/` — la charte ne touche que la présentation.
- **Conséquence :** interdiction maintenue d'écrire une couleur, un rayon
  ou une ombre en dur. Le vocabulaire de tokens sémantiques (`canvas`,
  `surface`, `line`, `ink`, `brand`, `context`, `success`…) est conservé,
  seule leur valeur change.

---

## ADR-011 — Périmètre de l'intégration du prototype

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Décision :** l'intégration du prototype livre la charte, le shell et
  **tous** les écrans du prototype, ainsi que le module Caisse (POS)
  (`/caisse`). Les écrans sont des interfaces de référence protégées par
  permission ; leur branchement aux services métier suit la ROADMAP
  (achats Phase 3, production Phase 4, ventes Phase 5, etc.).
- **Justification :** le prototype définit la composition visuelle de
  chaque module ; livrer les écrans tôt fige la navigation, les
  permissions et les composants partagés, sans introduire de règle métier
  prématurée.
- **Conséquence :** `config/boulangerie.php` porte désormais un catalogue
  `permissions` (union des permissions de module) créé par le seeder ; un
  rôle `cashier` est ajouté. Le catalogue produits du POS est un jeu de
  démonstration en attendant le modèle `Product` (Phase 1.4) et le domaine
  Ventes (Phase 5).

---

## ADR-012 — Fin de la Phase 1 : produits partagés et unités administrées

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Décisions :**
  1. **Catalogue produits partagé.** `products` ne porte pas de `bakery_id` :
     le catalogue est commun à tous les sites, seul le stock est propre à un
     site (cahier §3.1). Le champ « site(s) autorisé(s) » du cahier §4.2 sera
     modélisé par un pivot de sites en Phase 2 si besoin.
  2. **`product_suppliers` reporté en Phase 3.** La table `suppliers` du
     cahier §24 n'existe pas encore : créer la relation à sens unique
     produirait un code mort.
  3. **Prix en entiers FCFA**, historisés dans `product_prices` ; le prix
     courant reste sur `products`. Jamais de `float`.
  4. **`UnitConversionService` en décimal exact (BCMath)** : conversion
     directe, inverse et chaînée par parcours de graphe borné à 4 niveaux,
     facteur calculé à 16 décimales puis arrondi de présentation.
  5. **Mode dégradé de `Navigation` retiré** : sans utilisateur, aucune
     entrée permissionnée n'est affichée.
- **Justification :** le cahier impose le catalogue et les conversions
  configurables ; ces choix permettent de livrer la Phase 1 sans introduire
  de règle métier prématurée tout en gardant le fail-closed.
- **Conséquence :** à réévaluer dès la Phase 2 (stock par site) et la
  Phase 3 (fournisseurs).

---

## ADR-013 — Stock : grand livre + matérialisation verrouillée

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §3.2 et §27 à §29 imposent une source de
  vérité unique du stock, un grand livre traçable, des transactions et
  l'idempotence des opérations critiques.
- **Décisions :**
  1. **Double représentation.** `stock_movements` est le grand livre
     (vérité, jamais supprimé — `restrictOnDelete`, aucune mise à jour) ;
     `stocks` n'est qu'un cache matérialisé par (produit, site)
     reconstituable depuis les mouvements.
  2. **Point d'écriture unique.** `StockMovementService::apply()` est le
     seul code autorisé à écrire `stocks.quantity`. Il exige une quantité
     strictement positive, dérive le sens du type, refuse un stock
     négatif (sauf `guards.allow_negative_stock`), verrouille la ligne
     (`lockForUpdate`) et journalise `quantity_after`.
  3. **Idempotence par clé.** Un `idempotency_key` unique est persisté ;
     rejouer la même clé renvoie le mouvement existant au lieu de le
     rejouer (y compris en cas de course sur l'index unique).
  4. **Création de la ligne de stock.** `insertOrIgnore` puis `lockForUpdate`
     évitent la fenêtre de course à la première écriture d'un couple
     (produit, site).
  5. **Réservations.** `stock_reservations` immobilise sans consommer ;
     `StockReservationService` réserve / libère / consomme (idempotent),
     le disponible valant « physique − réservé » (cahier §37.3).
  6. **Alertes.** `stock_alert_rules` + `stock_alerts` : recalcul après
     chaque mouvement. Règle du site prioritaire sur la règle globale ;
     à défaut de règle, le seuil porté par le produit fait repli. Une
     condition disparue résout l'alerte automatiquement.
  7. **Décimal exact.** Nouveau `App\Support\Decimal` (BCMath) partagé
     par les services de stock ; jamais de `float`.
- **Justification :** séparer la vérité (mouvement) de la performance
  (cache) permet de vérifier le second par le premier — test
  `test_the_stock_is_derivable_from_the_ledger`.
- **Conséquence :** `ignoreErrors` retiré de `phpstan.neon` (le trait
  `BelongsToBakery` est désormais utilisé). Les transferts inter-sites
  restent en Phase 10 ; les lots et la péremption en Phases 4 et 6.

---

## ADR-014 — Achats : réception non rejouable et statut de commande dérivé

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §11.3 impose qu'une réception validée augmente
  le stock automatiquement et ne puisse jamais l'augmenter deux fois, même
  en cas de double clic, de rafraîchissement ou de rejeu réseau. Le §27
  impose transactions, verrouillage et idempotence.
- **Décisions :**
  1. **Point d'entrée unique du stock.** `GoodsReceiptService::validate()`
     est le seul code qui transforme une réception en entrée de stock. Il
     appelle `StockMovementService::apply()` (type `SupplierIn`) : aucune
     écriture directe de `stocks.quantity` depuis le module Achats.
  2. **Non rejouabilité par état, pas seulement par clé.** La validation
     ouvre une transaction, recharge la réception
     (`withoutBakeryScope()->lockForUpdate()`) et refuse si
     `validated_at !== null` ou si le statut est déjà `validated`
     (`GoodsReceiptAlreadyValidatedException`). La garde est évaluée
     **dans** la transaction et sous verrou, donc une seconde soumission
     concurrente échoue proprement.
  3. **Idempotence par ligne.** Chaque mouvement porte une clé
     `goods-receipt:{receiptId}:item:{itemId}` : même si la garde d'état
     était contournée, le grand livre ne doublerait pas la quantité.
  4. **Statut de commande dérivé.** Le statut de `PurchaseOrder` n'est pas
     saisi : `recalculateStatus()` le calcule à partir des quantités
     reçues (toutes les lignes couvertes → `validated`, sinon
     `partially_received`). Un statut dérivé ne peut pas désynchroniser
     l'avancement réel.
  5. **Références générées côté serveur.** `PO-{Ym}-{NNNN}` et
     `GR-{Ym}-{NNNN}`, produites dans la transaction ; jamais saisies par
     l'utilisateur.
  6. **Total et lignes en FCFA entiers.** `lineTotal()` arrondit au franc
     (`bcadd($raw, '0.5', 0)`) ; les quantités restent en décimal exact
     BCMath.
- **Justification :** séparer la garde d'état (métier, lisible) de la
  clé d'idempotence (filet technique) couvre à la fois l'usage normal et
  les cas de course, sans faire reposer la correction sur une seule
  protection.
- **Conséquence :** la réception partielle est supportée et réorientée
  vers une seconde réception (`isReceivable()`, `remainingQuantity()`) ;
  le contrôle des écarts est exposé à l'écran de détail. La commande
  affichée comme `received` reste réceptionnable pour couvrir un écart.

---

## ADR-015 — Production : ressources typées, lot unique et écart motivé

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §5.3 distingue quatre natures de ressources, le
  §7.3 décrit un bloc transactionnel de validation, le §7.4 impose de
  conserver l'écart et son motif, et le §8 impose la création d'un lot à
  chaque production de produit fini. Les questions métier 1 à 5 du §54
  (glace, fagot, levure, sel, durée limite du pain) restent ouvertes.
- **Décisions :**
  1. **Ressources typées par un mode de déduction.**
     `RecipeDeductionMode` porte la distinction du §5.3 : `quantity`
     (stock déduit), `monetary` (coût configuré, pas de stock),
     `cumulated` (cumul, pas de stock) et `manual` (quantité réelle
     saisie). Seules les ressources physiques génèrent un mouvement.
  2. **Glace et fagot modélisés comme produits de type `Resource`.**
     Leur coût vit dans `recipe_items.unit_cost` (FCFA par lot) ; la
     question « quantité physique ou coût » est tranchée côté coût, la
     valeur restant administrable.
  3. **Recette partagée entre sites.** `recipes` ne porte pas de
     `bakery_id` (comme le catalogue produits, ADR-012) ; les paramètres
     propres à un site passent par la production.
  4. **Validation transactionnelle et non rejouable.**
     `ProductionService::validate()` verrouille l'ordre, refuse un ordre
     déjà validé ou annulé, consomme les matières, crée le lot et l'entrée
     de produit fini, puis calcule le coût. Chaque mouvement porte une clé
     `production:{id}:item:{id}` (sortie) ou `production:{id}:output`
     (entrée). `produce()` enveloppe création et validation dans une
     **seule** transaction : un échec n'laisse aucun ordre orphelin.
  5. **Écart motivé obligatoire.** L'écart vaut « réel − théorique ».
     `guards.require_production_gap_reason` exige un motif configurable
     (`config('boulangerie.production_gap_reasons')`) dès que l'écart est
     non nul ; un écart nul efface le motif.
  6. **Un lot par production.** Une production crée un lot (`initial`,
     `available`, `sold`, `lost`, dates, statut `BatchStatus`). Sa date
     limite vaut recette → produit → `production.default_shelf_life_days`.
- **Justification :** typer la ressource permet de calculer les besoins et
  les coûts sans branchement sur un libellé ; la garde d'état couplée à la
  clé d'idempotence couvre l'usage normal et les courses (même logique
  qu'ADR-014).
- **Conséquence :** la question §54-5 (durée limite du pain) reste
  configurable par produit et par recette ; la transformation des invendus
  en pertes est traitée en Phase 6 avec `expiry-indicator`.

---

## ADR-016 — Ventes : sortie idempotente, type de vente et clôture unique

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §13 impose une vente qui déduit le stock sans
  jamais le rendre négatif, §15 décrit la clôture journalière avec
  justification des écarts importants, et §29 impose l'idempotence des
  opérations critiques. Les questions métier 8, 9, 10, 15, 17 et 18 du
  §54 (retours grossistes, prix, modes de paiement, caisse, tickets)
  restent ouvertes.
- **Décisions :**
  1. **Une vente = une sortie `SaleOut` idempotente par ligne.**
     `SalesService::record()` ouvre une transaction, crée la vente et ses
     lignes, puis écrit le stock via `StockMovementService` avec une clé
     `sale:{saleId}:item:{itemId}`. Le stock négatif reste refusé par le
     service de stock (ADR-013) : la vente échoue proprement avec un
     message métier, jamais un stock négatif.
  2. **Rejeu détecté par contrainte unique.** `sales.idempotency_key` est
     unique : un double clic ou un retry réseau rejoint la vente
     existante (`UniqueConstraintViolationException` → lecture de la
     vente) au lieu d'en créer une seconde.
  3. **Pas de table `wholesaler_sales`.** Le §13 décrit les ventes
     grossistes avec « prix applicable » ; le type `SaleType` (retail /
     wholesale) porte cette distinction sur `sales`, et le prix par
     défaut se déduit du produit (`sale_price` ou `wholesale_price`).
     Une table séparée dupliquerait paiements, lignes et clôture. Les
     ristournes et fiches grossistes restent en Phase 7.
  4. **Clôture unique par site et par date.** Index unique
     `[bakery_id, closing_date]` + garde dans la transaction
     (`DayClosingAlreadyExistsException`). L'écart vaut « restant saisi −
     (produit − vendu) » par produit ; les totaux sont matérialisés sur
     `day_closings`.
  5. **Justification d'écart au-delà d'un seuil.** Cahier §15 : « une
     différence importante peut demander une justification ».
     `day_closing.gap_justification_threshold` (défaut 10 unités, 0 =
     tout écart) rend l'observation obligatoire
     (`DayClosingGapJustificationRequiredException`), contrôlée dans la
     transaction avant toute écriture.
  6. **Paiements en lignes.** `payments` (méthode, montant, date) lié à
     la vente, méthodes typées par la table `payment_methods` amorcée
     depuis la config (+ `credit` « À crédit »).
  7. **Ordre des middleware.** `ResolveBakeryContext` précède
     `SubstituteBindings` (priority list de `bootstrap/app.php`) : sans
     cela, un route model binding sur un modèle `BelongsToBakery` échoue
     en 404 (scope fail-closed) au premier appel d'une session.
- **Justification :** réunir type, paiements et clôture sur le même
  modèle évite des jointures fragiles ; la clé unique et le
  `StockMovementService` (ADR-013) suffisent à garantir « jamais deux fois
  » sans rejeu applicatif.
- **Conséquence :** les retours et invendus grossistes (questions §54-8
  et 9) s'appuieront sur ce modèle en Phase 7 (annulation compensatoire,
  jamais de suppression). L'impression de ticket (§54-18) reste en Phase 9.

---

## ADR-017 — Pertes et péremption : opération historique, conversion idempotente

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §41 impose qu'un lot périmé ne soit plus
  vendable, §11 / §50 veulent que les invendus et périmés soient
  transformés en pertes tracées (valeur, motif, date), §40 attend un
  traitement planifié de l'expiration, et §54-6 / §54-7 (règles exactes de
  péremption, produits partiellement vendus) restent ouverts.
- **Décisions :**
  1. **Une perte est une opération de stock historique et valorisée.**
     `LossService::record()` écrit une sortie `LossOut` via
     `StockMovementService` (ADR-013) avec une clé
     `loss:{recordId}:item:{itemId}` : le stock suit le flux normal,
     jamais une écriture directe, et une perte ne se corrige que par une
     opération compensatoire, jamais par suppression.
  2. **Une perte par ligne de produit, sans lot obligatoire.** L'écran de
     saisie ne demande pas de lot : `LotService::attributeToLots()` impute
     la quantité aux lots en FEFO (expirés compris, car une perte de
     périmé doit vider les lots concernés). Un lot éventuellement désigné
     et insuffisant lève `InsufficientLotException`.
  3. **Le lot périmé n'est plus vendable.** `LotService::sellFromLots()`
     exclut les lots expirés : une vente qui devrait s'y appuyer échoue
     (`LotExpiredException`, cahier §41) au lieu de sortir un produit
     impropre. Le stock physique insuffisant hors expirés reste traité par
     `InsufficientStockException`.
  4. **Ordre de verrouillage : lots puis stock.** Purge de péremption,
     vente et perte s'appuient toutes sur le même ordre (lots ordonnés
     FEFO verrouillés avant la ligne de stock) pour éviter les
     interblocages en concurrence.
  5. **La conversion des lots expirés est idempotente.**
     `ExpiryService::convertExpired()` regroupe les lots expirés du jour en
     une perte `LossReason::Expiry` / `LossOrigin::Expiry` et écrit le
     mouvement avec la clé `batch-expiry:{lotId}:{date}` : une seconde
     exécution le même jour rejoint la perte existante au lieu d'en créer
     une autre. Le job `CheckBatchExpiry` peut donc être rejoué sans
     risque.
  6. **Règle de péremption configurable.** `expiry.conversion`
     (`on_expiry_date` = périme le jour même, `day_after_expiry_date` =
     périme le lendemain) et `expiry.soon_days` (défaut 1) portent le
     §54-6 ; `LossService` valorise à `loss.valuation_field`
     (`purchase_price` par défaut) plutôt qu'un prix de vente, car une
     perte est un coût, et la valeur est arrondie au franc
     (`Decimal::toFrancs`).
  7. **Deux familles d'alertes.** Les alertes *conditionnelles*
     (`Low`, `Critical`, `Cumulated`) sont recalculées à chaque passage de
     `StockAlertService` ; les alertes *événementielles* (`ExpirySoon`,
     `Expired`, `SignificantLoss`, `ProductionGap`) ne sont ouvertes que
     lors de l'événement puis **auto-résolues** (`resolve()`) quand la
     condition disparaît. Aucune alerte n'est supprimée. Le recalcul ne
     referme jamais une alerte événementielle encore en cours.
- **Justification :** réutiliser `StockMovementService` et l'idempotence
  par clé garde une seule source de vérité pour le stock ; imputer en
  FEFO sans imposer de lot à l'utilisateur respecte la saisie réelle en
  boutique tout en gardant la traçabilité par lot.
- **Conséquence :** les questions §54-6 et §54-7 sont couvertes par des
  valeurs configurables (hypothèse par défaut retenue). Les produits **non
  tracés par lot** ne sont pas imputés (`LotService` retourne `[]`) : le
  stock fait foi, comportement voulu à documenter pour la Phase 7.

---

## ADR-018 — Grossistes et ristournes : paliers administrables, base CA

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §12 décrit une fiche grossiste complète et un
  historique par client ; le §13 exige des ristournes configurables, dont
  les seuils et taux « doivent être administrables », et le §37.5 résume
  la formule à « ristourne = base éligible × taux applicable ». La
  question §54-11 (règles exactes de ristourne) n'est pas tranchée.
- **Décisions :**
  1. **Une table grossiste dédiée, reliée au compte client.**
     `wholesalers` porte la fiche §12.1 et un `customer_id` optionnel
     (unique) vers `customers`. Les ventes grossistes existantes portent
     déjà `customer_id` (ADR-016) : ce lien permet de calculer la
     ristourne sur l'historique réel sans créer de table de ventes
     parallèle ni modifier `sales`.
  2. **Les paliers sont des données, pas du code.** `wholesaler_rules`
     stocke base, famille éventuelle, seuil bas, seuil haut et taux en
     pourcentage. Ajouter ou modifier un palier change le résultat sans
     déploiement, ce qui satisfait le critère de la Phase 7.
  3. **Le palier est choisi par la base, la ristourne porte sur le CA.**
     Quel que soit le critère de sélection (quantité, CA, famille,
     combinaison), la « base éligible » est le chiffre d'affaires
     grossiste de l'exercice (filtré par famille le cas échéant) : on ne
     multiplie pas une quantité par un pourcentage. Pour la base
     « combinaison », seuil bas et haut s'appliquent simultanément à la
     quantité et au CA.
  4. **Trois états, historiques préservés.** `RebateStatus` =
     théorique → validée → versée. Le recalcul met à jour tant que la
     ristourne est théorique ; une ristourne validée ou versée est figée
     (`RebateAlreadyValidatedException`) et une ristourne est unique par
     grossiste et par exercice (`[wholesaler_id, period_year]`), §53-11.
  5. **Un palier n'est jamais supprimé.** « Désactiver » un palier le
     passe en `is_active = false` plutôt que de le supprimer : les
     ristournes passées gardent leur référence.
- **Justification :** la règle du cahier est reproduite telle quelle ; des
  paliers en base couvrent les quatre critères demandés sans branche de
  code spécifique ; réutiliser `customer_id` évite de dupliquer l'axe de
  vente déjà livré en Phase 5.
- **Conséquence :** l'hypothèse §54-11 retenue est « palier sur la base de
  calcul, ristourne = CA éligible × taux », entièrement configurable. Les
  retours et invendus grossistes (§54-8 et 9) resteront des opérations
  compensatoires, sans influence sur la ristourne tant qu'ils ne sont pas
  modélisés.

---

## ADR-019 — Paie : période unique, net calculé, historique figé

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §16 décrit une fiche employé avec poste et
  contrat configurables, et le §17 impose de gérer salaire de base,
  primes, avances, retenues et net, avec « un historique de paie par
  employé et par période ». Le §53-11 interdit d'écraser les données
  historiques, et la question §54 reste silencieuse sur la période
  (mensuelle ou autre).
- **Décisions :**
  1. **La période de paie est mensuelle et unique par site.**
     `payroll_periods` porte `[bakery_id, period_year, period_month]`
     unique et une référence `PAY-{Ym}`. Ouvrir une période existante la
     retourne au lieu d'en créer une seconde.
  2. **Un historique « un employé, une période ».**
     `payroll_items` est unique sur `[payroll_period_id, employee_id]` :
     la ligne est mise à jour tant que la période est en brouillon, puis
     figée. `payroll_payments` conserve chaque versement (mode, montant,
     date) sans jamais l'écraser.
  3. **Le net est `base + primes − avances − retenues`, jamais négatif.**
     `PayrollService::net()` borne le résultat à zéro : une avance
     supérieure au salaire ne produit pas de net négatif.
  4. **Génération idempotente depuis les employés actifs.**
     `generate()` crée une ligne par employé `active` du site
     (`firstOrCreate`) sans écraser les ajustements déjà saisis.
  5. **La validation fige, le versement clôt.** Trois statuts
     (`draft` / `validated` / `paid`). Toute modification d'une période
     verrouillée lève `PayrollPeriodLockedException` ; le versement exige
     une validation préalable (`PayrollPeriodNotValidatedException`) et
     une période sans ligne ne peut être validée
     (`EmptyPayrollPeriodException`).
  6. **Postes et contrats en configuration.** Les listes `positions` et
     `contract_types` de `config/boulangerie.php` remplacent une table
     dédiée : elles sont amorçables et ne nécessitent pas de migration
     pour évoluer (le ROADMAP §8 ne prévoit pas de table de postes).
- **Justification :** une ligne par employé et par période rend
  l'historique interrogeable sans jointure fragile ; figer à la
  validation reproduit la règle « une fois validée, on n'y touche plus »
  du cahier.
- **Conséquence :** une période rectificative éventuelle devra être
  modélisée comme une nouvelle ligne ou une nouvelle période, jamais par
  écrasement. Les heures supplémentaires et absences (§17, « si
  activées ») restent hors périmètre et n'ont pas de colonne dédiée.

---

## Hypothèses à confirmer

Le cahier des charges §54 liste 20 décisions métier à valider avec le
propriétaire du projet. Voir le tableau en fin de `ROADMAP.md` pour le
rattachement de chacune à une phase.

Tant qu'une question n'est pas tranchée :

1. utiliser la valeur par défaut de `config/boulangerie.php` ;
2. ne jamais la coder en dur dans un service ou une vue ;
3. consigner l'hypothèse retenue dans une nouvelle entrée ADR.

### Hypothèses actuellement retenues

| Sujet | Hypothèse par défaut | Statut |
|---|---|---|
| Stock négatif | interdit (`guards.allow_negative_stock = false`) | à confirmer |
| Clé d'idempotence | obligatoire sur les opérations critiques | à confirmer |
| Glace / fagot / sel | ressources cumulées en FCFA par sac | à confirmer |
| Durée limite de vente | configurable par produit | à confirmer |
| Règles de péremption | `expiry.conversion` + `expiry.soon_days` (ADR-017) | à confirmer |
| Produits partiellement vendus | imputation FEFO, lot non obligatoire (ADR-017) | à confirmer |
| Règles de ristourne | palier sur la base de calcul, ristourne = CA éligible × taux (ADR-018) | à confirmer |
| Période de paie | mensuelle, unique par site ; net = base + primes − avances − retenues (ADR-019) | à confirmer |
| Modes de paiement | espèces, mobile money, virement, autre | à confirmer |
