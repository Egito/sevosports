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
        
        add_action('wp_ajax_sevo_get_evento_form', array($this, 'ajax_get_evento_form'));
        add_action('wp_ajax_nopriv_sevo_get_evento_form', array($this, 'ajax_get_evento_form'));
        add_action('wp_ajax_sevo_save_evento', array($this, 'ajax_save_evento'));
        
        // AJAX para gerenciar inscrições
        add_action('wp_ajax_sevo_inscribe_evento', array($this, 'ajax_inscribe_evento'));
        add_action('wp_ajax_nopriv_sevo_inscribe_evento', array($this, 'ajax_inscribe_evento'));
        add_action('wp_ajax_sevo_cancel_inscricao', array($this, 'ajax_cancel_inscricao'));
        add_action('wp_ajax_nopriv_sevo_cancel_inscricao', array($this, 'ajax_cancel_inscricao'));
        
        // AJAX para filtros da landing page
        add_action('wp_ajax_sevo_get_filter_options', array($this, 'ajax_get_filter_options'));
        add_action('wp_ajax_nopriv_sevo_get_filter_options', array($this, 'ajax_get_filter_options'));
        add_action('wp_ajax_sevo_filter_eventos', array($this, 'ajax_filter_eventos'));
        add_action('wp_ajax_nopriv_sevo_filter_eventos', array($this, 'ajax_filter_eventos'));
    }

    /**
     * Renderiza o shortcode da landing page.
     */
    public function render_landing_page($atts) {
        // Enqueue dos estilos e scripts
        wp_enqueue_style('sevo-landing-page-style');
        wp_enqueue_style('sevo-modal-responsive');
        wp_enqueue_script('sevo-landing-page-script');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('sevo-toaster-style');
        wp_enqueue_script('sevo-toaster-script');
        
        // Localiza o script com dados necessários para AJAX
        wp_localize_script('sevo-landing-page-script', 'sevoLandingPage', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_landing_page_nonce'),
        ));
        
        // Adiciona script inline para garantir que o objeto esteja disponível
        wp_add_inline_script('sevo-landing-page-script', '
            // Garante que o objeto sevoLandingPage esteja disponível globalmente
            window.sevoLandingPageData = window.sevoLandingPage || {};
        ', 'after');

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
            'post_type' => SEVO_EVENTO_POST_TYPE,
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
                
            case 'planejados':
                $base_args['meta_query'] = array(
                    array(
                        'key' => '_sevo_evento_data_inicio_inscricoes',
                        'value' => $today,
                        'compare' => '>',
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

        // Filtrar eventos de tipos de eventos inativos para usuários sem permissão de atualização
        $user_can_update = current_user_can('manage_options') || current_user_can('edit_posts');
        if (!$user_can_update) {
            // Buscar IDs de tipos de eventos ativos
            $tipos_ativos = get_posts(array(
                'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_sevo_tipo_evento_status',
                        'value' => 'ativo',
                        'compare' => '='
                    )
                )
            ));
            
            if (!empty($tipos_ativos)) {
                // Adicionar filtro para mostrar apenas eventos de tipos ativos
                $tipo_evento_filter = array(
                    'key' => '_sevo_evento_tipo_evento_id',
                    'value' => $tipos_ativos,
                    'compare' => 'IN'
                );
                
                if (isset($base_args['meta_query'])) {
                    if (isset($base_args['meta_query']['relation'])) {
                        $base_args['meta_query'][] = $tipo_evento_filter;
                    } else {
                        $base_args['meta_query'] = array(
                            'relation' => 'AND',
                            $base_args['meta_query'],
                            $tipo_evento_filter
                        );
                    }
                } else {
                    $base_args['meta_query'] = array($tipo_evento_filter);
                }
            } else {
                // Se não há tipos de eventos ativos, não mostrar nenhum evento
                $base_args['post__in'] = array(0);
            }
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
        $data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
        $local = get_post_meta($post_id, '_sevo_evento_local', true);
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        $tipo_evento = $tipo_evento_id ? get_the_title($tipo_evento_id) : '';
        
        // Busca a organização através do tipo de evento
        $org_id = $tipo_evento_id ? get_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', true) : '';
        $org_name = $org_id ? get_the_title($org_id) : '';
        
        // Imagem do evento ou padrão
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
        if (!$thumbnail_url) {
            $thumbnail_url = SEVO_EVENTOS_PLUGIN_URL . 'assets/images/default-evento.svg';
        }
        
        // Formata as datas
        $data_inicio_formatted = $data_inicio ? date_i18n('d/m/Y', strtotime($data_inicio)) : '';
        $data_fim_formatted = $data_fim ? date_i18n('d/m/Y', strtotime($data_fim)) : '';
        
        // Status das inscrições
        $hoje = date('Y-m-d');
        $inscricoes_abertas = ($data_inicio_insc && $data_fim_insc && $hoje >= $data_inicio_insc && $hoje <= $data_fim_insc);
        
        // Excerpt do evento
        $excerpt = $evento->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(strip_tags($evento->post_content), 20, '...');
        }
        
        ob_start();
        ?>
        <div class="sevo-card evento-card" data-event-id="<?php echo esc_attr($post_id); ?>">
            <div class="sevo-card-image" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>')">
                <div class="sevo-card-overlay"></div>
                <div class="sevo-card-status">
                    <?php if ($inscricoes_abertas): ?>
                        <span class="sevo-status-badge status-ativo">Inscrições Abertas</span>
                    <?php else: ?>
                        <span class="sevo-status-badge status-inativo">Inscrições Fechadas</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sevo-card-content">
                <h3 class="sevo-card-title"><?php echo esc_html($evento->post_title); ?></h3>
                
                <?php if ($excerpt): ?>
                    <p class="sevo-card-description"><?php echo esc_html($excerpt); ?></p>
                <?php endif; ?>
                
                <div class="sevo-card-meta">
                    <?php if ($tipo_evento): ?>
                        <div class="sevo-meta-item">
                            <i class="dashicons dashicons-category"></i>
                            <span><?php echo esc_html($tipo_evento); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($org_name): ?>
                        <div class="sevo-meta-item">
                            <i class="dashicons dashicons-building"></i>
                            <span><?php echo esc_html($org_name); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($data_inicio_formatted): ?>
                        <div class="sevo-meta-item">
                            <i class="dashicons dashicons-calendar"></i>
                            <span>
                                <?php echo esc_html($data_inicio_formatted); ?>
                                <?php if ($data_fim_formatted && $data_fim_formatted !== $data_inicio_formatted): ?>
                                    - <?php echo esc_html($data_fim_formatted); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($local): ?>
                        <div class="sevo-meta-item">
                            <i class="dashicons dashicons-location"></i>
                            <span><?php echo esc_html($local); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="sevo-card-actions">
                    <button class="sevo-button-secondary sevo-view-evento" data-event-id="<?php echo esc_attr($post_id); ?>">
                        <i class="dashicons dashicons-visibility"></i>
                        Visualizar
                    </button>
                </div>
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
        
        // Filtrar eventos de tipos de eventos inativos para usuários sem permissão de atualização
        $user_can_update = current_user_can('manage_options') || current_user_can('edit_posts');
        $tipo_evento_filter = null;
        
        if (!$user_can_update) {
            // Buscar IDs de tipos de eventos ativos
            $tipos_ativos = get_posts(array(
                'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_sevo_tipo_evento_status',
                        'value' => 'ativo',
                        'compare' => '='
                    )
                )
            ));
            
            if (!empty($tipos_ativos)) {
                $tipo_evento_filter = array(
                    'key' => '_sevo_evento_tipo_evento_id',
                    'value' => $tipos_ativos,
                    'compare' => 'IN'
                );
            }
        }
        
        // Eventos com inscrições abertas
        $inscricoes_abertas_meta = array(
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
        if ($tipo_evento_filter) {
            $inscricoes_abertas_meta[] = $tipo_evento_filter;
        }
        
        $inscricoes_abertas = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $inscricoes_abertas_meta,
            'post__in' => (!$user_can_update && empty($tipos_ativos)) ? array(0) : null
        ));
        
        // Eventos planejados
        $planejados_meta = array(
            array(
                'key' => '_sevo_evento_data_inicio_inscricoes',
                'value' => $today,
                'compare' => '>',
                'type' => 'DATE'
            )
        );
        if ($tipo_evento_filter) {
            $planejados_meta = array(
                'relation' => 'AND',
                $planejados_meta[0],
                $tipo_evento_filter
            );
        }
        
        $planejados = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $planejados_meta,
            'post__in' => (!$user_can_update && empty($tipos_ativos)) ? array(0) : null
        ));
        
        // Eventos em andamento
        $em_andamento_meta = array(
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
        if ($tipo_evento_filter) {
            $em_andamento_meta[] = $tipo_evento_filter;
        }
        
        $em_andamento = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $em_andamento_meta,
            'post__in' => (!$user_can_update && empty($tipos_ativos)) ? array(0) : null
        ));
        
        // Eventos encerrados
        $encerrados_meta = array(
            array(
                'key' => '_sevo_evento_data_fim_evento',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE'
            )
        );
        if ($tipo_evento_filter) {
            $encerrados_meta = array(
                'relation' => 'AND',
                $encerrados_meta[0],
                $tipo_evento_filter
            );
        }
        
        $encerrados = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $encerrados_meta,
            'post__in' => (!$user_can_update && empty($tipos_ativos)) ? array(0) : null
        ));
        
        return array(
            'inscricoes_abertas' => $inscricoes_abertas->found_posts,
            'planejados' => $planejados->found_posts,
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
        
        if (!$evento || $evento->post_type !== SEVO_EVENTO_POST_TYPE || $evento->post_status !== 'publish') {
            wp_send_json_error('Evento não encontrado.');
        }
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-view.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Carrega o formulário de edição de um evento para o modal.
     */
    public function ajax_get_evento_form() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');
        
        // Verifica permissão usando a função centralizada
        if (!sevo_check_user_permission('edit_evento')) {
            wp_send_json_error('Você não tem permissão para editar eventos.');
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error('ID do evento não fornecido.');
            return;
        }
        
        $evento = get_post($event_id);
        
        if (!$evento || $evento->post_type !== SEVO_EVENTO_POST_TYPE) {
            wp_send_json_error('Evento não encontrado.');
            return;
        }
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-form.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Salva (cria ou atualiza) um evento.
     */
    public function ajax_save_evento() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');
        sevo_check_permission_or_die('edit_evento');

        $evento_id = isset($_POST['evento_id']) ? intval($_POST['evento_id']) : 0;
        
        if (empty($_POST['post_title']) || empty($_POST['_sevo_evento_tipo_evento_id'])) {
            wp_send_json_error('Título e Tipo de Evento são obrigatórios.');
        }

        $post_data = array(
            'post_title'   => sanitize_text_field($_POST['post_title']),
            'post_content' => wp_kses_post($_POST['post_content']),
            'post_type'    => SEVO_EVENTO_POST_TYPE,
            'post_status'  => 'publish',
        );

        if ($evento_id > 0) {
            $post_data['ID'] = $evento_id;
            wp_update_post($post_data);
        } else {
            $evento_id = wp_insert_post($post_data);
        }

        if (is_wp_error($evento_id)) {
            wp_send_json_error('Erro ao salvar o evento.');
        }

        // Processar upload de imagem se fornecida
        if (isset($_FILES['evento_image']) && $_FILES['evento_image']['error'] === UPLOAD_ERR_OK) {
            $attachment_id = $this->process_evento_image($_FILES['evento_image']);
            if ($attachment_id) {
                set_post_thumbnail($evento_id, $attachment_id);
            }
        }

        // Salva os metadados
        $meta_fields = array(
            '_sevo_evento_tipo_evento_id' => 'int',
            '_sevo_evento_data_inicio_inscricoes' => 'text',
            '_sevo_evento_data_fim_inscricoes' => 'text',
            '_sevo_evento_data_inicio_evento' => 'text',
            '_sevo_evento_data_fim_evento' => 'text',
            '_sevo_evento_vagas' => 'int',
            '_sevo_evento_local' => 'text',
            '_sevo_evento_regras' => 'text'
        );

        foreach ($meta_fields as $key => $type) {
            if (isset($_POST[$key])) {
                $value = ($type === 'int') ? intval($_POST[$key]) : sanitize_text_field($_POST[$key]);
                
                // Validação de vagas
                if($key === '_sevo_evento_vagas' && isset($_POST['_sevo_evento_tipo_evento_id'])) {
                    $tipo_evento_id = intval($_POST['_sevo_evento_tipo_evento_id']);
                    $max_vagas = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', true);
                    if ($max_vagas > 0 && $value > $max_vagas) {
                        $value = $max_vagas; // Limita ao máximo permitido
                    }
                }
                
                update_post_meta($evento_id, $key, $value);
            }
        }

        wp_send_json_success(array('message' => 'Evento salvo com sucesso!', 'evento_id' => $evento_id));
    }

    /**
     * Processa o upload de imagem para eventos.
     */
    private function process_evento_image($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $image_path = $movefile['file'];
            $image_url = $movefile['url'];

            // Criar attachment no WordPress
            $attachment = array(
                'guid'           => $image_url,
                'post_mime_type' => $file['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($file['name'])),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attachment_id = wp_insert_attachment($attachment, $image_path);
            if (!$attachment_id) {
                return false;
            }

            // Gerar metadados do attachment
            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $image_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);

            return $attachment_id;
        }

        return false;
    }

    /**
     * AJAX para inscrever usuário em evento
     */
    public function ajax_inscribe_evento() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sevo_landing_page_nonce')) {
            wp_send_json_error('Nonce inválido');
        }

        // Verifica se usuário está logado
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuário deve estar logado para se inscrever');
        }

        $event_id = intval($_POST['event_id']);
        $user_id = get_current_user_id();

        if (!$event_id) {
            wp_send_json_error('ID do evento inválido');
        }

        // Verifica se o evento existe
        $evento = get_post($event_id);
        if (!$evento || $evento->post_type !== SEVO_EVENTO_POST_TYPE) {
            wp_send_json_error('Evento não encontrado');
        }

        // Verifica se as inscrições estão abertas
        $data_inicio_insc = get_post_meta($event_id, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($event_id, '_sevo_evento_data_fim_inscricoes', true);
        $now = current_time('Y-m-d');

        if ($data_inicio_insc && $now < $data_inicio_insc) {
            wp_send_json_error('As inscrições ainda não foram abertas');
        }

        if ($data_fim_insc && $now > $data_fim_insc) {
            wp_send_json_error('As inscrições já foram encerradas');
        }

        // Verifica se o usuário já está inscrito
        $existing_inscricao = get_posts(array(
            'post_type' => SEVO_INSCR_POST_TYPE,
            'meta_query' => array(
                array(
                    'key' => '_sevo_inscricao_evento_id',
                    'value' => $event_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_sevo_inscricao_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'post_status' => array('publish', 'pending', 'draft'),
            'numberposts' => 1
        ));

        if (!empty($existing_inscricao)) {
            wp_send_json_error('Usuário já está inscrito neste evento');
        }

        // Verifica limite de vagas
        $vagas = get_post_meta($event_id, '_sevo_evento_vagas', true);
        if ($vagas) {
            $total_inscricoes = get_posts(array(
                'post_type' => SEVO_INSCR_POST_TYPE,
                'meta_query' => array(
                    array(
                        'key' => '_sevo_inscricao_evento_id',
                        'value' => $event_id,
                        'compare' => '='
                    ),
                    array(
                        'key' => '_sevo_inscricao_status',
                        'value' => 'aceita',
                        'compare' => '='
                    )
                ),
                'post_status' => 'publish',
                'numberposts' => -1
            ));

            if (count($total_inscricoes) >= intval($vagas)) {
                wp_send_json_error('Não há mais vagas disponíveis para este evento');
            }
        }

        // Cria a inscrição
        $inscricao_data = array(
            'post_title' => 'Inscrição de ' . wp_get_current_user()->display_name . ' em ' . $evento->post_title,
            'post_type' => SEVO_INSCR_POST_TYPE,
            'post_status' => 'publish',
            'post_author' => $user_id
        );

        $inscricao_id = wp_insert_post($inscricao_data);

        if (is_wp_error($inscricao_id)) {
            wp_send_json_error('Erro ao criar inscrição: ' . $inscricao_id->get_error_message());
        }

        // Adiciona metadados
        update_post_meta($inscricao_id, '_sevo_inscricao_evento_id', $event_id);
        update_post_meta($inscricao_id, '_sevo_inscricao_user_id', $user_id);
        update_post_meta($inscricao_id, '_sevo_inscricao_status', 'solicitada');
        update_post_meta($inscricao_id, '_sevo_inscricao_data', current_time('Y-m-d H:i:s'));

        // Adiciona comentário no fórum do evento
        $user = wp_get_current_user();
        $data_hora = current_time('d/m/Y H:i:s');
        $message = sprintf(
            'Nova inscrição solicitada por %s em %s',
            $user->display_name,
            $data_hora
        );
        sevo_add_inscription_log_comment($event_id, $message);

        wp_send_json_success(array(
            'message' => 'Inscrição realizada com sucesso',
            'inscricao_id' => $inscricao_id
        ));
    }

    /**
     * AJAX para cancelar inscrição
     */
    public function ajax_cancel_inscricao() {
        // Verifica nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sevo_landing_page_nonce')) {
            wp_send_json_error('Nonce inválido');
        }

        // Verifica se usuário está logado
        if (!is_user_logged_in()) {
            wp_send_json_error('Usuário deve estar logado');
        }

        $inscricao_id = intval($_POST['inscricao_id']);
        $user_id = get_current_user_id();

        if (!$inscricao_id) {
            wp_send_json_error('ID da inscrição inválido');
        }

        // Verifica se a inscrição existe
        $inscricao = get_post($inscricao_id);
        if (!$inscricao || $inscricao->post_type !== SEVO_INSCR_POST_TYPE) {
            wp_send_json_error('Inscrição não encontrada');
        }

        // Verifica se o usuário é o dono da inscrição ou tem permissão para gerenciar
        $inscricao_user_id = get_post_meta($inscricao_id, '_sevo_inscricao_user_id', true);
        if ($inscricao_user_id != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão para cancelar esta inscrição');
        }

        // Verifica se a inscrição não está aceita (não pode cancelar inscrição aceita)
        $status_atual = get_post_meta($inscricao_id, '_sevo_inscricao_status', true);
        if ($status_atual === 'aceita') {
            wp_send_json_error('Não é possível cancelar uma inscrição já aceita');
        }

        // Obtém o ID do evento para retornar
        $event_id = get_post_meta($inscricao_id, '_sevo_inscricao_evento_id', true);

        // Incrementa o contador de cancelamentos
        $cancelamentos = get_post_meta($inscricao_id, '_sevo_inscricao_cancelamentos', true);
        $cancelamentos = intval($cancelamentos) + 1;
        update_post_meta($inscricao_id, '_sevo_inscricao_cancelamentos', $cancelamentos);

        // Atualiza o status para cancelada
        update_post_meta($inscricao_id, '_sevo_inscricao_status', 'cancelada');
        update_post_meta($inscricao_id, '_sevo_inscricao_data_cancelamento', current_time('Y-m-d H:i:s'));

        // Adiciona comentário no fórum do evento
        $user = wp_get_current_user();
        $data_hora = current_time('d/m/Y H:i:s');
        $motivo = isset($_POST['motivo']) ? sanitize_text_field($_POST['motivo']) : 'Não informado';
        $message = sprintf(
            'Inscrição cancelada por %s em %s. Motivo: %s. Total de cancelamentos: %d',
            $user->display_name,
            $data_hora,
            $motivo,
            $cancelamentos
        );
        sevo_add_inscription_log_comment($event_id, $message);

        wp_send_json_success(array(
            'message' => 'Inscrição cancelada com sucesso',
            'event_id' => $event_id
        ));
    }

    /**
     * AJAX para buscar opções dos filtros
     */
    public function ajax_get_filter_options() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');

        $options = array();

        // Organizações
        $orgs = get_posts(array(
            'post_type' => SEVO_ORG_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        $options['organizacoes'] = array();
        foreach ($orgs as $org) {
            $options['organizacoes'][] = array(
                'id' => $org->ID,
                'nome' => $org->post_title
            );
        }

        // Tipos de evento
        $tipos = get_posts(array(
            'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        $options['tipos_evento'] = array();
        foreach ($tipos as $tipo) {
            $options['tipos_evento'][] = array(
                'id' => $tipo->ID,
                'nome' => $tipo->post_title
            );
        }

        // Anos de inscrição
        global $wpdb;
        $anos_inscricao = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT YEAR(meta_value) as ano 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s 
             AND p.post_type = %s 
             AND p.post_status = 'publish' 
             AND meta_value != '' 
             ORDER BY ano DESC",
            '_sevo_evento_data_inicio_inscricoes',
            SEVO_EVENTO_POST_TYPE
        ));
        $options['anos_inscricao'] = array_filter($anos_inscricao);

        // Anos de evento
        $anos_evento = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT YEAR(meta_value) as ano 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s 
             AND p.post_type = %s 
             AND p.post_status = 'publish' 
             AND meta_value != '' 
             ORDER BY ano DESC",
            '_sevo_evento_data_inicio_evento',
            SEVO_EVENTO_POST_TYPE
        ));
        $options['anos_evento'] = array_filter($anos_evento);

        wp_send_json_success($options);
    }

    /**
     * AJAX para aplicar filtros nos eventos
     */
    public function ajax_filter_eventos() {
        check_ajax_referer('sevo_landing_page_nonce', 'nonce');

        $filters = array(
            'organizacao' => sanitize_text_field($_POST['organizacao'] ?? ''),
            'tipo_evento' => sanitize_text_field($_POST['tipo_evento'] ?? ''),
            'ano_inscricao' => sanitize_text_field($_POST['ano_inscricao'] ?? ''),
            'mes_inscricao' => sanitize_text_field($_POST['mes_inscricao'] ?? ''),
            'ano_evento' => sanitize_text_field($_POST['ano_evento'] ?? ''),
            'mes_evento' => sanitize_text_field($_POST['mes_evento'] ?? '')
        );

        // Remove filtros vazios
        $filters = array_filter($filters);

        // Busca eventos filtrados para cada seção
        $sections = array('inscricoes_abertas', 'planejados', 'em_andamento', 'encerrados');
        $results = array();

        foreach ($sections as $section) {
            $eventos = $this->get_filtered_eventos($section, $filters);
            $results[$section] = array(
                'count' => count($eventos),
                'eventos' => array_slice($eventos, 0, 8) // Primeiros 8 para o carrossel
            );
        }

        wp_send_json_success($results);
    }

    /**
     * Busca eventos filtrados para uma seção específica
     */
    private function get_filtered_eventos($section, $filters) {
        $today = date('Y-m-d');
        $meta_query = array('relation' => 'AND');

        // Filtros de seção
        switch ($section) {
            case 'inscricoes_abertas':
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_inscricoes',
                    'value' => $today,
                    'compare' => '<=',
                    'type' => 'DATE'
                );
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_fim_inscricoes',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
                break;

            case 'planejados':
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_inscricoes',
                    'value' => $today,
                    'compare' => '>',
                    'type' => 'DATE'
                );
                break;

            case 'em_andamento':
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_evento',
                    'value' => $today,
                    'compare' => '<=',
                    'type' => 'DATE'
                );
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_fim_evento',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
                break;

            case 'encerrados':
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_fim_evento',
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'DATE'
                );
                break;
        }

        // Filtros personalizados
        if (!empty($filters['tipo_evento'])) {
            $meta_query[] = array(
                'key' => '_sevo_evento_tipo_evento_id',
                'value' => $filters['tipo_evento'],
                'compare' => '='
            );
        }

        if (!empty($filters['organizacao'])) {
            // Buscar tipos de evento da organização
            $tipos_org = get_posts(array(
                'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
                'meta_key' => '_sevo_tipo_evento_organizacao_id',
                'meta_value' => $filters['organizacao'],
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            if (!empty($tipos_org)) {
                $meta_query[] = array(
                    'key' => '_sevo_evento_tipo_evento_id',
                    'value' => $tipos_org,
                    'compare' => 'IN'
                );
            } else {
                // Se não há tipos para a organização, retorna vazio
                return array();
            }
        }

        // Filtros de data de inscrição
        if (!empty($filters['ano_inscricao'])) {
            $ano = $filters['ano_inscricao'];
            $mes = !empty($filters['mes_inscricao']) ? str_pad($filters['mes_inscricao'], 2, '0', STR_PAD_LEFT) : '';
            
            if ($mes) {
                $data_inicio = $ano . '-' . $mes . '-01';
                $data_fim = $ano . '-' . $mes . '-31';
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_inscricoes',
                    'value' => array($data_inicio, $data_fim),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                );
            } else {
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_inscricoes',
                    'value' => $ano,
                    'compare' => 'LIKE'
                );
            }
        }

        // Filtros de data do evento
        if (!empty($filters['ano_evento'])) {
            $ano = $filters['ano_evento'];
            $mes = !empty($filters['mes_evento']) ? str_pad($filters['mes_evento'], 2, '0', STR_PAD_LEFT) : '';
            
            if ($mes) {
                $data_inicio = $ano . '-' . $mes . '-01';
                $data_fim = $ano . '-' . $mes . '-31';
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_evento',
                    'value' => array($data_inicio, $data_fim),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                );
            } else {
                $meta_query[] = array(
                    'key' => '_sevo_evento_data_inicio_evento',
                    'value' => $ano,
                    'compare' => 'LIKE'
                );
            }
        }

        $query = new WP_Query(array(
            'post_type' => SEVO_EVENTO_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => $meta_query,
            'orderby' => 'meta_value',
            'meta_key' => '_sevo_evento_data_inicio_evento',
            'order' => ($section === 'encerrados') ? 'DESC' : 'ASC'
        ));

        $eventos = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $eventos[] = array(
                    'id' => get_the_ID(),
                    'html' => $this->render_evento_carousel_card(get_the_ID())
                );
            }
            wp_reset_postdata();
        }

        return $eventos;
    }
}

// Inicializa o shortcode
new Sevo_Landing_Page_Shortcode();