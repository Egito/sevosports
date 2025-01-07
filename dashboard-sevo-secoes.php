<?php
/**
 * Template for Sevo Secoes Dashboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue necessary styles and scripts
wp_enqueue_style('dashboard-sevo-secoes-style', plugin_dir_url(__FILE__) . 'assets/css/dashboard-sevo-secoes.css', array(), '1.0.0');
wp_enqueue_script('dashboard-sevo-secoes-script', plugin_dir_url(__FILE__) . 'assets/js/dashboard-sevo-secoes.js', array('jquery'), '1.0.0', true);

// Localize script
wp_localize_script('dashboard-sevo-secoes-script', 'sevoSecoesDashboard', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('sevo_secoes_nonce')
));

// Include summary cards template
require_once plugin_dir_path(__FILE__) . 'templates/summary-cards.php';

// Handler AJAX para carregar seções
function sevo_load_more_secoes() {
    check_ajax_referer('sevo_secoes_nonce', 'nonce');
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    $args = array(
        'post_type' => 'sevo-secoes',
        'posts_per_page' => 12,
        'paged' => $page,
        'orderby' => 'meta_value',
        'meta_key' => '_sevo_secao_data_inicio_evento',
        'order' => 'ASC'
    );
    
    $query = new WP_Query($args);
    
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $secao_id = get_the_ID();
            $evento_id = get_post_meta($secao_id, '_sevo_secao_evento_id', true);
            $evento_thumbnail = get_the_post_thumbnail_url($evento_id, 'thumbnail');
            $secao_thumbnail = get_the_post_thumbnail_url($secao_id, 'medium');
            $data_insc_inicio = get_post_meta($secao_id, '_sevo_secao_data_inicio_inscricoes', true);
            $data_insc_fim = get_post_meta($secao_id, '_sevo_secao_data_fim_inscricoes', true);
            $data_secao_inicio = get_post_meta($secao_id, '_sevo_secao_data_inicio_evento', true);
            $data_secao_fim = get_post_meta($secao_id, '_sevo_secao_data_fim_evento', true);
            $vagas = get_post_meta($secao_id, '_sevo_secao_max_vagas', true);
            $inscritos = get_post_meta($secao_id, '_sevo_secao_inscritos', true);
            $inscritos_count = is_array($inscritos) ? count($inscritos) : 0;
            ?>
            <div class="secao-card" 
                 data-inicio-inscricao="<?php echo esc_attr($data_insc_inicio); ?>"
                 data-fim-inscricao="<?php echo esc_attr($data_insc_fim); ?>"
                 data-inicio-secao="<?php echo esc_attr($data_secao_inicio); ?>"
                 data-fim-secao="<?php echo esc_attr($data_secao_fim); ?>"
                 data-vagas="<?php echo esc_attr($vagas); ?>"
                 data-inscricoes="<?php echo esc_attr($inscritos_count); ?>">
                <div class="secao-images">
                    <?php if ($secao_thumbnail): ?>
                        <div class="secao-main-image" style="background-image: url('<?php echo esc_url($secao_thumbnail); ?>');"></div>
                    <?php endif; ?>
                    <?php if ($evento_thumbnail): ?>
                        <div class="evento-thumbnail">
                            <img src="<?php echo esc_url($evento_thumbnail); ?>" alt="Evento relacionado">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="secao-content">
                    <div class="secao-header">
                        <h3 class="secao-title"><?php the_title(); ?></h3>
                        <?php if ($evento_id): ?>
                            <div class="secao-evento">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo get_the_title($evento_id); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="secao-dates">
                        <div class="date-row inscricao">
                            <span class="date-label">Inscrições:</span>
                            <span class="date-range"></span>
                        </div>
                        <div class="date-row secao">
                            <span class="date-label">Seção:</span>
                            <span class="date-range"></span>
                        </div>
                    </div>
                    
                    <div class="secao-vagas">
                        <div class="vagas-progress">
                            <div class="progress-bar"></div>
                        </div>
                        <div class="vagas-info">
                            <span class="vagas-numero"><?php echo esc_html($vagas); ?></span> vagas | 
                            <span class="inscritos-numero"><?php echo esc_html($inscritos_count); ?></span> inscritos
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $query->max_num_pages > $page
    ));
}
add_action('wp_ajax_load_more_secoes', 'sevo_load_more_secoes');
add_action('wp_ajax_nopriv_load_more_secoes', 'sevo_load_more_secoes');
?>

<div class="sevo-dashboard-container">
    <!-- Cards de Resumo -->
    <?php echo sevo_get_summary_cards(); ?>

    <!-- Container de Seções -->
    <div id="secoes-container"></div>
</div>
