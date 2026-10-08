<?php
// data-cleaner/index.php
// Outil de nettoyage de données - Version XAMPP compatible
// Commentaires en français comme demandé

session_start();
$cleanedData = [];
$originalData = [];
$errors = [];
$successMessage = "";

// Traitement du formulaire soumis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clean_data'])) {
    // Récupération des données d'entrée (textarea ou fichier uploadé)
    if (!empty($_FILES['data_file']['tmp_name'])) {
        // Upload de fichier
        $file = $_FILES['data_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $handle = fopen($file['tmp_name'], "r");
            if ($handle) {
                while (($row = fgetcsv($handle, 0, ",")) !== false) {
                    $originalData[] = $row;
                }
                fclose($handle);
            } else {
                $errors[] = "Impossible de lire le fichier uploadé.";
            }
        } else {
            $errors[] = "Erreur lors de l'upload du fichier.";
        }
    } elseif (!empty($_POST['raw_data'])) {
        // Saisie manuelle dans le textarea
        $lines = explode("\n", trim($_POST['raw_data']));
        foreach ($lines as $line) {
            $originalData[] = str_getcsv($line);
        }
    }

    // Si nous avons des données, appliquer les nettoyages sélectionnés
    if (!empty($originalData)) {
        $cleanedData = $originalData; // Copie pour travailler dessus

        // Option 1: Supprimer les doublons
        if (!empty($_POST['remove_duplicates'])) {
            $cleanedData = array_map("unserialize", array_unique(array_map("serialize", $cleanedData)));
        }

        // Option 2: Supprimer les espaces en début/fin
        if (!empty($_POST['trim_whitespace'])) {
            foreach ($cleanedData as &$row) {
                foreach ($row as &$cell) {
                    if (is_string($cell)) {
                        $cell = trim($cell);
                    }
                }
            }
            unset($row, $cell); // Nettoyer les références
        }

        // Option 3: Supprimer les lignes vides
        if (!empty($_POST['remove_empty_rows'])) {
            $cleanedData = array_filter($cleanedData, function($row) {
                return !empty(array_filter($row, function($cell) {
                    return !is_null($cell) && $cell !== '';
                }));
            });
        }

        // Option 4: Standardiser les dates (format YYYY-MM-DD)
        if (!empty($_POST['standardize_dates'])) {
            foreach ($cleanedData as &$row) {
                foreach ($row as &$cell) {
                    if (is_string($cell)) {
                        // Essai de conversion de formats date courants vers YYYY-MM-DD
                        $dateFormats = [
                            'd/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d',
                            'm/d/Y', 'm-d-Y', 'd.m.Y', 'Y.m.d'
                        ];
                        foreach ($dateFormats as $fmt) {
                            $date = DateTime::createFromFormat($fmt, $cell);
                            if ($date && $date->format($fmt) === $cell) {
                                $cell = $date->format('Y-m-d');
                                break;
                            }
                        }
                    }
                }
            }
            unset($row, $cell);
        }

        // Option 5: Supprimer les colonnes vides (toutes les cellules vides)
        if (!empty($_POST['remove_empty_columns'])) {
            if (!empty($cleanedData)) {
                $numCols = count($cleanedData[0]);
                $colsToKeep = [];
                for ($col = 0; $col < $numCols; $col++) {
                    $colHasData = false;
                    foreach ($cleanedData as $row) {
                        if (isset($row[$col]) && !is_null($row[$col]) && $row[$col] !== '') {
                            $colHasData = true;
                            break;
                        }
                    }
                    if ($colHasData) {
                        $colsToKeep[] = $col;
                    }
                }
                // Reconstruire les lignes avec seulement les colonnes utiles
                $newData = [];
                foreach ($cleanedData as $row) {
                    $newRow = [];
                    foreach ($colsToKeep as $col) {
                        $newRow[] = $row[$col] ?? null;
                    }
                    $newData[] = $newRow;
                }
                $cleanedData = $newData;
            }
        }

        $successMessage = "Données nettoyées avec succès ! " . count($cleanedData) . " lignes traitées.";
    } else {
        $errors[] = "Aucune donnée à traiter. Veuillez entrer ou uploader des données.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outil de Nettoyage de Données</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Réutilisation des variables du portfolio pour cohérence visuelle */
        :root {
            --accent: #22c490; /* Déjà défini dans ton style.css principal */
            --bg-2: #0c1015;
            --text: #eef2f6;
            --muted: #8b97a6;
        }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
        .btn-solid { background: linear-gradient(120deg, #1db882 0%, #4ab8d4 45%, #c99b40 100%); }
        .data-preview { max-height: 400px; overflow-y: auto; border: 1px solid var(--line-2); border-radius: var(--radius-sm); }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--line-2); }
        th { background-color: var(--bg-2); font-weight: 600; }
        tr:hover { background-color: rgba(255,255,255,0.02); }
    </style>
</head>
<body>
    <div class="container">
        <header style="text-align: center; margin-bottom: 2rem;">
            <h1 style="color: var(--accent); font-family: 'Space Grotesk', sans-serif;">🧹 Outil de Nettoyage de Données</h1>
            <p style="color: var(--muted); max-width: 600px; margin: 0 auto;">
                Nettoyez vos données sales en quelques clics : doublons, espaces, formats de dates, lignes vides, etc.
                Idéal pour préparer vos jeux de données avant analyse dans Power BI, Python ou Excel.
            </p>
        </header>

        <?php if (!empty($errors)): ?>
            <div style="background: rgba(239,107,107,0.1); border: 1px solid #ef6b6b; color: #ef6b6b; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
                <strong>Erreurs :</strong><br>
                <?php foreach ($errors as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div style="background: rgba(34,196,144,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem;">
                <strong>Succès :</strong> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="background: var(--card); padding: 2rem; border-radius: var(--radius); border: 1px solid var(--line);">
            <div style="display: grid; gap: 1.5rem; grid-template-columns: 1fr 1fr;">
                <!-- Zone de saisie des données -->
                <div>
                    <label for="raw_data" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text);">Données à nettoyer</label>
                    <div style="position: relative;">
                        <textarea id="raw_data" name="raw_data" rows="10" placeholder="Collez vos données CSV ici (séparées par des virgules) ou uploadez un fichier ci-dessous..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--line-2); border-radius: var(--radius-sm); font-family: monospace; resize: vertical; background: var(--bg-2); color: var(--text);"></textarea>
                        <label for="data_file" style="position: absolute; top: 0.75rem; right: 0.75rem; background: var(--accent-2); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.85rem; cursor: pointer;">
                            Ou uploader un fichier CSV
                        </label>
                        <input type="file" id="data_file" name="data_file" accept=".csv,.tsv,.txt" style="display: none;">
                    </div>
                    <small style="color: var(--muted);">Format attendu : valeurs séparées par des virgules (CSV). Première ligne = en-têtes (optionnelle).</small>
                </div>

                <!-- Options de nettoyage -->
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text);">Options de nettoyage</label>
                    <div style="display: grid; gap: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text);">
                            <input type="checkbox" name="remove_duplicates" value="1" checked>
                            <span>Supprimer les lignes dupliquées exactement</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text);">
                            <input type="checkbox" name="trim_whitespace" value="1" checked>
                            <span>Supprimer les espaces en début et fin de chaque cellule</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text);">
                            <input type="checkbox" name="remove_empty_rows" value="1">
                            <span>Supprimer les lignes complètement vides</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text);">
                            <input type="checkbox" name="standardize_dates" value="1">
                            <span>Standardiser les formats de dates vers YYYY-MM-DD</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text);">
                            <input type="checkbox" name="remove_empty_columns" value="1">
                            <span>Supprimer les colonnes totalement vides</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 1.5rem;">
                <button type="submit" name="clean_data" class="btn btn-solid btn-block" style="cursor: pointer;">
                    🚀 Nettoyer les données
                </button>
                <button type="reset" class="btn btn-ghost btn-block" style="margin-left: 0.5rem; cursor: pointer;">
                    🔄 Réinitialiser
                </button>
            </div>
        </form>

        <!-- Résultats -->
        <?php if (!empty($cleanedData)): ?>
            <section style="margin-top: 2.5rem;">
                <h2 style="color: var(--accent); border-bottom: 1px solid var(--line-2); padding-bottom: 0.5rem;">📊 Résultats du nettoyage</h2>
                
                <div style="display: grid; gap: 1.5rem; grid-template-columns: 1fr 1fr;">
                    <!-- Avant -->
                    <div>
                        <h3 style="color: var(--muted);">Avant nettoyage (<span style="color: var(--text);"><?php echo count($originalData); ?></span> lignes)</h3>
                        <?php if (!empty($originalData)): ?>
                            <div class="data-preview">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php
                                            // Afficher les en-têtes (première ligne ou génériques)
                                            $headers = [];
                                            if (!empty($originalData[0])) {
                                                foreach ($originalData[0] as $key => $value) {
                                                    $headers[] = is_string($value) && strlen($value) > 0 ? htmlspecialchars($value) : "Colonne " . ($key+1);
                                                }
                                            } else {
                                                $headers = ["Colonne 1", "Colonne 2", "Colonne 3"]; // Fallback
                                            }
                                            foreach ($headers as $header): ?>
                                                <th><?php echo $header; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Afficher les 20 premières lignes pour éviter la surcharge
                                        $displayRows = array_slice($originalData, 0, 20);
                                        foreach ($displayRows as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $cell): ?>
                                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo is_string($cell) ? htmlspecialchars($cell) : (is_null($cell) ? '' : $cell); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (count($originalData) > 20): ?>
                                            <tr><td colspan="<?php echo count($headers); ?>" style="text-align: center; color: var(--muted); font-style: italic;">
                                                ... et <?php echo count($originalData) - 20; ?> lignes supplémentaires
                                            </td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Après -->
                    <div>
                        <h3 style="color: var(--muted);">Après nettoyage (<span style="color: var(--text);"><?php echo count($cleanedData); ?></span> lignes)</h3>
                        <?php if (!empty($cleanedData)): ?>
                            <div class="data-preview">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php
                                            // Afficher les en-têtes (première ligne ou génériques)
                                            $headers = [];
                                            if (!empty($cleanedData[0])) {
                                                foreach ($cleanedData[0] as $key => $value) {
                                                    $headers[] = is_string($value) && strlen($value) > 0 ? htmlspecialchars($value) : "Colonne " . ($key+1);
                                                }
                                            } else {
                                                $headers = ["Colonne 1", "Colonne 2", "Colonne 3"]; // Fallback
                                            }
                                            foreach ($headers as $header): ?>
                                                <th><?php echo $header; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Afficher les 20 premières lignes pour éviter la surcharge
                                        $displayRows = array_slice($cleanedData, 0, 20);
                                        foreach ($displayRows as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $cell): ?>
                                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo is_string($cell) ? htmlspecialchars($cell) : (is_null($cell) ? '' : $cell); ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (count($cleanedData) > 20): ?>
                                            <tr><td colspan="<?php echo count($headers); ?>" style="text-align: center; color: var(--muted); font-style: italic;">
                                                ... et <?php echo count($cleanedData) - 20; ?> lignes supplémentaires
                                            </td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bouton de téléchargement -->
                <div style="text-align: center; margin-top: 1.5rem;">
                    <?php
                    // Générer le CSV pour téléchargement
                    $output = fopen('php://output', 'w');
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="donnees_nettoyees_' . date('Y-m-d_H-i-s') . '.csv"');
                    foreach ($cleanedData as $row) {
                        fputcsv($output, $row);
                    }
                    fclose($output);
                    ?>
                    <a href="index.php?download=cleaned" class="btn btn-solid btn-block" style="background: var(--accent-2);">
                        ⬇️ Télécharger les données nettoyées (CSV)
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>