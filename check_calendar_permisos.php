<?php
$pdo = new PDO('sqlsrv:server=localhost;database=BD_SIGO', 'sa', '');
$stmt = $pdo->query('SELECT TOP 10 id_permiso, fk_id_directivo, email_directivo, activo, created_at, updated_at FROM directivos_calendario_permisos ORDER BY id_permiso DESC');
foreach($stmt as $row) {
    echo 'ID: ' . $row['id_permiso'] . ', Directivo: ' . $row['fk_id_directivo'] . ', Email: ' . $row['email_directivo'] . ', Activo: ' . $row['activo'] . ', Created: ' . $row['created_at'] . PHP_EOL;
}
?>