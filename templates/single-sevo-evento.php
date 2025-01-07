<?php
/**
 * Template for displaying single evento as shortcode
 */

// Verificar se é uma requisição direta
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function sevo_evento_shortcode($atts) {
    // Extrair atributos do shortcode
    $atts = shortcode_atts(array(
        'slug' => ''
    ), $atts, 'sevo_evento');

    // Se não tiver slug, tentar pegar da URL
    if (empty($atts['slug'])) {
        $atts['slug'] = get_query_var('evento_slug');
    }

    // Buscar post pelo slug
    $evento = get_page_by_path($atts['slug'], OBJECT, 'sevo-evento');

    if (!$evento) {
        return '<p>Evento não encontrado</p>';
    }

    // Configurar query
    global $post;
    $post = $evento;
    setup_postdata($post);

    // Capturar output
    ob_start();
    ?>
    
    <main id="sevo-evento-content" class="sevo-single-evento">
        <?php
        // Exibir conteúdo do evento
        get_template_part('template-parts/content', 'sevo-evento');
        ?>
    </main>

    <?php
    // Resetar post data
    wp_reset_postdata();
    
    return ob_get_clean();
}
add_shortcode('sevo_evento', 'sevo_evento_shortcode');

// Se for acesso direto, exibir template normal
if (!isset($shortcode_mode)) {
    get_header();
    echo do_shortcode('[sevo_evento]');
    get_footer();
}
