/**
 * Sistema de Toaster reutilizável para o plugin Sevo
 * Pode ser usado em qualquer modal ou página do sistema
 */

(function($) {
    'use strict';

    // Namespace global para o toaster
    window.SevoToaster = {
        
        /**
         * Mostra um toaster de sucesso
         * @param {string} message - Mensagem a ser exibida
         * @param {number} duration - Duração em milissegundos (padrão: 10000)
         */
        showSuccess: function(message, duration = 10000) {
            this.show(message, 'success', duration);
        },
        
        /**
         * Mostra um toaster de erro
         * @param {string} message - Mensagem a ser exibida
         * @param {number} duration - Duração em milissegundos (padrão: 8000)
         */
        showError: function(message, duration = 8000) {
            this.show(message, 'error', duration);
        },
        
        /**
         * Mostra um toaster de informação
         * @param {string} message - Mensagem a ser exibida
         * @param {number} duration - Duração em milissegundos (padrão: 6000)
         */
        showInfo: function(message, duration = 6000) {
            this.show(message, 'info', duration);
        },
        
        /**
         * Função principal para mostrar toaster
         * @param {string} message - Mensagem a ser exibida
         * @param {string} type - Tipo do toaster (success, error, info)
         * @param {number} duration - Duração em milissegundos
         */
        show: function(message, type = 'success', duration = 10000) {
            // Remove toaster existente se houver
            $('.sevo-toaster').remove();
            
            // Define ícone baseado no tipo
            let icon = 'dashicons-yes-alt'; // success
            if (type === 'error') {
                icon = 'dashicons-dismiss';
            } else if (type === 'info') {
                icon = 'dashicons-info';
            }
            
            // Cria o toaster
            const toaster = $('<div class="sevo-toaster sevo-toaster-' + type + '">' + 
                '<i class="dashicons ' + icon + '"></i>' + 
                '<span>' + message + '</span>' + 
                '<button class="sevo-toaster-close" aria-label="Fechar">' +
                '<i class="dashicons dashicons-no-alt"></i>' +
                '</button>' +
                '</div>');
            
            // Adiciona o toaster ao body
            $('body').append(toaster);
            
            // Event listener para fechar manualmente
            toaster.find('.sevo-toaster-close').on('click', function() {
                SevoToaster.hide(toaster);
            });
            
            // Mostra o toaster com animação
            setTimeout(function() {
                toaster.addClass('sevo-toaster-show');
            }, 100);
            
            // Remove o toaster após a duração especificada
            setTimeout(function() {
                SevoToaster.hide(toaster);
            }, duration);
        },
        
        /**
         * Esconde um toaster específico
         * @param {jQuery} toaster - Elemento jQuery do toaster
         */
        hide: function(toaster) {
            toaster.removeClass('sevo-toaster-show');
            setTimeout(function() {
                toaster.remove();
            }, 300);
        },
        
        /**
         * Remove todos os toasters
         */
        hideAll: function() {
            $('.sevo-toaster').removeClass('sevo-toaster-show');
            setTimeout(function() {
                $('.sevo-toaster').remove();
            }, 300);
        }
    };
    
})(jQuery);