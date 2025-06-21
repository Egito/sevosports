<?php
/**
 * Shortcode para exibir o dashboard de organizações
 */

function sevo_orgs_dashboard_shortcode() {
    // Enfileira os scripts e estilos
    wp_enqueue_style(
        'dashboard-sevo-orgs-css',
        plugins_url('assets/css/dashboard-sevo-orgs.css', dirname(__FILE__))
    );

    wp_enqueue_script(
        'dashboard-sevo-orgs-js',
        plugins_url('assets/js/dashboard-sevo-orgs.js', dirname(__FILE__)),
        array('jquery'),
        null,
        true
    );

    // Inicia o buffer de saída
    ob_start();

    // Inclui o template de visualização
    include plugin_dir_path(__FILE__) . '../templates/view/dashboard-sevo-orgs-view.php';

    // Retorna o conteúdo do buffer
    return ob_get_clean();
}
add_shortcode('sevo-orgs', 'sevo_orgs_dashboard_shortcode');