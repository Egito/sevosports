<?php
/**
 * View para o Dashboard de Organizações.
 * Este template é incluído pelo shortcode [sevo-orgs-dashboard].
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica se o usuário tem permissão para ver organizações inativas
$can_manage_orgs = current_user_can('manage_options');

// Define meta_query baseado nas permissões
$meta_query = array();
if (!$can_manage_orgs) {
    // Usuários sem permissão só veem organizações ativas ou sem status definido
    $meta_query[] = array(
        'relation' => 'OR',
        array(
            'key' => 'sevo_org_status',
            'value' => 'ativo',
            'compare' => '='
        ),
        array(
            'key' => 'sevo_org_status',
            'compare' => 'NOT EXISTS'
        )
    );
}

// Busca organizações baseado nas permissões
$args = array(
    'post_type' => SEVO_ORG_POST_TYPE,
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => $meta_query,
);
$organizacoes = new WP_Query($args);
?>

<div class="sevo-dashboard-wrapper">
    <div class="sevo-orgs-dashboard-container">
    <div class="sevo-dashboard-header">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Nossas Organizações</h2>
        <?php if (current_user_can('manage_options')): ?>
            <button id="sevo-create-org-button" class="sevo-add-button" data-tooltip="Criar Nova Organização">
                <i class="dashicons dashicons-plus-alt"></i>
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Summary Cards -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>
    
    <?php if ($organizacoes->have_posts()) : ?>
        <div class="sevo-grid">
            <?php while ($organizacoes->have_posts()) : $organizacoes->the_post(); 
                $org_status = get_post_meta(get_the_ID(), 'sevo_org_status', true);
                $org_status = !empty($org_status) ? $org_status : 'ativo';
            ?>
                <div class="sevo-card org-card" data-org-id="<?php echo get_the_ID(); ?>">
                    <div class="sevo-card-image" style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium_large'); ?>');">
                        <div class="sevo-card-overlay"></div>
                        <div class="sevo-card-status">
                            <span class="sevo-status-badge status-<?php echo esc_attr($org_status); ?>">
                                <?php echo ucfirst($org_status); ?>
                            </span>
                        </div>
                    </div>
                    <div class="sevo-card-content">
                        <h3 class="sevo-card-title"><?php the_title(); ?></h3>
                        <p class="sevo-card-description">
                           <?php
                           $excerpt = get_the_excerpt();
                           echo wp_trim_words($excerpt, 15, '...');
                           ?>
                        </p>
                        
                        <div class="card-actions">
                            <button class="btn-view-org" onclick="SevoOrgsDashboard.viewOrg(<?php echo esc_attr(get_the_ID()); ?>)">
                                <i class="dashicons dashicons-visibility"></i>
                                Ver Detalhes
                            </button>
                            <?php if (current_user_can('manage_options')): ?>
                                <button class="btn-edit-org" onclick="SevoOrgsDashboard.editOrg(<?php echo esc_attr(get_the_ID()); ?>)">
                                    <i class="dashicons dashicons-edit"></i>
                                    Alterar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p>Nenhuma organização encontrada.</p>
    <?php endif; ?>

    <!-- Estrutura do Modal (inicialmente oculta) -->
    <div id="sevo-org-modal" class="sevo-modal-backdrop hidden">
        <div class="sevo-modal-container">
            <button id="sevo-modal-close" class="sevo-modal-close-button">&times;</button>
            <div id="sevo-modal-content">
                <!-- O conteúdo da organização será carregado aqui via AJAX -->
                <div class="sevo-spinner"></div>
            </div>
        </div>
    </div>
</div>
</div>
