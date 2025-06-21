jQuery(document).ready(function($) {
    'use strict';

    // --- Seletores Globais ---
    const dashboard = $('.sevo-eventos-dashboard-container');
    const container = $('#sevo-eventos-container');
    const modal = $('#sevo-evento-modal');
    const modalContent = $('#sevo-evento-modal-content');
    
    // --- Variáveis de Estado ---
    let page = 1;
    let loading = false;
    let hasMore = true;

    /**
     * Carrega eventos via AJAX para a listagem principal.
     */
    function loadEvents(reset = false) {
        if (loading || (!hasMore && !reset)) return;
        loading = true;
        $('#sevo-loading-indicator').show();

        if (reset) {
            page = 1;
            hasMore = true;
            container.empty();
        }

        $.post(sevoDashboard.ajax_url, {
            action: 'sevo_load_more_eventos',
            nonce: sevoDashboard.nonce,
            page: page,
            tipo_evento: $('#filtro-tipo-evento').val(),
            categoria_evento: $('#filtro-categoria-evento').val(),
            ano_evento: $('#filtro-ano-evento').val(),
        }).done(function(response) {
            if (response.success && response.data.items) {
                container.append(response.data.items);
                hasMore = response.data.hasMore;
            } else if (reset) {
                 container.html('<p class="col-span-full text-center text-gray-500">Nenhum evento encontrado com os filtros selecionados.</p>');
            }
        }).fail(function() {
            container.html('<p class="col-span-full text-center text-red-500">Ocorreu um erro ao carregar os eventos.</p>');
        }).always(function() {
            loading = false;
            $('#sevo-loading-indicator').hide();
        });
    }

    /**
     * Abre o modal e carrega o formulário.
     * @param {number|null} eventId - O ID do evento para editar, ou nulo para criar.
     */
    function openEventFormModal(eventId = null) {
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

        $.post(sevoDashboard.ajax_url, {
            action: 'sevo_get_evento_form',
            nonce: sevoDashboard.nonce,
            event_id: eventId
        }).done(function(response) {
            if (response.success) {
                modalContent.html(response.data.html);
            } else {
                modalContent.html(`<p class="text-red-500 text-center p-8">${response.data}</p>`);
            }
        }).fail(function() {
            modalContent.html('<p class="text-red-500 text-center">Erro ao carregar o formulário.</p>');
        });
    }

    // --- Listeners de Eventos ---

    // Botão "Criar Novo Evento"
    dashboard.on('click', '#sevo-create-event-button', function() {
        openEventFormModal();
    });

    // Clicar num card para editar
    container.on('click', '.evento-card', function() {
        openEventFormModal($(this).data('event-id'));
    });

    // Submeter o formulário (Salvar/Criar)
    modal.on('submit', '#sevo-evento-form', function(e) {
        e.preventDefault();
        const saveButton = $(this).find('#sevo-save-evento-button');
        const originalText = saveButton.text();
        saveButton.text('A guardar...').prop('disabled', true);

        $.post(sevoDashboard.ajax_url, {
            action: 'sevo_save_evento',
            nonce: sevoDashboard.nonce,
            form_data: $(this).serialize()
        }).done(function(response) {
            if (response.success) {
                modal.addClass('hidden');
                loadEvents(true); // Recarrega a lista de eventos
            } else {
                alert('Erro: ' + response.data);
                saveButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Botão para Inativar/Ativar
    modal.on('click', '#sevo-toggle-status-button', function() {
        if (!confirm('Tem a certeza que deseja alterar o estado deste evento?')) return;
        
        const button = $(this);
        const eventId = button.data('event-id');
        
        $.post(sevoDashboard.ajax_url, {
            action: 'sevo_toggle_evento_status',
            nonce: sevoDashboard.nonce,
            event_id: eventId
        }).done(function(response) {
            if (response.success) {
                const isActive = response.data.new_status === 'publish';
                button.text(isActive ? 'Inativar Evento' : 'Ativar Evento');
                button.toggleClass('sevo-button-danger', isActive).toggleClass('sevo-button-secondary', !isActive);
                alert(response.data.message); // Feedback para o utilizador
            } else {
                alert('Erro: ' + response.data);
            }
        });
    });

    // Fechar o modal
    modal.on('click', '#sevo-evento-modal-close', () => modal.addClass('hidden'));
    modal.on('click', (e) => { if ($(e.target).is(modal)) modal.addClass('hidden'); });
    $(document).on('keyup', (e) => { if (e.key === "Escape") modal.addClass('hidden'); });
    
    // Listeners dos filtros
    dashboard.on('change', '.sevo-filter', () => loadEvents(true));

    // Carga inicial
    loadEvents(true);
});
