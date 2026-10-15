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

## ADR-020 — Charges et reporting : agrégats SQL, exports sans dépendance

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §33 demande huit rapports (ventes,
  production, stock, achats, pertes, grossistes, charges, paie) et §55
  impose que le Super-Admin réponde à 17 questions précises « sans
  intervention technique ». Le Design System §84 prévoit des exports
  PDF / XLSX / CSV. Les questions §54-16 (règles fiscales) et §54-18
  (impression de documents) ne sont pas encore tranchées, et le projet
  est livré en autonomie : chaque dépendance lourde (dompdf,
  PhpSpreadsheet) est un risque de build et de maintenance.
- **Décisions :**
  1. **Un service d'agrégation unique.** `App\Domain\Reporting\
     ReportingService` calcule les huit rapports par requêtes SQL
     groupées (aucune donnée dénormalisée, aucun cache) et expose
     `questions()` qui produit les 17 réponses §55. Chaque méthode prend
     un `?int $bakeryId` : renseigné = un site, `null` = consolidation
     de tous les sites (réservée au Super-Admin).
  2. **Les exports n'ont aucune dépendance.** Trois formats natifs via
     `App\Support\ReportExport` : CSV (UTF-8 avec BOM, séparateur `;`
     pour Excel francophone), SpreadsheetML 2003 (XML `<Workbook>`
     ouvert par Excel sous `.xls`) et une vue imprimable
     `reports.print` avec `@media print` pour « Imprimer → PDF ».
     Aucun package tiers installé (décision explicite du propriétaire).
     Le même `ReportExport` sert l'export du grand livre des mouvements
     (engagement du ROADMAP Phase 2 reporté en Phase 9).
  3. **Les dates de période sont filtrées en `whereDate()`.** Les
     colonnes `date` sont stockées avec une heure résiduelle selon le
     moteur : un `whereBetween` sur une chaîne laisserait échapper des
     lignes en fin de journée.
  4. **Les catégories de charges sont configurables et
     désactivables.** Les 12 catégories sont amorcées depuis
     `config('boulangerie.expense_categories')` ; une catégorie qui a
     servi à enregistrer une charge se désactive
     (`ExpenseService::deactivateCategory`) et n'est jamais supprimée,
     conformément au §53-11.
  5. **L'export est une permission distincte.** `report.view` ouvre la
     consultation, `report.export` les téléchargements (middleware
     `permission:` sur `/reports/{kind}/export`) ; `report.export` est
     accordée au seul Super-Admin par défaut.
  6. **Un dashboard par mode de fonctionnement.** Le contrôleur choisit
     la vue depuis le rôle connecté : Super-Admin (consolidé + CA par
     site), Production (alertes, écarts), Vente (top produits,
     encaissements) — Design System §15-§17.
- **Justification :** les agrégats SQL évitent toute table de résumé à
  maintenir hors synchro et rendent les 17 questions §55 testables en
  base ; les trois formats d'export couvrent Excel, l'analyse de données
  et l'impression sans introduire de dépendance non auditable.
- **Conséquence :** un futur export PDF de qualité (tableaux multi-pages,
  en-têtes répétés) demandera soit d'affiner `reports.print`, soit
  d'ajouter une dépendance — décision à reprendre en ADR. Les rapports
  sont recalculés à chaque affichage : si le volume justifie un cache,
  il viendra en Phase 10 (queue/cache) sans changer l'API du service.

---

## ADR-021 — Exports en file d'attente, audit automatique, canaux de notification

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** la Phase 10 doit livrer les notifications (§22), la
  file d'attente pour les traitements lourds (§48, §53-13) et l'audit
  avancé (§32). Trois questions restaient ouvertes : un export est-il
  produit pendant la requête ou en arrière-plan ? quels objets sont
  journalisés automatiquement ? qui reçoit les emails d'alerte et qui
  décide des canaux ?
- **Décisions :**
  1. **Les exports passent en queue.** `ExportController::store()`
     enregistre la demande (`pending`) et dispatche `GenerateExport`,
     qui écrit le fichier dans `storage/app/private/exports/` puis passe
     la ligne en `ready` (ou `failed` avec le message). L'écran
     « Téléchargements » (`/exports`) sert le fichier 7 jours, puis la
     commande planifiée `boulangerie:exports-prune` (03:15) supprime les
     fichiers expirés et les échecs de plus de 24 h. Un export
     n'appartient qu'à son demandeur, et la table porte `bakery_id`
     (nullable pour la consolidation) : l'écran est isolé par site comme
     le reste.
  2. **La permission dépend de la nature de l'export.**
     `report.export` pour les 8 rapports, `inventory.view` pour le grand
     livre, `audit.view` pour le journal ; un type inconnu répond 404.
     Hors Super-Admin, le périmètre est toujours borné au site courant
     (jamais la consolidation).
  3. **L'audit automatique est opt-in.** Le trait `AuditsChanges` est
     posé sur les 11 modèles critiques du cahier (produit, recette,
     prix, mouvement de stock, vente, perte, charge, ristourne,
     versement de paie, réception, ordre de production) ; les
     changements de permissions d'un rôle sont journalisés explicitement
     depuis `RoleController`. `AuditLogger` reste le seul point
     d'écriture, l'insertion suit la transaction du modèle, `user_name`
     fige le nom, le mot de passe et le jeton de session ne sont jamais
     consignés, et un changement de `status` nomme l'action
     (`production_order.validated` plutôt que `.updated`).
  4. **Deux canaux de notification, configurables par site.**
     `internal` (écran `/notifications`) et `email` (queue). Les
     valeurs par défaut vivent dans
     `config('boulangerie.notification_channels')` — email activé pour
     `critical`, `expired` et `significant_loss` — et
     `notification_preferences` (une ligne par site et par type) les
     surcharge. Les destinataires sont les utilisateurs **affectés au
     site** ayant `alert.view` ; WhatsApp/SMS reste une extension
     future. L'écran `/settings/notifications` est sous `settings.view`
     (c'est une politique de site, pas un réglage individuel).
  5. **Cache de configuration et de navigation, jamais des rapports.**
     Ce qui coûte cher à chaque requête est mémorisé : l'arbre de menu
     résolu (`App\Support\Navigation` — vérifier que la route de chaque
     entrée existe parcourt toute la table des routes) et les structures
     dérivées de la configuration (`App\Support\Configuration` — plan de
     permissions groupé par module, libellés et permissions des rôles,
     reconstruits par les écrans de rôles et d'utilisateurs). Les clés de
     cache sont **indexées par le contenu** (empreinte de
     `config/boulangerie.php`, empreinte de `config/navigation.php` et de
     la table des routes) : une modification produit une nouvelle clé, il
     n'y a donc ni purge manuelle, ni risque de servir du périmé. Le TTL
     d'un jour ne sert qu'à nettoyer les anciennes clés. La partie
     dépendant de l'utilisateur ou de l'URL (filtre par permission, état
     « actif », URL absolue dont l'hôte dépend de la requête) n'est
     jamais mémorisée. Aucun rapport, aucun agrégat métier n'est mis en
     cache : un rapport en cache masquerait une vente du jour (ADR-020).
  6. **Le N+1 est contrôlé par un test, pas par une intention.**
     `tests/Feature/Performance/QueryBudgetTest.php` mesure le nombre de
     requêtes d'un écran avec un jeu de données réduit, puis élargi, et
     exige le même nombre : la propriété testée est « le coût ne dépend
     pas du nombre de lignes », pas un budget chiffré qui vieillirait.
     Les deux N+1 trouvés par ce test (mode de paiement de chaque vente,
     chiffre d'affaires par site du tableau de bord) ont été corrigés en
     requêtes uniques.
- **Justification :** le travail en arrière-plan rend les gros exports
  utilisables sur un poste modeste et évite les délais d'attente HTTP ;
  l'audit opt-in garde le journal lisible (les tables de référence et
  les lignes de détail ne le polluent pas) ; les préférences par site
  évitent qu'un réglage individuel prive un site de ses alertes.
- **Conséquence :** un worker de queue doit tourner en exploitation
  (`php artisan queue:work`) et le scheduler doit être actif, sinon les
  exports restent `pending` — la purge les supprime alors au bout de
  24 h. Les rapports restant recalculés à chaque affichage, une lenteur
  éventuelle se traiterait par un cache dédié (nouvel ADR), jamais par
  un cache silencieux.

---

## ADR-022 — 2FA TOTP sans dépendance, limitation de débit nommée, sauvegardes documentées

- **Date :** 1er octobre 2026
- **Statut :** acceptée
- **Contexte :** le cahier §31 exige une authentification sécurisée, la
  **2FA optionnelle**, une autorisation par permissions, la protection
  CSRF/XSS/injection, le **rate limiting** et la journalisation des actions
  sensibles ; §47 exige des sauvegardes (base, fichiers, rétention,
  restauration testée, journal, reprise après incident). Le projet est
  livré en autonomie et refuse les dépendances non auditables (ADR-020) :
  ajouter un paquet de 2FA et un générateur de QR, ou un paquet de
  sauvegarde, reviendrait à confier l'authentification et la conservation
  des données à du code tiers non relu.
- **Décisions :**
  1. **TOTP implémenté dans le projet.** `App\Support\Totp` (RFC 6238 :
     HMAC-SHA1, secret Base32 de 160 bits, période 30 s, 6 chiffres,
     tolérance ±1 période pour les dérives d'horloge) est validé par les
     **vecteurs de test de la RFC** — c'est ce qui garantit la
     compatibilité avec Google Authenticator, Authy ou 1Password. Aucun
     paquet externe.
  2. **Pas de QR code embarqué.** L'activation affiche la clé par groupes
     de quatre caractères et le lien `otpauth://` à saisir dans
     l'application. Générer un QR exigerait soit une bibliothèque
     d'encodage, soit un service en ligne — or un service en ligne
     recevrait le secret du compte, ce qui est inacceptable. La saisie
     manuelle est supportée par toutes les applications du marché.
  3. **Le secret est chiffré au repos.** Le secret TOTP et les codes de
     secours sont chiffrés par les casts `encrypted` du modèle `User`, les
     codes de secours sont en outre hachés (`Hash::make`) et consommés à
     l'usage, et les quatre colonnes correspondantes sont exclues de
     `AuditLogger::SENSITIVE`. La sauvegarde de la base **doit** donc
     inclure l'`APP_KEY` (docs/SAUVEGARDES.md §2).
  4. **2FA en libre-service et fermée par défaut.** L'écran
     `/settings/security` (aucune permission : chacun protège son propre
     compte) exige le mot de passe courant pour activer, désactiver ou
     régénérer les codes. Un enrôlement non confirmé ne verrouille pas le
     compte. Le middleware `EnsureTwoFactorIsVerified` ferme toutes les
     pages applicatives tant que le défi n'est pas franchi — sauf le défi,
     la déconnexion et la réinitialisation de mot de passe, sans quoi
     l'utilisateur serait enfermé.
  5. **Limitation de débit nommée, par parcours.** `login` (20/min par IP,
     plus 5 échecs par compte dans `LoginRequest`), `two-factor` (5/min par
     compte — un code à six chiffres se devine en force brute),
     `password-reset` (6/min par IP sur la demande **et** sur la
     réinitialisation) et `exports` (10/min par compte : dix exports
     mobilisent la file d'attente).
  6. **Sauvegardes documentées, pas outillées.** `docs/SAUVEGARDES.md`
     fixe les objectifs (RPO 24 h / 1 h avec binlog, RTO 2 h), le script
     `mysqldump` + `tar` + `rsync` avec contrôle d'intégrité, la rétention
     (7 quotidiennes / 4 hebdomadaires / 12 mensuelles), le journal CSV,
     la procédure de restauration, le test trimestriel et la procédure de
     reprise après incident. La sauvegarde reste une responsabilité
     d'exploitation (cron + supervision), pas une fonctionnalité de
     l'application : aucun paquet de sauvegarde n'est ajouté.
- **Justification :** la 2FA protège l'accès sans dépendre d'une chaîne
  SMS facturée et indisponible hors réseau ; l'implémentation maison reste
  vérifiable contre la RFC ; le chiffrement au repos limite la portée d'une
  fuite de base ; la limitation par parcours protège les seuls points
  réellement attaquables ; documenter les sauvegardes rend la procédure
  auditable et testable sans alourdir l'application.
- **Conséquence :** le repère « second facteur franchi » vit en **session**
  (aucun cookie « remember » de confiance) : une nouvelle session redemande
  le code, ce qui est plus strict. L'horloge du serveur doit être à l'heure
  (NTP) — une dérive de plus de 30 s ferait refuser des codes valides.
  L'envoi d'un QR code, un canal SMS/WhatsApp et l'exigence de la 2FA pour
  certains rôles restent ouverts : ils passeraient par un nouvel ADR.

---

## ADR-023 — Transferts inter-sites : objet à deux sites, sortie et entrée explicites

- **Décision :** un transfert de stock relie **deux** boulangeries et se
  matérialise par une ligne `stock_transfers` portant deux clés étrangères
  (`bakery_id` expéditeur, `destination_bakery_id` destinataire), le
  produit, la quantité, un lot facultatif, le motif, la date métier et le
  cycle `draft → sent → received` (plus `cancelled` avant expédition).
  L'expédition crée un mouvement `transfer_out` chez l'expéditeur, la
  réception un mouvement `transfer_in` chez le destinataire ; les deux
  passent par `StockMovementService::apply()` (seul écrivain du stock),
  avec les clés d'idempotence `transfer:{id}:out` et `transfer:{id}:in`.
- **Contexte :** le cahier §19.3 décrit « un transfert d'une boulangerie A
  vers B, avec sortie chez A et entrée chez B, en brouillon puis expédié
  puis reçu ». Le modèle `StockTransfer` ne peut pas utiliser le trait
  `BelongsToBakery` : son scope global suppose **un** site, or un transfert
  en concerne deux. L'isolation par site doit donc être explicite —
  l'expéditeur voit le transfert (source) et le destinataire aussi, un
  site tiers jamais.
- **Justification :**
  - la sortie n'a lieu qu'à l'expédition et l'entrée à la réception : le
    stock n'est jamais « en l'air » sans trace (cahier §53-11) ;
  - le lot suivi sort du site expéditeur (compteur `transferred_quantity`,
    invariant `initial = disponible + vendu + perdu + transféré` conservé)
    et **renaît** chez le destinataire avec les mêmes dates de production
    et de limite de vente — sans quoi le produit arriverait sans traçabilité
    et pourrait être vendu après péremption. Le lot d'origine n'est jamais
    déplacé : l'historique du site expéditeur reste intact ;
  - un transfert expédié ou reçu ne se supprime pas : il se corrige par un
    transfert inverse ou un ajustement motivé (cahier §53-11).
- **Conséquence :**
  - la résolution de route est bornée aux sites accessibles à
    l'utilisateur (`Route::bind('transfer', …)` dans `AppServiceProvider`) :
    un lien direct vers un transfert étranger renvoie **404**, comme les
    modèles isolés par le scope global, et non 403 ;
  - la Policy distingue les rôles : `transfer.view` pour consulter,
    `transfer.create` pour préparer **et expédier depuis le site
    expéditeur**, `transfer.receive` pour réceptionner **au site
    destinataire**. Le Super-Admin passe par `Gate::before` ;
  - la navigation gagne l'entrée « Transferts » (`inventory/transfers`),
    visible avec `transfer.view` ;
  - le destinataire reste sélectionnable parmi **tous** les sites actifs
    (un transfert relie deux sites, le réceptionnaire n'appartient pas
    forcément à l'équipe de l'expéditeur).

---

## ADR-024 — Recherche globale : palette serveur, permissions par groupe, racine Alpine unique

**Contexte.** La topbar affichait un bouton « Recherche globale » qui ne
déclenchait qu'un message d'accueil : aucune recherche n'existait. En
mobile, deux défauts visibles : le menu burger ne s'ouvrait pas et la
recherche ne faisait rien.

**Causes.** (1) Alpine n'initialise que les sous-arbres dont la racine porte
`x-data` ou `wire:id` : le `@click` du bouton burger, seul élément
d'interactivité de la coquille situé sous **aucune** racine Alpine, n'était
jamais lié — sans aucune erreur. (2) Aucun moteur de recherche n'existait.

**Décision.**
- La coquille applicative (`x-layout.app-shell`) porte `x-data` : elle est
  la racine Alpine de toutes les pages du gabarit applicatif. Un test
  structural (`tests/Feature/Design/AlpineRootTest.php`) vérifie sur le
  rendu réel que toute directive Alpine a un ancêtre `x-data`/`wire:id`.
- La recherche globale est une **palette** ouverte depuis la topbar
  (`$dispatch('open-global-search')`, `Ctrl K` / `Cmd K`, `Échap`), montée à
  la racine de la coquille pour échapper au contexte d'empilement de
  l'en-tête collant. Les résultats se recalculent à la frappe
  (`wire:model.live.debounce.300ms`) : pas de page de résultats
  intermédiaire, pas d'index plein texte.
- Le moteur vit dans `App\Domain\Search\GlobalSearchService`. Il interroge
  dix groupes avec les scopes `search()` existants et applique **côté
  serveur** : permission du module (un groupe sans permission n'est pas
  interrogé), isolation par site (`BakeryScope` pour les modèles rattachés à
  un site, `involvingAny()` explicite pour `StockTransfer`). Le service ne
  renvoie jamais de modèle ni d'identifiant : uniquement des chaînes déjà
  résolues et des URL.
- Bornes : 2 caractères minimum, 64 maximum, 5 résultats par groupe.
- La visibilité du panneau utilise une bascule de classe et non `x-show` :
  Alpine diffère l'affichage par `setTimeout`, ce qui rendait impossible le
  focus immédiat du champ de saisie.

**Conséquences.** Le client ne reçoit que des lignes autorisées — le filtrage
n'est pas une commodité d'affichage. Ajouter un groupe de recherche = une
méthode privée dans le service (permission + site + mapping d'affichage),
sans toucher aux vues. La limite de 5 par groupe impose de préciser la
requête plutôt que de parcourir la onzième ligne.

**Alternatives écartées.** Page `/recherche` dédiée (un aller-retour de plus
pour un usage de navigation rapide) ; index plein texte (MySQL FULLTEXT
impose MyISAM/InnoDB avec index spécifique et une cohérence à maintenir,
pour un besoin de sous-chaîne déjà couvert par les scopes `LIKE`) ;
recherche Alpine côté client (données non autorisées envoyées au
navigateur).

---

## ADR-025 — Création de recettes : formulaire unique, lignes dynamiques, duplication par `?from`

**Contexte.** L'écran `/production/recipes` ne faisait que consulter le
catalogue de nomenclatures ; les recettes étaient amorcées par le seeder
(`RCP-PAIN`). Le besoin : créer une recette avec les mêmes réglages que le
« pain traditionnel » (produit fini, rendement par lot, durées, pertes
autorisées et ressources avec mode de déduction).

**Décision.**
- Routes `production.recipes.create` (GET) et `production.recipes.store`
  (POST), protégées par `permission:recipe.manage` ; l'autorisation est
  aussi vérifiée par `RecipePolicy::create`. L'index reste en `recipe.view`.
- Un `StoreRecipeRequest` valide l'en-tête **et** les lignes : le produit
  cible doit être un produit fini, chaque ressource doit être non finie,
  le mode de déduction est un `RecipeDeductionMode`, et `unit_cost` est
  `required_if` pour les modes monétaire/cumulé.
- `RecipeController` reste mince : transaction `Recipe` + `RecipeItem`,
  positions reindexées à partir de l'ordre soumis.
- La duplication est un **pré-remplissage** (`create?from={id}`), pas un
  nouvel endpoint : le formulaire est identique, la référence repart vierge
  (unicité) et un bandeau indique la source. Un bouton « Dupliquer » sur
  chaque carte de l'index y mène.
- La **modification** (`edit`/`update`, `UpdateRecipeRequest`) réutilise le
  même composant de formulaire (`x-production.recipe-form`). La nomenclature
  est remplacée en bloc dans la transaction (`items()->delete()` puis
  recréation) ; les productions déjà validées ne sont pas touchées, elles
  ne référencent que `recipe_id` (`nullOnDelete`) et portent leurs propres
  lignes. `UpdateRecipeRequest` ne rend l'unicité de la référence stricte
  que vis-à-vis des **autres** recettes (`Rule::unique(...)->ignore(...)`).

**Conséquences.** Aucune donnée n'est écrite sans repasser par le même
chemin de validation. Les recettes restent partagées entre sites (pas de
`bakery_id`), comme le catalogue produits. `unit_cost` nul sur une ressource
physique signifie « prix d'achat du produit au moment de la consommation ».

**Alternatives écartées.** Endpoint `/duplicate` dédié (duplique la logique
d'un formulaire déjà complet) ; formulaire vierge seul (ne répond pas au
« comme la recette du pain traditionnel »).

---

## ADR-026 — Catalogue scindé en deux familles : Produits et Matières premières

**Contexte.** Le catalogue (`/products`) exposait six types (cahier §4.1) dans
un simple menu déroulant. L'usage quotidien distingue surtout deux familles :
les produits et les matières premières.

**Décision.**
- `ProductType::group()` range chaque type dans `products` (produit fini) ou
  `materials` (tout le reste : matière première, semi-fini, consommable,
  emballage, ressource) ; `ProductType::forGroup()` et `groups()` complètent
  la correspondance. Les libellés viennent de `config('boulangerie.product_groups')`.
- `/products` présente deux onglets avec compteurs (`group=products|materials`,
  défaut `products`). Le lien d'onglet conserve la recherche et la catégorie.
- Le filtre `type` est conservé mais **scopé à la famille active** : il
  n'apparaît que si la famille compte plus d'un type (donc jamais dans
  « Produits »).
- Après création, on revient sur la famille du produit créé.
- Le bouton « Nouveau produit » du catalogue ouvre une **modale** qui
  demande la famille (Produits / Matières premières) ; le choix mène au
  formulaire avec la famille et le type pré-sélectionnés (`?group=…`).
- Le **formulaire** de création/édition demande d'abord la **famille**
  (deux choix : Produits / Matières premières) ; la **nature** précise
  (matière première, semi-fini, consommable, emballage, ressource)
  n'apparaît que pour « Matières premières ». Le `type` enregistré en découle
  (champ caché) : « Produits » fixe `produit fini`. Le formulaire s'ouvre par
  défaut sur « Produits ».

**Conséquences.** Aucune migration : le rangement est dérivé du `type`, jamais
stocké. Les écrans et exports qui listent des produits restent inchangés ;
seul l'écran catalogue change de présentation. La requête de comptage par
famille reste constante (deux requêtes), sans dépendre du volume.

**Alternatives écartées.** Ajouter un champ « famille » en base (dupliquerait
le type, source d'incohérence) ; forcer deux types seulement (perte de la
typologie fine du cahier) ; onglet unique avec sections défilantes (moins
lisible au-delà de quelques dizaines de références).

---

## ADR-027 — Génération du code produit (SKU) depuis le formulaire

**Contexte.** Le code produit est obligatoire et unique (cahier §4.2). Le
saisir à la main pour chaque article est fastidieux et source de doublons.

**Décision.**
- `ProductType::skuPrefix()` fixe le préfixe de famille : `MP` (matière
  première), `SF` (semi-fini), `PF` (produit fini), `CONS`, `EMB`, `RES`.
- `GET /products/generate-sku?type=…&name=…` (`permission:product.view` +
  policy `create`) renvoie un code **dérivé du nom** : préfixe de famille +
  nom normalisé (accents translittérés via `Str::slug`, espaces en tirets,
  majuscules), ex. `PF-BAGUETTE`, `MP-FARINE-50-KG`. Un suffixe numérique
  n'est ajouté qu'en cas de collision (`PF-BAGUETTE-2`, `-3`…).
- Le formulaire expose un bouton « Générer » (Alpine + `fetch`), désactivé
  tant que le nom est vide ; il remplit le champ, qui reste éditable et
  obligatoire.

**Conséquences.** Le code proposé n'est **pas réservé** : il n'est jamais
écrit en base par l'endpoint. L'unicité reste garantie par la validation
`Rule::unique` à l'enregistrement (deux générations concurrentes peuvent
suggérer le même code ; la seconde échoue et se régénère). Aucune table ni
compteur séquentiel : le calcul est dérivé du nom et de l'existant.

**Alternatives écartées.** Code strictement séquentiel `PREFIX-0001`
(abandonné : un code dérivé du nom est plus lisible et rejoint la
convention des codes existants `PF-BAGUETTE`, `MP-FARINE`) ; compteur
auto-incrémenté en base (verrou et migration inutiles, risque de trous) ;
génération purement côté client (aucune garantie d'unicité) ; SKU optionnel
auto-généré à l'enregistrement (retire le contrôle de l'utilisateur sur les
codes métier).

---

## ADR-028 — Caisse (POS) : panier en tiroir sur mobile

**Contexte.** La caisse (`/caisse`) était conçue pour un écran large : le
panier était `hidden lg:flex`. Sur téléphone, le panier — et donc le bouton
« Payer » — était **inaccessible**, et les catégories occupaient une colonne
verticale étroite.

**Décision.**
- Le panier est extrait dans un partiel
  (`livewire/sales/partials/cart.blade.php`), rendu **deux fois** : colonne
  fixe en `lg+`, tiroir bas en dessous. Les `wire:key` sont préfixés par un
  `$scope` (`desktop`/`mobile`) pour éviter toute collision de morphing.
- Sur mobile : catégories en **défilement horizontal**, header allégé
  (badges de site/caissier masqués sous `md`), **barre d'action fixe** en bas
  (total + « Panier » + « Payer ») et tiroir Alpine (`cartOpen`, `x-cloak`).
- Le corps réserve `pb-24` sur mobile pour ne rien cacher sous la barre.
- Le tiroir et la barre sont montés dans la **racine Alpine** du composant
  (`x-data="{ cartOpen: false }"`, cf. ADR-024).

**Conséquences.** La caisse reste pleine hauteur (`h-screen`) avec défilement
interne des produits : l'en-tête et la barre d'action restent visibles.
Aucune logique métier modifiée (mêmes méthodes Livewire).

---

## ADR-029 — Tableaux de données : cartes empilées sur mobile

**Contexte.** `x-ui.data-table` — utilisé par 35 écrans — rend un `<table>`
dans un `overflow-x-auto`. Sur téléphone, le tableau (jusqu'à ~810 px de
large) ne tient pas dans les ~356 px utiles : défilement horizontal, colonnes
coupées et libellés hors écran rendent la liste difficile à lire.

**Décision.**
- Sous `md` (`@media (max-width: 767px)`), chaque ligne devient une **carte** :
  `thead` masqué, `tr` en bloc encadré, `td` en ligne « libellé / valeur ».
  La **première cellule** fait office de titre de carte, sans libellé.
- Le composant ne peut pas étiqueter le contenu du slot Blade : les libellés
  de colonnes sont transmis en **variables CSS** (`--dt-label-2`, `-3`, …),
  consommées par `td::before { content: var(--dt-label-N) }`. Les styles vivent
  dans `resources/css/app.css` (tokens de la charte uniquement).
- La prop `stack` (`true` par défaut) permet de désactiver le mode carte.
- Corrigé au passage les en-têtes dupliqués « Produit » des clôtures
  journalières (deuxième colonne = quantités produites → « Production »).

**Conséquences.** Tous les écrans à tableau deviennent lisibles sur téléphone
sans défilement horizontal ; le rendu `md+` est **inchangé** (vérifié au
navigateur en 390 px et 1280 px). Aucune logique métier touchée.
Garde-fou : `tests/Feature/Design/ResponsiveTableTest.php` (rendu, désactivation,
échappement) et `SalesScreenTest` (l'écran des ventes rend bien des cartes).

---

## ADR-030 — Photos de produit : un fichier sur le disque public

**Contexte.** Le cahier §4.2 liste la « photo facultative » du produit. La
colonne `products.photo_path` (nullable), son entrée dans `$fillable` et la
factory existaient déjà — mais rien ne la remplissait ni ne l'affichait.

**Décision.**
- **Stockage** : disque `public`, dossier `products/`, nom de fichier généré
  (`$file->store()`), chemin relatif stocké dans `products.photo_path`. Le lien
  `public/storage` est créé par `php artisan storage:link`, ajouté à
  `composer setup` et à l'installation manuelle du README (ce dossier est
  ignoré par git).
- **Validation** : `photo` nullable, `image` + `mimes:jpeg,jpg,png,webp,gif` et
  `max:4096` (4 Mo), messages en français. Enregistrer un produit sans photo
  reste valide.
- **Cycle de vie** : un nouveau fichier remplace l'ancien ; la case
  `remove_photo` (absente quand il n'y a pas de photo) efface le champ **et** le
  fichier. La suppression du fichier n'a lieu **qu'après** la mise à jour
  réussie. L'orchestration reste dans `ProductController`
  (`storePhoto()` / `forgetPhoto()`) pour ne pas alourdir le modèle.
- **Lecture** : `Product::photoUrl()` renvoie l'URL publique ou `null`.
- **Affichage** : vignette 36 px dans la première colonne du catalogue (icône
  `package` en repli), aperçu 96 px + case de suppression dans le formulaire,
  et pastille ronde en caisse (POS) à la place de l'emoji de la famille.

**Conséquences.** Aucune migration (la colonne était déjà là) et aucune règle
métier touchée. Sans photo, les écrans gardent exactement le même aspect (emoji
en caisse, icône au catalogue). Garde-fous : `tests/Feature/Products/ProductPhotoTest.php`
(9 tests) et 2 tests ajoutés à `PointOfSaleTest`.

---

## ADR-031 — Carte produit de la caisse : conteneur explicite et photo plein cadre

**Contexte.** Le sondage du POS a montré une carte quasi invisible : le bouton
n'avait ni bordure ni fond (transparent sur le canvas), le visuel était réduit
à une pastille ronde de 72 px sur un dégradé crust, et le badge de prix
chevauche le bas de cette pastille. Une fois les photos de produit livrées
(ADR-030), ces 72 px ne mettaient pas la photo en valeur.

**Décision.**
- La carte est un conteneur explicite : `rounded-card border border-line
  bg-surface shadow-card`, survol `hover:border-brand-500 hover:shadow-elevated`,
  en `overflow-hidden` pour que le visuel épouse les coins hauts.
- Le visuel occupe toute la largeur en `aspect-square` : la photo est en
  `size-full object-cover` (léger zoom `group-hover:scale-105`), l'emoji de la
  famille reste le repli (sans photo).
- Le prix quitte la pastille et rejoint un corps aéré : nom sur deux lignes,
  puis ligne prix / état du stock (petite pastille sémantique danger/warning/
  brand). Plus aucun chevauchement.
- Grille : l'écart passe de `1.5` à `2` pour laisser respirer les bordures.

**Conséquences.** La cible tactile et la lisibilité de la photo augmentent
fortement (154 px en desktop, 165 px en mobile, mesurés au CDP, contre 72 px
avant). Aucune règle métier ni requête modifiée — seules la vue et une
assertion de test changent. Garde-fou :
`PointOfSaleTest::test_the_product_card_is_a_structured_container`. Cette
décision remplace la « pastille ronde » d'ADR-030 pour la caisse.

---

## ADR-032 — Identité du site : nom modifiable, logo et favicon

**Contexte.** Le nom d'une boulangerie n'était modifiable que dans
l'administration des sites (`/settings/bakeries`), et rien ne représentait
visuellement une boulangerie : ni logo, ni icône d'onglet. Le propriétaire
demande de pouvoir renommer la boulangerie depuis le tableau de bord, d'y
ajouter un logo, et que ce logo serve de favicon.

**Décision.**
- Migration additive : `bakeries.logo_path` (nullable) — réversible, aucune
  table existante modifiée.
- Le fichier vit sur le disque `public`, dossier `bakeries/` ; seul le
  chemin est persisté (`Bakery::logoUrl()`, comme `Product::photoUrl()`).
  `php artisan storage:link` reste indispensable.
- Le formulaire d'administration (`/settings/bakeries`) passe en
  `multipart/form-data` et gagne une section « Logo et favicon » : aperçu,
  remplacement et case « Supprimer le logo ». Un nouveau fichier remplace
  l'ancien, effacé du disque **après** l'enregistrement réussi ; un envoi
  sans fichier conserve le logo en place.
- `App\Support\Branding::faviconUrl()` résout l'icône de l'onglet : logo du
  site actif, sinon premier site accessible qui en définit un (ordre de
  `BakeryContext::availableBakeries()` : site par défaut puis nom), sinon le
  `favicon.ico` livré. Les trois gabarits (`app`, `auth`, `pos`) déclarent
  `<link rel="icon">`.
- Le tableau de bord ouvre un bandeau « Identité du site » (logo, nom, code)
  avec un raccourci vers `bakeries.edit` ; il n'apparaît que si un site
  précis est ouvert et que l'utilisateur a `update` (Policy, vérifiée côté
  serveur). Le renommage lui-même reste l'action du formulaire
  d'administration : une seule source de vérité du nom.
- La liste d'administration et la barre latérale affichent le logo, avec
  repli sur l'initiale du nom (`Bakery::initial()`) — jamais de blanc.
- Le rôle Super-Admin reste le seul à administrer les sites
  (`BakeryPolicy`) : un gérant renomme son site via un compte Super-Admin.

**Conséquences.** Aucune règle métier ni aucun mouvement de stock n'est
touché. Garde-fou : `tests/Feature/Bakeries/BakeryLogoTest.php` (16 tests) —
téléversement, refus d'un fichier non image, remplacement, suppression,
conservation du logo à défaut de fichier, affichage (liste, formulaire,
tableau de bord) et favicon (site actif, vision consolidée, repli livré).
Les tests téléversent un vrai PNG plutôt que `UploadedFile::fake()->image()`
afin de ne pas dépendre de l'extension GD.

---

## ADR-033 — Identité du site dans les paramètres, réservée au Super-Admin

**Contexte.** ADR-032 avait livré le renommage et le logo d'un site depuis
l'administration des sites (`/settings/bakeries`) et un raccourci « Modifier le
nom ou le logo » sur le tableau de bord. Le propriétaire demande que la
modification des informations d'une boulangerie se fasse **dans les
paramètres** et **uniquement par l'administrateur** : la carte « Général » de
`/settings` affichait encore des champs factices (Nom du site, Code,
Téléphone) qui n'enregistraient rien.

**Décision.**
- La page Paramètres (`/settings`) porte l'identité du **site courant** : nom,
  code interne, adresse, téléphone et logo (aperçu, remplacement, case
  « Supprimer le logo »). Les champs factices sont remplacés par ce
  formulaire ; la devise reste un paramètre global affiché en lecture seule.
- Nouvelle route `PUT /settings/site` (`settings.site.update`), protégée par
  le middleware `permission:bakery.update` **et** par la Policy via
  `UpdateBakeryIdentityRequest` : les deux sont revérifiés côté serveur.
- Le site visé n'est jamais transmis par le formulaire : il est lu dans
  `BakeryContext::current()`. Modifier un site non ouvert, ou sans site
  courant, est refusé — il n'existe aucun champ caché détournable.
- Le reste de la fiche (statut, responsable, notes) reste dans
  l'administration des sites, accessible par le bouton « Gérer les sites » et
  par la navigation (Administration → Boulangeries).
- **Un seul point d'écriture** : `App\Domain\Sites\BakeryProfileService`
  (`create()`, `update()` et le cycle de vie du logo — remplacement, effacement
  après enregistrement réussi) est partagé par `BakeryController` et
  `SettingsController`. Les deux contrôleurs restent minces ; aucun ne
  manipule plus le fichier.
- Le raccourci du tableau de bord (bandeau « Identité du site », ADR-032)
  pointe désormais vers les paramètres : le tableau de bord n'est plus un lieu
  d'administration.
- Les rôles standard gardent `settings.view` pour lire la fiche (le nom du
  site, son code, ses coordonnées) ; sans `bakery.update` ils voient une vue
  en lecture seule et le message « Seul un administrateur peut modifier les
  informations d'une boulangerie ». Masquer le formulaire ne suffit jamais :
  la route et la Policy refusent l'écriture.

**Conséquences.** Aucune migration et aucune règle métier touchée. Un
Super-Admin en vision consolidée (aucun site ouvert) voit un état vide qui
l'invite à choisir un site, plutôt qu'un formulaire sans cible. Garde-fou :
`tests/Feature/Bakeries/SiteIdentitySettingsTest.php` (15 tests) — rendu de la
fiche, lecture seule sans permission, mise à jour, isolation entre sites,
refus sans site courant, unicité du code, nom obligatoire, téléversement /
conservation / suppression du logo, refus d'un fichier non image — et les 16
tests d'ADR-032 (`BakeryLogoTest`) qui continuent de couvrir
`/settings/bakeries`.

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
