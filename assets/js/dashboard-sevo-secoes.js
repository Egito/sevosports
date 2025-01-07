jQuery(document).ready(function($) {
    let page = 1;
    let loading = false;
    let hasMore = true;
    
    // Inicializar filtros com valores da URL
    function initializeFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('taxonomy')) {
            $('#taxonomy-filter').val(urlParams.get('taxonomy'));
        }
        if (urlParams.has('tipo')) {
            $('#participation-type-filter').val(urlParams.get('tipo'));
        }
        if (urlParams.has('evento')) {
            $('#related-event-filter').val(urlParams.get('evento'));
        }
        if (urlParams.has('ano_inscricao')) {
            $('#registration-year-filter').val(urlParams.get('ano_inscricao'));
        }
        if (urlParams.has('ano_secao')) {
            $('#section-year-filter').val(urlParams.get('ano_secao'));
        } else {
            // Se não houver ano da seção na URL, usar o ano atual
            $('#section-year-filter').val(new Date().getFullYear());
        }
    }

    // Carregar mais seções
    function loadMoreSecoes() {
        if (loading || !hasMore) return;
        
        loading = true;
        $('.sevo-loading-spinner').show();

        let filters = {
            taxonomy: $('#taxonomy-filter').val(),
            participation_type: $('#participation-type-filter').val(),
            related_event: $('#related-event-filter').val(),
            registration_year: $('#registration-year-filter').val(),
            section_year: $('#section-year-filter').val()
        };
        
        // Atualizar URL com os filtros
        let urlParams = new URLSearchParams(window.location.search);
        for (let key in filters) {
            if (filters[key]) {
                urlParams.set(key, filters[key]);
            } else {
                urlParams.delete(key);
            }
        }
        window.history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);

        $.ajax({
            url: sevoSecoesDashboard.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_more_secoes',
                nonce: sevoSecoesDashboard.nonce,
                page: page,
                ...filters
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.items) {
                        const items = $(response.data.items);
                        
                        // Processar cada card
                        items.each(function() {
                            const card = $(this);
                            
                            // Formatar datas com tooltips
                            const formatDate = (dateString, full = false) => {
                                const date = new Date(dateString);
                                const options = full ? {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric'
                                } : {
                                    day: '2-digit',
                                    month: '2-digit'
                                };
                                return date.toLocaleDateString('pt-BR', options);
                            };
                            
                            // Atualizar datas com tooltips
                            const inscricaoRange = card.find('.date-row.inscricao .date-range');
                            inscricaoRange.html(`
                                ${formatDate(card.data('inicio-inscricao'))} - 
                                ${formatDate(card.data('fim-inscricao'))}
                            `);
                            inscricaoRange.attr('title', 
                                `Inscrições: ${formatDate(card.data('inicio-inscricao'), true)} a ${formatDate(card.data('fim-inscricao'), true)}`
                            );
                            
                            const secaoRange = card.find('.date-row.secao .date-range');
                            secaoRange.html(`
                                ${formatDate(card.data('inicio-secao'))} - 
                                ${formatDate(card.data('fim-secao'))}
                            `);
                            secaoRange.attr('title',
                                `Seção: ${formatDate(card.data('inicio-secao'), true)} a ${formatDate(card.data('fim-secao'), true)}`
                            );
                            
                            // Atualizar vagas e inscrições
                            const vagas = card.data('vagas');
                            const inscricoes = card.data('inscricoes');
                            
                            // Atualizar barra de progresso
                            const progresso = Math.min((inscricoes / vagas) * 100, 100);
                            card.find('.progress-bar').css('width', `${progresso}%`);
                            
                            // Atualizar texto das vagas
                            card.find('.vagas-info .vagas-numero').text(vagas);
                            card.find('.vagas-info .inscritos-numero').text(inscricoes);
                            
                            // Atualizar cor da barra de progresso conforme ocupação
                            if (progresso >= 90) {
                                card.find('.progress-bar').css('background-color', '#e74c3c');
                            } else if (progresso >= 75) {
                                card.find('.progress-bar').css('background-color', '#f39c12');
                            } else {
                                card.find('.progress-bar').css('background-color', '#3498db');
                            }
                            
                            // Tornar card clicável
                            card.css('cursor', 'pointer').on('click', function() {
                                window.location.href = `/single-sevo-secao/${card.data('slug')}`;
                            });
                        });
                        
                        if (page === 1) {
                            $('.sevo-secoes-grid').html(items);
                        } else {
                            $('.sevo-secoes-grid').append(items);
                        }
                        page++;
                        hasMore = response.data.hasMore;
                    } else {
                        hasMore = false;
                        if (page === 1) {
                            $('.sevo-secoes-grid').html('<p class="no-results">Nenhuma seção encontrada com os filtros selecionados.</p>');
                        }
                    }
                } else {
                    console.error('Erro na resposta do servidor:', response);
                }
                loading = false;
                $('.sevo-loading-spinner').hide();
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar seções:', error);
                loading = false;
                $('.sevo-loading-spinner').hide();
            }
        });
    }

    // Scroll infinito
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
            loadMoreSecoes();
        }
    });

    // Eventos dos filtros
    $('.sevo-filter').on('change', function() {
        page = 1;
        hasMore = true;
        loadMoreSecoes();
    });

    // Inicializar
    initializeFilters();
    loadMoreSecoes();
});
