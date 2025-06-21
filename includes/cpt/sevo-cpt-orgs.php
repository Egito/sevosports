<?php
if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Orgs_CPT {
    private $post_type = 'sevo-orgs';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Adicionar colunas personalizadas
        add_filter('manage_' . $this->post_type . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'display_custom_columns'), 10, 2);
        
        // Enfileirar estilos específicos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        if (is_singular($this->post_type) || has_shortcode(get_post()->post_content, 'sevo_org')) {
            wp_enqueue_style('cpt-sevo-org-style');
        }
    }

    public function register_post_type() {
        $labels = array(
            'name'               => 'Organizações',
            'singular_name'      => 'Organização',
            'menu_name'          => 'Organizações',
            'add_new'           => 'Adicionar Nova',
            'add_new_item'      => 'Adicionar Nova Organização',
            'edit_item'         => 'Editar Organização',
            'new_item'          => 'Nova Organização',
            'view_item'         => 'Ver Organização',
            'search_items'      => 'Buscar Organizações',
            'not_found'         => 'Nenhuma organização encontrada',
            'not_found_in_trash'=> 'Nenhuma organização encontrada na lixeira'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'organizacoes'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'menu_icon'           => 'dashicons-groups'
        );

        register_post_type($this->post_type, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_org_details',
            'Detalhes da Organização',
            array($this, 'render_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('sevo_org_meta_box', 'sevo_org_meta_box_nonce');

        // Recuperar valor salvo
        $proprietario_id = get_post_meta($post->ID, '_sevo_org_proprietario_id', true);

        // Buscar usuários editores e administradores
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
                <th><label for="sevo_org_proprietario_id">Proprietário</label></th>
                <td>
                    <select id="sevo_org_proprietario_id" name="sevo_org_proprietario_id" required>
                        <option value="">Selecione um proprietário</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" 
                                    <?php selected($proprietario_id ? $proprietario_id : $current_user_id, $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?> 
                                (<?php echo esc_html(implode(', ', $user->roles)); ?>)
                            </option>
                        <?php endforeach; ?>
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
        if (!isset($_POST['sevo_org_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_org_meta_box_nonce'], 'sevo_org_meta_box')) {
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

        // Salvar o campo do proprietário
        if (isset($_POST['sevo_org_proprietario_id'])) {
            $proprietario_id = absint($_POST['sevo_org_proprietario_id']);
            update_post_meta($post_id, '_sevo_org_proprietario_id', $proprietario_id);
        }
    }

    public function add_custom_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['proprietario'] = 'Proprietário';
            }
        }
        return $new_columns;
    }

    public function display_custom_columns($column, $post_id) {
        switch ($column) {
            case 'proprietario':
                $proprietario_id = get_post_meta($post_id, '_sevo_org_proprietario_id', true);
                $proprietario = get_user_by('id', $proprietario_id);
                echo $proprietario ? esc_html($proprietario->display_name) : '-';
                break;
        }
    }
}
