<?php
/**
 * Classe para o Custom Post Type: Eventos.
 * O post type original 'sevo-secoes' foi refatorado para 'sevo-evento'.
 * Este CPT agora representa o evento final, que está ligado a um 'sevo-tipo-evento'.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_CPT_Final {

    private $post_type = SEVO_EVENTO_POST_TYPE;

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->post_type, array($this, 'save_post_meta'));
        
        // Enqueue de scripts para o admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Eventos',
            'singular_name'      => 'Evento',
            'menu_name'          => 'Eventos',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Evento',
            'edit_item'          => 'Editar Evento',
            'new_item'           => 'Novo Evento',
            'view_item'          => 'Ver Evento',
            'search_items'       => 'Buscar Eventos',
            'not_found'          => 'Nenhum evento encontrado',
            'not_found_in_trash' => 'Nenhum evento encontrado na lixeira'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'          => 'sevo-eventos',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'   => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'evento'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'menu_icon'           => 'dashicons-calendar-alt' // Ícone de calendário
        );

        register_post_type($this->post_type, $args);
    }

    public function register_taxonomies() {
        $labels = array(
            'name'              => 'Categorias de Eventos',
            'singular_name'     => 'Categoria de Evento',
            'search_items'      => 'Buscar Categorias',
            'all_items'         => 'Todas as Categorias',
            'parent_item'       => 'Categoria Pai',
            'parent_item_colon' => 'Categoria Pai:',
            'edit_item'         => 'Editar Categoria',
            'update_item'       => 'Atualizar Categoria',
            'add_new_item'      => 'Adicionar Nova Categoria',
            'new_item_name'     => 'Nome da Nova Categoria',
            'menu_name'         => 'Categorias',
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'evento-categoria'),
        );

        register_taxonomy('sevo_evento_categoria', array($this->post_type), $args);
    }

    public function admin_enqueue_scripts($hook) {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ($post && $this->post_type === $post->post_type) {
                wp_enqueue_script(
                    'sevo-admin-evento-script',
                    SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-sevo-evento.js',
                    array('jquery'),
                    SEVO_EVENTOS_VERSION,
                    true
                );
                wp_localize_script('sevo-admin-evento-script', 'sevoEventoAdmin', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('sevo_admin_nonce')
                ));
            }
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_evento_details_meta_box',
            'Detalhes do Evento',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('sevo_evento_meta_box_nonce', 'sevo_evento_meta_box_nonce');

        // Campos de metadados
        $tipo_evento_id = get_post_meta($post->ID, '_sevo_evento_tipo_evento_id', true);
        $data_inicio_insc = get_post_meta($post->ID, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($post->ID, '_sevo_evento_data_fim_inscricoes', true);
        $data_inicio_evento = get_post_meta($post->ID, '_sevo_evento_data_inicio_evento', true);
        $data_fim_evento = get_post_meta($post->ID, '_sevo_evento_data_fim_evento', true);
        $vagas = get_post_meta($post->ID, '_sevo_evento_vagas', true);

        // Busca os Tipos de Evento
        $tipos_de_evento = get_posts(array(
            'post_type' => SEVO_TIPO_EVENTO_POST_TYPE,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <table class="form-table">
            <tr id="sevo-tipo-evento-field">
                <th><label for="sevo_evento_tipo_evento_id">Tipo de Evento</label></th>
                <td>
                    <select id="sevo_evento_tipo_evento_id" name="sevo_evento_tipo_evento_id" required>
                        <option value="">Selecione um Tipo de Evento</option>
                        <?php foreach ($tipos_de_evento as $tipo) : ?>
                            <option value="<?php echo esc_attr($tipo->ID); ?>" data-max-vagas="<?php echo esc_attr(get_post_meta($tipo->ID, '_sevo_tipo_evento_max_vagas', true)); ?>" <?php selected($tipo_evento_id, $tipo->ID); ?>>
                                <?php echo esc_html($tipo->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Selecione o tipo de evento ao qual este evento pertence.</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_vagas">Número de Vagas</label></th>
                <td>
                    <input type="number" id="sevo_evento_vagas" name="sevo_evento_vagas" value="<?php echo esc_attr($vagas); ?>" min="1" step="1">
                    <p id="vagas-info" class="description">O número de vagas não pode exceder o limite definido no Tipo de Evento.</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_data_inicio_inscricoes">Início das Inscrições</label></th>
                <td>
                    <input type="date" id="sevo_evento_data_inicio_inscricoes" name="sevo_evento_data_inicio_inscricoes" value="<?php echo esc_attr($data_inicio_insc); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_data_fim_inscricoes">Fim das Inscrições</label></th>
                <td>
                    <input type="date" id="sevo_evento_data_fim_inscricoes" name="sevo_evento_data_fim_inscricoes" value="<?php echo esc_attr($data_fim_insc); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_data_inicio_evento">Início do Evento</label></th>
                <td>
                    <input type="date" id="sevo_evento_data_inicio_evento" name="sevo_evento_data_inicio_evento" value="<?php echo esc_attr($data_inicio_evento); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_data_fim_evento">Fim do Evento</label></th>
                <td>
                    <input type="date" id="sevo_evento_data_fim_evento" name="sevo_evento_data_fim_evento" value="<?php echo esc_attr($data_fim_evento); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['sevo_evento_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_evento_meta_box_nonce'], 'sevo_evento_meta_box_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $meta_fields = array(
            '_sevo_evento_tipo_evento_id' => 'int',
            '_sevo_evento_data_inicio_inscricoes' => 'text',
            '_sevo_evento_data_fim_inscricoes' => 'text',
            '_sevo_evento_data_inicio_evento' => 'text',
            '_sevo_evento_data_fim_evento' => 'text',
            '_sevo_evento_vagas' => 'int',
        );

        foreach ($meta_fields as $key => $type) {
            $post_key = ltrim($key, '_');
            if (isset($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                if ($type === 'int') {
                    $value = absint($value);
                }
                
                // Validação de vagas
                if($key === '_sevo_evento_vagas' && isset($_POST['sevo_evento_tipo_evento_id'])) {
                    $tipo_evento_id = absint($_POST['sevo_evento_tipo_evento_id']);
                    $max_vagas = get_post_meta($tipo_evento_id, '_sevo_tipo_evento_max_vagas', true);
                    if ($max_vagas > 0 && $value > $max_vagas) {
                        $value = $max_vagas; // Limita ao máximo permitido
                    }
                }
                
                update_post_meta($post_id, $key, $value);
            }
        }

        // Integração com fórum - criar/atualizar sub-fórum para o evento
        $this->handle_forum_integration($post_id);
    }

    /**
     * Gerencia a integração com o fórum para eventos.
     */
    private function handle_forum_integration($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        // Criar/atualizar sub-fórum para este evento
        $this->create_or_update_event_subforum($post_id, $post);
    }

    /**
     * Cria ou atualiza sub-fórum para o evento.
     */
    private function create_or_update_event_subforum($post_id, $post) {
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        if (!$tipo_evento_id) {
            return;
        }

        $forum_id = get_post_meta($tipo_evento_id, '_sevo_forum_forum_id', true);
        if (!$forum_id) {
            return;
        }

        global $asgarosforum;
        $existing_subforum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
        $event_name = $post->post_title;
        
        // Se já existe um sub-fórum, verificar se precisa atualizar o nome
        if ($existing_subforum_id && class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'get_forum')) {
                $subforum = $asgarosforum->content->get_forum($existing_subforum_id);
                if ($subforum && is_object($subforum) && property_exists($subforum, 'name')) {
                    // Verificar se o nome mudou
                    if ($subforum->name !== $event_name) {
                        // Atualizar o nome do sub-fórum usando consulta SQL direta
                        // Buscar a categoria do fórum pai
                        $forum_data = $asgarosforum->content->get_forum($forum_id);
                        $category_id = ($forum_data && is_object($forum_data) && property_exists($forum_data, 'parent_id')) ? $forum_data->parent_id : 0;
                        
                        if ($category_id) {
                            $asgarosforum->db->update(
                                $asgarosforum->tables->forums,
                                array(
                                    'name'         => $event_name,
                                    'description'  => 'Tópicos de discussão para o evento: ' . $event_name,
                                    'icon'         => 'fas fa-calendar-alt',
                                    'sort'         => 1,
                                    'forum_status' => 'normal',
                                    'parent_id'    => $category_id,
                                    'parent_forum' => $forum_id,
                                ),
                                array('id' => $existing_subforum_id),
                                array('%s', '%s', '%s', '%d', '%s', '%d', '%d'),
                                array('%d')
                            );
                        }
                    }
                    return; // Sub-fórum existe e foi atualizado se necessário
                } else {
                    // Sub-fórum não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_subforum_id');
                }
            }
        }

        // Criar novo sub-fórum usando a instância do AsgarosForum
        $sub_forum_id = 0;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_forum')) {
                // Buscar a categoria do fórum pai
                $forum_data = $asgarosforum->content->get_forum($forum_id);
                $category_id = $forum_data ? $forum_data->parent_id : 0;
                
                if ($category_id) {
                    $sub_forum_id = $asgarosforum->content->insert_forum(
                        $category_id, // category_id
                        $event_name,
                        'Tópicos de discussão para o evento: ' . $event_name,
                        $forum_id, // parent_forum
                        'fas fa-calendar-alt', // icon
                        1, // order
                        'normal' // status
                    );
                }
            }
        }

        if ($sub_forum_id) {
            update_post_meta($post_id, '_sevo_forum_subforum_id', $sub_forum_id);
        }
    }
}