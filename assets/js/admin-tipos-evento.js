/**
 * JavaScript para administração de tipos de evento
 * Gerencia CRUD via AJAX usando tabelas customizadas
 */

(function($) {
    'use strict';
    
    var SevoTipoAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadTiposEvento();
            this.loadOrganizacoes();
        },
        
        bindEvents: function() {
            // Botão adicionar novo tipo
            $('#sevo-add-tipo-btn').on('click', this.showCreateModal);
            
            // Fechar modal
            $('.sevo-modal-close, #sevo-tipo-cancel').on('click', this.hideModal);
            
            // Salvar tipo
            $('#sevo-tipo-save').on('click', this.saveTipoEvento);
            
            // Editar tipo (delegated event)
            $(document).on('click', '.sevo-edit-tipo', this.showEditModal);
            
            // Excluir tipo (delegated event)
            $(document).on('click', '.sevo-delete-tipo', this.deleteTipoEvento);
            
            // Paginação (delegated event)
            $(document).on('click', '.sevo-page-btn', this.changePage);
            
            // Fechar modal ao clicar fora
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('sevo-modal')) {
                    SevoTipoAdmin.hideModal();
                }
            });
        },
        
        showCreateModal: function() {
            $('#sevo-tipo-modal-title').text('Novo Tipo de Evento');
            $('#sevo-tipo-form')[0].reset();
            $('#tipo-id').val('');
            $('#tipo-vagas-max').val('50'); // Valor padrão
            $('#sevo-tipo-modal').show();
        },
        
        showEditModal: function() {
            var tipoId = $(this).data('id');
            
            $.ajax({
                url: sevoTipoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_tipo_evento',
                    id: tipoId,
                    nonce: sevoTipoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var tipo = response.data;
                        
                        $('#sevo-tipo-modal-title').text('Editar Tipo de Evento');
                        $('#tipo-id').val(tipo.id);
                        $('#tipo-nome').val(tipo.nome);
                        $('#tipo-descricao').val(tipo.descricao);
                        $('#tipo-organizacao-id').val(tipo.organizacao_id);
                        $('#tipo-autor-id').val(tipo.autor_id);
                        $('#tipo-vagas-max').val(tipo.vagas_max);
                        $('#tipo-participacao').val(tipo.tipo_participacao);
                        $('#tipo-status').val(tipo.status);
                        
                        $('#sevo-tipo-modal').show();
                    } else {
                        SevoTipoAdmin.showNotice(response.data || sevoTipoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoTipoAdmin.showNotice(sevoTipoAdmin.strings.error, 'error');
                }
            });
        },
        
        hideModal: function() {
            $('#sevo-tipo-modal').hide();
        },
        
        saveTipoEvento: function() {
            var formData = $('#sevo-tipo-form').serialize();
            var tipoId = $('#tipo-id').val();
            var action = tipoId ? 'sevo_update_tipo_evento' : 'sevo_create_tipo_evento';
            
            // Validação básica
            if (!$('#tipo-nome').val().trim()) {
                SevoTipoAdmin.showNotice('Nome é obrigatório.', 'error');
                return;
            }
            
            if (!$('#tipo-organizacao-id').val()) {
                SevoTipoAdmin.showNotice('Organização é obrigatória.', 'error');
                return;
            }
            
            if (!$('#tipo-autor-id').val()) {
                SevoTipoAdmin.showNotice('Autor é obrigatório.', 'error');
                return;
            }
            
            var vagasMax = parseInt($('#tipo-vagas-max').val());
            if (!vagasMax || vagasMax < 1) {
                SevoTipoAdmin.showNotice('Vagas máximas deve ser um número maior que zero.', 'error');
                return;
            }
            
            $('#sevo-tipo-save').prop('disabled', true).text('Salvando...');
            
            $.ajax({
                url: sevoTipoAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + sevoTipoAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        SevoTipoAdmin.showNotice(response.data.message, 'success');
                        SevoTipoAdmin.hideModal();
                        SevoTipoAdmin.loadTiposEvento();
                    } else {
                        SevoTipoAdmin.showNotice(response.data || sevoTipoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoTipoAdmin.showNotice(sevoTipoAdmin.strings.error, 'error');
                },
                complete: function() {
                    $('#sevo-tipo-save').prop('disabled', false).text('Salvar');
                }
            });
        },
        
        deleteTipoEvento: function() {
            var tipoId = $(this).data('id');
            
            if (!confirm(sevoTipoAdmin.strings.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: sevoTipoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_delete_tipo_evento',
                    id: tipoId,
                    nonce: sevoTipoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoTipoAdmin.showNotice(response.data.message, 'success');
                        SevoTipoAdmin.loadTiposEvento();
                    } else {
                        SevoTipoAdmin.showNotice(response.data || sevoTipoAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoTipoAdmin.showNotice(sevoTipoAdmin.strings.error, 'error');
                }
            });
        },
        
        loadTiposEvento: function(page) {
            page = page || 1;
            console.log('SevoTipoAdmin: Carregando tipos de evento, página:', page);
            
            $.ajax({
                url: sevoTipoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_list_tipos_evento',
                    page: page,
                    per_page: 20,
                    nonce: sevoTipoAdmin.nonce
                },
                success: function(response) {
                    console.log('SevoTipoAdmin: Resposta recebida:', response);
                    if (response.success) {
                        $('#sevo-tipo-list-container').html(response.data.html);
                    } else {
                        SevoTipoAdmin.showNotice(response.data || sevoTipoAdmin.strings.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SevoTipoAdmin: Erro AJAX:', status, error);
                    SevoTipoAdmin.showNotice(sevoTipoAdmin.strings.error, 'error');
                }
            });
        },
        
        loadOrganizacoes: function() {
            $.ajax({
                url: sevoTipoAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_organizacoes_select',
                    nonce: sevoTipoAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#tipo-organizacao-id').html(response.data.html);
                    }
                },
                error: function() {
                    console.log('Erro ao carregar organizações');
                }
            });
        },
        
        changePage: function() {
            var page = $(this).data('page');
            SevoTipoAdmin.loadTiposEvento(page);
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
        console.log('SevoTipoAdmin: Inicializando...');
        SevoTipoAdmin.init();
    });
    
})(jQuery);