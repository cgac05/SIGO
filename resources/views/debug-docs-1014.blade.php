@extends('layouts.app')
@section('content')
<div class="container mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-6">Debug: Documentos Folio 1014</h1>
    
    @php
        $docs = \App\Models\Documento::where('fk_folio', 1014)->get();
    @endphp
    
    <table class="w-full border-collapse border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">ID</th>
                <th class="border p-2">Ruta en BD</th>
                <th class="border p-2">Origen</th>
                <th class="border p-2">Google ID</th>
                <th class="border p-2">¿Archivo existe?</th>
                <th class="border p-2">Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($docs as $doc)
            @php
                $rutaExiste = false;
                if ($doc->isLocal() && $doc->ruta_archivo) {
                    $posibles = [
                        storage_path('app/public/' . $doc->ruta_archivo),
                        public_path('storage/' . $doc->ruta_archivo),
                    ];
                    foreach ($posibles as $p) {
                        if (file_exists($p)) {
                            $rutaExiste = true;
                            break;
                        }
                    }
                }
            @endphp
            <tr>
                <td class="border p-2">{{ $doc->id_doc }}</td>
                <td class="border p-2 text-sm font-mono">{{ $doc->ruta_archivo }}</td>
                <td class="border p-2">{{ $doc->origen_archivo ?? 'NULL' }}</td>
                <td class="border p-2 text-sm font-mono">{{ $doc->google_file_id ?? 'NULL' }}</td>
                <td class="border p-2">
                    @if($doc->isLocal())
                        @if($rutaExiste)
                            <span class="text-green-600">✓ Sí</span>
                        @else
                            <span class="text-red-600">✗ No encontrado</span>
                        @endif
                    @elseif($doc->isFromDrive())
                        <span class="text-blue-600">🔗 Google Drive</span>
                    @else
                        <span class="text-gray-600">?</span>
                    @endif
                </td>
                <td class="border p-2">
                    <a href="{{ route('documentos.view', ['path' => $doc->ruta_archivo]) }}" target="_blank" class="text-blue-600 hover:underline">Ver</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
