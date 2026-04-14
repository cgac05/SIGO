<?php
/**
 * Script de verificación - Confirmar que los cambios se aplicaron correctamente
 * 
 * Uso: php artisan tinker < check_corrections.php
 * O copiar y pegar en tinker
 */

// 1. Verificar que la Vista tiene la sección de mensajes
echo "=== VERIFICACIÓN DE CAMBIOS ===\n";

$viewPath = __DIR__ . '/resources/views/apoyos/form.blade.php';
$viewContent = file_get_contents($viewPath);

echo "\n✓ Verificando Vista:\n";

$checks = [
    'messagesContainer' => preg_match('/id="messagesContainer"/', $viewContent),
    'errorsAlert' => preg_match('/id="errorsAlert"/', $viewContent),
    'successAlert' => preg_match('/id="successAlert"/', $viewContent),
    'formularioApoyo addEventListener' => preg_match('/addEventListener.*submit/', $viewContent),
    'fetch formularioApoyo' => preg_match('/fetch.*this\.action/', $viewContent),
];

foreach ($checks as $check => $result) {
    echo ($result ? '  ✅' : '  ❌') . " $check\n";
}

$allViewChecksPass = array_sum($checks) === count($checks);

// 2. Verificar que el Controlador tiene el manejo correcto
echo "\n✓ Verificando Controlador:\n";

$controllerPath = __DIR__ . '/app/Http/Controllers/ApoyoController.php';
$controllerContent = file_get_contents($controllerPath);

$controllerChecks = [
    'ValidationException imported' => preg_match('/ValidationException/', $controllerContent),
    'catch ValidationException' => preg_match('/catch.*ValidationException/', $controllerContent),
    'return errors JSON' => preg_match('/errors.*\$e->errors/', $controllerContent),
    'Mensajes en español' => preg_match('/El nombre del apoyo es obligatorio/', $controllerContent),
];

foreach ($controllerChecks as $check => $result) {
    echo ($result ? '  ✅' : '  ❌') . " $check\n";
}

$allControllerChecksPass = array_sum($controllerChecks) === count($controllerChecks);

// 3. Resumen final
echo "\n=== RESUMEN ===\n";
echo ($allViewChecksPass ? '✅' : '❌') . " Vista: " . ($allViewChecksPass ? 'OK' : 'FALTAN CAMBIOS') . "\n";
echo ($allControllerChecksPass ? '✅' : '❌') . " Controlador: " . ($allControllerChecksPass ? 'OK' : 'FALTAN CAMBIOS') . "\n";

if ($allViewChecksPass && $allControllerChecksPass) {
    echo "\n🎉 ¡Todos los cambios se aplicaron correctamente!\n";
    echo "\nPróximos pasos:\n";
    echo "1. Acceder a http://localhost:8000/apoyos/create\n";
    echo "2. Hacer clic en 'Crear apoyo' sin llenar datos\n";
    echo "3. Debería mostrarse alerta roja con errores\n";
} else {
    echo "\n⚠️ Debe revisar los cambios\n";
}
?>
