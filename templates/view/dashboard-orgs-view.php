<?php
/**
 * View para o Dashboard de Organizações.
 * Este template é incluído pelo shortcode [sevo-orgs-dashboard].
 */

if (!defined('ABSPATH')) {
    exit;
}

// Busca todas as organizações publicadas para exibir no grid.
$args = array(
    'post_type' => SEVO_ORG_POST_TYPE,
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
);
$organizacoes = new WP_Query($args);
?>

<div class="sevo-orgs-dashboard-container">
    <div class="sevo-dashboard-header">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Nossas Organizações</h2>
        <?php if (current_user_can('manage_options')): ?>
            <button id="sevo-create-org-button" class="sevo-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> Criar Nova Organização
            </button>
        <?php endif; ?>
    </div>
    
    <?php if ($organizacoes->have_posts()) : ?>
        <div class="sevo-grid">
            <?php while ($organizacoes->have_posts()) : $organizacoes->the_post(); ?>
                <div class="sevo-card org-card" data-org-id="<?php echo get_the_ID(); ?>">
                    <div class="sevo-card-image" style="background-image: url('<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium_large'); ?>');">
                        <div class="sevo-card-overlay"></div>
                    </div>
                    <div class="sevo-card-content">
                        <h3 class="sevo-card-title"><?php the_title(); ?></h3>
                        <p class="sevo-card-description">
                           <?php
                           $excerpt = get_the_excerpt();
                           echo wp_trim_words($excerpt, 15, '...');
                           ?>
                        </p>
                        <span class="sevo-card-link">Ver Detalhes <i class="fas fa-arrow-right ml-2"></i></span>
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
