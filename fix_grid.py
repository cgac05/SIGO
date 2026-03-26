#!/usr/bin/env python3

# Arreglar create.blade.php - consolidar grid en una línea

with open('resources/views/apoyos/create.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Buscar el div del grid que está en dos líneas
old_pattern = '''class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6
                    grid grid-cols-1 xl:grid-cols-3 gap-6 pb-24"'''

new_pattern = '''class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 xl:grid-cols-3 gap-6 pb-24"'''

if old_pattern in content:
    content = content.replace(old_pattern, new_pattern)
    with open('resources/views/apoyos/create.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print('✓ Fixed: Grid consolidado en una línea en create.blade.php')
else:
    print('✗ Pattern no encontrado')
