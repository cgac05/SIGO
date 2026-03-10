<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registrar Solicitud') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ 
        mostrarModal: {{ session('exito') ? 'true' : 'false' }},
        apoyoSeleccionado: '',
        listaApoyos: {{ isset($apoyosJson) ? $apoyosJson : '[]' }},
        get documentosRequeridos() {
            if (!this.apoyoSeleccionado) return [];
            let apoyo = this.listaApoyos.find(a => a.id_apoyo == this.apoyoSeleccionado);
            return apoyo ? apoyo.requisitos : [];
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <h1 class="text-2xl font-bold mb-2 text-indigo-800">Nueva Solicitud de Apoyo</h1>
                    <p class="mb-6 text-indigo-600">Completa el formulario a continuación. Los campos marcados con <span class="text-red-600">*</span> son obligatorios.</p>

                    <div class="mb-6 p-4 bg-indigo-50 border-l-4 border-indigo-400 text-indigo-700 rounded">
                        <p class="font-semibold">Antes de subir archivos:</p>
                        <ul class="list-disc list-inside">
                            <li>La lista de documentos cambiará dependiendo del apoyo que elijas.</li>
                            <li>Todos deben ser <strong>archivos PDF</strong>, <span class="font-semibold">excepto</span> la fotografía.</li>
                        </ul>
                    </div>

                    <form action="{{ route('solicitud.guardar') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label for="apoyo" class="block font-bold text-md text-gray-700 mb-2">1. Selecciona el Tipo de Apoyo:</label>
                            
                            <select name="apoyo" id="apoyo" x-model="apoyoSeleccionado" class="block w-full md:w-1/2 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                                <option value="">Selecciona un apoyo para ver los requisitos...</option>
                                <template x-for="apoyo in listaApoyos" :key="apoyo.id_apoyo">
                                    <option :value="apoyo.id_apoyo" x-text="apoyo.nombre_apoyo"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="documentosRequeridos.length > 0" x-transition.opacity>
                            <h2 class="font-bold text-md text-gray-700 mb-4">2. Documentación Requerida para este apoyo:</h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                                    <thead class="bg-indigo-100">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-indigo-700 uppercase tracking-wider">Documento</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-indigo-700 uppercase tracking-wider">Archivo a subir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        
                                        <template x-for="req in documentosRequeridos" :key="req.fk_id_tipo_doc">
                                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-100 transition-colors duration-300">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-lg font-semibold text-indigo-900"><span x-text="req.nombre_documento"></span> <span class="text-red-600">*</span></div>
                                                    
                                                    <div class="text-sm text-gray-500" x-show="req.fk_id_tipo_doc == 7">Blanco y negro o a color (JPEG/PNG).</div>
                                                    <div class="text-sm text-gray-500" x-show="req.fk_id_tipo_doc != 7">Formato PDF obligatorio.</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="file" 
                                                           :name="'documento_' + req.fk_id_tipo_doc" 
                                                           :accept="req.fk_id_tipo_doc == 7 ? 'image/jpeg, image/png' : '.pdf'" 
                                                           class="block w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-200 transition" 
                                                           required />
                                                </td>
                                            </tr>
                                        </template>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-center mt-12 mb-4" x-show="documentosRequeridos.length > 0">
                            <button type="submit" class="inline-flex items-center px-10 py-4 bg-indigo-600 border border-transparent rounded-lg font-bold text-base text-white uppercase tracking-wider hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-colors ease-in-out duration-300 shadow-md">
                                Registrar Solicitud
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="mostrarModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">¡Éxito!</h3>
                        <p class="text-md text-gray-600">¡La solicitud se ha registrado correctamente!</p>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-center">
                        <button type="button" @click="mostrarModal = false" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>