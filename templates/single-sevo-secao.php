<?php
/**
 * Template for displaying single seção as shortcode
 */

// Verificar se é uma requisição direta
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function sevo_secao_shortcode($atts) {
    // Extrair atributos do shortcode
    $atts = shortcode_atts(array(
        'slug' => ''
    ), $atts, 'sevo_secao');

    // Se não tiver slug, tentar pegar da URL
    if (empty($atts['slug'])) {
        $atts['slug'] = get_query_var('secao_slug');
    }

    // Buscar post pelo slug
    $secao = get_page_by_path($atts['slug'], OBJECT, 'sevo-secao');

    if (!$secao) {
        return '<p>Seção não encontrada</p>';
    }

    // Configurar query
    global $post;
    $post = $secao;
    setup_postdata($post);

    // Capturar output
    ob_start();
    ?>
    
    <main id="sevo-secao-content" class="sevo-single-secao">
        <?php
        // Exibir conteúdo da seção
        get_template_part('template-parts/content', 'sevo-secao');
        ?>
    </main>

    <?php
    // Resetar post data
    wp_reset_postdata();
    
    return ob_get_clean();
}
add_shortcode('sevo_secao', 'sevo_secao_shortcode');

// Se for acesso direto, exibir template normal
if (!isset($shortcode_mode)) {
    get_header();
    echo do_shortcode('[sevo_secao]');
    get_footer();
}
