<?php
/**
 * CPT Sevo Organização
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Orgs_CPT {
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    private $post_type = SEVO_ORG_POST_TYPE;

    public function register_cpt() {
        $labels = array(
            'name'                  => _x('Organizações', 'Post Type General Name', 'sevo-eventos'),
            'singular_name'         => _x('Organização', 'Post Type Singular Name', 'sevo-eventos'),
            'menu_name'             => __('Organizações', 'sevo-eventos'),
            'name_admin_bar'        => __('Organização', 'sevo-eventos'),
            'archives'              => __('Arquivos de organizações', 'sevo-eventos'),
            'attributes'            => __('Atributos da organização', 'sevo-eventos'),
            'parent_item_colon'     => __('Organização pai:', 'sevo-eventos'),
            'all_items'             => __('Todas as organizações', 'sevo-eventos'),
            'add_new_item'          => __('Adicionar nova organização', 'sevo-eventos'),
            'add_new'               => __('Nova organização', 'sevo-eventos'),
            'new_item'              => __('Nova organização', 'sevo-eventos'),
            'edit_item'             => __('Editar organização', 'sevo-eventos'),
            'update_item'           => __('Atualizar organização', 'sevo-eventos'),
            'view_item'             => __('Ver organização', 'sevo-eventos'),
            'view_items'            => __('Ver organizações', 'sevo-eventos'),
            'search_items'          => __('Buscar organizações', 'sevo-eventos'),
            'not_found'             => __('Não encontrado', 'sevo-eventos'),
            'not_found_in_trash'    => __('Não encontrado na lixeira', 'sevo-eventos'),
            'featured_image'        => __('Imagem destacada', 'sevo-eventos'),
            'set_featured_image'    => __('Definir imagem destacada', 'sevo-eventos'),
            'remove_featured_image' => __('Remover imagem destacada', 'sevo-eventos'),
            'use_featured_image'    => __('Usar como imagem destacada', 'sevo-eventos'),
            'insert_into_item'      => __('Inserir na organização', 'sevo-eventos'),
            'uploaded_to_this_item' => __('Enviado para esta organização', 'sevo-eventos'),
            'items_list'            => __('Lista de organizações', 'sevo-eventos'),
            'items_list_navigation' => __('Navegação da lista de organizações', 'sevo-eventos'),
            'filter_items_list'     => __('Filtrar lista de organizações', 'sevo-eventos'),
        );

        $args = array(
            'label'                 => __('Organização', 'sevo-eventos'),
            'description'           => __('CPT para Organizações', 'sevo-eventos'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'sevo-eventos',
            'menu_icon'             => 'dashicons-building',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'map_meta_cap'        => true,
        );
        register_post_type($this->post_type, $args);
    }

    public function register_taxonomy() {
        $labels = array(
            'name'                       => _x('Categorias de Organização', 'taxonomy general name', 'sevo-eventos'),
            'singular_name'              => _x('Categoria de Organização', 'taxonomy singular name', 'sevo-eventos'),
            'search_items'               => __('Buscar categorias', 'sevo-eventos'),
            'all_items'                  => __('Todas as categorias', 'sevo-eventos'),
            'parent_item'                => __('Categoria pai', 'sevo-eventos'),
            'parent_item_colon'          => __('Categoria pai:', 'sevo-eventos'),
            'edit_item'                  => __('Editar categoria', 'sevo-eventos'),
            'update_item'                => __('Atualizar categoria', 'sevo-eventos'),
            'add_new_item'               => __('Adicionar nova categoria', 'sevo-eventos'),
            'new_item_name'              => __('Nome da nova categoria', 'sevo-eventos'),
            'menu_name'                  => __('Categorias', 'sevo-eventos'),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'organizacao-categoria'),
        );
        register_taxonomy('sevo_org_categoria', array($this->post_type), $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_org_info',
            __('Informações da Organização', 'sevo-eventos'),
            array($this, 'render_org_info_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'sevo_org_contato',
            __('Informações de Contato', 'sevo-eventos'),
            array($this, 'render_contato_meta_box'),
            $this->post_type,
            'side'
        );

        add_meta_box(
            'sevo_org_eventos',
            __('Eventos Relacionados', 'sevo-eventos'),
            array($this, 'render_eventos_meta_box'),
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function render_eventos_meta_box($post) {
        $eventos = get_posts(array(
            'post_type' => 'sevo_evento',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $selected_eventos = get_post_meta($post->ID, 'sevo_org_eventos', true);
        if (!is_array($selected_eventos)) {
            $selected_eventos = array();
        }
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label><?php _e('Selecione os eventos:', 'sevo-eventos'); ?></label>
                <?php foreach ($eventos as $evento) : ?>
                    <div>
                        <input type="checkbox"
                               name="sevo_org_eventos[]"
                               value="<?php echo $evento->ID; ?>"
                               <?php checked(in_array($evento->ID, $selected_eventos)); ?>>
                        <?php echo esc_html($evento->post_title); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_org_info_meta_box($post) {
        wp_nonce_field('sevo_org_info_nonce', 'sevo_org_info_nonce');
        
        $cnpj = get_post_meta($post->ID, 'sevo_org_cnpj', true);
        $endereco = get_post_meta($post->ID, 'sevo_org_endereco', true);
        $cidade = get_post_meta($post->ID, 'sevo_org_cidade', true);
        $estado = get_post_meta($post->ID, 'sevo_org_estado', true);
        $cep = get_post_meta($post->ID, 'sevo_org_cep', true);
        $status = get_post_meta($post->ID, 'sevo_org_status', true);
        if (empty($status)) {
            $status = 'ativo'; // Valor padrão
        }
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_org_cnpj"><?php _e('CNPJ:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_cnpj" value="<?php echo esc_attr($cnpj); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_endereco"><?php _e('Endereço:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_endereco" value="<?php echo esc_attr($endereco); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_cidade"><?php _e('Cidade:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_cidade" value="<?php echo esc_attr($cidade); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_estado"><?php _e('Estado:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_estado" value="<?php echo esc_attr($estado); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_cep"><?php _e('CEP:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_cep" value="<?php echo esc_attr($cep); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                <select name="sevo_org_status" id="sevo_org_status">
                    <option value="ativo" <?php selected($status, 'ativo'); ?>><?php _e('Ativo', 'sevo-eventos'); ?></option>
                    <option value="inativo" <?php selected($status, 'inativo'); ?>><?php _e('Inativo', 'sevo-eventos'); ?></option>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_contato_meta_box($post) {
        $telefone = get_post_meta($post->ID, 'sevo_org_telefone', true);
        $email = get_post_meta($post->ID, 'sevo_org_email', true);
        $site = get_post_meta($post->ID, 'sevo_org_site', true);
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_org_telefone"><?php _e('Telefone:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_org_telefone" value="<?php echo esc_attr($telefone); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_email"><?php _e('Email:', 'sevo-eventos'); ?></label>
                <input type="email" name="sevo_org_email" value="<?php echo esc_attr($email); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_org_site"><?php _e('Site:', 'sevo-eventos'); ?></label>
                <input type="url" name="sevo_org_site" value="<?php echo esc_attr($site); ?>">
            </div>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['sevo_org_info_nonce']) || 
            !wp_verify_nonce($_POST['sevo_org_info_nonce'], 'sevo_org_info_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array(
            'sevo_org_cnpj',
            'sevo_org_endereco',
            'sevo_org_cidade',
            'sevo_org_estado',
            'sevo_org_cep',
            'sevo_org_telefone',
            'sevo_org_email',
            'sevo_org_site',
            'sevo_org_status'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Integração com fórum - criar/atualizar categoria para a organização
        $this->handle_forum_integration($post_id);
    }

    /**
     * Gerencia a integração com o fórum para organizações.
     */
    private function handle_forum_integration($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }

        // Criar/atualizar categoria de fórum para esta organização
        $this->create_or_update_organization_forum_category($post_id, $post);
    }

    /**
     * Cria ou atualiza categoria de fórum para a organização.
     */
    private function create_or_update_organization_forum_category($post_id, $post) {
        if (!class_exists('AsgarosForum')) {
            return;
        }

        global $asgarosforum;
        $existing_category_id = get_post_meta($post_id, '_sevo_forum_category_id', true);
        $organization_name = $post->post_title;
        
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
                    delete_post_meta($post_id, '_sevo_forum_category_id');
                }
            }
        }

        // Verificar se já existe uma categoria com este nome (evitar duplicatas)
        $existing_term = get_term_by('name', $organization_name, 'asgarosforum-category');
        if ($existing_term) {
            update_post_meta($post_id, '_sevo_forum_category_id', $existing_term->term_id);
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
            update_post_meta($post_id, '_sevo_forum_category_id', $category_id['term_id']);
        }
    }
}
