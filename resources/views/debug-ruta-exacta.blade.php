@extends('layouts.app')
@section('content')
<div class="container mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6">Debug: Ruta exacta en BD folio 1014</h1>
    
    @php
        // Usar el modelo directamente
        $docs = \App\Models\Documento::where('fk_folio', 1014)->get();
    @endphp
    
    <div class="bg-white rounded-lg p-6">
        <h3 class="text-lg font-bold mb-4">Documentos registrados:</h3>
        
        @forelse($docs as $doc)
        <div class="mb-6 border-l-4 border-blue-500 pl-4">
            <p class="font-bold">Documento ID: {{ $doc->id_doc }}</p>
            <p class="text-sm text-gray-600">Tipo: {{ $doc->fk_id_tipo_doc }}</p>
            
            <div class="mt-3 bg-gray-50 p-3 rounded font-mono text-sm">
                <p><strong>Ruta en BD:</strong> {{ $doc->ruta_archivo }}</p>
                <p><strong>Origen:</strong> {{ $doc->origen_archivo ?? 'NULL' }}</p>
                <p><strong>Google ID:</strong> {{ $doc->google_file_id ?? 'NULL' }}</p>
            </div>

            <p class="text-xs text-gray-600 mt-2"><strong>Verificación de existencia:</strong></p>
            <ul class="text-xs text-gray-700 ml-4 mt-1">
                @php
                    $ruta = $doc->ruta_archivo;
                    $checks = [
                        'storage_path(app/public/' . $ruta . ')' => storage_path('app/public/' . $ruta),
                        'public_path(storage/' . $ruta . ')' => public_path('storage/' . $ruta),
                        'Storage::disk(public)->exists()' => \Illuminate\Support\Facades\Storage::disk('public')->exists($ruta) ? 'TRUE' : 'FALSE',
                    ];
                @endphp
                @foreach($checks as $label => $result)
                    <li>
                        {{ $label }}:
                        @if(is_string($result) && (str_contains($result, 'TRUE') || str_contains($result, 'FALSE')))
                            <span class="{{ str_contains($result, 'TRUE') ? 'text-green-600' : 'text-red-600' }}">{{ $result }}</span>
                        @elseif(file_exists($result))
                            <span class="text-green-600 font-bold">✓ EXISTE</span>
                        @else
                            <span class="text-red-600">✗ No existe</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @empty
        <div class="text-gray-600">No hay documentos para folio 1014</div>
        @endforelse
    </div>

    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-sm text-yellow-900">
            <strong>Nota:</strong> Si el documento dice "No existe" en todas las rutas, probablemente fue eliminado o movido desde que se guardó.
        </p>
    </div>
</div>
@endsection
