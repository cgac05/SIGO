#!/usr/bin/env python3

with open('resources/views/apoyos/edit.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# El problema: hay un SVG flotante entre el cierre del panel Documentos y el panel Imagen
# Necesito eliminar las líneas 425-430 que tienen el SVG mal colocado

old_section = '''                </div>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                        </svg>
                        Imagen representativa
                    </div>
                    <div class="panel-body space-y-3">'''

new_section = '''                </div>

                {{-- Panel: Imagen --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                        </svg>
                        Imagen representativa
                    </div>
                    <div class="panel-body space-y-3">'''

if old_section in content:
    content = content.replace(old_section, new_section)
    with open('resources/views/apoyos/edit.blade.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print('✓ Fixed edit.blade.php: Limpiado SVG flotante y reconstruido panel Imagen')
else:
    print('✗ Pattern no encontrado en edit.blade.php')
    print('Verificando con búsqueda alternativa...')
    if 'Imagen representativa' in content and old_section not in content:
        print('El problema ya fue parcialmente reparado o la estructura es diferente')
