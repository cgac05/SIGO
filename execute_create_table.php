<?php
$host = 'localhost';
$database = 'BD_SIGO';
$username = 'SigoWebAppUser';
$password = 'UsuarioSigo159';

try {
    $conn = new PDO("sqlsrv:Server=$host;Database=$database", $username, $password);
    
    $sql = file_get_contents('create_politicas_retencion.sql');
    
    // Split by GO and execute each statement
    $statements = array_filter(array_map('trim', preg_split('/GO\s*\n/i', $sql)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            echo "Executing: " . substr($stmt, 0, 60) . "...\n";
            $conn->exec($stmt);
        }
    }
    
    echo "\n✓ Table created successfully!\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
