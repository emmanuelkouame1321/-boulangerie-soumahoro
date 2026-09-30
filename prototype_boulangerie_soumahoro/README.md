# Prototype Boulangerie Soumahoro

Prototype UI/UX navigable construit à partir du **Cahier des charges fonctionnel et technique v1.0** et du **Design System v1.0**.

## Lancer le prototype

Ouvrir `index.html` dans un navigateur moderne.

> Le prototype utilise Lucide via CDN pour les icônes. Une connexion Internet est donc recommandée pour obtenir les icônes. Le reste de l'interface est autonome.

## Ce que le prototype couvre

- Dashboard Super-Admin, Responsable Production et Responsable Vente
- Sélecteur multi-boulangeries
- Navigation par rôle
- Production en 4 étapes :
  1. sélection
  2. prévisualisation des matières
  3. rendement théorique/réel
  4. confirmation et impact stock
- Stocks temps réel, seuils, alertes et historique
- Achats / commandes / réception / validation
- Ventes grossistes et particuliers
- Lots et péremption
- Clôture journalière : ventes → restes → pertes → écarts → confirmation
- Grossistes, habitudes et ristournes
- Pertes et invendus
- Employés et paie
- Charges
- Rapports
- Boulangeries et transferts inter-sites
- Utilisateurs, rôles et permissions
- Paramétrage des recettes, seuils cumulés, durées de vente et unités
- Journal d’audit
- Notifications
- Recherche globale Ctrl+K
- Responsive desktop/tablette/mobile
- États visuels : disponible, faible, critique, en attente, validé, à écouler, expiré, etc.

## Important pour l’agent de codage

Ce prototype est une **référence d’interaction et de composition d’écran**, pas la source de vérité métier.

Ordre de référence :

1. Cahier des charges — règles métier, permissions, données, workflows et architecture.
2. Design System — tokens, composants, états, responsive, accessibilité.
3. Prototype — matérialisation visuelle et comportementale des parcours.

L’agent doit conserver les principes suivants :

- une seule source de vérité pour le stock ;
- chaque variation de stock génère un mouvement traçable ;
- opérations critiques transactionnelles ;
- paramètres métier configurables ;
- isolation par site par défaut ;
- permissions contrôlées côté serveur ;
- historique non écrasé ;
- traitements automatiques idempotents ;
- logique métier hors des composants UI.

## Mapping indicatif

| Prototype | Route cible |
|---|---|
| Tableau de bord | `/dashboard` |
| Production | `/production` |
| Nouvelle production | `/production/create` |
| Détail production | `/production/{id}` |
| Stocks | `/inventory` |
| Mouvements | `/inventory/movements` |
| Achats | `/purchasing/orders` |
| Réceptions | `/purchasing/receipts` |
| Ventes | `/sales` |
| Grossistes | `/wholesalers` |
| Fiche grossiste | `/wholesalers/{id}` |
| Pertes | `/losses` |
| Employés | `/employees` |
| Paie | `/payroll` |
| Charges | `/expenses` |
| Rapports | `/reports` |
| Paramètres | `/settings` |

## Données

Les données affichées sont des **fixtures de démonstration**. Elles servent à montrer les états métier, les transitions et les volumes d’information ; elles ne remplacent pas les règles de calcul Laravel/PostgreSQL.

## Décisions encore à valider

Le cahier des charges identifie notamment comme décisions métier préalables : définition physique/coût de la glace et du fagot, unité de levure/améliorant, quantité de sel, durée limite du pain, règles de péremption, retours grossistes, prix, ristournes, masse salariale, transferts, stock négatif, moyens de paiement, fiscalité/comptabilité, POS, impression, intégrations Mobile Money et future application mobile.
