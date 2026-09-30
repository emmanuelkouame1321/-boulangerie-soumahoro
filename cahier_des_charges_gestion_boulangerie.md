# Cahier des charges fonctionnel et technique

## Logiciel complet de gestion de boulangerie --- Laravel + Tailwind CSS

**Version :** 1.0\
**Date :** 30 septembre 2026\
**Technologies cibles :** Laravel, PHP, PostgreSQL/MySQL, Tailwind CSS,
Blade/Livewire ou Inertia selon décision technique\
**Type :** Application web de gestion intégrée de boulangerie,
multi-sites, multi-rôles et multi-utilisateurs

------------------------------------------------------------------------

# 1. Présentation du projet

## 1.1. Contexte

Le projet consiste à développer un logiciel complet de gestion de
boulangerie permettant de piloter de manière centralisée et cohérente :

-   les matières premières ;
-   les fournisseurs ;
-   les commandes d'approvisionnement ;
-   les stocks par boulangerie/magasin ;
-   les recettes et nomenclatures de production ;
-   la production de pains et produits de boulangerie/pâtisserie ;
-   les quantités théoriques et réellement produites ;
-   les ventes aux grossistes et aux particuliers ;
-   les pertes et invendus ;
-   les ristournes annuelles des grossistes ;
-   les charges d'exploitation ;
-   les employés et la paie ;
-   les alertes ;
-   les tableaux de bord et statistiques ;
-   la gestion de plusieurs boulangeries avec stocks indépendants ;
-   les droits et responsabilités des utilisateurs.

L'objectif est d'obtenir une **source de vérité unique**, avec une
traçabilité de chaque mouvement et une synchronisation cohérente entre
achats, stocks, production, ventes, pertes et finances.

## 1.2. Objectifs

Le logiciel devra notamment permettre :

1.  de connaître le stock réel de chaque matière première et produit
    fini ;
2.  de déduire automatiquement les matières premières lors d'une
    production ;
3.  de calculer automatiquement les quantités de produits finis
    théoriquement attendues ;
4.  de gérer les produits périssables selon leur durée limite de vente ;
5.  d'enregistrer automatiquement les pertes liées aux invendus arrivés
    à échéance ;
6.  de suivre les fournisseurs et les commandes de matières premières ;
7.  d'ajouter automatiquement au stock les produits reçus et validés ;
8.  de gérer plusieurs boulangeries indépendamment ;
9.  de centraliser la supervision par le Super-Admin ;
10. de sécuriser les opérations selon le rôle de chaque utilisateur ;
11. de fournir des statistiques fiables et en temps réel ;
12. de conserver un historique auditable de toutes les opérations
    importantes.

------------------------------------------------------------------------

# 2. Périmètre fonctionnel

Le système sera composé des modules suivants :

-   Authentification et utilisateurs
-   Multi-boulangeries / multi-sites
-   Produits et matières premières
-   Unités de mesure
-   Recettes / nomenclatures / règles de consommation
-   Production
-   Stocks
-   Fournisseurs
-   Commandes d'achat
-   Réceptions
-   Alertes de stock
-   Produits finis et péremption
-   Ventes
-   Clients particuliers
-   Grossistes
-   Ristournes
-   Pertes / invendus
-   Charges
-   Employés
-   Paie
-   Tableaux de bord
-   Rapports et statistiques
-   Journal d'audit
-   Notifications
-   Paramétrage général

------------------------------------------------------------------------

# 3. Principes métier fondamentaux

## 3.1. Stock par boulangerie

Chaque boulangerie possède ses propres stocks.

Un produit ne possède donc pas simplement un stock global : le stock est
rattaché à un site.

Exemple :

-   Boulangerie A : farine = 50 sacs
-   Boulangerie B : farine = 12 sacs
-   Boulangerie C : farine = 30 sacs

Le Super-Admin peut consulter l'ensemble des stocks.

Les responsables opérationnels ne doivent accéder qu'aux stocks des
sites auxquels ils sont affectés, selon leurs permissions.

## 3.2. Principe du grand livre de stock

Le stock ne doit pas être modifié arbitrairement par une simple écriture
du champ `quantity`.

Chaque variation importante doit générer un mouvement de stock.

Types de mouvements :

-   entrée fournisseur ;
-   sortie production ;
-   entrée production ;
-   vente ;
-   perte ;
-   ajustement positif ;
-   ajustement négatif ;
-   transfert entre boulangeries ;
-   retour fournisseur ;
-   retour client ;
-   correction administrative.

Le stock courant est calculable à partir des mouvements et peut être
matérialisé/caché pour les performances.

## 3.3. Traçabilité

Chaque mouvement devra conserver :

-   utilisateur ;
-   boulangerie ;
-   date et heure ;
-   produit ;
-   quantité ;
-   unité ;
-   type de mouvement ;
-   document source ;
-   motif ;
-   référence ;
-   commentaire éventuel.

Aucune suppression physique d'une opération métier critique ne doit être
autorisée.

Les corrections doivent être effectuées par une opération compensatoire
et apparaître dans l'audit.

------------------------------------------------------------------------

# 4. Gestion des produits et matières premières

## 4.1. Types de produits

Le système devra distinguer au minimum :

-   matière première ;
-   produit semi-fini ;
-   produit fini ;
-   consommable ;
-   emballage ;
-   charge/ressource consommée dans une production.

Exemples de matières premières :

-   farine ;
-   améliorant ;
-   levure ;
-   sel ;
-   glace/eau/glace selon le modèle retenu ;
-   autres ingrédients.

Exemples de produits finis :

-   pain ;
-   croissant ;
-   cake ;
-   gâteau ;
-   autres produits configurables.

## 4.2. Fiche produit

Chaque produit devra comporter :

-   nom ;
-   code interne/SKU ;
-   catégorie ;
-   type ;
-   unité principale ;
-   unités secondaires ;
-   coefficient de conversion ;
-   prix d'achat ;
-   prix de vente ;
-   prix grossiste ;
-   seuil d'alerte ;
-   mode d'alerte ;
-   durée limite de vente ;
-   actif/inactif ;
-   description ;
-   fournisseur(s) ;
-   photo facultative ;
-   site(s) autorisé(s).

## 4.3. Unités de mesure

Le système devra gérer :

-   kilogramme ;
-   gramme ;
-   litre ;
-   millilitre ;
-   sac ;
-   paquet ;
-   carton ;
-   pièce ;
-   fagot ;
-   autre unité configurable.

Les conversions devront être configurables.

Exemple :

`1 sac de farine = 50 kg`

------------------------------------------------------------------------

# 5. Gestion des recettes et nomenclatures de production

Le cœur du système sera un module de **recettes/nomenclatures
configurables**.

Une recette définit les ressources nécessaires pour produire une
quantité déterminée de produit fini.

## 5.1. Exemple : production d'un sac de farine en pain

Configuration initiale :

  Ressource          Quantité Unité          Mode
  ------------ -------------- -------------- --------------------------------
  Farine                    1 sac de 50 kg   déduction directe
  Améliorant                1 paquet         déduction directe
  Levure                    1 paquet         déduction directe
  Glace                 2 000 FCFA           consommation monétaire cumulée
  Fagot                 2 000 FCFA           consommation monétaire
  Sel            configurable FCFA           consommation cumulée

Rendement théorique :

`1 sac de farine = 300 pains`

Cette valeur doit être configurable.

## 5.2. Ressources en quantité

Exemple :

-   1 sac de farine ;
-   1 paquet d'améliorant ;
-   1 paquet de levure.

Lors de la validation d'une production :

-   le stock de farine diminue de 1 sac ;
-   le stock d'améliorant diminue de 1 paquet ;
-   le stock de levure diminue de 1 paquet.

## 5.3. Ressources monétaires ou cumulées

Certaines ressources doivent être gérées selon une logique cumulée.

Exemple :

-   fagot : 2 000 FCFA par sac ;
-   glace : 2 000 FCFA par sac ;
-   sel : coût configurable.

Pour une production de 10 sacs :

-   fagot théorique = 20 000 FCFA ;
-   glace = 20 000 FCFA ;
-   sel = coût unitaire configuré × 10.

Le système doit pouvoir distinguer :

1.  une ressource stockée physiquement ;
2.  une ressource consommée selon un coût ;
3.  une ressource cumulée ;
4.  une ressource dont la quantité réelle est saisie lors de la
    production.

------------------------------------------------------------------------

# 6. Configuration de plusieurs chaînes de production

Le système ne doit pas être limité au pain.

L'administrateur pourra créer plusieurs recettes :

-   pain ;
-   croissant ;
-   cake ;
-   gâteau ;
-   beignet ;
-   viennoiserie ;
-   produit personnalisé.

Chaque recette devra définir :

-   produit fini ;
-   matières premières ;
-   quantités nécessaires ;
-   unités ;
-   rendement théorique ;
-   durée de production ;
-   durée limite de vente ;
-   coûts associés ;
-   pertes autorisées/configurables ;
-   règles de déduction ;
-   paramètres propres au site si nécessaire.

## 6.1. Exemple

### Croissant

-   recette configurable ;
-   rendement configurable ;
-   durée limite de vente : 2 jours.

### Cake

-   recette configurable ;
-   rendement configurable ;
-   durée limite de vente : 2 jours.

### Gâteau

-   recette configurable ;
-   rendement configurable ;
-   durée limite de vente : 7 jours.

Toutes ces valeurs doivent être modifiables sans modification du code.

------------------------------------------------------------------------

# 7. Gestion de la production

## 7.1. Création d'une production

Le responsable de production sélectionne :

-   site ;
-   recette ;
-   quantité de lots/sacs ;
-   date ;
-   équipe/opérateur ;
-   observations.

Le système calcule automatiquement :

-   matières premières nécessaires ;
-   quantités disponibles ;
-   coût estimatif ;
-   rendement théorique ;
-   produits finis attendus.

## 7.2. Contrôle avant validation

Avant validation, le système vérifie :

-   disponibilité des matières premières ;
-   disponibilité des consommables ;
-   règles de production ;
-   éventuels seuils critiques.

Si une matière est insuffisante, le système doit empêcher ou signaler
clairement la production selon le paramétrage.

## 7.3. Validation de production

À la validation :

1.  création de l'ordre de production ;
2.  réservation ou consommation des matières premières ;
3.  génération des sorties de stock ;
4.  génération de l'entrée de produits finis ;
5.  calcul du rendement théorique ;
6.  enregistrement du coût ;
7.  création des lots de produits finis ;
8.  calcul de la date limite de vente ;
9.  génération éventuelle d'alerte ;
10. journalisation complète.

## 7.4. Quantité théorique vs quantité réelle

Le système doit conserver :

-   quantité théorique ;
-   quantité réellement produite ;
-   écart ;
-   motif de l'écart.

Exemple :

`1 sac → 300 pains théoriques`

Production réelle :

`292 pains`

Écart :

`-8 pains`

Le système doit demander ou permettre d'indiquer le motif :

-   problème de production ;
-   perte technique ;
-   défaut ;
-   erreur de saisie ;
-   autre.

------------------------------------------------------------------------

# 8. Gestion des produits finis et péremption

Chaque production de produit fini doit créer un lot.

Un lot contient :

-   produit ;
-   site ;
-   quantité initiale ;
-   quantité disponible ;
-   quantité vendue ;
-   quantité perdue ;
-   date de production ;
-   date limite de vente ;
-   référence du lot.

## 8.1. Durée limite

La durée est configurable par produit.

Exemples :

-   pain : durée configurable ;
-   croissant : 2 jours ;
-   cake : 2 jours ;
-   gâteau : 7 jours.

## 8.2. Gestion des invendus

À l'arrivée de la date limite :

`quantité restante du lot → perte`

Exemple :

Production :

`300 pains`

Vendus :

`285`

Restants :

`15`

Si la durée limite est dépassée :

`15 → perte`

Le système doit :

-   réduire le stock disponible ;
-   créer un mouvement de perte ;
-   enregistrer la valeur de la perte ;
-   conserver le lot comme historique ;
-   mettre à jour les statistiques.

La règle de péremption doit être configurable.

------------------------------------------------------------------------

# 9. Gestion des stocks

## 9.1. Stock temps réel

Le tableau de bord devra afficher en temps réel :

-   stock actuel ;
-   stock réservé ;
-   stock disponible ;
-   seuil d'alerte ;
-   quantité en commande ;
-   quantité en transit ;
-   quantité consommée ;
-   historique.

## 9.2. Stock minimum

Exemple :

`Farine : seuil = 10 sacs`

Lorsque :

`stock <= 10`

le système génère une alerte.

## 9.3. Alerte cumulée

Certains consommables doivent fonctionner sur une consommation cumulée.

Exemple :

`Fagot : seuil cumulé = 100 000 FCFA`

Consommation :

-   production 1 = 20 000 ;
-   production 2 = 30 000 ;
-   production 3 = 25 000 ;
-   production 4 = 30 000.

Cumul :

`105 000 FCFA`

L'alerte est déclenchée.

Le système devra donc proposer deux modes :

### Mode A --- seuil de stock

`stock actuel <= seuil`

### Mode B --- seuil cumulé

`consommation cumulée >= seuil`

La période du cumul doit être configurable :

-   journée ;
-   semaine ;
-   mois ;
-   période personnalisée ;
-   depuis dernier réapprovisionnement ;
-   autre règle métier.

------------------------------------------------------------------------

# 10. Fournisseurs

Chaque fournisseur possède :

-   raison sociale ;
-   nom du contact ;
-   téléphone ;
-   email ;
-   adresse ;
-   numéro d'identification si nécessaire ;
-   catégories fournies ;
-   conditions de paiement ;
-   délai moyen de livraison ;
-   statut actif/inactif ;
-   historique des commandes.

Un produit peut avoir plusieurs fournisseurs.

Le système doit permettre de définir :

-   fournisseur principal ;
-   fournisseur secondaire ;
-   prix négocié ;
-   délai de livraison.

------------------------------------------------------------------------

# 11. Commandes de matières premières

## 11.1. Cycle

Statuts :

1.  brouillon ;
2.  en attente ;
3.  commandée ;
4.  partiellement livrée ;
5.  livrée ;
6.  validée ;
7.  annulée.

Le statut métier demandé « en attente » devra être utilisé lorsque la
commande est en cours.

Le statut « validé » correspond à une réception effectivement contrôlée
et intégrée.

## 11.2. Création

Une commande contient :

-   fournisseur ;
-   site ;
-   date ;
-   lignes ;
-   quantité ;
-   unité ;
-   prix unitaire ;
-   total ;
-   observation.

## 11.3. Réception

À la livraison :

-   l'utilisateur enregistre les quantités réellement reçues ;
-   contrôle des écarts ;
-   validation de la réception.

Après validation :

`stock existant + quantité reçue = nouveau stock`

Le système crée automatiquement les mouvements d'entrée.

Une réception ne doit pas pouvoir être validée deux fois.

------------------------------------------------------------------------

# 12. Vente aux grossistes

## 12.1. Fiche grossiste

Informations :

-   nom/raison sociale ;
-   personne responsable ;
-   téléphone ;
-   adresse ;
-   zone ;
-   type de client ;
-   prix applicable ;
-   quantité habituelle par jour ;
-   conditions de paiement ;
-   statut ;
-   date d'adhésion ;
-   observations.

## 12.2. Historique

Pour chaque grossiste :

-   ventes quotidiennes ;
-   quantité de pains ;
-   quantité de croissants ;
-   quantité de cakes ;
-   quantité de gâteaux ;
-   chiffre d'affaires ;
-   quantité totale annuelle ;
-   paiements ;
-   restes éventuels ;
-   pertes éventuelles ;
-   ristourne.

## 12.3. Statistiques

Le système devra afficher :

-   consommation journalière moyenne ;
-   consommation mensuelle ;
-   consommation annuelle ;
-   évolution ;
-   chiffre d'affaires ;
-   quantité totale achetée ;
-   taux de régularité ;
-   ristourne calculée.

------------------------------------------------------------------------

# 13. Ristournes annuelles

Les ristournes doivent être configurables.

Exemple de configuration :

     Quantité annuelle   Taux
  -------------------- ------
           0 -- 50 000    0 %
     50 001 -- 100 000    1 %
    100 001 -- 200 000    2 %
            \> 200 000    3 %

Les seuils et taux sont uniquement des exemples et doivent être
administrables.

Le système calcule :

-   quantité annuelle ;
-   montant annuel éligible ;
-   palier atteint ;
-   ristourne théorique ;
-   ristourne validée ;
-   montant versé/crédité ;
-   date de règlement.

La règle de calcul devra pouvoir être basée sur :

-   quantité ;
-   chiffre d'affaires ;
-   famille de produits ;
-   combinaison de critères.

------------------------------------------------------------------------

# 14. Vente aux particuliers

Le responsable de vente pourra enregistrer les ventes au détail.

Le système devra gérer :

-   produit ;
-   quantité ;
-   prix ;
-   remise éventuelle ;
-   total ;
-   mode de paiement ;
-   utilisateur ;
-   date ;
-   site.

Modes de paiement configurables :

-   espèces ;
-   mobile money ;
-   virement ;
-   autre.

------------------------------------------------------------------------

# 15. Gestion des restes et invendus

À chaque clôture de journée, le responsable de vente doit pouvoir
effectuer un inventaire des produits finis restants.

Pour chaque produit :

-   quantité produite ;
-   quantité vendue ;
-   quantité restante ;
-   quantité perdue ;
-   quantité retournée ;
-   écart.

Le système compare les données théoriques et réelles.

Toute différence importante peut déclencher une alerte ou demander une
justification.

------------------------------------------------------------------------

# 16. Gestion des employés

## 16.1. Fiche employé

-   nom ;
-   prénom ;
-   téléphone ;
-   adresse ;
-   poste ;
-   site d'affectation ;
-   date d'embauche ;
-   salaire de base ;
-   type de contrat ;
-   statut ;
-   informations administratives nécessaires.

## 16.2. Postes configurables

Exemples :

-   Super-Admin ;
-   Responsable production ;
-   Responsable vente ;
-   Manœuvre ;
-   Caissier ;
-   Chauffeur ;
-   Agent administratif ;
-   autre.

------------------------------------------------------------------------

# 17. Gestion de la paie

Le Super-Admin pourra gérer :

-   salaire de base ;
-   primes ;
-   avances ;
-   retenues ;
-   heures supplémentaires si activées ;
-   absences si activées ;
-   salaire net ;
-   historique des paiements.

Le système doit générer un historique de paie par employé et par
période.

------------------------------------------------------------------------

# 18. Gestion des charges

Module de gestion des dépenses/charges.

Catégories initiales :

-   électricité ;
-   eau ;
-   imprévus.

Catégories supplémentaires configurables :

-   carburant ;
-   transport ;
-   maintenance ;
-   réparation ;
-   loyer ;
-   communication ;
-   emballage ;
-   administration ;
-   autres.

Chaque charge contient :

-   catégorie ;
-   montant ;
-   site ;
-   date ;
-   bénéficiaire/fournisseur ;
-   moyen de paiement ;
-   justificatif facultatif ;
-   commentaire ;
-   utilisateur.

------------------------------------------------------------------------

# 19. Multi-boulangeries

Le logiciel doit être nativement multi-sites.

## 19.1. Entité boulangerie

Chaque boulangerie possède :

-   nom ;
-   code ;
-   adresse ;
-   téléphone ;
-   responsable ;
-   statut ;
-   paramètres ;
-   stocks ;
-   utilisateurs affectés ;
-   recettes spécifiques éventuelles.

## 19.2. Isolation des données

Chaque opération métier doit être rattachée à un site lorsqu'elle
concerne un site.

Le Super-Admin peut :

-   voir tous les sites ;
-   créer/modifier les sites ;
-   consulter tous les stocks ;
-   comparer les performances ;
-   superviser les utilisateurs ;
-   transférer des produits entre sites ;
-   consolider les rapports.

Les responsables opérationnels travaillent dans leur périmètre autorisé.

## 19.3. Transfert inter-sites

Le système doit permettre :

`Boulangerie A → Boulangerie B`

avec :

-   produit ;
-   quantité ;
-   lot si nécessaire ;
-   motif ;
-   expéditeur ;
-   réceptionnaire ;
-   statut ;
-   date.

Le transfert doit créer :

-   une sortie du site A ;
-   une entrée du site B.

------------------------------------------------------------------------

# 20. Gestion des rôles et permissions

Le système devra utiliser une gestion fine des permissions.

## 20.1. Super-Admin

Accès complet :

-   utilisateurs ;
-   rôles ;
-   permissions ;
-   sites ;
-   produits ;
-   recettes ;
-   fournisseurs ;
-   commandes ;
-   stocks ;
-   production ;
-   ventes ;
-   grossistes ;
-   ristournes ;
-   charges ;
-   employés ;
-   paie ;
-   rapports ;
-   audit ;
-   configuration.

## 20.2. Responsable production

Permissions principales :

-   consulter stocks ;
-   créer productions ;
-   valider productions selon permission ;
-   consulter recettes ;
-   enregistrer consommations ;
-   gérer inventaires de production ;
-   recevoir les alertes ;
-   signaler les besoins au Super-Admin.

Ne doit pas pouvoir modifier librement :

-   paie ;
-   utilisateurs ;
-   paramètres critiques ;
-   ristournes ;
-   données financières globales.

## 20.3. Responsable vente

Permissions principales :

-   enregistrer ventes ;
-   gérer grossistes ;
-   gérer particuliers ;
-   consulter produits finis ;
-   gérer restes ;
-   enregistrer pertes ;
-   effectuer clôture de journée ;
-   consulter statistiques commerciales.

## 20.4. Permissions configurables

Le Super-Admin doit pouvoir :

-   créer un rôle ;
-   modifier un rôle ;
-   attribuer des permissions ;
-   affecter un utilisateur à un site ;
-   autoriser plusieurs sites pour un même utilisateur.

------------------------------------------------------------------------

# 21. Tableau de bord

## 21.1. Dashboard Super-Admin

Indicateurs :

-   chiffre d'affaires ;
-   ventes du jour ;
-   ventes du mois ;
-   production du jour ;
-   production du mois ;
-   stock critique ;
-   commandes en attente ;
-   commandes en transit ;
-   pertes du jour ;
-   pertes du mois ;
-   charges ;
-   marge/coût selon données disponibles ;
-   performances par boulangerie ;
-   performances des grossistes.

## 21.2. Dashboard Production

-   production du jour ;
-   matières disponibles ;
-   matières critiques ;
-   productions en cours ;
-   rendement ;
-   pertes de production ;
-   consommation des matières.

## 21.3. Dashboard Vente

-   ventes du jour ;
-   ventes par produit ;
-   ventes grossistes ;
-   ventes particuliers ;
-   invendus ;
-   pertes ;
-   grossistes actifs ;
-   créances éventuelles.

------------------------------------------------------------------------

# 22. Notifications et alertes

Les alertes doivent être configurables.

Types :

-   stock faible ;
-   stock critique ;
-   consommation cumulée atteinte ;
-   commande en retard ;
-   commande reçue ;
-   production insuffisante ;
-   écart de rendement ;
-   produit proche de la date limite ;
-   produit expiré ;
-   perte importante ;
-   écart de caisse si module activé ;
-   autre.

Canaux possibles :

-   notification interne ;
-   email ;
-   WhatsApp/SMS via intégration future ;
-   notification tableau de bord.

------------------------------------------------------------------------

# 23. Architecture technique recommandée

## 23.1. Backend

-   PHP 8.3+ selon version Laravel retenue ;
-   Laravel dernière version LTS/stable compatible au moment du
    développement ;
-   Laravel Eloquent ;
-   Laravel Queues ;
-   Laravel Events/Listeners ;
-   Laravel Notifications ;
-   Laravel Policies/Gates ;
-   Laravel Scheduler ;
-   Laravel Transactions.

## 23.2. Frontend

-   Tailwind CSS ;
-   Blade + Livewire, ou Inertia si l'équipe souhaite une architecture
    SPA ;
-   composants réutilisables ;
-   responsive design ;
-   interface desktop/tablette/mobile.

## 23.3. Base de données

PostgreSQL recommandé, MySQL/MariaDB acceptable.

La base doit être transactionnelle et correctement indexée.

------------------------------------------------------------------------

# 24. Modèle de données principal

Tables principales recommandées :

### Administration

-   users
-   roles
-   permissions
-   role_user
-   permission_role
-   audit_logs

### Sites

-   bakeries
-   bakery_user
-   bakery_settings

### Produits

-   product_categories
-   products
-   units
-   unit_conversions
-   product_suppliers
-   product_prices

### Production

-   recipes
-   recipe_items
-   production_orders
-   production_order_items
-   production_batches
-   production_outputs

### Stocks

-   stocks
-   stock_movements
-   stock_alert_rules
-   stock_alerts
-   stock_reservations
-   stock_transfers
-   stock_transfer_items

### Fournisseurs

-   suppliers
-   purchase_orders
-   purchase_order_items
-   goods_receipts
-   goods_receipt_items

### Clients

-   customers
-   wholesalers
-   wholesaler_rules
-   wholesaler_sales
-   wholesaler_rebates

### Ventes

-   sales
-   sale_items
-   payments
-   payment_methods

### Pertes

-   loss_records
-   loss_items

### Employés

-   employees
-   employee_contracts
-   payroll_periods
-   payroll_items
-   payroll_payments

### Charges

-   expense_categories
-   expenses

### Notifications

-   notifications
-   notification_preferences

------------------------------------------------------------------------

# 25. Relations principales

## Bakery

Une boulangerie possède :

-   plusieurs utilisateurs ;
-   plusieurs stocks ;
-   plusieurs productions ;
-   plusieurs commandes ;
-   plusieurs ventes ;
-   plusieurs charges ;
-   plusieurs employés.

## Product

Un produit possède :

-   une catégorie ;
-   plusieurs fournisseurs ;
-   plusieurs prix ;
-   plusieurs mouvements de stock ;
-   éventuellement plusieurs recettes.

## Recipe

Une recette possède :

-   un produit fini ;
-   plusieurs matières/ressources ;
-   un rendement ;
-   une durée limite ;
-   des paramètres.

## ProductionOrder

Une production possède :

-   une recette ;
-   un site ;
-   un utilisateur ;
-   plusieurs consommations ;
-   plusieurs sorties ;
-   un ou plusieurs lots produits.

## Sale

Une vente possède :

-   un site ;
-   un client optionnel ;
-   plusieurs lignes ;
-   un paiement ou plusieurs paiements.

------------------------------------------------------------------------

# 26. Architecture applicative

Le projet devra privilégier une architecture modulaire et maintenable.

Organisation recommandée :

``` text
app/
├── Actions/
├── Console/
├── Domain/
│   ├── Inventory/
│   ├── Production/
│   ├── Purchasing/
│   ├── Sales/
│   ├── Customers/
│   ├── Payroll/
│   └── Reporting/
├── Events/
├── Exceptions/
├── Http/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
├── Services/
└── Support/
```

Les règles métier critiques ne doivent pas être placées directement dans
les contrôleurs.

Exemple :

``` text
ProductionService
InventoryService
StockMovementService
PurchaseOrderService
GoodsReceiptService
SalesService
WholesalerRebateService
PayrollService
ReportingService
```

------------------------------------------------------------------------

# 27. Transactions et cohérence

Les opérations critiques doivent utiliser des transactions SQL.

Exemple : validation d'une production.

``` text
BEGIN TRANSACTION

1. vérifier disponibilité
2. verrouiller les stocks concernés
3. créer l'ordre de production
4. créer les consommations
5. créer les sorties de stock
6. créer les lots
7. créer les entrées de produits finis
8. calculer les coûts
9. enregistrer l'audit

COMMIT
```

En cas d'erreur :

``` text
ROLLBACK
```

Aucune opération partiellement validée ne doit rester en base.

------------------------------------------------------------------------

# 28. Gestion de la concurrence

Le système devra éviter les erreurs lorsqu'un même stock est utilisé
simultanément.

Exemple :

Deux responsables lancent une production nécessitant chacun 1 sac de
farine alors qu'il ne reste qu'un seul sac.

Le système doit utiliser :

-   transaction ;
-   verrouillage pessimiste ou stratégie équivalente ;
-   contrôle de disponibilité ;
-   idempotence des opérations critiques.

Objectif :

**ne jamais permettre un stock négatif sauf permission administrative
explicitement configurée.**

------------------------------------------------------------------------

# 29. Idempotence

Les opérations sensibles doivent être idempotentes.

Exemple :

Une réception fournisseur validée ne doit pas pouvoir ajouter deux fois
les mêmes quantités au stock en cas de double clic, rafraîchissement ou
retry réseau.

Même principe pour :

-   production ;
-   vente ;
-   transfert ;
-   paie ;
-   perte.

------------------------------------------------------------------------

# 30. API interne et évolutivité

Même si la première version est une application web Laravel
monolithique, les services métiers doivent être conçus de manière à
pouvoir exposer ultérieurement :

-   API mobile ;
-   application mobile ;
-   terminal de vente ;
-   application de livraison ;
-   intégration comptable ;
-   intégration SMS/WhatsApp.

Une API REST pourra être ajoutée ultérieurement.

------------------------------------------------------------------------

# 31. Sécurité

Le système devra intégrer :

-   authentification sécurisée ;
-   mots de passe hashés ;
-   2FA optionnelle ;
-   autorisation par permissions ;
-   Policies Laravel ;
-   protection CSRF ;
-   validation serveur ;
-   protection XSS ;
-   protection SQL injection via Eloquent/Query Builder ;
-   rate limiting ;
-   journalisation des actions sensibles ;
-   gestion sécurisée des sessions ;
-   expiration de session configurable ;
-   sauvegardes ;
-   chiffrement des données sensibles lorsque nécessaire.

------------------------------------------------------------------------

# 32. Audit

Les actions critiques doivent être enregistrées.

Exemples :

-   création/modification/suppression logique d'un produit ;
-   modification d'une recette ;
-   validation production ;
-   validation réception ;
-   modification stock ;
-   vente ;
-   perte ;
-   modification d'un prix ;
-   modification d'une ristourne ;
-   paiement de salaire ;
-   création de charge ;
-   changement de permission.

L'audit doit enregistrer :

-   utilisateur ;
-   action ;
-   objet ;
-   ancienne valeur ;
-   nouvelle valeur ;
-   IP ;
-   date/heure ;
-   site.

------------------------------------------------------------------------

# 33. Rapports

Le système doit générer des rapports :

## Production

-   production journalière ;
-   production mensuelle ;
-   rendement ;
-   consommation ;
-   coût estimatif ;
-   écarts.

## Stock

-   stock actuel ;
-   mouvements ;
-   consommation ;
-   pertes ;
-   valorisation ;
-   historique.

## Achats

-   achats par fournisseur ;
-   achats par produit ;
-   coûts ;
-   commandes en attente ;
-   réceptions.

## Ventes

-   ventes journalières ;
-   ventes mensuelles ;
-   ventes par produit ;
-   ventes par grossiste ;
-   ventes particuliers ;
-   chiffre d'affaires.

## Pertes

-   pertes par produit ;
-   pertes par site ;
-   pertes par période ;
-   valeur des pertes ;
-   causes.

## Grossistes

-   quantité achetée ;
-   CA ;
-   évolution ;
-   ristournes.

## Charges

-   charges par catégorie ;
-   charges par site ;
-   évolution mensuelle.

## Paie

-   masse salariale ;
-   paies par période ;
-   paies par site.

------------------------------------------------------------------------

# 34. Recherche et filtres

Toutes les listes importantes doivent disposer de :

-   recherche ;
-   filtres ;
-   tri ;
-   pagination ;
-   export.

Filtres fréquents :

-   site ;
-   période ;
-   produit ;
-   catégorie ;
-   fournisseur ;
-   client ;
-   statut ;
-   utilisateur.

------------------------------------------------------------------------

# 35. Exports

Formats recommandés :

-   PDF ;
-   Excel/XLSX ;
-   CSV.

Exports possibles :

-   stocks ;
-   ventes ;
-   productions ;
-   achats ;
-   fournisseurs ;
-   grossistes ;
-   pertes ;
-   charges ;
-   paie ;
-   rapports.

------------------------------------------------------------------------

# 36. Paramétrage général

Le Super-Admin pourra configurer :

-   informations de la boulangerie ;
-   sites ;
-   unités ;
-   catégories ;
-   produits ;
-   recettes ;
-   rendements ;
-   seuils ;
-   règles de péremption ;
-   fournisseurs ;
-   prix ;
-   catégories de charges ;
-   postes ;
-   rôles ;
-   permissions ;
-   règles de ristournes ;
-   modes de paiement ;
-   paramètres de notification.

------------------------------------------------------------------------

# 37. Règles de calcul essentielles

## 37.1. Rendement

``` text
Rendement réel =
quantité réellement produite
```

Écart :

``` text
Écart =
quantité réelle - quantité théorique
```

## 37.2. Stock

Conceptuellement :

``` text
Stock final =
Stock initial
+ Entrées
- Sorties
+ Ajustements positifs
- Ajustements négatifs
```

## 37.3. Produit disponible

``` text
Disponible =
Stock physique
- Stock réservé
```

## 37.4. Perte produit fini

``` text
Perte =
Quantité restante
à la date limite
```

## 37.5. Ristourne

Selon la règle configurée :

``` text
Ristourne =
base éligible × taux applicable
```

------------------------------------------------------------------------

# 38. Workflow global

``` text
FOURNISSEUR
    │
    ▼
COMMANDE D'ACHAT
    │
    ▼
RÉCEPTION
    │
    ▼
VALIDATION
    │
    ▼
STOCK MATIÈRES PREMIÈRES
    │
    ▼
ORDRE DE PRODUCTION
    │
    ▼
CONSOMMATION AUTOMATIQUE
    │
    ▼
PRODUCTION
    │
    ▼
STOCK PRODUITS FINIS / LOTS
    │
    ├───────────────┐
    ▼               ▼
VENTE            DATE LIMITE
    │               │
    ▼               ▼
CLIENT          PERTE / INVENDU
    │
    ▼
STATISTIQUES / CA / RISTOURNES
```

------------------------------------------------------------------------

# 39. Synchronisation métier

La synchronisation devra être événementielle lorsque cela apporte une
valeur réelle.

Exemples :

### Réception validée

``` text
GoodsReceiptValidated
        ↓
StockInCreated
        ↓
StockUpdated
        ↓
LowStockAlertRecalculated
```

### Production validée

``` text
ProductionValidated
        ↓
RawMaterialsConsumed
        ↓
FinishedGoodsCreated
        ↓
BatchCreated
        ↓
ExpiryScheduled
        ↓
StockUpdated
```

### Vente validée

``` text
SaleValidated
        ↓
FinishedGoodsConsumed
        ↓
StockUpdated
        ↓
WholesalerStatisticsUpdated
        ↓
RebateAccumulatorUpdated
```

### Expiration

``` text
BatchExpired
        ↓
RemainingQuantityDetected
        ↓
LossCreated
        ↓
StockUpdated
        ↓
LossStatisticsUpdated
```

------------------------------------------------------------------------

# 40. Scheduler Laravel

Le scheduler devra notamment vérifier :

-   produits arrivant à expiration ;
-   produits expirés ;
-   alertes cumulées ;
-   commandes en retard ;
-   tâches statistiques ;
-   clôtures automatiques éventuelles ;
-   rappels administratifs.

Les jobs lourds pourront être exécutés via Laravel Queue.

------------------------------------------------------------------------

# 41. Gestion des erreurs métier

Messages utilisateur clairs.

Exemples :

``` text
Stock insuffisant :
"Production impossible : il manque 3 sacs de farine."
```

``` text
Réception déjà validée :
"Cette réception a déjà été intégrée au stock."
```

``` text
Lot expiré :
"Ce lot ne peut plus être vendu car sa date limite est dépassée."
```

Les erreurs techniques ne doivent jamais être exposées directement à
l'utilisateur final.

------------------------------------------------------------------------

# 42. Interface utilisateur

L'interface Tailwind devra être :

-   moderne ;
-   responsive ;
-   rapide ;
-   accessible ;
-   cohérente ;
-   orientée métier.

## Navigation recommandée

``` text
Dashboard
├── Production
├── Stocks
├── Achats
├── Fournisseurs
├── Ventes
├── Grossistes
├── Clients
├── Pertes
├── Charges
├── Employés
├── Paie
├── Rapports
├── Notifications
└── Paramètres
```

Le menu devra être filtré automatiquement selon les permissions.

------------------------------------------------------------------------

# 43. UX de production

L'écran de production doit privilégier la simplicité :

1.  choisir le produit ;
2.  choisir la recette ;
3.  saisir quantité ;
4.  voir les matières nécessaires ;
5.  voir le stock disponible ;
6.  voir les éventuels manques ;
7.  confirmer ;
8.  valider ;
9.  afficher le résultat.

Exemple :

``` text
Production : Pain
Lots : 5 sacs

Farine requise : 5 sacs
Stock disponible : 27 sacs
Après production : 22 sacs

Rendement théorique : 1 500 pains
```

------------------------------------------------------------------------

# 44. UX du stock

Pour chaque produit :

``` text
Produit : Farine 50 kg

Stock actuel : 24 sacs
Seuil : 10 sacs
En commande : 20 sacs
Disponible : 24 sacs

Statut : NORMAL
```

En cas d'alerte :

``` text
Produit : Farine 50 kg

Stock actuel : 7 sacs
Seuil : 10 sacs

Statut : ALERTE STOCK
```

------------------------------------------------------------------------

# 45. Gestion des coûts

Le système devra conserver le coût des matières au moment de leur
consommation afin de permettre une analyse historique.

Pour une production, le coût peut inclure :

-   matières premières ;
-   consommables ;
-   coûts configurés ;
-   éventuellement main-d'œuvre ;
-   éventuellement charges indirectes.

Le modèle devra distinguer :

-   coût théorique ;
-   coût réel ;
-   prix de vente ;
-   marge calculée lorsque les données sont disponibles.

------------------------------------------------------------------------

# 46. Comptabilité : périmètre recommandé

La première version est un logiciel de gestion opérationnelle et non
nécessairement un logiciel de comptabilité générale complet.

Le système devra toutefois préparer les données nécessaires :

-   achats ;
-   ventes ;
-   paiements ;
-   charges ;
-   salaires ;
-   pertes ;
-   coûts de production.

Une intégration comptable pourra être développée ultérieurement.

------------------------------------------------------------------------

# 47. Sauvegarde et reprise

Prévoir :

-   sauvegarde automatique de la base ;
-   sauvegarde des fichiers ;
-   stratégie de rétention ;
-   restauration testée ;
-   journal des sauvegardes ;
-   procédure de reprise après incident.

------------------------------------------------------------------------

# 48. Performance

Objectifs :

-   pages principales \< 2 secondes dans des conditions normales ;
-   requêtes indexées ;
-   pagination obligatoire sur les grandes listes ;
-   traitement asynchrone pour les tâches lourdes ;
-   cache des données de configuration ;
-   optimisation des requêtes N+1 ;
-   index sur les colonnes de recherche et relations fréquentes.

------------------------------------------------------------------------

# 49. Tests

Le projet doit inclure :

## Tests unitaires

Pour :

-   calcul de recettes ;
-   calcul de rendement ;
-   calcul de stock ;
-   calcul de ristournes ;
-   calcul de pertes ;
-   calcul de coûts.

## Tests fonctionnels

Pour :

-   achat ;
-   réception ;
-   production ;
-   vente ;
-   perte ;
-   transfert ;
-   utilisateur ;
-   permissions.

## Tests d'intégration

Exemple :

``` text
Commande fournisseur
→ Réception
→ Stock
→ Production
→ Vente
→ Stock final
```

## Tests de concurrence

Tester deux opérations simultanées sur le même stock.

------------------------------------------------------------------------

# 50. Critères d'acceptation principaux

Le logiciel sera considéré fonctionnel lorsque :

### Stock

-   une réception validée augmente automatiquement le stock ;
-   une production validée diminue automatiquement les matières ;
-   une production augmente automatiquement le stock de produits finis ;
-   une vente diminue automatiquement les produits finis ;
-   une perte diminue automatiquement les produits concernés ;
-   les mouvements sont historisés.

### Production

-   une recette est configurable ;
-   les matières sont calculées automatiquement ;
-   le rendement est configurable ;
-   la quantité réelle est enregistrable ;
-   les écarts sont calculés.

### Péremption

-   les dates limites sont calculées ;
-   les produits expirés sont identifiés ;
-   les invendus sont transformés en pertes selon la règle configurée.

### Grossistes

-   chaque grossiste possède une fiche ;
-   les ventes sont historisées ;
-   les quantités annuelles sont calculées ;
-   les règles de ristourne sont configurables.

### Multi-sites

-   chaque site possède ses stocks ;
-   les données sont isolées ;
-   le Super-Admin voit tous les sites ;
-   les transferts sont traçables.

### Sécurité

-   les permissions sont appliquées côté serveur ;
-   les opérations sensibles sont auditées ;
-   un utilisateur ne peut pas contourner les permissions par
    l'interface ou une requête HTTP directe.

------------------------------------------------------------------------

# 51. Phasage recommandé

## Phase 1 --- Socle

-   architecture Laravel ;
-   authentification ;
-   utilisateurs ;
-   rôles ;
-   permissions ;
-   boulangeries ;
-   produits ;
-   unités.

## Phase 2 --- Stocks

-   stocks ;
-   mouvements ;
-   seuils ;
-   alertes ;
-   inventaires.

## Phase 3 --- Achats

-   fournisseurs ;
-   commandes ;
-   réceptions ;
-   intégration automatique au stock.

## Phase 4 --- Production

-   recettes ;
-   nomenclatures ;
-   ordres de production ;
-   consommation automatique ;
-   lots ;
-   rendement.

## Phase 5 --- Ventes

-   particuliers ;
-   grossistes ;
-   prix ;
-   ventes ;
-   paiements ;
-   restes.

## Phase 6 --- Pertes et péremption

-   durée de vente ;
-   expiration ;
-   pertes ;
-   statistiques.

## Phase 7 --- Grossistes et ristournes

-   statistiques ;
-   règles ;
-   calcul annuel ;
-   validation.

## Phase 8 --- Employés et paie

-   employés ;
-   postes ;
-   contrats ;
-   paie ;
-   avances ;
-   retenues.

## Phase 9 --- Charges et reporting

-   charges ;
-   rapports ;
-   tableaux de bord ;
-   exports.

## Phase 10 --- Optimisation

-   notifications ;
-   queue ;
-   cache ;
-   audit avancé ;
-   performance ;
-   sécurité ;
-   sauvegardes ;
-   tests complets.

------------------------------------------------------------------------

# 52. Priorités MVP

Pour obtenir rapidement une première version exploitable, le MVP doit
prioriser :

1.  multi-boulangeries ;
2.  utilisateurs/rôles ;
3.  produits ;
4.  fournisseurs ;
5.  stocks ;
6.  achats/réceptions ;
7.  recettes ;
8.  production ;
9.  produits finis ;
10. ventes ;
11. pertes ;
12. alertes ;
13. grossistes ;
14. tableau de bord.

La paie avancée, certaines intégrations externes et les fonctionnalités
comptables peuvent être livrées dans une phase ultérieure.

------------------------------------------------------------------------

# 53. Principes d'architecture à respecter

1.  **Une seule source de vérité pour le stock.**
2.  **Toute variation de stock est traçable.**
3.  **Les règles métier sont centralisées dans les services métier.**
4.  **Les contrôleurs restent minces.**
5.  **Les opérations critiques sont transactionnelles.**
6.  **Les permissions sont contrôlées côté serveur.**
7.  **Les paramètres métier ne doivent pas être codés en dur.**
8.  **Les sites sont isolés par défaut.**
9.  **Le Super-Admin dispose d'une vision consolidée.**
10. **Les opérations critiques sont auditables.**
11. **Les données historiques ne sont pas écrasées.**
12. **Les tâches automatiques sont idempotentes.**
13. **Les traitements lourds passent par les queues.**
14. **La logique métier doit être testable indépendamment de
    l'interface.**

------------------------------------------------------------------------

# 54. Décisions métier à valider avant développement

Les points suivants devront être confirmés avec le propriétaire du
projet avant la conception finale de la base de données :

1.  La définition exacte de « glace » : quantité physique ou coût de
    consommation.
2.  La définition exacte du « fagot » : stock physique, coût ou
    consommation cumulée.
3.  L'unité réelle de la levure et de l'améliorant.
4.  La quantité exacte de sel par sac.
5.  La durée limite de vente du pain.
6.  Les règles exactes de péremption.
7.  Le traitement des produits partiellement vendus.
8.  Le traitement des retours grossistes.
9.  Le traitement des invendus retournés par les grossistes.
10. La politique de prix particuliers/grossistes.
11. Les règles exactes de ristourne.
12. Le mode de calcul de la masse salariale.
13. Les règles de transfert entre boulangeries.
14. La possibilité ou non d'avoir un stock négatif.
15. Les modes de paiement à supporter.
16. Les règles fiscales/comptables applicables.
17. La nécessité d'une caisse POS.
18. Les besoins d'impression de factures/tickets.
19. Les intégrations Mobile Money éventuelles.
20. La nécessité d'une application mobile future.

------------------------------------------------------------------------

# 55. Résultat attendu

À terme, le logiciel doit permettre au Super-Admin de répondre
immédiatement à des questions telles que :

-   Combien de farine reste-t-il dans chaque boulangerie ?
-   Quelle quantité de farine est en commande ?
-   Quelles matières sont sous le seuil ?
-   Combien de sacs ont été produits aujourd'hui ?
-   Combien de pains devraient être produits ?
-   Combien ont réellement été produits ?
-   Quelle quantité a été vendue ?
-   Quelle quantité est restée ?
-   Quelle quantité est devenue une perte ?
-   Quel est le coût de production ?
-   Quel grossiste a pris combien de pains cette année ?
-   Quelle ristourne lui est applicable selon la règle configurée ?
-   Combien ont coûté l'électricité, l'eau et les autres charges ?
-   Quel est le chiffre d'affaires de chaque boulangerie ?
-   Quelle est la consommation de matières premières ?
-   Quelles opérations ont été effectuées par chaque utilisateur ?
-   Quelle est la situation consolidée de toutes les boulangeries ?

------------------------------------------------------------------------

# 56. Conclusion

Le logiciel devra être conçu comme un **ERP métier spécialisé pour
boulangerie**, et non comme une simple application de caisse ou de
gestion de stock.

Le cœur architectural repose sur la relation :

``` text
ACHATS
   ↓
STOCK MATIÈRES
   ↓
RECETTES / NOMENCLATURES
   ↓
PRODUCTION
   ↓
STOCK PRODUITS FINIS / LOTS
   ↓
VENTES
   ↓
RESTES / PERTES
   ↓
STATISTIQUES
   ↓
GESTION GROSSISTES / RISTOURNES
```

L'ensemble doit être relié à :

``` text
UTILISATEURS
RÔLES
PERMISSIONS
SITES
FOURNISSEURS
EMPLOYÉS
PAIE
CHARGES
AUDIT
ALERTES
RAPPORTS
```

La conception devra privilégier la fiabilité des données, la
traçabilité, la modularité, l'évolutivité et la cohérence
transactionnelle. Toutes les valeurs métier susceptibles d'évoluer
doivent être configurables par le Super-Admin sans intervention du
développeur.

**Livrable cible :** application web Laravel/Tailwind CSS multi-sites,
sécurisée, responsive, transactionnelle, auditable et extensible.
