/**
 * Sevo Back to Top Button JavaScript
 * Funcionalidade do botão "Voltar ao Topo" para o plugin Sevo Eventos
 */

(function($) {
    'use strict';

    /**
     * Classe para gerenciar o botão Back to Top
     */
    class SevoBackToTop {
        constructor() {
            this.button = null;
            this.scrollThreshold = 300;
            this.isVisible = false;
            this.init();
        }

        /**
         * Inicializa o botão back to top
         */
        init() {
            this.createButton();
            this.bindEvents();
        }

        /**
         * Cria o elemento HTML do botão
         */
        createButton() {
            // Verifica se o botão já existe
            if ($('.sevo-back-to-top').length > 0) {
                this.button = $('.sevo-back-to-top');
                return;
            }

            // Cria o botão
            this.button = $('<button>', {
                class: 'sevo-back-to-top',
                'aria-label': 'Voltar ao topo da página',
                'title': 'Voltar ao topo',
                'type': 'button'
            });

            // Adiciona o ícone
            this.button.html('<i class="dashicons dashicons-arrow-up-alt2"></i>');

            // Adiciona o botão ao body
            $('body').append(this.button);
        }

        /**
         * Vincula os eventos necessários
         */
        bindEvents() {
            const self = this;

            // Evento de scroll da janela
            $(window).on('scroll.sevoBackToTop', function() {
                self.handleScroll();
            });

            // Evento de clique no botão
            this.button.on('click.sevoBackToTop', function(e) {
                e.preventDefault();
                self.scrollToTop();
            });

            // Evento de teclado para acessibilidade
            this.button.on('keydown.sevoBackToTop', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.scrollToTop();
                }
            });
        }

        /**
         * Manipula o evento de scroll
         */
        handleScroll() {
            const scrollTop = $(window).scrollTop();
            const shouldShow = scrollTop > this.scrollThreshold;

            if (shouldShow && !this.isVisible) {
                this.showButton();
            } else if (!shouldShow && this.isVisible) {
                this.hideButton();
            }
        }

        /**
         * Mostra o botão com animação
         */
        showButton() {
            this.isVisible = true;
            this.button.removeClass('hide').addClass('show');
        }

        /**
         * Esconde o botão com animação
         */
        hideButton() {
            this.isVisible = false;
            this.button.removeClass('show').addClass('hide');
            
            // Remove a classe hide após a animação
            setTimeout(() => {
                this.button.removeClass('hide');
            }, 300);
        }

        /**
         * Faz o scroll suave para o topo
         */
        scrollToTop() {
            // Verifica se o usuário prefere movimento reduzido
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            if (prefersReducedMotion) {
                // Scroll instantâneo para usuários que preferem movimento reduzido
                window.scrollTo(0, 0);
            } else {
                // Scroll suave
                $('html, body').animate({
                    scrollTop: 0
                }, {
                    duration: 600,
                    easing: 'swing'
                });
            }

            // Foca no topo da página para acessibilidade
            $('body').attr('tabindex', '-1').focus().removeAttr('tabindex');
        }

        /**
         * Destrói o botão e remove eventos
         */
        destroy() {
            if (this.button) {
                this.button.off('.sevoBackToTop').remove();
            }
            $(window).off('scroll.sevoBackToTop');
            this.button = null;
            this.isVisible = false;
        }
    }

    /**
     * Inicialização quando o documento estiver pronto
     */
    $(document).ready(function() {
        // Verifica se estamos em uma página do plugin Sevo
        if ($('.sevo-dashboard-wrapper, .sevo-eventos-container, .sevo-orgs-container, .sevo-tipos-container').length > 0) {
            // Inicializa o botão back to top
            window.sevoBackToTop = new SevoBackToTop();
        }
    });

    /**
     * Cleanup quando a página for descarregada
     */
    $(window).on('beforeunload', function() {
        if (window.sevoBackToTop) {
            window.sevoBackToTop.destroy();
        }
    });

})(jQuery);