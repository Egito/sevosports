<?php
/**
 * Shortcode handler for [sevo-secoes]
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Secoes_Shortcode {
    public function __construct() {
        add_shortcode('sevo-secoes', array($this, 'render_shortcode'));
        add_action('wp_ajax_load_more_secoes', array($this, 'load_more_secoes'));
        add_action('wp_ajax_nopriv_load_more_secoes', array($this, 'load_more_secoes'));
    }

    public function render_shortcode($atts) {
        // Enqueue required assets
        wp_enqueue_style('dashboard-sevo-secoes-style');
        wp_enqueue_script('dashboard-sevo-secoes-script');
        wp_enqueue_style('dashicons');
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . '/dashboard-sevo-secoes.php');
        return ob_get_clean();
    }

    private function get_filtered_query_args($page = 1) {
        $args = array(
            'post_type' => 'sevo-secoes',
            'posts_per_page' => 12,
            'paged' => $page,
            'post_status' => 'publish'
        );

        // Tax Query
        $tax_query = array();

        // Taxonomy Filter
        if (!empty($_POST['taxonomy'])) {
            $tax_query[] = array(
                'taxonomy' => 'secao_taxonomy',
                'field' => 'term_id',
                'terms' => intval($_POST['taxonomy'])
            );
        }

        // Participation Type Filter
        if (!empty($_POST['participation_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'tipo_participacao',
                'field' => 'term_id',
                'terms' => intval($_POST['participation_type'])
            );
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        // Meta Query
        $meta_query = array();

        // Related Event Filter
        if (!empty($_POST['related_event'])) {
            $meta_query[] = array(
                'key' => 'evento_relacionado',
                'value' => intval($_POST['related_event']),
                'compare' => '='
            );
        }

        // Registration Year Filter
        if (!empty($_POST['registration_year'])) {
            $meta_query[] = array(
                'key' => 'data_inscricao_inicial',
                'value' => array(
                    $_POST['registration_year'] . '-01-01',
                    $_POST['registration_year'] . '-12-31'
                ),
                'type' => 'DATE',
                'compare' => 'BETWEEN'
            );
        }

        // Section Year Filter
        if (!empty($_POST['section_year'])) {
            $meta_query[] = array(
                'key' => 'data_inicio',
                'value' => array(
                    $_POST['section_year'] . '-01-01',
                    $_POST['section_year'] . '-12-31'
                ),
                'type' => 'DATE',
                'compare' => 'BETWEEN'
            );
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    public function load_more_secoes() {
        check_ajax_referer('sevo_secoes_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = $this->get_filtered_query_args($page);
        
        $query = new WP_Query($args);
        $response = array(
            'items' => '',
            'hasMore' => $page < $query->max_num_pages
        );
        
        if ($query->have_posts()) {
            ob_start();
            while ($query->have_posts()) {
                $query->the_post();
                echo $this->get_secao_card(get_the_ID());
            }
            $response['items'] = ob_get_clean();
        }
        
        wp_reset_postdata();
        wp_send_json_success($response);
    }

    private function get_secao_card($post_id) {
        $title = get_the_title($post_id);
        $taxonomies = get_the_terms($post_id, 'secao_taxonomy');
        $participation_types = get_the_terms($post_id, 'tipo_participacao');
        $data_inscricao_inicial = get_post_meta($post_id, 'data_inscricao_inicial', true);
        $data_inscricao_final = get_post_meta($post_id, 'data_inscricao_final', true);
        $data_inicio = get_post_meta($post_id, 'data_inicio', true);
        $data_fim = get_post_meta($post_id, 'data_fim', true);
        $vagas = get_post_meta($post_id, 'vagas', true);
        $evento_relacionado = get_post_meta($post_id, 'evento_relacionado', true);
        $evento_title = $evento_relacionado ? get_the_title($evento_relacionado) : '';
        
        // Buscar número de inscritos
        $inscritos = $this->get_total_inscritos($post_id);
        $porcentagem = $vagas ? min(100, ($inscritos / $vagas) * 100) : 0;

        ob_start();
        // Get images
        $secao_image = get_the_post_thumbnail_url($post_id, 'large');
        $evento_image = $evento_relacionado ? get_the_post_thumbnail_url($evento_relacionado, 'thumbnail') : '';
        ?>
        <div class="secao-card" 
             data-inicio-inscricao="<?php echo esc_attr($data_inscricao_inicial); ?>"
             data-fim-inscricao="<?php echo esc_attr($data_inscricao_final); ?>"
             data-inicio-secao="<?php echo esc_attr($data_inicio); ?>"
             data-fim-secao="<?php echo esc_attr($data_fim); ?>"
             data-vagas="<?php echo esc_attr($vagas); ?>"
             data-inscricoes="<?php echo esc_attr($inscritos); ?>">
            
            <div class="secao-images">
                <?php if ($secao_image) : ?>
                    <div class="secao-main-image" style="background-image: url('<?php echo esc_url($secao_image); ?>')"></div>
                <?php endif; ?>
                
                <?php if ($evento_image) : ?>
                    <div class="evento-thumbnail">
                        <img src="<?php echo esc_url($evento_image); ?>" alt="<?php echo esc_attr($evento_title); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <div class="secao-content">
                <div class="secao-header">
                    <h3 class="secao-title"><?php echo esc_html($title); ?></h3>
                    <?php if ($evento_title) : ?>
                        <div class="secao-evento">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span><?php echo esc_html($evento_title); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="secao-dates">
                    <div class="date-row">
                        <span class="date-label">Inscrições:</span>
                        <span class="date-range inscricao"></span>
                    </div>
                    <div class="date-row">
                        <span class="date-label">Seção:</span>
                        <span class="date-range secao"></span>
                    </div>
                </div>

                <div class="secao-vagas">
                    <div class="vagas-progress">
                        <div class="progress-bar" style="width: <?php echo esc_attr($porcentagem); ?>%"></div>
                    </div>
                    <div class="vagas-info">
                        <span class="vagas-numero"><?php echo esc_html($inscritos); ?>/<?php echo esc_html($vagas ?: '∞'); ?></span>
                        <span>vagas preenchidas</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_total_inscritos($secao_id) {
        global $wpdb;
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}inscricoes WHERE secao_id = %d",
            $secao_id
        ));
        return (int) $total;
    }

    private function get_available_years($meta_key) {
        global $wpdb;
        $years = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT YEAR(meta_value)
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
            AND meta_value != ''
            ORDER BY meta_value DESC
        ", $meta_key));
        
        return array_map('intval', $years);
    }
}

// Initialize the shortcode
new Sevo_Secoes_Shortcode();
