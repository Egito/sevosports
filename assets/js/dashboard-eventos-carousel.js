/**
 * JavaScript para Carrossel de Eventos no Dashboard
 * Plugin Sevo Eventos
 */

(function($) {
    'use strict';

    class SevoEventsCarousel {
        constructor() {
            this.carousels = new Map();
            this.init();
        }

        init() {
            this.initCarousels();
            this.bindEvents();
        }

        initCarousels() {
            $('.sevo-carousel-container').each((index, container) => {
                const $container = $(container);
                const section = $container.data('section');
                
                if (section) {
                    this.carousels.set(section, {
                        container: $container,
                        track: $container.find('.sevo-carousel-track'),
                        currentIndex: 0,
                        cardWidth: 300 + 16, // largura do card + gap
                        visibleCards: this.getVisibleCards($container),
                        totalCards: $container.find('.sevo-event-card').length
                    });
                    
                    this.updateCarouselButtons(section);
                }
            });
        }

        getVisibleCards($container) {
            const containerWidth = $container.width();
            const cardWidth = 300 + 16; // largura do card + gap
            return Math.floor(containerWidth / cardWidth);
        }

        bindEvents() {
            // Botões de navegação
            $('.carousel-btn.prev-btn').on('click', (e) => {
                const section = $(e.currentTarget).data('section');
                this.movePrevious(section);
            });

            $('.carousel-btn.next-btn').on('click', (e) => {
                const section = $(e.currentTarget).data('section');
                this.moveNext(section);
            });

            // Redimensionamento da janela
            $(window).on('resize', () => {
                this.handleResize();
            });

            // Scroll infinito com mouse wheel
            $('.sevo-carousel-container').on('wheel', (e) => {
                e.preventDefault();
                const section = $(e.currentTarget).data('section');
                
                if (e.originalEvent.deltaY > 0) {
                    this.moveNext(section);
                } else {
                    this.movePrevious(section);
                }
            });

            // Touch/swipe para dispositivos móveis
            this.initTouchEvents();
        }

        initTouchEvents() {
            let startX = 0;
            let currentX = 0;
            let isDragging = false;

            $('.sevo-carousel-container').on('touchstart', (e) => {
                startX = e.originalEvent.touches[0].clientX;
                isDragging = true;
            });

            $('.sevo-carousel-container').on('touchmove', (e) => {
                if (!isDragging) return;
                
                currentX = e.originalEvent.touches[0].clientX;
                const diffX = startX - currentX;
                
                // Adiciona resistência visual durante o arraste
                const section = $(e.currentTarget).data('section');
                const carousel = this.carousels.get(section);
                if (carousel) {
                    const currentTransform = this.getCurrentTransform(carousel.track);
                    carousel.track.css('transform', `translateX(${currentTransform - diffX * 0.5}px)`);
                }
            });

            $('.sevo-carousel-container').on('touchend', (e) => {
                if (!isDragging) return;
                
                const diffX = startX - currentX;
                const section = $(e.currentTarget).data('section');
                
                // Determina a direção do swipe
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        this.moveNext(section);
                    } else {
                        this.movePrevious(section);
                    }
                } else {
                    // Volta para a posição original se o swipe foi muito pequeno
                    this.updateCarouselPosition(section);
                }
                
                isDragging = false;
            });
        }

        getCurrentTransform($element) {
            const transform = $element.css('transform');
            if (transform === 'none') return 0;
            
            const matrix = transform.match(/matrix\((.+)\)/);
            if (matrix) {
                const values = matrix[1].split(', ');
                return parseFloat(values[4]) || 0;
            }
            return 0;
        }

        moveNext(section) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
            
            if (carousel.currentIndex < maxIndex) {
                carousel.currentIndex++;
            } else {
                // Scroll infinito - volta para o início
                carousel.currentIndex = 0;
            }
            
            this.updateCarouselPosition(section);
            this.updateCarouselButtons(section);
        }

        movePrevious(section) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            if (carousel.currentIndex > 0) {
                carousel.currentIndex--;
            } else {
                // Scroll infinito - vai para o final
                const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
                carousel.currentIndex = maxIndex;
            }
            
            this.updateCarouselPosition(section);
            this.updateCarouselButtons(section);
        }

        updateCarouselPosition(section) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            const translateX = -carousel.currentIndex * carousel.cardWidth;
            carousel.track.css('transform', `translateX(${translateX}px)`);
        }

        updateCarouselButtons(section) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            const $prevBtn = $(`.carousel-btn.prev-btn[data-section="${section}"]`);
            const $nextBtn = $(`.carousel-btn.next-btn[data-section="${section}"]`);
            
            // Para scroll infinito, os botões nunca ficam desabilitados
            // Mas podemos adicionar indicadores visuais
            $prevBtn.removeClass('disabled');
            $nextBtn.removeClass('disabled');
            
            // Adiciona indicadores visuais se estiver no início ou fim
            if (carousel.currentIndex === 0) {
                $prevBtn.addClass('at-start');
            } else {
                $prevBtn.removeClass('at-start');
            }
            
            const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
            if (carousel.currentIndex >= maxIndex) {
                $nextBtn.addClass('at-end');
            } else {
                $nextBtn.removeClass('at-end');
            }
        }

        handleResize() {
            // Recalcula as dimensões dos carrosséis
            this.carousels.forEach((carousel, section) => {
                const newVisibleCards = this.getVisibleCards(carousel.container);
                carousel.visibleCards = newVisibleCards;
                
                // Ajusta a posição atual se necessário
                const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
                if (carousel.currentIndex > maxIndex) {
                    carousel.currentIndex = maxIndex;
                }
                
                this.updateCarouselPosition(section);
                this.updateCarouselButtons(section);
            });
        }

        // Método público para adicionar novos eventos dinamicamente
        addEvent(section, eventHtml) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            carousel.track.append(eventHtml);
            carousel.totalCards++;
            this.updateCarouselButtons(section);
        }

        // Método público para remover eventos
        removeEvent(section, eventId) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            carousel.track.find(`[data-event-id="${eventId}"]`).remove();
            carousel.totalCards--;
            
            // Ajusta a posição se necessário
            const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
            if (carousel.currentIndex > maxIndex) {
                carousel.currentIndex = maxIndex;
                this.updateCarouselPosition(section);
            }
            
            this.updateCarouselButtons(section);
        }

        // Método público para atualizar um carrossel específico
        refreshCarousel(section) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            carousel.totalCards = carousel.track.find('.sevo-event-card').length;
            carousel.visibleCards = this.getVisibleCards(carousel.container);
            carousel.currentIndex = 0;
            
            this.updateCarouselPosition(section);
            this.updateCarouselButtons(section);
        }

        // Método público para ir para um card específico
        goToCard(section, cardIndex) {
            const carousel = this.carousels.get(section);
            if (!carousel) return;

            const maxIndex = Math.max(0, carousel.totalCards - carousel.visibleCards);
            carousel.currentIndex = Math.min(cardIndex, maxIndex);
            
            this.updateCarouselPosition(section);
            this.updateCarouselButtons(section);
        }
    }

    // Auto-play opcional (pode ser ativado/desativado)
    class SevoCarouselAutoPlay {
        constructor(carousel, interval = 5000) {
            this.carousel = carousel;
            this.interval = interval;
            this.timers = new Map();
            this.isEnabled = false;
        }

        enable() {
            this.isEnabled = true;
            this.startAll();
        }

        disable() {
            this.isEnabled = false;
            this.stopAll();
        }

        startAll() {
            if (!this.isEnabled) return;
            
            this.carousel.carousels.forEach((carouselData, section) => {
                this.start(section);
            });
        }

        stopAll() {
            this.timers.forEach((timer) => {
                clearInterval(timer);
            });
            this.timers.clear();
        }

        start(section) {
            if (!this.isEnabled) return;
            
            this.stop(section);
            
            const timer = setInterval(() => {
                this.carousel.moveNext(section);
            }, this.interval);
            
            this.timers.set(section, timer);
        }

        stop(section) {
            const timer = this.timers.get(section);
            if (timer) {
                clearInterval(timer);
                this.timers.delete(section);
            }
        }
    }

    // Inicialização quando o documento estiver pronto
    $(document).ready(function() {
        // Inicializa o carrossel
        window.SevoEventsCarousel = new SevoEventsCarousel();
        
        // Inicializa o auto-play (desabilitado por padrão)
        window.SevoCarouselAutoPlay = new SevoCarouselAutoPlay(window.SevoEventsCarousel);
        
        // Pausa o auto-play quando o mouse está sobre o carrossel
        $('.sevo-carousel-container').on('mouseenter', function() {
            const section = $(this).data('section');
            window.SevoCarouselAutoPlay.stop(section);
        });
        
        $('.sevo-carousel-container').on('mouseleave', function() {
            const section = $(this).data('section');
            if (window.SevoCarouselAutoPlay.isEnabled) {
                window.SevoCarouselAutoPlay.start(section);
            }
        });
    });

})(jQuery);