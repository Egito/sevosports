<?php
/**
 * View para o dashboard de Organizações.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH') || !is_user_logged_in()) {
    exit;
}

// Parâmetros de paginação
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;

// Usar o modelo para buscar organizações com estatísticas
require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model.php';
$org_model = new Sevo_Organizacao_Model();

// Preparar filtros
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filters = [];

// Buscar organizações com estatísticas
$result = $org_model->get_with_full_stats($current_page, $per_page, $search, $filters);
$organizacoes = $result['data'];
$total_items = $result['total'];
$total_pages = $result['total_pages'];

// Check user permissions using the new centralized system
$current_user_id = get_current_user_id();
$can_view_orgs = sevo_check_record_permission('view_org', 0, 'organizacao', $current_user_id);
$can_edit_orgs = sevo_check_record_permission('edit_org', 0, 'organizacao', $current_user_id);
$can_create_orgs = sevo_check_record_permission('create_org', 0, 'organizacao', $current_user_id);
?>

<div class="sevo-dashboard-wrapper">
    <div class="sevo-dashboard-header">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Nossas Organizações</h2>
    </div>
    
    <!-- Summary Cards -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>
    
    <?php if (!empty($organizacoes)) : ?>
        <div class="sevo-grid">
            <?php foreach ($organizacoes as $organizacao) : 
                // As estatísticas já vêm do modelo
                $tipos_count = $organizacao->tipos_count;
                $eventos_count = $organizacao->eventos_count;
                
                $status_class = ($organizacao->status === 'ativo') ? 'status-ativo' : 'status-inativo';
                $status_text = ($organizacao->status === 'ativo') ? 'Ativo' : 'Inativo';
                
                // Check permissions for this specific organization
                $can_view_this_org = sevo_check_record_permission('view_org', $organizacao->id, 'organizacao', $current_user_id);
                $can_edit_this_org = sevo_check_record_permission('edit_org', $organizacao->id, 'organizacao', $current_user_id);
            ?>
                <div class="sevo-card org-card" data-org-id="<?php echo esc_attr($organizacao->id); ?>">
                    <div class="sevo-card-image" style="background-image: url('<?php echo esc_url($organizacao->imagem_url ?: ''); ?>');">
                        <div class="sevo-card-overlay"></div>
                        <div class="sevo-card-status">
                            <span class="sevo-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                    </div>
                    <div class="sevo-card-content">
                        <h3 class="sevo-card-title"><?php echo esc_html($organizacao->titulo); ?></h3>
                        <p class="sevo-card-description">
                           <?php echo esc_html(wp_trim_words($organizacao->descricao, 15, '...')); ?>
                        </p>
                        
                        <div class="card-actions">
                            <?php if ($can_view_this_org): ?>
                                <button class="btn-view-org" onclick="SevoOrgsDashboard.viewOrg(<?php echo esc_attr($organizacao->id); ?>)" title="Ver Detalhes">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($can_edit_this_org): ?>
                                <button class="btn-edit-org" onclick="SevoOrgsDashboard.editOrg(<?php echo esc_attr($organizacao->id); ?>)" title="Editar">
                                    <i class="dashicons dashicons-edit"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p>Nenhuma organização encontrada.</p>
    <?php endif; ?>

    <!-- Estrutura do Modal (inicialmente oculta) -->
    <div id="sevo-org-modal" class="sevo-modal hidden">
        <div class="sevo-modal-overlay"></div>
        <div class="sevo-modal-container">
            <button id="sevo-modal-close" class="sevo-modal-close">&times;</button>
            <div id="sevo-modal-content">
                <!-- O conteúdo da organização será carregado aqui via AJAX -->
                <div class="sevo-spinner"></div>
            </div>
        </div>
    </div>
    
    <!-- Botão Flutuante de Adicionar -->
    <?php if ($can_create_orgs): ?>
        <button id="sevo-create-org-button" class="sevo-floating-add-button sevo-orgs sevo-animate-in" data-tooltip="Criar Nova Organização">
            <i class="dashicons dashicons-plus-alt"></i>
        </button>
    <?php endif; ?>
</div>