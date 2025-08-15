/**
 * SEVO Popup - Sistema Centralizado de Confirmações e Perguntas
 * Fornece uma interface unificada para diálogos interativos
 */

(function() {
    'use strict';

    // Verificar se já foi inicializado
    if (window.SevoPopup) {
        return;
    }

    // Configurações padrão
    const defaultConfig = {
        closeOnOverlay: true,
        closeOnEscape: true,
        autoFocus: true,
        animation: true
    };

    // Sistema de popup centralizado
    window.SevoPopup = {
        
        /**
         * Exibe um popup de confirmação
         * @param {string} message - Mensagem a ser exibida
         * @param {Object} options - Opções do popup
         * @returns {Promise} Promise que resolve com true/false
         */
        confirm: function(message, options = {}) {
            const config = {
                title: options.title || 'Confirmação',
                message: message,
                type: options.type || 'confirm',
                confirmText: options.confirmText || 'Confirmar',
                cancelText: options.cancelText || 'Cancelar',
                confirmClass: options.confirmClass || 'sevo-popup-btn-primary',
                cancelClass: options.cancelClass || 'sevo-popup-btn-secondary',
                icon: options.icon || '❓',
                ...defaultConfig,
                ...options
            };

            return this._showPopup(config);
        },

        /**
         * Exibe um popup de aviso/confirmação de perigo
         * @param {string} message - Mensagem a ser exibida
         * @param {Object} options - Opções do popup
         * @returns {Promise} Promise que resolve com true/false
         */
        warning: function(message, options = {}) {
            const config = {
                title: options.title || 'Atenção',
                message: message,
                type: 'warning',
                confirmText: options.confirmText || 'Continuar',
                cancelText: options.cancelText || 'Cancelar',
                confirmClass: 'sevo-popup-btn-warning',
                cancelClass: 'sevo-popup-btn-secondary',
                icon: '⚠️',
                ...defaultConfig,
                ...options
            };

            return this._showPopup(config);
        },

        /**
         * Exibe um popup de confirmação de ação perigosa
         * @param {string} message - Mensagem a ser exibida
         * @param {Object} options - Opções do popup
         * @returns {Promise} Promise que resolve com true/false
         */
        danger: function(message, options = {}) {
            const config = {
                title: options.title || 'Ação Perigosa',
                message: message,
                type: 'danger',
                confirmText: options.confirmText || 'Excluir',
                cancelText: options.cancelText || 'Cancelar',
                confirmClass: 'sevo-popup-btn-danger',
                cancelClass: 'sevo-popup-btn-secondary',
                icon: '🗑️',
                ...defaultConfig,
                ...options
            };

            return this._showPopup(config);
        },

        /**
         * Exibe um popup para entrada de texto
         * @param {string} message - Mensagem a ser exibida
         * @param {Object} options - Opções do popup
         * @returns {Promise} Promise que resolve com o texto inserido ou null
         */
        prompt: function(message, options = {}) {
            const config = {
                title: options.title || 'Entrada de Dados',
                message: message,
                type: 'prompt',
                confirmText: options.confirmText || 'OK',
                cancelText: options.cancelText || 'Cancelar',
                confirmClass: 'sevo-popup-btn-primary',
                cancelClass: 'sevo-popup-btn-secondary',
                icon: '✏️',
                placeholder: options.placeholder || '',
                defaultValue: options.defaultValue || '',
                inputType: options.inputType || 'text',
                required: options.required !== false,
                multiline: options.multiline || false,
                ...defaultConfig,
                ...options
            };

            return this._showPopup(config);
        },

        /**
         * Exibe um popup informativo
         * @param {string} message - Mensagem a ser exibida
         * @param {Object} options - Opções do popup
         * @returns {Promise} Promise que resolve quando o popup é fechado
         */
        info: function(message, options = {}) {
            const config = {
                title: options.title || 'Informação',
                message: message,
                type: 'info',
                confirmText: options.confirmText || 'OK',
                cancelText: null, // Sem botão cancelar
                confirmClass: 'sevo-popup-btn-primary',
                icon: 'ℹ️',
                ...defaultConfig,
                ...options
            };

            return this._showPopup(config);
        },

        /**
         * Exibe um popup customizado
         * @param {Object} config - Configuração completa do popup
         * @returns {Promise} Promise que resolve com o resultado
         */
        custom: function(config) {
            return this._showPopup({
                ...defaultConfig,
                ...config
            });
        },

        /**
         * Fecha o popup atual
         */
        close: function() {
            const overlay = document.querySelector('.sevo-popup-overlay');
            if (overlay) {
                this._hidePopup(overlay);
            }
        },

        /**
         * Método interno para exibir o popup
         * @private
         */
        _showPopup: function(config) {
            return new Promise((resolve) => {
                // Remover popup existente
                this._removeExistingPopup();

                // Criar elementos
                const overlay = this._createOverlay(config);
                const popup = this._createPopup(config, resolve);
                
                overlay.appendChild(popup);
                document.body.appendChild(overlay);

                // Configurar eventos
                this._setupEvents(overlay, config, resolve);

                // Mostrar popup
                requestAnimationFrame(() => {
                    overlay.classList.add('show');
                    
                    // Auto focus
                    if (config.autoFocus) {
                        const input = popup.querySelector('.sevo-popup-input');
                        const confirmBtn = popup.querySelector('.sevo-popup-btn-primary');
                        
                        if (input) {
                            input.focus();
                        } else if (confirmBtn) {
                            confirmBtn.focus();
                        }
                    }
                });
            });
        },

        /**
         * Cria o overlay do popup
         * @private
         */
        _createOverlay: function(config) {
            const overlay = document.createElement('div');
            overlay.className = 'sevo-popup-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-labelledby', 'sevo-popup-title');
            
            return overlay;
        },

        /**
         * Cria o popup
         * @private
         */
        _createPopup: function(config, resolve) {
            const popup = document.createElement('div');
            popup.className = 'sevo-popup';
            popup.setAttribute('tabindex', '-1');

            // Header
            const header = this._createHeader(config);
            popup.appendChild(header);

            // Body
            const body = this._createBody(config);
            popup.appendChild(body);

            // Footer
            const footer = this._createFooter(config, resolve);
            popup.appendChild(footer);

            return popup;
        },

        /**
         * Cria o header do popup
         * @private
         */
        _createHeader: function(config) {
            const header = document.createElement('div');
            header.className = 'sevo-popup-header';

            const icon = document.createElement('div');
            icon.className = `sevo-popup-icon ${config.type}`;
            icon.textContent = config.icon;

            const title = document.createElement('h3');
            title.className = 'sevo-popup-title';
            title.id = 'sevo-popup-title';
            title.textContent = config.title;

            header.appendChild(icon);
            header.appendChild(title);

            return header;
        },

        /**
         * Cria o body do popup
         * @private
         */
        _createBody: function(config) {
            const body = document.createElement('div');
            body.className = 'sevo-popup-body';

            const message = document.createElement('p');
            message.className = 'sevo-popup-message';
            message.textContent = config.message;
            body.appendChild(message);

            // Adicionar input se for prompt
            if (config.type === 'prompt') {
                const input = this._createInput(config);
                body.appendChild(input);
            }

            return body;
        },

        /**
         * Cria o input para prompts
         * @private
         */
        _createInput: function(config) {
            let input;
            
            if (config.multiline) {
                input = document.createElement('textarea');
                input.className = 'sevo-popup-input sevo-popup-textarea';
                input.rows = 4;
            } else {
                input = document.createElement('input');
                input.className = 'sevo-popup-input';
                input.type = config.inputType;
            }

            input.placeholder = config.placeholder;
            input.value = config.defaultValue;
            input.required = config.required;

            return input;
        },

        /**
         * Cria o footer com botões
         * @private
         */
        _createFooter: function(config, resolve) {
            const footer = document.createElement('div');
            footer.className = 'sevo-popup-footer';

            // Botão cancelar (se existir)
            if (config.cancelText) {
                const cancelBtn = document.createElement('button');
                cancelBtn.className = `sevo-popup-btn ${config.cancelClass}`;
                cancelBtn.textContent = config.cancelText;
                cancelBtn.onclick = () => this._handleCancel(resolve);
                footer.appendChild(cancelBtn);
            }

            // Botão confirmar
            const confirmBtn = document.createElement('button');
            confirmBtn.className = `sevo-popup-btn ${config.confirmClass}`;
            confirmBtn.textContent = config.confirmText;
            confirmBtn.onclick = () => this._handleConfirm(config, resolve);
            footer.appendChild(confirmBtn);

            return footer;
        },

        /**
         * Configura eventos do popup
         * @private
         */
        _setupEvents: function(overlay, config, resolve) {
            // Fechar ao clicar no overlay
            if (config.closeOnOverlay) {
                overlay.onclick = (e) => {
                    if (e.target === overlay) {
                        this._handleCancel(resolve);
                    }
                };
            }

            // Fechar com ESC
            if (config.closeOnEscape) {
                const handleEscape = (e) => {
                    if (e.key === 'Escape') {
                        document.removeEventListener('keydown', handleEscape);
                        this._handleCancel(resolve);
                    }
                };
                document.addEventListener('keydown', handleEscape);
            }

            // Enter para confirmar (em inputs)
            const input = overlay.querySelector('.sevo-popup-input');
            if (input && !config.multiline) {
                input.onkeydown = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this._handleConfirm(config, resolve);
                    }
                };
            }
        },

        /**
         * Manipula confirmação
         * @private
         */
        _handleConfirm: function(config, resolve) {
            const overlay = document.querySelector('.sevo-popup-overlay');
            
            if (config.type === 'prompt') {
                const input = overlay.querySelector('.sevo-popup-input');
                const value = input.value.trim();
                
                if (config.required && !value) {
                    input.focus();
                    input.style.borderColor = '#dc3545';
                    return;
                }
                
                this._hidePopup(overlay);
                resolve(value);
            } else {
                this._hidePopup(overlay);
                resolve(true);
            }
        },

        /**
         * Manipula cancelamento
         * @private
         */
        _handleCancel: function(resolve) {
            const overlay = document.querySelector('.sevo-popup-overlay');
            this._hidePopup(overlay);
            resolve(false);
        },

        /**
         * Esconde o popup
         * @private
         */
        _hidePopup: function(overlay) {
            overlay.classList.remove('show');
            
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 300);
        },

        /**
         * Remove popup existente
         * @private
         */
        _removeExistingPopup: function() {
            const existing = document.querySelector('.sevo-popup-overlay');
            if (existing) {
                existing.parentNode.removeChild(existing);
            }
        }
    };

    // Compatibilidade com confirm() nativo
    window.SevoPopup.confirmNative = window.confirm;
    
    // Método para substituir confirm() global (opcional)
    window.SevoPopup.replaceNativeConfirm = function() {
        window.confirm = function(message) {
            return window.SevoPopup.confirm(message);
        };
    };

    // Método para restaurar confirm() nativo
    window.SevoPopup.restoreNativeConfirm = function() {
        window.confirm = window.SevoPopup.confirmNative;
    };

    console.log('SEVO Popup System loaded successfully');

})();