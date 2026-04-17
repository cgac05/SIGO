@extends('layouts.app')
@section('content')
<div class="container mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6">Debug: Análisis de rutas</h1>
    
    <div class="bg-white rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Storage Paths</h2>
        <pre class="bg-gray-100 p-4 text-sm overflow-x-auto">
storage_path(): {{ storage_path() }}
storage_path('app/public'): {{ storage_path('app/public') }}
public_path(): {{ public_path() }}
public_path('storage'): {{ public_path('storage') }}
        </pre>
    </div>

    <div class="bg-white rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">Documentos de Folio 1014</h2>
        
        @php
            $docs = \App\Models\Documento::where('fk_folio', 1014)->orderBy('id_doc', 'desc')->take(5)->get();
        @endphp
        
        <table class="w-full border-collapse text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">ID</th>
                    <th class="border p-2 text-left">Ruta en BD</th>
                    <th class="border p-2 text-left">Origen</th>
                    <th class="border p-2 text-left">¿Existe locale?</th>
                    <th class="border p-2 text-left">Caminos verificados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($docs as $doc)
                @php
                    $ruta = $doc->ruta_archivo;
                    $paths = [
                        'storage_path(app/public/' . $ruta . ')' => storage_path('app/public/' . $ruta),
                        'public_path(storage/' . $ruta . ')' => public_path('storage/' . $ruta),
                    ];
                    $exists = false;
                    $foundPath = null;
                    foreach ($paths as $label => $fullPath) {
                        if (file_exists($fullPath)) {
                            $exists = true;
                            $foundPath = $label;
                            break;
                        }
                    }
                @endphp
                <tr>
                    <td class="border p-2">{{ $doc->id_doc }}</td>
                    <td class="border p-2 font-mono text-xs">{{ $ruta }}</td>
                    <td class="border p-2">{{ $doc->origen_archivo ?? 'NULL' }}</td>
                    <td class="border p-2">
                        @if($exists)
                            <span class="text-green-600 font-bold">✓ Sí</span>
                        @else
                            <span class="text-red-600 font-bold">✗ No</span>
                        @endif
                    </td>
                    <td class="border p-2 text-xs">
                        @foreach($paths as $label => $fullPath)
                            <div>{{ $label }}</div>
                            <div class="text-gray-600">{{ file_exists($fullPath) ? '✓' : '✗' }} {{ $fullPath }}</div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
