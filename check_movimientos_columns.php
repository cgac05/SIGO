<?php
$pdo = new PDO('sqlsrv:Server=localhost;Database=BD_SIGO', 'sa', '1234');
$result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'movimientos_presupuestarios'")->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $result);
