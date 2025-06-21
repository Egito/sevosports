jQuery(document).ready(function($) {
    'use strict';

    let page = 1;
    let loading = false;
    let hasMore = true;
    const container = $('#sevo-eventos-container');
    const loadingIndicator = $('#sevo-loading-indicator');

    /**
     * Busca e carrega os eventos via AJAX.
     * @param {boolean} reset - Se verdadeiro, limpa o container e reseta a paginação.
     */
    function loadEvents(reset = false) {
        if (loading || (!hasMore && !reset)) {
            return;
        }

        loading = true;
        loadingIndicator.show();

        if (reset) {
            page = 1;
            hasMore = true;
            container.empty();
        }

        const filters = {
            action: sevoDashboard.action,
            nonce: sevoDashboard.nonce,
            page: page,
            tipo_evento: $('#filtro-tipo-evento').val(),
            categoria_evento: $('#filtro-categoria-evento').val(),
            ano_evento: $('#filtro-ano-evento').val(),
        };

        $.ajax({
            url: sevoDashboard.ajax_url,
            type: 'POST',
            data: filters,
            success: function(response) {
                if (response.success) {
                    if (response.data.items && response.data.items.length > 0) {
                        container.append(response.data.items);
                        page++;
                    } else if (reset) {
                        container.html('<p>Nenhum evento encontrado com os filtros selecionados.</p>');
                    }
                    
                    hasMore = response.data.hasMore;
                } else {
                    if (reset) {
                         container.html('<p>Ocorreu um erro ao carregar os eventos.</p>');
                    }
                }
            },
            error: function() {
                if (reset) {
                    container.html('<p>Ocorreu um erro de comunicação. Tente novamente.</p>');
                }
            },
            complete: function() {
                loading = false;
                loadingIndicator.hide();
            }
        });
    }

    // Listener para os filtros
    $('.sevo-filter').on('change', function() {
        loadEvents(true); // Reseta e carrega os eventos
    });
    
    // Adiciona o click para ir para a página do evento
    container.on('click', '.evento-card', function() {
        const slug = $(this).data('slug');
        if (slug) {
            window.location.href = '/evento/' + slug;
        }
    });

    // Scroll Infinito
    $(window).on('scroll', function() {
        // Carrega mais quando o usuário estiver a 300px do final da página
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 300) {
            loadEvents();
        }
    });

    // Carga inicial
    loadEvents(true);
});