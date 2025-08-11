<?php
/**
 * Shortcode handler para a Landing Page de Eventos [sevo-landing-page]
 * Exibe uma página com summary cards no topo e três seções de eventos:
 * - Eventos com inscrições abertas
 * - Eventos em andamento
 * - Eventos encerrados
 * Cada seção possui um carrossel de 4 cards por vez.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Landing_Page_Shortcode {
    public function __construct() {
        add_shortcode('sevo-landing-page', array($this, 'render_landing_page'));
        
        // AJAX para carregar mais eventos no carrossel
        add_action('wp_ajax_sevo_load_carousel_eventos', array($this, 'ajax_load_carousel_eventos'));
        add_action('wp_ajax_nopriv_sevo_load_carousel_eventos', array($this, 'ajax_load_carousel_eventos'));
        
        // AJAX para carregar visualização do evento no modal
        add_action('wp_ajax_sevo_get_evento_view', array($this, 'ajax_get_evento_view'));
        add_action('wp_ajax_nopriv_sevo_get_evento_view', array($this, 'ajax_get_evento_view'));
    }

    /**
     * Renderiza o shortcode da landing page.
     */
    public function render_landing_page($atts) {
        // Enqueue dos estilos e scripts
        wp_enqueue_style('sevo-landing-page-style');
        wp_enqueue_script('sevo-landing-page-script');
        wp_enqueue_style('dashicons');
        
        wp_localize_script('sevo-landing-page-script', 'sevoLandingPage', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_landing_page_nonce'),
        ));

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/landing-page-view.php');
        return ob_get_clean();
    }

    /**
     * AJAX: Carrega eventos para o carrossel baseado no tipo de seção.
     */
    public function ajax_load_carousel_eventos() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');
        
        $section_type = isset($_POST['section_type']) ? sanitize_text_field($_POST['section_type']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 4; // 4 cards por vez no carrossel
        
        $args = $this->get_eventos_args_by_section($section_type, $page, $per_page);
        $query = new WP_Query($args);
        
        $items_html = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items_html .= $this->render_evento_carousel_card(get_the_ID());
            }
        }
        wp_reset_postdata();

        wp_send_json_success(array(
            'items' => $items_html,
            'hasMore' => $page < $query->max_num_pages,
            'currentPage' => $page,
            'totalPages' => $query->max_num_pages
        ));
    }

    /**
     * Retorna os argumentos da query baseado no tipo de seção.
     */
    private function get_eventos_args_by_section($section_type, $page = 1, $per_page = 4) {
        $today = date('Y-m-d');
        
        $base_args = array(
            'post_type' => 'sevo-eventos',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'meta_value',
            'meta_key' => '_sevo_evento_data_inicio_evento',
            'order' => 'ASC'
        );

        switch ($section_type) {
            case 'inscricoes_abertas':
                $base_args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key' => '_sevo_evento_data_inicio_inscricoes',
                        'value' => $today,
                        'compare' => '<=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_sevo_evento_data_fim_inscricoes',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    )
                );
                break;
                
            case 'em_andamento':
                $base_args['meta_query'] = array(
                    'relation' => 'AND',
                    array(
                        'key' => '_sevo_evento_data_inicio_evento',
                        'value' => $today,
                        'compare' => '<=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_sevo_evento_data_fim_evento',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    )
                );
                break;
                
            case 'encerrados':
                $base_args['meta_query'] = array(
                    array(
                        'key' => '_sevo_evento_data_fim_evento',
                        'value' => $today,
                        'compare' => '<',
                        'type' => 'DATE'
                    )
                );
                $base_args['order'] = 'DESC'; // Mais recentes primeiro
                break;
        }

        return $base_args;
    }

    /**
     * Renderiza um card de evento para o carrossel.
     */
    private function render_evento_carousel_card($post_id) {
        $evento = get_post($post_id);
        $data_inicio = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $data_fim = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);
        $local = get_post_meta($post_id, '_sevo_evento_local', true);
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        $tipo_evento = $tipo_evento_id ? get_the_title($tipo_evento_id) : '';
        
        // Busca a organização através do tipo de evento
        $org_id = $tipo_evento_id ? get_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', true) : '';
        $org_name = $org_id ? get_the_title($org_id) : '';
        $org_thumbnail = $org_id ? get_the_post_thumbnail_url($org_id, 'thumbnail') : '';
        
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: '';
        
        // Formata as datas
        $data_inicio_formatted = $data_inicio ? date_i18n('d/m/Y', strtotime($data_inicio)) : '';
        $data_fim_formatted = $data_fim ? date_i18n('d/m/Y', strtotime($data_fim)) : '';
        
        ob_start();
        ?>
        <div class="sevo-carousel-card" data-event-id="<?php echo esc_attr($post_id); ?>">
            <div class="sevo-carousel-card-image">
                <?php if ($thumbnail_url): ?>
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($evento->post_title); ?>" />
                <?php else: ?>
                    <div class="sevo-carousel-card-placeholder">
                        <i class="dashicons dashicons-calendar-alt"></i>
                    </div>
                <?php endif; ?>
                
                <?php if ($org_thumbnail): ?>
                    <div class="sevo-carousel-card-org-thumb">
                        <img src="<?php echo esc_url($org_thumbnail); ?>" alt="<?php echo esc_attr($org_name); ?>" />
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sevo-carousel-card-content">
                <h3 class="sevo-carousel-card-title"><?php echo esc_html($evento->post_title); ?></h3>
                
                <?php if ($tipo_evento): ?>
                    <p class="sevo-carousel-card-type"><?php echo esc_html($tipo_evento); ?></p>
                <?php endif; ?>
                
                <?php if ($org_name): ?>
                    <p class="sevo-carousel-card-org"><?php echo esc_html($org_name); ?></p>
                <?php endif; ?>
                
                <div class="sevo-carousel-card-dates">
                    <?php if ($data_inicio_formatted): ?>
                        <span class="sevo-carousel-card-date">
                            <i class="dashicons dashicons-calendar"></i>
                            <?php echo esc_html($data_inicio_formatted); ?>
                            <?php if ($data_fim_formatted && $data_fim_formatted !== $data_inicio_formatted): ?>
                                - <?php echo esc_html($data_fim_formatted); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($local): ?>
                    <div class="sevo-carousel-card-location">
                        <i class="dashicons dashicons-location"></i>
                        <span><?php echo esc_html($local); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtém a contagem de eventos por seção.
     */
    public function get_section_counts() {
        $today = date('Y-m-d');
        
        // Eventos com inscrições abertas
        $inscricoes_abertas = new WP_Query(array(
            'post_type' => 'sevo-eventos',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_sevo_evento_data_inicio_inscricoes',
                    'value' => $today,
                    'compare' => '<=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_sevo_evento_data_fim_inscricoes',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        ));
        
        // Eventos em andamento
        $em_andamento = new WP_Query(array(
            'post_type' => 'sevo-eventos',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_sevo_evento_data_inicio_evento',
                    'value' => $today,
                    'compare' => '<=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_sevo_evento_data_fim_evento',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        ));
        
        // Eventos encerrados
        $encerrados = new WP_Query(array(
            'post_type' => 'sevo-eventos',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_sevo_evento_data_fim_evento',
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'DATE'
                )
            )
        ));
        
        return array(
            'inscricoes_abertas' => $inscricoes_abertas->found_posts,
            'em_andamento' => $em_andamento->found_posts,
            'encerrados' => $encerrados->found_posts
        );
    }

    /**
     * AJAX: Carrega a visualização de um evento para o modal.
     */
    public function ajax_get_evento_view() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error('ID do evento não fornecido.');
        }
        
        $evento = get_post($event_id);
        
        if (!$evento || $evento->post_type !== 'sevo-eventos' || $evento->post_status !== 'publish') {
            wp_send_json_error('Evento não encontrado.');
        }
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}

// Inicializa o shortcode
new Sevo_Landing_Page_Shortcode();