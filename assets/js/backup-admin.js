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
            // Backup manual tradicional
            $(document).on('click', '#manual-backup-btn', this.executeManualBackup.bind(this));
            
            // Backup em chunks
            $(document).on('click', '#chunked-backup-btn', this.executeChunkedBackup.bind(this));
            
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
         * Executar backup em chunks
         */
        executeChunkedBackup: function(e) {
            e.preventDefault();
            
            var $btn = $('#chunked-backup-btn');
            var $progress = $('#backup-progress');
            var $progressFill = $('#progress-fill');
            var $progressText = $('#progress-text');
            var $progressTitle = $('#progress-title');
            var $chunksProgress = $('#chunks-progress');
            var $realtimeLog = $('#realtime-log');
            
            // Confirmar ação
            if (!confirm('Executar backup em pedaços?\n\nEste método é mais lento mas não trava com grandes volumes de dados.')) {
                return;
            }
            
            // Estado de loading
            $btn.addClass('loading').prop('disabled', true).text('⏳ Iniciando...');
            $('#manual-backup-btn').prop('disabled', true);
            $progress.show();
            $chunksProgress.show();
            $realtimeLog.show();
            $progressFill.css('width', '0%');
            $progressText.text('Iniciando sessão de backup...');
            $progressTitle.text('🧩 Backup em Pedaços');
            
            this.logMessage('Iniciando backup em chunks...');
            
            // Iniciar sessão de backup
            $.ajax({
                url: sevoBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_start_chunked_backup',
                    nonce: sevoBackup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoBackupAdmin.logMessage('✅ Sessão iniciada: ' + response.data.session_id);
                        SevoBackupAdmin.processChunksSequentially(
                            response.data.session_id,
                            response.data.chunks,
                            $btn
                        );
                    } else {
                        SevoBackupAdmin.handleChunkedBackupError($btn, response.data.message || 'Erro ao iniciar sessão');
                    }
                },
                error: function(xhr, status, error) {
                    SevoBackupAdmin.handleChunkedBackupError($btn, 'Erro de conexão: ' + error);
                }
            });
        },
        
        /**
         * Processar chunks sequencialmente
         */
        processChunksSequentially: function(sessionId, chunks, $btn) {
            var self = this;
            var currentChunk = 0;
            var totalChunks = chunks.length;
            var $progressFill = $('#progress-fill');
            var $progressText = $('#progress-text');
            
            // Exibir lista de chunks
            this.displayChunksList(chunks);
            
            function processNextChunk() {
                if (currentChunk >= totalChunks) {
                    // Todos os chunks processados, finalizar backup
                    self.finalizeChunkedBackup(sessionId, $btn);
                    return;
                }
                
                var chunk = chunks[currentChunk];
                var progress = Math.round(((currentChunk + 1) / totalChunks) * 100);
                
                // Atualizar UI
                $progressFill.css('width', progress + '%');
                $progressText.text('Processando: ' + chunk.name + ' (' + (currentChunk + 1) + '/' + totalChunks + ')');
                
                self.logMessage('🛠️ Processando chunk: ' + chunk.name);
                self.updateChunkStatus(currentChunk, 'processing');
                
                // Processar chunk atual
                $.ajax({
                    url: sevoBackup.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sevo_process_backup_chunk',
                        session_id: sessionId,
                        chunk_type: chunk.type,
                        chunk_number: currentChunk,
                        nonce: sevoBackup.nonce
                    },
                    timeout: 120000, // 2 minutos por chunk
                    success: function(response) {
                        if (response.success) {
                            self.logMessage('✅ ' + chunk.name + ' concluído em ' + (response.data.elapsed_time || 0) + 's');
                            self.updateChunkStatus(currentChunk, 'completed');
                            
                            currentChunk++;
                            setTimeout(processNextChunk, 500); // Pausa de 500ms entre chunks
                        } else {
                            self.logMessage('❌ Erro no chunk ' + chunk.name + ': ' + (response.data.message || 'Erro desconhecido'));
                            self.updateChunkStatus(currentChunk, 'error');
                            
                            // Continuar com próximo chunk mesmo com erro
                            currentChunk++;
                            setTimeout(processNextChunk, 1000);
                        }
                    },
                    error: function(xhr, status, error) {
                        self.logMessage('❌ Erro de conexão no chunk ' + chunk.name + ': ' + error);
                        self.updateChunkStatus(currentChunk, 'error');
                        
                        // Continuar com próximo chunk
                        currentChunk++;
                        setTimeout(processNextChunk, 1000);
                    }
                });
            }
            
            // Iniciar processamento do primeiro chunk
            processNextChunk();
        },
        
        /**
         * Finalizar backup em chunks
         */
        finalizeChunkedBackup: function(sessionId, $btn) {
            var self = this;
            var $progressFill = $('#progress-fill');
            var $progressText = $('#progress-text');
            var $progress = $('#backup-progress');
            
            $progressText.text('Finalizando backup e criando arquivo ZIP...');
            this.logMessage('📋 Finalizando backup...');
            
            $.ajax({
                url: sevoBackup.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_finalize_chunked_backup',
                    session_id: sessionId,
                    nonce: sevoBackup.nonce
                },
                timeout: 180000, // 3 minutos para finalizar
                success: function(response) {
                    $progressFill.css('width', '100%');
                    $progressText.text('Backup concluído!');
                    
                    setTimeout(function() {
                        $btn.removeClass('loading').prop('disabled', false).text('🧩 Backup em Pedaços (Recomendado)');
                        $('#manual-backup-btn').prop('disabled', false);
                        $progress.hide();
                        $('#chunks-progress').hide();
                        $('#realtime-log').hide();
                        
                        if (response.success) {
                            self.logMessage('✅ Backup finalizado com sucesso!');
                            SevoBackupAdmin.showMessage('✅ Backup em chunks executado com sucesso!\n\nArquivo: ' + response.data.filename + '\nTamanho: ' + self.formatBytes(response.data.size), 'success');
                            SevoBackupAdmin.refreshBackupList();
                        } else {
                            self.logMessage('❌ Erro ao finalizar: ' + (response.data.message || 'Erro desconhecido'));
                            SevoBackupAdmin.showMessage('❌ Erro ao finalizar backup:\n\n' + (response.data.message || 'Erro desconhecido'), 'error');
                        }
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    self.handleChunkedBackupError($btn, 'Erro ao finalizar backup: ' + error);
                }
            });
        },
        
        /**
         * Exibir lista de chunks
         */
        displayChunksList: function(chunks) {
            var $chunksList = $('#chunks-list');
            $chunksList.empty();
            
            chunks.forEach(function(chunk, index) {
                var $chunkItem = $('<div class="chunk-item" data-chunk="' + index + '">' +
                    '<div class="chunk-status">⏳</div>' +
                    '<div class="chunk-name">' + chunk.name + '</div>' +
                    '<div class="chunk-description">' + chunk.description + '</div>' +
                    '</div>');
                $chunksList.append($chunkItem);
            });
        },
        
        /**
         * Atualizar status do chunk
         */
        updateChunkStatus: function(chunkIndex, status) {
            var $chunkItem = $('.chunk-item[data-chunk="' + chunkIndex + '"]');
            var $status = $chunkItem.find('.chunk-status');
            
            $chunkItem.removeClass('pending processing completed error');
            
            switch(status) {
                case 'processing':
                    $chunkItem.addClass('processing');
                    $status.text('⚙️');
                    break;
                case 'completed':
                    $chunkItem.addClass('completed');
                    $status.text('✅');
                    break;
                case 'error':
                    $chunkItem.addClass('error');
                    $status.text('❌');
                    break;
                default:
                    $chunkItem.addClass('pending');
                    $status.text('⏳');
            }
        },
        
        /**
         * Log em tempo real
         */
        logMessage: function(message) {
            var $logOutput = $('#log-output');
            var timestamp = new Date().toLocaleTimeString();
            
            $logOutput.append('<div class="log-line">[' + timestamp + '] ' + message + '</div>');
            $logOutput.scrollTop($logOutput[0].scrollHeight);
            
            // Manter apenas as últimas 50 linhas
            var $lines = $logOutput.find('.log-line');
            if ($lines.length > 50) {
                $lines.slice(0, $lines.length - 50).remove();
            }
        },
        
        /**
         * Tratar erro do backup em chunks
         */
        handleChunkedBackupError: function($btn, errorMessage) {
            $btn.removeClass('loading').prop('disabled', false).text('🧩 Backup em Pedaços (Recomendado)');
            $('#manual-backup-btn').prop('disabled', false);
            $('#backup-progress').hide();
            $('#chunks-progress').hide();
            $('#realtime-log').hide();
            
            this.logMessage('❌ Erro: ' + errorMessage);
            SevoBackupAdmin.showMessage('❌ Erro no backup em chunks:\n\n' + errorMessage, 'error');
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