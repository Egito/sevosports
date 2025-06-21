<?php
/**
 * CPT para Inscrições (sevo_inscr)
 * Gerencia as inscrições dos usuários nos eventos.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Inscricoes_CPT {

    private $post_type = 'sevo_inscr';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_custom_statuses'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_data'));
        add_filter('display_post_states', array($this, 'add_custom_post_states'), 10, 2);
    }

    /**
     * Registra o Custom Post Type para inscrições.
     */
    public function register_post_type() {
        $labels = array(
            'name'          => 'Inscrições',
            'singular_name' => 'Inscrição',
            'menu_name'     => 'Inscrições',
            'all_items'     => 'Todas as Inscrições',
            'add_new'       => 'Adicionar Nova',
            'add_new_item'  => 'Adicionar Nova Inscrição',
            'edit_item'     => 'Editar Inscrição',
        );
        $args = array(
            'labels'        => $labels,
            'public'        => false,
            'publicly_queryable' => false,
            'show_ui'       => true,
            'show_in_menu'  => 'edit.php?post_type=sevo-evento', // Adiciona como submenu de Eventos
            'capability_type' => 'post',
            'has_archive'   => false,
            'hierarchical'  => false,
            'supports'      => array('title'),
            'menu_icon'     => 'dashicons-id-alt',
        );
        register_post_type($this->post_type, $args);
    }

    /**
     * Registra os status personalizados para as inscrições.
     */
    public function register_custom_statuses() {
        register_post_status('solicitada', array(
            'label'                     => 'Solicitada',
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Solicitada <span class="count">(%s)</span>', 'Solicitadas <span class="count">(%s)</span>'),
        ));
        register_post_status('aceita', array(
            'label'                     => 'Aceita',
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Aceita <span class="count">(%s)</span>', 'Aceitas <span class="count">(%s)</span>'),
        ));
        register_post_status('rejeitada', array(
            'label'                     => 'Rejeitada',
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Rejeitada <span class="count">(%s)</span>', 'Rejeitadas <span class="count">(%s)</span>'),
        ));
        register_post_status('cancelada', array(
            'label'                     => 'Cancelada',
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Cancelada <span class="count">(%s)</span>', 'Canceladas <span class="count">(%s)</span>'),
        ));
    }
    
    /**
     * Adiciona os status na lista de estados do post para fácil visualização.
     */
    public function add_custom_post_states($post_states, $post) {
        if ($post->post_type === $this->post_type && get_post_status($post->ID) !== 'publish') {
            $status = get_post_status_object(get_post_status($post->ID));
            if ($status) {
                $post_states[$status->name] = $status->label;
            }
        }
        return $post_states;
    }

    /**
     * Adiciona os Meta Boxes.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'sevo_inscr_details_meta_box',
            'Detalhes da Inscrição',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    /**
     * Renderiza o conteúdo do Meta Box.
     */
    public function render_meta_box($post) {
        wp_nonce_field('sevo_inscr_nonce', 'sevo_inscr_nonce');
        $evento_id = get_post_meta($post->ID, '_sevo_inscr_evento_id', true);
        $user_id = get_post_meta($post->ID, '_sevo_inscr_user_id', true);
        $cancel_count = get_post_meta($post->ID, '_sevo_inscr_cancel_count', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label>Evento</label></th>
                <td><?php echo esc_html(get_the_title($evento_id)); ?> (ID: <?php echo esc_html($evento_id); ?>)</td>
            </tr>
             <tr>
                <th><label>Usuário</label></th>
                <td><?php $user_info = get_userdata($user_id); echo esc_html($user_info->display_name); ?> (ID: <?php echo esc_html($user_id); ?>)</td>
            </tr>
             <tr>
                <th><label>Contador de Cancelamentos</label></th>
                <td><?php echo esc_html($cancel_count ?: 0); ?></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Salva os dados do Meta Box.
     */
    public function save_meta_data($post_id) {
        if (!isset($_POST['sevo_inscr_nonce']) || !wp_verify_nonce($_POST['sevo_inscr_nonce'], 'sevo_inscr_nonce')) {
            return;
        }
        // A lógica de salvamento será feita principalmente via AJAX.
    }
}

/**
 * Adiciona um comentário de log no post do evento.
 *
 * @param int    $evento_id ID do post do evento.
 * @param string $message   A mensagem a ser registrada.
 */
function sevo_add_inscription_log_comment($evento_id, $message) {
    if (!$evento_id || empty($message)) {
        return;
    }

    $user = wp_get_current_user();
    $user_name = $user->exists() ? $user->display_name : 'Sistema';

    $commentdata = array(
        'comment_post_ID'      => $evento_id,
        'comment_author'       => $user_name,
        'comment_author_email' => $user->exists() ? $user->user_email : 'sistema@sevosports.com',
        'comment_content'      => $message,
        'comment_type'         => 'inscription_log',
        'comment_agent'        => 'SevoSports Plugin',
        'comment_date'         => current_time('mysql'),
        'comment_approved'     => 1,
    );

    wp_insert_comment($commentdata);
}

new Sevo_Inscricoes_CPT();