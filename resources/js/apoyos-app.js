function apoyosApp(apoyosData, userRole = 'beneficiario', canEdit = false) {
    return {
        apoyos: apoyosData || [],
        userRole,
        canEdit,
        selectedApoyo: null,
        commentLoading: false,
        comments: [],
        newComment: '',
        replyText: '',
        replyingTo: null,
        commentEditingId: null,
        commentEditingText: '',

        init() {
            const preselected = Number(window.apoyoCommentSelected || 0);
            if (preselected) {
                const apoyo = this.apoyos.find(a => Number(a.id_apoyo) === preselected);
                if (apoyo) {
                    this.selectApoyo(apoyo);
                    return;
                }
            }

            if (this.apoyos.length) {
                this.selectApoyo(this.apoyos[0]);
            }
        },

        esBeneficiario() {
            return this.userRole === 'beneficiario';
        },

        formatMoney(value) {
            const amount = Number(value || 0);
            return '$' + amount.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatFechaComentario(fecha) {
            if (!fecha) return '-';
            const date = new Date(fecha);
            return date.toLocaleString('es-MX', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatMilestoneRange(hito) {
            const start = hito?.fecha_inicio || null;
            const end = hito?.fecha_fin || null;
            if (start && end) {
                return `${start} al ${end}`;
            }
            return start || end || 'Sin fecha';
        },

        timelineDotClass(hito) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const start = hito?.fecha_inicio ? new Date(hito.fecha_inicio) : null;
            const end = hito?.fecha_fin ? new Date(hito.fecha_fin) : null;
            if (start) start.setHours(0, 0, 0, 0);
            if (end) end.setHours(0, 0, 0, 0);

            if (end && end < today) return 'bg-slate-400';
            if (start && start > today) return 'bg-amber-400';
            return 'bg-emerald-500';
        },

        formalSolicitudUrl(apoyo) {
            if (!apoyo || !apoyo.id_apoyo) return '#';
            return `/apoyos/${apoyo.id_apoyo}/solicitud`;
        },

        async selectApoyo(apoyo) {
            if (!apoyo || !apoyo.id_apoyo) return;
            this.selectedApoyo = apoyo;
            this.cancelReply();
            this.cancelCommentEdit();
            this.newComment = '';
            await this.loadComments(apoyo.id_apoyo);
        },

        async loadComments(apoyoId) {
            this.commentLoading = true;
            this.comments = [];
            try {
                const response = await fetch(`/apoyos/${apoyoId}/comentarios?json=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No fue posible cargar comentarios.');
                }
                this.comments = data.comments || [];
            } catch (error) {
                alert(error.message || 'Error al cargar comentarios.');
            } finally {
                this.commentLoading = false;
            }
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

        startCommentEdit(comment) {
            this.commentEditingId = comment.id_comentario;
            this.commentEditingText = comment.contenido || '';
        },

        cancelCommentEdit() {
            this.commentEditingId = null;
            this.commentEditingText = '';
        },

        async submitComment(parentId = null) {
            if (!this.selectedApoyo || this.commentLoading) return;

            const text = parentId ? (this.replyText || '').trim() : (this.newComment || '').trim();
            if (!text) return;

            this.commentLoading = true;
            try {
                const response = await fetch(`/apoyos/${this.selectedApoyo.id_apoyo}/comentarios`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        Accept: 'application/json'
                    },
                    body: JSON.stringify({ contenido: text, parent_id: parentId })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo publicar el comentario.');
                }
                this.comments = data.comments || [];
                if (parentId) {
                    this.cancelReply();
                } else {
                    this.newComment = '';
                }
            } catch (error) {
                alert(error.message || 'Error al publicar comentario.');
            } finally {
                this.commentLoading = false;
            }
        },

        async saveCommentEdit(commentId) {
            if (!this.selectedApoyo || this.commentLoading) return;
            const text = (this.commentEditingText || '').trim();
            if (!text) return;

            this.commentLoading = true;
            try {
                const response = await fetch(`/apoyos/${this.selectedApoyo.id_apoyo}/comentarios/${commentId}`, {
                    method: 'PUT',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        Accept: 'application/json'
                    },
                    body: JSON.stringify({ contenido: text })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo editar el comentario.');
                }
                this.comments = data.comments || [];
                this.cancelCommentEdit();
            } catch (error) {
                alert(error.message || 'Error al editar comentario.');
            } finally {
                this.commentLoading = false;
            }
        },

        async deleteComment(commentId) {
            if (!this.selectedApoyo || this.commentLoading) return;
            if (!confirm('Esta acción eliminará el comentario y sus respuestas.')) return;

            this.commentLoading = true;
            try {
                const response = await fetch(`/apoyos/${this.selectedApoyo.id_apoyo}/comentarios/${commentId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo eliminar el comentario.');
                }
                this.comments = data.comments || [];
                if (this.commentEditingId === commentId) this.cancelCommentEdit();
            } catch (error) {
                alert(error.message || 'Error al eliminar comentario.');
            } finally {
                this.commentLoading = false;
            }
        },

        async toggleCommentLike(commentId) {
            if (!this.selectedApoyo || this.commentLoading) return;
            try {
                const response = await fetch(`/apoyos/${this.selectedApoyo.id_apoyo}/comentarios/${commentId}/like`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json'
                    }
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo actualizar la reacción.');
                }
                this.comments = data.comments || [];
            } catch (error) {
                alert(error.message || 'Error al reaccionar.');
            }
        },

        confirmarEliminacion(apoyo) {
            if (!apoyo || !apoyo.id_apoyo) return;
            if (!confirm('¿Seguro que deseas eliminar este apoyo?')) return;

            fetch(`/apoyos/${apoyo.id_apoyo}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.success) {
                        throw new Error(data.message || 'No se pudo eliminar el apoyo.');
                    }
                    this.apoyos = this.apoyos.filter(a => Number(a.id_apoyo) !== Number(apoyo.id_apoyo));
                    if (this.selectedApoyo && Number(this.selectedApoyo.id_apoyo) === Number(apoyo.id_apoyo)) {
                        this.selectedApoyo = this.apoyos.length ? this.apoyos[0] : null;
                        if (this.selectedApoyo) {
                            this.loadComments(this.selectedApoyo.id_apoyo);
                        } else {
                            this.comments = [];
                        }
                    }
                })
                .catch(error => {
                    alert(error.message || 'Error al eliminar apoyo.');
                });
        }
    };
}

window.apoyosApp = apoyosApp;

