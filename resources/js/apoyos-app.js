/**
 * Aplicación compartida para gestión de apoyos
 * Usada tanto en /apoyos como en /registrar-solicitud
 */
function apoyosApp(apoyosData, userRole = 'beneficiario', canEdit = false) {
    return {
        apoyos: apoyosData,
        apoyoActual: null,
        mensajeUsuario: '',
        modalAbierto: false,
        modalEditarAbierto: false,
        confirmarEliminacionAbierto: false,
        confirmarSolicitudAbierta: false,
        apoyoAEliminar: null,
        userRole: userRole,
        canEdit: canEdit,
        formEditandose: false,
        chatInput: '',
        chatMessages: [
            { id: 1, author: 'Soporte SIGO', body: 'Bienvenido al chat de soporte. Aqui puedes dejar dudas sobre tu apoyo.', time: '09:00' },
            { id: 2, author: 'Soporte SIGO', body: 'Su documento esta en revision.', time: '09:02' }
        ],

        // ────────────────────────────────────────────
        // MODALES
        // ────────────────────────────────────────────
        abrirModal(apoyo) {
            this.apoyoActual = apoyo;
            this.modalAbierto = true;
            document.body.style.overflow = 'hidden';
        },

        cerrarModal() {
            this.modalAbierto = false;
            this.modalEditarAbierto = false;
            this.confirmarEliminacionAbierto = false;
            this.confirmarSolicitudAbierta = false;
            document.body.style.overflow = '';
            this.apoyoAEliminar = null;
            this.mensajeUsuario = '';
        },

        // ────────────────────────────────────────────
        // FUNCIONES DE EDICIÓN
        // ────────────────────────────────────────────
        abrirEditar(apoyo) {
            this.apoyoActual = { ...apoyo };
            this.modalEditarAbierto = true;
            document.body.style.overflow = 'hidden';
        },

        cerrarEditar() {
            this.modalEditarAbierto = false;
            document.body.style.overflow = '';
        },

        guardarEdicion() {
            this.formEditandose = true;
            const formElement = document.getElementById('formularioEditarApoyo');
            if (!formElement) {
                console.error('Formulario de edición no encontrado');
                this.formEditandose = false;
                return;
            }

            const formData = new FormData(formElement);
            const apoyoId = this.apoyoActual.id_apoyo;

            fetch(`/apoyos/${apoyoId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.mostrarToast('Apoyo actualizado correctamente', 'success');
                        this.cerrarEditar();
                        // Actualizar el apoyo en la lista
                        const index = this.apoyos.findIndex(a => a.id_apoyo === apoyoId);
                        if (index !== -1) {
                            this.apoyos[index] = data.apoyo || this.apoyoActual;
                        }
                    } else {
                        this.mostrarToast(data.message || 'Error al actualizar', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.mostrarToast('Error al guardar los cambios', 'error');
                })
                .finally(() => {
                    this.formEditandose = false;
                });
        },

        // ────────────────────────────────────────────
        // FUNCIONES DE ELIMINACIÓN
        // ────────────────────────────────────────────
        abrirEliminar(apoyo) {
            this.apoyoAEliminar = apoyo;
            this.confirmarEliminacionAbierto = true;
        },

        confirmarEliminacion() {
            if (!this.apoyoAEliminar) return;

            const apoyoId = this.apoyoAEliminar.id_apoyo;

            fetch(`/apoyos/${apoyoId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.mostrarToast('Apoyo eliminado correctamente', 'success');
                        // Remover de la lista
                        this.apoyos = this.apoyos.filter(a => a.id_apoyo !== apoyoId);
                        this.cerrarModal();
                    } else {
                        this.mostrarToast(data.message || 'Error al eliminar', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.mostrarToast('Error al eliminar el apoyo', 'error');
                });
        },

        abrirConfirmacionSolicitud() {
            this.confirmarSolicitudAbierta = true;
        },

        enviarSolicitud() {
            const form = document.getElementById('formSolicitud');
            if (!form) {
                this.mostrarToast('No se encontro el formulario de solicitud', 'error');
                return;
            }

            this.confirmarSolicitudAbierta = false;

            if (typeof grecaptcha === 'undefined') {
                form.submit();
                return;
            }

            grecaptcha.ready(() => {
                grecaptcha.execute(window.recaptchaSiteKey || '', { action: 'solicitud' })
                    .then(token => {
                        const tokenInput = document.getElementById('g-recaptcha-response-solicitud');
                        if (tokenInput) {
                            tokenInput.value = token;
                        }
                        form.submit();
                    })
                    .catch(() => {
                        form.submit();
                    });
            });
        },

        enviarMensajeChat() {
            const texto = (this.chatInput || '').trim();
            if (!texto) {
                return;
            }

            const ahora = new Date();
            const hh = String(ahora.getHours()).padStart(2, '0');
            const mm = String(ahora.getMinutes()).padStart(2, '0');

            this.chatMessages.push({
                id: Date.now(),
                author: 'Tu',
                body: texto,
                time: `${hh}:${mm}`
            });

            this.chatInput = '';
        },

        // ────────────────────────────────────────────
        // UTILIDADES
        // ────────────────────────────────────────────
        formatFecha(fecha) {
            if (!fecha) return '-';
            const d = new Date(fecha);
            return d.toLocaleDateString('es-MX', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        tipoArchivoLabel(req) {
            const tipos = {
                pdf: 'PDF',
                image: 'IMG',
                word: 'DOC',
                excel: 'EXCEL',
                zip: 'ZIP',
                any: 'ANY'
            };
            return tipos[req.tipo_archivo_permitido] || 'ANY';
        },

        getAcceptByTipo(req) {
            const accepts = {
                pdf: 'application/pdf',
                image: 'image/jpeg,image/png,image/webp',
                word: '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                excel: '.xls,.xlsx,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                zip: '.zip,.rar,.7z,application/x-zip-compressed,application/x-rar-compressed,application/x-7z-compressed',
                any: '*'
            };
            return accepts[req.tipo_archivo_permitido] || '*';
        },

        mostrarToast(mensaje, tipo = 'info') {
            const toast = document.getElementById('toast') || this.crearToast();
            toast.textContent = mensaje;
            toast.className = `show ${tipo}`;

            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        },

        crearToast() {
            const toast = document.createElement('div');
            toast.id = 'toast';
            Object.assign(toast.style, {
                position: 'fixed',
                bottom: '5rem',
                right: '1.5rem',
                zIndex: '100',
                background: '#1e293b',
                color: '#fff',
                borderRadius: '12px',
                padding: '.75rem 1.25rem',
                fontSize: '.85rem',
                fontWeight: '600',
                boxShadow: '0 8px 24px rgba(0,0,0,.2)',
                transform: 'translateY(20px)',
                opacity: '0',
                transition: 'transform .3s, opacity .3s',
                pointerEvents: 'none',
                maxWidth: '320px'
            });
            document.body.appendChild(toast);
            return toast;
        },

        esBeneficiario() {
            return this.userRole === 'beneficiario';
        },

        esAdministrativo() {
            return this.userRole === 'administrativo' && this.canEdit;
        },

        esDirectivo() {
            return this.userRole === 'directivo' && this.canEdit;
        }
    };
}

// Hacer la función global para Alpine.js
window.apoyosApp = apoyosApp;
