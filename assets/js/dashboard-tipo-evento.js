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

    // Abrir modal para editar
    container.on('click', '.tipo-evento-card', function() {
        openFormModal($(this).data('id'));
    });

    // Submeter formulário
    modal.on('submit', '#sevo-tipo-evento-form', function(e) {
        e.preventDefault();
        const button = $(this).find('#sevo-save-button');
        button.text('Salvando...').prop('disabled', true);

        $.post(sevoTipoEventoDashboard.ajax_url, {
            action: 'sevo_save_tipo_evento',
            nonce: sevoTipoEventoDashboard.nonce,
            form_data: $(this).serialize()
        }).done(function(response) {
            if (response.success) {
                modal.addClass('hidden');
                loadTiposEvento(true); // Recarrega a lista
            } else {
                alert('Erro: ' + response.data);
                button.text('Salvar Alterações').prop('disabled', false);
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

    // Fechar o modal
    modal.on('click', '#sevo-modal-close', () => modal.addClass('hidden'));
    modal.on('click', (e) => { if ($(e.target).is(modal)) modal.addClass('hidden'); });
    $(document).on('keyup', (e) => { if (e.key === "Escape") modal.addClass('hidden'); });
});
