/**
 * JavaScript para administração de eventos
 * Gerencia CRUD via AJAX usando tabelas customizadas
 */

(function($) {
    'use strict';
    
    var SevoEventoAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadEventos();
            this.loadTiposEvento();
        },
        
        bindEvents: function() {
            // Botão adicionar novo evento
            $('#sevo-add-evento-btn').on('click', this.showCreateModal);
            
            // Fechar modal
            $('.sevo-modal-close, #sevo-evento-cancel').on('click', this.hideModal);
            
            // Salvar evento
            $('#sevo-evento-save').on('click', this.saveEvento);
            
            // Editar evento (delegated event)
            $(document).on('click', '.sevo-edit-evento', this.showEditModal);
            
            // Excluir evento (delegated event)
            $(document).on('click', '.sevo-delete-evento', this.deleteEvento);
            
            // Paginação (delegated event)
            $(document).on('click', '.sevo-page-btn', this.changePage);
            
            // Fechar modal ao clicar fora
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('sevo-modal')) {
                    SevoEventoAdmin.hideModal();
                }
            });
            
            // Validação de datas em tempo real
            $('#evento-data-inicio-inscricao, #evento-data-fim-inscricao, #evento-data-inicio, #evento-data-fim').on('change', this.validateDates);
        },
        
        showCreateModal: function() {
            $('#sevo-evento-modal-title').text('Novo Evento');
            $('#sevo-evento-form')[0].reset();
            $('#evento-id').val('');
            
            // Definir valores padrão
            var now = new Date();
            var tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
            var nextWeek = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
            
            $('#evento-data-inicio-inscricao').val(SevoEventoAdmin.formatDateTimeLocal(now));
            $('#evento-data-fim-inscricao').val(SevoEventoAdmin.formatDateTimeLocal(tomorrow));
            $('#evento-data-inicio').val(SevoEventoAdmin.formatDateTimeLocal(nextWeek));
            $('#evento-data-fim').val(SevoEventoAdmin.formatDateTimeLocal(new Date(nextWeek.getTime() + 2 * 60 * 60 * 1000)));
            
            $('#sevo-evento-modal').show();
        },
        
        showEditModal: function() {
            var eventoId = $(this).data('id');
            
            $.ajax({
                url: sevoEventoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento',
                    id: eventoId,
                    nonce: sevoEventoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var evento = response.data;
                        
                        $('#sevo-evento-modal-title').text('Editar Evento');
                        $('#evento-id').val(evento.id);
                        $('#evento-titulo').val(evento.titulo);
                        $('#evento-descricao').val(evento.descricao);
                        $('#evento-tipo-id').val(evento.tipo_evento_id);
                        $('#evento-data-inicio-inscricao').val(evento.data_inicio_inscricao);
                        $('#evento-data-fim-inscricao').val(evento.data_fim_inscricao);
                        $('#evento-data-inicio').val(evento.data_inicio);
                        $('#evento-data-fim').val(evento.data_fim);
                        $('#evento-vagas').val(evento.vagas);
                        $('#evento-imagem-url').val(evento.imagem_url || '');
                        $('#evento-status').val(evento.status);
                        
                        $('#sevo-evento-modal').show();
                    } else {
                        SevoEventoAdmin.showNotice(response.data || sevoEventoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoEventoAdmin.showNotice(sevoEventoAdmin.strings.error, 'error');
                }
            });
        },
        
        hideModal: function() {
            $('#sevo-evento-modal').hide();
        },
        
        saveEvento: function() {
            var formData = $('#sevo-evento-form').serialize();
            var eventoId = $('#evento-id').val();
            var action = eventoId ? 'sevo_update_evento' : 'sevo_create_evento';
            
            // Validação básica
            if (!$('#evento-titulo').val().trim()) {
                SevoEventoAdmin.showNotice('Título é obrigatório.', 'error');
                return;
            }
            
            if (!$('#evento-tipo-id').val()) {
                SevoEventoAdmin.showNotice('Tipo de evento é obrigatório.', 'error');
                return;
            }
            
            var vagas = parseInt($('#evento-vagas').val());
            if (!vagas || vagas < 1) {
                SevoEventoAdmin.showNotice('Número de vagas deve ser maior que zero.', 'error');
                return;
            }
            
            // Validação de datas
            if (!SevoEventoAdmin.validateDates()) {
                return;
            }
            
            $('#sevo-evento-save').prop('disabled', true).text('Salvando...');
            
            $.ajax({
                url: sevoEventoAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + sevoEventoAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        SevoEventoAdmin.showNotice(response.data.message, 'success');
                        SevoEventoAdmin.hideModal();
                        SevoEventoAdmin.loadEventos();
                    } else {
                        SevoEventoAdmin.showNotice(response.data || sevoEventoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoEventoAdmin.showNotice(sevoEventoAdmin.strings.error, 'error');
                },
                complete: function() {
                    $('#sevo-evento-save').prop('disabled', false).text('Salvar');
                }
            });
        },
        
        deleteEvento: function() {
            var eventoId = $(this).data('id');
            
            if (!confirm(sevoEventoAdmin.strings.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: sevoEventoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_delete_evento',
                    id: eventoId,
                    nonce: sevoEventoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoEventoAdmin.showNotice(response.data.message, 'success');
                        SevoEventoAdmin.loadEventos();
                    } else {
                        SevoEventoAdmin.showNotice(response.data || sevoEventoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoEventoAdmin.showNotice(sevoEventoAdmin.strings.error, 'error');
                }
            });
        },
        
        loadEventos: function(page) {
            page = page || 1;
            console.log('SevoEventoAdmin: Carregando eventos, página:', page);
            
            $.ajax({
                url: sevoEventoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_list_eventos',
                    page: page,
                    per_page: 20,
                    nonce: sevoEventoAdmin.nonce
                },
                success: function(response) {
                    console.log('SevoEventoAdmin: Resposta recebida:', response);
                    if (response.success) {
                        $('#sevo-evento-list-container').html(response.data.html);
                    } else {
                        SevoEventoAdmin.showNotice(response.data || sevoEventoAdmin.strings.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SevoEventoAdmin: Erro AJAX:', status, error);
                    SevoEventoAdmin.showNotice(sevoEventoAdmin.strings.error, 'error');
                }
            });
        },
        
        loadTiposEvento: function() {
            $.ajax({
                url: sevoEventoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_tipos_evento_select',
                    nonce: sevoEventoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#evento-tipo-id').html(response.data.html);
                    }
                },
                error: function() {
                    console.log('Erro ao carregar tipos de evento');
                }
            });
        },
        
        changePage: function() {
            var page = $(this).data('page');
            SevoEventoAdmin.loadEventos(page);
        },
        
        validateDates: function() {
            var dataInicioInscricao = new Date($('#evento-data-inicio-inscricao').val());
            var dataFimInscricao = new Date($('#evento-data-fim-inscricao').val());
            var dataInicio = new Date($('#evento-data-inicio').val());
            var dataFim = new Date($('#evento-data-fim').val());
            
            // Verificar se todas as datas foram preenchidas
            if (!$('#evento-data-inicio-inscricao').val() || 
                !$('#evento-data-fim-inscricao').val() || 
                !$('#evento-data-inicio').val() || 
                !$('#evento-data-fim').val()) {
                return true; // Não validar se alguma data estiver vazia
            }
            
            // Validações
            if (dataFimInscricao <= dataInicioInscricao) {
                SevoEventoAdmin.showNotice('A data de fim das inscrições deve ser posterior ao início das inscrições.', 'error');
                return false;
            }
            
            if (dataInicio <= dataFimInscricao) {
                SevoEventoAdmin.showNotice('A data de início do evento deve ser posterior ao fim das inscrições.', 'error');
                return false;
            }
            
            if (dataFim <= dataInicio) {
                SevoEventoAdmin.showNotice('A data de fim do evento deve ser posterior ao início do evento.', 'error');
                return false;
            }
            
            return true;
        },
        
        formatDateTimeLocal: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            
            return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            // Remove notices existentes
            $('.sevo-notice').remove();
            
            var noticeClass = 'notice notice-' + type;
            if (type === 'error') {
                noticeClass += ' notice-error';
            } else if (type === 'success') {
                noticeClass += ' notice-success';
            }
            
            var notice = $('<div class="' + noticeClass + ' sevo-notice is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto-remove após 5 segundos
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        console.log('SevoEventoAdmin: Inicializando...');
        SevoEventoAdmin.init();
    });
    
})(jQuery);