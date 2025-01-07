jQuery(document).ready(function($) {
    let page = 1;
    let loading = false;
    let noMorePosts = false;
    
    // Função para carregar eventos
    function loadEventos(resetGrid = false) {
        if (loading) return;
        
        loading = true;
        $('#eventos-container').addClass('loading');
        
        if (resetGrid) {
            page = 1;
            noMorePosts = false;
            $('#eventos-container').empty();
        }
        
        $.ajax({
            url: sevoEventos.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_more_eventos',
                nonce: sevoEventos.nonce,
                page: page,
                tipo_participacao: $('#tipo-participacao-filter').val(),
                organizacao: $('#organizacao-filter').val()
            },
            success: function(response) {
                if (response.success && response.data) {
                    if (response.data.html) {
                        const $html = $(response.data.html);
                        
                        // Tornar cards clicáveis
                        $html.find('.sevo-evento-card').each(function() {
                            const $card = $(this);
                            const slug = $card.data('slug');
                            
                            $card.css('cursor', 'pointer').on('click', function() {
                                window.location.href = `/single-sevo-evento/${slug}`;
                            });
                        });
                        
                        $('#eventos-container').append($html);
                        page++;
                    } else {
                        noMorePosts = true;
                    }
                }
                
                loading = false;
                $('#eventos-container').removeClass('loading');
            },
            error: function() {
                loading = false;
                $('#eventos-container').removeClass('loading');
                console.error('Erro ao carregar eventos');
            }
        });
    }
    
    // Event listeners para filtros
    $('.sevo-filter').on('change', function() {
        const currentId = $(this).attr('id');
        // Limpar outros filtros
        $('.sevo-filter').not('#' + currentId).val('');
        loadEventos(true);
    });
    
    // Infinite scroll
    $(window).on('scroll', function() {
        if (!loading && !noMorePosts && 
            ($(window).scrollTop() + $(window).height() > $(document).height() - 100)) {
            loadEventos();
        }
    });
    
    // Carregar eventos iniciais
    loadEventos(true);
});
