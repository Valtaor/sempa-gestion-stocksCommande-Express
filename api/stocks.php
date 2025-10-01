<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const DATA_FILE = __DIR__ . '/data/stocks.json';

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function errorResponse(string $message, int $status = 400): void
{
    respond(['error' => $message], $status);
}

function ensureDataDirectory(): void
{
    $directory = dirname(DATA_FILE);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le répertoire de données.');
        }
    }
}

function decodeData(?string $contents): array
{
    if ($contents === false || trim((string)$contents) === '') {
        return ['products' => [], 'movements' => []];
    }
    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return ['products' => [], 'movements' => []];
    }
    $products = [];
    foreach (($decoded['products'] ?? []) as $product) {
        if (is_array($product)) {
            $products[] = $product;
        }
    }
    $movements = [];
    foreach (($decoded['movements'] ?? []) as $movement) {
        if (is_array($movement)) {
            $movements[] = $movement;
        }
    }
    return ['products' => $products, 'movements' => $movements];
}

function loadData(): array
{
    ensureDataDirectory();
    $handle = fopen(DATA_FILE, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Impossible d\'ouvrir le fichier de données.');
    }
    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        throw new RuntimeException('Impossible de verrouiller le fichier de données.');
    }
    rewind($handle);
    $contents = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return decodeData($contents);
}

function withData(callable $callback)
{
    ensureDataDirectory();
    $handle = fopen(DATA_FILE, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Impossible d\'ouvrir le fichier de données.');
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new RuntimeException('Impossible de verrouiller le fichier de données.');
    }
    rewind($handle);
    $contents = stream_get_contents($handle);
    $data = decodeData($contents);
    $response = $callback($data);
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($encoded === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw new RuntimeException('Impossible d\'encoder les données.');
    }
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, $encoded . PHP_EOL);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $response;
}

function decodeJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('Corps de requête JSON invalide.');
    }
    return $decoded;
}

function sanitizeString(mixed $value): string
{
    return trim((string)($value ?? ''));
}

function sanitizeDate(?string $value): string
{
    if ($value) {
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('c', $timestamp);
        }
    }
    return date('c');
}

function validateProduct(array $payload, bool $isCreation = false): array
{
    $id = sanitizeString($payload['id'] ?? '');
    if ($id === '') {
        if ($isCreation) {
            $id = uniqid('p_', true);
        } else {
            throw new InvalidArgumentException('Identifiant du produit manquant.');
        }
    }
    $name = sanitizeString($payload['name'] ?? '');
    if ($name === '') {
        throw new InvalidArgumentException('Le nom du produit est obligatoire.');
    }
    $stock = filter_var($payload['stock'] ?? null, FILTER_VALIDATE_INT);
    if ($stock === false || $stock < 0) {
        throw new InvalidArgumentException('Le stock doit être un entier positif.');
    }
    $minStock = filter_var($payload['minStock'] ?? null, FILTER_VALIDATE_INT);
    if ($minStock === false || $minStock <= 0) {
        throw new InvalidArgumentException('Le seuil doit être un entier positif.');
    }
    $price = filter_var($payload['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($price === false || $price < 0) {
        throw new InvalidArgumentException('Le prix doit être un nombre positif.');
    }
    $category = sanitizeString($payload['category'] ?? 'autre') ?: 'autre';
    $description = sanitizeString($payload['description'] ?? '');
    $lastUpdated = sanitizeDate($payload['lastUpdated'] ?? null);

    return [
        'id' => $id,
        'name' => $name,
        'stock' => (int)$stock,
        'minStock' => (int)$minStock,
        'price' => round((float)$price, 2),
        'category' => $category,
        'description' => $description,
        'lastUpdated' => $lastUpdated,
    ];
}

function validateMovement(array $payload): array
{
    $id = sanitizeString($payload['id'] ?? '') ?: uniqid('m_', true);
    $productId = sanitizeString($payload['productId'] ?? '');
    if ($productId === '') {
        throw new InvalidArgumentException('Le mouvement doit être lié à un produit.');
    }
    $type = sanitizeString($payload['type'] ?? '');
    $allowedTypes = ['in', 'out', 'adjust'];
    if (!in_array($type, $allowedTypes, true)) {
        throw new InvalidArgumentException('Type de mouvement invalide.');
    }
    $quantity = filter_var($payload['quantity'] ?? null, FILTER_VALIDATE_INT);
    $isAdjust = $type === 'adjust';
    if ($quantity === false || ($isAdjust ? $quantity < 0 : $quantity <= 0)) {
        throw new InvalidArgumentException('Quantité invalide pour le mouvement.');
    }
    $reason = sanitizeString($payload['reason'] ?? '');
    $productName = sanitizeString($payload['productName'] ?? '');
    $date = sanitizeDate($payload['date'] ?? null);

    return [
        'id' => $id,
        'productId' => $productId,
        'productName' => $productName,
        'type' => $type,
        'quantity' => (int)$quantity,
        'reason' => $reason,
        'date' => $date,
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $resource = sanitizeString($_GET['resource'] ?? '');
    $id = isset($_GET['id']) ? sanitizeString($_GET['id']) : null;

    switch ($method) {
        case 'GET':
            $data = loadData();
            if ($resource === 'products') {
                respond(['products' => $data['products']]);
            }
            if ($resource === 'movements') {
                respond(['movements' => $data['movements']]);
            }
            respond($data);

        case 'POST':
            if ($resource === 'products') {
                $payload = decodeJsonBody();
                $product = validateProduct($payload, true);
                $result = withData(function (array &$data) use ($product) {
                    foreach ($data['products'] as $existing) {
                        if (($existing['id'] ?? '') === $product['id']) {
                            throw new RuntimeException('Un produit avec cet identifiant existe déjà.', 409);
                        }
                    }
                    $data['products'][] = $product;
                    return ['product' => $product];
                });
                respond($result, 201);
            }
            if ($resource === 'movements') {
                $payload = decodeJsonBody();
                $movement = validateMovement($payload);
                $result = withData(function (array &$data) use ($movement) {
                    $productName = $movement['productName'];
                    $productFound = false;
                    foreach ($data['products'] as $product) {
                        if (($product['id'] ?? '') === $movement['productId']) {
                            $productFound = true;
                            if ($productName === '') {
                                $productName = $product['name'] ?? '';
                            }
                            break;
                        }
                    }
                    if (!$productFound) {
                        throw new RuntimeException('Produit introuvable pour ce mouvement.', 404);
                    }
                    $movement['productName'] = $productName;
                    $data['movements'][] = $movement;
                    return ['movement' => $movement];
                });
                respond($result, 201);
            }
            errorResponse('Ressource inconnue pour création.', 400);

        case 'PUT':
            if ($resource === 'products') {
                if ($id === null || $id === '') {
                    errorResponse('Identifiant du produit requis.', 400);
                }
                $payload = decodeJsonBody();
                $product = validateProduct(array_merge($payload, ['id' => $id]));
                $result = withData(function (array &$data) use ($product, $id) {
                    foreach ($data['products'] as $index => $existing) {
                        if (($existing['id'] ?? '') === $id) {
                            $data['products'][$index] = $product;
                            foreach ($data['movements'] as &$movement) {
                                if (($movement['productId'] ?? '') === $id) {
                                    $movement['productName'] = $product['name'];
                                }
                            }
                            return ['product' => $product];
                        }
                    }
                    throw new RuntimeException('Produit introuvable.', 404);
                });
                respond($result);
            }
            errorResponse('Méthode PUT non prise en charge pour cette ressource.', 405);

        case 'DELETE':
            if ($resource === 'products') {
                if ($id === null || $id === '') {
                    errorResponse('Identifiant du produit requis.', 400);
                }
                $result = withData(function (array &$data) use ($id) {
                    $index = null;
                    $productName = '';
                    foreach ($data['products'] as $key => $product) {
                        if (($product['id'] ?? '') === $id) {
                            $index = $key;
                            $productName = $product['name'] ?? '';
                            break;
                        }
                    }
                    if ($index === null) {
                        throw new RuntimeException('Produit introuvable.', 404);
                    }
                    array_splice($data['products'], $index, 1);
                    foreach ($data['movements'] as &$movement) {
                        if (($movement['productId'] ?? '') === $id && ($movement['productName'] ?? '') === '') {
                            $movement['productName'] = $productName;
                        }
                    }
                    return ['success' => true];
                });
                respond($result);
            }
            errorResponse('Méthode DELETE non prise en charge pour cette ressource.', 405);

        default:
            errorResponse('Méthode HTTP non autorisée.', 405);
    }
} catch (InvalidArgumentException $exception) {
    errorResponse($exception->getMessage(), 400);
} catch (RuntimeException $exception) {
    $code = $exception->getCode();
    if ($code < 400 || $code > 599) {
        $code = 400;
    }
    error_log($exception->getMessage());
    errorResponse($exception->getMessage(), $code);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    errorResponse('Erreur interne du serveur.', 500);
}
