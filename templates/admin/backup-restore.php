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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('backup-upload-area');
    const fileInput = document.getElementById('backup-file-input');
    const selectBtn = document.getElementById('select-backup-btn');
    const fileInfo = document.getElementById('uploaded-file-info');
    const restoreOptions = document.getElementById('restore-options');
    
    let uploadedFile = null;
    
    selectBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#667eea';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#ccc';
        const file = e.dataTransfer.files[0];
        if (file) validateAndUploadFile(file);
    });
    
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) validateAndUploadFile(file);
    }
    
    function validateAndUploadFile(file) {
        if (!file.name.toLowerCase().endsWith('.zip')) {
            alert('❌ Apenas arquivos ZIP são aceitos.');
            return;
        }
        
        uploadedFile = file;
        showFileInfo(file);
    }
    
    function showFileInfo(file) {
        uploadArea.style.display = 'none';
        fileInfo.style.display = 'block';
        
        document.getElementById('file-info-content').innerHTML = `
            <div><strong>📁 Nome:</strong> <code>${file.name}</code></div>
            <div><strong>📊 Tamanho:</strong> ${formatBytes(file.size)}</div>
            <div><strong>📅 Data:</strong> ${new Date(file.lastModified).toLocaleString('pt-BR')}</div>
        `;
    }
    
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    document.getElementById('remove-file-btn').addEventListener('click', function() {
        uploadedFile = null;
        fileInput.value = '';
        uploadArea.style.display = 'block';
        fileInfo.style.display = 'none';
        restoreOptions.style.display = 'none';
    });
    
    document.getElementById('analyze-backup-btn').addEventListener('click', function() {
        if (!uploadedFile) return;
        
        this.textContent = '🔍 Analisando...';
        setTimeout(() => {
            this.textContent = '🔍 Analisar';
            restoreOptions.style.display = 'block';
        }, 1500);
    });
    
    document.getElementById('restore-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const selectedOptions = [];
        
        if (formData.get('restore_forum')) selectedOptions.push('Fórum');
        if (formData.get('restore_events')) selectedOptions.push('Eventos');
        if (formData.get('restore_wordpress')) selectedOptions.push('WordPress');
        if (formData.get('restore_images')) selectedOptions.push('Imagens');
        
        if (selectedOptions.length === 0) {
            alert('❌ Selecione pelo menos uma opção para restaurar.');
            return;
        }
        
        const confirmMsg = `⚠️ CONFIRMAR RESTAURAÇÃO ⚠️\n\nVocê vai restaurar: ${selectedOptions.join(', ')}\n\nEsta operação NÃO pode ser desfeita.\n\nContinuar?`;
        
        if (confirm(confirmMsg)) {
            alert('🔙 Funcionalidade de restauração será implementada na próxima fase.');
        }
    });
});
</script>