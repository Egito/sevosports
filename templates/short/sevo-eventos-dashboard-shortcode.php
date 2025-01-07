<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir template de cards
require_once plugin_dir_path(__FILE__) . '../view/summary-cards.php';
// Incluir template do dashboard
require_once plugin_dir_path(__FILE__) . '../view/dashboard-sevo-eventos-view.php';

function sevo_eventos_dashboard_shortcode($atts = []) {
    // Atributos padrão
    $atts = shortcode_atts([
        'posts_per_page' => 12,
        'show_filters' => true,
        'show_summary' => true
    ], $atts, 'sevo-eventos');

    // Validar atributos
    $config = [
        'posts_per_page' => absint($atts['posts_per_page']),
        'show_filters' => filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN),
        'show_summary' => filter_var($atts['show_summary'], FILTER_VALIDATE_BOOLEAN)
    ];

    // Carregar assets necessários
    wp_enqueue_style('dashboard-sevo-eventos-css', plugins_url('assets/css/dashboard-sevo-eventos.css', __FILE__));
    wp_enqueue_script('dashboard-sevo-eventos-js', plugins_url('assets/js/dashboard-sevo-eventos.js', __FILE__), array('jquery'), null, true);

    // Passar configurações para o JS
    wp_localize_script('dashboard-sevo-eventos-js', 'sevo_eventos_config', $config);

    // Gerar cache key baseado nos atributos
    $cache_key = 'sevo_eventos_dashboard_' . md5(serialize($config));
    $output = get_transient($cache_key);

    if (false === $output) {
        ob_start();
        sevo_render_dashboard($config);
        $output = ob_get_clean();
        set_transient($cache_key, $output, HOUR_IN_SECONDS);
    }

    return $output;
}
add_shortcode('sevo-eventos', 'sevo_eventos_dashboard_shortcode');
