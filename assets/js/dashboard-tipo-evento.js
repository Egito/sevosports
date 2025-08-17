// Objeto global para o dashboard de tipo de evento
window.SevoTipoEventoDashboard = {
    viewTipoEvento: function(tipoEventoId) {
        openViewModal(tipoEventoId);
    },
    editTipoEvento: function(tipoEventoId) {
        openFormModal(tipoEventoId);
    }
};

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

    const dashboard = $('.sevo-dashboard-wrapper');
    if (dashboard.length === 0) return;

    const container = $('#sevo-tipo-eventos-container');
    const modal = $('#sevo-tipo-evento-modal');
    const modalContent = $('#sevo-modal-content');
    const loadingIndicator = $('#sevo-loading-indicator');
    
    let page = 1;
    let loading = false;
    let hasMore = true;

    function loadTiposEvento(reset = false) {
        console.log('loadTiposEvento chamada:', { reset, loading, hasMore });
        if (loading || (!hasMore && !reset)) return;
        
        loading = true;
        loadingIndicator.show();

        if (reset) {
            page = 1;
            hasMore = true;
            container.empty();
        }

        console.log('Fazendo requisição AJAX:', {
            url: sevoTipoEventoDashboard.ajax_url,
            action: 'sevo_load_more_tipos_evento',
            nonce: sevoTipoEventoDashboard.nonce,
            page: page
        });

        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_load_more_tipos_evento',
            nonce: sevoTipoEventoDashboard.nonce,
            page: page,
        }).done(function(response) {
            console.log('Resposta AJAX recebida:', response);
            if (response.success) {
                if (response.data.items && response.data.items.trim() !== '') {
                    container.append(response.data.items);
                    hasMore = response.data.hasMore;
                    page++;
                    console.log('Items carregados com sucesso');
                } else {
                    hasMore = false;
                    if (reset && page === 1) {
                        // Primeira página sem resultados - mostra estado vazio mas mantém a estrutura
                        container.html('<div class="sevo-empty-state"><div class="sevo-empty-icon"><i class="dashicons dashicons-format-aside"></i></div><h3>Nenhum tipo de evento cadastrado</h3><p>Ainda não há tipos de evento cadastrados no sistema.</p></div>');
                    }
                    console.log('Nenhum item encontrado na página', page);
                }
            } else {
                hasMore = false;
                console.log('Erro na resposta do servidor:', response);
            }
        }).fail(function(xhr, status, error) {
            console.error('Erro na requisição AJAX:', { xhr, status, error });
            hasMore = false;
            if (reset) {
                 container.html('<div class="sevo-empty-state"><div class="sevo-empty-icon"><i class="dashicons dashicons-warning"></i></div><h3>Erro ao carregar</h3><p>Ocorreu um erro ao carregar os tipos de evento. Tente novamente.</p></div>');
            }
        }).always(function() {
            loading = false;
            loadingIndicator.hide();
        });
    }

    function openFormModal(id = null) {
        console.log('openFormModal chamada com parâmetros:', { id: id, tipo: typeof id });
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden').addClass('show').css('display', 'flex');

        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_get_tipo_evento_form',
            nonce: sevoTipoEventoDashboard.nonce,
            tipo_evento_id: id
        }).done(function(response) {
            if (response.success) {
                modalContent.html(response.data.html);
            } else {
                modalContent.html(`<p class="text-red-500 text-center p-8">${response.data}</p>`);
            }
        });
    }

    // Carregar mais ao rolar (opcional) ou com um botão "Carregar mais".
    // Aqui, vamos carregar tudo na primeira vez para simplicidade.
    console.log('Iniciando carregamento de tipos de evento...');
    loadTiposEvento(true);

    // Abrir modal para criar
    dashboard.on('click', '#sevo-create-tipo-evento-button', function() {
        openFormModal();
    });

    // Event listener para clicar nos cards de tipo de evento (visualização)
    $(document).on('click', '.tipo-evento-card', function(e) {
        // Previne a abertura do modal se clicou em um botão
        if ($(e.target).closest('.card-actions').length > 0) {
            return;
        }
        const tipoEventoId = $(this).data('tipo-evento-id') || $(this).data('id');
        openViewModal(tipoEventoId);
    });

    // Event listeners para os botões dos cards
    $(document).on('click', '.btn-view-tipo-evento', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const tipoEventoId = $(this).data('tipo-evento-id');
        if (tipoEventoId) {
            openViewModal(tipoEventoId);
        }
    });

    $(document).on('click', '.btn-edit-tipo-evento', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const tipoEventoId = $(this).data('tipo-evento-id');
        if (tipoEventoId) {
            openFormModal(tipoEventoId);
        }
    });

    function openViewModal(tipoEventoId) {
        console.log('openViewModal chamada com parâmetros:', { tipoEventoId: tipoEventoId, tipo: typeof tipoEventoId });
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden').addClass('show').css('display', 'flex');

        $.ajax({
            url: sevoTipoEventoDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_get_tipo_evento_details',
                nonce: sevoTipoEventoDashboard.nonce,
                tipo_evento_id: tipoEventoId,
            },
            success: function(response) {
                if (response.success) {
                    modalContent.html(response.data.html);
                } else {
                    modalContent.html('<p class="text-red-500 text-center">Ocorreu um erro ao carregar os detalhes do tipo de evento.</p>');
                }
            },
            error: function() {
                modalContent.html('<p class="text-red-500 text-center">Erro de comunicação. Por favor, tente novamente.</p>');
            }
        });
    }

    // Event listener para o botão de editar no modal de visualização
    modal.on('click', '.sevo-button-edit', function(e) {
        e.preventDefault();
        const tipoEventoId = $(this).data('tipo-evento-id');
        if (tipoEventoId) {
            openFormModal(tipoEventoId);
        }
    });

    // Submeter formulário
    modal.on('submit', '#sevo-tipo-evento-form', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const saveButton = $(this).find('#sevo-save-tipo-evento-button, #sevo-save-button');
        const originalText = saveButton.text();
        
        // Adiciona os dados AJAX necessários
        formData.append('action', 'sevo_save_tipo_evento');
        formData.append('nonce', sevoTipoEventoDashboard.nonce);
        
        saveButton.text('A guardar...').prop('disabled', true);

        $.ajax({
            url: sevoTipoEventoDashboard.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    modal.addClass('hidden').removeClass('show').css('display', 'none');
                    SevoToaster.showSuccess('Tipo de evento salvo com sucesso!');
                    loadTiposEvento(true); // Recarrega a lista
                } else {
                    SevoToaster.showError('Erro: ' + response.data);
                    saveButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                SevoToaster.showError('Erro de comunicação. Por favor, tente novamente.');
                saveButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Ativar/Inativar
    modal.on('click', '#sevo-toggle-status-button', function() {
        const button = $(this);
        const id = button.data('id');
        
        SevoPopup.confirm('Tem certeza que deseja alterar o status?', {
            title: 'Confirmar Alteração',
            confirmText: 'Sim, alterar',
            cancelText: 'Cancelar'
        }).then(confirmed => {
            if (!confirmed) return;
        
        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_toggle_tipo_evento_status',
            nonce: sevoTipoEventoDashboard.nonce,
            tipo_evento_id: id
        }).done(function(response) {
            if (response.success) {
                modal.addClass('hidden').removeClass('show').css('display', 'none');
                SevoToaster.showSuccess('Status alterado com sucesso!');
                loadTiposEvento(true);
            } else {
                SevoToaster.showError('Erro: ' + response.data);
            }
        });
        });
    });

    // Event listener para o botão cancelar no formulário
    modal.on('click', '#sevo-cancel-button', function() {
        modal.addClass('hidden').removeClass('show').css('display', 'none');
    });

    // Fechar o modal
    modal.on('click', '#sevo-modal-close', () => modal.addClass('hidden').removeClass('show').css('display', 'none'));
    modal.on('click', (e) => { if ($(e.target).is(modal)) modal.addClass('hidden').removeClass('show').css('display', 'none'); });
    $(document).on('keyup', (e) => { if (e.key === "Escape") modal.addClass('hidden').removeClass('show').css('display', 'none'); });
});
