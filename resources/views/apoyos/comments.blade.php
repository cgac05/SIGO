@php
    $isBeneficiario = $user && $user->isBeneficiario();
    $canEditApoyo = $user && $user->personal && in_array((int) $user->personal->fk_rol, [1, 2], true);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('apoyos.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Detalle del apoyo</h2>
                    <p class="text-xs text-slate-500">Vista informativa y comentarios publicos</p>
                </div>
            </div>
            @if($canEditApoyo)
                <a href="{{ route('apoyos.edit', $apoyo->id_apoyo) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Editar apoyo</a>
            @endif
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-100 py-6" x-data="apoyoCommentsApp()">
        <script>
            window.apoyoCommentsBootstrap = {
                apoyoId: {{ (int) $apoyo->id_apoyo }},
                currentUserId: {{ (int) $user->id_usuario }},
                comments: @json($comments),
                endpoints: {
                    store: '{{ route('apoyos.comments.store', $apoyo->id_apoyo) }}',
                    updateBase: '{{ url('/apoyos/' . $apoyo->id_apoyo . '/comentarios') }}',
                    likeBase: '{{ url('/apoyos/' . $apoyo->id_apoyo . '/comentarios') }}'
                }
            };
        </script>

        <div class="mx-auto w-full px-4 md:px-6 space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                @if(!empty($apoyo->foto_url))
                    <img src="{{ $apoyo->foto_url }}" alt="{{ $apoyo->nombre_apoyo }}" class="h-72 md:h-[420px] w-full object-cover">
                @else
                    <div class="h-72 md:h-[420px] w-full border-b border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-sm text-slate-400">Sin imagen disponible</div>
                @endif

                <div class="p-6 md:p-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Convocatoria</p>
                    <h3 class="mt-2 w-full text-3xl md:text-4xl font-extrabold text-slate-900 leading-tight">{{ $apoyo->nombre_apoyo }}</h3>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $apoyo->tipo_apoyo === 'Económico' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $apoyo->tipo_apoyo }}</span>
                        @if($isBeneficiario && !empty($solicitudActiva))
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">Tu solicitud: {{ $solicitudActiva->estado }}</span>
                        @endif
                    </div>

                    <div class="mt-5 grid grid-cols-1 xl:grid-cols-12 gap-5">
                        <div class="xl:col-span-8">
                            <div class="prose prose-sm md:prose-base max-w-none text-slate-700">{!! $apoyo->descripcion_html ?: '<p>Sin descripcion disponible.</p>' !!}</div>

                            @if($isBeneficiario)
                                <div class="mt-5">
                                    @if(!empty($solicitudActiva))
                                        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 mb-3">
                                            <p class="text-sm font-bold text-blue-800">Ya tienes una solicitud en proceso</p>
                                            <p class="text-xs text-blue-700 mt-1">Folio: {{ $solicitudActiva->folio }} · Estado: {{ $solicitudActiva->estado }}</p>
                                        </div>
                                    @endif

                                    <a href="{{ route('solicitud.create', $apoyo->id_apoyo) }}"
                                       class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-600 transition {{ !empty($solicitudActiva) ? 'opacity-50 pointer-events-none' : '' }}">
                                        Ir a carga formal de documentacion
                                    </a>
                                </div>
                            @endif
                        </div>

                        <aside class="xl:col-span-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h4 class="text-xs font-extrabold uppercase tracking-wide text-slate-600">Fechas importantes</h4>
                            @if(isset($hitos) && $hitos->count())
                                <ol class="mt-3 space-y-2">
                                    @foreach($hitos as $hito)
                                        <li class="flex items-start gap-2">
                                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ $hito->titulo_hito ?? $hito->nombre_hito ?? $hito->clave_hito ?? $hito->slug_hito ?? 'Hito' }}
                                                </p>
                                                <p class="text-xs text-slate-500">
                                                    {{ $hito->fecha_inicio ?? '-' }}
                                                    @if(!empty($hito->fecha_fin))
                                                        al {{ $hito->fecha_fin }}
                                                    @endif
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="mt-2 text-xs text-slate-500">Este apoyo no tiene hitos configurados.</p>
                            @endif
                        </aside>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                <h3 class="text-lg font-extrabold text-slate-800">Documentos necesarios</h3>
                <p class="text-xs text-slate-500 mt-1">Estos documentos te serán solicitados en la ventana de carga formal.</p>

                @if(isset($requisitos) && $requisitos->count())
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($requisitos as $req)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ $req->nombre_documento }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-[11px] text-slate-500">Tipo permitido</span>
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 uppercase">{{ $req->tipo_archivo_permitido ?? 'any' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-500">No hay documentos obligatorios para este apoyo.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5" id="comentarios-publicos">
                <h3 class="text-lg font-extrabold text-slate-800">Comentarios publicos</h3>
                <p class="text-xs text-slate-500 mt-1">Espacio de preguntas y respuestas para este apoyo.</p>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-2 flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-800" x-text="myInitials"></div>
                        <p class="text-xs font-semibold text-slate-600">Publicar comentario</p>
                    </div>
                    <textarea x-model="newComment" class="w-full rounded-lg border-slate-300 text-sm" rows="3" maxlength="1200" placeholder="Escribe tu comentario..."></textarea>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs text-slate-500" x-text="`${newComment.length}/1200`"></span>
                        <button type="button" @click="submitComment()" :disabled="loading || !newComment.trim()" class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">Comentar</button>
                    </div>
                </div>

                <template x-if="comments.length === 0">
                    <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Aun no hay comentarios. Se la primera persona en comentar.</div>
                </template>

                <div class="mt-4 space-y-3">
                    <template x-for="comment in comments" :key="comment.id_comentario">
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-slate-800" x-text="comment.autor_nombre"></p>
                                        <template x-if="comment.autor_verificado"><span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-bold text-blue-700">Verificado</span></template>
                                    </div>
                                    <p class="text-xs text-slate-500" x-text="formatDate(comment.fecha_creacion)"></p>
                                </div>
                                <template x-if="comment.can_manage">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-800" @click="startEdit(comment)">Editar</button>
                                        <button type="button" class="text-xs font-semibold text-red-500 hover:text-red-700" @click="deleteComment(comment.id_comentario)">Eliminar</button>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-2 text-sm text-slate-700" x-show="editingId !== comment.id_comentario" x-text="comment.contenido"></div>
                            <div class="mt-2" x-show="editingId === comment.id_comentario">
                                <textarea x-model="editingText" class="w-full rounded-lg border-slate-300 text-sm" rows="3" maxlength="1200"></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white" @click="saveEdit(comment.id_comentario)">Guardar</button>
                                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700" @click="cancelEdit()">Cancelar</button>
                                </div>
                            </div>

                            <div class="mt-2 flex items-center gap-4 text-xs">
                                <button type="button" @click="toggleLike(comment.id_comentario)" class="font-semibold" :class="comment.liked_by_me ? 'text-blue-700' : 'text-slate-500 hover:text-slate-800'">Me gusta (<span x-text="comment.likes_count"></span>)</button>
                                <button type="button" class="font-semibold text-slate-500 hover:text-slate-800" @click="toggleReply(comment.id_comentario)">Responder</button>
                                <template x-if="comment.editado"><span class="text-slate-400">Editado</span></template>
                            </div>

                            <div class="mt-2" x-show="replyingTo === comment.id_comentario">
                                <textarea x-model="replyText" class="w-full rounded-lg border-slate-300 text-sm" rows="2" maxlength="1200" placeholder="Escribe una respuesta..."></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white" @click="submitComment(comment.id_comentario)">Publicar respuesta</button>
                                    <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700" @click="cancelReply()">Cancelar</button>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2 border-l-2 border-slate-300 pl-3" x-show="comment.replies && comment.replies.length">
                                <template x-for="reply in comment.replies" :key="reply.id_comentario">
                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-xs font-semibold text-slate-800" x-text="reply.autor_nombre"></p>
                                                    <template x-if="reply.autor_verificado"><span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">Verificado</span></template>
                                                </div>
                                                <p class="text-[11px] text-slate-500" x-text="formatDate(reply.fecha_creacion)"></p>
                                            </div>
                                            <template x-if="reply.can_manage">
                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="text-[11px] font-semibold text-slate-500" @click="startEdit(reply)">Editar</button>
                                                    <button type="button" class="text-[11px] font-semibold text-red-600" @click="deleteComment(reply.id_comentario)">Eliminar</button>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="mt-1 text-xs text-slate-700" x-show="editingId !== reply.id_comentario" x-text="reply.contenido"></div>
                                        <div class="mt-1" x-show="editingId === reply.id_comentario">
                                            <textarea x-model="editingText" class="w-full rounded-lg border-slate-300 text-xs" rows="2" maxlength="1200"></textarea>
                                            <div class="mt-2 flex gap-2">
                                                <button type="button" class="rounded bg-blue-700 px-2 py-1 text-white text-[10px] font-semibold" @click="saveEdit(reply.id_comentario)">Guardar</button>
                                                <button type="button" class="rounded border border-slate-300 px-2 py-1 text-[10px] font-semibold" @click="cancelEdit()">Cancelar</button>
                                            </div>
                                        </div>

                                        <div class="mt-1 flex items-center gap-3 text-[10px]">
                                            <button type="button" @click="toggleLike(reply.id_comentario)" class="font-semibold" :class="reply.liked_by_me ? 'text-blue-700' : 'text-slate-500 hover:text-slate-800'">Me gusta (<span x-text="reply.likes_count"></span>)</button>
                                            <template x-if="reply.editado"><span class="text-slate-400">Editado</span></template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </article>
                    </template>
                </div>
            </section>
        </div>
    </div>

    <script>
        function apoyoCommentsApp() {
            const bootstrap = window.apoyoCommentsBootstrap;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            return {
                comments: bootstrap.comments || [],
                newComment: '',
                replyText: '',
                replyingTo: null,
                editingId: null,
                editingText: '',
                loading: false,
                myInitials: 'TU',

                init() {
                    this.myInitials = 'YO';
                },

                formatDate(dateValue) {
                    if (!dateValue) return '-';
                    const date = new Date(dateValue);
                    return date.toLocaleString('es-MX', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                toggleReply(commentId) {
                    if (this.replyingTo === commentId) {
                        this.cancelReply();
                        return;
                    }
                    this.replyingTo = commentId;
                    this.replyText = '';
                },

                cancelReply() {
                    this.replyingTo = null;
                    this.replyText = '';
                },

                startEdit(comment) {
                    this.editingId = comment.id_comentario;
                    this.editingText = comment.contenido;
                },

                cancelEdit() {
                    this.editingId = null;
                    this.editingText = '';
                },

                async submitComment(parentId = null) {
                    const text = parentId ? this.replyText.trim() : this.newComment.trim();
                    if (!text || this.loading) return;

                    this.loading = true;

                    try {
                        const response = await fetch(bootstrap.endpoints.store, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ contenido: text, parent_id: parentId })
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No se pudo publicar el comentario.');
                        }

                        this.comments = data.comments || [];
                        if (parentId) this.cancelReply(); else this.newComment = '';
                    } catch (error) {
                        alert(error.message || 'Error al publicar comentario.');
                    } finally {
                        this.loading = false;
                    }
                },

                async saveEdit(commentId) {
                    const text = this.editingText.trim();
                    if (!text || this.loading) return;

                    this.loading = true;

                    try {
                        const response = await fetch(`${bootstrap.endpoints.updateBase}/${commentId}`, {
                            method: 'PUT',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ contenido: text })
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No se pudo editar el comentario.');
                        }

                        this.comments = data.comments || [];
                        this.cancelEdit();
                    } catch (error) {
                        alert(error.message || 'Error al editar comentario.');
                    } finally {
                        this.loading = false;
                    }
                },

                async deleteComment(commentId) {
                    if (!confirm('Esta accion eliminara el comentario y sus respuestas.')) return;
                    if (this.loading) return;

                    this.loading = true;

                    try {
                        const response = await fetch(`${bootstrap.endpoints.updateBase}/${commentId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf
                            }
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No se pudo eliminar el comentario.');
                        }

                        this.comments = data.comments || [];
                        if (this.editingId === commentId) this.cancelEdit();
                    } catch (error) {
                        alert(error.message || 'Error al eliminar comentario.');
                    } finally {
                        this.loading = false;
                    }
                },

                async toggleLike(commentId) {
                    if (this.loading) return;

                    try {
                        const response = await fetch(`${bootstrap.endpoints.likeBase}/${commentId}/like`, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf
                            }
                        });

                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'No se pudo actualizar el like.');
                        }

                        this.comments = data.comments || [];
                    } catch (error) {
                        alert(error.message || 'Error al reaccionar.');
                    }
                }
            };
        }
    </script>
</x-app-layout>
