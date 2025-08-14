jQuery(document).ready(function($) {
    'use strict';

    const dashboard = $('#sevo-tipo-evento-dashboard');
    if (dashboard.length === 0) return;

    const container = $('#sevo-tipo-eventos-container');
    const modal = $('#sevo-tipo-evento-modal');
    const modalContent = $('#sevo-modal-content');
    const loadingIndicator = $('#sevo-loading-indicator');
    
    let page = 1;
    let loading = false;
    let hasMore = true;

    function loadTiposEvento(reset = false) {
        if (loading || (!hasMore && !reset)) return;
        
        loading = true;
        loadingIndicator.show();

        if (reset) {
            page = 1;
            hasMore = true;
            container.empty();
        }

        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_load_more_tipos_evento',
            nonce: sevoTipoEventoDashboard.nonce,
            page: page,
        }).done(function(response) {
            if (response.success && response.data.items) {
                container.append(response.data.items);
                hasMore = response.data.hasMore;
                page++;
            } else {
                hasMore = false;
                if (reset) {
                    container.html('<p class="col-span-full text-center">Nenhum tipo de evento encontrado.</p>');
                }
            }
        }).fail(function() {
            if (reset) {
                 container.html('<p class="col-span-full text-center text-red-500">Erro ao carregar tipos de evento.</p>');
            }
        }).always(function() {
            loading = false;
            loadingIndicator.hide();
        });
    }

    function openFormModal(id = null) {
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

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

    // Carregar mais ao rolar (opcional) ou com um botão "Carregar mais"
    // Aqui, vamos carregar tudo na primeira vez para simplicidade.
    loadTiposEvento(true);

    // Abrir modal para criar
    dashboard.on('click', '#sevo-create-tipo-evento-button', function() {
        openFormModal();
    });

    // Abrir modal para visualizar detalhes
    container.on('click', '.tipo-evento-card', function() {
        const tipoEventoId = $(this).data('tipo-evento-id') || $(this).data('id');
        openViewModal(tipoEventoId);
    });

    function openViewModal(tipoEventoId) {
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

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
        const tipoEventoId = $(this).attr('href').match(/post=(\d+)/)[1];
        openFormModal(tipoEventoId);
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
                    modal.addClass('hidden');
                    loadTiposEvento(true); // Recarrega a lista
                } else {
                    alert('Erro: ' + response.data);
                    saveButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Erro de comunicação. Por favor, tente novamente.');
                saveButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Ativar/Inativar
    modal.on('click', '#sevo-toggle-status-button', function() {
        if (!confirm('Tem certeza que deseja alterar o status?')) return;
        
        const button = $(this);
        const id = button.data('id');
        
        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_toggle_tipo_evento_status',
            nonce: sevoTipoEventoDashboard.nonce,
            tipo_evento_id: id
        }).done(function(response) {
            if (response.success) {
                modal.addClass('hidden');
                loadTiposEvento(true);
            } else {
                alert('Erro: ' + response.data);
            }
        });
    });

    // Event listener para o botão cancelar no formulário
    modal.on('click', '#sevo-cancel-button', function() {
        modal.addClass('hidden');
    });

    // Fechar o modal
    modal.on('click', '#sevo-modal-close', () => modal.addClass('hidden'));
    modal.on('click', (e) => { if ($(e.target).is(modal)) modal.addClass('hidden'); });
    $(document).on('keyup', (e) => { if (e.key === "Escape") modal.addClass('hidden'); });
});
