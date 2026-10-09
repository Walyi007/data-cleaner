<?php
// data-cleaner/history.php
// Affichage de l'historique des opérations de nettoyage
?>
<?php if ($pdo !== null): ?>
    <?php
    try {
        $stmt = $pdo->query("SELECT * FROM cleaning_history ORDER BY created_at DESC");
        $history = $stmt->fetchAll();
    } catch (PDOException $e) {
        $history = false;
    }
    ?>
    <?php if ($history && count($history) > 0): ?>
        <!-- Historique des opérations -->
        <section style="margin-top: 2.5rem;">
            <h2>🕒 Historique des opérations</h2>
            <div class="data-preview">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Opération</th>
                            <th>Lignes traitées</th>
                            <th>Doublons supprimés</th>
                            <th>Lignes vides supprimées</th>
                            <th>Colonnes vides supprimées</th>
                            <th>Dates standardisées</th>
                            <th>Date</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['operation_name']) ?></td>
                                <td><?= number_format($row['rows_processed']) ?></td>
                                <td><?= number_format($row['duplicates_removed']) ?></td>
                                <td><?= number_format($row['empty_rows_removed']) ?></td>
                                <td><?= number_format($row['columns_removed']) ?></td>
                                <td><?= number_format($row['dates_standardized']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                <td><?= htmlspecialchars($row['user_ip']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <section style="margin-top: 2.5rem;">
            <p>Aucune opération enregistrée.</p>
        </section>
    <?php endif; ?>
<?php else: ?>
    <section style="margin-top: 2.5rem;">
        <p>Base de données non configurée. L'historique n'est pas disponible.</p>
    </section>
<?php endif; ?>