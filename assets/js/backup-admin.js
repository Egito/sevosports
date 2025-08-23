/**
 * JavaScript para administração do sistema de backup Sevo Eventos
 * 
 * @package Sevo_Eventos
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    var SevoBackupAdmin = {
        
        /**
         * Inicialização
         */
        init: function() {
            this.bindEvents();
            this.refreshStatus();
            
            // Auto-refresh a cada 30 segundos
            setInterval(this.refreshStatus.bind(this), 30000);
        },
        
        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Backup manual
            $(document).on('click', '#manual-backup-btn', this.executeManualBackup.bind(this));
            
            // Refresh status
            $(document).on('click', '#refresh-status-btn', this.refreshStatus.bind(this));
            
            // Delete backup
            $(document).on('click', '.sevo-delete-backup', this.deleteBackup.bind(this));
            
            // Confirmar antes de sair durante backup
            window.addEventListener('beforeunload', function(e) {
                if ($('#backup-progress').is(':visible')) {
                    e.preventDefault();
                    e.returnValue = 'Um backup está em andamento. Tem certeza que deseja sair?';
                    return e.returnValue;
                }
            });
        },
        
        /**
         * Executar backup manual
         */
        executeManualBackup: function(e) {
            e.preventDefault();
            
            var $btn = $('#manual-backup-btn');
            var $progress = $('#backup-progress');
            var $progressFill = $('.sevo-progress-fill');
            var $progressText = $('.sevo-progress-text');
            
            // Confirmar ação
            if (!confirm('Executar backup manual agora?\n\nEste processo pode levar alguns minutos.')) {
                return;
            }
            
            // Estado de loading
            $btn.addClass('loading').prop('disabled', true).text('⏳ Executando...');
            $progress.show();
            $progressFill.css('width', '0%');
            $progressText.text('Preparando backup...');
            
            // Simular progresso
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 10;
                if (progress > 85) progress = 85;
                
                $progressFill.css('width', progress + '%');
                
                if (progress < 20) {
                    $progressText.text('Exportando dados do fórum...');
                } else if (progress < 40) {
                    $progressText.text('Exportando dados dos eventos...');
                } else if (progress < 60) {
                    $progressText.text('Exportando dados do WordPress...');
                } else if (progress < 80) {
                    $progressText.text('Processando imagens...');
                } else {
                    $progressText.text('Finalizando backup...');
                }
            }, 800);
            
            // Fazer requisição AJAX
            $.ajax({
                url: sevoBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_manual_backup',
                    nonce: sevoBackup.nonce
                },
                timeout: 300000, // 5 minutos
                success: function(response) {
                    clearInterval(progressInterval);
                    $progressFill.css('width', '100%');
                    $progressText.text('Backup concluído!');
                    
                    setTimeout(function() {
                        $btn.removeClass('loading').prop('disabled', false).text('🚀 Executar Backup Manual');
                        $progress.hide();
                        
                        if (response.success) {
                            SevoBackupAdmin.showMessage('✅ Backup executado com sucesso!\\n\\nArquivo: ' + response.data.filename, 'success');
                            SevoBackupAdmin.refreshBackupList();
                        } else {
                            SevoBackupAdmin.showMessage('❌ Erro ao executar backup:\\n\\n' + (response.data.message || 'Erro desconhecido'), 'error');
                        }
                    }, 1500);
                },
                error: function(xhr, status, error) {
                    clearInterval(progressInterval);
                    $btn.removeClass('loading').prop('disabled', false).text('🚀 Executar Backup Manual');
                    $progress.hide();
                    
                    var errorMsg = 'Erro de conexão';
                    if (status === 'timeout') {
                        errorMsg = 'Timeout - o backup pode estar sendo processado em segundo plano';
                    }
                    
                    SevoBackupAdmin.showMessage('❌ ' + errorMsg + ':\\n\\n' + error, 'error');
                }
            });
        },
        
        /**
         * Atualizar status do backup
         */
        refreshStatus: function() {
            $.ajax({
                url: sevoBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_backup_status',
                    nonce: sevoBackup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoBackupAdmin.updateStatusDisplay(response.data);
                    }
                },
                error: function() {
                    // Falha silenciosa no refresh automático
                }
            });
        },
        
        /**
         * Deletar backup
         */
        deleteBackup: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var filename = $btn.data('filename');
            var $row = $btn.closest('tr');
            
            if (!confirm('Tem certeza que deseja remover este backup?\\n\\nArquivo: ' + filename + '\\n\\nEsta ação não pode ser desfeita.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('⏳ Removendo...');
            
            $.ajax({
                url: sevoBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_delete_backup',
                    filename: filename,
                    nonce: sevoBackup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $row.remove();
                            
                            // Verificar se não há mais backups
                            if ($('#backup-list-tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                        SevoBackupAdmin.showMessage('✅ Backup removido com sucesso!', 'success');
                        SevoBackupAdmin.refreshStatus();
                    } else {
                        $btn.prop('disabled', false).text('🗑️ Remover');
                        SevoBackupAdmin.showMessage('❌ Erro ao remover backup:\\n\\n' + (response.data.message || 'Erro desconhecido'), 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('🗑️ Remover');
                    SevoBackupAdmin.showMessage('❌ Erro de conexão ao remover backup', 'error');
                }
            });
        },
        
        /**
         * Atualizar exibição do status
         */
        updateStatusDisplay: function(status) {
            $('#next-backup-time').text(status.next_scheduled);
            $('#total-backups').text(status.total_backups);
            $('#last-backup').text(status.last_backup ? status.last_backup.date : 'Nenhum backup realizado');
        },
        
        /**
         * Atualizar lista de backups
         */
        refreshBackupList: function() {
            // Recarregar a página para atualizar a lista
            setTimeout(function() {
                location.reload();
            }, 2000);
        },
        
        /**
         * Exibir mensagem
         */
        showMessage: function(message, type) {
            // Remover mensagem existente
            $('.sevo-message').remove();
            
            // Criar nova mensagem
            var $message = $('<div class=\"sevo-message ' + type + '\"></div>');
            $message.text(message);
            
            // Adicionar antes do primeiro card
            $('.sevo-backup-cards').before($message);
            
            // Auto-remover após 5 segundos
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 5000);
            
            // Scroll para o topo
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        },
        
        /**
         * Formatar bytes
         */
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';
            
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        /**
         * Validar arquivo de backup
         */
        validateBackupFile: function(filename) {
            var pattern = /^sevo_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/;
            return pattern.test(filename);
        }
    };
    
    // Inicializar quando document estiver pronto
    $(document).ready(function() {
        // Verificar se estamos na página de backup
        if ($('.sevo-backup-admin').length > 0) {
            SevoBackupAdmin.init();
        }
    });
    
    // Expor para uso global se necessário
    window.SevoBackupAdmin = SevoBackupAdmin;
    
})(jQuery);