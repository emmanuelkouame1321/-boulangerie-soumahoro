# Prototype → Implémentation Laravel

## Source de vérité
- `cahier_des_charges_gestion_boulangerie(2).md` : exigences fonctionnelles et techniques.
- `design_system_boulangerie_soumahoro(1).md` : source de vérité UI/UX.
- `index.html` : prototype navigable, comportements et composition visuelle.

## Composants à reproduire
AppShell, Sidebar, Topbar, PageHeader, Breadcrumbs, SiteSwitcher, StatCard, DataTable, StatusBadge, AlertCard, Modal, Drawer, Toast, Tabs, Stepper, FormField, QuantityInput, RecipePreview, ProductionSummary, StockStatus, ExpiryIndicator, WholesalerSummary, RebateProgress, SiteStatus.

## Parcours prioritaires
1. Dashboard → alerte stock → création commande.
2. Achats → réception → validation → entrée stock.
3. Production → contrôle matières → rendement → confirmation → sorties matières + entrée PF + lot.
4. Ventes → déduction stock PF → historique grossiste/particulier.
5. Clôture → restes → pertes → écarts → audit.
6. Lot → date limite → perte automatique.
7. Grossiste → ventes annuelles → règle de ristourne configurable.
8. Multi-site → changement de contexte → transfert inter-sites.
9. Rôle → menu et permissions adaptés.
10. Audit → historique des opérations critiques.

## Règles UX obligatoires
- Contexte site toujours visible.
- Une action primaire par contexte.
- Couleur d’état jamais seule : texte/icône obligatoire.
- Confirmation explicite des opérations critiques avec impact.
- Tables desktop, cartes sur mobile.
- Loading/skeleton, empty state, erreurs et toasts.
- Accessibilité WCAG 2.2 AA visée.
