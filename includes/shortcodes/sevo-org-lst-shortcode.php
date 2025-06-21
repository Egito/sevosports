<?php
/**
 * Shortcode para exibição das organizações
 */

function sevo_orgs_shortcode($atts) {
    // Atributos padrão
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'sevo-orgs');

    // Verifica se o ID foi fornecido
    if (!$atts['id']) {
        return '';
    }

    // Inicia o buffer de saída
    ob_start();

    // Carrega o template
    include plugin_dir_path(__FILE__) . '../views/dashboard-sevo-orgs-view.php';

    // Retorna o conteúdo do buffer
    return ob_get_clean();
}
add_shortcode('sevo-orgs', 'sevo_orgs_shortcode');