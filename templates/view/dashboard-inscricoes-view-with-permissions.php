<?php
/**
 * Template do Dashboard de Inscrições com controle de permissões organizacionais
 * Exibe uma interface para gerenciar inscrições nos eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carrega a função dos summary cards
if (!function_exists('sevo_get_summary_cards')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/components/summary-cards.php';
}

// Verificar se o usuário está logado
if (!is_user_logged_in()) {
    echo '<div class="sevo-dashboard-error"><p>Você precisa estar logado para acessar o dashboard de inscrições.</p><p><a href="' . wp_login_url(get_permalink()) . '">Fazer login</a></p></div>';
    return;
}

$user_id = get_current_user_id();

// Verificar permissões organizacionais
$can_manage_all = user_can($user_id, 'manage_options');
$can_view_own = true; // Todos os usuários logados podem ver suas próprias inscrições

// Usar o modelo para buscar inscrições
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Inscricao_Model_With_Permissions.php';
$inscricao_model = new Sevo_Inscricao_Model_With_Permissions();

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
$result = $inscricao_model->get_for_user($user_id, $paged, $per_page, $filters);
$inscricoes = $result['data'];
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
                    
                    // Verificar se o usuário pode gerenciar esta inscrição
                    $can_manage_inscricao = $can_manage_all || sevo_user_can_manage_organization($user_id, $inscricao->organizacao_id);
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
                            <?php if ($can_manage_inscricao): ?>
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
                                    
                                    <?php if (user_can($user_id, 'manage_options')): ?>
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
                                    
                                    <?php if (in_array($inscricao->status, ['solicitada', 'aceita']) && $inscricao->usuario_id == $user_id): ?>
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
<?php if (user_can($user_id, 'manage_options')): ?>
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

/* Imagem do card */
.sevo-card-image {
    width: 120px;
    min-width: 120px;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
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
    color: #999;
    font-size: 24px;
}

/* Conteúdo do card */
.sevo-card-content {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.sevo-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.sevo-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    flex: 1;
    margin-right: 10px;
}

.sevo-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.status-solicitada {
    background-color: #fff3cd;
    color: #856404;
}

.status-aceita {
    background-color: #d4edda;
    color: #155724;
}

.status-rejeitada {
    background-color: #f8d7da;
    color: #721c24;
}

.status-cancelada {
    background-color: #d1ecf1;
    color: #0c5460;
}

.sevo-card-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sevo-info-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.sevo-info-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #666;
}

.sevo-info-item i {
    font-size: 14px;
}

.sevo-card-actions {
    padding: 15px;
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
}

.sevo-admin-actions,
.sevo-user-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.sevo-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.sevo-btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.sevo-btn-primary {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.sevo-btn-secondary {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}

.sevo-btn-success {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

.sevo-btn-danger {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.sevo-btn-warning {
    background-color: #ffc107;
    color: #212529;
    border-color: #ffc107;
}

.sevo-btn-info {
    background-color: #17a2b8;
    color: white;
    border-color: #17a2b8;
}

.sevo-btn:hover {
    opacity: 0.85;
}

.sevo-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Estados vazios */
.sevo-no-inscricoes,
.sevo-no-results {
    text-align: center;
    padding: 40px 20px;
}

.sevo-empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.sevo-empty-state h3 {
    margin: 0 0 10px;
    color: #666;
    font-size: 20px;
}

.sevo-empty-state p {
    margin: 0;
    color: #999;
    font-size: 14px;
}

/* Loading */
.sevo-table-loading,
#infinite-loading {
    text-align: center;
    padding: 20px;
}

.sevo-spinner {
    width: 30px;
    height: 30px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Modal */
.sevo-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sevo-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.sevo-modal-container {
    position: relative;
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 10001;
}

.sevo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
}

.sevo-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.sevo-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sevo-modal-content {
    padding: 20px;
}

.sevo-modal-input {
    margin-top: 15px;
}

.sevo-modal-input label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.sevo-modal-input textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    resize: vertical;
}

.sevo-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
    .sevo-filters-single-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .sevo-filter-group {
        width: 100%;
        min-width: auto;
    }
    
    .sevo-inscricao-card {
        flex-direction: column;
    }
    
    .sevo-card-image {
        width: 100%;
        min-width: 100%;
        height: 150px;
    }
    
    .sevo-info-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .sevo-card-actions {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funções para manipular o dashboard de inscrições
    const SevoDashboard = {
        init: function() {
            this.bindEvents();
            this.loadFilterOptions();
            this.loadStats();
        },
        
        bindEvents: function() {
            // Filtros
            document.getElementById('filter-usuario').addEventListener('input', this.debounce(this.applyFilters.bind(this), 500));
            document.getElementById('filter-organizacao').addEventListener('change', this.applyFilters.bind(this));
            document.getElementById('filter-evento').addEventListener('change', this.applyFilters.bind(this));
            document.getElementById('filter-status').addEventListener('change', this.applyFilters.bind(this));
            document.getElementById('filter-periodo').addEventListener('change', this.applyFilters.bind(this));
            document.getElementById('clear-filters').addEventListener('click', this.clearFilters.bind(this));
            
            // Botões de ação
            document.addEventListener('click', (e) => {
                if (e.target.closest('.approve-btn')) {
                    this.confirmAction('Aprovar Inscrição', 'Tem certeza que deseja aprovar esta inscrição?', e.target.closest('.approve-btn').dataset.inscricaoId, 'aceita');
                }
                if (e.target.closest('.reject-btn')) {
                    this.showRejectModal(e.target.closest('.reject-btn').dataset.inscricaoId);
                }
                if (e.target.closest('.cancel-own-btn')) {
                    this.confirmAction('Cancelar Inscrição', 'Tem certeza que deseja cancelar sua inscrição?', e.target.closest('.cancel-own-btn').dataset.inscricaoId, 'cancelada');
                }
                if (e.target.closest('.view-event-btn')) {
                    this.viewEvent(e.target.closest('.view-event-btn').dataset.eventoId);
                }
                if (e.target.closest('.edit-inscricao-btn')) {
                    this.editInscricao(e.target.closest('.edit-inscricao-btn').dataset.inscricaoId);
                }
            });
            
            // Modal de confirmação
            document.getElementById('modal-confirm').addEventListener('click', this.handleModalConfirm.bind(this));
            document.getElementById('modal-cancel').addEventListener('click', this.closeModal.bind(this));
            document.getElementById('modal-close').addEventListener('click', this.closeModal.bind(this));
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        applyFilters: function() {
            // Implementar a lógica de aplicação de filtros
            console.log('Aplicando filtros...');
            this.loadStats();
        },
        
        clearFilters: function() {
            document.getElementById('filter-usuario').value = '';
            document.getElementById('filter-organizacao').value = '';
            document.getElementById('filter-evento').value = '';
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-periodo').value = '';
            this.applyFilters();
        },
        
        loadFilterOptions: function() {
            // Carregar opções para os filtros
            fetch(sevoDashboardInscricoes.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sevo_dashboard_get_filter_options',
                    nonce: sevoDashboardInscricoes.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.populateFilterOptions(data.data);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar opções de filtro:', error);
            });
        },
        
        populateFilterOptions: function(options) {
            // Preencher as opções dos filtros
            if (options.organizacoes) {
                const orgSelect = document.getElementById('filter-organizacao');
                orgSelect.innerHTML = '<option value="">Todas</option>';
                options.organizacoes.forEach(org => {
                    const option = document.createElement('option');
                    option.value = org.id;
                    option.textContent = org.organizacao_titulo;
                    orgSelect.appendChild(option);
                });
            }
            
            if (options.eventos) {
                const eventoSelect = document.getElementById('filter-evento');
                eventoSelect.innerHTML = '<option value="">Todos</option>';
                options.eventos.forEach(evento => {
                    const option = document.createElement('option');
                    option.value = evento.id;
                    option.textContent = evento.evento_titulo;
                    eventoSelect.appendChild(option);
                });
            }
            
            if (options.periodos) {
                const periodoSelect = document.getElementById('filter-periodo');
                periodoSelect.innerHTML = '<option value="">Todos</option>';
                options.periodos.forEach(periodo => {
                    const option = document.createElement('option');
                    option.value = periodo.periodo;
                    option.textContent = periodo.periodo_formatted;
                    periodoSelect.appendChild(option);
                });
            }
        },
        
        loadStats: function() {
            // Carregar estatísticas
            const filters = this.getFilters();
            
            fetch(sevoDashboardInscricoes.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sevo_dashboard_get_stats',
                    nonce: sevoDashboardInscricoes.nonce,
                    filters: JSON.stringify(filters)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar estatísticas nos summary cards
                    console.log('Estatísticas carregadas:', data.data);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar estatísticas:', error);
            });
        },
        
        getFilters: function() {
            return {
                usuario: document.getElementById('filter-usuario').value,
                organizacao_id: document.getElementById('filter-organizacao').value,
                evento_id: document.getElementById('filter-evento').value,
                status: document.getElementById('filter-status').value,
                periodo: document.getElementById('filter-periodo').value
            };
        },
        
        confirmAction: function(title, message, inscricaoId, newStatus) {
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-message').textContent = message;
            if (newStatus === 'rejeitada') {
                document.getElementById('modal-input-container').style.display = 'block';
            } else {
                document.getElementById('modal-input-container').style.display = 'none';
            }
            
            // Armazenar dados para uso posterior
            document.getElementById('modal-confirm').dataset.inscricaoId = inscricaoId;
            document.getElementById('modal-confirm').dataset.newStatus = newStatus;
            
            document.getElementById('confirmation-modal').style.display = 'flex';
        },
        
        showRejectModal: function(inscricaoId) {
            this.confirmAction('Rejeitar Inscrição', 'Tem certeza que deseja rejeitar esta inscrição?', inscricaoId, 'rejeitada');
        },
        
        handleModalConfirm: function() {
            const inscricaoId = document.getElementById('modal-confirm').dataset.inscricaoId;
            const newStatus = document.getElementById('modal-confirm').dataset.newStatus;
            const reason = document.getElementById('modal-input').value;
            
            this.updateInscricaoStatus(inscricaoId, newStatus, reason);
            this.closeModal();
        },
        
        closeModal: function() {
            document.getElementById('confirmation-modal').style.display = 'none';
            document.getElementById('modal-input').value = '';
        },
        
        updateInscricaoStatus: function(inscricaoId, newStatus, reason) {
            fetch(sevoDashboardInscricoes.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sevo_dashboard_update_inscricao',
                    nonce: sevoDashboardInscricoes.nonce,
                    inscricao_id: inscricaoId,
                    new_status: newStatus,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar a interface
                    this.showToast('success', data.data || 'Status atualizado com sucesso!');
                    this.loadStats();
                    // Recarregar a lista de inscrições
                    location.reload();
                } else {
                    this.showToast('error', data.data || 'Erro ao atualizar status.');
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar status:', error);
                this.showToast('error', 'Erro ao atualizar status.');
            });
        },
        
        viewEvent: function(eventoId) {
            // Implementar visualização do evento
            console.log('Visualizar evento:', eventoId);
        },
        
        editInscricao: function(inscricaoId) {
            // Implementar edição da inscrição
            console.log('Editar inscrição:', inscricaoId);
        },
        
        showToast: function(type, message) {
            // Implementar sistema de notificações
            alert(message); // Placeholder simples
        }
    };
    
    // Inicializar o dashboard
    SevoDashboard.init();
});
</script>