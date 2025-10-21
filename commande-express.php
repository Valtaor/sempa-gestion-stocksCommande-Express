<?php
/**
 * Template Name: Commande Express
 * Description: Formulaire de commande SEMPA avec séparation front/back.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="commande-express-template">
    <div class="container">
        <div class="header">
            <h1>Bienvenue sur votre espace commande SEMPA</h1>
            <p class="subtitle">Processus simplifié en 3 étapes.</p>
            <div class="payment-summary-notice">
                <h3>Commande sans paiement immédiat</h3>
                <p>La facturation s'effectuera après validation, selon vos conditions habituelles.</p>
            </div>
            <div class="progress-container">
                <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
                <div class="steps-container">
                    <div class="step active" id="step-indicator-1"><div class="step-number">1</div><div class="step-label">Infos client</div></div>
                    <div class="step" id="step-indicator-2"><div class="step-number">2</div><div class="step-label">Produits</div></div>
                    <div class="step" id="step-indicator-3"><div class="step-number">3</div><div class="step-label">Validation</div></div>
                </div>
            </div>
        </div>

        <form id="orderForm" onsubmit="event.preventDefault(); submitForm();">
            <div class="form-section active" id="section-1">
                <h2 class="section-title">Vos informations</h2>
                <div class="form-grid">
                    <div class="form-group"><label for="clientName" class="required">Nom / Société</label><input type="text" id="clientName" required></div>
                    <div class="form-group"><label for="clientEmail" class="required">Email</label><input type="email" id="clientEmail" required></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="phone" class="required">Téléphone</label><input type="tel" id="phone" required></div>
                    <div class="form-group"><label for="clientNumber">N° de Client (pour un traitement + rapide)</label><input type="text" id="clientNumber" placeholder="Numéro à 6 chiffres 1xxxxx"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="postalCode" class="required">Code Postal</label><input type="text" id="postalCode" required></div>
                    <div class="form-group"><label for="city" class="required">Ville</label><input type="text" id="city" required></div>
                </div>
                <div class="form-group"><label for="orderDate" class="required">Date de commande</label><input type="date" id="orderDate" required></div>
                <div class="button-group">
                    <div></div>
                    <button type="button" class="btn btn-primary" onclick="changeStep(2)">Étape suivante</button>
                </div>
            </div>

            <div class="form-section" id="section-2">
                <h2 class="section-title">Vos produits</h2>
                <div class="product-tabs" id="productTabs"></div>
                <div class="warning-message" id="smoothieWarning">
                    Vous avez dépassé la limite de 3 cartons de smoothies au total. Veuillez ajuster votre commande.
                </div>
                <div id="productGridContainer"></div>
                <div class="order-details" id="orderDetails"></div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="changeStep(1)">Retour</button>
                    <button type="button" class="btn btn-primary" id="nextToStep3" onclick="changeStep(3)">Récapitulatif</button>
                </div>
            </div>

            <div class="form-section" id="section-3">
                <h2 class="section-title">Validation de la commande</h2>
                <div class="form-group"><label for="comments">Instructions spéciales (optionnel)</label><textarea id="comments" rows="4"></textarea></div>
                <div class="form-group"><input type="checkbox" id="sendConfirmationEmail" checked style="width: auto; margin-right: 8px;"><label for="sendConfirmationEmail" style="display: inline;">Recevoir une confirmation par email</label></div>

                <div class="shipping-info" id="shippingInfo">
                    <h4 onclick="toggleShippingInfo()">Information sur les frais de transport (cliquez pour afficher/masquer)</h4>
                    <div class="shipping-info-content">
                        <p>Les frais de transport sont calculés en fonction du nombre de cartons :</p>
                        <ul>
                            <li>1 carton : 21,95 €</li>
                            <li>2 cartons : 26,95 €</li>
                            <li>3 cartons : 36,95 €</li>
                            <li>4 cartons : 43,95 €</li>
                            <li>5 cartons : 49,95 €</li>
                            <li>6 cartons : 49,95 €</li>
                            <li>7 cartons : 53,95 €</li>
                            <li>8 cartons : 53,95 €</li>
                            <li>9 cartons : 56,95 €</li>
                            <li>10 cartons : 56,95 €</li>
                            <li>11 cartons : 64,95 €</li>
                            <li>12 cartons : 64,95 €</li>
                        </ul>
                        <p><strong>Les smoothies ont des frais de transport fixes de 49,90 € HT</strong> car ils sont expédiés séparément (transport réfrigéré).</p>
                    </div>
                </div>

                <div class="summary-card">
                    <h3>Récapitulatif</h3>
                    <div id="finalSummary"></div>
                </div>
                <div class="loading" id="loadingSpinner"><div class="spinner"></div><p>Envoi en cours...</p></div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="changeStep(2)">Modifier</button>
                    <button type="submit" class="btn btn-primary" id="confirmOrderBtn">Confirmer la commande</button>
                </div>
            </div>

            <div class="confirmation" id="confirmationScreen">
                <h2>Commande Confirmée !</h2>
                <p>Merci ! Nous avons bien reçu votre demande et la traiterons dans les plus brefs délais.</p>
                <button type="button" class="btn btn-primary" onclick="location.reload()">Effectuer une nouvelle commande</button>
            </div>
        </form>
    </div>
</main>

<style>
    :root {
        --primary: #f4a412;
        --primary-light: #FFF1EB; --dark: #0F172A; --light: #F8FAFC; --gray: #94A3B8;
        --gray-light: #E2E8F0; --success: #10B981; --error: #EF4444; --warning: #F59E0B; --radius: 12px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1); --shadow-md: 0 4px 6px rgba(0,0,0,0.1); --transition: all 0.3s ease;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: #f9fafb; color: var(--dark); line-height: 1.6; scroll-behavior: smooth; }
    .commande-express-template .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
    .commande-express-template .header { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 30px; margin-bottom: 24px; text-align: center; }
    .commande-express-template .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
    .commande-express-template .header .subtitle { font-size: 16px; color: #64748B; margin-bottom: 20px; }
    .commande-express-template .payment-info { background: #F1F5F9; border-radius: var(--radius); padding: 16px; margin-top: 20px; text-align: left; font-size: 14px; border-left: 4px solid var(--primary); }
    .commande-express-template .payment-info p:not(:last-child) { margin-bottom: 8px; }
    .commande-express-template .highlight-orange { color: var(--primary); font-weight: 600; }
    .commande-express-template .payment-summary-notice { background-color: #F8FAFC; border: 1px solid var(--gray-light); border-radius: var(--radius); padding: 16px 24px; margin-top: 20px; text-align: center; }
    .commande-express-template .payment-summary-notice h3 { font-size: 16px; font-weight: 600; color: var(--dark); margin: 0 0 4px 0; }
    .commande-express-template .payment-summary-notice p { font-size: 14px; color: #64748B; margin: 0; }
    .commande-express-template .progress-container { display: flex; justify-content: center; margin: 30px 0; position: relative; }
    .commande-express-template .progress-bar { position: absolute; top: 50%; transform: translateY(-50%); height: 3px; background: #E2E8F0; width: 80%; z-index: 1; }
    .commande-express-template .progress-fill { height: 100%; background: var(--primary); transition: var(--transition); width: 0%; }
    .commande-express-template .steps-container { display: flex; justify-content: space-between; width: 80%; position: relative; z-index: 2; }
    .commande-express-template .step { display: flex; flex-direction: column; align-items: center; }
    .commande-express-template .step-number { width: 40px; height: 40px; border-radius: 50%; background: white; color: var(--gray); display: flex; align-items: center; justify-content: center; font-weight: 600; margin-bottom: 8px; border: 2px solid var(--gray-light); transition: var(--transition); }
    .commande-express-template .step.active .step-number { background: var(--primary); border-color: var(--primary); color: white; }
    .commande-express-template .step-label { font-size: 14px; font-weight: 500; color: var(--gray); transition: var(--transition); }
    .commande-express-template .step.active .step-label { color: var(--primary); }
    .commande-express-template .form-section { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 30px; margin-bottom: 24px; display: none; }
    .commande-express-template .form-section.active { display: block; }
    .commande-express-template .section-title { font-size: 20px; font-weight: 600; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--gray-light); }
    .commande-express-template .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .commande-express-template .form-group { margin-bottom: 20px; }
    .commande-express-template label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #374151; }
    .commande-express-template .required::after { content: " *"; color: var(--error); }
    .commande-express-template input,
    .commande-express-template textarea { width: 100%; padding: 12px 16px; border: 1px solid #D1D5DB; border-radius: var(--radius); font-size: 16px; }
    .commande-express-template input:focus,
    .commande-express-template textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(244, 164, 18, 0.1); }
    .commande-express-template .product-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
    .commande-express-template .product-tab { padding: 12px 20px; border-radius: var(--radius); background: #F3F4F6; color: #4B5563; font-weight: 500; cursor: pointer; border: none; font-size: 14px; }
    .commande-express-template .product-tab.active { background: var(--primary); color: white; }
    .commande-express-template .category-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #E5E7EB; }
    .commande-express-template .category-header h3 { font-size: 20px; font-weight: 600; color: var(--dark); margin-bottom: 8px; }
    .commande-express-template .category-header p { color: #64748B; line-height: 1.6; font-style: italic; }
    .commande-express-template .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .commande-express-template .product-card { background: white; border-radius: var(--radius); padding: 20px; border: 1px solid var(--gray-light); }
    .commande-express-template .product-card h4 { font-size: 18px; margin-bottom: 12px; }
    .commande-express-template .product-card .details { font-size: 14px; color: #6B7280; margin-bottom: 8px; }
    .commande-express-template .product-card .flavor { font-size: 14px; color: #64748B; margin-bottom: 16px; font-style: italic; }
    .commande-express-template .product-card .price { font-weight: 600; color: var(--primary); font-size: 18px; margin-bottom: 16px; }
    .commande-express-template .product-card .max-notice { font-size: 12px; color: var(--error); margin-top: 8px; text-align: center; }
    .commande-express-template .order-details,
    .commande-express-template .summary-card { margin-top: 24px; background: #F8FAFC; border-radius: var(--radius); padding: 24px; border: 1px solid var(--gray-light); }
    .commande-express-template .order-item { display: flex; justify-content: space-between; padding: 12px 0; }
    .commande-express-template .order-item + .order-item { border-top: 1px solid var(--gray-light); }
    .commande-express-template .order-subtotal { margin-top: 16px; padding-top: 16px; border-top: 2px solid #334155; font-weight: 600; display: flex; justify-content: space-between; }
    .commande-express-template .button-group { display: flex; justify-content: space-between; margin-top: 32px; gap: 16px; flex-wrap: wrap; }
    .commande-express-template .btn { padding: 14px 28px; border-radius: var(--radius); font-weight: 600; cursor: pointer; border: none; font-size: 16px; transition: var(--transition); }
    .commande-express-template .btn:disabled { background-color: var(--gray-light) !important; color: var(--gray) !important; cursor: not-allowed !important; }
    .commande-express-template .btn-primary { background: var(--primary); color: white; }
    .commande-express-template .btn-secondary { background: #E2E8F0; color: var(--dark); }
    .commande-express-template .confirmation { background: white; border-radius: var(--radius); box-shadow: var(--shadow-md); text-align: center; padding: 40px; display: none; }
    .commande-express-template .confirmation h2 { font-size: 24px; color: var(--success); }
    .commande-express-template .confirmation p { font-size: 16px; color: #64748B; margin-top: 8px; margin-bottom: 24px; }
    .commande-express-template .warning-message { background-color: var(--primary-light); color: var(--error); border-left: 4px solid var(--error); padding: 16px; margin-bottom: 20px; font-weight: 500; display: none; }
    .commande-express-template .smoothie-info { background-color: #FFFBEB; border: 1px solid #FCD34D; border-radius: var(--radius); padding: 16px; margin-top: 16px; font-size: 14px; }
    .commande-express-template .smoothie-info h4 { color: #D97706; margin-bottom: 8px; }
    .commande-express-template .smoothie-info ul { list-style-position: inside; padding-left: 0; }
    .commande-express-template .shipping-info { background-color: #EFF6FF; border: 1px solid #93C5FD; border-radius: var(--radius); padding: 16px; margin: 24px 0; font-size: 14px; }
    .commande-express-template .shipping-info h4 { color: #1D4ED8; margin: 0; font-size: 16px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; font-weight: bold; }
    .commande-express-template .shipping-info h4::after { content: "+"; font-size: 20px; font-weight: bold; }
    .commande-express-template .shipping-info-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; padding-top: 0; }
    .commande-express-template .shipping-info-content p,
    .commande-express-template .shipping-info-content ul { margin-top: 12px; }
    .commande-express-template .shipping-info-content ul { padding-left: 20px; }
    .commande-express-template .shipping-info.expanded h4::after { content: "−"; }
    .commande-express-template .shipping-info.expanded .shipping-info-content { max-height: 1000px; }
    .commande-express-template .loading { display: none; text-align: center; margin: 20px 0; }
    .commande-express-template .spinner { border: 4px solid rgba(0,0,0,0.1); border-radius: 50%; border-top: 4px solid var(--primary); width: 40px; height: 40px; animation: spin 1s linear infinite; margin: auto; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @media (max-width: 768px) {
        .commande-express-template .button-group { flex-direction: column; }
        .commande-express-template .button-group .btn { width: 100%; }
        .commande-express-template .steps-container,
        .commande-express-template .progress-bar { width: 100%; }
    }
</style>

<script>
    const productsData = {
        classic: { title: "Bouteilles Classiques PET", description: "Découvrez nos bouteilles pratiques et élégantes...", items: [ { id: 'c1', name: 'Bouteille 1L', shortName: 'Bouteille 1L', details: 'Carton x78', price: 22.62 }, { id: 'c2', name: 'Bouteille 0,5L', shortName: 'Bouteille 0,5L', details: 'Carton x192', price: 51.84 }, { id: 'c3', name: 'Bouteille 0,33L', shortName: 'Bouteille 0,33L', details: 'Carton x231', price: 60.06 }, { id: 'c4', name: 'Bouteille 0,25L', shortName: 'Bouteille 0,25L', details: 'Carton x324', price: 81.00 } ] },
        bio: { title: "Bouteilles Bio", description: "Optez pour une solution écologique et responsable...", items: [ { id: 'b1', name: 'Bouteille 1L Bio', shortName: 'Bouteille 1L Bio', details: 'Carton x100', price: 45.00 }, { id: 'b2', name: 'Bouteille 0,5L Bio', shortName: 'Bouteille 0,5L Bio', details: 'Carton x200', price: 80.00 }, { id: 'b3', name: 'Bouteille 0,25L Bio', shortName: 'Bouteille 0,25L Bio', details: 'Carton x400', price: 156.00 } ] },
        smoothie: { title: "Smoothies SEMPA", description: "Succombez à la tentation de nos smoothies onctueux...", items: [ { id: 's1', name: 'LE FRISSON (1 carton)', shortName: 'LE FRISSON', details: '9x1L', flavor: 'Pomme, Kiwi', price: 46.50 }, { id: 's2', name: 'LE TENDRE (1 carton)', shortName: 'LE TENDRE', details: '9x1L', flavor: 'Pomme, Banane', price: 46.50 }, { id: 's3', name: "L'AIMABLE (1 carton)", shortName: "L'AIMABLE", details: '2 BIB de 3L', flavor: 'Pomme, Carotte, Citron', price: 31.00 }, { id: 's4', name: "L'EXOTIK (1 carton)", shortName: "L'EXOTIK", details: '2 BIB de 3L', flavor: 'Pomme, Mangue, Gingembre', price: 31.00 }, { id: 's5', name: 'LE TONIK (1 carton)', shortName: 'LE TONIK', details: '2 BIB de 3L', flavor: 'Pomme, Menthe, Citron', price: 31.00 } ] },
        cups: { title: "Gobelets", description: "Découvrez nos gobelets design et parfaitement étanches...", items: [ { id: 'cup1', name: 'Gobelets (x1000)', shortName: 'Gobelets (x1000)', details: 'Avec couvercles et pailles', price: 180.00 } ] },
        cleaning: { title: "Nettoyant pour machines", description: "Entretenez facilement et efficacement vos machines...", items: [ { id: 'clean1', name: 'Nettoyant Machines (5L)', shortName: 'Nettoyant Machines (5L)', details: 'Kit D2A', price: 109.00 } ] }
    };

    const shippingRates = {
        1: 21.95,
        2: 26.95,
        3: 36.95,
        4: 43.95,
        5: 49.95,
        6: 49.95,
        7: 53.95,
        8: 53.95,
        9: 56.95,
        10: 56.95,
        11: 64.95,
        12: 64.95
    };
    const smoothieShippingRate = 49.90;
    const apiEndpoint = '<?php echo esc_url_raw( rest_url( 'sempa/v1/commandes' ) ); ?>';
    const apiNonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
    let productQuantities = {};

    const formatPrice = (price) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price);

    function toggleShippingInfo() {
        document.getElementById('shippingInfo').classList.toggle('expanded');
    }

    function changeStep(targetStep) {
        if (targetStep > 1 && ['clientName', 'clientEmail', 'phone', 'postalCode', 'city', 'orderDate'].some(id => !document.getElementById(id).value)) {
            alert('Veuillez remplir tous les champs obligatoires de l\'étape 1.');
            return;
        }
        if (targetStep > 2 && Object.values(productQuantities).every(quantity => parseInt(quantity, 10) <= 0)) {
            alert('Veuillez sélectionner au moins un produit.');
            return;
        }

        document.querySelectorAll('.form-section').forEach(el => el.classList.remove('active'));
        document.getElementById(`section-${targetStep}`).classList.add('active');

        document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
        for (let i = 1; i <= targetStep; i++) document.getElementById(`step-indicator-${i}`).classList.add('active');

        document.getElementById('progressFill').style.width = `${((targetStep - 1) / 2) * 100}%`;
        window.scrollTo(0, 0);

        if (targetStep === 3) updateFinalSummary();
    }

    function renderProductTabs() {
        const container = document.getElementById('productTabs');
        container.innerHTML = Object.keys(productsData).map((key, index) =>
            `<button type="button" class="product-tab ${index === 0 ? 'active' : ''}" data-category="${key}">${productsData[key].title}</button>`
        ).join('');

        container.querySelectorAll('.product-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                container.querySelector('.active').classList.remove('active');
                tab.classList.add('active');
                renderProductGrid(tab.dataset.category);
            });
        });
    }

    function renderProductGrid(categoryKey) {
        const container = document.getElementById('productGridContainer');
        const categoryData = productsData[categoryKey];
        let html = `<div class="category-header"><h3>${categoryData.title}</h3><p>${categoryData.description}</p></div>`;
        if (categoryKey === 'smoothie') {
            html += `<div class="smoothie-info"><h4>⚠️ Information importante</h4><ul><li>Maximum 3 cartons de smoothies au total.</li><li>Commande avant mardi midi pour une livraison le mercredi.</li></ul></div>`;
        }
        html += `<div class="products-grid">`;
        html += categoryData.items.map(p => `
            <div class="product-card">
                <h4>${p.name}</h4>
                <div class="details">${p.details || ''}</div>
                ${p.flavor ? `<div class="flavor">Saveur: ${p.flavor}</div>` : ''}
                <div class="price">${formatPrice(p.price)} HT</div>
                <input type="number" min="0" value="${productQuantities[p.id] || 0}" data-product-id="${p.id}" oninput="updateQuantity('${p.id}', this.value)">
                ${categoryKey === 'smoothie' ? `<div class="max-notice">Maximum 3 cartons au total</div>` : ''}
            </div>
        `).join('');
        html += `</div>`;
        container.innerHTML = html;
    }

    function updateQuantity(productId, value) {
        const quantity = Math.max(0, parseInt(value, 10) || 0);
        if (quantity > 0) {
            productQuantities[productId] = quantity;
        } else {
            delete productQuantities[productId];
        }
        updateOrderDetails();
        validateLimits();
    }

    function getSelectedProducts() {
        const selected = [];
        Object.keys(productsData).forEach(catKey => {
            productsData[catKey].items.forEach(item => {
                const quantity = productQuantities[item.id] || 0;
                if (quantity > 0) {
                    selected.push({
                        id: item.id,
                        name: item.name,
                        shortName: item.shortName,
                        details: item.details || '',
                        flavor: item.flavor || '',
                        price: item.price,
                        quantity: quantity,
                        total: parseFloat((item.price * quantity).toFixed(2)),
                        category: catKey
                    });
                }
            });
        });
        return selected;
    }

    function validateLimits() {
        const selected = getSelectedProducts();
        const smoothieCartons = selected.filter(p => p.category === 'smoothie').reduce((sum, p) => sum + p.quantity, 0);
        document.getElementById('smoothieWarning').style.display = smoothieCartons > 3 ? 'block' : 'none';
        document.getElementById('nextToStep3').disabled = smoothieCartons > 3;
    }

    function calculateTotals() {
        const selected = getSelectedProducts();
        const subtotal = selected.reduce((sum, p) => sum + p.total, 0);
        const normalCartons = selected.filter(p => p.category !== 'smoothie').reduce((sum, p) => sum + p.quantity, 0);
        const hasSmoothies = selected.some(p => p.category === 'smoothie');

        let normalShipping = 0;
        if (normalCartons > 0) {
            let rateKey = 1;
            for (const key of Object.keys(shippingRates).sort((a, b) => b - a)) {
                if (normalCartons >= parseInt(key, 10)) {
                    rateKey = parseInt(key, 10);
                    break;
                }
            }
            normalShipping = shippingRates[rateKey] || 0;
        }
        const smoothieShipping = hasSmoothies ? smoothieShippingRate : 0;
        const totalShipping = normalShipping + smoothieShipping;

        const vat = parseFloat(((subtotal + totalShipping) * 0.20).toFixed(2));
        const totalTTC = parseFloat((subtotal + totalShipping + vat).toFixed(2));

        return { subtotal, totalShipping, vat, totalTTC, normalShipping, smoothieShipping, hasSmoothies };
    }

    function updateOrderDetails() {
        const container = document.getElementById('orderDetails');
        const selected = getSelectedProducts();
        if (selected.length === 0) {
            container.innerHTML = '';
            return;
        }
        const { subtotal } = calculateTotals();
        container.innerHTML = `<h3>Votre commande</h3>${selected.map(p => `<div class="order-item"><span>${p.quantity}x ${p.shortName}</span><span>${formatPrice(p.total)}</span></div>`).join('')}<div class="order-subtotal"><span>Sous-total HT</span><span>${formatPrice(subtotal)}</span></div><p class="shipping-notice">Les frais de transport seront calculés à l'étape suivante.</p>`;
    }

    function updateFinalSummary() {
        const container = document.getElementById('finalSummary');
        const { subtotal, vat, totalTTC, normalShipping, smoothieShipping, hasSmoothies } = calculateTotals();
        let summaryHTML = `<div class="order-item"><span>Total Produits HT</span><span>${formatPrice(subtotal)}</span></div>`;
        if (normalShipping > 0) {
            summaryHTML += `<div class="order-item"><span>Frais de port standards</span><span>${formatPrice(normalShipping)}</span></div>`;
        }
        if (hasSmoothies) {
            summaryHTML += `<div class="order-item"><span>Frais de port smoothies (transport réfrigéré)</span><span>${formatPrice(smoothieShipping)}</span></div>`;
        }
        summaryHTML += `<div class="order-item"><span>TVA (20%)</span><span>${formatPrice(vat)}</span></div>`;
        summaryHTML += `<div class="order-subtotal"><span>TOTAL TTC</span><span>${formatPrice(totalTTC)}</span></div>`;
        container.innerHTML = summaryHTML;
    }

    async function submitForm() {
        const confirmBtn = document.getElementById('confirmOrderBtn');
        const spinner = document.getElementById('loadingSpinner');

        spinner.style.display = 'block';
        confirmBtn.disabled = true;

        try {
            const selectedProducts = getSelectedProducts();
            const totals = calculateTotals();
            const formattedTotals = {
                totalHT: formatPrice(totals.subtotal),
                shipping: formatPrice(totals.totalShipping),
                vat: formatPrice(totals.vat),
                totalTTC: formatPrice(totals.totalTTC)
            };
            const formData = {
                client: {
                    name: document.getElementById('clientName').value,
                    email: document.getElementById('clientEmail').value,
                    phone: document.getElementById('phone').value,
                    clientNumber: document.getElementById('clientNumber').value,
                    postalCode: document.getElementById('postalCode').value,
                    city: document.getElementById('city').value,
                    orderDate: document.getElementById('orderDate').value,
                    comments: document.getElementById('comments').value,
                    sendConfirmationEmail: document.getElementById('sendConfirmationEmail').checked
                },
                products: selectedProducts,
                totals: {
                    raw: totals,
                    formatted: formattedTotals
                }
            };

            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': apiNonce
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                const errorBody = await response.json().catch(() => ({}));
                const message = errorBody.message || `Erreur du serveur (HTTP ${response.status})`;
                throw new Error(message);
            }

            const responseData = await response.json();
            if (!responseData.success) {
                throw new Error(responseData.message || 'Erreur lors de l\'enregistrement.');
            }

            document.querySelectorAll('.form-section').forEach(el => el.classList.remove('active'));
            document.getElementById('confirmationScreen').style.display = 'block';
        } catch (error) {
            console.error('ERREUR DE SOUMISSION :', error);
            alert(`Une erreur est survenue. Veuillez contacter le support.\n\nDétail : ${error.message}`);
        } finally {
            spinner.style.display = 'none';
            confirmBtn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const orderDateInput = document.getElementById('orderDate');
        const today = new Date().toISOString().split('T')[0];
        orderDateInput.min = today;
        orderDateInput.value = today;

        renderProductTabs();
        renderProductGrid(Object.keys(productsData)[0]);
    });
</script>
<?php
get_footer();
