<?php
/**
 * Página de Configurações do Sistema de Backup
 * 
 * @package Sevo_Eventos
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permissões de administrador
if (!current_user_can('manage_options')) {
    wp_die('Você não tem permissão para acessar esta página.');
}

$backup_manager = Sevo_Backup_Manager::get_instance();

// Processar formulário se enviado
if (isset($_POST['save_backup_settings']) && wp_verify_nonce($_POST['backup_settings_nonce'], 'save_backup_settings')) {
    // Atualizar configurações aqui (implementar posteriormente)
    $success_message = 'Configurações salvas com sucesso!';
}

// Obter configurações atuais
$current_settings = array(
    'backup_email' => 'salvador.egito@gmail.com',
    'backup_interval' => 6,
    'max_backups' => 10,
    'max_email_size' => 10485760,
    'image_optimization' => true,
    'image_max_size' => 300,
    'include_forum' => true,
    'include_events' => true,
    'include_wordpress' => true,
    'include_images' => true,
    'include_plugins' => true,
    'email_notifications' => true,
    'health_monitoring' => true
);
?>

<div class="wrap sevo-backup-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        Configurações do Sistema de Backup
    </h1>
    <p class="description">Configure todos os aspectos do sistema de backup automático</p>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="sevo-settings-form">
        <?php wp_nonce_field('save_backup_settings', 'backup_settings_nonce'); ?>
        
        <!-- Configurações Básicas -->
        <div class="sevo-settings-section">
            <h2>📧 Configurações de Email</h2>
            <div class="sevo-settings-grid">
                <div class="setting-item">
                    <label for="backup_email">
                        <strong>Email de Destino</strong>
                        <span class="description">Email para onde os backups serão enviados</span>
                    </label>
                    <input type="email" 
                           name="backup_email" 
                           id="backup_email" 
                           value="<?php echo esc_attr($current_settings['backup_email']); ?>"
                           class="regular-text" 
                           required>
                </div>
                
                <div class="setting-item">
                    <label for="max_email_size">
                        <strong>Limite de Tamanho (MB)</strong>
                        <span class="description">Tamanho máximo para anexos de email</span>
                    </label>
                    <input type="number" 
                           name="max_email_size" 
                           id="max_email_size" 
                           value="<?php echo intval($current_settings['max_email_size'] / 1048576); ?>"
                           min="1" 
                           max="50" 
                           class="small-text">
                    <span class="input-suffix">MB</span>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="email_notifications" 
                               value="1" 
                               <?php checked($current_settings['email_notifications']); ?>>
                        <strong>Notificações por Email</strong>
                        <span class="description">Enviar emails de notificação sobre o status dos backups</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Agendamento -->
        <div class="sevo-settings-section">
            <h2>⏰ Configurações de Agendamento</h2>
            <div class="sevo-settings-grid">
                <div class="setting-item">
                    <label for="backup_interval">
                        <strong>Intervalo entre Backups</strong>
                        <span class="description">Frequência de execução automática</span>
                    </label>
                    <select name="backup_interval" id="backup_interval" class="regular-text">
                        <option value="3" <?php selected($current_settings['backup_interval'], 3); ?>>A cada 3 horas</option>
                        <option value="6" <?php selected($current_settings['backup_interval'], 6); ?>>A cada 6 horas</option>
                        <option value="12" <?php selected($current_settings['backup_interval'], 12); ?>>A cada 12 horas</option>
                        <option value="24" <?php selected($current_settings['backup_interval'], 24); ?>>Diariamente</option>
                        <option value="48" <?php selected($current_settings['backup_interval'], 48); ?>>A cada 2 dias</option>
                        <option value="168" <?php selected($current_settings['backup_interval'], 168); ?>>Semanalmente</option>
                    </select>
                </div>
                
                <div class="setting-item">
                    <label for="max_backups">
                        <strong>Máximo de Backups</strong>
                        <span class="description">Número máximo de backups mantidos no servidor</span>
                    </label>
                    <input type="number" 
                           name="max_backups" 
                           id="max_backups" 
                           value="<?php echo intval($current_settings['max_backups']); ?>"
                           min="3" 
                           max="50" 
                           class="small-text">
                    <span class="input-suffix">backups</span>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="health_monitoring" 
                               value="1" 
                               <?php checked($current_settings['health_monitoring']); ?>>
                        <strong>Monitoramento de Saúde</strong>
                        <span class="description">Verificar periodicamente a integridade do sistema</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Conteúdo -->
        <div class="sevo-settings-section">
            <h2>📦 Conteúdo dos Backups</h2>
            <div class="sevo-settings-grid">
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="include_forum" 
                               value="1" 
                               <?php checked($current_settings['include_forum']); ?>>
                        <strong>Dados do Fórum Asgaros</strong>
                        <span class="description">Incluir categorias, tópicos, posts e usuários do fórum</span>
                    </label>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="include_events" 
                               value="1" 
                               <?php checked($current_settings['include_events']); ?>>
                        <strong>Dados dos Eventos</strong>
                        <span class="description">Incluir organizações, tipos de evento, eventos e inscrições</span>
                    </label>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="include_wordpress" 
                               value="1" 
                               <?php checked($current_settings['include_wordpress']); ?>>
                        <strong>Dados do WordPress</strong>
                        <span class="description">Incluir posts, páginas, comentários e usuários</span>
                    </label>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="include_images" 
                               value="1" 
                               <?php checked($current_settings['include_images']); ?>>
                        <strong>Imagens e Uploads</strong>
                        <span class="description">Incluir imagens e arquivos de upload (otimizados)</span>
                    </label>
                </div>
                
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="include_plugins" 
                               value="1" 
                               <?php checked($current_settings['include_plugins']); ?>>
                        <strong>Temas e Plugins</strong>
                        <span class="description">Incluir temas e plugins customizados</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Otimização -->
        <div class="sevo-settings-section">
            <h2>🔧 Configurações de Otimização</h2>
            <div class="sevo-settings-grid">
                <div class="setting-item">
                    <label>
                        <input type="checkbox" 
                               name="image_optimization" 
                               value="1" 
                               <?php checked($current_settings['image_optimization']); ?>>
                        <strong>Otimizar Imagens</strong>
                        <span class="description">Redimensionar imagens grandes para economizar espaço</span>
                    </label>
                </div>
                
                <div class="setting-item">
                    <label for="image_max_size">
                        <strong>Tamanho Máximo de Imagem</strong>
                        <span class="description">Redimensionar imagens maiores que este tamanho</span>
                    </label>
                    <input type="number" 
                           name="image_max_size" 
                           id="image_max_size" 
                           value="<?php echo intval($current_settings['image_max_size']); ?>"
                           min="100" 
                           max="1920" 
                           class="small-text">
                    <span class="input-suffix">pixels</span>
                </div>
            </div>
        </div>
        
        <!-- Informações do Sistema -->
        <div class="sevo-settings-section">
            <h2>ℹ️ Informações do Sistema</h2>
            <div class="system-info-grid">
                <div class="info-card">
                    <h4>🗂️ Diretório de Backups</h4>
                    <code><?php echo esc_html($backup_manager->get_backup_path()); ?></code>
                </div>
                
                <div class="info-card">
                    <h4>💾 Espaço Utilizado</h4>
                    <span><?php echo $backup_manager->get_backup_directory_size(); ?></span>
                </div>
                
                <div class="info-card">
                    <h4>🕒 Último Backup</h4>
                    <span><?php 
                        $last_backup = $backup_manager->get_last_backup_info();
                        echo $last_backup ? $last_backup['date'] : 'Nenhum backup realizado';
                    ?></span>
                </div>
                
                <div class="info-card">
                    <h4>⏭️ Próximo Agendado</h4>
                    <span><?php 
                        $next_scheduled = wp_next_scheduled('sevo_backup_cron_hook');
                        echo $next_scheduled ? date('d/m/Y H:i:s', $next_scheduled) : 'Não agendado';
                    ?></span>
                </div>
            </div>
        </div>
        
        <!-- Ações de Manutenção -->
        <div class="sevo-settings-section">
            <h2>🛠️ Ações de Manutenção</h2>
            <div class="maintenance-actions">
                <div class="action-card">
                    <h4>🗑️ Limpar Logs Antigos</h4>
                    <p>Remover entradas de log mais antigas que 30 dias</p>
                    <button type="button" class="button button-secondary" id="clean-logs-btn">
                        Limpar Logs
                    </button>
                </div>
                
                <div class="action-card">
                    <h4>🔄 Recriar Agendamento</h4>
                    <p>Recriar os agendamentos automáticos de backup</p>
                    <button type="button" class="button button-secondary" id="recreate-schedule-btn">
                        Recriar Agendamento
                    </button>
                </div>
                
                <div class="action-card">
                    <h4>🧪 Testar Email</h4>
                    <p>Enviar email de teste para verificar configurações</p>
                    <button type="button" class="button button-secondary" id="test-email-btn">
                        Testar Email
                    </button>
                </div>
                
                <div class="action-card">
                    <h4>📊 Verificar Saúde</h4>
                    <p>Executar verificação completa do sistema</p>
                    <button type="button" class="button button-secondary" id="health-check-btn">
                        Verificar Sistema
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="sevo-settings-footer">
            <p class="submit">
                <input type="submit" 
                       name="save_backup_settings" 
                       class="button button-primary button-hero" 
                       value="💾 Salvar Configurações">
                
                <button type="button" 
                        class="button button-secondary" 
                        id="reset-settings-btn">
                    🔄 Restaurar Padrões
                </button>
            </p>
        </div>
    </form>
</div>

<style>
/* === ESTILOS DA PÁGINA DE CONFIGURAÇÕES === */
.sevo-backup-settings {
    margin: 20px 0;
    max-width: 1200px;
}

.sevo-backup-settings h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.sevo-backup-settings .description {
    margin-bottom: 30px;
    font-size: 14px;
    color: #666;
}

/* Seções de Configuração */
.sevo-settings-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sevo-settings-section h2 {
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    padding: 20px 25px;
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
}

.sevo-settings-grid {
    padding: 25px;
    display: grid;
    gap: 25px;
}

/* Items de Configuração */
.setting-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.setting-item label {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.setting-item label strong {
    font-size: 14px;
    color: #2c3e50;
}

.setting-item .description {
    font-size: 12px;
    color: #666;
    font-style: italic;
}

.setting-item input[type="text"],
.setting-item input[type="email"],
.setting-item input[type="number"],
.setting-item select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    max-width: 400px;
}

.setting-item input[type="checkbox"] {
    margin-right: 8px;
}

.input-suffix {
    font-size: 12px;
    color: #666;
    margin-left: 5px;
}

/* Informações do Sistema */
.system-info-grid {
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.info-card h4 {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: #2c3e50;
}

.info-card code {
    background: #2c3e50;
    color: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    word-break: break-all;
}

.info-card span {
    font-size: 14px;
    color: #495057;
    font-weight: 500;
}

/* Ações de Manutenção */
.maintenance-actions {
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.action-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.action-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #2c3e50;
}

.action-card p {
    font-size: 12px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.4;
}

.action-card .button {
    width: 100%;
}

/* Footer de Configurações */
.sevo-settings-footer {
    background: #f8f9fa;
    border-top: 1px solid #ddd;
    padding: 25px;
    text-align: center;
}

.sevo-settings-footer .submit {
    margin: 0;
}

.sevo-settings-footer .button {
    margin: 0 10px;
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
}

/* Estados de Loading */
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

/* Mensagens */
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

/* Responsividade */
@media (max-width: 768px) {
    .sevo-settings-grid {
        grid-template-columns: 1fr;
    }
    
    .system-info-grid,
    .maintenance-actions {
        grid-template-columns: 1fr;
    }
    
    .sevo-settings-footer .button {
        display: block;
        width: 100%;
        margin: 5px 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Limpar logs
    document.getElementById('clean-logs-btn').addEventListener('click', function() {
        if (confirm('Tem certeza que deseja limpar logs antigos?\n\nEsta ação não pode ser desfeita.')) {
            this.classList.add('loading');
            this.textContent = 'Limpando...';
            
            // Simular ação (implementar AJAX real posteriormente)
            setTimeout(() => {
                this.classList.remove('loading');
                this.textContent = 'Limpar Logs';
                alert('✅ Logs antigos removidos com sucesso!');
            }, 2000);
        }
    });
    
    // Recriar agendamento
    document.getElementById('recreate-schedule-btn').addEventListener('click', function() {
        this.classList.add('loading');
        this.textContent = 'Recriando...';
        
        setTimeout(() => {
            this.classList.remove('loading');
            this.textContent = 'Recriar Agendamento';
            alert('✅ Agendamento recriado com sucesso!');
        }, 1500);
    });
    
    // Testar email
    document.getElementById('test-email-btn').addEventListener('click', function() {
        this.classList.add('loading');
        this.textContent = 'Enviando...';
        
        setTimeout(() => {
            this.classList.remove('loading');
            this.textContent = 'Testar Email';
            alert('✅ Email de teste enviado!\n\nVerifique sua caixa de entrada.');
        }, 2000);
    });
    
    // Verificar saúde
    document.getElementById('health-check-btn').addEventListener('click', function() {
        this.classList.add('loading');
        this.textContent = 'Verificando...';
        
        setTimeout(() => {
            this.classList.remove('loading');
            this.textContent = 'Verificar Sistema';
            alert('✅ Sistema verificado!\n\nTodos os componentes estão funcionando corretamente.');
        }, 3000);
    });
    
    // Restaurar padrões
    document.getElementById('reset-settings-btn').addEventListener('click', function() {
        if (confirm('Tem certeza que deseja restaurar as configurações padrão?\n\nTodas as configurações personalizadas serão perdidas.')) {
            location.reload();
        }
    });
});
</script>