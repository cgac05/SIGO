<?php
/**
 * Script de verificación - Validación de corrección de fechas
 */

echo "\n=== VERIFICACIÓN DE CORRECCIÓN DE FECHAS ===\n";

$viewPath = __DIR__ . '/resources/views/apoyos/form.blade.php';
$controllerPath = __DIR__ . '/app/Http/Controllers/ApoyoController.php';

// Verificar Vista
echo "\n✓ Verificando Vista:\n";
$viewContent = file_get_contents($viewPath);

$viewChecks = [
    'Función convertDateFormat' => preg_match('/function convertDateFormat/', $viewContent),
    'Conversión fechaInicio' => preg_match('/convertDateFormat\(fechaInicio\)/', $viewContent),
    'Conversión fechafin' => preg_match('/convertDateFormat\(fechafin\)/', $viewContent),
    'Logs de conversión' => preg_match('/📅.*Fecha.*convertida/', $viewContent),
];

foreach ($viewChecks as $check => $result) {
    echo ($result ? '  ✅' : '  ❌') . " $check\n";
}

$viewPassed = array_sum($viewChecks) === count($viewChecks);

// Verificar Controlador
echo "\n✓ Verificando Controlador:\n";
$controllerContent = file_get_contents($controllerPath);

$controllerChecks = [
    'date_format:Y-m-d en fechaInicio' => preg_match("/fechaInicio.*date_format:Y-m-d/", $controllerContent),
    'date_format:Y-m-d en fechafin' => preg_match("/fechafin.*date_format:Y-m-d/", $controllerContent),
    'Mensaje de error date_format fechaInicio' => preg_match("/fechaInicio\.date_format/", $controllerContent),
    'Mensaje de error date_format fechafin' => preg_match("/fechafin\.date_format/", $controllerContent),
    'after_or_equal en fechafin' => preg_match("/fechafin.*after_or_equal/", $controllerContent),
];

foreach ($controllerChecks as $check => $result) {
    echo ($result ? '  ✅' : '  ❌') . " $check\n";
}

$controllerPassed = array_sum($controllerChecks) === count($controllerChecks);

// Resumen
echo "\n=== RESUMEN ===\n";
echo ($viewPassed ? '✅' : '❌') . " Vista: " . ($viewPassed ? 'OK' : 'FALTAN CAMBIOS') . "\n";
echo ($controllerPassed ? '✅' : '❌') . " Controlador: " . ($controllerPassed ? 'OK' : 'FALTAN CAMBIOS') . "\n";

if ($viewPassed && $controllerPassed) {
    echo "\n🎉 ¡Todos los cambios se aplicaron correctamente!\n";
    echo "\nPróximos pasos:\n";
    echo "1. Accede a http://localhost:8000/apoyos/create\n";
    echo "2. Llena los campos incluyendo:\n";
    echo "   - Fecha de inicio: 13/04/2026\n";
    echo "   - Fecha de cierre: 19/04/2026\n";
    echo "3. Abre F12 (Console) y verifica los logs de conversión\n";
    echo "4. Haz clic en 'Crear apoyo'\n";
    echo "5. Debería guardar sin errores de validación\n";
} else {
    echo "\n⚠️ Debe revisar los cambios\n";
    exit(1);
}
?>
