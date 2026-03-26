#!/usr/bin/env python3
import re

with open('resources/views/apoyos/create.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Buscar y eliminar los 4 divs vacíos sin contenido que cierran luego del panel de Hitos
# Patrón: hitos-custom-grid, luego 4 divs vacíos
pattern = r'(<div id="hitos-custom-grid" class="space-y-3"><\/div>\s*<\/div>\s*<\/div>)\n\n\s*<\/div>\n\s*<\/div>\n\s*<\/div>\n\s*<\/div>\n\n(\s*<\/div>{{-- \/columna izquierda --}})'

replacement = r'\1\n\n\2'

if re.search(pattern, content):
    content = re.sub(pattern, replacement, content)
    with open('resources/views/apoyos/create.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print('✓ Fixed: Eliminados 4 divs vacíos sin contenido')
else:
    print('Pattern no encontrado, intentando alternativa...')
    # Intenta de forma más específica
    old = '                    </div>\n                </div>\n\n\n                            </div>\n                        </div>\n                    </div>\n                </div>\n\n            </div>{{-- /columna izquierda --}}'
    new = '                    </div>\n                </div>\n\n            </div>{{-- /columna izquierda --}}'
    
    if old in content:
        content = content.replace(old, new)
        with open('resources/views/apoyos/create.blade.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print('✓ Fixed: Eliminados divs vacíos')
    else:
        print('✗ No pattern matched')
