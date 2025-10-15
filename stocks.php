<?php
/**
 * Template Name: Gestion des stocks SEMPA
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$allowed = class_exists('Sempa_Stocks_App') ? Sempa_Stocks_App::user_is_allowed() : false;
$nonce = class_exists('Sempa_Stocks_App') ? Sempa_Stocks_App::nonce() : wp_create_nonce('sempa_stocks_nonce');
?>
<div class="sempa-stocks-wrapper" data-stock-nonce="<?php echo esc_attr($nonce); ?>">
    <?php if (!$allowed) : ?>
        <section class="stocks-locked">
            <div class="stocks-locked__inner">
                <h1><?php esc_html_e('Accès réservé', 'sempa'); ?></h1>
                <p><?php esc_html_e('Cette application est réservée à l\'équipe SEMPA. Merci de vous connecter avec un compte autorisé.', 'sempa'); ?></p>
                <?php wp_login_form([
                    'label_log_in' => __('Se connecter', 'sempa'),
                    'redirect' => home_url('/stocks'),
                ]); ?>
            </div>
        </section>
    <?php else : ?>
        <header class="stocks-header">
            <div>
                <span class="stocks-badge"><?php esc_html_e('SEMPA', 'sempa'); ?></span>
                <h1><?php esc_html_e('Gestion des stocks SEMPA', 'sempa'); ?></h1>
                <p><?php esc_html_e('Tableau de bord centralisé pour les produits, mouvements et rapports.', 'sempa'); ?></p>
            </div>
            <div class="stocks-actions">
                <a class="button export" href="#" id="stocks-export" data-export="1"><?php esc_html_e('Exporter le stock (CSV)', 'sempa'); ?></a>
                <button type="button" id="stocks-refresh" class="button secondary"><?php esc_html_e('Actualiser', 'sempa'); ?></button>
            </div>
        </header>

        <section class="stocks-dashboard" aria-labelledby="stocks-dashboard-title">
            <h2 id="stocks-dashboard-title"><?php esc_html_e('Synthèse des stocks', 'sempa'); ?></h2>
            <div class="dashboard-cards" id="stocks-dashboard-cards">
                <article class="card">
                    <h3><?php esc_html_e('Produits actifs', 'sempa'); ?></h3>
                    <p class="value" data-dashboard="produits">0</p>
                </article>
                <article class="card">
                    <h3><?php esc_html_e('Unités en stock', 'sempa'); ?></h3>
                    <p class="value" data-dashboard="unites">0</p>
                </article>
                <article class="card">
                    <h3><?php esc_html_e('Valeur d\'achat estimée', 'sempa'); ?></h3>
                    <p class="value" data-dashboard="valeur">0 €</p>
                </article>
            </div>
            <div class="dashboard-lists">
                <div class="alerts">
                    <h3><?php esc_html_e('Alertes stock minimum', 'sempa'); ?></h3>
                    <ul id="stocks-alerts" class="list"></ul>
                </div>
                <div class="recent">
                    <h3><?php esc_html_e('Derniers mouvements', 'sempa'); ?></h3>
                    <ul id="stocks-recent" class="list"></ul>
                </div>
            </div>
        </section>

        <section class="stocks-management" aria-labelledby="stocks-products-title">
            <div class="section-head">
                <div>
                    <h2 id="stocks-products-title"><?php esc_html_e('Produits', 'sempa'); ?></h2>
                    <p><?php esc_html_e('Ajoutez, éditez ou supprimez les produits de votre catalogue.', 'sempa'); ?></p>
                </div>
                <div class="section-actions">
                    <input type="search" id="stocks-search" placeholder="<?php esc_attr_e('Rechercher par référence ou désignation…', 'sempa'); ?>" />
                    <button type="button" class="button primary" id="stocks-open-product-form"><?php esc_html_e('Nouvel article', 'sempa'); ?></button>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="stocks-table" id="stocks-products-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Référence', 'sempa'); ?></th>
                            <th><?php esc_html_e('Désignation', 'sempa'); ?></th>
                            <th><?php esc_html_e('Catégorie', 'sempa'); ?></th>
                            <th><?php esc_html_e('Fournisseur', 'sempa'); ?></th>
                            <th><?php esc_html_e('Stock', 'sempa'); ?></th>
                            <th><?php esc_html_e('Min.', 'sempa'); ?></th>
                            <th><?php esc_html_e('Valeur', 'sempa'); ?></th>
                            <th class="actions">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="empty"><?php esc_html_e('Chargement des produits…', 'sempa'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="stocks-form" id="stocks-product-panel" hidden>
            <h2><?php esc_html_e('Fiche produit', 'sempa'); ?></h2>
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
                        <span><?php esc_html_e('Stock maximum', 'sempa'); ?></span>
                        <input type="number" name="stock_maximum" min="0" />
                    </label>
                    <label>
                        <span><?php esc_html_e('Emplacement', 'sempa'); ?></span>
                        <input type="text" name="emplacement" />
                    </label>
                    <label>
                        <span><?php esc_html_e('Date d\'entrée', 'sempa'); ?></span>
                        <input type="date" name="date_entree" />
                    </label>
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
                    <button type="submit" class="button primary"><?php esc_html_e('Enregistrer', 'sempa'); ?></button>
                    <button type="button" class="button" id="stocks-cancel-product"><?php esc_html_e('Annuler', 'sempa'); ?></button>
                </div>
            </form>
            <aside class="meta" id="stocks-product-meta"></aside>
        </section>

        <section class="stocks-movements" aria-labelledby="stocks-movements-title">
            <div class="section-head">
                <div>
                    <h2 id="stocks-movements-title"><?php esc_html_e('Mouvements de stock', 'sempa'); ?></h2>
                    <p><?php esc_html_e('Enregistrez les entrées, sorties et ajustements pour un suivi précis.', 'sempa'); ?></p>
                </div>
                <button type="button" class="button secondary" id="stocks-open-movement-form"><?php esc_html_e('Nouveau mouvement', 'sempa'); ?></button>
            </div>
            <div class="table-wrapper">
                <table class="stocks-table" id="stocks-movements-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'sempa'); ?></th>
                            <th><?php esc_html_e('Produit', 'sempa'); ?></th>
                            <th><?php esc_html_e('Type', 'sempa'); ?></th>
                            <th><?php esc_html_e('Quantité', 'sempa'); ?></th>
                            <th><?php esc_html_e('Ancien/Nouveau', 'sempa'); ?></th>
                            <th><?php esc_html_e('Motif', 'sempa'); ?></th>
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

        <section class="stocks-form" id="stocks-movement-panel" hidden>
            <h2><?php esc_html_e('Ajouter un mouvement', 'sempa'); ?></h2>
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
                    <button type="submit" class="button primary"><?php esc_html_e('Enregistrer le mouvement', 'sempa'); ?></button>
                    <button type="button" class="button" id="stocks-cancel-movement"><?php esc_html_e('Annuler', 'sempa'); ?></button>
                </div>
            </form>
        </section>

        <section class="stocks-reports" aria-labelledby="stocks-reports-title">
            <h2 id="stocks-reports-title"><?php esc_html_e('Rapports & documents', 'sempa'); ?></h2>
            <div class="reports-grid">
                <article class="report">
                    <h3><?php esc_html_e('Rapport valeur du stock', 'sempa'); ?></h3>
                    <p><?php esc_html_e('Téléchargez la photographie financière actuelle du stock SEMPA.', 'sempa'); ?></p>
                    <a href="#" class="button" data-trigger="export"><?php esc_html_e('Exporter au format CSV', 'sempa'); ?></a>
                </article>
                <article class="report">
                    <h3><?php esc_html_e('Documents techniques', 'sempa'); ?></h3>
                    <p><?php esc_html_e('Les documents PDF et images attachés aux fiches produits sont disponibles depuis la liste principale.', 'sempa'); ?></p>
                    <a class="button secondary" href="<?php echo esc_url(trailingslashit(get_stylesheet_directory_uri()) . 'uploads-stocks/'); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Ouvrir le dossier', 'sempa'); ?></a>
                </article>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php
get_footer();
