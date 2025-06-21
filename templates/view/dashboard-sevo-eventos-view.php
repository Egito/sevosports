<?php
/**
 * View para o Dashboard de Eventos
 * Carrega os filtros e o container onde os eventos serão exibidos via AJAX.
 * Inclui a estrutura base para o modal de detalhes do evento.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carrega a função dos cards de resumo, se ainda não tiver sido carregada.
if (!function_exists('sevo_get_summary_cards')) {
    // Assumindo que o arquivo está em /templates/
    require_once SEVO_EVENTOS_PLUGIN_DIR . 'templates/summary-cards.php';
}

// Busca os termos para os filtros
$categorias_evento = get_terms(array('taxonomy' => 'sevo_evento_categoria', 'hide_empty' => false));
$tipos_evento = get_posts(array('post_type' => 'sevo-tipo-evento', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));

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

<div class="sevo-eventos-dashboard-container">
    
    <div class="sevo-dashboard-header">
        <h2 class="text-3xl font-bold text-gray-800">Eventos</h2>
        <?php if (is_user_logged_in()): ?>
            <button id="sevo-create-event-button" class="sevo-button-primary">
                <i class="fas fa-plus mr-2"></i>Criar Novo Evento
            </button>
        <?php endif; ?>
    </div>

    <!-- 1. Cards de Resumo -->
    <?php echo function_exists('sevo_get_summary_cards') ? sevo_get_summary_cards() : ''; ?>

    <!-- 2. Filtros do Dashboard -->
    <div class="sevo-filters">
        <div class="sevo-filter-group">
            <label for="filtro-tipo-evento" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Evento</label>
            <select id="filtro-tipo-evento" class="sevo-filter">
                <option value="">Todos os Tipos</option>
                <?php foreach ($tipos_evento as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo->ID); ?>"><?php echo esc_html($tipo->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sevo-filter-group">
            <label for="filtro-categoria-evento" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select id="filtro-categoria-evento" class="sevo-filter">
                <option value="">Todas as Categorias</option>
                <?php foreach ($categorias_evento as $categoria) : ?>
                    <option value="<?php echo esc_attr($categoria->term_id); ?>"><?php echo esc_html($categoria->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sevo-filter-group">
            <label for="filtro-ano-evento" class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
            <select id="filtro-ano-evento" class="sevo-filter">
                <option value="">Todos os Anos</option>
                <?php foreach ($years as $year) : ?>
                    <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- 3. Container para os Cards de Eventos -->
    <div id="sevo-eventos-container" class="sevo-grid"></div>

    <!-- 4. Indicador de Carregamento -->
    <div id="sevo-loading-indicator" style="display: none; text-align: center; padding: 20px;"></div>

    <!-- 5. Estrutura do Modal para o Formulário do Evento -->
    <div id="sevo-evento-modal" class="sevo-modal-backdrop hidden">
        <div class="sevo-modal-container">
            <button id="sevo-evento-modal-close" class="sevo-modal-close-button">&times;</button>
            <div id="sevo-evento-modal-content">
                <!-- O formulário do evento será carregado aqui via AJAX -->
            </div>
        </div>
    </div>
</div>
