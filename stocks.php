/**
 * Template Name: Gestion des stocks SEMPA
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Sempa_Stocks_App')) {
    Sempa_Stocks_App::ensure_assets_for_template();
}

get_header();

$allowed = class_exists('Sempa_Stocks_App') ? Sempa_Stocks_App::user_is_allowed() : false;
$nonce = class_exists('Sempa_Stocks_App') ? Sempa_Stocks_App::nonce() : wp_create_nonce('sempa_stocks_nonce');

$current_user = wp_get_current_user();
$user_name = ($current_user instanceof WP_User && $current_user->exists())
    ? ($current_user->display_name ?: $current_user->user_login)
    : '';
$user_email = ($current_user instanceof WP_User && $current_user->exists()) ? $current_user->user_email : '';
$user_role = '';

if ($current_user instanceof WP_User && $current_user->exists()) {
    $roles = $current_user->roles;
    $primary_role = is_array($roles) && !empty($roles) ? reset($roles) : '';
    if ($primary_role) {
        $wp_roles = wp_roles();
        if ($wp_roles instanceof WP_Roles && isset($wp_roles->roles[$primary_role]['name'])) {
            $user_role = translate_user_role($wp_roles->roles[$primary_role]['name']);
        } else {
            $user_role = ucfirst(str_replace('_', ' ', $primary_role));
        }
    }
}
?>
<div class="sempa-stocks-wrapper" data-stock-nonce="<?php echo esc_attr($nonce); ?>">
    <?php if (!$allowed) : ?>
        <section class="stocks-locked">
            <div class="stocks-locked__inner">
                <h1><?php esc_html_e('Accès réservé', 'sempa'); ?></h1>
                <p><?php esc_html_e('Cette application est réservée à l\'équipe SEMPA. Merci de vous connecter avec un compte autorisé.', 'sempa'); ?></p>
                <?php wp_login_form([
                    'label_username' => __('Identifiant ou adresse e-mail', 'sempa'),
                    'label_password' => __('Mot de passe', 'sempa'),
                    'label_remember' => __('Se souvenir de moi', 'sempa'),
                    'label_log_in' => __('Se connecter', 'sempa'),
                    'remember' => true,
                    'redirect' => home_url('/stocks'),
                    'form_id' => 'stockpilot-login-form',
                ]); ?>
            </div>
        </section>
    <?php else : ?>
        <div class="stockpilot-app">
            <aside class="stockpilot-sidebar" role="navigation" aria-label="<?php esc_attr_e('Navigation principale', 'sempa'); ?>">
                <div class="stockpilot-sidebar__brand">
                    <img src="<?php echo esc_url(trailingslashit(get_stylesheet_directory_uri()) . 'logo-since-b.svg'); ?>" alt="<?php esc_attr_e('Logo SEMPA', 'sempa'); ?>" loading="lazy" />
                    <div class="stockpilot-sidebar__titles">
                        <span class="sidebar-app">StockPilot</span>
                        <span class="sidebar-company"><?php esc_html_e('SEMPA Stocks', 'sempa'); ?></span>
                    </div>
                </div>
                <nav class="stockpilot-sidebar__nav">
                    <ul>
                        <li class="is-active"><a href="#stockpilot-dashboard"><span class="nav-icon" aria-hidden="true"></span><?php esc_html_e('Tableau de bord', 'sempa'); ?></a></li>
                        <li><a href="#stockpilot-products"><span class="nav-icon" aria-hidden="true"></span><?php esc_html_e('Produits', 'sempa'); ?></a></li>
                        <li><a href="#stockpilot-movements"><span class="nav-icon" aria-hidden="true"></span><?php esc_html_e('Mouvements', 'sempa'); ?></a></li>
                        <li><a href="#stockpilot-reports"><span class="nav-icon" aria-hidden="true"></span><?php esc_html_e('Rapports', 'sempa'); ?></a></li>
                        <li><a href="#stockpilot-settings"><span class="nav-icon" aria-hidden="true"></span><?php esc_html_e('Paramètres', 'sempa'); ?></a></li>
                    </ul>
                </nav>
                <div class="stockpilot-sidebar__user">
                    <?php echo get_avatar($current_user ? $current_user->ID : 0, 48, '', '', ['class' => 'sidebar-avatar']); ?>
                    <div class="sidebar-user__meta">
                        <strong><?php echo esc_html($user_name ?: __('Utilisateur SEMPA', 'sempa')); ?></strong>
                        <?php if ($user_role) : ?>
                            <span><?php echo esc_html($user_role); ?></span>
                        <?php endif; ?>
                        <?php if ($user_email) : ?>
                            <span class="sidebar-user__email"><?php echo esc_html($user_email); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <div class="stockpilot-main">
                <header class="stockpilot-header">
                    <div class="stockpilot-header__titles">
                        <p class="stockpilot-header__eyebrow"><?php esc_html_e('SEMPA Stocks', 'sempa'); ?></p>
                        <h1><?php esc_html_e('Tableau de bord StockPilot', 'sempa'); ?></h1>
                        <p class="stockpilot-header__subtitle"><?php esc_html_e('Suivez vos produits, alertes et mouvements dans une interface professionnelle.', 'sempa'); ?></p>
                    </div>
                    <div class="stockpilot-header__tools">
                        <label for="stocks-search" class="screen-reader-text"><?php esc_html_e('Rechercher un produit', 'sempa'); ?></label>
                        <div class="header-search">
                            <input type="search" id="stocks-search" aria-label="<?php esc_attr_e('Rechercher un produit par référence ou désignation', 'sempa'); ?>" placeholder="<?php esc_attr_e('Rechercher un produit…', 'sempa'); ?>" />
                        </div>
                        <div class="stockpilot-header__actions">
                            <a class="button button--ghost" href="#" id="stocks-export" data-export="1"><?php esc_html_e('Exporter CSV', 'sempa'); ?></a>
                            <button type="button" id="stocks-refresh" class="button button--primary"><?php esc_html_e('Actualiser', 'sempa'); ?></button>
                        </div>
                    </div>
                </header>

                <main class="stockpilot-content">
                    <section class="stockpilot-section" id="stockpilot-dashboard" aria-labelledby="stocks-dashboard-title">
                        <div class="section-header">
                            <div>
                                <p class="section-eyebrow"><?php esc_html_e('Vue d\'ensemble', 'sempa'); ?></p>
                                <h2 id="stocks-dashboard-title"><?php esc_html_e('Métriques principales', 'sempa'); ?></h2>
                            </div>
                            <div class="section-context">
                                <span class="section-context__badge"><?php esc_html_e('Données en temps réel', 'sempa'); ?></span>
                            </div>
                        </div>
                        <div class="stockpilot-metrics" id="stocks-dashboard-cards">
                            <article class="metric-card metric-card--products">
                                <header>
                                    <span class="metric-card__title"><?php esc_html_e('Total produits', 'sempa'); ?></span>
                                    <span class="metric-card__icon" aria-hidden="true"></span>
                                </header>
                                <p class="metric-card__value" data-dashboard="produits">0</p>
                                <p class="metric-card__hint"><?php esc_html_e('Catalogue actif', 'sempa'); ?></p>
                            </article>
                            <article class="metric-card metric-card--value">
                                <header>
                                    <span class="metric-card__title"><?php esc_html_e('Valeur du stock', 'sempa'); ?></span>
                                    <span class="metric-card__icon" aria-hidden="true"></span>
                                </header>
                                <p class="metric-card__value" data-dashboard="valeur">0 €</p>
                                <p class="metric-card__hint"><?php esc_html_e('Estimation achat', 'sempa'); ?></p>
                            </article>
                            <article class="metric-card metric-card--alerts">
                                <header>
                                    <span class="metric-card__title"><?php esc_html_e('Alertes stock', 'sempa'); ?></span>
                                    <span class="metric-card__icon" aria-hidden="true"></span>
                                </header>
                                <p class="metric-card__value" data-dashboard="alertes">0</p>
                                <p class="metric-card__hint"><?php esc_html_e('À traiter rapidement', 'sempa'); ?></p>
                            </article>
                            <article class="metric-card metric-card--movements">
                                <header>
                                    <span class="metric-card__title"><?php esc_html_e('Mouvements', 'sempa'); ?></span>
                                    <span class="metric-card__icon" aria-hidden="true"></span>
                                </header>
                                <p class="metric-card__value" data-dashboard="mouvements">0</p>
                                <p class="metric-card__hint"><?php esc_html_e('7 derniers jours', 'sempa'); ?></p>
                            </article>
                        </div>
                        <div class="stockpilot-panels">
                            <article class="panel panel--alerts" aria-labelledby="stockpilot-alerts-title">
                                <div class="panel__header">
                                    <h3 id="stockpilot-alerts-title"><?php esc_html_e('Alertes nécessitant attention', 'sempa'); ?></h3>
                                    <span class="panel__badge panel__badge--urgent"><?php esc_html_e('Urgent', 'sempa'); ?></span>
                                </div>
                                <ul id="stocks-alerts" class="alerts-list"></ul>
                            </article>
                            <article class="panel panel--recent" aria-labelledby="stockpilot-recent-title">
                                <div class="panel__header">
                                    <h3 id="stockpilot-recent-title"><?php esc_html_e('Mouvements récents', 'sempa'); ?></h3>
                                    <span class="panel__badge"><?php esc_html_e('Timeline', 'sempa'); ?></span>
                                </div>
                                <ul id="stocks-recent" class="recent-list"></ul>
                            </article>
                        </div>
                    </section>

                    <section class="stockpilot-section" id="stockpilot-products" aria-labelledby="stocks-products-title">
                        <div class="section-header">
                            <div>
                                <p class="section-eyebrow"><?php esc_html_e('Catalogue', 'sempa'); ?></p>
                                <h2 id="stocks-products-title"><?php esc_html_e('Produits', 'sempa'); ?></h2>
                                <p class="section-subtitle"><?php esc_html_e('Gérez vos références, fournisseurs et niveaux de stock.', 'sempa'); ?></p>
                            </div>
                            <div class="section-actions">
                                <button type="button" class="button button--primary" id="stocks-open-product-form"><?php esc_html_e('Ajouter un produit', 'sempa'); ?></button>
                            </div>
                        </div>
                        <div class="products-toolbar" role="group" aria-label="<?php esc_attr_e('Filtres produits', 'sempa'); ?>">
                            <div class="toolbar-field">
                                <label for="stocks-filter-category"><?php esc_html_e('Catégorie', 'sempa'); ?></label>
                                <select id="stocks-filter-category"></select>
                            </div>
                            <div class="toolbar-field">
                                <label for="stocks-filter-supplier"><?php esc_html_e('Fournisseur', 'sempa'); ?></label>
                                <select id="stocks-filter-supplier"></select>
                            </div>
                            <div class="toolbar-field">
                                <label for="stocks-filter-status"><?php esc_html_e('Statut', 'sempa'); ?></label>
                                <select id="stocks-filter-status">
                                    <option value=""><?php esc_html_e('Tous les statuts', 'sempa'); ?></option>
                                    <option value="normal"><?php esc_html_e('En stock', 'sempa'); ?></option>
                                    <option value="warning"><?php esc_html_e('Stock faible', 'sempa'); ?></option>
                                    <option value="critical"><?php esc_html_e('Rupture', 'sempa'); ?></option>
                                </select>
                            </div>
                            <div class="toolbar-actions">
                                <button type="button" class="button button--ghost" id="stocks-clear-filters"><?php esc_html_e('Réinitialiser', 'sempa'); ?></button>
                            </div>
                            <div class="toolbar-segment" role="group" aria-label="<?php esc_attr_e('Type de matériel', 'sempa'); ?>">
                                <button type="button" class="segment-button is-active" data-condition-view="all" aria-pressed="true"><?php esc_html_e('Tout', 'sempa'); ?></button>
                                <button type="button" class="segment-button" data-condition-view="neuf" aria-pressed="false"><?php esc_html_e('Matériel neuf', 'sempa'); ?></button>
                                <button type="button" class="segment-button" data-condition-view="reconditionne" aria-pressed="false"><?php esc_html_e('Reconditionné', 'sempa'); ?></button>
                            </div>
                        </div>
                        <div class="table-wrapper table-wrapper--elevated">
                            <table class="stocks-table stocks-table--products" id="stocks-products-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Produit', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Référence', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Stock', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Statut', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Condition', 'sempa'); ?></th>
                                        <th scope="col" class="actions">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="empty"><?php esc_html_e('Chargement des produits…', 'sempa'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-pagination" id="stocks-products-pagination" aria-live="polite">
                            <button type="button" class="button button--ghost" data-pagination="prev" disabled>
                                <?php esc_html_e('Précédent', 'sempa'); ?>
                            </button>
                            <div class="table-pagination__status">
                                <span data-pagination="summary"><?php esc_html_e('Aucun produit à afficher', 'sempa'); ?></span>
                                <span class="table-pagination__page">
                                    <?php esc_html_e('Page', 'sempa'); ?>
                                    <span data-pagination="page">1</span>
                                    <?php esc_html_e('sur', 'sempa'); ?>
                                    <span data-pagination="pages">1</span>
                                </span>
                            </div>
                            <button type="button" class="button button--ghost" data-pagination="next" disabled>
                                <?php esc_html_e('Suivant', 'sempa'); ?>
                            </button>
                        </div>
                    </section>

                    <section class="stockpilot-section" id="stockpilot-movements" aria-labelledby="stocks-movements-title">
                        <div class="section-header">
                            <div>
                                <p class="section-eyebrow"><?php esc_html_e('Suivi des flux', 'sempa'); ?></p>
                                <h2 id="stocks-movements-title"><?php esc_html_e('Mouvements de stock', 'sempa'); ?></h2>
                                <p class="section-subtitle"><?php esc_html_e('Analysez les entrées, sorties et ajustements récents.', 'sempa'); ?></p>
                            </div>
                            <div class="section-actions">
                                <button type="button" class="button button--secondary" id="stocks-open-movement-form"><?php esc_html_e('Enregistrer un mouvement', 'sempa'); ?></button>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table class="stocks-table" id="stocks-movements-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Date', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Produit', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Type', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Quantité', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Stock', 'sempa'); ?></th>
                                        <th scope="col"><?php esc_html_e('Motif', 'sempa'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="empty"><?php esc_html_e('Chargement de l\'historique…', 'sempa'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="stockpilot-section" id="stockpilot-reports" aria-labelledby="stocks-reports-title">
                        <div class="section-header">
                            <div>
                                <p class="section-eyebrow"><?php esc_html_e('Pilotage', 'sempa'); ?></p>
                                <h2 id="stocks-reports-title"><?php esc_html_e('Rapports & documents', 'sempa'); ?></h2>
                                <p class="section-subtitle"><?php esc_html_e('Exportez vos données et accédez aux ressources partagées.', 'sempa'); ?></p>
                            </div>
                        </div>
                        <div class="reports-grid">
                            <article class="report-card">
                                <h3><?php esc_html_e('Rapport valeur du stock', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Téléchargez la photographie financière actuelle du stock SEMPA.', 'sempa'); ?></p>
                                <a href="#" class="button button--primary" data-trigger="export"><?php esc_html_e('Exporter au format CSV', 'sempa'); ?></a>
                            </article>
                            <article class="report-card">
                                <h3><?php esc_html_e('Documents techniques', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Les PDF et médias liés aux fiches produits restent accessibles depuis la liste principale.', 'sempa'); ?></p>
                                <a class="button button--secondary" href="<?php echo esc_url(trailingslashit(get_stylesheet_directory_uri()) . 'uploads-stocks/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ouvrir le dossier partagé', 'sempa'); ?></a>
                            </article>
                        </div>
                    </section>

                    <section class="stockpilot-section" id="stockpilot-settings" aria-labelledby="stockpilot-settings-title">
                        <div class="section-header">
                            <div>
                                <p class="section-eyebrow"><?php esc_html_e('Automations', 'sempa'); ?></p>
                                <h2 id="stockpilot-settings-title"><?php esc_html_e('Raccourcis d\'administration', 'sempa'); ?></h2>
                                <p class="section-subtitle"><?php esc_html_e('Activez les fonctionnalités clés de StockPilot pour gagner du temps.', 'sempa'); ?></p>
                            </div>
                        </div>
                        <div class="automation-grid">
                            <article class="automation-card">
                                <h3><?php esc_html_e('Recherche produits', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Filtrage instantané par référence, catégorie ou fournisseur.', 'sempa'); ?></p>
                                <span class="automation-status automation-status--active"><?php esc_html_e('Actif', 'sempa'); ?></span>
                            </article>
                            <article class="automation-card">
                                <h3><?php esc_html_e('Filtres avancés', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Combinez plusieurs critères pour isoler vos segments critiques.', 'sempa'); ?></p>
                                <span class="automation-status automation-status--active"><?php esc_html_e('Actif', 'sempa'); ?></span>
                            </article>
                            <article class="automation-card">
                                <h3><?php esc_html_e('Export CSV', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Synchronisez vos données avec vos outils BI en un clic.', 'sempa'); ?></p>
                                <span class="automation-status automation-status--active"><?php esc_html_e('Actif', 'sempa'); ?></span>
                            </article>
                            <article class="automation-card">
                                <h3><?php esc_html_e('Alertes automatiques', 'sempa'); ?></h3>
                                <p><?php esc_html_e('Restez informé dès qu\'un stock passe sous le seuil minimum.', 'sempa'); ?></p>
                                <span class="automation-status automation-status--planned"><?php esc_html_e('Bientôt', 'sempa'); ?></span>
                            </article>
                        </div>
                    </section>
                </main>

                <aside class="stockpilot-drawers">
                    <section class="stocks-form" id="stocks-product-panel" hidden>
                        <div class="stocks-form__header">
                            <h2><?php esc_html_e('Fiche produit', 'sempa'); ?></h2>
                            <button type="button" class="stocks-form__close" id="stocks-cancel-product" aria-label="<?php esc_attr_e('Fermer la fiche produit', 'sempa'); ?>"></button>
                        </div>
                        <form id="stock-product-form" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="sempa_stocks_save_product" />
                            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                            <input type="hidden" name="id" value="" />
                            <div class="form-grid">
                                <label>
                                    <span><?php esc_html_e('Référence', 'sempa'); ?> *</span>
                                    <input type="text" name="reference" required />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Désignation', 'sempa'); ?> *</span>
                                    <input type="text" name="designation" required />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Catégorie', 'sempa'); ?></span>
                                    <div class="field-with-action">
                                        <select name="categorie" id="stocks-category-select"></select>
                                        <button type="button" class="link-button" data-action="add-category"><?php esc_html_e('Ajouter', 'sempa'); ?></button>
                                    </div>
                                </label>
                                <label>
                                    <span><?php esc_html_e('Fournisseur', 'sempa'); ?></span>
                                    <div class="field-with-action">
                                        <select name="fournisseur" id="stocks-supplier-select"></select>
                                        <button type="button" class="link-button" data-action="add-supplier"><?php esc_html_e('Ajouter', 'sempa'); ?></button>
                                    </div>
                                </label>
                                <label>
                                    <span><?php esc_html_e('Prix d\'achat (€)', 'sempa'); ?></span>
                                    <input type="number" name="prix_achat" step="0.01" min="0" />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Prix de vente (€)', 'sempa'); ?></span>
                                    <input type="number" name="prix_vente" step="0.01" min="0" />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Stock actuel', 'sempa'); ?></span>
                                    <input type="number" name="stock_actuel" min="0" />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Stock minimum', 'sempa'); ?></span>
                                    <input type="number" name="stock_minimum" min="0" />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Emplacement', 'sempa'); ?></span>
                                    <input type="text" name="emplacement" />
                                </label>
                                <label>
                                    <span><?php esc_html_e('Date d’entrée', 'sempa'); ?></span>
                                    <input type="date" name="date_entree" />
                                </label>
                                <fieldset class="condition-field">
                                    <legend><?php esc_html_e('Condition du matériel', 'sempa'); ?></legend>
                                    <div class="condition-toggle">
                                        <label>
                                            <input type="radio" name="condition_materiel" value="neuf" checked />
                                            <span><?php esc_html_e('Matériel neuf', 'sempa'); ?></span>
                                        </label>
                                        <label>
                                            <input type="radio" name="condition_materiel" value="reconditionne" />
                                            <span><?php esc_html_e('Reconditionné', 'sempa'); ?></span>
                                        </label>
                                    </div>
                                </fieldset>
                                <label class="file">
                                    <span><?php esc_html_e('Document (PDF ou image)', 'sempa'); ?></span>
                                    <input type="file" name="document" accept=".pdf,image/*" />
                                </label>
                                <label class="notes">
                                    <span><?php esc_html_e('Notes internes', 'sempa'); ?></span>
                                    <textarea name="notes" rows="4"></textarea>
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="button button--primary"><?php esc_html_e('Enregistrer', 'sempa'); ?></button>
                                <button type="button" class="button button--ghost" data-dismiss="product"><?php esc_html_e('Annuler', 'sempa'); ?></button>
                            </div>
                        </form>
                        <aside class="meta" id="stocks-product-meta"></aside>
                    </section>

                    <section class="stocks-form" id="stocks-movement-panel" hidden>
                        <div class="stocks-form__header">
                            <h2><?php esc_html_e('Ajouter un mouvement', 'sempa'); ?></h2>
                            <button type="button" class="stocks-form__close" id="stocks-cancel-movement" aria-label="<?php esc_attr_e('Fermer le formulaire mouvement', 'sempa'); ?>"></button>
                        </div>
                        <form id="stock-movement-form">
                            <input type="hidden" name="action" value="sempa_stocks_record_movement" />
                            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                            <div class="form-grid">
                                <label>
                                    <span><?php esc_html_e('Produit concerné', 'sempa'); ?></span>
                                    <select name="produit_id" id="movement-product"></select>
                                </label>
                                <label>
                                    <span><?php esc_html_e('Type de mouvement', 'sempa'); ?></span>
                                    <select name="type_mouvement">
                                        <option value="entree"><?php esc_html_e('Entrée', 'sempa'); ?></option>
                                        <option value="sortie"><?php esc_html_e('Sortie', 'sempa'); ?></option>
                                        <option value="ajustement"><?php esc_html_e('Ajustement', 'sempa'); ?></option>
                                    </select>
                                </label>
                                <label>
                                    <span><?php esc_html_e('Quantité', 'sempa'); ?></span>
                                    <input type="number" name="quantite" min="0" required />
                                </label>
                                <label class="notes">
                                    <span><?php esc_html_e('Motif / commentaire', 'sempa'); ?></span>
                                    <textarea name="motif" rows="3"></textarea>
                                </label>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="button button--primary"><?php esc_html_e('Enregistrer le mouvement', 'sempa'); ?></button>
                                <button type="button" class="button button--ghost" data-dismiss="movement"><?php esc_html_e('Annuler', 'sempa'); ?></button>
                            </div>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
