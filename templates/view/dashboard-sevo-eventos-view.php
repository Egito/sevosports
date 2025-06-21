<?php
/**
 * View para o Dashboard de Eventos
 * Carrega os filtros e o container onde os eventos serão exibidos via AJAX.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carrega a função dos cards de resumo, se ainda não tiver sido carregada.
if (!function_exists('sevo_get_summary_cards')) {
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/summary-cards.php';
}

// Busca os termos para os filtros
$categorias_evento = get_terms(array('taxonomy' => 'sevo_evento_categoria', 'hide_empty' => false));
$tipos_evento = get_posts(array('post_type' => 'sevo-tipo-evento', 'posts_per_page' => -1));

// Busca os anos disponíveis para o filtro
global $wpdb;
$years = $wpdb->get_col("
    SELECT DISTINCT YEAR(meta_value)
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_sevo_evento_data_inicio_evento'
    AND meta_value IS NOT NULL
    AND meta_value != ''
    ORDER BY meta_value DESC
");

?>

<div class="sevo-dashboard-container">
    <?php echo sevo_get_summary_cards(); ?>

    <div class="sevo-filters">
        <div class="sevo-filter-group">
            <select id="filtro-tipo-evento" class="sevo-filter">
                <option value="">Todos os Tipos de Evento</option>
                <?php foreach ($tipos_evento as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo->ID); ?>"><?php echo esc_html($tipo->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sevo-filter-group">
            <select id="filtro-categoria-evento" class="sevo-filter">
                <option value="">Todas as Categorias</option>
                <?php foreach ($categorias_evento as $categoria) : ?>
                    <option value="<?php echo esc_attr($categoria->term_id); ?>"><?php echo esc_html($categoria->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sevo-filter-group">
            <select id="filtro-ano-evento" class="sevo-filter">
                <option value="">Todos os Anos</option>
                <?php foreach ($years as $year) : ?>
                    <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="sevo-eventos-container" class="sevo-grid">
        </div>

    <div id="sevo-loading-indicator" style="display: none; text-align: center; padding: 20px;">
        <p>Carregando mais eventos...</p>
    </div>
</div>