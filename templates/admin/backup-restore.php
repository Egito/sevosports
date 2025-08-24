<?php
/**
 * Template da página administrativa de restauração de backup
 * 
 * @package Sevo_Eventos
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$backup_manager = Sevo_Backup_Manager::get_instance();
?>

<div class="wrap sevo-restore-admin">
    <h1>🔙 Restauração de Backup Sevo Eventos</h1>
    
    <script>
    // JavaScript variables for AJAX
    const sevoRestore = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('sevo_backup_nonce'); ?>'
    };
    </script>
    
    <!-- Warning Alert -->
    <div class="sevo-restore-warning">
        <h3>⚠️ ATENÇÃO - OPERAÇÃO CRÍTICA ⚠️</h3>
        <p><strong>A restauração substituirá TODOS os dados selecionados. Esta operação NÃO pode ser desfeita.</strong></p>
    </div>
    
    <!-- Upload Section -->
    <div class="sevo-card">
        <div class="sevo-card-header">
            <h3>📤 Enviar Arquivo de Backup</h3>
        </div>
        <div class="sevo-card-body">
            <div id="backup-upload-area" class="sevo-upload-area">
                <div class="sevo-upload-content">
                    <div class="sevo-upload-icon">📁</div>
                    <h4>Arraste um arquivo .zip aqui ou clique para selecionar</h4>
                    <input type="file" id="backup-file-input" accept=".zip" style="display: none;">
                    <button type="button" class="button button-primary" id="select-backup-btn">
                        📁 Selecionar Arquivo
                    </button>
                </div>
            </div>
            
            <div id="uploaded-file-info" class="sevo-file-info" style="display: none;">
                <h4>📋 Informações do Arquivo</h4>
                <div id="file-info-content"></div>
                <div class="sevo-file-actions">
                    <button type="button" class="button button-secondary" id="remove-file-btn">🗑️ Remover</button>
                    <button type="button" class="button button-primary" id="analyze-backup-btn">🔍 Analisar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Restoration Options -->
    <div id="restore-options" class="sevo-card" style="display: none;">
        <div class="sevo-card-header">
            <h3>🔧 Opções de Restauração</h3>
        </div>
        <div class="sevo-card-body">
            <form id="restore-form">
                <div class="sevo-restore-sections">
                    <h4>📊 Selecione o que restaurar:</h4>
                    
                    <label class="sevo-restore-option">
                        <input type="checkbox" name="restore_forum" value="1">
                        <span>🗣️ Dados do Fórum</span>
                        <small>Substituirá todos os dados do fórum</small>
                    </label>
                    
                    <label class="sevo-restore-option">
                        <input type="checkbox" name="restore_events" value="1">
                        <span>🎯 Dados dos Eventos</span>
                        <small>Substituirá organizações, eventos e inscrições</small>
                    </label>
                    
                    <label class="sevo-restore-option">
                        <input type="checkbox" name="restore_wordpress" value="1">
                        <span>📄 Dados do WordPress</span>
                        <small>CUIDADO: Pode afetar acesso ao site</small>
                    </label>
                    
                    <label class="sevo-restore-option">
                        <input type="checkbox" name="restore_images" value="1">
                        <span>🖼️ Imagens</span>
                        <small>Imagens processadas e otimizadas</small>
                    </label>
                </div>
                
                <div class="sevo-confirmation-section">
                    <label class="sevo-confirmation">
                        <input type="checkbox" name="confirm_restore" value="1" required>
                        <strong>Eu entendo que esta operação NÃO pode ser desfeita</strong>
                    </label>
                </div>
                
                <div class="sevo-restore-actions">
                    <button type="button" class="button button-secondary" id="cancel-restore-btn">❌ Cancelar</button>
                    <button type="submit" class="button button-primary" id="execute-restore-btn">🔙 Executar Restauração</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sevo-restore-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    border-left: 4px solid #f39c12;
}

.sevo-upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #fafafa;
}

.sevo-upload-area:hover { border-color: #667eea; }
.sevo-upload-icon { font-size: 48px; margin-bottom: 20px; }

.sevo-file-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.sevo-file-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.sevo-restore-option {
    display: block;
    margin-bottom: 15px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
}

.sevo-restore-option:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.sevo-restore-option input { margin-right: 10px; }
.sevo-restore-option span { font-weight: bold; }
.sevo-restore-option small { display: block; color: #666; margin-top: 5px; }

.sevo-confirmation-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.sevo-restore-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.sevo-restore-actions .button {
    margin: 0 10px;
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
}

/* File Info Grid */
.file-info-grid {
    display: grid;
    gap: 12px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    font-size: 13px;
    color: #2c3e50;
}

.info-item code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #2c3e50;
    border: 1px solid #e9ecef;
    word-break: break-all;
}

/* Analysis Results */
.backup-analysis {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.backup-analysis h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 14px;
}

.content-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.content-item {
    background: white;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    font-size: 13px;
    color: #495057;
}

.warnings-title {
    color: #f39c12 !important;
}

.warning-item {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 8px;
    font-size: 13px;
    color: #856404;
}

/* Progress Bar */
.restore-progress {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
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

.progress-text {
    font-weight: bold;
    color: #2c3e50;
    font-size: 14px;
}

/* Messages */
.restore-message {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
    position: relative;
}

.message-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.message-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.message-info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

/* Loading states */
.button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .content-summary {
        grid-template-columns: 1fr;
    }
    
    .sevo-restore-actions .button {
        display: block;
        width: 100%;
        margin: 5px 0;
    }
    
    .file-info-grid {
        gap: 8px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('backup-upload-area');
    const fileInput = document.getElementById('backup-file-input');
    const selectBtn = document.getElementById('select-backup-btn');
    const fileInfo = document.getElementById('uploaded-file-info');
    const restoreOptions = document.getElementById('restore-options');
    const analyzeBtn = document.getElementById('analyze-backup-btn');
    const removeBtn = document.getElementById('remove-file-btn');
    const restoreForm = document.getElementById('restore-form');
    
    let uploadedFile = null;
    let backupAnalysis = null;
    
    // Event listeners
    selectBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
    removeBtn.addEventListener('click', removeFile);
    analyzeBtn.addEventListener('click', analyzeBackup);
    restoreForm.addEventListener('submit', executeRestore);
    
    // Drag and drop events
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#ccc';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#ccc';
        const file = e.dataTransfer.files[0];
        if (file) {
            fileInput.files = e.dataTransfer.files;
            validateAndUploadFile(file);
        }
    });
    
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) validateAndUploadFile(file);
    }
    
    function validateAndUploadFile(file) {
        if (!file.name.toLowerCase().endsWith('.zip')) {
            showMessage('❌ Apenas arquivos ZIP são aceitos.', 'error');
            return;
        }
        
        if (file.size > 100 * 1024 * 1024) { // 100MB limit
            showMessage('❌ Arquivo muito grande. Limite máximo: 100MB.', 'error');
            return;
        }
        
        uploadedFile = file;
        uploadFile(file);
    }
    
    function uploadFile(file) {
        const formData = new FormData();
        formData.append('backup_file', file);
        formData.append('action', 'sevo_upload_backup');
        formData.append('nonce', sevoRestore.nonce);
        
        selectBtn.textContent = '📤 Enviando...';
        selectBtn.disabled = true;
        
        fetch(sevoRestore.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            selectBtn.textContent = '📁 Selecionar Arquivo';
            selectBtn.disabled = false;
            
            if (data.success) {
                showFileInfo(file, data.data);
                showMessage('✅ Arquivo enviado com sucesso!', 'success');
            } else {
                showMessage('❌ Erro ao enviar arquivo: ' + data.data.message, 'error');
            }
        })
        .catch(error => {
            selectBtn.textContent = '📁 Selecionar Arquivo';
            selectBtn.disabled = false;
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function showFileInfo(file, uploadData) {
        uploadArea.style.display = 'none';
        fileInfo.style.display = 'block';
        
        document.getElementById('file-info-content').innerHTML = `
            <div class="file-info-grid">
                <div class="info-item">
                    <strong>📁 Nome:</strong> 
                    <code>${file.name}</code>
                </div>
                <div class="info-item">
                    <strong>📊 Tamanho:</strong> 
                    <span>${formatBytes(file.size)}</span>
                </div>
                <div class="info-item">
                    <strong>📅 Data:</strong> 
                    <span>${new Date(file.lastModified).toLocaleString('pt-BR')}</span>
                </div>
                <div class="info-item">
                    <strong>🆔 ID Temporário:</strong> 
                    <code>${uploadData.temp_id}</code>
                </div>
            </div>
        `;
    }
    
    function analyzeBackup() {
        if (!uploadedFile) {
            showMessage('❌ Nenhum arquivo selecionado.', 'error');
            return;
        }
        
        analyzeBtn.textContent = '🔍 Analisando...';
        analyzeBtn.disabled = true;
        
        fetch(sevoRestore.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sevo_analyze_backup',
                nonce: sevoRestore.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            analyzeBtn.textContent = '🔍 Analisar';
            analyzeBtn.disabled = false;
            
            if (data.success) {
                backupAnalysis = data.data;
                showAnalysisResults(data.data);
                restoreOptions.style.display = 'block';
                showMessage('✅ Arquivo analisado com sucesso!', 'success');
            } else {
                showMessage('❌ Erro na análise: ' + data.data.message, 'error');
            }
        })
        .catch(error => {
            analyzeBtn.textContent = '🔍 Analisar';
            analyzeBtn.disabled = false;
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function showAnalysisResults(analysis) {
        let analysisHtml = '<div class="analysis-results">';
        
        if (analysis.content_summary) {
            analysisHtml += '<h4>📦 Conteúdo do Backup:</h4>';
            analysisHtml += '<div class="content-summary">';
            
            const contentMap = {
                'forum_data': '🗣️ Dados do Fórum',
                'events_data': '🎯 Dados dos Eventos', 
                'wordpress_data': '📄 Dados do WordPress',
                'images': '🖼️ Imagens',
                'themes': '🎨 Temas',
                'plugins': '🔌 Plugins'
            };
            
            Object.keys(analysis.content_summary).forEach(key => {
                if (analysis.content_summary[key] > 0) {
                    const label = contentMap[key] || key;
                    const count = analysis.content_summary[key];
                    analysisHtml += `<div class="content-item">✅ ${label} (${count} itens)</div>`;
                }
            });
            
            analysisHtml += '</div>';
        }
        
        if (analysis.warnings && analysis.warnings.length > 0) {
            analysisHtml += '<h4 class="warnings-title">⚠️ Avisos:</h4>';
            analysis.warnings.forEach(warning => {
                analysisHtml += `<div class="warning-item">${warning}</div>`;
            });
        }
        
        analysisHtml += '</div>';
        
        // Add analysis results before restore options
        const analysisDiv = document.createElement('div');
        analysisDiv.className = 'backup-analysis';
        analysisDiv.innerHTML = analysisHtml;
        
        const existingAnalysis = document.querySelector('.backup-analysis');
        if (existingAnalysis) {
            existingAnalysis.remove();
        }
        
        restoreOptions.querySelector('.sevo-card-body').insertBefore(
            analysisDiv, 
            restoreOptions.querySelector('form')
        );
    }
    
    function executeRestore(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const selectedOptions = [];
        
        // Collect selected options
        if (formData.get('restore_forum')) selectedOptions.push('Fórum');
        if (formData.get('restore_events')) selectedOptions.push('Eventos');
        if (formData.get('restore_wordpress')) selectedOptions.push('WordPress');
        if (formData.get('restore_images')) selectedOptions.push('Imagens');
        
        if (selectedOptions.length === 0) {
            showMessage('❌ Selecione pelo menos uma opção para restaurar.', 'error');
            return;
        }
        
        if (!formData.get('confirm_restore')) {
            showMessage('❌ Você deve confirmar que entende que esta operação não pode ser desfeita.', 'error');
            return;
        }
        
        const confirmMsg = `⚠️ CONFIRMAR RESTAURAÇÃO ⚠️\n\n` +
                          `Você vai restaurar: ${selectedOptions.join(', ')}\n\n` +
                          `Esta operação NÃO pode ser desfeita e pode afetar o funcionamento do site.\n\n` +
                          `Continuar?`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Prepare restore data
        const restoreData = new URLSearchParams({
            action: 'sevo_restore_backup',
            nonce: sevoRestore.nonce,
            restore_forum: formData.get('restore_forum') ? '1' : '',
            restore_events: formData.get('restore_events') ? '1' : '',
            restore_wordpress: formData.get('restore_wordpress') ? '1' : '',
            restore_images: formData.get('restore_images') ? '1' : ''
        });
        
        const executeBtn = document.getElementById('execute-restore-btn');
        executeBtn.textContent = '🔄 Restaurando...';
        executeBtn.disabled = true;
        
        // Show progress indication
        showRestoreProgress();
        
        fetch(sevoRestore.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: restoreData
        })
        .then(response => response.json())
        .then(data => {
            executeBtn.textContent = '🔙 Executar Restauração';
            executeBtn.disabled = false;
            hideRestoreProgress();
            
            if (data.success) {
                showMessage('✅ Restauração executada com sucesso!\n\n' + data.data.message, 'success');
                // Reset form after successful restore
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showMessage('❌ Erro na restauração: ' + data.data.message, 'error');
            }
        })
        .catch(error => {
            executeBtn.textContent = '🔙 Executar Restauração';
            executeBtn.disabled = false;
            hideRestoreProgress();
            showMessage('❌ Erro de conexão: ' + error.message, 'error');
        });
    }
    
    function removeFile() {
        uploadedFile = null;
        backupAnalysis = null;
        fileInput.value = '';
        uploadArea.style.display = 'block';
        fileInfo.style.display = 'none';
        restoreOptions.style.display = 'none';
        
        // Remove analysis results
        const existingAnalysis = document.querySelector('.backup-analysis');
        if (existingAnalysis) {
            existingAnalysis.remove();
        }
    }
    
    function showRestoreProgress() {
        let progressHtml = `
            <div id="restore-progress" class="restore-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Executando restauração...</div>
            </div>
        `;
        
        restoreForm.insertAdjacentHTML('afterend', progressHtml);
        
        // Animate progress bar
        const progressFill = document.querySelector('.progress-fill');
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 85) progress = 85;
            progressFill.style.width = progress + '%';
        }, 800);
        
        // Store interval for cleanup
        restoreForm.progressInterval = progressInterval;
    }
    
    function hideRestoreProgress() {
        const progressElement = document.getElementById('restore-progress');
        if (progressElement) {
            clearInterval(restoreForm.progressInterval);
            progressElement.remove();
        }
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.restore-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `restore-message message-${type}`;
        messageDiv.textContent = message;
        
        // Insert at top of the page
        const firstCard = document.querySelector('.sevo-card');
        firstCard.parentNode.insertBefore(messageDiv, firstCard);
        
        // Auto-remove after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>