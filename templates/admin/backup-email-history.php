<?php
/**
 * Página de Histórico de Emails do Sistema de Backup
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

// Buscar histórico de emails do banco de dados
global $wpdb;

// Criar tabela de histórico de emails se não existir
$table_name = $wpdb->prefix . 'sevo_backup_email_log';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id int(11) NOT NULL AUTO_INCREMENT,
    email_type varchar(50) NOT NULL,
    recipient varchar(255) NOT NULL,
    subject varchar(500) NOT NULL,
    backup_filename varchar(255) DEFAULT NULL,
    file_size bigint DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    error_message text DEFAULT NULL,
    sent_at datetime NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sent_at (sent_at),
    KEY idx_status (status),
    KEY idx_email_type (email_type)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Filtros
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';

// Query para buscar emails
$where_clauses = array('1=1');
if ($filter_type) {
    $where_clauses[] = $wpdb->prepare('email_type = %s', $filter_type);
}
if ($filter_status) {
    $where_clauses[] = $wpdb->prepare('status = %s', $filter_status);
}
if ($filter_date) {
    $where_clauses[] = $wpdb->prepare('DATE(sent_at) = %s', $filter_date);
}

$where_clause = implode(' AND ', $where_clauses);
$email_logs = $wpdb->get_results("
    SELECT * FROM $table_name 
    WHERE $where_clause 
    ORDER BY sent_at DESC 
    LIMIT 100
");

// Estatísticas
$stats = $wpdb->get_row("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM $table_name
");
?>

<div class="wrap sevo-email-history">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-email"></span>
        Histórico de Emails do Backup
    </h1>
    <p class="description">Acompanhe todos os emails enviados pelo sistema de backup</p>
    
    <!-- Estatísticas -->
    <div class="sevo-email-stats">
        <div class="stat-card stat-total">
            <div class="stat-number"><?php echo $stats->total ?? 0; ?></div>
            <div class="stat-label">Total de Emails</div>
        </div>
        <div class="stat-card stat-sent">
            <div class="stat-number"><?php echo $stats->sent ?? 0; ?></div>
            <div class="stat-label">Enviados com Sucesso</div>
        </div>
        <div class="stat-card stat-failed">
            <div class="stat-number"><?php echo $stats->failed ?? 0; ?></div>
            <div class="stat-label">Falharam</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-number"><?php echo $stats->pending ?? 0; ?></div>
            <div class="stat-label">Pendentes</div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="sevo-email-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="sevo-backup-emails">
            
            <div class="filter-group">
                <label for="filter_type">Tipo de Email:</label>
                <select name="filter_type" id="filter_type">
                    <option value="">Todos os tipos</option>
                    <option value="backup_success" <?php selected($filter_type, 'backup_success'); ?>>Backup Realizado</option>
                    <option value="backup_failed" <?php selected($filter_type, 'backup_failed'); ?>>Falha no Backup</option>
                    <option value="health_warning" <?php selected($filter_type, 'health_warning'); ?>>Aviso de Saúde</option>
                    <option value="health_critical" <?php selected($filter_type, 'health_critical'); ?>>Erro Crítico</option>
                    <option value="notification" <?php selected($filter_type, 'notification'); ?>>Notificação</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_status">Status:</label>
                <select name="filter_status" id="filter_status">
                    <option value="">Todos os status</option>
                    <option value="sent" <?php selected($filter_status, 'sent'); ?>>Enviado</option>
                    <option value="failed" <?php selected($filter_status, 'failed'); ?>>Falhou</option>
                    <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pendente</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_date">Data:</label>
                <input type="date" name="filter_date" id="filter_date" value="<?php echo esc_attr($filter_date); ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button">Filtrar</button>
                <a href="<?php echo admin_url('admin.php?page=sevo-backup-emails'); ?>" class="button">Limpar</a>
            </div>
        </form>
    </div>
    
    <!-- Lista de Emails -->
    <div class="sevo-email-list">
        <?php if (empty($email_logs)): ?>
            <div class="sevo-empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhum email encontrado</h3>
                <p>Não há emails no histórico com os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <div class="email-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-date">Data/Hora</th>
                            <th class="column-type">Tipo</th>
                            <th class="column-subject">Assunto</th>
                            <th class="column-recipient">Destinatário</th>
                            <th class="column-backup">Backup</th>
                            <th class="column-status">Status</th>
                            <th class="column-actions">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($email_logs as $log): ?>
                            <tr>
                                <td class="column-date">
                                    <strong><?php echo date('d/m/Y', strtotime($log->sent_at)); ?></strong><br>
                                    <small><?php echo date('H:i:s', strtotime($log->sent_at)); ?></small>
                                </td>
                                <td class="column-type">
                                    <span class="email-type-badge type-<?php echo esc_attr($log->email_type); ?>">
                                        <?php 
                                        switch($log->email_type) {
                                            case 'backup_success': echo '✅ Backup'; break;
                                            case 'backup_failed': echo '❌ Erro'; break;
                                            case 'health_warning': echo '⚠️ Aviso'; break;
                                            case 'health_critical': echo '🚨 Crítico'; break;
                                            case 'notification': echo '📧 Notificação'; break;
                                            default: echo '📬 ' . ucfirst($log->email_type);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="column-subject">
                                    <strong><?php echo esc_html($log->subject); ?></strong>
                                </td>
                                <td class="column-recipient">
                                    <a href="mailto:<?php echo esc_attr($log->recipient); ?>">
                                        <?php echo esc_html($log->recipient); ?>
                                    </a>
                                </td>
                                <td class="column-backup">
                                    <?php if ($log->backup_filename): ?>
                                        <div class="backup-info">
                                            <div class="filename"><?php echo esc_html($log->backup_filename); ?></div>
                                            <?php if ($log->file_size): ?>
                                                <div class="filesize"><?php echo size_format($log->file_size); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-backup">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                                        <?php 
                                        switch($log->status) {
                                            case 'sent': echo '✅ Enviado'; break;
                                            case 'failed': echo '❌ Falhou'; break;
                                            case 'pending': echo '⏳ Pendente'; break;
                                            default: echo '❓ ' . ucfirst($log->status);
                                        }
                                        ?>
                                    </span>
                                    <?php if ($log->error_message): ?>
                                        <div class="error-message" title="<?php echo esc_attr($log->error_message); ?>">
                                            ⚠️ Ver erro
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="#" class="view-email" data-id="<?php echo $log->id; ?>">Ver detalhes</a>
                                        </span>
                                        <?php if ($log->backup_filename): ?>
                                            | <span class="download">
                                                <a href="<?php echo admin_url('admin.php?page=sevo-backup-manage'); ?>">Ver backup</a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Informações do Sistema -->
    <div class="sevo-email-info">
        <h3>ℹ️ Informações do Sistema de Email</h3>
        <div class="info-grid">
            <div class="info-item">
                <strong>Email configurado:</strong>
                <span>salvador.egito@gmail.com</span>
            </div>
            <div class="info-item">
                <strong>Limite de anexo:</strong>
                <span>10MB</span>
            </div>
            <div class="info-item">
                <strong>Servidor SMTP:</strong>
                <span><?php echo ini_get('SMTP') ?: 'Configuração padrão do WordPress'; ?></span>
            </div>
            <div class="info-item">
                <strong>Retenção de logs:</strong>
                <span>90 dias</span>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalhes do email -->
<div id="email-details-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-content">
        <div class="sevo-modal-header">
            <h3>Detalhes do Email</h3>
            <button type="button" class="sevo-modal-close">&times;</button>
        </div>
        <div class="sevo-modal-body">
            <div id="email-details-content">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
/* === ESTILOS DA PÁGINA DE HISTÓRICO DE EMAILS === */
.sevo-email-history {
    margin: 20px 0;
}

.sevo-email-history h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

/* Estatísticas */
.sevo-email-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}

.stat-total .stat-number { color: #2196F3; }
.stat-sent .stat-number { color: #4CAF50; }
.stat-failed .stat-number { color: #f44336; }
.stat-pending .stat-number { color: #FF9800; }

/* Filtros */
.sevo-email-filters {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sevo-email-filters form {
    display: flex;
    align-items: end;
    gap: 20px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    font-size: 13px;
    color: #2c3e50;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

/* Lista de Emails */
.sevo-email-list {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.email-table-wrapper {
    overflow-x: auto;
}

.column-date { width: 120px; }
.column-type { width: 100px; }
.column-subject { width: 300px; }
.column-recipient { width: 200px; }
.column-backup { width: 180px; }
.column-status { width: 120px; }
.column-actions { width: 120px; }

/* Badges */
.email-type-badge,
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.type-backup_success { background: #e8f5e8; color: #2e7d32; }
.type-backup_failed { background: #ffebee; color: #c62828; }
.type-health_warning { background: #fff3e0; color: #f57c00; }
.type-health_critical { background: #ffebee; color: #d32f2f; }
.type-notification { background: #e3f2fd; color: #1976d2; }

.status-sent { background: #e8f5e8; color: #2e7d32; }
.status-failed { background: #ffebee; color: #c62828; }
.status-pending { background: #fff3e0; color: #f57c00; }

/* Info do backup */
.backup-info {
    font-size: 12px;
}

.backup-info .filename {
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 2px;
}

.backup-info .filesize {
    color: #666;
}

.no-backup {
    color: #999;
    font-style: italic;
}

/* Mensagem de erro */
.error-message {
    font-size: 11px;
    color: #d32f2f;
    cursor: help;
    margin-top: 2px;
}

/* Estado vazio */
.sevo-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.sevo-empty-state h3 {
    margin-bottom: 10px;
    color: #2c3e50;
}

/* Informações do sistema */
.sevo-email-info {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sevo-email-info h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    color: #2c3e50;
    font-size: 13px;
}

.info-item span {
    color: #666;
    font-size: 13px;
}

/* Modal */
.sevo-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sevo-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.sevo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.sevo-modal-header h3 {
    margin: 0;
}

.sevo-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.sevo-modal-body {
    padding: 20px;
}

/* Responsividade */
@media (max-width: 768px) {
    .sevo-email-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .sevo-email-filters form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ver detalhes do email
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-email')) {
            e.preventDefault();
            const emailId = e.target.dataset.id;
            showEmailDetails(emailId);
        }
    });
    
    // Fechar modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('sevo-modal-close') || e.target.classList.contains('sevo-modal')) {
            document.getElementById('email-details-modal').style.display = 'none';
        }
    });
    
    function showEmailDetails(emailId) {
        const modal = document.getElementById('email-details-modal');
        const content = document.getElementById('email-details-content');
        
        content.innerHTML = '<div style="text-align: center; padding: 20px;">Carregando...</div>';
        modal.style.display = 'flex';
        
        // Simular carregamento de detalhes (implementar AJAX real)
        setTimeout(() => {
            content.innerHTML = `
                <div class="email-detail">
                    <h4>Informações do Email</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>ID:</strong> <span>${emailId}</span>
                        </div>
                        <div class="detail-item">
                            <strong>Status:</strong> <span>Enviado com sucesso</span>
                        </div>
                        <div class="detail-item">
                            <strong>Tentativas:</strong> <span>1</span>
                        </div>
                        <div class="detail-item">
                            <strong>Servidor SMTP:</strong> <span>Padrão WordPress</span>
                        </div>
                    </div>
                    <h4>Conteúdo do Email</h4>
                    <div class="email-content">
                        <p>Backup do sistema Sevo Eventos realizado com sucesso.</p>
                        <p>Este backup inclui todos os dados do fórum, eventos e configurações do sistema.</p>
                    </div>
                </div>
            `;
        }, 500);
    }
});
</script>