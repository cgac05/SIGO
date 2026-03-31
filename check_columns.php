<?php

try {
    $pdo = new PDO('sqlsrv:Server=localhost;Database=BD_SIGO', 'sa', '');
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Documentos_Expediente' ORDER BY COLUMN_NAME");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== Existing columns in Documentos_Expediente ===\n";
    foreach($columns as $col) {
        echo "  - $col\n";
    }
    
    echo "\n=== Existing columns in Hitos_Apoyo ===\n";
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Hitos_Apoyo' ORDER BY COLUMN_NAME");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($columns as $col) {
        echo "  - $col\n";
    }
    
    echo "\n=== Existing columns in Apoyos ===\n";
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Apoyos' ORDER BY COLUMN_NAME");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($columns as $col) {
        echo "  - $col\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
