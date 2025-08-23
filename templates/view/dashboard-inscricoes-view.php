<?php
/**
 * Template do Dashboard de Inscrições
 * Exibe uma interface para gerenciar inscrições nos eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carrega a função dos summary cards
if (!function_exists('sevo_get_summary_cards')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
}

// Verificar permissões
$is_super_admin = is_super_admin();
$is_admin = current_user_can('manage_options');
$can_manage_all = $is_super_admin || $is_admin || sevo_check_user_permission('manage_inscricoes');
$current_user = wp_get_current_user();

// Usar o modelo para buscar inscrições
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model.php';
$inscricao_model = new Sevo_Inscricao_Model();

// Parâmetros de paginação
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;

// Preparar filtros
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$evento_filter = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;
$org_filter = isset($_GET['organizacao_id']) ? intval($_GET['organizacao_id']) : 0;

$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}
if (!empty($status_filter)) {
    $filters['status'] = $status_filter;
}
if (!empty($evento_filter)) {
    $filters['evento_id'] = $evento_filter;
}
if (!empty($org_filter)) {
    $filters['organizacao_id'] = $org_filter;
}

// Buscar inscrições com paginação
$result = $inscricao_model->get_paginated($paged, $per_page, $filters);
$inscricoes = $result['items'];
$total_items = $result['total'];
$total_pages = $result['total_pages'];
?>

<div class="sevo-dashboard-wrapper">
    <!-- Summary Cards -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>

    <!-- Filtros Simplificados -->
    <div class="sevo-dashboard-filters">
        <div class="sevo-filters-single-row">
            <div class="sevo-filter-group">
                <label for="filter-usuario">Usuário:</label>
                <input type="text" id="filter-usuario" name="usuario" placeholder="Nome..." class="sevo-filter-input">
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-organizacao">Organização:</label>
                <select id="filter-organizacao" name="organizacao_id" class="sevo-filter-select">
                    <option value="">Todas</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-evento">Evento:</label>
                <select id="filter-evento" name="evento_id" class="sevo-filter-select">
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-status">Status:</label>
                <select id="filter-status" name="status" class="sevo-filter-select">
                    <option value="">Todos</option>
                    <option value="solicitada">Solicitada</option>
                    <option value="aceita">Aceita</option>
                    <option value="rejeitada">Rejeitada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-periodo">Período:</label>
                <select id="filter-periodo" name="periodo" class="sevo-filter-select">
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="sevo-filter-actions">
                <button type="button" class="sevo-btn sevo-btn-secondary" id="clear-filters" title="Limpar Filtros">
                    <i class="dashicons dashicons-dismiss"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de Inscrições em Cards -->
    <div class="sevo-inscricoes-container">
        <div class="sevo-table-loading" id="table-loading">
            <div class="sevo-spinner"></div>
            <p>Carregando inscrições...</p>
        </div>
        
        <div class="sevo-inscricoes-list" id="inscricoes-list">
            <?php if (!empty($inscricoes)) : ?>
                <?php foreach ($inscricoes as $inscricao) : ?>
                    <?php
                    $status_class = 'status-' . $inscricao->status;
                    $status_text = ucfirst($inscricao->status);
                    
                    // Formatação da data
                    $data_formatted = $inscricao->created_at ? date('d/m/Y H:i', strtotime($inscricao->created_at)) : '';
                    $data_evento = $inscricao->data_inicio_evento ? date('d/m/Y', strtotime($inscricao->data_inicio_evento)) : 'Data não definida';
                    
                    // Status badge text
                    $status_labels = [
                        'solicitada' => 'Solicitada',
                        'aceita' => 'Aceita',
                        'rejeitada' => 'Rejeitada',
                        'cancelada' => 'Cancelada'
                    ];
                    $status_display = $status_labels[$inscricao->status] ?? ucfirst($inscricao->status);
                    ?>
                    <div class="sevo-inscricao-card" data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" data-status="<?php echo esc_attr($inscricao->status); ?>">
                        <!-- Imagem do Evento -->
                        <div class="sevo-card-image">
                            <?php if (!empty($inscricao->evento_imagem)): ?>
                                <img src="<?php echo esc_url($inscricao->evento_imagem); ?>" alt="<?php echo esc_attr($inscricao->evento_titulo); ?>">
                            <?php else: ?>
                                <div class="sevo-card-placeholder">
                                    <i class="dashicons dashicons-calendar-alt"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Conteúdo Principal -->
                        <div class="sevo-card-content">
                            <!-- Header do Card -->
                            <div class="sevo-card-header">
                                <h3 class="sevo-card-title"><?php echo esc_html($inscricao->evento_titulo); ?></h3>
                                <span class="sevo-status-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_display); ?>
                                </span>
                            </div>
                            
                            <!-- Informações do Evento -->
                            <div class="sevo-card-info">
                                <div class="sevo-info-row">
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-calendar"></i>
                                        <span><strong>Data do Evento:</strong> <?php echo esc_html($data_evento); ?></span>
                                    </div>
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-building"></i>
                                        <span><strong>Organização:</strong> <?php echo esc_html($inscricao->organizacao_titulo); ?></span>
                                    </div>
                                </div>
                                
                                <div class="sevo-info-row">
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-category"></i>
                                        <span><strong>Tipo:</strong> <?php echo esc_html($inscricao->tipo_evento_titulo ?? 'Não definido'); ?></span>
                                    </div>
                                    <?php if ($can_manage_all): ?>
                                        <div class="sevo-info-item">
                                            <i class="dashicons dashicons-admin-users"></i>
                                            <span><strong>Usuário:</strong> <?php echo esc_html($inscricao->usuario_nome); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="sevo-info-row">
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-clock"></i>
                                        <span><strong>Inscrito em:</strong> <?php echo esc_html($data_formatted); ?></span>
                                    </div>
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-id"></i>
                                        <span><strong>ID:</strong> #<?php echo esc_html($inscricao->id); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações do Card -->
                        <div class="sevo-card-actions">
                            <?php if ($can_manage_all): ?>
                                <!-- Ações para Gestores/Admins -->
                                <div class="sevo-admin-actions">
                                    <?php if ($inscricao->status === 'solicitada'): ?>
                                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-success approve-btn" 
                                                data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Aprovar Inscrição">
                                            <i class="dashicons dashicons-yes"></i>
                                        </button>
                                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger reject-btn" 
                                                data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Rejeitar Inscrição">
                                            <i class="dashicons dashicons-no"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                                            data-evento-id="<?php echo esc_attr($inscricao->evento_id); ?>" title="Ver Detalhes do Evento">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                    
                                    <?php if (is_super_admin()): ?>
                                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-warning edit-inscricao-btn" 
                                                data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Editar Inscrição">
                                            <i class="dashicons dashicons-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Ações para Usuários Comuns -->
                                <div class="sevo-user-actions">
                                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                                            data-evento-id="<?php echo esc_attr($inscricao->evento_id); ?>" title="Ver Detalhes do Evento">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                    
                                    <?php if (in_array($inscricao->status, ['solicitada', 'aceita']) && $inscricao->usuario_id == get_current_user_id()): ?>
                                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger cancel-own-btn" 
                                                data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Cancelar Minha Inscrição">
                                            <i class="dashicons dashicons-dismiss"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sevo-no-inscricoes">
                    <div class="sevo-empty-state">
                        <i class="dashicons dashicons-clipboard"></i>
                        <h3>Nenhuma inscrição encontrada</h3>
                        <p>Não há inscrições para exibir com os filtros aplicados.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sevo-no-results" id="no-results" style="display: none;">
            <div class="sevo-empty-state">
                <i class="dashicons dashicons-search"></i>
                <h3>Nenhum resultado encontrado</h3>
                <p>Tente ajustar os filtros ou limpar a busca.</p>
            </div>
        </div>
        
        <!-- Loading para scroll infinito -->
        <div id="infinite-loading" style="display: none; text-align: center; padding: 20px;">
            <div class="sevo-spinner"></div>
            <p>Carregando mais inscrições...</p>
        </div>
        
        <!-- Fim da lista -->
        <div id="end-of-list" style="display: none; text-align: center; padding: 20px; color: #666;">
            <p>Todas as inscrições foram carregadas</p>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="sevo-modal" id="confirmation-modal" style="display: none;">
    <div class="sevo-modal-overlay"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <h3 id="modal-title">Confirmar Ação</h3>
            <button type="button" class="sevo-modal-close" id="modal-close">×</button>
        </div>
        <div class="sevo-modal-content">
            <p id="modal-message">Tem certeza que deseja realizar esta ação?</p>
            <div class="sevo-modal-input" id="modal-input-container" style="display: none;">
                <label for="modal-input">Motivo (opcional):</label>
                <textarea id="modal-input" rows="3" placeholder="Digite o motivo..."></textarea>
            </div>
        </div>
        <div class="sevo-modal-footer">
            <button type="button" class="sevo-btn sevo-btn-secondary" id="modal-cancel">Cancelar</button>
            <button type="button" class="sevo-btn sevo-btn-primary" id="modal-confirm">Confirmar</button>
        </div>
    </div>
</div>





<!-- Modal do Evento -->
<div id="sevo-event-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-overlay"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <h2>Detalhes do Evento</h2>
            <button type="button" class="sevo-modal-close" onclick="SevoDashboard.closeEventModal()">&times;</button>
        </div>
        <div class="sevo-modal-loading" style="display: none;">
            <div class="sevo-loading-spinner"></div>
            <p>Carregando...</p>
        </div>
        <div class="sevo-modal-content"></div>
    </div>
</div>

<!-- Modal de Edição de Inscrição -->
<?php if ($is_super_admin): ?>
<div id="sevo-edit-inscricao-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-backdrop" onclick="SevoDashboard.closeEditModal()"></div>
    <div class="sevo-modal-dialog">
        <div class="sevo-loading" id="sevo-edit-loading">
            <div class="sevo-spinner"></div>
            <p>Carregando dados da inscrição...</p>
        </div>
        <div class="sevo-modal-content-area" id="sevo-edit-content" style="display: none;"></div>
    </div>
</div>
<?php endif; ?>
</div>

<style>
/* === FILTROS EM LINHA === */
.sevo-filters-single-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}

.sevo-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 120px;
}

.sevo-filter-group label {
    font-size: 12px;
    font-weight: 500;
    color: #495057;
    margin: 0;
}

.sevo-filter-input,
.sevo-filter-select {
    padding: 6px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
    background: white;
    min-width: 120px;
}

.sevo-filter-actions {
    display: flex;
    align-items: end;
    margin-left: auto;
}

/* === LAYOUT EM CARDS PARA INSCRIÇÕES - CORRIGIDO === */

/* Container principal */
.sevo-inscricoes-container {
    margin-top: 20px;
}

.sevo-inscricoes-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Card individual da inscrição - ALTURA CORRIGIDA */
.sevo-inscricao-card {
    display: flex;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: box-shadow 0.2s ease;
    border-left: 3px solid #e0e0e0;
    position: relative;
    min-height: 100px;
    /* Removed max-height restriction to prevent text cutting */
}

.sevo-inscricao-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

/* Cores da borda baseadas no status */
.sevo-inscricao-card[data-status="solicitada"] {
    border-left-color: #ffc107;
}

.sevo-inscricao-card[data-status="aceita"] {
    border-left-color: #28a745;
}

.sevo-inscricao-card[data-status="rejeitada"] {
    border-left-color: #dc3545;
}

.sevo-inscricao-card[data-status="cancelada"] {
    border-left-color: #6c757d;
}

/* Imagem do evento - CORRIGIDA */
.sevo-card-image {
    width: 100px;
    min-width: 100px;
    height: 100px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sevo-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sevo-card-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.sevo-card-placeholder i {
    font-size: 28px;
}

/* Conteúdo principal - TEXTO NÃO CORTADO */
.sevo-card-content {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
    min-width: 0;
    justify-content: space-between;
}

/* Header do card */
.sevo-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 10px;
}

.sevo-card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    line-height: 1.3;
    word-wrap: break-word;
    /* REMOVIDO: text-overflow e white-space para não cortar texto */
}

/* Status badge */
.sevo-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    flex-shrink: 0;
}

.sevo-status-badge.status-solicitada {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.sevo-status-badge.status-aceita {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sevo-status-badge.status-rejeitada {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sevo-status-badge.status-cancelada {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

/* Informações do card - MELHORADAS */
.sevo-card-info {
    flex: 1;
}

.sevo-info-row {
    display: flex;
    gap: 15px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.sevo-info-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: #495057;
    min-width: 0;
    flex: 1;
    min-width: 200px;
}

.sevo-info-item i {
    color: #667eea;
    font-size: 14px;
    flex-shrink: 0;
}

.sevo-info-item strong {
    color: #2c3e50;
    font-weight: 500;
}

.sevo-info-item span {
    word-wrap: break-word;
    min-width: 0;
    /* REMOVIDO: text-overflow para não cortar */
}

/* Ações do card - SOMENTE ÍCONES */
.sevo-card-actions {
    width: 100px;
    min-width: 100px;
    flex-shrink: 0;
    padding: 15px 10px;
    background: #f8f9fa;
    border-left: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    gap: 6px;
    justify-content: center;
    align-items: center;
}

.sevo-admin-actions,
.sevo-user-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
}

/* Botões de ação - SOMENTE ÍCONES */
.sevo-btn {
    padding: 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-align: center;
    min-height: 32px;
    min-width: 32px;
    position: relative;
}

.sevo-btn i {
    font-size: 16px;
}

.sevo-btn-sm {
    padding: 6px;
    font-size: 12px;
    min-height: 28px;
    min-width: 28px;
}

.sevo-btn-success {
    background: #28a745;
    color: white;
}

.sevo-btn-success:hover {
    background: #218838;
    transform: translateY(-1px);
}

.sevo-btn-danger {
    background: #dc3545;
    color: white;
}

.sevo-btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.sevo-btn-info {
    background: #17a2b8;
    color: white;
}

.sevo-btn-info:hover {
    background: #138496;
    transform: translateY(-1px);
}

.sevo-btn-warning {
    background: #ffc107;
    color: #212529;
}

.sevo-btn-warning:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

.sevo-btn-secondary {
    background: #6c757d;
    color: white;
}

.sevo-btn-secondary:hover {
    background: #545b62;
    transform: translateY(-1px);
}

/* Estado vazio */
.sevo-no-inscricoes {
    padding: 60px 20px;
    text-align: center;
}

.sevo-empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.sevo-empty-state i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 20px;
}

.sevo-empty-state h3 {
    font-size: 1.2rem;
    color: #495057;
    margin-bottom: 10px;
}

.sevo-empty-state p {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* === RESPONSIVIDADE OTIMIZADA === */

/* Tablets */
@media (max-width: 768px) {
    .sevo-inscricao-card {
        flex-direction: column;
        max-height: none;
        min-height: auto;
    }
    
    .sevo-card-image {
        width: 100%;
        height: 60px;
    }
    
    .sevo-card-actions {
        width: 100%;
        border-left: none;
        border-top: 1px solid #e9ecef;
        flex-direction: row;
        justify-content: center;
        padding: 12px;
    }
    
    .sevo-admin-actions,
    .sevo-user-actions {
        flex-direction: row;
        gap: 8px;
        justify-content: center;
    }
    
    .sevo-info-row {
        flex-direction: column;
        gap: 4px;
    }
    
    .sevo-card-header {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
}

/* Smartphones */
@media (max-width: 480px) {
    .sevo-inscricoes-list {
        gap: 10px;
    }
    
    .sevo-inscricao-card {
        margin: 0 -5px;
        border-radius: 6px;
    }
    
    .sevo-card-content {
        padding: 10px 12px;
    }
    
    .sevo-card-actions {
        padding: 10px;
    }
    
    .sevo-btn {
        min-width: 32px;
        min-height: 32px;
    }
    
    .sevo-card-title {
        font-size: 0.9rem;
    }
    
    .sevo-info-item {
        font-size: 0.75rem;
    }
}

/* Performance optimizations */
.sevo-inscricao-card {
    will-change: transform;
    contain: layout style;
}

.sevo-inscricoes-list {
    contain: layout;
}

/* === ANIMAÇÕES === */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sevo-inscricao-card {
    animation: fadeInUp 0.3s ease-out;
}

/* Loading state */
.sevo-table-loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.sevo-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>