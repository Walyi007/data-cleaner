<?php
session_start();
if (isset($_SESSION['cleanedData'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="donnees_nettoyees_' . date('Y-m-d_H-i-s') . '.csv"');
    $output = fopen('php://output', 'w');
    foreach ($_SESSION['cleanedData'] as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
} else {
    // No data to download
    header('Location: index.php');
    exit;
}
?>