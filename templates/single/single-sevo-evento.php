<?php
/**
 * Template para exibir um único Evento [sevo-evento slug="..."]
 * Este arquivo renderiza os detalhes de um evento específico e
 * prepara o terreno para futuras funcionalidades, como a lista de inscritos.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra o shortcode [sevo-evento].
 *
 * @param array $atts Atributos do shortcode.
 * @return string HTML do evento.
 */
function sevo_render_single_evento_shortcode($atts) {
    $atts = shortcode_atts(array(
        'slug' => '',
    ), $atts, 'sevo-evento');

    $slug = !empty($atts['slug']) ? $atts['slug'] : get_query_var('sevo-evento');

    if (empty($slug)) {
        return '<p>Evento não especificado.</p>';
    }

    $evento = get_page_by_path($slug, OBJECT, 'sevo-evento');

    if (!$evento) {
        return '<p>Evento não encontrado.</p>';
    }

    // Passa os dados do evento para o template de visualização
    ob_start();
    include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/single-evento-view.php');
    return ob_get_clean();
}
add_shortcode('sevo-evento', 'sevo_render_single_evento_shortcode');

/**
 * Se o template for chamado diretamente (ex: pelo /evento/slug/),
 * ele renderiza o conteúdo usando o shortcode.
 * Isso garante que o mesmo código seja usado em ambos os casos.
 */
if (!is_admin() && in_the_loop() && is_main_query() && is_singular('sevo-evento')) {
    get_header();
    echo '<main class="sevo-main-container">';
    echo do_shortcode('[sevo-evento slug="' . get_post()->post_name . '"]');
    echo '</main>';
    get_footer();
}
