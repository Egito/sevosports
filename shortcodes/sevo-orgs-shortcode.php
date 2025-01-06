<?php
// Não permitir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Função para exibir a listagem de organizações
function sevo_orgs_shortcode_callback($atts) {
    // Parâmetros padrão para o shortcode
    $atts = shortcode_atts(array(
        'owner' => null, // ID do proprietário para filtrar
    ), $atts);

    $owner = $atts['owner'];

    // Argumentos da query para buscar as organizações
    $args = array(
        'post_type'      => 'sevo_orgs',
        'posts_per_page' => -1, // Exibir todas as organizações
        'meta_query'     => array(
            'relation' => 'AND',
        ),
    );

    // Adiciona o filtro por proprietário se estiver definido
    if (!empty($owner)) {
        $args['meta_query'][] = array(
            'key'     => '_sevo_orgs_owner',
            'value'   => $owner,
            'compare' => '=',
        );
    }

    // Consulta as organizações
    $query = new WP_Query($args);

    // Verifica se há resultados
    if ($query->have_posts()) {
        // Inicia o buffer de saída
        ob_start();

        // Inclui o template para exibir as organizações
        include plugin_dir_path(__FILE__) . '../templates/sevo-orgs-template.php';

        // Retorna o conteúdo do buffer
        return ob_get_clean();
    } else {
        return '<p>' . __('Nenhuma organização encontrada.', 'sevosports') . '</p>';
    }
}

// Registrar o shortcode
add_shortcode('sevo-orgs', 'sevo_orgs_shortcode_callback');
?>
