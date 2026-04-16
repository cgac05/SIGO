<?php
/**
 * Generador de Script SQL para migración a Azure
 * Exporta estructura + datos de BD_SIGO
 */

try {
    $pdo = new PDO('sqlsrv:Server=JDEV\PARTIDA;Database=BD_SIGO', '', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $output = "-- ============================================================================\n";
    $output .= "-- BD_SIGO SCRIPT COMPLETO PARA AZURE SQL\n";
    $output .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- ============================================================================\n\n";
    
    // 1. Obtener todas las tablas
    $stmt = $pdo->query("
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($tables as $table) {
        $output .= "\n-- ============================================================================\n";
        $output .= "-- TABLE: $table\n";
        $output .= "-- ============================================================================\n\n";
        
        // Obtener DDL de la tabla
        $stmt = $pdo->query("EXEC sp_helptext N'$table'");
        $ddl = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si eso no funciona, obtener CREATE TABLE
        $stmt = $pdo->query("
            SELECT 
                'CREATE TABLE ' + TABLE_NAME + ' (' AS sql
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_NAME = '$table'
        ");
        
        // Obtener columnas
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                DATA_TYPE,
                CHARACTER_MAXIMUM_LENGTH,
                IS_NULLABLE,
                COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '$table'
            ORDER BY ORDINAL_POSITION
        ");
        
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS [$table];\n";
        $output .= "CREATE TABLE [$table] (\n";
        
        $colDefs = [];
        foreach($cols as $col) {
            $colDef = "    [" . $col['COLUMN_NAME'] . "] " . strtoupper($col['DATA_TYPE']);
            
            if($col['CHARACTER_MAXIMUM_LENGTH'] && $col['CHARACTER_MAXIMUM_LENGTH'] > 0) {
                $colDef .= "(" . $col['CHARACTER_MAXIMUM_LENGTH'] . ")";
            }
            
            $colDef .= " " . ($col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL');
            
            if($col['COLUMN_DEFAULT']) {
                $colDef .= " DEFAULT " . $col['COLUMN_DEFAULT'];
            }
            
            $colDefs[] = $colDef;
        }
        
        $output .= implode(",\n", $colDefs) . "\n";
        $output .= ");\n\n";
        
        // Obtener datos
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM [$table]");
        $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        if($rowCount > 0) {
            $output .= "-- Inserting $rowCount rows\n";
            $stmt = $pdo->query("SELECT * FROM [$table]");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($rows as $row) {
                $cols = array_keys($row);
                $colNames = implode("], [", $cols);
                
                $values = [];
                foreach($row as $val) {
                    if($val === null) {
                        $values[] = "NULL";
                    } else {
                        $val = str_replace("'", "''", $val);
                        $values[] = "'" . $val . "'";
                    }
                }
                $valueStr = implode(", ", $values);
                
                $output .= "INSERT INTO [$table] ([$colNames]) VALUES ($valueStr);\n";
            }
        }
    }
    
    // Guardar a archivo
    $file = 'BD_SIGO_export_azure.sql';
    file_put_contents($file, $output);
    
    echo "✓ Script generado: $file\n";
    echo "✓ Tamaño: " . round(filesize($file) / 1024, 2) . " KB\n";
    echo "✓ Tablas exportadas: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
