#!/usr/bin/env python3

files = {
    'create.blade.php': 'resources/views/apoyos/create.blade.php',
    'edit.blade.php': 'resources/views/apoyos/edit.blade.php'
}

for fname, fpath in files.items():
    with open(fpath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    print(f"\n{'='*60}")
    print(f"Verificando: {fname}")
    print(f"{'='*60}")
    
    column_left_start = None
    column_right_start = None
    column_left_end = None
    column_right_end = None
    
    for i, line in enumerate(lines, 1):
        if 'COLUMNA IZQUIERDA' in line or 'columna izquierda' in line:
            if 'xl:col-span-2' in lines[i] if i < len(lines) else False:
                column_left_start = i
        if 'COLUMNA DERECHA' in line or 'columna derecha' in line:
            if 'xl:col-span-1' in (lines[i] if i < len(lines) else ''):
                column_right_start = i
        if '/columna izquierda' in line:
            column_left_end = i
        if '/columna derecha' in line:
            column_right_end = i
    
    print(f"Columna Izquierda: inicia línea {column_left_start}, termina {column_left_end}")
    print(f"Columna Derecha: inicia línea {column_right_start}, termina {column_right_end}")
    print(f"Líneas Columna Izquierda: {column_left_end - column_left_start if column_left_end and column_left_start else 'ERROR'}")
    print(f"Líneas Columna Derecha: {column_right_end - column_right_start if column_right_end and column_right_start else 'ERROR'}")
    
    # Mostrar líneas alrededor del cierre de columna izquierda
    if column_left_end:
        start = max(0, column_left_end - 5)
        end = min(len(lines), column_left_end + 3)
        print(f"\nLíneas {start+1}-{end} (cierre columna izquierda):")
        for i in range(start, end):
            print(f"{i+1:4d}: {lines[i]}", end='')
    
    # Mostrar líneas alrededor del inicio de columna derecha        
    if column_right_start:
        start = max(0, column_right_start - 3)
        end = min(len(lines), column_right_start + 5)
        print(f"\nLíneas {start+1}-{end} (inicio columna derecha):")
        for i in range(start, end):
            print(f"{i+1:4d}: {lines[i]}", end='')

print("\n" + "="*60)
print("✓ Verificación completada")
