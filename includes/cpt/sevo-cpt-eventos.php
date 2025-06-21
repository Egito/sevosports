<?php
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_CPT {
    private $post_type = 'sevo-eventos';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Adicionar colunas personalizadas
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Eventos',
            'singular_name'      => 'Evento',
            'menu_name'          => 'Eventos',
            'add_new'           => 'Adicionar Novo',
            'add_new_item'      => 'Adicionar Novo Evento',
            'edit_item'         => 'Editar Evento',
            'new_item'          => 'Novo Evento',
            'view_item'         => 'Ver Evento',
            'search_items'      => 'Buscar Eventos',
            'not_found'         => 'Nenhum evento encontrado',
            'not_found_in_trash'=> 'Nenhum evento encontrado na lixeira'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'eventos'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'menu_icon'           => 'dashicons-calendar-alt'
        );

        register_post_type($this->post_type, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_evento_details',
            'Detalhes do Evento',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('sevo_evento_meta_box', 'sevo_evento_meta_box_nonce');

        // Recuperar valores salvos
        $organizacao_id = get_post_meta($post->ID, '_sevo_evento_organizacao_id', true);
        $autor_id = get_post_meta($post->ID, '_sevo_evento_autor_id', true);
        $max_vagas = get_post_meta($post->ID, '_sevo_evento_max_vagas', true);
        $status = get_post_meta($post->ID, '_sevo_evento_status', true);
        $tipo_participacao = get_post_meta($post->ID, '_tipo_participacao', true);

        // Valores padrão
        $status = $status ?: 'ativo';

        // Buscar organizações
        $organizacoes = get_posts(array(
            'post_type' => 'sevo-orgs',
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
                <th><label for="sevo_evento_organizacao_id">Organização</label></th>
                <td>
                    <select id="sevo_evento_organizacao_id" name="sevo_evento_organizacao_id" required>
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
                <th><label for="sevo_evento_autor_id">Autor</label></th>
                <td>
                    <select id="sevo_evento_autor_id" name="sevo_evento_autor_id" required>
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
                <th><label for="sevo_evento_max_vagas">Número Máximo de Vagas</label></th>
                <td>
                    <input type="number" id="sevo_evento_max_vagas" name="sevo_evento_max_vagas" 
                           value="<?php echo esc_attr($max_vagas); ?>" min="1" step="1" required>
                    <p class="description">Defina o número máximo de vagas disponíveis para este evento</p>
                </td>
            </tr>
            <tr>
                <th><label for="sevo_evento_status">Status</label></th>
                <td>
                    <select id="sevo_evento_status" name="sevo_evento_status" required>
                        <option value="ativo" <?php selected($status, 'ativo'); ?>>Ativo</option>
                        <option value="inativo" <?php selected($status, 'inativo'); ?>>Inativo</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="tipo_participacao">Tipo de Participação</label></th>
                <td>
                    <select id="tipo_participacao" name="tipo_participacao" required>
                        <option value="individual" <?php selected($tipo_participacao, 'individual'); ?>>Individual</option>
                        <option value="grupo" <?php selected($tipo_participacao, 'grupo'); ?>>Grupo</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_post_meta($post_id) {
        // Verificar se é um autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar o nonce
        if (!isset($_POST['sevo_evento_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_evento_meta_box_nonce'], 'sevo_evento_meta_box')) {
            return;
        }

        // Verificar as permissões
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verificar se é o tipo de post correto
        if (get_post_type($post_id) !== $this->post_type) {
            return;
        }

        // Salvar os campos
        if (isset($_POST['sevo_evento_organizacao_id'])) {
            $organizacao_id = absint($_POST['sevo_evento_organizacao_id']);
            update_post_meta($post_id, '_sevo_evento_organizacao_id', $organizacao_id);
        }

        if (isset($_POST['sevo_evento_autor_id'])) {
            $autor_id = absint($_POST['sevo_evento_autor_id']);
            update_post_meta($post_id, '_sevo_evento_autor_id', $autor_id);
        }

        if (isset($_POST['sevo_evento_max_vagas'])) {
            $max_vagas = absint($_POST['sevo_evento_max_vagas']);
            if ($max_vagas < 1) {
                $max_vagas = 1;
            }
            update_post_meta($post_id, '_sevo_evento_max_vagas', $max_vagas);
        }

        // Salvar status
        if (isset($_POST['sevo_evento_status'])) {
            $status = sanitize_text_field($_POST['sevo_evento_status']);
            if (in_array($status, array('ativo', 'inativo'))) {
                update_post_meta($post_id, '_sevo_evento_status', $status);
            }
        }

        // Salvar tipo de participação
        if (isset($_POST['tipo_participacao'])) {
            $tipo_participacao = sanitize_text_field($_POST['tipo_participacao']);
            if (in_array($tipo_participacao, array('individual', 'grupo'))) {
                update_post_meta($post_id, '_tipo_participacao', $tipo_participacao);
            }
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
                $organizacao_id = get_post_meta($post_id, '_sevo_evento_organizacao_id', true);
                $organizacao = get_post($organizacao_id);
                echo $organizacao ? esc_html($organizacao->post_title) : '-';
                break;
            case 'autor':
                $autor_id = get_post_meta($post_id, '_sevo_evento_autor_id', true);
                $autor = get_user_by('id', $autor_id);
                echo $autor ? esc_html($autor->display_name) : '-';
                break;
            case 'max_vagas':
                $max_vagas = get_post_meta($post_id, '_sevo_evento_max_vagas', true);
                echo $max_vagas ? esc_html($max_vagas) : '0';
                break;
            case 'status':
                $status = get_post_meta($post_id, '_sevo_evento_status', true);
                echo esc_html(ucfirst($status ?: 'ativo'));
                break;
            case 'tipo_participacao':
                $tipo_participacao = get_post_meta($post_id, '_tipo_participacao', true);
                echo $tipo_participacao ? esc_html(ucfirst($tipo_participacao)) : '-';
                break;
        }
    }
}
