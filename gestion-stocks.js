(function ($) {
    'use strict';

    if (typeof SempaStocksData === 'undefined') {
        return;
    }

    const state = {
        products: [],
        movements: [],
        categories: [],
        suppliers: [],
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

    document.querySelector('#stocks-open-movement-form')?.addEventListener('click', () => {
        openMovementForm();
    });

    document.querySelector('#stocks-cancel-movement')?.addEventListener('click', () => {
        movementForm?.reset();
        hidePanel(movementPanel);
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
            } else if (action === 'add-supplier') {
                createSupplier();
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
                        const product = state.products.find((item) => item.id === response.data.movement.produit_id);
                        if (product) {
                            product.stock_actuel = response.data.movement.nouveau_stock;
                            renderProducts();
                        }
                        movementForm.reset();
                        hidePanel(movementPanel);
                    }
                })
                .catch(showError);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            renderProducts(searchInput.value);
        });
    }

    function loadAll() {
        Promise.all([
            request('sempa_stocks_dashboard'),
            request('sempa_stocks_products'),
            request('sempa_stocks_movements'),
            request('sempa_stocks_reference_data')
        ])
            .then(([dashboardData, productData, movementData, referenceData]) => {
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
                    state.suppliers = referenceData.data.suppliers || [];
                    populateSelects();
                }
            })
            .catch(showError);
    }

    function request(action, formData) {
        const data = formData instanceof FormData ? formData : new FormData();
        if (!(formData instanceof FormData)) {
            Object.entries(formData || {}).forEach(([key, value]) => {
                data.append(key, value);
            });
        }
        if (typeof data.set === 'function') {
            data.set('action', action);
            data.set('nonce', SempaStocksData.nonce);
        } else {
            data.append('action', action);
            data.append('nonce', SempaStocksData.nonce);
        }

        return fetch(SempaStocksData.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        }).then((response) => response.json());
    }

    function renderDashboard(data) {
        if (!data) {
            return;
        }

        if (data.totals) {
            const totals = data.totals;
            setMetricValue(document.querySelector('[data-dashboard="produits"]'), (totals.produits ?? 0).toString());
            setMetricValue(document.querySelector('[data-dashboard="unites"]'), (totals.unites ?? 0).toString());
            setMetricValue(document.querySelector('[data-dashboard="valeur"]'), formatCurrency(totals.valeur));
        }

        if (Array.isArray(data.alerts)) {
            alertsList.innerHTML = '';
            if (!data.alerts.length) {
                alertsList.innerHTML = `<li class="empty">${escapeHtml('Aucune alerte.')}</li>`;
            } else {
                data.alerts.forEach((alert) => {
                    const status = stockStatus(alert.stock_actuel, alert.stock_minimum);
                    const current = Number(alert.stock_actuel ?? 0);
                    const minimum = Number(alert.stock_minimum ?? 0);
                    const li = document.createElement('li');
                    li.className = `alert-item alert-item--${status}`;
                    li.innerHTML = `
                        <div class="alert-item__main">
                            <strong>${escapeHtml(alert.reference)}</strong>
                            <span class="alert-item__designation">${escapeHtml(alert.designation)}</span>
                        </div>
                        <div class="alert-item__meta">
                            <span class="status-pill status-pill--${status}">${escapeHtml(statusLabel(status))}</span>
                            <span class="alert-item__count">${current} / ${minimum}</span>
                        </div>`;
                    alertsList.appendChild(li);
                });
            }
        }

        if (Array.isArray(data.recent)) {
            recentList.innerHTML = '';
            if (!data.recent.length) {
                recentList.innerHTML = `<li class="empty">${escapeHtml('Aucun mouvement récent.')}</li>`;
            } else {
                data.recent.forEach((movement) => {
                    const tone = movementTone(movement.type_mouvement);
                    const label = labelMovement(movement.type_mouvement);
                    const quantity = movement.quantite ?? 0;
                    const li = document.createElement('li');
                    li.className = 'recent-item';
                    li.dataset.type = tone;
                    li.innerHTML = `
                        <div class="recent-item__header">
                            <span class="recent-item__title">${escapeHtml(movement.reference)} – ${escapeHtml(movement.designation)}</span>
                        </div>
                        <div class="recent-item__meta">
                            <span class="movement-chip movement-chip--${tone}">${escapeHtml(label)}</span>
                            <span class="recent-item__quantity">${escapeHtml(String(quantity))}</span>
                            <span class="recent-item__date">${escapeHtml(formatDate(movement.date_mouvement))}</span>
                        </div>`;
                    recentList.appendChild(li);
                });
            }
        }
    }

    function renderProducts(search = '') {
        if (!productTable) {
            return;
        }

        const query = search.trim().toLowerCase();
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
                const tr = document.createElement('tr');
                tr.dataset.id = product.id;
                tr.dataset.status = status;
                tr.innerHTML = `
                    <td>${escapeHtml(product.reference)}</td>
                    <td>
                        <span class="designation">${escapeHtml(product.designation)}</span>
                        ${documentUrl ? `<a class="file-link" href="${escapeAttribute(documentUrl)}" target="_blank" rel="noopener">PDF</a>` : ''}
                    </td>
                    <td>${escapeHtml(product.categorie)}</td>
                    <td>${escapeHtml(product.fournisseur)}</td>
                    <td>
                        <div class="stock-cell">
                            <span class="stock-cell__value">${stockActual}</span>
                            <span class="status-pill status-pill--${status}">${escapeHtml(statusLabel(status))}</span>
                        </div>
                    </td>
                    <td>${stockMinimum}</td>
                    <td>${value}</td>
                    <td class="actions">
                        <button type="button" class="link" data-action="edit">${escapeHtml('Modifier')}</button>
                        <button type="button" class="link danger" data-action="delete">${escapeHtml('Supprimer')}</button>
                    </td>`;
                return tr;
            });

        productTable.innerHTML = '';
        if (!rows.length) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="8" class="empty">${escapeHtml('Aucun produit trouvé')}</td>`;
            productTable.appendChild(row);
        } else {
            rows.forEach((row) => productTable.appendChild(row));
        }
    }

    function renderMovements() {
        if (!movementTable) {
            return;
        }

        movementTable.innerHTML = '';
        if (!state.movements.length) {
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="6" class="empty">${escapeHtml('Aucun mouvement enregistré')}</td>`;
            movementTable.appendChild(row);
            return;
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
    }

    function populateSelects() {
        const categorySelect = document.querySelector('#stocks-category-select');
        const supplierSelect = document.querySelector('#stocks-supplier-select');
        const movementSelect = document.querySelector('#movement-product');

        if (categorySelect) {
            const current = categorySelect.value;
            categorySelect.innerHTML = '<option value="">—</option>';
            state.categories.forEach((category) => {
                const option = document.createElement('option');
                option.value = category.nom;
                option.textContent = category.nom;
                categorySelect.appendChild(option);
            });
            if (current) {
                categorySelect.value = current;
            }
        }

        if (supplierSelect) {
            const currentSupplier = supplierSelect.value;
            supplierSelect.innerHTML = '<option value="">—</option>';
            state.suppliers.forEach((supplier) => {
                const option = document.createElement('option');
                option.value = supplier.nom;
                option.textContent = supplier.nom;
                supplierSelect.appendChild(option);
            });
            if (currentSupplier) {
                supplierSelect.value = currentSupplier;
            }
        }

        if (movementSelect) {
            updateMovementSelect();
        }
    }

    function updateMovementSelect() {
        const movementSelect = document.querySelector('#movement-product');
        if (!movementSelect) {
            return;
        }
        movementSelect.innerHTML = '';
        state.products.forEach((product) => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.reference} – ${product.designation}`;
            movementSelect.appendChild(option);
        });
    }

    function openMovementForm() {
        if (movementForm) {
            movementForm.reset();
        }
        updateMovementSelect();
        showPanel(movementPanel);
    }

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
            productForm.querySelector('[name="fournisseur"]').value = product.fournisseur || '';
            productForm.querySelector('[name="prix_achat"]').value = product.prix_achat || '';
            productForm.querySelector('[name="prix_vente"]').value = product.prix_vente || '';
            productForm.querySelector('[name="stock_actuel"]').value = product.stock_actuel || 0;
            productForm.querySelector('[name="stock_minimum"]').value = product.stock_minimum || 0;
            productForm.querySelector('[name="stock_maximum"]').value = product.stock_maximum || 0;
            productForm.querySelector('[name="emplacement"]').value = product.emplacement || '';
            productForm.querySelector('[name="date_entree"]').value = product.date_entree || '';
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
        if (productMeta) {
            productMeta.innerHTML = '';
        }
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
                } else {
                    throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                }
            })
            .catch(showError);
    }

    function showPanel(panel) {
        if (!panel) {
            return;
        }
        panel.removeAttribute('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function hidePanel(panel) {
        panel?.setAttribute('hidden', 'hidden');
    }

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
        const color = window.prompt('Couleur hexadécimale (#f4a412 par défaut)', '#f4a412') || '#f4a412';
        const data = new FormData();
        data.append('nom', name.trim());
        data.append('couleur', color.trim());
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

    function createSupplier() {
        const name = window.prompt('Nom du fournisseur ?');
        if (!name) {
            return;
        }
        const contact = window.prompt('Nom du contact (optionnel)') || '';
        const phone = window.prompt('Téléphone (optionnel)') || '';
        const email = window.prompt('Email (optionnel)') || '';
        const data = new FormData();
        data.append('nom', name.trim());
        data.append('contact', contact.trim());
        data.append('telephone', phone.trim());
        data.append('email', email.trim());
        request('sempa_stocks_save_supplier', data)
            .then((response) => {
                if (response?.success && response.data?.supplier) {
                    state.suppliers.push(response.data.supplier);
                    populateSelects();
                    const select = document.querySelector('#stocks-supplier-select');
                    if (select) {
                        select.value = response.data.supplier.nom;
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
        if (min > 0 && stock <= min) {
            return 'warning';
        }
        return 'normal';
    }

    function statusLabel(status) {
        switch (status) {
            case 'critical':
                return 'Urgent';
            case 'warning':
                return 'À surveiller';
            default:
                return 'Normal';
        }
    }

    function movementTone(type) {
        switch (type) {
            case 'entree':
                return 'entry';
            case 'sortie':
                return 'exit';
            case 'ajustement':
                return 'adjust';
            default:
                return 'neutral';
        }
    }

    function setMetricValue(element, text) {
        if (!element) {
            return;
        }
        const value = text == null ? '' : String(text);
        if (element.textContent !== value) {
            element.textContent = value;
            element.classList.remove('is-updated');
            void element.offsetWidth;
            element.classList.add('is-updated');
        } else {
            element.textContent = value;
        }
    }

    function labelMovement(type) {
        switch (type) {
            case 'entree':
                return 'Entrée';
            case 'sortie':
                return 'Sortie';
            case 'ajustement':
                return 'Ajustement';
            default:
                return type || '';
        }
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value.replace(' ', 'T'));
        return date.toLocaleString('fr-FR');
    }

    function formatCurrency(value) {
        const amount = Number(value || 0);
        return amount.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
    }

    function escapeHtml(value) {
        if (value == null) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/\s/g, '%20');
    }

    function showError(error) {
        const message = error?.message || SempaStocksData.strings.unknownError;
        window.alert(message);
    }

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
                            state.products = state.products.filter((item) => item.id !== product.id);
                            renderProducts(searchInput?.value || '');
                            updateMovementSelect();
                        } else {
                            throw new Error(response?.data?.message || SempaStocksData.strings.unknownError);
                        }
                    })
                    .catch(showError);
            }
        }
    });

    loadAll();
})(jQuery);
