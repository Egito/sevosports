<?php
/**
 * CPT para Inscrições (sevo_inscr)
 * Gerencia as inscrições dos usuários nos eventos.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Inscricoes_CPT {

    private $post_type = SEVO_INSCR_POST_TYPE;

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_custom_statuses'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->post_type, array($this, 'save_meta_data'));
        add_filter('display_post_states', array($this, 'add_custom_post_states'), 10, 2);
        add_action('admin_head', array($this, 'hide_add_new_button'));
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
            'show_in_menu'  => 'sevo-eventos', // Adiciona como submenu do plugin principal
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts'       => 'do_not_allow',
            ),
            'map_meta_cap'  => true,
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
        $status = get_post_meta($post->ID, '_sevo_inscr_status', true);
        $data_inscricao = get_post_meta($post->ID, '_sevo_inscr_data', true);
        $cancel_count = get_post_meta($post->ID, '_sevo_inscr_cancel_count', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label>Evento</label></th>
                <td><?php echo esc_html(get_the_title($evento_id)); ?> (ID: <?php echo esc_html($evento_id); ?>)</td>
            </tr>
             <tr>
                <th><label>Usuário</label></th>
                <td><?php 
                    $user_info = get_userdata($user_id); 
                    $user_name = ($user_info && isset($user_info->display_name)) ? $user_info->display_name : 'Usuário não encontrado';
                    echo esc_html($user_name); 
                ?> (ID: <?php echo esc_html($user_id); ?>)</td>
            </tr>
            <tr>
                <th><label>Status</label></th>
                <td><?php echo esc_html($status ?: 'solicitada'); ?></td>
            </tr>
            <tr>
                <th><label>Data da Inscrição</label></th>
                <td><?php echo esc_html($data_inscricao ?: $post->post_date); ?></td>
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
    /**
     * Oculta o botão "Adicionar Nova" na interface administrativa.
     */
    public function hide_add_new_button() {
        global $pagenow, $post_type;
        
        if ($pagenow === 'edit.php' && $post_type === $this->post_type) {
            echo '<style>
                .page-title-action { display: none !important; }
                .wrap .add-new-h2 { display: none !important; }
            </style>';
        }
    }
}

/**
 * Adiciona um post de log no tópico do fórum do evento.
 *
 * @param int    $evento_id ID do post do evento.
 * @param string $message   A mensagem a ser registrada.
 */
function sevo_add_inscription_log_comment($evento_id, $message) {
    if (!$evento_id || empty($message)) {
        return;
    }

    // Verificar se o Asgaros Forum está ativo
    if (!class_exists('AsgarosForum')) {
        return;
    }

    // Obter o ID do tópico do fórum associado ao evento
    $topic_id = get_post_meta($evento_id, '_sevo_forum_topic_id', true);
    if (!$topic_id) {
        return;
    }

    global $asgarosforum;
    if (!$asgarosforum || !method_exists($asgarosforum->content, 'insert_post')) {
        return;
    }

    $user = wp_get_current_user();
    $author_id = $user->exists() ? $user->ID : 1; // ID 1 geralmente é o admin

    // Inserir post no tópico do fórum
    $post_id = $asgarosforum->content->insert_post(
        $topic_id,  // topic_id
        $message,   // post content
        $author_id  // author_id
    );

    return $post_id;
}