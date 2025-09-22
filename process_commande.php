<?php
/**
 * Traitement du formulaire de commande express.
 * - Validation serveur des champs requis
 * - Connexion PDO sécurisée à MySQL
 * - Insertion dans la table `commandes`
 *
 * Adapter les identifiants de connexion selon l'environnement d'hébergement.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode HTTP non autorisée. Utilisez POST pour soumettre une commande.'
    ]);
    exit;
}

$clientName = trim($_POST['client_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$productId = trim($_POST['product'] ?? '');
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
$notes = trim($_POST['notes'] ?? '');

$allowedProducts = [
    'cartouche-orange' => 'Cartouche orange 10L',
    'cartouche-fraise' => 'Cartouche fraise 10L',
    'machine-sempa-500' => 'Machine SEMPA 500',
    'kit-nettoyage' => 'Kit nettoyage premium',
];

$errors = [];

if ($clientName === '') {
    $errors['client_name'] = 'Le nom du client est requis.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Adresse email invalide.';
}

if ($phone === '' || strlen($phone) < 6) {
    $errors['phone'] = 'Le numéro de téléphone doit contenir au moins 6 caractères.';
}

if (!array_key_exists($productId, $allowedProducts)) {
    $errors['product'] = 'Le produit sélectionné est invalide.';
}

if ($quantity < 1 || $quantity > 999) {
    $errors['quantity'] = 'La quantité doit être comprise entre 1 et 999.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Certaines informations sont invalides.',
        'errors' => $errors,
    ]);
    exit;
}

// Nettoyage supplémentaire avant insertion.
$clientName = htmlspecialchars($clientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$notes = htmlspecialchars($notes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
// Adapter la requête INSERT si votre table stocke également le téléphone ou un commentaire.

$dsn = 'mysql:host=db5016439102.hosting-data.io;dbname=dbs1363734;charset=utf8mb4';
$dbUser = 'dbu1662343';
$dbPassword = '14Juillet@';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'La connexion à la base de données a échoué. Veuillez réessayer ultérieurement.',
    ]);
    exit;
}

try {
    $statement = $pdo->prepare(
        'INSERT INTO commandes (date, client_nom, client_email, produit_id, quantite, statut) VALUES (NOW(), :client_nom, :client_email, :produit_id, :quantite, :statut)'
    );

    $statement->execute([
        ':client_nom' => $clientName,
        ':client_email' => $email,
        ':produit_id' => $productId,
        ':quantite' => $quantity,
        ':statut' => 'En attente',
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Commande enregistrée avec succès.',
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Impossible d\'enregistrer la commande pour le moment.',
    ]);
}
