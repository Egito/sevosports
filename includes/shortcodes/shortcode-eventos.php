<?php
/**
 * Shortcode handler para o dashboard de Eventos [sevo-eventos-dashboard]
 * Agora com funcionalidade CRUD completa via modal e sistema de inscrições.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_Dashboard_Shortcode {
    public function __construct() {
        add_shortcode('sevo-eventos-dashboard', array($this, 'render_dashboard_shortcode'));
        
        // Ações AJAX para o CRUD de eventos
        add_action('wp_ajax_sevo_get_evento_form', array($this, 'ajax_get_evento_form'));
        add_action('wp_ajax_sevo_save_evento', array($this, 'ajax_save_evento'));
        add_action('wp_ajax_sevo_toggle_evento_status', array($this, 'ajax_toggle_evento_status'));
        
        // Ação AJAX para a listagem
        add_action('wp_ajax_sevo_load_more_eventos', array($this, 'ajax_load_more_eventos'));
        add_action('wp_ajax_nopriv_sevo_load_more_eventos', array($this, 'ajax_load_more_eventos'));

        // AÇÃO AJAX PARA INSCRIÇÕES
        add_action('wp_ajax_sevo_handle_inscription', array($this, 'ajax_handle_inscription'));
    }

    // ... (as funções render_dashboard_shortcode, ajax_get_evento_form, ajax_save_evento, ajax_toggle_evento_status permanecem as mesmas) ...
    
    /**
     * Renderiza o shortcode do dashboard.
     */
    public function render_dashboard_shortcode() {
        wp_enqueue_style('sevo-eventos-dashboard-style');
        wp_enqueue_script('sevo-eventos-dashboard-script');
        wp_enqueue_style('dashicons');
        
        wp_localize_script('sevo-eventos-dashboard-script', 'sevoDashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_dashboard_nonce'),
        ));

        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/view/dashboard-eventos-view.php');
        return ob_get_clean();
    }

    /**
     * AJAX: Retorna o HTML do formulário para criar ou editar um evento.
     */
    public function ajax_get_evento_form() {
        check_ajax_referer('sevo_dashboard_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('Acesso negado.');
        }

        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $evento = ($event_id > 0) ? get_post($event_id) : null;

        // Verificação de permissão (exemplo)
        if ($evento) {
            $tipo_evento_id = get_post_meta($evento->ID, '_sevo_evento_tipo_evento_id', true);
            $org_id = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_organizacao_id', true);
            $org_owner_id = get_post_field('post_author', $org_id);
            if (get_current_user_id() != $org_owner_id && !current_user_can('manage_options')) {
                 wp_send_json_error('Você não tem permissão para editar este evento.');
            }
        }
        
        ob_start();
        include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-evento-form.php'); // Caminho corrigido
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Salva (cria ou atualiza) um evento.
     */
    public function ajax_save_evento() {
        // ... (código existente sem alteração)
    }
    
    /**
     * AJAX: Alterna o status de um evento entre 'publicado' e 'rascunho'.
     */
    public function ajax_toggle_evento_status() {
        // ... (código existente sem alteração)
    }

    /**
     * AJAX: Lida com solicitações de inscrição e cancelamento.
     */
    public function ajax_handle_inscription() {
        check_ajax_referer('sevo_dashboard_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Você precisa estar logado para se inscrever.'));
        }

        $evento_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $user_id = get_current_user_id();
        $action = isset($_POST['inscription_action']) ? sanitize_text_field($_POST['inscription_action']) : '';

        if (!$evento_id || !in_array($action, ['inscr', 'cancel'])) {
            wp_send_json_error(array('message' => 'Ação inválida.'));
        }
        
        $evento_title = get_the_title($evento_id);
        $inscription = $this->get_user_inscription_for_event($user_id, $evento_id);

        if ($action === 'inscr') {
            $new_post_id = wp_insert_post(array(
                'post_type'   => 'sevo_inscr',
                'post_title'  => 'Inscrição de ' . wp_get_current_user()->display_name . ' em ' . $evento_title,
                'post_status' => 'solicitada',
                'post_author' => $user_id,
            ));

            if ($new_post_id) {
                update_post_meta($new_post_id, '_sevo_inscr_evento_id', $evento_id);
                update_post_meta($new_post_id, '_sevo_inscr_user_id', $user_id);
                update_post_meta($new_post_id, '_sevo_inscr_cancel_count', 0);
                
                sevo_add_inscription_log_comment($evento_id, "Inscrição solicitada.");
                wp_send_json_success(array('message' => 'Inscrição solicitada com sucesso!', 'newState' => 'cancelar'));
            } else {
                wp_send_json_error(array('message' => 'Erro ao solicitar inscrição.'));
            }

        } elseif ($action === 'cancel') {
            if (!$inscription) {
                wp_send_json_error(array('message' => 'Inscrição não encontrada para cancelar.'));
            }

            $cancel_count = (int) get_post_meta($inscription->ID, '_sevo_inscr_cancel_count', true);
            if ($cancel_count >= 2) {
                wp_update_post(array('ID' => $inscription->ID, 'post_status' => 'cancelada'));
                sevo_add_inscription_log_comment($evento_id, "Inscrição cancelada (limite atingido).");
                wp_send_json_error(array('message' => 'Limite de cancelamentos atingido. A inscrição foi cancelada permanentemente.', 'newState' => 'disabled'));
            }

            $cancel_count++;
            update_post_meta($inscription->ID, '_sevo_inscr_cancel_count', $cancel_count);
            wp_update_post(array('ID' => $inscription->ID, 'post_status' => 'cancelada'));

            sevo_add_inscription_log_comment($evento_id, "Inscrição cancelada pelo usuário (tentativa {$cancel_count}).");
            wp_send_json_success(array('message' => 'Inscrição cancelada.', 'newState' => 'inscrever'));
        }
    }
    
    /**
     * AJAX para carregar mais eventos
     */
    public function ajax_load_more_eventos() {
        check_ajax_referer('sevo_dashboard_nonce', 'nonce');
        
        $args = $this->get_filtered_query_args(isset($_POST['page']) ? intval($_POST['page']) : 1);
        $query = new WP_Query($args);
        $items_html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items_html .= $this->render_evento_card(get_the_ID());
            }
        }
        wp_reset_postdata();

        wp_send_json_success(array(
            'items' => $items_html,
            'hasMore' => (isset($_POST['page']) ? intval($_POST['page']) : 1) < $query->max_num_pages
        ));
    }

    /**
     * Renderiza o card de um evento.
     */
    private function render_evento_card($post_id) {
        // ... (código existente para buscar dados do card)
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: 'default_image_url.jpg';

        ob_start();
        ?>
        <div class="sevo-card evento-card" data-event-id="<?php echo esc_attr($post_id); ?>">
            <div class="sevo-card-content">
                <h3 class="sevo-card-title"><?php echo get_the_title($post_id); ?></h3>
                </div>
            <div class="sevo-card-footer">
                <?php if (is_user_logged_in()):
                    $inscription = $this->get_user_inscription_for_event(get_current_user_id(), $post_id);
                    $status = $inscription ? get_post_status($inscription->ID) : '';
                    $cancel_count = $inscription ? (int)get_post_meta($inscription->ID, '_sevo_inscr_cancel_count', true) : 0;

                    $button_text = 'Inscrever-se';
                    $button_action = 'inscr';
                    $button_class = 'sevo-button-primary';
                    $disabled = '';

                    if ($status === 'solicitada' || $status === 'aceita') {
                        $button_text = 'Cancelar Inscrição';
                        $button_action = 'cancel';
                        $button_class = 'sevo-button-danger';
                        if ($cancel_count >= 2) {
                             $disabled = 'disabled';
                        }
                    }
                ?>
                    <button class="sevo-inscription-button <?php echo $button_class; ?>" 
                            data-event-id="<?php echo esc_attr($post_id); ?>"
                            data-action="<?php echo esc_attr($button_action); ?>" <?php echo $disabled; ?>>
                        <?php echo esc_html($button_text); ?>
                    </button>
                <?php endif; ?>
                 <button class="sevo-button-secondary sevo-edit-button" data-event-id="<?php echo esc_attr($post_id); ?>">Ver Detalhes</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Busca a inscrição de um usuário para um evento específico.
     */
    private function get_user_inscription_for_event($user_id, $evento_id) {
        $args = array(
            'post_type' => 'sevo_inscr',
            'posts_per_page' => 1,
            'author' => $user_id,
            'meta_query' => array(
                array(
                    'key' => '_sevo_inscr_evento_id',
                    'value' => $evento_id,
                    'compare' => '=',
                ),
            ),
            'post_status' => array('solicitada', 'aceita', 'cancelada', 'rejeitada', 'draft', 'publish'),
        );
        $query = new WP_Query($args);
        return $query->have_posts() ? $query->posts[0] : null;
    }

    private function get_filtered_query_args($page = 1) { /* ... Implementação existente ... */ return []; }
}
new Sevo_Eventos_Dashboard_Shortcode();