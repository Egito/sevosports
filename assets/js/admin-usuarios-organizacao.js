/**
 * JavaScript para administração de usuários por organização
 * Gerencia vínculos entre usuários e organizações
 */

(function($) {
    'use strict';
    
    var SevoUsuarioOrgAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadUsuariosOrg();
        },
        
        bindEvents: function() {
            // Botão adicionar novo vínculo
            $('#sevo-add-usuario-org-btn').on('click', this.showCreateModal);
            
            // Fechar modal
            $('.sevo-modal-close, #sevo-usuario-org-cancel').on('click', this.hideModal);
            
            // Salvar vínculo
            $('#sevo-usuario-org-save').on('click', this.saveUsuarioOrg);
            
            // Editar vínculo (delegated event)
            $(document).on('click', '.sevo-edit-usuario-org', this.showEditModal);
            
            // Remover vínculo (delegated event)
            $(document).on('click', '.sevo-delete-usuario-org', this.deleteUsuarioOrg);
            
            // Paginação (delegated event)
            $(document).on('click', '.sevo-page-btn', this.changePage);
            
            // Fechar modal ao clicar fora
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('sevo-modal')) {
                    SevoUsuarioOrgAdmin.hideModal();
                }
            });
        },
        
        showCreateModal: function() {
            $('#sevo-usuario-org-modal-title').text('Novo Vínculo Usuário-Organização');
            var form = $('#sevo-usuario-org-form')[0];
            if (form) {
                form.reset();
            }
            $('#usuario-org-id').val('');
            $('#usuario-org-status').val('ativo');
            $('#sevo-usuario-org-modal').show();
        },
        
        showEditModal: function() {
            var vinculoId = $(this).data('id');
            
            $.ajax({
                url: sevoUsuarioOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_usuario_org',
                    id: vinculoId,
                    nonce: sevoUsuarioOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var vinculo = response.data;
                        
                        $('#sevo-usuario-org-modal-title').text('Editar Vínculo Usuário-Organização');
                        $('#usuario-org-id').val(vinculo.id);
                        $('#usuario-org-usuario-id').val(vinculo.usuario_id);
                        $('#usuario-org-organizacao-id').val(vinculo.organizacao_id);
                        $('#usuario-org-papel').val(vinculo.papel);
                        $('#usuario-org-observacoes').val(vinculo.observacoes || '');
                        $('#usuario-org-status').val(vinculo.status);
                        
                        $('#sevo-usuario-org-modal').show();
                    } else {
                        SevoUsuarioOrgAdmin.showNotice(response.data || sevoUsuarioOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoUsuarioOrgAdmin.showNotice(sevoUsuarioOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        hideModal: function() {
            $('#sevo-usuario-org-modal').hide();
            var form = $('#sevo-usuario-org-form')[0];
            if (form) {
                form.reset();
            }
        },
        
        saveUsuarioOrg: function() {
            var formData = $('#sevo-usuario-org-form').serialize();
            var vinculoId = $('#usuario-org-id').val();
            var action = vinculoId ? 'sevo_update_usuario_org' : 'sevo_create_usuario_org';
            
            // Validação básica
            if (!$('#usuario-org-usuario-id').val()) {
                SevoUsuarioOrgAdmin.showNotice('Usuário é obrigatório.', 'error');
                return;
            }
            
            if (!$('#usuario-org-organizacao-id').val()) {
                SevoUsuarioOrgAdmin.showNotice('Organização é obrigatória.', 'error');
                return;
            }
            
            if (!$('#usuario-org-papel').val()) {
                SevoUsuarioOrgAdmin.showNotice('Papel é obrigatório.', 'error');
                return;
            }
            
            $('#sevo-usuario-org-save').prop('disabled', true).text('Salvando...');
            
            $.ajax({
                url: sevoUsuarioOrgAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + sevoUsuarioOrgAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        SevoUsuarioOrgAdmin.showNotice(response.data.message, 'success');
                        SevoUsuarioOrgAdmin.hideModal();
                        SevoUsuarioOrgAdmin.loadUsuariosOrg();
                    } else {
                        SevoUsuarioOrgAdmin.showNotice(response.data || sevoUsuarioOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoUsuarioOrgAdmin.showNotice(sevoUsuarioOrgAdmin.strings.error, 'error');
                },
                complete: function() {
                    $('#sevo-usuario-org-save').prop('disabled', false).text('Salvar');
                }
            });
        },
        
        deleteUsuarioOrg: function() {
            var vinculoId = $(this).data('id');
            
            if (!confirm(sevoUsuarioOrgAdmin.strings.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: sevoUsuarioOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_delete_usuario_org',
                    id: vinculoId,
                    nonce: sevoUsuarioOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoUsuarioOrgAdmin.showNotice(response.data.message, 'success');
                        SevoUsuarioOrgAdmin.loadUsuariosOrg();
                    } else {
                        SevoUsuarioOrgAdmin.showNotice(response.data || sevoUsuarioOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoUsuarioOrgAdmin.showNotice(sevoUsuarioOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        loadUsuariosOrg: function(page) {
            page = page || 1;
            console.log('SevoUsuarioOrgAdmin: Carregando vínculos, página:', page);
            
            $.ajax({
                url: sevoUsuarioOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_list_usuarios_org',
                    page: page,
                    per_page: 20,
                    nonce: sevoUsuarioOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sevo-usuario-org-list-container').html(response.data.html);
                    } else {
                        SevoUsuarioOrgAdmin.showNotice(response.data || sevoUsuarioOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoUsuarioOrgAdmin.showNotice(sevoUsuarioOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        changePage: function() {
            var page = $(this).data('page');
            SevoUsuarioOrgAdmin.loadUsuariosOrg(page);
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            // Remove avisos anteriores
            $('.sevo-admin-notice').remove();
            
            var noticeClass = 'notice notice-' + type;
            if (type === 'success') {
                noticeClass = 'notice notice-success is-dismissible';
            } else if (type === 'error') {
                noticeClass = 'notice notice-error is-dismissible';
            }
            
            var notice = $('<div class="sevo-admin-notice ' + noticeClass + '"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto remover após 5 segundos
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
    };
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        if (typeof sevoUsuarioOrgAdmin !== 'undefined') {
            SevoUsuarioOrgAdmin.init();
        }
    });
    
})(jQuery);