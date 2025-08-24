<?php
/**
 * Template para o dashboard de Organizações com controle de permissões organizacionais
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se usuário está logado
if (!is_user_logged_in()) {
    echo '<div class="sevo-error">Você precisa estar logado para acessar esta página.</div>';
    return;
}

// Carregar o modelo com permissões
if (!class_exists('Sevo_Organizacao_Model_With_Permissions')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'includes/models/Organizacao_Model_With_Permissions.php';
}

$user_id = get_current_user_id();
$is_admin = user_can($user_id, 'manage_options');

// Carregar o modelo com permissões
$organizacao_model = new Sevo_Organizacao_Model_With_Permissions();

// Obter organizações com estatísticas para o usuário
$org_data = $organizacao_model->get_with_full_stats_for_user(1, 100, '', [], $user_id);
$organizacoes = $org_data['data'];

// Obter todas as organizações ativas (para administradores)
$todas_organizacoes = [];
if ($is_admin) {
    $todas_organizacoes = $organizacao_model->get_active();
}
?>

<div class="sevo-dashboard-container">
    <div class="sevo-dashboard-header">
        <h1>Organizações</h1>
        <p class="sevo-description">Gerencie as organizações do sistema</p>
    </div>

    <?php echo sevo_get_summary_cards($organizacoes); ?>

    <div class="sevo-dashboard-controls">
        <?php if ($is_admin): ?>
            <button type="button" class="sevo-btn sevo-btn-primary" id="sevo-add-org-btn">
                <span class="dashicons dashicons-plus"></span> Adicionar Organização
            </button>
        <?php endif; ?>
    </div>

    <div class="sevo-dashboard-content">
        <?php if (empty($organizacoes)): ?>
            <div class="sevo-no-data">
                <p>Nenhuma organização encontrada.</p>
                <?php if ($is_admin): ?>
                    <button type="button" class="sevo-btn sevo-btn-primary" id="sevo-add-first-org-btn">
                        <span class="dashicons dashicons-plus"></span> Adicionar Primeira Organização
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sevo-org-grid">
                <?php foreach ($organizacoes as $organizacao): ?>
                    <div class="sevo-org-card" data-org-id="<?php echo esc_attr($organizacao->id); ?>">
                        <div class="sevo-org-card-header">
                            <?php if (!empty($organizacao->imagem_url)): ?>
                                <img src="<?php echo esc_url($organizacao->imagem_url); ?>" 
                                     alt="<?php echo esc_attr($organizacao->titulo); ?>" 
                                     class="sevo-org-image">
                            <?php else: ?>
                                <div class="sevo-org-placeholder">
                                    <span class="dashicons dashicons-building"></span>
                                </div>
                            <?php endif; ?>
                            <h3><?php echo esc_html($organizacao->titulo); ?></h3>
                        </div>
                        
                        <div class="sevo-org-card-body">
                            <p class="sevo-org-description">
                                <?php echo esc_html(wp_trim_words($organizacao->descricao, 20)); ?>
                            </p>
                            
                            <div class="sevo-org-stats">
                                <div class="sevo-stat-item">
                                    <span class="sevo-stat-value"><?php echo esc_html($organizacao->tipos_count); ?></span>
                                    <span class="sevo-stat-label">Tipos</span>
                                </div>
                                <div class="sevo-stat-item">
                                    <span class="sevo-stat-value"><?php echo esc_html($organizacao->eventos_count); ?></span>
                                    <span class="sevo-stat-label">Eventos</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sevo-org-card-footer">
                            <button type="button" class="sevo-btn sevo-btn-secondary sevo-view-org" 
                                    data-org-id="<?php echo esc_attr($organizacao->id); ?>">
                                <span class="dashicons dashicons-visibility"></span> Visualizar
                            </button>
                            
                            <?php if (sevo_user_can_manage_organization($user_id, $organizacao->id)): ?>
                                <button type="button" class="sevo-btn sevo-btn-primary sevo-edit-org" 
                                        data-org-id="<?php echo esc_attr($organizacao->id); ?>">
                                    <span class="dashicons dashicons-edit"></span> Editar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para visualização/edição de organização -->
<div id="sevo-org-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-overlay"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <h2 id="sevo-modal-title">Detalhes da Organização</h2>
            <button type="button" class="sevo-modal-close">&times;</button>
        </div>
        <div class="sevo-modal-content">
            <!-- Conteúdo do modal será carregado via AJAX -->
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="sevo-loading-overlay" style="display: none;">
    <div class="sevo-spinner"></div>
</div>