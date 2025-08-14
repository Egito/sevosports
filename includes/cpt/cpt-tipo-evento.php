<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe para o Custom Post Type: Tipos de Eventos.
 * O post type original 'sevo-eventos' foi refatorado para 'sevo-tipo-evento'.
 */
class Sevo_Tipo_Evento_CPT {

    private $post_type = SEVO_TIPO_EVENTO_POST_TYPE;

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Adicionar colunas personalizadas na listagem do admin
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Tipos de Eventos',
            'singular_name'      => 'Tipo de Evento',
            'menu_name'          => 'Tipos de Eventos',
            'add_new'           => 'Adicionar Novo',
            'add_new_item'      => 'Adicionar Novo Tipo de Evento',
            'edit_item'         => 'Editar Tipo de Evento',
            'new_item'          => 'Novo Tipo de Evento',
            'view_item'         => 'Ver Tipo de Evento',
            'search_items'      => 'Buscar Tipos de Eventos',
            'not_found'         => 'Nenhum tipo de evento encontrado',
            'not_found_in_trash'=> 'Nenhum tipo de evento encontrado na lixeira'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'sevo-eventos',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'tipos-de-evento'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'menu_icon'           => 'dashicons-forms' // Ícone alterado para diferenciar
        );

        register_post_type($this->post_type, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_tipo_evento_details',
            'Detalhes do Tipo de Evento',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('sevo_tipo_evento_meta_box', 'sevo_tipo_evento_meta_box_nonce');

        // Recuperar valores salvos com as novas chaves
        $organizacao_id = get_post_meta($post->ID, '_sevo_tipo_evento_organizacao_id', true);
        $autor_id = get_post_meta($post->ID, '_sevo_tipo_evento_autor_id', true);
        $max_vagas = get_post_meta($post->ID, '_sevo_tipo_evento_max_vagas', true);
        $status = get_post_meta($post->ID, '_sevo_tipo_evento_status', true);
        $tipo_participacao = get_post_meta($post->ID, '_sevo_tipo_evento_participacao', true);

        // Valores padrão
        $status = $status ?: 'ativo';

        // Buscar organizações
        $organizacoes = get_posts(array(
            'post_type' => SEVO_ORG_POST_TYPE,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Buscar usuários com roles específicas
        $users = get_users(array(
            'role__in' => array('administrator', 'editor', 'author'),
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        // Obter o usuário atual
        $current_user_id = get_current_user_id();
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sevo_tipo_evento_organizacao_id">Organização</label></th>
                <td>
                    <select id="sevo_tipo_evento_organizacao_id" name="sevo_tipo_evento_organizacao_id" required>
                        <option value="">Selecione uma organização</option>
                        <?php foreach ($organizacoes as $org) : ?>
                            <option value="<?php echo esc_attr($org->ID); ?>" 
                                    <?php selected($organizacao_id, $org->ID); ?>>
                                <?php echo esc_html($org->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_tipo_evento_autor_id">Autor</label></th>
                <td>
                    <select id="sevo_tipo_evento_autor_id" name="sevo_tipo_evento_autor_id" required>
                        <option value="">Selecione um autor</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" 
                                    <?php selected($autor_id ? $autor_id : $current_user_id, $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?> 
                                (<?php echo esc_html(implode(', ', $user->roles)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_tipo_evento_max_vagas">Número Máximo de Vagas</label></th>
                <td>
                    <input type="number" id="sevo_tipo_evento_max_vagas" name="sevo_tipo_evento_max_vagas" 
                           value="<?php echo esc_attr($max_vagas); ?>" min="1" step="1" required>
                    <p class="description">Defina o número máximo de vagas disponíveis para este tipo de evento. Os eventos criados a partir deste tipo usarão este valor como base.</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_tipo_evento_status">Status</label></th>
                <td>
                    <select id="sevo_tipo_evento_status" name="sevo_tipo_evento_status" required>
                        <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_tipo_evento_participacao">Tipo de Participação</label></th>
                <td>
                    <select id="sevo_tipo_evento_participacao" name="sevo_tipo_evento_participacao" required>
                        <option value="individual" <?php selected($tipo_participacao, 'individual'); ?>>Individual</option>
                        <option value="grupo" <?php selected($tipo_participacao, 'grupo'); ?>>Grupo</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_post_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['sevo_tipo_evento_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_tipo_evento_meta_box_nonce'], 'sevo_tipo_evento_meta_box')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) !== $this->post_type) {
            return;
        }

        // Salvar os campos com as novas chaves
        if (isset($_POST['sevo_tipo_evento_organizacao_id'])) {
            $organizacao_id = absint($_POST['sevo_tipo_evento_organizacao_id']);
            update_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', $organizacao_id);
        }

        if (isset($_POST['sevo_tipo_evento_autor_id'])) {
            $autor_id = absint($_POST['sevo_tipo_evento_autor_id']);
            update_post_meta($post_id, '_sevo_tipo_evento_autor_id', $autor_id);
        }

        if (isset($_POST['sevo_tipo_evento_max_vagas'])) {
            $max_vagas = absint($_POST['sevo_tipo_evento_max_vagas']);
            if ($max_vagas < 1) {
                $max_vagas = 1;
            }
            update_post_meta($post_id, '_sevo_tipo_evento_max_vagas', $max_vagas);
        }

        if (isset($_POST['sevo_tipo_evento_status'])) {
            $status = sanitize_text_field($_POST['sevo_tipo_evento_status']);
            if (in_array($status, array('ativo', 'inativo'))) {
                update_post_meta($post_id, '_sevo_tipo_evento_status', $status);
            }
        }

        if (isset($_POST['sevo_tipo_evento_participacao'])) {
            $tipo_participacao = sanitize_text_field($_POST['sevo_tipo_evento_participacao']);
            if (in_array($tipo_participacao, array('individual', 'grupo'))) {
                update_post_meta($post_id, '_sevo_tipo_evento_participacao', $tipo_participacao);
            }
        }

        // Integração com fórum - criar/atualizar fórum para o tipo de evento
        $this->handle_forum_integration($post_id);
    }

    /**
     * Gerencia a integração com o fórum para tipos de evento.
     */
    private function handle_forum_integration($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        // Verificar se a organização tem categoria de fórum
        $organizacao_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
        if (!$organizacao_id) {
            return;
        }

        // Garantir que a organização tenha uma categoria de fórum
        $org_post = get_post($organizacao_id);
        if ($org_post) {
            $this->ensure_organization_forum_category($organizacao_id, $org_post);
        }

        // Criar/atualizar fórum para este tipo de evento
        $this->create_or_update_event_type_forum($post_id, $post);
    }

    /**
     * Garante que a organização tenha uma categoria de fórum.
     */
    private function ensure_organization_forum_category($org_id, $org_post) {
        if (!class_exists('AsgarosForum')) {
            return;
        }

        global $asgarosforum;
        $existing_category_id = get_post_meta($org_id, '_sevo_forum_category_id', true);
        $organization_name = $org_post->post_title;
        
        // Se já existe uma categoria, verificar se precisa atualizar o nome
        if ($existing_category_id) {
            $category = get_term($existing_category_id, 'asgarosforum-category');
            if ($category && !is_wp_error($category)) {
                if ($category && is_object($category) && property_exists($category, 'name')) {
                    // Verificar se o nome mudou
                    if ($category->name !== $organization_name) {
                        // Atualizar o nome da categoria
                        wp_update_term($existing_category_id, 'asgarosforum-category', array(
                            'name' => $organization_name,
                            'description' => 'Categoria para discussões da organização: ' . $organization_name,
                            'slug' => sanitize_title($organization_name)
                        ));
                    }
                    return; // Categoria existe e foi atualizada se necessário
                } else {
                    // Categoria não existe mais, remover meta
                    delete_post_meta($org_id, '_sevo_forum_category_id');
                }
            }
        }

        // Verificar se já existe uma categoria com este nome (evitar duplicatas)
        $existing_term = get_term_by('name', $organization_name, 'asgarosforum-category');
        if ($existing_term) {
            update_post_meta($org_id, '_sevo_forum_category_id', $existing_term->term_id);
            return;
        }

        // Criar nova categoria
        $category_id = wp_insert_term(
            $organization_name,
            'asgarosforum-category',
            array(
                'description' => 'Categoria para discussões da organização: ' . $organization_name,
                'slug' => sanitize_title($organization_name)
            )
        );

        if (!is_wp_error($category_id)) {
            update_post_meta($org_id, '_sevo_forum_category_id', $category_id['term_id']);
        }
    }

    /**
     * Cria ou atualiza fórum para o tipo de evento.
     */
    private function create_or_update_event_type_forum($post_id, $post) {
        $organizacao_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
        if (!$organizacao_id) {
            return;
        }

        $category_id = get_post_meta($organizacao_id, '_sevo_forum_category_id', true);
        if (!$category_id) {
            return;
        }

        global $asgarosforum;
        $existing_forum_id = get_post_meta($post_id, '_sevo_forum_forum_id', true);
        $event_type_name = $post->post_title;
        
        // Se já existe um fórum, verificar se precisa atualizar o nome
        if ($existing_forum_id && class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'get_forum')) {
                $forum = $asgarosforum->content->get_forum($existing_forum_id);
                if ($forum && is_object($forum) && property_exists($forum, 'name')) {
                    // Verificar se o nome mudou
                    if ($forum->name !== $event_type_name) {
                        // Atualizar o nome do fórum usando consulta SQL direta
                        $asgarosforum->db->update(
                            $asgarosforum->tables->forums,
                            array(
                                'name'         => $event_type_name,
                                'description'  => 'Discussões sobre o tipo de evento: ' . $event_type_name,
                                'icon'         => 'fas fa-comments',
                                'sort'         => 1,
                                'forum_status' => 'normal',
                                'parent_id'    => $category_id,
                                'parent_forum' => 0,
                            ),
                            array('id' => $existing_forum_id),
                            array('%s', '%s', '%s', '%d', '%s', '%d', '%d'),
                            array('%d')
                        );
                    }
                    return; // Fórum existe e foi atualizado se necessário
                } else {
                    // Fórum não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_forum_id');
                }
            }
        }

        // Criar novo fórum usando a instância do AsgarosForum
        $forum_id = 0;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_forum')) {
                $forum_id = $asgarosforum->content->insert_forum(
                    $category_id, // category_id
                    $event_type_name,
                    'Discussões sobre o tipo de evento: ' . $event_type_name,
                    0, // parent_forum
                    'fas fa-comments', // icon
                    1, // order
                    'normal' // status
                );
            }
        }

        if ($forum_id) {
            update_post_meta($post_id, '_sevo_forum_forum_id', $forum_id);
        }
    }

    public function add_custom_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['organizacao'] = 'Organização';
                $new_columns['autor'] = 'Autor';
                $new_columns['max_vagas'] = 'Vagas';
                $new_columns['status'] = 'Status';
                $new_columns['tipo_participacao'] = 'Tipo de Participação';
            }
        }
        return $new_columns;
    }

    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'organizacao':
                $organizacao_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
                $organizacao = get_post($organizacao_id);
                echo $organizacao ? esc_html($organizacao->post_title) : '-';
                break;
            case 'autor':
                $autor_id = get_post_meta($post_id, '_sevo_tipo_evento_autor_id', true);
                $autor = get_user_by('id', $autor_id);
                echo $autor ? esc_html($autor->display_name) : '-';
                break;
            case 'max_vagas':
                $max_vagas = get_post_meta($post_id, '_sevo_tipo_evento_max_vagas', true);
                echo $max_vagas ? esc_html($max_vagas) : '0';
                break;
            case 'status':
                $status = get_post_meta($post_id, '_sevo_tipo_evento_status', true);
                echo esc_html(ucfirst($status ?: 'ativo'));
                break;
            case 'tipo_participacao':
                $tipo_participacao = get_post_meta($post_id, '_sevo_tipo_evento_participacao', true);
                echo $tipo_participacao ? esc_html(ucfirst($tipo_participacao)) : '-';
                break;
        }
    }
}