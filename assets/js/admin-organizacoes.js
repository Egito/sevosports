/**
 * JavaScript para administração de organizações
 * Gerencia CRUD via AJAX usando tabelas customizadas
 */

(function($) {
    'use strict';
    
    var SevoOrgAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadOrganizacoes();
        },
        
        bindEvents: function() {
            // Botão adicionar nova organização
            $('#sevo-add-org-btn').on('click', this.showCreateModal);
            
            // Fechar modal
            $('.sevo-modal-close, #sevo-org-cancel').on('click', this.hideModal);
            
            // Salvar organização
            $('#sevo-org-save').on('click', this.saveOrganizacao);
            
            // Editar organização (delegated event)
            $(document).on('click', '.sevo-edit-org', this.showEditModal);
            
            // Excluir organização (delegated event)
            $(document).on('click', '.sevo-delete-org', this.deleteOrganizacao);
            
            // Paginação (delegated event)
            $(document).on('click', '.sevo-page-btn', this.changePage);
            
            // Fechar modal ao clicar fora
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('sevo-modal')) {
                    SevoOrgAdmin.hideModal();
                }
            });
            
            // Eventos de formulário configurados
        },
        
        showCreateModal: function() {
            $('#sevo-org-modal-title').text(sevoOrgAdmin.strings.add_new || 'Nova Organização');
            $('#sevo-org-form')[0].reset();
            $('#org-id').val('');
            // Limpar mensagens de erro
            $('.sevo-notice').remove();
            $('#sevo-org-modal').show();
        },
        
        showEditModal: function() {
            var orgId = $(this).data('id');
            
            $.ajax({
                url: sevoOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_organizacao',
                    id: orgId,
                    nonce: sevoOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var org = response.data;
                        
                        $('#sevo-org-modal-title').text('Editar Organização');
                        $('input[name="org_id"]').val(org.id);
                        $('#org_titulo').val(org.titulo);
                        $('#org_descricao').val(org.descricao);
                        $('#org_autor').val(org.autor_id);
                        $('#org_status').val(org.status);
                        $('#org_imagem_url').val(org.imagem_url || '');
                        
                        // Atualizar preview da imagem
                        if (org.imagem_url) {
                            $('#current-image-preview').show();
                            $('#current-image-preview img').attr('src', org.imagem_url);
                            $('#remove-image-btn').show();
                        } else {
                            $('#current-image-preview').hide();
                            $('#remove-image-btn').hide();
                        }
                        
                        $('#sevo-org-modal').show();
                    } else {
                        SevoOrgAdmin.showNotice(response.data || sevoOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoOrgAdmin.showNotice(sevoOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        hideModal: function() {
            $('#sevo-org-modal').hide();
            // Limpar formulário
            $('#sevo-org-form')[0].reset();
            $('input[name="org_id"]').val('');
            $('#current-image-preview').hide();
            $('#remove-image-btn').hide();
            // Limpar mensagens de erro
            $('.sevo-notice').remove();
        },
        
        closeModal: function() {
            this.hideModal();
        },
        
        saveOrganizacao: function() {
            var formData = $('#sevo-org-form').serialize();
            var orgId = $('input[name="org_id"]').val();
            var action = orgId ? 'sevo_update_organizacao' : 'sevo_create_organizacao';
            
            // Validação básica - usando os IDs corretos dos campos
            var titulo = $('#org_titulo').val();
            var autor = $('#org_autor').val();
            
            if (!titulo || titulo.trim() === '') {
                SevoOrgAdmin.showNotice('Nome da organização é obrigatório.', 'error');
                $('#org_titulo').focus();
                return;
            }
            
            if (!autor) {
                SevoOrgAdmin.showNotice('Autor é obrigatório.', 'error');
                $('#org_autor').focus();
                return;
            }
            
            $('#sevo-org-save').prop('disabled', true).text('Salvando...');
            
            $.ajax({
                url: sevoOrgAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&nonce=' + sevoOrgAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        SevoOrgAdmin.showNotice(response.data.message, 'success');
                        SevoOrgAdmin.hideModal();
                        SevoOrgAdmin.loadOrganizacoes();
                    } else {
                        SevoOrgAdmin.showNotice(response.data || sevoOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoOrgAdmin.showNotice(sevoOrgAdmin.strings.error, 'error');
                },
                complete: function() {
                    $('#sevo-org-save').prop('disabled', false).text(orgId ? 'Atualizar' : 'Criar');
                }
            });
        },
        
        deleteOrganizacao: function() {
            var orgId = $(this).data('id');
            
            if (!confirm(sevoOrgAdmin.strings.confirm_delete)) {
                return;
            }
            
            $.ajax({
                url: sevoOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_delete_organizacao',
                    id: orgId,
                    nonce: sevoOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoOrgAdmin.showNotice(response.data.message, 'success');
                        SevoOrgAdmin.loadOrganizacoes();
                    } else {
                        SevoOrgAdmin.showNotice(response.data || sevoOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoOrgAdmin.showNotice(sevoOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        loadOrganizacoes: function(page) {
            page = page || 1;
            
            $.ajax({
                url: sevoOrgAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_list_organizacoes',
                    page: page,
                    per_page: 20,
                    nonce: sevoOrgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sevo-org-list-container').html(response.data.html);
                    } else {
                        SevoOrgAdmin.showNotice(response.data || sevoOrgAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    SevoOrgAdmin.showNotice(sevoOrgAdmin.strings.error, 'error');
                }
            });
        },
        
        changePage: function() {
            var page = $(this).data('page');
            SevoOrgAdmin.loadOrganizacoes(page);
        },
        
        showNotice: function(message, type) {
            type = type || 'info';
            
            // Remove notices existentes
            $('.sevo-notice').remove();
            
            var noticeClass = 'notice notice-' + type;
            if (type === 'error') {
                noticeClass += ' notice-error';
            } else if (type === 'success') {
                noticeClass += ' notice-success';
            }
            
            var notice = $('<div class="' + noticeClass + ' sevo-notice is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto-remove após 5 segundos
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Funcionalidade de upload de imagem - namespace isolado
    var sevoOrgMediaUploader;
    
    $(document).on('click', '#upload-image-btn', function(e) {
        e.preventDefault();
        
        // Debug detalhado
        console.log('Botão upload clicado - organizações');
        console.log('wp object:', typeof wp);
        console.log('wp.media object:', typeof wp !== 'undefined' ? typeof wp.media : 'wp undefined');
        
        // Verificar se wp.media está disponível
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            console.error('wp.media não está disponível para organizações');
            console.error('Scripts carregados:', Object.keys(window));
            alert('Erro: Biblioteca de mídia não carregada. Recarregue a página e tente novamente.');
            return;
        }
        
        // Verificar se já existe uma instância
        if (sevoOrgMediaUploader) {
            sevoOrgMediaUploader.open();
            return;
        }
        
        // Criar nova instância com configurações específicas para organizações
        sevoOrgMediaUploader = wp.media({
            title: 'Selecionar Imagem da Organização',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false,
            library: {
                type: 'image'
            },
            frame: 'select'
        });
        
        sevoOrgMediaUploader.on('select', function() {
            var attachment = sevoOrgMediaUploader.state().get('selection').first().toJSON();
            $('#org_imagem_url').val(attachment.url);
            
            // Mostrar preview
            $('#current-image-preview').show();
            $('#current-image-preview p').text('Imagem selecionada:');
            $('#current-image-preview img, #preview-img').attr('src', attachment.url);
            
            // Mostrar botão remover
            $('#remove-image-btn').show();
        });
        
        sevoOrgMediaUploader.open();
    });
    
    // Remover imagem
    $(document).on('click', '#remove-image-btn', function(e) {
        e.preventDefault();
        
        $('#org_imagem_url').val('');
        $('#current-image-preview').hide();
        $('#remove-image-btn').hide();
    });
    
    // Preview da URL quando digitada manualmente
    $(document).on('input', '#org_imagem_url', function() {
        var url = $(this).val();
        if (url && url.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
            $('#current-image-preview').show();
            $('#current-image-preview p').text('Imagem selecionada:');
            $('#current-image-preview img, #preview-img').attr('src', url);
            $('#remove-image-btn').show();
        } else if (!url) {
            $('#current-image-preview').hide();
            $('#remove-image-btn').hide();
        }
    });
    
    // Event listeners para o modal
    $(document).on('click', '#sevo-org-cancel, .sevo-modal-close', function() {
        SevoOrgAdmin.closeModal();
    });
    
    $(document).on('click', '#sevo-org-save', function() {
        SevoOrgAdmin.saveOrganizacao();
    });
    
    $(document).on('submit', '#sevo-org-form', function(e) {
        e.preventDefault();
        SevoOrgAdmin.saveOrganizacao();
    });
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        SevoOrgAdmin.init();
    });
    
})(jQuery);