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
    
    // Sistema moderno de upload de imagem
    var imageUploadHandler = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Clique no botão de upload
            $(document).on('click', '#upload-image-btn', this.openFileDialog.bind(this));
            
            // Clique na área de preview para upload
            $(document).on('click', '#image-placeholder', this.openFileDialog.bind(this));
            
            // Mudança no input de arquivo
            $(document).on('change', '#image-file-input', this.handleFileSelect.bind(this));
            
            // Botões de remoção
            $(document).on('click', '#remove-image-btn, #remove-image-action', this.removeImage.bind(this));
        },
        
        openFileDialog: function(e) {
            e.preventDefault();
            $('#image-file-input').click();
        },
        
        handleFileSelect: function(e) {
            var file = e.target.files[0];
            if (!file) return;
            
            // Validar tipo de arquivo
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/i)) {
                alert('Por favor, selecione um arquivo de imagem válido (JPG, PNG, GIF ou WebP).');
                return;
            }
            
            // Validar tamanho (máximo 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('A imagem é muito grande. Por favor, selecione uma imagem menor que 5MB.');
                return;
            }
            
            this.processImage(file);
        },
        
        processImage: function(file) {
            var self = this;
            var $container = $('#image-preview-container');
            
            // Mostrar estado de loading
            $container.addClass('loading');
            $container.html('<div class="sevo-image-placeholder"><i class="dashicons dashicons-update"></i><p>Processando imagem...</p></div>');
            
            // Criar canvas para redimensionar
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var img = new Image();
            
            img.onload = function() {
                // Definir tamanho do canvas (300x300)
                canvas.width = 300;
                canvas.height = 300;
                
                // Calcular dimensões mantendo proporção
                var scale = Math.min(300 / img.width, 300 / img.height);
                var newWidth = img.width * scale;
                var newHeight = img.height * scale;
                
                // Centralizar imagem no canvas
                var x = (300 - newWidth) / 2;
                var y = (300 - newHeight) / 2;
                
                // Preencher fundo branco
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 300, 300);
                
                // Desenhar imagem redimensionada
                ctx.drawImage(img, x, y, newWidth, newHeight);
                
                // Converter para blob
                canvas.toBlob(function(blob) {
                    self.uploadProcessedImage(blob);
                }, 'image/jpeg', 0.9);
            };
            
            img.onerror = function() {
                alert('Erro ao processar a imagem. Tente novamente.');
                self.resetUploadState();
            };
            
            // Carregar imagem
            var reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        
        uploadProcessedImage: function(blob) {
            var self = this;
            var formData = new FormData();
            
            formData.append('action', 'sevo_upload_org_image');
            formData.append('nonce', sevoOrgAdmin.nonce);
            formData.append('image', blob, 'organization-image.jpg');
            
            $.ajax({
                url: sevoOrgAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showUploadedImage(response.data.url);
                        $('#org_imagem_url').val(response.data.url);
                    } else {
                        alert('Erro ao fazer upload: ' + (response.data || 'Erro desconhecido'));
                        self.resetUploadState();
                    }
                },
                error: function() {
                    alert('Erro de conexão. Tente novamente.');
                    self.resetUploadState();
                }
            });
        },
        
        showUploadedImage: function(imageUrl) {
            var $container = $('#image-preview-container');
            $container.removeClass('loading');
            
            var html = '<img src="' + imageUrl + '" alt="Imagem carregada" id="preview-image">' +
                      '<button type="button" class="sevo-remove-image" id="remove-image-btn" title="Remover imagem">×</button>';
            
            $container.html(html);
            
            // Atualizar botões de ação
            $('#upload-image-btn').html('<i class="dashicons dashicons-upload"></i> Alterar Imagem');
            
            // Mostrar botão de remoção se não existir
            if ($('#remove-image-action').length === 0) {
                $('.sevo-upload-actions').append(
                    '<button type="button" id="remove-image-action" class="sevo-btn sevo-btn-danger">' +
                    '<i class="dashicons dashicons-trash"></i> Remover</button>'
                );
            }
        },
        
        removeImage: function(e) {
            e.preventDefault();
            
            if (confirm('Tem certeza que deseja remover a imagem?')) {
                this.resetUploadState();
                $('#org_imagem_url').val('');
            }
        },
        
        resetUploadState: function() {
            var $container = $('#image-preview-container');
            $container.removeClass('loading');
            
            var html = '<div class="sevo-image-placeholder" id="image-placeholder">' +
                      '<i class="dashicons dashicons-camera"></i>' +
                      '<p>Clique para carregar uma imagem</p>' +
                      '<small>Recomendado: 300x300 pixels</small>' +
                      '</div>';
            
            $container.html(html);
            
            // Atualizar botões
            $('#upload-image-btn').html('<i class="dashicons dashicons-upload"></i> Carregar Imagem');
            $('#remove-image-action').remove();
            
            // Limpar input de arquivo
            $('#image-file-input').val('');
        }
    };
    
    // Inicializar upload de imagem
    imageUploadHandler.init();
    
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