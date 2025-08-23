<?php
/**
 * Página de Visão Geral do Sistema de Backup
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
$backup_list = $backup_manager->get_backup_list();
$logs = $backup_manager->get_logs(10);
$health_status = $backup_manager->check_backup_health();
$next_scheduled = wp_next_scheduled('sevo_backup_cron_hook');
$log_stats = $backup_manager->get_log_statistics();
?>

<div class="wrap sevo-backup-overview">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-archive"></span>
        Sistema de Backup Sevo Eventos
    </h1>
    <p class="description">Painel administrativo completo para gerenciamento de backups e restauração</p>
    
    <!-- Status Cards -->
    <div class="sevo-overview-cards">
        <!-- Status Geral -->
        <div class="sevo-overview-card <?php echo $health_status['status']; ?>">
            <div class="card-icon">
                <?php if ($health_status['status'] === 'healthy'): ?>
                    <span class="dashicons dashicons-yes-alt"></span>
                <?php elseif ($health_status['status'] === 'warning'): ?>
                    <span class="dashicons dashicons-warning"></span>
                <?php else: ?>
                    <span class="dashicons dashicons-dismiss"></span>
                <?php endif; ?>
            </div>
            <div class="card-content">
                <h3>Status do Sistema</h3>
                <div class="card-value">
                    <?php 
                    switch($health_status['status']) {
                        case 'healthy': echo '✅ Funcionando'; break;
                        case 'warning': echo '⚠️ Atenção'; break;
                        case 'critical': echo '❌ Crítico'; break;
                        default: echo '❓ Desconhecido';
                    }
                    ?>
                </div>
                <?php if (!empty($health_status['issues'])): ?>
                    <div class="card-details">
                        <?php foreach (array_slice($health_status['issues'], 0, 2) as $issue): ?>
                            <div class="issue-item">⚠️ <?php echo esc_html($issue); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Total de Backups -->
        <div class="sevo-overview-card">
            <div class="card-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="card-content">
                <h3>Total de Backups</h3>
                <div class="card-value"><?php echo count($backup_list); ?></div>
                <div class="card-details">
                    <?php if (!empty($backup_list)): ?>
                        <div>Último: <?php echo esc_html($backup_list[0]['date']); ?></div>
                        <div>Tamanho total: <?php 
                            $total_size = array_sum(array_column($backup_list, 'size_bytes'));
                            echo $backup_manager->format_bytes($total_size);
                        ?></div>
                    <?php else: ?>
                        <div>Nenhum backup encontrado</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Próximo Backup -->
        <div class="sevo-overview-card">
            <div class="card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="card-content">
                <h3>Próximo Backup</h3>
                <div class="card-value">
                    <?php if ($next_scheduled): ?>
                        <?php echo date('H:i', $next_scheduled); ?>
                    <?php else: ?>
                        Não agendado
                    <?php endif; ?>
                </div>
                <div class="card-details">
                    <?php if ($next_scheduled): ?>
                        <div><?php echo date('d/m/Y', $next_scheduled); ?></div>
                        <div>Em <?php echo human_time_diff($next_scheduled); ?></div>
                    <?php else: ?>
                        <div>Sistema inativo</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas de Log -->
        <div class="sevo-overview-card">
            <div class="card-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="card-content">
                <h3>Logs do Sistema</h3>
                <div class="card-value"><?php echo $log_stats['total_entries']; ?></div>
                <div class="card-details">
                    <?php if ($log_stats['by_level']['error'] > 0): ?>
                        <div class="error-count">❌ <?php echo $log_stats['by_level']['error']; ?> erros</div>
                    <?php endif; ?>
                    <?php if ($log_stats['by_level']['warning'] > 0): ?>
                        <div class="warning-count">⚠️ <?php echo $log_stats['by_level']['warning']; ?> avisos</div>
                    <?php endif; ?>
                    <div>📝 <?php echo $log_stats['by_level']['info']; ?> informações</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="sevo-quick-actions">
        <h2>Ações Rápidas</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=sevo-backup-manage'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-backup"></span>
                Executar Backup Manual
            </a>
            <a href="<?php echo admin_url('admin.php?page=sevo-backup-restore'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-restore"></span>
                Restaurar Backup
            </a>
            <a href="<?php echo admin_url('admin.php?page=sevo-backup-emails'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-email"></span>
                Ver Histórico de Emails
            </a>
            <a href="<?php echo admin_url('admin.php?page=sevo-backup-settings'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-admin-settings"></span>
                Configurações
            </a>
        </div>
    </div>
    
    <!-- Resumo de Configurações -->
    <div class="sevo-config-summary">
        <div class="config-section">
            <h3>📋 Configurações Atuais</h3>
            <div class="config-grid">
                <div class="config-item">
                    <strong>Frequência:</strong>
                    <span>A cada 6 horas</span>
                </div>
                <div class="config-item">
                    <strong>Horários:</strong>
                    <span>00:00, 06:00, 12:00, 18:00</span>
                </div>
                <div class="config-item">
                    <strong>Email destino:</strong>
                    <span>salvador.egito@gmail.com</span>
                </div>
                <div class="config-item">
                    <strong>Rotação:</strong>
                    <span>Máximo 10 backups</span>
                </div>
                <div class="config-item">
                    <strong>Limite de email:</strong>
                    <span>10MB</span>
                </div>
                <div class="config-item">
                    <strong>Otimização de imagens:</strong>
                    <span>300x300px</span>
                </div>
            </div>
        </div>
        
        <div class="config-section">
            <h3>📦 Conteúdo do Backup</h3>
            <div class="content-list">
                <div class="content-item">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <span>Dados do Fórum Asgaros</span>
                </div>
                <div class="content-item">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span>Eventos e Inscrições</span>
                </div>
                <div class="content-item">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span>Usuários WordPress</span>
                </div>
                <div class="content-item">
                    <span class="dashicons dashicons-admin-page"></span>
                    <span>Posts, Páginas e Comentários</span>
                </div>
                <div class="content-item">
                    <span class="dashicons dashicons-format-image"></span>
                    <span>Imagens Otimizadas</span>
                </div>
                <div class="content-item">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <span>Temas e Plugins</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimos Logs -->
    <?php if (!empty($logs)): ?>
    <div class="sevo-recent-logs">
        <h3>📝 Últimas Atividades</h3>
        <div class="logs-container">
            <?php foreach (array_slice(array_reverse($logs), 0, 5) as $log_line): ?>
                <div class="log-entry <?php 
                    if (strpos($log_line, '[error]') !== false) echo 'log-error';
                    elseif (strpos($log_line, '[warning]') !== false) echo 'log-warning';
                    elseif (strpos($log_line, '[critical]') !== false) echo 'log-critical';
                    else echo 'log-info';
                ?>">
                    <?php echo esc_html($log_line); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="logs-footer">
            <a href="<?php echo admin_url('admin.php?page=sevo-backup-manage'); ?>" class="button">Ver todos os logs</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* === ESTILOS DA PÁGINA DE OVERVIEW === */
.sevo-backup-overview {
    margin: 20px 0;
}

.sevo-backup-overview h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.sevo-backup-overview .description {
    margin-bottom: 30px;
    font-size: 14px;
    color: #666;
}

/* Cards de Overview */
.sevo-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.sevo-overview-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.sevo-overview-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.sevo-overview-card.healthy {
    border-left: 4px solid #4CAF50;
}

.sevo-overview-card.warning {
    border-left: 4px solid #FF9800;
}

.sevo-overview-card.critical {
    border-left: 4px solid #f44336;
}

.card-icon {
    font-size: 24px;
    color: #666;
    flex-shrink: 0;
}

.sevo-overview-card.healthy .card-icon {
    color: #4CAF50;
}

.sevo-overview-card.warning .card-icon {
    color: #FF9800;
}

.sevo-overview-card.critical .card-icon {
    color: #f44336;
}

.card-content {
    flex: 1;
}

.card-content h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
}

.card-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 8px;
}

.card-details {
    font-size: 12px;
    color: #888;
}

.card-details div {
    margin-bottom: 3px;
}

.issue-item {
    color: #e74c3c;
    font-weight: 500;
}

.error-count {
    color: #e74c3c;
}

.warning-count {
    color: #f39c12;
}

/* Ações Rápidas */
.sevo-quick-actions {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sevo-quick-actions h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.action-buttons .button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 14px;
    text-decoration: none;
}

.action-buttons .button-hero {
    font-size: 16px;
    padding: 15px 25px;
}

/* Resumo de Configurações */
.sevo-config-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.config-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.config-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    font-size: 16px;
}

.config-grid {
    display: grid;
    gap: 12px;
}

.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.config-item:last-child {
    border-bottom: none;
}

.config-item strong {
    color: #2c3e50;
    font-size: 13px;
}

.config-item span {
    color: #666;
    font-size: 13px;
}

.content-list {
    display: grid;
    gap: 10px;
}

.content-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: #555;
}

.content-item .dashicons {
    color: #667eea;
    font-size: 16px;
}

/* Logs Recentes */
.sevo-recent-logs {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sevo-recent-logs h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
}

.logs-container {
    background: #2c3e50;
    border-radius: 6px;
    padding: 15px;
    max-height: 200px;
    overflow-y: auto;
}

.log-entry {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    margin-bottom: 5px;
    color: #f8f9fa;
}

.log-entry.log-error {
    color: #ff6b6b;
}

.log-entry.log-warning {
    color: #feca57;
}

.log-entry.log-critical {
    color: #ff3838;
    font-weight: bold;
}

.logs-footer {
    margin-top: 15px;
    text-align: right;
}

/* Responsividade */
@media (max-width: 768px) {
    .sevo-overview-cards {
        grid-template-columns: 1fr;
    }
    
    .sevo-config-summary {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .button {
        justify-content: center;
        width: 100%;
    }
    
    .sevo-overview-card {
        flex-direction: column;
        text-align: center;
    }
    
    .card-icon {
        align-self: center;
    }
}
</style>