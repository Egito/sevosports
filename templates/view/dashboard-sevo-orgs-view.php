<?php
/**
 * Dashboard de Organizações
 */

// Verifica se o usuário tem permissão
if (!current_user_can('edit_posts')) {
    return;
}

// Inclui o template de cards de resumo
include plugin_dir_path(__FILE__) . '../templates/summary-cards.php';

// Filtro de proprietários
$proprietarios = get_terms(array(
    'taxonomy' => 'sevo_org_proprietario',
    'hide_empty' => false,
));

// Query das organizações
$args = array(
    'post_type' => 'sevo_org',
    'posts_per_page' => -1,
);

if (isset($_GET['proprietario']) && !empty($_GET['proprietario'])) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'sevo_org_proprietario',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['proprietario']),
        ),
    );
}

$organizacoes = new WP_Query($args);
?>

<div class="sevo-dashboard-container">
    <!-- Filtros -->
    <div class="sevo-filters">
        <form method="get" action="">
            <select name="proprietario" id="proprietario-filter">
                <option value="">Todos os Proprietários</option>
                <?php foreach ($proprietarios as $proprietario): ?>
                    <option value="<?php echo esc_attr($proprietario->slug); ?>"
                        <?php selected($_GET['proprietario'] ?? '', $proprietario->slug); ?>>
                        <?php echo esc_html($proprietario->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="sevo-filter-button">Filtrar</button>
        </form>
    </div>

    <!-- Lista de Organizações -->
    <div class="sevo-org-grid">
        <?php if ($organizacoes->have_posts()): ?>
            <?php while ($organizacoes->have_posts()): $organizacoes->the_post(); ?>
                <div class="sevo-org-card">
                    <!-- Conteúdo do card -->
                    <div class="sevo-org-content">
                        <h3><?php the_title(); ?></h3>
                        <div class="sevo-org-meta">
                            <?php
                            $proprietario_terms = get_the_terms(get_the_ID(), 'sevo_org_proprietario');
                            if ($proprietario_terms && !is_wp_error($proprietario_terms)) {
                                echo '<span class="sevo-org-proprietario">' . esc_html($proprietario_terms[0]->name) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="sevo-no-results">Nenhuma organização encontrada.</p>
        <?php endif; ?>
    </div>
</div>

<?php
wp_reset_postdata();