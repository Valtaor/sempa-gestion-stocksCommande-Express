<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-User');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

const DATA_FILE = __DIR__ . '/data/bijoux.json';
const AUTH_TOKEN = 'inventaire-bijoux-token';

function getAuthorizationToken(): string
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if ($auth !== '') {
        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return trim($auth);
    }
    return $headers['X-Auth-Token'] ?? $headers['x-auth-token'] ?? '';
}

function ensureAuthorized(): void
{
    $token = getAuthorizationToken();
    if ($token !== AUTH_TOKEN) {
        http_response_code(401);
        echo json_encode(['error' => 'Accès non autorisé.']);
        exit;
    }
}

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
    if ($contents === false || trim((string) $contents) === '') {
        return [
            'bijoux' => [],
            'logs' => [],
            'titres' => [],
        ];
    }
    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        return [
            'bijoux' => [],
            'logs' => [],
            'titres' => [],
        ];
    }
    $decoded['bijoux'] = array_values(array_filter($decoded['bijoux'] ?? [], 'is_array'));
    $decoded['logs'] = array_values(array_filter($decoded['logs'] ?? [], 'is_array'));
    $decoded['titres'] = array_values(array_filter($decoded['titres'] ?? [], 'is_array'));
    return $decoded;
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
    return trim((string) ($value ?? ''));
}

function sanitizeInt(mixed $value, bool $allowZero = true): int
{
    if ($value === null || $value === '') {
        if ($allowZero) {
            return 0;
        }
        throw new InvalidArgumentException('Valeur entière requise.');
    }
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false) {
        throw new InvalidArgumentException('Valeur entière invalide.');
    }
    if (!$allowZero && $filtered <= 0) {
        throw new InvalidArgumentException('La valeur doit être supérieure à zéro.');
    }
    if ($filtered < 0) {
        throw new InvalidArgumentException('La valeur ne peut pas être négative.');
    }
    return $filtered;
}

function sanitizeFloat(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($filtered === false || $filtered < 0) {
        throw new InvalidArgumentException('Valeur numérique invalide.');
    }
    return round((float) $filtered, 2);
}

function currentIsoDate(): string
{
    return date('c');
}

function getUser(): string
{
    $headers = getallheaders();
    $user = $headers['X-User'] ?? $headers['x-user'] ?? 'système';
    $user = trim((string) $user);
    return $user !== '' ? $user : 'système';
}

function normalizePhotos(mixed $value): array
{
    if (is_array($value)) {
        return array_values(array_filter(array_map('sanitizeString', $value), static fn ($item) => $item !== ''));
    }
    $single = sanitizeString($value ?? '');
    return $single === '' ? [] : [$single];
}

function validateBijou(array $payload, bool $isCreation = false): array
{
    $id = sanitizeString($payload['id'] ?? '');
    if ($id === '' && $isCreation) {
        $id = uniqid('b_', true);
    }
    if ($id === '') {
        throw new InvalidArgumentException('Identifiant du bijou requis.');
    }

    $type = sanitizeString($payload['type'] ?? '');
    $typeAllowed = ['collier', 'boucles', 'bracelet'];
    if (!in_array($type, $typeAllowed, true)) {
        throw new InvalidArgumentException('Type de bijou invalide.');
    }

    $matiere = sanitizeString($payload['matiere'] ?? '');
    if ($matiere === '') {
        throw new InvalidArgumentException('La matière est obligatoire.');
    }

    $caracteristique = sanitizeString($payload['caracteristique'] ?? '');
    if ($caracteristique === '') {
        throw new InvalidArgumentException('La caractéristique principale est obligatoire.');
    }

    $dimensions = sanitizeString($payload['dimensions'] ?? '');
    if ($dimensions === '') {
        throw new InvalidArgumentException('Les dimensions sont obligatoires.');
    }

    $couleur = sanitizeString($payload['couleur'] ?? '');
    $couleurs = sanitizeString($payload['couleurs'] ?? '');

    $quantite = sanitizeInt($payload['quantite'] ?? 0);
    $seuilBas = sanitizeInt($payload['seuil_bas'] ?? 0);
    $prixAchat = sanitizeFloat($payload['prix_achat'] ?? null);
    $flag = (bool) ($payload['flag_a_renseigner'] ?? false);

    $photos = normalizePhotos($payload['photos'] ?? []);
    $marque = sanitizeString($payload['marque'] ?? '');
    $etat = sanitizeString($payload['etat'] ?? '');
    $defauts = sanitizeString($payload['defauts'] ?? '');

    $titreCourt = sanitizeString($payload['titre_court'] ?? '');
    $titreAvertissement = (bool) ($payload['titre_avertissement'] ?? false);

    return [
        'id' => $id,
        'type' => $type,
        'photos' => $photos,
        'couleur' => $couleur,
        'couleurs' => $couleurs,
        'matiere' => $matiere,
        'caracteristique' => $caracteristique,
        'dimensions' => $dimensions,
        'quantite' => $quantite,
        'seuil_bas' => $seuilBas,
        'prix_achat' => $prixAchat,
        'flag_a_renseigner' => $flag,
        'titre_court' => $titreCourt,
        'titre_avertissement' => $titreAvertissement,
        'marque' => $marque,
        'etat' => $etat,
        'defauts' => $defauts,
        'created_at' => sanitizeString($payload['created_at'] ?? '') ?: currentIsoDate(),
        'updated_at' => currentIsoDate(),
    ];
}

function buildTitleHistory(string $bijouId, string $user, string $field, string $ancienneValeur, string $nouvelleValeur): array
{
    return [
        'id' => uniqid('t_', true),
        'bijou_id' => $bijouId,
        'champ' => $field,
        'ancienne_valeur' => $ancienneValeur,
        'nouvelle_valeur' => $nouvelleValeur,
        'utilisateur' => $user,
        'timestamp' => currentIsoDate(),
    ];
}

function buildLog(string $bijouId, string $action, int $delta, int $nouvelleQuantite, string $user, array $extra = []): array
{
    return array_merge([
        'id' => uniqid('l_', true),
        'bijou_id' => $bijouId,
        'action' => $action,
        'delta' => $delta,
        'nouvelle_quantite' => $nouvelleQuantite,
        'utilisateur' => $user,
        'timestamp' => currentIsoDate(),
    ], $extra);
}

function applyStockOperation(array &$bijou, string $action, int $valeur, string $user, array &$logs): array
{
    if ($valeur <= 0 && $action !== 'set') {
        throw new InvalidArgumentException('La quantité doit être supérieure à zéro.');
    }
    $quantiteActuelle = $bijou['quantite'];
    $nouvelleQuantite = $quantiteActuelle;
    $delta = 0;

    switch ($action) {
        case 'add':
            $nouvelleQuantite += $valeur;
            $delta = $valeur;
            break;
        case 'remove':
        case 'sold':
        case 'reserve':
            if ($quantiteActuelle - $valeur < 0) {
                throw new InvalidArgumentException('Stock insuffisant.');
            }
            $nouvelleQuantite -= $valeur;
            $delta = -$valeur;
            break;
        case 'set':
            if ($valeur < 0) {
                throw new InvalidArgumentException('La quantité ne peut pas être négative.');
            }
            $delta = $valeur - $quantiteActuelle;
            $nouvelleQuantite = $valeur;
            break;
        default:
            throw new InvalidArgumentException('Action de stock inconnue.');
    }

    $bijou['quantite'] = $nouvelleQuantite;
    $bijou['updated_at'] = currentIsoDate();

    $logs[] = buildLog($bijou['id'], $action, $delta, $nouvelleQuantite, $user, ['valeur' => $valeur]);

    return ['bijou' => $bijou];
}

function extractPrimaryDimension(string $dimensions): string
{
    if ($dimensions === '') {
        return '';
    }
    $parts = preg_split('/[,\/]/', $dimensions);
    if ($parts === false || count($parts) === 0) {
        return trim($dimensions);
    }
    $first = trim($parts[0]);
    return $first !== '' ? $first : trim($dimensions);
}

function generateTitles(string $type, string $matiere, string $caracteristique, string $dimensions): array
{
    $typeTitre = ucfirst($type);
    $matiereTitre = trim($matiere);
    $caracTitre = trim($caracteristique);
    $dimensionPrincipale = extractPrimaryDimension($dimensions);

    if ($typeTitre === '' || $matiereTitre === '' || $caracTitre === '' || $dimensionPrincipale === '') {
        return [
            'titre_court' => '',
            'avertissement' => true,
        ];
    }

    $titreCourt = sprintf('%s %s %s %s', $typeTitre, $matiereTitre, $caracTitre, $dimensionPrincipale);

    return [
        'titre_court' => trim(preg_replace('/\s+/', ' ', $titreCourt)),
        'avertissement' => false,
    ];
}

function handleCsvImport(array $fileData, string $user): array
{
    if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
        throw new InvalidArgumentException('Fichier CSV manquant.');
    }
    $handle = fopen($fileData['tmp_name'], 'r');
    if ($handle === false) {
        throw new RuntimeException('Impossible d\'ouvrir le fichier CSV.');
    }
    $header = fgetcsv($handle, 0, ';');
    if ($header === false) {
        fclose($handle);
        throw new InvalidArgumentException('CSV vide.');
    }
    $header = array_map('mb_strtolower', $header);
    $required = ['type', 'matiere', 'caracteristique', 'dimensions', 'quantite'];
    foreach ($required as $column) {
        if (!in_array($column, $header, true)) {
            fclose($handle);
            throw new InvalidArgumentException('Colonne requise manquante: ' . $column);
        }
    }
    $results = ['crees' => 0, 'mis_a_jour' => 0];

    withData(function (array &$data) use ($handle, $header, &$results, $user) {
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            $assoc = [];
            foreach ($header as $index => $col) {
                $assoc[$col] = $row[$index] ?? '';
            }
            $quantite = sanitizeInt($assoc['quantite'] ?? 0);
            $seuilBas = isset($assoc['seuil_bas']) ? sanitizeInt($assoc['seuil_bas'], true) : 0;
            $prix = isset($assoc['prix_achat']) ? sanitizeFloat($assoc['prix_achat']) : null;

            $titre = sanitizeString($assoc['titre'] ?? '');
            $gen = generateTitles(
                sanitizeString($assoc['type'] ?? ''),
                sanitizeString($assoc['matiere'] ?? ''),
                sanitizeString($assoc['caracteristique'] ?? ''),
                sanitizeString($assoc['dimensions'] ?? '')
            );
            if ($titre === '') {
                $titre = $gen['titre_court'];
            }

            $bijou = [
                'id' => $assoc['id'] ?? uniqid('b_', true),
                'type' => sanitizeString($assoc['type'] ?? ''),
                'photos' => [],
                'couleur' => sanitizeString($assoc['couleur'] ?? ''),
                'couleurs' => sanitizeString($assoc['couleurs'] ?? ''),
                'matiere' => sanitizeString($assoc['matiere'] ?? ''),
                'caracteristique' => sanitizeString($assoc['caracteristique'] ?? ''),
                'dimensions' => sanitizeString($assoc['dimensions'] ?? ''),
                'quantite' => $quantite,
                'seuil_bas' => $seuilBas,
                'prix_achat' => $prix,
                'flag_a_renseigner' => (bool) ($assoc['flag_a_renseigner'] ?? false),
                'titre_court' => $titre,
                'titre_avertissement' => $gen['avertissement'],
                'marque' => sanitizeString($assoc['marque'] ?? ''),
                'etat' => sanitizeString($assoc['etat'] ?? ''),
                'defauts' => sanitizeString($assoc['defauts'] ?? ''),
                'created_at' => currentIsoDate(),
                'updated_at' => currentIsoDate(),
            ];

            $existingKey = null;
            foreach ($data['bijoux'] as $index => $existing) {
                if (($existing['id'] ?? '') === $bijou['id']) {
                    $existingKey = $index;
                    break;
                }
            }

            if ($existingKey === null) {
                $data['bijoux'][] = $bijou;
                $data['logs'][] = buildLog($bijou['id'], 'set', $bijou['quantite'], $bijou['quantite'], $user, ['via' => 'import']);
                $results['crees']++;
            } else {
                $delta = $bijou['quantite'] - ($data['bijoux'][$existingKey]['quantite'] ?? 0);
                $data['bijoux'][$existingKey] = $bijou;
                $data['logs'][] = buildLog($bijou['id'], 'set', $delta, $bijou['quantite'], $user, ['via' => 'import']);
                $results['mis_a_jour']++;
            }
        }
        fclose($handle);
        return $results;
    });

    return $results;
}

function handleCsvExport(array $data): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventaire_bijoux.csv"');
    $output = fopen('php://output', 'w');
    $header = [
        'id',
        'type',
        'titre',
        'matiere',
        'caracteristique',
        'dimensions',
        'couleur',
        'couleurs',
        'marque',
        'etat',
        'defauts',
        'quantite',
        'seuil_bas',
        'prix_achat',
        'flag_a_renseigner',
    ];
    fputcsv($output, $header, ';');
    foreach ($data['bijoux'] as $bijou) {
        fputcsv($output, [
            $bijou['id'] ?? '',
            $bijou['type'] ?? '',
            $bijou['titre_court'] ?? '',
            $bijou['matiere'] ?? '',
            $bijou['caracteristique'] ?? '',
            $bijou['dimensions'] ?? '',
            $bijou['couleur'] ?? '',
            $bijou['couleurs'] ?? '',
            $bijou['marque'] ?? '',
            $bijou['etat'] ?? '',
            $bijou['defauts'] ?? '',
            $bijou['quantite'] ?? 0,
            $bijou['seuil_bas'] ?? 0,
            $bijou['prix_achat'] ?? '',
            $bijou['flag_a_renseigner'] ? '1' : '0',
        ], ';');
    }
    fclose($output);
    exit;
}

try {
    ensureAuthorized();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $resource = sanitizeString($_GET['resource'] ?? '');
    $id = isset($_GET['id']) ? sanitizeString($_GET['id']) : null;
    $action = sanitizeString($_GET['action'] ?? '');
    $user = getUser();

    if ($method === 'GET' && $resource === 'export') {
        $data = loadData();
        handleCsvExport($data);
    }

    switch ($method) {
        case 'GET':
            $data = loadData();
            if ($resource === 'bijoux') {
                if ($id) {
                    foreach ($data['bijoux'] as $bijou) {
                        if (($bijou['id'] ?? '') === $id) {
                            respond(['bijou' => $bijou]);
                        }
                    }
                    errorResponse('Bijou introuvable.', 404);
                }
                respond(['bijoux' => $data['bijoux']]);
            }
            if ($resource === 'historique') {
                $bijouId = sanitizeString($_GET['bijou_id'] ?? '');
                $page = max(1, (int) ($_GET['page'] ?? 1));
                $perPage = max(1, (int) ($_GET['per_page'] ?? 10));
                $logs = array_values(array_filter($data['logs'], static fn ($log) => $bijouId === '' || ($log['bijou_id'] ?? '') === $bijouId));
                $total = count($logs);
                $offset = ($page - 1) * $perPage;
                $items = array_slice($logs, $offset, $perPage);
                respond([
                    'logs' => $items,
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                    ],
                ]);
            }
            if ($resource === 'titres') {
                $bijouId = sanitizeString($_GET['bijou_id'] ?? '');
                $history = array_values(array_filter($data['titres'], static fn ($entry) => $bijouId === '' || ($entry['bijou_id'] ?? '') === $bijouId));
                respond(['titres' => $history]);
            }
            respond($data);

        case 'POST':
            if ($resource === 'bijoux') {
                $payload = decodeJsonBody();
                $bijou = validateBijou($payload, true);
                $titles = generateTitles($bijou['type'], $bijou['matiere'], $bijou['caracteristique'], $bijou['dimensions']);
                if ($bijou['titre_court'] === '') {
                    $bijou['titre_court'] = $titles['titre_court'];
                }
                $bijou['titre_avertissement'] = $titles['avertissement'];

                $result = withData(function (array &$data) use ($bijou, $user) {
                    foreach ($data['bijoux'] as $existing) {
                        if (($existing['id'] ?? '') === $bijou['id']) {
                            throw new RuntimeException('Un bijou avec cet identifiant existe déjà.', 409);
                        }
                    }
                    $data['bijoux'][] = $bijou;
                    $data['logs'][] = buildLog($bijou['id'], 'set', $bijou['quantite'], $bijou['quantite'], $user, ['via' => 'creation']);
                    return ['bijou' => $bijou];
                });
                respond($result, 201);
            }

            if ($resource === 'stock' && $id) {
                $payload = decodeJsonBody();
                $valeur = sanitizeInt($payload['valeur'] ?? null, $action === 'set');
                $result = withData(function (array &$data) use ($id, $action, $valeur, $user) {
                    foreach ($data['bijoux'] as &$bijou) {
                        if (($bijou['id'] ?? '') === $id) {
                            return applyStockOperation($bijou, $action, $valeur, $user, $data['logs']);
                        }
                    }
                    throw new RuntimeException('Bijou introuvable.', 404);
                });
                respond($result);
            }

            if ($resource === 'import' && isset($_FILES['fichier'])) {
                $result = handleCsvImport($_FILES['fichier'], $user);
                respond(['import' => $result]);
            }

            errorResponse('Ressource inconnue.', 400);

        case 'PUT':
            if ($resource === 'bijoux' && $id) {
                $payload = decodeJsonBody();
                $payload['id'] = $id;
                $bijou = validateBijou($payload);
                $result = withData(function (array &$data) use ($bijou, $user) {
                    foreach ($data['bijoux'] as &$existing) {
                        if (($existing['id'] ?? '') === $bijou['id']) {
                            $changes = [];
                            if ($existing['titre_court'] !== $bijou['titre_court']) {
                                $changes[] = buildTitleHistory($bijou['id'], $user, 'titre_court', $existing['titre_court'] ?? '', $bijou['titre_court']);
                            }
                            $existing = array_merge($existing, $bijou);
                            foreach ($changes as $entry) {
                                $data['titres'][] = $entry;
                            }
                            return ['bijou' => $existing];
                        }
                    }
                    throw new RuntimeException('Bijou introuvable.', 404);
                });
                respond($result);
            }
            errorResponse('Ressource inconnue pour mise à jour.', 400);

        case 'DELETE':
            if ($resource === 'bijoux' && $id) {
                $result = withData(function (array &$data) use ($id) {
                    foreach ($data['bijoux'] as $index => $bijou) {
                        if (($bijou['id'] ?? '') === $id) {
                            array_splice($data['bijoux'], $index, 1);
                            return ['supprime' => true];
                        }
                    }
                    throw new RuntimeException('Bijou introuvable.', 404);
                });
                respond($result);
            }
            errorResponse('Suppression non autorisée.', 405);

        default:
            errorResponse('Méthode non prise en charge.', 405);
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
