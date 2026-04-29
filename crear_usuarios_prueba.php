<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "\n=== CREANDO USUARIOS DE PRUEBA PARA SIGO ===\n\n";

try {
    // 1. BENEFICIARIO CON GMAIL
    echo "1️⃣  Creando Beneficiario (Gmail)...\n";
    
    $emailBeneficiario = '5j3sus01@gmail.com';
    $passwordBeneficiario = 'Beneficiario@2026!999';
    
    // Verificar si ya existe
    $existeBeneficiario = DB::table('Usuarios')->where('email', $emailBeneficiario)->first();
    
    if ($existeBeneficiario) {
        echo "   ⚠️  Ya existe. Actualizando contraseña...\n";
        DB::table('Usuarios')
            ->where('email', $emailBeneficiario)
            ->update([
                'password_hash' => Hash::make($passwordBeneficiario),
                'debe_cambiar_password' => 0
            ]);
    } else {
        DB::table('Usuarios')->insert([
            'email' => $emailBeneficiario,
            'password_hash' => Hash::make($passwordBeneficiario),
            'nombre' => 'Jesús',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'López',
            'tipo_usuario' => 'Beneficiario',
            'fk_id_rol' => 4, // Beneficiario
            'activo' => 1,
            'debe_cambiar_password' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    echo "   ✅ Email: $emailBeneficiario\n";
    echo "   ✅ Contraseña: $passwordBeneficiario\n\n";

    // 2. ADMINISTRATIVO
    echo "2️⃣  Creando Administrativo...\n";
    
    $emailAdmin = 'admin@injuve.gob.mx';
    $passwordAdmin = 'AdminSIGO@2026!724';
    
    $existeAdmin = DB::table('Usuarios')->where('email', $emailAdmin)->first();
    
    if ($existeAdmin) {
        echo "   ⚠️  Ya existe. Actualizando contraseña...\n";
        DB::table('Usuarios')
            ->where('email', $emailAdmin)
            ->update([
                'password_hash' => Hash::make($passwordAdmin),
                'debe_cambiar_password' => 0
            ]);
        $adminId = $existeAdmin->id_usuario;
    } else {
        $adminId = DB::table('Usuarios')->insertGetId([
            'email' => $emailAdmin,
            'password_hash' => Hash::make($passwordAdmin),
            'nombre' => 'Admin',
            'apellido_paterno' => 'INJUVE',
            'apellido_materno' => 'Test',
            'tipo_usuario' => 'Personal',
            'fk_id_rol' => 1, // Administrativo
            'activo' => 1,
            'debe_cambiar_password' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    // Crear registro en tabla Personal si no existe
    $personalAdmin = DB::table('Personal')->where('fk_id_usuario', $adminId)->first();
    if (!$personalAdmin) {
        DB::table('Personal')->insert([
            'numero_empleado' => 'ADM-' . $adminId,
            'fk_id_usuario' => $adminId,
            'nombre' => 'Admin',
            'apellido_paterno' => 'INJUVE',
            'apellido_materno' => 'Test',
            'fk_rol' => 1, // Administrativo
            'puesto' => 'Administrativo de Sistema',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    echo "   ✅ Email: $emailAdmin\n";
    echo "   ✅ Contraseña: $passwordAdmin\n\n";

    // 3. DIRECTIVO
    echo "3️⃣  Creando Directivo...\n";
    
    $emailDirectivo = 'directivo@test.local';
    $passwordDirectivo = 'DirectivoSIGO@2026!587';
    
    $existeDirectivo = DB::table('Usuarios')->where('email', $emailDirectivo)->first();
    
    if ($existeDirectivo) {
        echo "   ⚠️  Ya existe. Actualizando contraseña...\n";
        DB::table('Usuarios')
            ->where('email', $emailDirectivo)
            ->update([
                'password_hash' => Hash::make($passwordDirectivo),
                'debe_cambiar_password' => 0
            ]);
        $directivoId = $existeDirectivo->id_usuario;
    } else {
        $directivoId = DB::table('Usuarios')->insertGetId([
            'email' => $emailDirectivo,
            'password_hash' => Hash::make($passwordDirectivo),
            'nombre' => 'Test',
            'apellido_paterno' => 'Directivo',
            'apellido_materno' => 'Usuario',
            'tipo_usuario' => 'Personal',
            'fk_id_rol' => 2, // Directivo
            'activo' => 1,
            'debe_cambiar_password' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    // Crear registro en tabla Personal si no existe
    $personalDirectivo = DB::table('Personal')->where('fk_id_usuario', $directivoId)->first();
    if (!$personalDirectivo) {
        DB::table('Personal')->insert([
            'numero_empleado' => 'DIR-' . $directivoId,
            'fk_id_usuario' => $directivoId,
            'nombre' => 'Test',
            'apellido_paterno' => 'Directivo',
            'apellido_materno' => 'Usuario',
            'fk_rol' => 2, // Directivo
            'puesto' => 'Director de Prueba',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    echo "   ✅ Email: $emailDirectivo\n";
    echo "   ✅ Contraseña: $passwordDirectivo\n\n";

    echo "\n" . str_repeat("═", 60) . "\n";
    echo "✅ USUARIOS CREADOS EXITOSAMENTE\n";
    echo str_repeat("═", 60) . "\n\n";

    echo "📋 CREDENCIALES PARA LOGIN:\n\n";

    echo "┌────────────────────────────────────────────────────┐\n";
    echo "│ 👤 BENEFICIARIO (Gmail)                            │\n";
    echo "├────────────────────────────────────────────────────┤\n";
    echo "│ Email:      5j3sus01@gmail.com                     │\n";
    echo "│ Contraseña: Beneficiario@2026!999                  │\n";
    echo "└────────────────────────────────────────────────────┘\n\n";

    echo "┌────────────────────────────────────────────────────┐\n";
    echo "│ 🔑 ADMINISTRATIVO                                  │\n";
    echo "├────────────────────────────────────────────────────┤\n";
    echo "│ Email:      admin@injuve.gob.mx                    │\n";
    echo "│ Contraseña: AdminSIGO@2026!724                     │\n";
    echo "└────────────────────────────────────────────────────┘\n\n";

    echo "┌────────────────────────────────────────────────────┐\n";
    echo "│ 👔 DIRECTIVO                                       │\n";
    echo "├────────────────────────────────────────────────────┤\n";
    echo "│ Email:      directivo@test.local                   │\n";
    echo "│ Contraseña: DirectivoSIGO@2026!587                 │\n";
    echo "└────────────────────────────────────────────────────┘\n\n";

    echo "🌐 Accede a: http://localhost/SIGO/public/login\n";
    echo "📍 Luego a: /solicitudes/proceso (Directivo)\n";
    echo "           /admin/solicitudes (Administrativo)\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
