/**
 * JavaScript para a Landing Page de Eventos
 * Controla os carrosséis, navegação e interações
 */

jQuery(document).ready(function($) {
    'use strict';

    // Objeto principal da Landing Page
    const SevoLandingPage = {
        carousels: {},
        
        init: function() {
            this.initCarousels();
            this.bindEvents();
            this.loadInitialContent();
        },

        // Inicializa todos os carrosséis
        initCarousels: function() {
            $('.sevo-carousel-container').each((index, container) => {
                const $container = $(container);
                const section = $container.data('section');
                
                this.carousels[section] = {
                    container: $container,
                    track: $container.find('.sevo-carousel-track'),
                    prevBtn: $container.find('.sevo-carousel-prev'),
                    nextBtn: $container.find('.sevo-carousel-next'),
                    indicators: $container.siblings('.sevo-carousel-indicators'),
                    currentPage: 1,
                    totalPages: 1,
                    cardsPerView: this.getCardsPerView(),
                    isLoading: false
                };
            });
        },

        // Determina quantos cards mostrar por vez baseado na tela
        getCardsPerView: function() {
            const width = $(window).width();
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            if (width <= 1200) return 3;
            return 4;
        },

        // Vincula eventos
        bindEvents: function() {
            // Botões de navegação do carrossel
            $(document).on('click', '.sevo-carousel-prev', (e) => {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                this.navigateCarousel(section, 'prev');
            });

            $(document).on('click', '.sevo-carousel-next', (e) => {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                this.navigateCarousel(section, 'next');
            });

            // Clique nos cards para abrir o modal
            $(document).on('click', '.sevo-carousel-card', (e) => {
                const eventId = $(e.currentTarget).data('event-id');
                this.openEventModal(eventId);
            });

            // Fechar modal
            $(document).on('click', '#sevo-evento-view-modal-close, .sevo-modal-backdrop', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeEventModal();
                }
            });

            // Indicadores do carrossel
            $(document).on('click', '.sevo-carousel-indicator', (e) => {
                const $indicator = $(e.currentTarget);
                const section = $indicator.closest('.sevo-carousel-indicators').data('section');
                const page = $indicator.data('page');
                this.goToPage(section, page);
            });

            // Redimensionamento da janela
            $(window).on('resize', () => {
                this.handleResize();
            });

            // Navegação por teclado
            $(document).on('keydown', (e) => {
                if ($('#sevo-evento-view-modal').hasClass('hidden')) return;
                
                if (e.key === 'Escape') {
                    this.closeEventModal();
                }
            });
        },

        // Carrega o conteúdo inicial de todos os carrosséis
        loadInitialContent: function() {
            Object.keys(this.carousels).forEach(section => {
                this.loadCarouselContent(section, 1);
            });
        },

        // Carrega conteúdo do carrossel via AJAX
        loadCarouselContent: function(section, page = 1) {
            const carousel = this.carousels[section];
            if (!carousel || carousel.isLoading) return;

            // Verifica se os dados AJAX estão disponíveis
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis para carrossel');
                return;
            }

            carousel.isLoading = true;
            this.showLoading(true);

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_load_carousel_eventos',
                    nonce: ajaxData.nonce,
                    section_type: section,
                    page: page
                },
                success: (response) => {
                    if (response.success) {
                        if (page === 1) {
                            carousel.track.html(response.data.items);
                        } else {
                            carousel.track.append(response.data.items);
                        }
                        
                        carousel.currentPage = response.data.currentPage;
                        carousel.totalPages = response.data.totalPages;
                        
                        this.updateCarouselControls(section);
                        this.updateIndicators(section);
                        this.updateCarouselPosition(section);
                    } else {
                        console.error('Erro ao carregar eventos do carrossel:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao carregar eventos:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                },
                complete: () => {
                    carousel.isLoading = false;
                    this.showLoading(false);
                }
            });
        },

        // Navega no carrossel
        navigateCarousel: function(section, direction) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            const newPage = direction === 'next' ? 
                carousel.currentPage + 1 : 
                carousel.currentPage - 1;

            if (newPage < 1 || newPage > carousel.totalPages) return;

            this.goToPage(section, newPage);
        },

        // Vai para uma página específica
        goToPage: function(section, page) {
            const carousel = this.carousels[section];
            if (!carousel || page < 1 || page > carousel.totalPages) return;

            carousel.currentPage = page;
            this.updateCarouselPosition(section);
            this.updateCarouselControls(section);
            this.updateIndicators(section);
        },

        // Atualiza a posição do carrossel
        updateCarouselPosition: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            const cardWidth = 100 / carousel.cardsPerView;
            const translateX = -(carousel.currentPage - 1) * (cardWidth * carousel.cardsPerView);
            
            carousel.track.css('transform', `translateX(${translateX}%)`);
        },

        // Atualiza os controles do carrossel
        updateCarouselControls: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            carousel.prevBtn.prop('disabled', carousel.currentPage <= 1);
            carousel.nextBtn.prop('disabled', carousel.currentPage >= carousel.totalPages);
        },

        // Atualiza os indicadores
        updateIndicators: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            let indicatorsHtml = '';
            for (let i = 1; i <= carousel.totalPages; i++) {
                const activeClass = i === carousel.currentPage ? 'active' : '';
                indicatorsHtml += `<div class="sevo-carousel-indicator ${activeClass}" data-page="${i}"></div>`;
            }
            
            carousel.indicators.html(indicatorsHtml);
        },

        // Abre o modal do evento
        openEventModal: function(eventId) {
            console.log('SevoLandingPage.openEventModal chamado com ID:', eventId);
            
            const modal = document.getElementById('sevo-event-modal');
            if (!modal) {
                console.error('Modal #sevo-event-modal não encontrado');
                return;
            }
            
            const modalContent = modal.querySelector('.sevo-modal-content');
            const loadingIndicator = modal.querySelector('.sevo-modal-loading');
            
            if (!modalContent || !loadingIndicator) {
                console.error('Elementos do modal não encontrados');
                return;
            }
            
            // Verifica se os dados AJAX estão disponíveis
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis (sevoLandingPage)');
                alert('Erro: Dados de configuração não encontrados');
                return;
            }
            
            console.log('Dados AJAX:', ajaxData);
            
            // Mostra o modal e o loading
            modal.style.display = 'flex';
            loadingIndicator.style.display = 'block';
            modalContent.style.display = 'none';
            
            // Faz a requisição AJAX
            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_view',
                    event_id: eventId,
                    nonce: ajaxData.nonce
                },
                beforeSend: function() {
                    console.log('Enviando requisição AJAX para:', ajaxData.ajax_url);
                    console.log('Dados:', {
                        action: 'sevo_get_evento_view',
                        event_id: eventId,
                        nonce: ajaxData.nonce
                    });
                },
                success: function(response) {
                    console.log('Resposta AJAX recebida:', response);
                    if (response.success) {
                        modalContent.innerHTML = response.data.html;
                        loadingIndicator.style.display = 'none';
                        modalContent.style.display = 'block';
                    } else {
                        console.error('Erro ao carregar evento:', response.data);
                        alert('Erro ao carregar evento: ' + (response.data || 'Erro desconhecido'));
                        this.closeEventModal();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        xhr: xhr
                    });
                    alert('Erro na requisição AJAX: ' + error);
                    this.closeEventModal();
                }.bind(this)
            });
        },

        // Fecha o modal do evento
        closeEventModal: function() {
            const modal = document.getElementById('sevo-event-modal');
            modal.style.display = 'none';
        },

        // Mostra/esconde indicador de loading
        showLoading: function(show) {
            const $loading = $('#sevo-landing-loading');
            if (show) {
                $loading.show();
            } else {
                $loading.hide();
            }
        },

        // Lida com redimensionamento da tela
        handleResize: function() {
            const newCardsPerView = this.getCardsPerView();
            
            Object.keys(this.carousels).forEach(section => {
                const carousel = this.carousels[section];
                if (carousel.cardsPerView !== newCardsPerView) {
                    carousel.cardsPerView = newCardsPerView;
                    this.updateCarouselPosition(section);
                }
            });
        }
    };

    // Inicializa a Landing Page
    SevoLandingPage.init();

    // Torna o objeto global para debug
    window.SevoLandingPage = SevoLandingPage;
});