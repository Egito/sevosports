/**
 * JavaScript para Gerenciamento Frontend de Papéis de Usuários
 * Sevo Eventos Plugin
 */

(function($) {
    'use strict';
    
    // Objeto principal para gerenciamento de papéis
    const SevoPapeis = {
        
        // Configurações
        config: {
            nonce: sevoPapeisData.nonce,
            ajaxUrl: sevoPapeisData.ajax_url,
            currentUserId: sevoPapeisData.current_user_id,
            isAdmin: sevoPapeisData.is_admin,
            isEditor: sevoPapeisData.is_editor,
            strings: sevoPapeisData.strings
        },
        
        // Cache de elementos DOM
        elements: {
            container: null,
            listContainer: null,
            modal: null,
            form: null,
            loadingOverlay: null,
            filters: {
                organization: null,
                role: null,
                applyBtn: null
            }
        },
        
        // Estado atual
        state: {
            currentFilters: {
                organizacao_id: '',
                papel: ''
            },
            isLoading: false,
            availableUsers: [],
            availableOrganizations: [],
            userFilterTimeout: null // Para debounce do filtro de usuários
        },
        
        // Inicialização
        init: function() {
            this.cacheDOMElements();
            this.bindEvents();
            this.loadInitialData();
            // Aplicar filtros iniciais
            this.applyFilters();
        },
        
        // Cache elementos DOM
        cacheDOMElements: function() {
            this.elements.container = $('#sevo-papeis-container');
            this.elements.listContainer = $('#sevo-papeis-list-container');
            this.elements.modal = $('#sevo-user-role-modal');
            this.elements.form = $('#sevo-user-role-form');
            this.elements.loadingOverlay = $('#sevo-loading-overlay');
            
            // Filtros
            this.elements.filters.organization = $('#sevo-filter-organization');
            this.elements.filters.role = $('#sevo-filter-role');
            this.elements.filters.applyBtn = $('#sevo-apply-filters');
        },
        
        // Vincular eventos
        bindEvents: function() {
            const self = this;
            
            // Botão adicionar usuário
            $('#sevo-add-user-role-btn').on('click', function() {
                self.openModal('create');
            });
            
            // Aplicar filtros
            this.elements.filters.applyBtn.on('click', function() {
                self.applyFilters();
            });
            
            // Filtros com Enter
            this.elements.filters.organization.on('keypress', function(e) {
                if (e.which === 13) {
                    self.applyFilters();
                }
            });
            
            this.elements.filters.role.on('keypress', function(e) {
                if (e.which === 13) {
                    self.applyFilters();
                }
            });
            
            // Eventos do modal
            this.bindModalEvents();
            
            // Eventos da lista
            this.bindListEvents();
        },
        
        // Eventos do modal
        bindModalEvents: function() {
            const self = this;
            
            // Fechar modal
            $('.sevo-modal-close, #sevo-user-role-cancel').on('click', function() {
                self.closeModal();
            });
            
            // Clique fora do modal
            this.elements.modal.on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            // Salvar
            $('#sevo-user-role-save').on('click', function() {
                self.saveUserRole();
            });
            
            // Escape para fechar
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.elements.modal.is(':visible')) {
                    self.closeModal();
                }
            });
            
            // Adicionar evento de filtro para usuários
            $('#user-role-user-filter').on('input', function() {
                const filter = $(this).val();
                const self = SevoPapeis;
                
                // Cancelar timeout anterior, se existir
                if (self.state.userFilterTimeout) {
                    clearTimeout(self.state.userFilterTimeout);
                }
                
                // Definir novo timeout
                self.state.userFilterTimeout = setTimeout(function() {
                    self.renderUserOptions(filter);
                }, 300); // 300ms de debounce
            });
            
            // Limpar filtro quando o modal é aberto
            $('#sevo-add-user-role-btn').on('click', function() {
                $('#user-role-user-filter').val('');
            });
        },
        
        // Eventos da lista
        bindListEvents: function() {
            const self = this;
            
            // Eventos delegados para botões que são criados dinamicamente
            this.elements.listContainer.on('click', '.sevo-edit-role', function() {
                const data = $(this).data();
                self.openModal('edit', data);
            });
            
            this.elements.listContainer.on('click', '.sevo-remove-role', function() {
                const id = $(this).data('id');
                self.removeUserRole(id);
            });
        },
        
        // Carregar dados iniciais
        loadInitialData: function() {
            this.loadOrganizationOptions();
            this.loadUserRoles();
        },
        
        // Carregar opções de organizações
        loadOrganizationOptions: function() {
            const self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_frontend_get_editor_organizations',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    console.log('loadOrganizationOptions response:', response);
                    if (response.success) {
                        self.elements.filters.organization.html(response.data.options);
                        
                        // Também atualizar as opções do modal se existir
                        $('#user-role-organization-id').html(
                            response.data.options.replace('Todas as organizações', 'Selecione uma organização')
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.log('loadOrganizationOptions error:', xhr, status, error);
                    self.showError(self.config.strings.error);
                }
            });
        },
        
        // Carregar lista de papéis
        loadUserRoles: function() {
            const self = this;
            
            if (this.state.isLoading) return;
            
            this.state.isLoading = true;
            this.showLoading();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_frontend_get_user_roles',
                    nonce: this.config.nonce,
                    organizacao_id: this.state.currentFilters.organizacao_id,
                    papel: this.state.currentFilters.papel
                },
                success: function(response) {
                    console.log('loadUserRoles response:', response);
                    self.state.isLoading = false;
                    self.hideLoading();
                    
                    if (response.success) {
                        self.elements.listContainer.html(response.data.html);
                    } else {
                        self.showError(response.data || self.config.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('loadUserRoles error:', xhr, status, error);
                    self.state.isLoading = false;
                    self.hideLoading();
                    self.showError(self.config.strings.error);
                }
            });
        },
        
        // Aplicar filtros
        applyFilters: function() {
            this.state.currentFilters.organizacao_id = this.elements.filters.organization.val();
            this.state.currentFilters.papel = this.elements.filters.role.val();
            this.loadUserRoles();
        },
        
        // Abrir modal
        openModal: function(action, data) {
            const self = this;
            
            // Limpar filtro de usuário quando o modal é aberto
            $('#user-role-user-filter').val('');
            
            // Configurar modal
            if (action === 'edit' && data) {
                $('#sevo-user-role-modal-title').text('Editar Papel');
                $('#user-role-id').val(data.id);
                $('#user-role-action').val('edit');
                $('#user-role-user-id').val(data.usuarioId).prop('disabled', true);
                $('#user-role-organization-id').val(data.organizacaoId).prop('disabled', true);
                $('#user-role-papel').val(data.papel);
                $('#user-role-observacoes').val(data.observacoes);
                
                // Preencher o filtro de usuário com o nome do usuário
                if (data.usuarioNome) {
                    $('#user-role-user-filter').val(data.usuarioNome);
                }
            } else {
                $('#sevo-user-role-modal-title').text('Adicionar Usuário');
                $('#user-role-id').val('');
                $('#user-role-action').val('create');
                $('#user-role-user-id').prop('disabled', false);
                $('#user-role-organization-id').prop('disabled', false);
                this.elements.form[0].reset();
                
                // Carregar usuários disponíveis apenas quando adicionando
                this.loadAvailableUsers();
            }
            
            this.elements.modal.fadeIn(300);
            $('body').addClass('sevo-modal-open');
        },
        
        // Fechar modal
        closeModal: function() {
            this.elements.modal.fadeOut(300);
            $('body').removeClass('sevo-modal-open');
            this.elements.form[0].reset();
            // Limpar filtro de usuário
            $('#user-role-user-filter').val('');
        },
        
        // Carregar usuários disponíveis
        loadAvailableUsers: function() {
            const self = this;
            
            // Adicionar loading indicator
            $('#user-role-user-id').html('<option>' + self.config.strings.loading + '</option>');
            
            // Usar paginação para evitar timeout
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_frontend_get_available_users',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    console.log('loadAvailableUsers response:', response);
                    if (response.success) {
                        $('#user-role-user-id').html(response.data.options);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('loadAvailableUsers error:', xhr, status, error);
                    self.showError(self.config.strings.error);
                }
            });
        },
        
        // Renderizar opções de usuários com base no filtro
        renderUserOptions: function(filter) {
            const self = this;
            const selectElement = $('#user-role-user-id');
            
            // Adicionar loading indicator
            selectElement.html('<option>' + self.config.strings.loading + '</option>');
            
            // Fazer uma nova requisição AJAX com o termo de busca
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_frontend_get_available_users',
                    nonce: this.config.nonce,
                    search: filter
                },
                success: function(response) {
                    if (response.success) {
                        selectElement.html(response.data.options);
                    } else {
                        selectElement.html('<option value="">' + (response.data || self.config.strings.error) + '</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('renderUserOptions error:', xhr, status, error);
                    selectElement.html('<option value="">' + self.config.strings.error + '</option>');
                }
            });
        },
        
        // Salvar papel de usuário
        saveUserRole: function() {
            const self = this;
            const formData = new FormData(this.elements.form[0]);
            const action = $('#user-role-action').val();
            
            // Validação básica
            if (!formData.get('usuario_id') || !formData.get('organizacao_id') || !formData.get('papel')) {
                this.showError('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            // Preparar dados
            const ajaxData = {
                nonce: this.config.nonce
            };
            
            // Adicionar campos do formulário
            for (let [key, value] of formData.entries()) {
                ajaxData[key] = value;
            }
            
            // Definir ação AJAX
            if (action === 'edit') {
                ajaxData.action = 'sevo_frontend_update_user_role';
            } else {
                ajaxData.action = 'sevo_frontend_add_user_role';
            }
            
            this.showLoading();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccess(response.data.message);
                        self.closeModal();
                        self.loadUserRoles();
                    } else {
                        self.showError(response.data || self.config.strings.error);
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showError(self.config.strings.error);
                }
            });
        },
        
        // Remover papel de usuário
        removeUserRole: function(id) {
            const self = this;
            
            if (!confirm(this.config.strings.confirm_remove)) {
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_frontend_remove_user_role',
                    nonce: this.config.nonce,
                    id: id
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccess(response.data.message);
                        self.loadUserRoles();
                    } else {
                        self.showError(response.data || self.config.strings.error);
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showError(self.config.strings.error);
                }
            });
        },
        
        // Mostrar loading
        showLoading: function() {
            this.elements.loadingOverlay.show();
        },
        
        // Esconder loading
        hideLoading: function() {
            this.elements.loadingOverlay.hide();
        },
        
        // Mostrar erro
        showError: function(message) {
            this.showNotification(message, 'error');
        },
        
        // Mostrar sucesso
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },
        
        // Mostrar notificação
        showNotification: function(message, type) {
            const notification = $('<div class="sevo-notification sevo-notification-' + type + '">');
            notification.html('<span>' + message + '</span><button class="sevo-notification-close">&times;</button>');
            
            $('body').append(notification);
            
            // Auto-remover após 5 segundos
            setTimeout(function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Botão fechar
            notification.find('.sevo-notification-close').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };
    
    // Expor globalmente para uso em outras partes
    window.SevoPapeis = SevoPapeis;
    
    // Inicializar quando documento estiver pronto
    $(document).ready(function() {
        // Verificar se estamos na página correta
        if ($('#sevo-papeis-container').length > 0) {
            SevoPapeis.init();
        }
    });
    
})(jQuery);
