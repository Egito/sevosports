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
                <label for="filter-ano">Ano:</label>
                <select id="filter-ano" name="ano" class="sevo-filter-select">
                    <option value="">Todos</option>
                </select>
            </div>
            
            <div class="sevo-filter-group">
                <label for="filter-mes">Mês:</label>
                <select id="filter-mes" name="mes" class="sevo-filter-select">
                    <option value="">Todos</option>
                    <option value="1">Jan</option>
                    <option value="2">Fev</option>
                    <option value="3">Mar</option>
                    <option value="4">Abr</option>
                    <option value="5">Mai</option>
                    <option value="6">Jun</option>
                    <option value="7">Jul</option>
                    <option value="8">Ago</option>
                    <option value="9">Set</option>
                    <option value="10">Out</option>
                    <option value="11">Nov</option>
                    <option value="12">Dez</option>
                </select>
            </div>
            
            <div class="sevo-filter-actions">
                <button type="button" class="sevo-btn sevo-btn-secondary" id="clear-filters" title="Limpar Filtros">🗑️</button>
            </div>
        </div>
    </div>

    <!-- Tabela de Inscrições -->
    <div class="sevo-table-container">
        <div class="sevo-table-loading" id="table-loading">
            <div class="sevo-spinner"></div>
            <p>Carregando inscrições...</p>
        </div>
        
        <table class="sevo-inscricoes-table" id="inscricoes-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="inscricao_id">ID</th>
                    <?php if ($can_manage_all): ?>
                        <th class="sortable" data-sort="usuario_nome">Usuário</th>
                    <?php endif; ?>
                    <th class="sortable" data-sort="evento_nome">Evento</th>
                    <th class="sortable" data-sort="evento_data">Data do Evento</th>
                    <th class="sortable" data-sort="organizacao_nome">Organização</th>
                    <th class="sortable" data-sort="data_inscricao">Data da Inscrição</th>
                    <th class="no-sort">Ações</th>
                </tr>
            </thead>
            <tbody id="inscricoes-tbody">
                <?php if (!empty($inscricoes)) : ?>
                    <?php foreach ($inscricoes as $inscricao) : ?>
                        <?php
                        $status_class = 'status-' . $inscricao->status;
                        $status_text = ucfirst($inscricao->status);
                        
                        // Formatação da data
                        $data_formatted = $inscricao->data_inscricao ? date('d/m/Y H:i', strtotime($inscricao->data_inscricao)) : '';
                        ?>
                        <tr data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" class="<?php echo esc_attr($status_class); ?>">
                            <td class="inscricao-id"><?php echo esc_html($inscricao->id); ?></td>
                            <?php if ($can_manage_all): ?>
                                <td class="usuario-nome"><?php echo esc_html($inscricao->user_name); ?></td>
                            <?php endif; ?>
                            <td class="evento-nome"><?php echo esc_html($inscricao->evento_titulo); ?></td>
                            <td class="evento-data"><?php echo esc_html($inscricao->data_evento ? date('d/m/Y', strtotime($inscricao->data_evento)) : ''); ?></td>
                            <td class="organizacao-nome"><?php echo esc_html($inscricao->organizacao_nome); ?></td>
                            <td class="data-inscricao"><?php echo esc_html($data_formatted); ?></td>
                            <td class="acoes">
                                <?php if ($can_manage_all): ?>
                                    <div class="action-buttons">
                                        <?php if ($inscricao->status === 'solicitada'): ?>
                                            <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-success approve-btn" 
                                                    data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Aprovar">
                                                ✓
                                            </button>
                                            <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger reject-btn" 
                                                    data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Reprovar">
                                                ✗
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                                                data-evento-id="<?php echo esc_attr($inscricao->evento_id); ?>" title="Ver Detalhes do Evento">
                                            👁
                                        </button>
                                        <?php if ($is_super_admin): ?>
                                            <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-warning edit-inscricao-btn" 
                                                    data-inscricao-id="<?php echo esc_attr($inscricao->id); ?>" title="Editar Inscrição">
                                                ✏️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                                            data-evento-id="<?php echo esc_attr($inscricao->evento_id); ?>" title="Ver Evento">
                                        Ver Evento
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $can_manage_all ? '7' : '6'; ?>" class="no-data">
                            Nenhuma inscrição encontrada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="sevo-no-results" id="no-results" style="display: none;">
            <p>Nenhuma inscrição encontrada com os filtros aplicados.</p>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
        <div class="sevo-pagination">
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo; Anterior',
                'next_text' => 'Próxima &raquo;',
                'total' => $total_pages,
                'current' => $paged,
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'type' => 'plain',
                'add_args' => array(
                    'search' => $search,
                    'status_filter' => $status_filter,
                    'evento_filter' => $evento_filter,
                    'org_filter' => $org_filter
                )
            );
            echo paginate_links($pagination_args);
            ?>
        </div>
    <?php endif; ?>
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