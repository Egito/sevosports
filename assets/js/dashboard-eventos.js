/**
 * JavaScript para o Dashboard de Eventos
 * Controla filtros, modais e interações
 */

jQuery(document).ready(function($) {
    'use strict';

    // Verifica se há mensagem de toaster armazenada após reload
    const storedMessage = sessionStorage.getItem('sevo_toaster_message');
    if (storedMessage) {
        try {
            const messageData = JSON.parse(storedMessage);
            // Remove a mensagem do storage
            sessionStorage.removeItem('sevo_toaster_message');
            // Mostra o toaster após um pequeno delay para garantir que a página carregou
            setTimeout(function() {
                if (messageData.type === 'success') {
                    SevoToaster.showSuccess(messageData.message);
                } else if (messageData.type === 'error') {
                    SevoToaster.showError(messageData.message);
                } else if (messageData.type === 'info') {
                    SevoToaster.showInfo(messageData.message);
                }
            }, 500);
        } catch (e) {
            // Remove mensagem corrompida
            sessionStorage.removeItem('sevo_toaster_message');
        }
    }

    // Objeto principal do Dashboard de Eventos
    const SevoEventosDashboard = {
        
        init: function() {
            this.bindEvents();
            this.loadFilterOptions();
        },

        // Vincula eventos
        bindEvents: function() {
            // Clique nos cards para abrir o modal
            $(document).on('click', '.sevo-card.evento-card', function(e) {
                // Previne a abertura do modal se clicou em um botão
                if ($(e.target).closest('.sevo-card-actions').length > 0) {
                    return;
                }
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.openEventModal(eventId);
            });

            // Botão de visualizar evento
            $(document).on('click', '.sevo-view-evento', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.openEventModal(eventId);
            });

            // Botão de editar evento
            $(document).on('click', '.sevo-edit-evento', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.editEvent(eventId);
            });

            // Botão de criar novo evento
            $(document).on('click', '#sevo-create-evento-button', function(e) {
                e.preventDefault();
                SevoEventosDashboard.editEvent(0); // 0 para novo evento
            });

            // Fechar modal
            $(document).on('click', '#sevo-evento-view-modal-close, .sevo-modal-backdrop', function(e) {
                if (e.target === e.currentTarget) {
                    SevoEventosDashboard.closeEventModal();
                }
            });

            // Navegação por teclado
            $(document).on('keydown', function(e) {
                if ($('#sevo-event-modal').is(':visible')) {
                    if (e.key === 'Escape') {
                        SevoEventosDashboard.closeEventModal();
                    }
                }
                if ($('#sevo-evento-form-modal-container').is(':visible')) {
                    if (e.key === 'Escape') {
                        SevoEventosDashboard.closeEventFormModal();
                    }
                }
            });

            // Botão de inscrição
            $(document).on('click', '.sevo-inscribe-evento', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.inscribeToEvent(eventId);
            });

            // Botão de cancelar inscrição
            $(document).on('click', '.sevo-cancel-inscricao', function(e) {
                e.preventDefault();
                const inscricaoId = $(e.currentTarget).data('inscricao-id');
                SevoEventosDashboard.cancelInscricao(inscricaoId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', function(e) {
                e.preventDefault();
                SevoEventosDashboard.submitEventForm(e.target);
            });

            // Event listeners para filtros
            $(document).on('change', '.sevo-filter-select', function() {
                SevoEventosDashboard.applyFilters();
            });

            $(document).on('click', '.sevo-clear-filters-btn', function(e) {
                e.preventDefault();
                SevoEventosDashboard.clearFilters();
            });

            // Event listener para fechar modal de formulário
            $(document).on('click', '#sevo-evento-form-modal-close', function(e) {
                e.preventDefault();
                SevoEventosDashboard.closeEventFormModal();
            });

            // Event listener para cancelar edição
            $(document).on('click', '#sevo-cancel-evento-button', function() {
                SevoEventosDashboard.closeEventFormModal();
            });

            // Event listener para fechar modal clicando no backdrop
            $(document).on('click', '#sevo-evento-form-modal-container', function(e) {
                if (e.target === e.currentTarget) {
                    SevoEventosDashboard.closeEventFormModal();
                }
            });

            // Botão de editar evento no modal
            $(document).on('click', '.sevo-edit-evento-modal, .sevo-modal-button[data-event-id]', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.editEvent(eventId);
            });
        },

        // Carrega opções dos filtros
        loadFilterOptions: function() {
            // Carrega organizações
            this.loadFilterOption('organizacao', '#filter-organizacao');
            // Carrega tipos de evento
            this.loadFilterOption('tipo_evento', '#filter-tipo');
        },

        // Carrega opções de um filtro específico
        loadFilterOption: function(filterType, selectElement) {
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_load_filter_options',
                    filter_type: filterType,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $(selectElement);
                        const currentValue = $select.val();
                        
                        // Limpa opções existentes (exceto a primeira)
                        $select.find('option:not(:first)').remove();
                        
                        // Adiciona novas opções
                        response.data.options.forEach(function(option) {
                            $select.append(`<option value="${option.value}">${option.label}</option>`);
                        });
                        
                        // Restaura valor selecionado se ainda existir
                        if (currentValue) {
                            $select.val(currentValue);
                        }
                    }
                },
                error: function() {
                    console.error('Erro ao carregar opções do filtro:', filterType);
                }
            });
        },

        // Aplica filtros
        applyFilters: function() {
            const filters = {
                organizacao: $('#filter-organizacao').val(),
                tipo_evento: $('#filter-tipo').val(),
                status: $('#filter-status').val()
            };

            $('#sevo-eventos-loading').show();
            $('#eventos-container').hide();

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_filter_eventos_dashboard',
                    organizacao: filters.organizacao,
                    tipo_evento: filters.tipo_evento,
                    status: filters.status,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    $('#sevo-eventos-loading').hide();
                    $('#eventos-container').show();
                    
                    if (response.success) {
                        $('#eventos-container').html(response.data.html);
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao aplicar filtros.');
                    }
                },
                error: function() {
                    $('#sevo-eventos-loading').hide();
                    $('#eventos-container').show();
                    SevoToaster.showError('Erro ao aplicar filtros.');
                }
            });
        },

        // Limpa filtros
        clearFilters: function() {
            $('.sevo-filter-select').val('');
            this.applyFilters();
        },

        // Abre modal do evento
        openEventModal: function(eventId) {
            const $modal = $('#sevo-event-modal');
            const $loading = $modal.find('.sevo-modal-loading');
            const $content = $modal.find('.sevo-modal-content');

            $modal.show();
            $loading.show();
            $content.empty();

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_view',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    $loading.hide();
                    if (response.success) {
                        $content.html(response.data.html);
                    } else {
                        $content.html('<p>Erro ao carregar evento.</p>');
                        SevoToaster.showError(response.data || 'Erro ao carregar evento.');
                    }
                },
                error: function() {
                    $loading.hide();
                    $content.html('<p>Erro ao carregar evento.</p>');
                    SevoToaster.showError('Erro ao carregar evento.');
                }
            });
        },

        // Fecha modal do evento
        closeEventModal: function() {
            $('#sevo-event-modal').hide();
        },

        // Edita evento
        editEvent: function(eventId) {
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_form',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sevo-evento-form-modal-container').html(response.data.html).show();
                        // Fecha o modal de visualização se estiver aberto
                        SevoEventosDashboard.closeEventModal();
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao carregar formulário.');
                    }
                },
                error: function() {
                    SevoToaster.showError('Erro ao carregar formulário.');
                }
            });
        },

        // Fecha modal de formulário
        closeEventFormModal: function() {
            $('#sevo-evento-form-modal-container').hide().empty();
        },

        // Submete formulário de evento
        submitEventForm: function(form) {
            const $form = $(form);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Salvando...');

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=sevo_save_evento&nonce=' + sevoEventosDashboard.nonce,
                success: function(response) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        SevoEventosDashboard.closeEventFormModal();
                        
                        // Armazena mensagem para mostrar após reload
                        sessionStorage.setItem('sevo_toaster_message', JSON.stringify({
                            type: 'success',
                            message: response.data.message
                        }));
                        
                        // Recarrega a página para mostrar as mudanças
                        location.reload();
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao salvar evento.');
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                    SevoToaster.showError('Erro ao salvar evento.');
                }
            });
        },

        // Inscreve em evento
        inscribeToEvent: function(eventId) {
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_inscribe_evento',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoToaster.showSuccess(response.data.message);
                        // Recarrega o modal para mostrar o novo status
                        SevoEventosDashboard.openEventModal(eventId);
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao se inscrever.');
                    }
                },
                error: function() {
                    SevoToaster.showError('Erro ao se inscrever.');
                }
            });
        },

        // Cancela inscrição
        cancelInscricao: function(inscricaoId) {
            if (!confirm('Tem certeza que deseja cancelar sua inscrição?')) {
                return;
            }

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_cancel_inscricao',
                    inscricao_id: inscricaoId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoToaster.showSuccess(response.data.message);
                        // Recarrega a página para mostrar as mudanças
                        location.reload();
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao cancelar inscrição.');
                    }
                },
                error: function() {
                    SevoToaster.showError('Erro ao cancelar inscrição.');
                }
            });
        }
    };

    // Torna o objeto disponível globalmente
    window.SevoEventosDashboard = SevoEventosDashboard;

    // Inicializa quando o documento estiver pronto
    SevoEventosDashboard.init();
});