<?php
/**
 * Shortcode para exibição das organizações
 * 
 * @package Sevo_Eventos
 */

function sevo_org_shortcode($atts) {
    // Atributos padrão
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'sevo_org');

    // Verifica se o ID foi fornecido
    if (!$atts['id']) {
        return '';
    }

    // Verifica se o usuário atual é o proprietário
    $current_user_id = get_current_user_id();
    $owner_id = get_post_meta($atts['id'], 'sevo_org_owner', true);

    if ($owner_id && $current_user_id != $owner_id) {
        return '';
    }

    // Inicia o buffer de saída
    ob_start();

    // Carrega o template
    include plugin_dir_path(__FILE__) . '../../templates/cpt/cpt-sevo-org-view.php';

    // Retorna o conteúdo do buffer
    return ob_get_clean();
}
add_shortcode('sevo-orgs', 'sevo_org_shortcode');