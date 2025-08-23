<?php
/**
 * Template da página administrativa de backup do Sevo Eventos
 * 
 * @package Sevo_Eventos
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$backup_manager = Sevo_Backup_Manager::get_instance();
$backup_list = $backup_manager->get_backup_list();
$logs = $backup_manager->get_logs(20);
$next_scheduled = wp_next_scheduled('sevo_backup_cron_hook');
?>

<div class="wrap sevo-backup-admin">
    <h1>🔄 Sistema de Backup Sevo Eventos</h1>
    
    <!-- Status Cards -->
    <div class="sevo-backup-cards">
        <div class="sevo-card sevo-card-primary">
            <div class="sevo-card-header">
                <h3>📊 Status do Sistema</h3>
            </div>
            <div class="sevo-card-body">
                <div class="sevo-status-item">
                    <strong>Próximo backup agendado:</strong>
                    <span id="next-backup-time">
                        <?php echo $next_scheduled ? date('d/m/Y H:i:s', $next_scheduled) : 'Não agendado'; ?>
                    </span>
                </div>
                <div class="sevo-status-item">
                    <strong>Total de backups:</strong>
                    <span id="total-backups"><?php echo count($backup_list); ?></span>
                </div>
                <div class="sevo-status-item">
                    <strong>Último backup:</strong>
                    <span id="last-backup">
                        <?php echo !empty($backup_list) ? $backup_list[0]['date'] : 'Nenhum backup realizado'; ?>
                    </span>
                </div>
                <div class="sevo-status-item">
                    <strong>Sistema ativo:</strong>
                    <span class="sevo-status-active">✅ Funcionando</span>
                </div>
            </div>
        </div>
        
        <div class="sevo-card sevo-card-info">
            <div class="sevo-card-header">
                <h3>⚙️ Configurações</h3>
            </div>
            <div class="sevo-card-body">
                <div class="sevo-config-item">
                    <strong>Frequência:</strong> A cada 6 horas
                </div>
                <div class="sevo-config-item">
                    <strong>Horários:</strong> 00:00, 06:00, 12:00, 18:00
                </div>
                <div class="sevo-config-item">
                    <strong>Email destino:</strong> salvador.egito@gmail.com
                </div>
                <div class="sevo-config-item">
                    <strong>Rotação:</strong> Máximo 10 backups
                </div>
                <div class="sevo-config-item">
                    <strong>Inclui imagens:</strong> Sim (otimizadas 300x300px)
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="sevo-backup-actions">
        <button id="manual-backup-btn" class="button button-primary button-hero">
            🚀 Executar Backup Manual
        </button>
        <button id="refresh-status-btn" class="button button-secondary">
            🔄 Atualizar Status
        </button>
    </div>
    
    <!-- Progress indicator -->
    <div id="backup-progress" class="sevo-progress-container" style="display: none;">
        <div class="sevo-progress-bar">
            <div class="sevo-progress-fill"></div>
        </div>
        <div class="sevo-progress-text">Processando backup...</div>
    </div>
    
    <!-- Backup List -->
    <div class="sevo-card sevo-card-list">
        <div class="sevo-card-header">
            <h3>📁 Lista de Backups Disponíveis</h3>
            <p class="description">
                <strong>📥 Como baixar e restaurar:</strong><br>
                1. Clique no botão "Baixar" ao lado do backup desejado<br>
                2. Salve o arquivo ZIP em local seguro<br>
                3. Para restaurar: vá em "Restauração" → "Enviar Backup" → selecione o arquivo<br>
                4. Escolha quais dados restaurar e confirme a operação
            </p>
        </div>
        <div class="sevo-card-body">
            <?php if (empty($backup_list)): ?>
                <div class="sevo-empty-state">
                    <p>📭 Nenhum backup encontrado. Execute um backup manual para começar.</p>
                </div>
            <?php else: ?>
                <div class="sevo-backup-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>📅 Data/Hora</th>
                                <th>📁 Arquivo</th>
                                <th>📊 Tamanho</th>
                                <th>🔗 Ações</th>
                            </tr>
                        </thead>
                        <tbody id="backup-list-tbody">
                            <?php foreach ($backup_list as $backup): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($backup['date']); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($backup['filename']); ?></code>
                                    </td>
                                    <td>
                                        <span class="sevo-file-size"><?php echo esc_html($backup['size']); ?></span>
                                    </td>
                                    <td>
                                        <div class="sevo-action-buttons">
                                            <a href="<?php echo admin_url('admin-ajax.php?action=sevo_download_backup&file=' . urlencode($backup['filename']) . '&nonce=' . wp_create_nonce('sevo_download_backup')); ?>" 
                                               class="button button-small"
                                               title="Baixar este backup">
                                                💾 Baixar
                                            </a>
                                            <button class="button button-small button-link-delete sevo-delete-backup" 
                                                    data-filename="<?php echo esc_attr($backup['filename']); ?>"
                                                    title="Remover este backup">
                                                🗑️ Remover
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Logs Section -->
    <div class="sevo-card sevo-card-logs">
        <div class="sevo-card-header">
            <h3>📝 Logs do Sistema (Últimas 20 entradas)</h3>
        </div>
        <div class="sevo-card-body">
            <?php if (empty($logs)): ?>
                <div class="sevo-empty-state">
                    <p>📄 Nenhum log encontrado.</p>
                </div>
            <?php else: ?>
                <div class="sevo-logs-container">
                    <pre id="backup-logs"><?php echo esc_html(implode("\n", array_reverse($logs))); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Instructions Card -->
    <div class="sevo-card sevo-card-instructions">
        <div class="sevo-card-header">
            <h3>📋 Instruções Importantes</h3>
        </div>
        <div class="sevo-card-body">
            <div class="sevo-instructions">
                <h4>🔄 Sistema de Backup Automático:</h4>
                <ul>
                    <li>✅ Backups executados automaticamente a cada 6 horas</li>
                    <li>📧 Tentativa de envio por email (limitado a 10MB)</li>
                    <li>☁️ Para arquivos maiores: tentativa de upload para Google Drive (se disponível)</li>
                    <li>💾 Todos os backups ficam salvos no servidor para download manual</li>
                    <li>🔄 Sistema mantém apenas os 10 backups mais recentes</li>
                </ul>
                
                <h4>📥 Como baixar um backup:</h4>
                <ol>
                    <li>Localize o backup desejado na lista acima</li>
                    <li>Clique no botão "💾 Baixar"</li>
                    <li>Salve o arquivo ZIP em local seguro</li>
                    <li>Mantenha sempre uma cópia local dos backups importantes</li>
                </ol>
                
                <h4>🔙 Para restaurar dados:</h4>
                <ol>
                    <li>Acesse a seção "Restauração" no menu Sevo Eventos</li>
                    <li>Faça upload do arquivo de backup (ZIP)</li>
                    <li>Selecione quais dados deseja restaurar</li>
                    <li>Confirme a operação (⚠️ dados atuais serão substituídos)</li>
                </ol>
                
                <h4>📊 O que é incluído no backup:</h4>
                <ul>
                    <li>🗂️ Dados do fórum Asgaros (categorias, posts, tópicos)</li>
                    <li>🎯 Dados dos eventos (organizações, tipos, eventos, inscrições)</li>
                    <li>👥 Usuários WordPress</li>
                    <li>📄 Posts, páginas e comentários</li>
                    <li>🎨 Temas e plugins (sevo-theme, sevo-eventos, asgarosforum)</li>
                    <li>🖼️ Imagens otimizadas (redimensionadas para 300x300px quando necessário)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* === ESTILOS DA INTERFACE DE BACKUP === */
.sevo-backup-admin {
    margin: 20px 0;
}

.sevo-backup-admin h1 {
    margin-bottom: 20px;
    font-size: 24px;
    color: #2c3e50;
}

/* Cards Layout */
.sevo-backup-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.sevo-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sevo-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    padding: 15px 20px;
}

.sevo-card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
}

.sevo-card-body {
    padding: 20px;
}

.sevo-card-primary .sevo-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.sevo-card-primary .sevo-card-header h3 {
    color: white;
}

.sevo-card-info .sevo-card-header {
    background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
    color: white;
}

.sevo-card-info .sevo-card-header h3 {
    color: white;
}

/* Status Items */
.sevo-status-item,
.sevo-config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.sevo-status-item:last-child,
.sevo-config-item:last-child {
    border-bottom: none;
}

.sevo-status-active {
    color: #4CAF50;
    font-weight: bold;
}

/* Actions */
.sevo-backup-actions {
    margin-bottom: 30px;
    text-align: center;
}

.sevo-backup-actions .button {
    margin: 0 10px;
}

/* Progress Bar */
.sevo-progress-container {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.sevo-progress-bar {
    width: 100%;
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.sevo-progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    width: 0%;
    transition: width 0.3s ease;
    animation: progressPulse 1.5s infinite;
}

@keyframes progressPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.sevo-progress-text {
    font-weight: bold;
    color: #2c3e50;
}

/* Backup Table */
.sevo-backup-table-wrapper {
    overflow-x: auto;
}

.sevo-backup-table-wrapper table {
    min-width: 600px;
}

.sevo-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.sevo-file-size {
    font-weight: bold;
    color: #666;
}

/* Logs */
.sevo-logs-container {
    background: #2c3e50;
    border-radius: 6px;
    padding: 15px;
    max-height: 300px;
    overflow-y: auto;
}

.sevo-logs-container pre {
    color: #f8f9fa;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Instructions */
.sevo-instructions h4 {
    color: #2c3e50;
    margin-top: 20px;
    margin-bottom: 10px;
    font-size: 14px;
}

.sevo-instructions ul,
.sevo-instructions ol {
    margin-left: 20px;
}

.sevo-instructions li {
    margin-bottom: 5px;
    line-height: 1.5;
}

/* Empty State */
.sevo-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.sevo-empty-state p {
    font-size: 16px;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .sevo-backup-cards {
        grid-template-columns: 1fr;
    }
    
    .sevo-action-buttons {
        flex-direction: column;
    }
    
    .sevo-backup-actions .button {
        display: block;
        margin: 5px 0;
        width: 100%;
    }
}

/* Loading state */
.button.loading {
    pointer-events: none;
    opacity: 0.7;
}

.button.loading::after {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
    display: inline-block;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Success/Error Messages */
.sevo-message {
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
}

.sevo-message.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.sevo-message.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.sevo-message.info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const manualBackupBtn = document.getElementById('manual-backup-btn');
    const refreshStatusBtn = document.getElementById('refresh-status-btn');
    const progressContainer = document.getElementById('backup-progress');
    const progressFill = document.querySelector('.sevo-progress-fill');
    const progressText = document.querySelector('.sevo-progress-text');
    
    // Manual backup
    manualBackupBtn.addEventListener('click', function() {
        executeManualBackup();
    });
    
    // Refresh status
    refreshStatusBtn.addEventListener('click', function() {
        refreshBackupStatus();
    });
    
    // Delete backup buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('sevo-delete-backup')) {
            const filename = e.target.dataset.filename;
            if (confirm('Tem certeza que deseja remover este backup?\n\nArquivo: ' + filename)) {
                deleteBackup(filename, e.target.closest('tr'));
            }
        }
    });
    
    function executeManualBackup() {
        manualBackupBtn.classList.add('loading');
        manualBackupBtn.textContent = '⏳ Executando...';
        progressContainer.style.display = 'block';
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressFill.style.width = progress + '%';
        }, 500);
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sevo_manual_backup',
                nonce: '<?php echo wp_create_nonce('sevo_backup_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            progressFill.style.width = '100%';
            
            setTimeout(() => {
                manualBackupBtn.classList.remove('loading');
                manualBackupBtn.textContent = '🚀 Executar Backup Manual';
                progressContainer.style.display = 'none';
                
                if (data.success) {
                    showMessage('✅ Backup executado com sucesso!', 'success');
                    refreshBackupList();
                } else {
                    showMessage('❌ Erro ao executar backup: ' + (data.data.message || 'Erro desconhecido'), 'error');
                }
            }, 1000);
        })
        .catch(error => {
            clearInterval(progressInterval);
            manualBackupBtn.classList.remove('loading');
            manualBackupBtn.textContent = '🚀 Executar Backup Manual';
            progressContainer.style.display = 'none';
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function refreshBackupStatus() {
        refreshStatusBtn.classList.add('loading');
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sevo_backup_status',
                nonce: '<?php echo wp_create_nonce('sevo_backup_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            refreshStatusBtn.classList.remove('loading');
            
            if (data.success) {
                updateStatusDisplay(data.data);
                showMessage('✅ Status atualizado!', 'info');
            } else {
                showMessage('❌ Erro ao atualizar status', 'error');
            }
        })
        .catch(error => {
            refreshStatusBtn.classList.remove('loading');
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function deleteBackup(filename, row) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sevo_delete_backup',
                filename: filename,
                nonce: '<?php echo wp_create_nonce('sevo_backup_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.remove();
                showMessage('✅ Backup removido com sucesso!', 'success');
            } else {
                showMessage('❌ Erro ao remover backup: ' + (data.data.message || 'Erro desconhecido'), 'error');
            }
        })
        .catch(error => {
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function updateStatusDisplay(status) {
        document.getElementById('next-backup-time').textContent = status.next_scheduled;
        document.getElementById('total-backups').textContent = status.total_backups;
        document.getElementById('last-backup').textContent = status.last_backup ? status.last_backup.date : 'Nenhum backup realizado';
    }
    
    function refreshBackupList() {
        // Reload the page to refresh the backup list
        window.location.reload();
    }
    
    function showMessage(message, type) {
        const existingMessage = document.querySelector('.sevo-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `sevo-message ${type}`;
        messageDiv.textContent = message;
        
        const firstCard = document.querySelector('.sevo-backup-cards');
        firstCard.parentNode.insertBefore(messageDiv, firstCard);
        
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
});
</script>