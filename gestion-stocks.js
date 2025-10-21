(function ($) {
    'use strict';

    if (typeof SempaStocksData === 'undefined') {
        return;
    }

    const state = {
        products: [],
        movements: [],
        categories: [],
    };

    const selectors = {
        wrapper: '.sempa-stocks-wrapper',
        dashboardCards: '[data-dashboard]'
    };

    const $wrapper = document.querySelector(selectors.wrapper);
    if (!$wrapper) {
        return;
    }

    const productTable = document.querySelector('#stocks-products-table tbody');
    const movementTable = document.querySelector('#stocks-movements-table tbody');
    const alertsList = document.querySelector('#stocks-alerts');
    const recentList = document.querySelector('#stocks-recent');
    const productPanel = document.querySelector('#stocks-product-panel');
    const productForm = document.querySelector('#stock-product-form');
    const movementPanel = document.querySelector('#stocks-movement-panel');
    const movementForm = document.querySelector('#stock-movement-form');
    const searchInput = document.querySelector('#stocks-search');
    const productMeta = document.querySelector('#stocks-product-meta');
    const filterCategory = document.querySelector('#stocks-filter-category');
    const filterStatus = document.querySelector('#stocks-filter-status');
    const clearFiltersButton = document.querySelector('#stocks-clear-filters');

    const exports = document.querySelectorAll('[data-trigger="export"], #stocks-export');
    exports.forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            window.location.href = SempaStocksData.exportUrl;
        });
    });

    const refreshButton = document.querySelector('#stocks-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', () => {
            loadAll();
        });
    }

    document.querySelector('#stocks-open-product-form')?.addEventListener('click', () => {
        openProductForm();
    });

    document.querySelector('#stocks-cancel-product')?.addEventListener('click', () => {
        resetProductForm();
        hidePanel(productPanel);
    });

    document.querySelectorAll('[data-dismiss="product"]').forEach((button) => {
        button.addEventListener('click', () => {
            resetProductForm();
            hidePanel(productPanel);
        });
    });

    document.querySelector('#stocks-open-movement-form')?.addEventListener('click', () => {
        ensureMovementOptions()
            .catch((error) => {
                showError(error);
            })
            .finally(() => {
                openMovementForm();
            });
    });

    document.querySelector('#stocks-cancel-movement')?.addEventListener('click', () => {
        movementForm?.reset();
        hidePanel(movementPanel);
    });

    document.querySelectorAll('[data-dismiss="movement"]').forEach((button) => {
        button.addEventListener('click', () => {
            movementForm?.reset();
            hidePanel(movementPanel);
        });
    });

    if (productForm) {
        productForm.addEventListener('submit', (event) => {
            event.preventDefault();
            saveProduct(new FormData(productForm));
        });

        productForm.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const action = target.dataset.action;
            if (!action) {
                return;
            }
            event.preventDefault();
            if (action === 'add-category') {
                createCategory();
            }
        });
    }

    if (movementForm) {
        movementForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(movementForm);
            formData.set('quantite', Math.abs(parseInt(formData.get('quantite'), 10) || 0));
            request('sempa_stocks_record_movement', formData)
                .then((response) => {
                    if (response?.success && response.data?.movement) {
                        state.movements.unshift(response.data.movement);
                        renderMovements();
                        movementForm.reset();
                        hidePanel(movementPanel);
                        loadProducts({ page: state.pagination.page }).catch(showError);
                    } else {
                        throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                    }
                })
                .catch(showError);
        });
    }
}

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimeout);
            searchTimeout = window.setTimeout(() => {
                updateProductFilters({ search: searchInput.value.trim() || '' });
            }, 250);
        });
    }

    filterCategory?.addEventListener('change', () => {
        updateProductFilters({ category: filterCategory.value || '' });
    });

    filterStatus?.addEventListener('change', () => {
        updateProductFilters({ status: filterStatus.value || '' });
    });

    clearFiltersButton?.addEventListener('click', () => {
        if (searchInput) {
            searchInput.value = '';
        }
        if (filterCategory) {
            filterCategory.value = '';
        }
        if (filterStatus) {
            filterStatus.value = '';
        }
        setConditionView('all', { fetch: false });
        state.filters = {
            search: '',
            category: '',
            supplier: '',
            status: '',
            condition: 'all',
        };
        state.pagination.page = 1;
        loadProducts().catch(showError);
    });

    function loadAll() {
        const dashboardPromise = request('sempa_stocks_dashboard');
        const productsPromise = loadProducts({ page: 1 });
        const movementsPromise = request('sempa_stocks_movements');
        const referencePromise = request('sempa_stocks_reference_data');

        Promise.all([dashboardPromise, productsPromise, movementsPromise, referencePromise])
            .then(([dashboardData, productResponse, movementData, referenceData]) => {
                if (dashboardData?.success) {
                    renderDashboard(dashboardData.data);
                }
                if (productData?.success) {
                    state.products = productData.data.products || [];
                    renderProducts();
                    updateMovementSelect();
                }
                if (movementData?.success) {
                    state.movements = movementData.data.movements || [];
                    renderMovements();
                }
                if (referenceData?.success) {
                    state.categories = referenceData.data.categories || [];
                    populateSelects();
                }

                return productResponse;
            })
            .catch(showError);
    }

        if ($result === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Erreur BDD: ' . $wpdb->last_error,
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Contact enregistré.',
        ]);
    }
}

final class Sempa_RankMath
{
    public static function register(): void
    {
        add_filter('rank_math/sitemap/portfolio/enabled', '__return_false');
        add_filter('rank_math/sitemap/post_tag/enabled', '__return_false');
        add_filter('rank_math/sitemap/portfolio_category/enabled', '__return_false');
        add_filter('rank_math/sitemap/page_category/enabled', '__return_false');
    }
}

final class Sempa_Stock_Permissions
{
    public const NAMESPACE_PREFIX = '/sempa-stocks/v1';

    public static function register(): void
    {
        add_filter('rest_authentication_errors', [__CLASS__, 'allow_public_cookie_errors'], 150, 3);
    }

    public static function allow_public_cookie_errors($result, $server, $request)
    {
        if (!is_wp_error($result)) {
            return $result;
        }

        $code = $result->get_error_code();
        if ($code !== 'rest_cookie_invalid_nonce' && $code !== 'nonce_failure') {
            return $result;
        }

        if (!($request instanceof WP_REST_Request)) {
            return $result;
        }

        $route = $request->get_route();
        if (is_string($route) && strpos($route, self::NAMESPACE_PREFIX) === 0) {
            return null;
        }

        return $result;
    }

    public static function allow_public_reads($request = null): bool
    {
        unset($request);
        return true;
    }

    public static function require_or_filter(WP_REST_Request $request)
    {
        $allow = apply_filters('sempa_allow_public_stock_writes', true, $request);
        if ($allow) {
            return true;
        }

        return new WP_Error('rest_forbidden', __('Authentification requise.', 'sempa'), ['status' => 401]);
    }
}

final class Sempa_Stock_Routes
{
    private const ROUTE_NAMESPACE = 'sempa-stocks/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route(self::ROUTE_NAMESPACE, '/products', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_products'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'save_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)', [
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_products'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'save_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_product'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)/photo', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'upload_photo'],
            'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/products/(?P<id>\d+)/history', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'get_history'],
            'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/movements', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_movements'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_movement'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/categories', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'get_categories'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'allow_public_reads'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'create_category'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);

        register_rest_route(self::ROUTE_NAMESPACE, '/categories/(?P<id>\d+)', [
            'args' => [
                'id' => [
                    'validate_callback' => [__CLASS__, 'validate_positive_int'],
                ],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [__CLASS__, 'delete_category'],
                'permission_callback' => [Sempa_Stock_Permissions::class, 'require_or_filter'],
            ],
        ]);
    }

    public static function validate_positive_int($value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }

    public static function get_products(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if ($id > 0) {
            $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
            if (!$product) {
                return new WP_Error('not_found', __('Produit introuvable.', 'sempa'), ['status' => 404]);
            }

            $product = self::hydrate_components($product, $wpdb);
            return rest_ensure_response(Sempa_Utils::normalize_product($product));
        }

        $products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}products ORDER BY name ASC", ARRAY_A);
        $products = array_map([Sempa_Utils::class, 'normalize_product'], $products);

        return rest_ensure_response([
            'products' => $products,
        ]);
    }

    function setProductsLoading(isLoading) {
        state.isLoadingProducts = isLoading;
        if (isLoading && productTable) {
            productTable.innerHTML = `<tr><td colspan="6" class="empty">${escapeHtml(SempaStocksData?.strings?.loadingProducts || 'Chargement des produits…')}</td></tr>`;
        }
    }

    function renderProductsError(message) {
        if (!productTable) {
            return;
        }
        productTable.innerHTML = `<tr><td colspan="6" class="empty">${escapeHtml(message)}</td></tr>`;
        renderPagination(true);
    }

        const searchValue = (typeof search === 'string' && search.length >= 0 ? search : '') || (searchInput?.value || '');
        const query = searchValue.trim().toLowerCase();
        const categoryFilter = filterCategory?.value?.toLowerCase() || '';
        const statusFilter = filterStatus?.value || '';

        const rows = state.products
            .filter((product) => {
                if (!query) {
                    return true;
                }
                return (
                    (product.reference || '').toLowerCase().includes(query) ||
                    (product.designation || '').toLowerCase().includes(query)
                );
            })
            .filter((product) => {
                const productCategory = (product.categorie || '').toLowerCase();
                const status = stockStatus(product.stock_actuel, product.stock_minimum);

                if (categoryFilter && productCategory !== categoryFilter) {
                    return false;
                }
                if (statusFilter && status !== statusFilter) {
                    return false;
                }

                return true;
            })
            .map((product) => {
                const documentUrl = product.document_pdf
                    ? (product.document_pdf.startsWith('http')
                        ? product.document_pdf
                        : SempaStocksData.uploadsUrl + product.document_pdf.replace(/^uploads-stocks\//, ''))
                    : '';
                const stockActual = Number(product.stock_actuel ?? 0);
                const stockMinimum = Number(product.stock_minimum ?? 0);
                const status = stockStatus(stockActual, stockMinimum);
                const value = formatCurrency((Number(product.prix_achat) || 0) * stockActual);
                const meta = product.categorie ? product.categorie : '';
                const tr = document.createElement('tr');
                tr.dataset.id = product.id;
                tr.dataset.status = status;
                tr.innerHTML = `
                    <td>
                        <div class="product-cell">
                            <span class="product-cell__name">${escapeHtml(product.designation)}</span>
                            <span class="product-cell__meta">${escapeHtml(meta || '—')}</span>
                        </div>
                    </td>
                    <td>
                        <div class="product-ref">
                            <span class="product-ref__code">${escapeHtml(product.reference)}</span>
                            ${documentUrl ? `<a class="product-ref__doc" href="${escapeAttribute(documentUrl)}" target="_blank" rel="noopener">PDF</a>` : ''}
                        </div>
                    </td>
                    <td>
                        <div class="stock-level">
                            <span class="stock-level__value">${stockActual}</span>
                            <span class="stock-level__hint">${escapeHtml(`Min ${stockMinimum}`)}</span>
                            <span class="stock-level__value-secondary">${escapeHtml(value)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge status-badge--${statusClassName(status)}">${escapeHtml(statusLabel(status))}</span>
                    </td>
                    <td class="actions">
                        <details class="actions-menu">
                            <summary class="actions-trigger" aria-label="${escapeAttribute(SempaStocksData?.strings?.productActions || 'Actions produit')}"><span aria-hidden="true">⋮</span></summary>
                            <div class="actions-menu__content">
                                <button type="button" data-action="edit">${escapeHtml('Modifier')}</button>
                                <button type="button" data-action="delete">${escapeHtml('Supprimer')}</button>
                            </div>
                        </details>
                    </td>`;
                return tr;
            });

        productTable.innerHTML = '';

        if (!state.products.length) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="5" class="empty">${escapeHtml(SempaStocksData?.strings?.noProducts || 'Aucun produit trouvé')}</td>`;
            productTable.appendChild(row);
            renderPagination();
            return;
        }

        state.products.forEach((product) => {
            const documentUrl = product.document_pdf
                ? (product.document_pdf.startsWith('http')
                    ? product.document_pdf
                    : SempaStocksData.uploadsUrl + product.document_pdf.replace(/^uploads-stocks\//, ''))
                : '';
            const stockActual = Number(product.stock_actuel ?? 0);
            const stockMinimum = Number(product.stock_minimum ?? 0);
            const status = stockStatus(stockActual, stockMinimum);
            const value = formatCurrency((Number(product.prix_achat) || 0) * stockActual);
            const meta = [product.categorie, product.fournisseur].filter(Boolean).join(' • ');
            const condition = getProductCondition(product);
            const tr = document.createElement('tr');
            tr.dataset.id = product.id;
            tr.innerHTML = `
                <td>
                    <div class="product-cell">
                        <span class="product-cell__name">${escapeHtml(product.designation)}</span>
                        <span class="product-cell__meta">${escapeHtml(meta || '—')}</span>
                    </div>
                </td>
                <td>
                    <div class="product-ref">
                        <span class="product-ref__code">${escapeHtml(product.reference)}</span>
                        ${documentUrl ? `<a class="product-ref__doc" href="${escapeAttribute(documentUrl)}" target="_blank" rel="noopener">PDF</a>` : ''}
                    </div>
                </td>
                <td>
                    <div class="stock-level">
                        <span class="stock-level__value">${stockActual}</span>
                        <span class="stock-level__hint">${escapeHtml(`Min ${stockMinimum}`)}</span>
                        <span class="stock-level__value-secondary">${escapeHtml(value)}</span>
                    </div>
                </td>
                <td>
                    <span class="status-badge status-badge--${statusClassName(status)}">${escapeHtml(statusLabel(status))}</span>
                </td>
                <td>
                    <span class="condition-chip condition-chip--${condition}">${escapeHtml(conditionLabel(condition))}</span>
                </td>
                <td class="actions">
                    <details class="actions-menu">
                        <summary class="actions-trigger" aria-label="${escapeAttribute(SempaStocksData?.strings?.productActions || 'Actions produit')}"><span aria-hidden="true">⋮</span></summary>
                        <div class="actions-menu__content">
                            <button type="button" data-action="edit">${escapeHtml('Modifier')}</button>
                            <button type="button" data-action="delete">${escapeHtml('Supprimer')}</button>
                        </div>
                    </details>
                </td>`;
            productTable.appendChild(tr);
        });

        renderPagination();
    }

    function renderPagination(force = false) {
        if (!paginationWrapper) {
            return;
        }

        const total = Number(state.pagination.total) || 0;
        const page = Number(state.pagination.page) || 1;
        const perPage = Number(state.pagination.perPage) || PRODUCTS_PER_PAGE;
        const totalPages = Math.max(1, Number(state.pagination.totalPages) || Math.ceil(total / perPage) || 1);

        paginationWrapper.classList.toggle('is-hidden', !force && total <= perPage && totalPages <= 1);

        if (paginationPrev) {
            paginationPrev.disabled = page <= 1;
        }
        if (paginationNext) {
            paginationNext.disabled = page >= totalPages;
        }

        state.movements.forEach((movement) => {
            const tr = document.createElement('tr');
            const tone = movementTone(movement.type_mouvement);
            const label = labelMovement(movement.type_mouvement);
            const quantity = movement.quantite ?? 0;
            tr.dataset.type = tone;
            tr.innerHTML = `
                <td>${escapeHtml(formatDate(movement.date_mouvement))}</td>
                <td>${escapeHtml(movement.reference)} – ${escapeHtml(movement.designation)}</td>
                <td><span class="movement-chip movement-chip--${tone}">${escapeHtml(label)}</span></td>
                <td><span class="movement-qty">${escapeHtml(String(quantity))}</span></td>
                <td>${movement.ancien_stock ?? 0} ➜ ${movement.nouveau_stock ?? 0}</td>
                <td>${escapeHtml(movement.motif || '')}</td>`;
            movementTable.appendChild(tr);
        });
        if (fetch) {
            state.pagination.page = 1;
            loadProducts({ page: 1 }).catch(showError);
        }
    }

    function populateSelects() {
        const categorySelect = document.querySelector('#stocks-category-select');
        const movementSelect = document.querySelector('#movement-product');

        setProductsLoading(true);

        return request('sempa_stocks_products', payload)
            .then((response) => {
                if (!response?.success) {
                    throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                }

                const products = Array.isArray(response.data?.products) ? response.data.products : [];
                state.products = products.map(normalizeProduct);
                addProductOptions(state.products);

                const pagination = response.data?.pagination || {};
                state.pagination.page = Number(pagination.page) || page;
                state.pagination.perPage = Number(pagination.per_page) || perPage;
                state.pagination.total = Number(pagination.total) || state.products.length;
                state.pagination.totalPages = Math.max(
                    1,
                    Number(pagination.total_pages) || Math.ceil(state.pagination.total / state.pagination.perPage) || 1
                );

                setProductsLoading(false);
                renderProducts();
                updateMovementSelect();

                return response;
            })
            .catch((error) => {
                setProductsLoading(false);
                state.products = [];
                state.pagination.page = 1;
                state.pagination.total = 0;
                state.pagination.totalPages = 1;
                renderProductsError(error?.message || SempaStocksData.strings.unknownError);
                throw error;
            });
    }

    function addProductOptions(products = []) {
        products.forEach((product) => {
            if (!product || product.id == null) {
                return;
            }
            const id = Number(product.id);
            productOptions.set(id, {
                id,
                reference: product.reference || '',
                designation: product.designation || '',
            });
        });
    }

    function removeProductOption(id) {
        productOptions.delete(Number(id));
    }

    function ensureMovementOptions() {
        if (movementOptionsLoaded) {
            return Promise.resolve();
        }
        if (productOptionsRequest) {
            return productOptionsRequest;
        }

        if (filterCategory) {
            const currentFilter = state.filters.category || '';
            filterCategory.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = (SempaStocksData?.strings?.allCategories) || 'Toutes les catégories';
            filterCategory.appendChild(placeholder);
            state.categories.forEach((category) => {
                const option = document.createElement('option');
                option.value = category.nom || '';
                option.textContent = category.nom;
                filterCategory.appendChild(option);
            });
            if (currentFilter) {
                filterCategory.value = currentFilter;
            }

        if (movementSelect) {
            updateMovementSelect();
        }

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }
        const options = Array.from(productOptions.values());
        movementSelect.innerHTML = '';
        options
            .sort((a, b) => a.designation.localeCompare(b.designation, 'fr', { sensitivity: 'base' }))
            .forEach((product) => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.reference} – ${product.designation}`;
                movementSelect.appendChild(option);
            });
    }

        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $id), ARRAY_A);
        $product = self::hydrate_components($product, $wpdb);

    function openProductForm(product = null) {
        productForm?.reset();
        if (!productForm) {
            return;
        }
        if (product) {
            productForm.querySelector('[name="id"]').value = product.id || '';
            productForm.querySelector('[name="reference"]').value = product.reference || '';
            productForm.querySelector('[name="designation"]').value = product.designation || '';
            productForm.querySelector('[name="categorie"]').value = product.categorie || '';
            productForm.querySelector('[name="prix_achat"]').value = product.prix_achat || '';
            productForm.querySelector('[name="prix_vente"]').value = product.prix_vente || '';
            productForm.querySelector('[name="stock_actuel"]').value = product.stock_actuel || 0;
            productForm.querySelector('[name="stock_minimum"]').value = product.stock_minimum || 0;
            productForm.querySelector('[name="notes"]').value = product.notes || '';
            renderMeta(product);
        } else if (productMeta) {
            productMeta.innerHTML = '';
        }
        showPanel(productPanel);
    }

    function resetProductForm() {
        if (productForm) {
            productForm.reset();
            productForm.querySelector('[name="id"]').value = '';
        }

        $attachment_id = media_handle_sideload($file, 0, sprintf(__('Photo du produit %d', 'sempa'), $id));
        if (is_wp_error($attachment_id)) {
            return new WP_Error('upload_error', $attachment_id->get_error_message(), ['status' => 500]);
        }

    function saveProduct(formData) {
        request('sempa_stocks_save_product', formData)
            .then((response) => {
                if (response?.success && response.data?.product) {
                    const product = response.data.product;
                    const index = state.products.findIndex((item) => item.id === product.id);
                    if (index >= 0) {
                        state.products[index] = product;
                    } else {
                        state.products.push(product);
                    }
                    renderProducts(searchInput?.value || '');
                    updateMovementSelect();
                    hidePanel(productPanel);
                    resetProductForm();
                    state.pagination.page = 1;
                    loadProducts({ page: 1 }).catch(showError);
                } else {
                    throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                }
            })
            .catch(showError);
    }

        $current_user = wp_get_current_user();
        $wpdb->insert($wpdb->prefix . 'product_history', [
            'product_id' => $id,
            'user_name' => $current_user->display_name,
            'action' => __('La photo du produit a été mise à jour.', 'sempa'),
            'timestamp' => current_time('mysql'),
        ]);

        return rest_ensure_response([
            'status' => 'success',
            'imageUrl' => $image_url,
        ]);
    }

    public static function get_history(WP_REST_Request $request)
    {
        global $wpdb;

    function renderMeta(product) {
        if (!productMeta) {
            return;
        }
        const documentUrl = product.document_pdf
            ? (product.document_pdf.startsWith('http')
                ? product.document_pdf
                : SempaStocksData.uploadsUrl + product.document_pdf.replace(/^uploads-stocks\//, ''))
            : '';
        productMeta.innerHTML = `
            <ul>
                <li><strong>${escapeHtml('Créé par')} :</strong> ${escapeHtml(product.ajoute_par || '—')}</li>
                <li><strong>${escapeHtml('Entrée')} :</strong> ${product.date_entree || '—'}</li>
                <li><strong>${escapeHtml('Modifié')} :</strong> ${product.date_modification || '—'}</li>
                ${documentUrl ? `<li><a href="${escapeAttribute(documentUrl)}" target="_blank" rel="noopener">${escapeHtml('Voir le document')}</a></li>` : ''}
            </ul>`;
    }

    function createCategory() {
        const name = window.prompt('Nom de la nouvelle catégorie ?');
        if (!name) {
            return;
        }
        const color = '#f4a412';
        const data = new FormData();
        data.append('nom', name.trim());
        data.append('couleur', color);
        request('sempa_stocks_save_category', data)
            .then((response) => {
                if (response?.success && response.data?.category) {
                    state.categories.push(response.data.category);
                    populateSelects();
                    const select = document.querySelector('#stocks-category-select');
                    if (select) {
                        select.value = response.data.category.nom;
                    }
                } else {
                    throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                }
            })
            .catch(showError);
    }

    function stockStatus(current, minimum) {
        const stock = Number(current ?? 0);
        const min = Number(minimum ?? 0);
        if (stock <= 0) {
            return 'critical';
        }

        $wpdb->update($wpdb->prefix . 'products', [
            'stock' => $new_stock,
            'lastUpdated' => current_time('mysql'),
        ], ['id' => $product_id], ['%d', '%s'], ['%d']);

        $inserted = $wpdb->insert($wpdb->prefix . 'movements', [
            'productId' => $product_id,
            'productName' => $product_name ?: $product['name'],
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'date' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%d', '%s', '%s']);

        if ($inserted === false || !empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error ?: __('Impossible de créer le mouvement.', 'sempa'), ['status' => 500]);
        }

        $current_user = wp_get_current_user();
        $message = '';
        switch ($type) {
            case 'in':
                $message = sprintf(__('Entrée de stock : +%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
                break;
            case 'out':
                $message = sprintf(__('Sortie de stock : -%d (stock actuel : %d). Raison : %s', 'sempa'), $quantity, $new_stock, $reason);
                if ($component_logs) {
                    $message .= ' | ' . __('Composants ajustés : ', 'sempa') . implode(', ', $component_logs);
                }
                break;
            case 'adjust':
                $message = sprintf(__('Stock ajusté à %d (ancien stock : %d). Raison : %s', 'sempa'), $new_stock, $current_stock, $reason);
                break;
        }

        $wpdb->insert($wpdb->prefix . 'product_history', [
            'product_id' => $product_id,
            'user_name' => $current_user->display_name,
            'action' => $message,
            'timestamp' => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s']);

        $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}products WHERE id = %d", $product_id), ARRAY_A);
        $updated = self::hydrate_components($updated, $wpdb);

        return rest_ensure_response([
            'status' => 'success',
            'product' => Sempa_Utils::normalize_product($updated),
        ]);
    }

    public static function get_categories(): WP_REST_Response
    {
        global $wpdb;

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}product_categories ORDER BY name ASC", ARRAY_A);

        return rest_ensure_response($categories);
    }

    public static function create_category(WP_REST_Request $request)
    {
        global $wpdb;

        $data = $request->get_json_params();
        $name = sanitize_text_field($data['name'] ?? '');

        if ($name === '') {
            return new WP_Error('bad_request', __('Le nom de la catégorie est obligatoire.', 'sempa'), ['status' => 400]);
        }

        $wpdb->insert($wpdb->prefix . 'product_categories', [
            'name' => $name,
            'slug' => sanitize_title($name),
        ], ['%s', '%s']);

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response([
            'status' => 'success',
            'id' => (int) $wpdb->insert_id,
        ]);
    }

    public static function delete_category(WP_REST_Request $request)
    {
        global $wpdb;

        $id = (int) $request->get_param('id');
        if ($id <= 0) {
            return new WP_Error('bad_request', __('Identifiant manquant.', 'sempa'), ['status' => 400]);
        }

        $wpdb->delete($wpdb->prefix . 'product_categories', ['id' => $id]);

        if (!empty($wpdb->last_error)) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['status' => 'success']);
    }

    private static function hydrate_components(array $product, \wpdb $wpdb): array
    {
        if (empty($product['is_kit'])) {
            return $product;
        }

        $product['components'] = $wpdb->get_results($wpdb->prepare(
            "SELECT p.id, p.name, p.reference, kc.quantity FROM {$wpdb->prefix}kit_components kc JOIN {$wpdb->prefix}products p ON p.id = kc.component_id WHERE kc.kit_id = %d",
            (int) $product['id']
        ), ARRAY_A);

        return $product;
    }
}

final class Sempa_Login_Redirect
{
    public static function register(): void
    {
        add_filter('login_redirect', [__CLASS__, 'maybe_redirect'], 10, 3);
    }

    public static function maybe_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if (!($user instanceof WP_User)) {
            return $redirect_to;
        }

        $emails = apply_filters('sempa_stock_redirect_emails', [
            'victorfaucher@sempa.fr',
            'jean-baptiste@sempa.fr',
        ]);

        $normalized = array_map('strtolower', $emails);
        if (in_array(strtolower($user->user_email), $normalized, true)) {
            $url = Sempa_Utils::get_stock_app_url();
            if ($url) {
                return $url;
            }
        }

        return $redirect_to;
    }
}

final class Sempa_Utils
{
    public static function parse_currency($value): float
    {
        $sanitized = preg_replace('/[^0-9,.]/', '', (string) $value);
        $sanitized = str_replace(',', '.', $sanitized);

        return (float) $sanitized;
    }

    public static function normalize_product(array $product): array
    {
        $product['id'] = isset($product['id']) ? (int) $product['id'] : 0;
        $product['stock'] = isset($product['stock']) ? (int) $product['stock'] : 0;
        $product['minStock'] = isset($product['minStock']) ? (int) $product['minStock'] : 0;
        $product['purchasePrice'] = isset($product['purchasePrice']) ? (float) $product['purchasePrice'] : 0.0;
        $product['salePrice'] = isset($product['salePrice']) ? (float) $product['salePrice'] : 0.0;
        $product['is_kit'] = !empty($product['is_kit']) ? 1 : 0;

        if (!empty($product['components']) && is_array($product['components'])) {
            $product['components'] = array_map(function ($component) {
                return [
                    'id' => isset($component['id']) ? (int) $component['id'] : 0,
                    'name' => $component['name'] ?? '',
                    'reference' => $component['reference'] ?? '',
                    'quantity' => isset($component['quantity']) ? (int) $component['quantity'] : 0,
                ];
            }, $product['components']);
        }

        return $product;
    }

    public static function get_stock_app_url(): string
    {
        $default = home_url('/gestion-stocks-sempa/');
        $slugs = [
            'stocks',
            'gestion-stocks-sempa',
            'gestion-stocks',
            'app-gestion-stocks',
            'stock-management',
        ];

    productTable?.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }
        const action = target.dataset.action;
        if (!action) {
            return;
        }
        const row = target.closest('tr');
        const id = row ? parseInt(row.dataset.id, 10) : 0;
        const product = state.products.find((item) => item.id === id);
        if (!product) {
            return;
        }
        if (action === 'edit') {
            openProductForm(product);
        } else if (action === 'delete') {
            if (confirm('Supprimer ce produit ?')) {
                const payload = new FormData();
                payload.append('id', String(product.id));
                request('sempa_stocks_delete_product', payload)
                    .then((response) => {
                        if (response?.success) {
                            removeProductOption(product.id);
                            loadProducts({ page: state.pagination.page }).catch(showError);
                        } else {
                            throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                        }
                    })
                    .catch(showError);
            }
        }

    setConditionView(state.filters.condition, { fetch: false });

    loadAll();
})(jQuery);
