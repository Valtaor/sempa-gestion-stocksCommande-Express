<?php
/**
 * SCRIPT DE TEST DE CONNEXION DB
 *
 * À copier dans : wp-content/themes/uncode-child/test-connexion-db.php
 * Puis visiter : https://votre-site.com/wp-content/themes/uncode-child/test-connexion-db.php
 */

// Configuration DB (identifiants actuels)
$DB_HOST = 'db5001643902.hosting-data.io';
$DB_NAME = 'dbs1363734';
$DB_USER = 'dbu1662343';
$DB_PASSWORD = '14Juillet@';
$DB_PORT = 3306;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Connexion DB Stocks</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #0f9d58; background: #e8f5e9; padding: 15px; border-radius: 5px; }
        .error { color: #d93025; background: #fce8e6; padding: 15px; border-radius: 5px; }
        .info { color: #1a73e8; background: #e8f0fe; padding: 15px; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        h2 { border-bottom: 2px solid #ccc; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Test de Connexion Base de Données Stocks</h1>

    <h2>1. Configuration</h2>
    <div class="info">
        <strong>Serveur :</strong> <?php echo htmlspecialchars($DB_HOST); ?><br>
        <strong>Base :</strong> <?php echo htmlspecialchars($DB_NAME); ?><br>
        <strong>Utilisateur :</strong> <?php echo htmlspecialchars($DB_USER); ?><br>
        <strong>Mot de passe :</strong> <?php echo str_repeat('*', strlen($DB_PASSWORD)); ?><br>
        <strong>Port :</strong> <?php echo $DB_PORT; ?>
    </div>

    <h2>2. Test de Connexion</h2>
    <?php
    $host_with_port = $DB_HOST . ':' . $DB_PORT;

    // Tentative de connexion
    $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);

    if ($mysqli->connect_error) {
        echo '<div class="error">';
        echo '<strong>❌ CONNEXION ÉCHOUÉE</strong><br>';
        echo 'Erreur n°' . $mysqli->connect_errno . ' : ' . htmlspecialchars($mysqli->connect_error);
        echo '</div>';

        echo '<h2>3. Diagnostic</h2>';
        echo '<div class="info">';
        echo '<strong>Causes possibles :</strong><ul>';
        echo '<li>Le serveur DB n\'est pas accessible</li>';
        echo '<li>Les identifiants sont incorrects</li>';
        echo '<li>Votre IP est bloquée par le pare-feu</li>';
        echo '<li>Le port 3306 est fermé</li>';
        echo '</ul></div>';

        exit;
    }

    echo '<div class="success">';
    echo '<strong>✅ CONNEXION RÉUSSIE</strong><br>';
    echo 'Version MySQL : ' . $mysqli->server_info;
    echo '</div>';
    ?>

    <h2>3. Liste des Tables</h2>
    <?php
    $result = $mysqli->query("SHOW TABLES");

    if (!$result) {
        echo '<div class="error">Erreur : ' . htmlspecialchars($mysqli->error) . '</div>';
    } else {
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }

        if (empty($tables)) {
            echo '<div class="error">❌ Aucune table trouvée dans la base</div>';
        } else {
            echo '<div class="success">';
            echo '<strong>✅ ' . count($tables) . ' tables trouvées :</strong>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li><strong>' . htmlspecialchars($table) . '</strong>';

                // Compter les lignes
                $count_result = $mysqli->query("SELECT COUNT(*) as cnt FROM `$table`");
                if ($count_result) {
                    $count_row = $count_result->fetch_assoc();
                    echo ' (' . $count_row['cnt'] . ' lignes)';
                }

                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
    ?>

    <h2>4. Recherche des Tables de Stocks</h2>
    <?php
    $stock_tables = [
        'stocks',
        'products',
        'stocks_sempa',
        'stocks_stockpilot',
        'stockpilot_stocks',
        'stock',
        'produits'
    ];

    echo '<div class="info">';
    echo '<strong>Recherche des tables potentielles...</strong><br><br>';

    $found_any = false;
    foreach ($stock_tables as $potential_table) {
        $test_query = $mysqli->query("SHOW TABLES LIKE '$potential_table'");
        if ($test_query && $test_query->num_rows > 0) {
            echo '✅ <strong style="color: #0f9d58;">' . htmlspecialchars($potential_table) . '</strong> existe<br>';

            // Afficher les colonnes
            $columns = $mysqli->query("SHOW COLUMNS FROM `$potential_table`");
            if ($columns) {
                echo '<pre style="margin-left: 20px; font-size: 12px;">';
                echo "Colonnes :\n";
                while ($col = $columns->fetch_assoc()) {
                    echo '  - ' . $col['Field'] . ' (' . $col['Type'] . ')' . "\n";
                }
                echo '</pre>';
            }

            $found_any = true;
        } else {
            echo '❌ <em style="color: #999;">' . htmlspecialchars($potential_table) . '</em> n\'existe pas<br>';
        }
    }

    if (!$found_any) {
        echo '<br><strong style="color: #d93025;">⚠️ Aucune table de stocks trouvée !</strong>';
    }

    echo '</div>';
    ?>

    <h2>5. Test d'une Requête Simple</h2>
    <?php
    // Essayer de trouver une table qui existe
    $working_table = null;
    foreach ($tables as $table) {
        if (stripos($table, 'stock') !== false || stripos($table, 'product') !== false) {
            $working_table = $table;
            break;
        }
    }

    if ($working_table) {
        echo '<div class="info">';
        echo '<strong>Test sur la table : ' . htmlspecialchars($working_table) . '</strong><br><br>';

        $test_result = $mysqli->query("SELECT * FROM `$working_table` LIMIT 1");
        if ($test_result) {
            if ($test_result->num_rows > 0) {
                $sample = $test_result->fetch_assoc();
                echo '✅ Requête réussie ! Exemple de données :<br>';
                echo '<pre>' . htmlspecialchars(print_r($sample, true)) . '</pre>';
            } else {
                echo '⚠️ La table existe mais est vide';
            }
        } else {
            echo '❌ Erreur requête : ' . htmlspecialchars($mysqli->error);
        }

        echo '</div>';
    } else {
        echo '<div class="error">Aucune table de stocks identifiée pour le test</div>';
    }

    $mysqli->close();
    ?>

    <h2>6. Conclusion</h2>
    <div class="info">
        <strong>✅ Si la connexion fonctionne :</strong><br>
        Le problème vient probablement du nom des tables ou des colonnes dans le code PHP.<br>
        Vérifiez que les noms correspondent dans <code>includes/db_connect_stocks.php</code><br><br>

        <strong>❌ Si la connexion échoue :</strong><br>
        Contactez votre hébergeur pour vérifier les identifiants et l'accès au serveur DB.
    </div>
</body>
</html>
