<?php
/**
 * CPT Sevo Seção
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Secoes_CPT {
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    public function register_cpt() {
        $labels = array(
            'name'                  => _x('Seções', 'Post Type General Name', 'sevo-eventos'),
            'singular_name'         => _x('Seção', 'Post Type Singular Name', 'sevo-eventos'),
            'menu_name'             => __('Seções', 'sevo-eventos'),
            'name_admin_bar'        => __('Seção', 'sevo-eventos'),
            'archives'              => __('Arquivos de seções', 'sevo-eventos'),
            'attributes'            => __('Atributos da seção', 'sevo-eventos'),
            'parent_item_colon'     => __('Seção pai:', 'sevo-eventos'),
            'all_items'             => __('Todas as seções', 'sevo-eventos'),
            'add_new_item'          => __('Adicionar nova seção', 'sevo-eventos'),
            'add_new'               => __('Nova seção', 'sevo-eventos'),
            'new_item'              => __('Nova seção', 'sevo-eventos'),
            'edit_item'             => __('Editar seção', 'sevo-eventos'),
            'update_item'           => __('Atualizar seção', 'sevo-eventos'),
            'view_item'             => __('Ver seção', 'sevo-eventos'),
            'view_items'            => __('Ver seções', 'sevo-eventos'),
            'search_items'          => __('Buscar seções', 'sevo-eventos'),
            'not_found'             => __('Não encontrado', 'sevo-eventos'),
            'not_found_in_trash'    => __('Não encontrado na lixeira', 'sevo-eventos'),
            'featured_image'        => __('Imagem destacada', 'sevo-eventos'),
            'set_featured_image'    => __('Definir imagem destacada', 'sevo-eventos'),
            'remove_featured_image' => __('Remover imagem destacada', 'sevo-eventos'),
            'use_featured_image'    => __('Usar como imagem destacada', 'sevo-eventos'),
            'insert_into_item'      => __('Inserir na seção', 'sevo-eventos'),
            'uploaded_to_this_item' => __('Enviado para esta seção', 'sevo-eventos'),
            'items_list'            => __('Lista de seções', 'sevo-eventos'),
            'items_list_navigation' => __('Navegação da lista de seções', 'sevo-eventos'),
            'filter_items_list'     => __('Filtrar lista de seções', 'sevo-eventos'),
        );

        $args = array(
            'label'                 => __('Seção', 'sevo-eventos'),
            'description'           => __('CPT para Seções de Evento', 'sevo-eventos'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
        );
        register_post_type('sevo_secao', $args);
    }

    public function register_taxonomy() {
        // Taxonomia para categorias de seção
        $labels = array(
            'name'                       => _x('Categorias de Seção', 'taxonomy general name', 'sevo-eventos'),
            'singular_name'              => _x('Categoria de Seção', 'taxonomy singular name', 'sevo-eventos'),
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
            'rewrite'               => array('slug' => 'secao-categoria'),
        );
        register_taxonomy('sevo_secao_categoria', array('sevo_secao'), $args);

        // Taxonomia para tipos de seção
        $labels = array(
            'name'                       => _x('Tipos de Seção', 'taxonomy general name', 'sevo-eventos'),
            'singular_name'              => _x('Tipo de Seção', 'taxonomy singular name', 'sevo-eventos'),
            'search_items'               => __('Buscar tipos', 'sevo-eventos'),
            'all_items'                  => __('Todos os tipos', 'sevo-eventos'),
            'parent_item'                => __('Tipo pai', 'sevo-eventos'),
            'parent_item_colon'          => __('Tipo pai:', 'sevo-eventos'),
            'edit_item'                  => __('Editar tipo', 'sevo-eventos'),
            'update_item'                => __('Atualizar tipo', 'sevo-eventos'),
            'add_new_item'               => __('Adicionar novo tipo', 'sevo-eventos'),
            'new_item_name'              => __('Nome do novo tipo', 'sevo-eventos'),
            'menu_name'                  => __('Tipos', 'sevo-eventos'),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'secao-tipo'),
        );
        register_taxonomy('secao-categoria', array('sevo_secao'), $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_secao_info',
            __('Informações da Seção', 'sevo-eventos'),
            array($this, 'render_secao_info_meta_box'),
            'sevo_secao',
            'normal',
            'high'
        );

        add_meta_box(
            'sevo_secao_config',
            __('Configurações da Seção', 'sevo-eventos'),
            array($this, 'render_secao_config_meta_box'),
            'sevo_secao',
            'side'
        );

        add_meta_box(
            'sevo_secao_inscricoes',
            __('Inscrições da Seção', 'sevo-eventos'),
            array($this, 'render_inscricoes_meta_box'),
            'sevo_secao',
            'normal',
            'high'
        );
    }

    public function render_inscricoes_meta_box($post) {
        $inscricoes = get_posts(array(
            'post_type' => 'sevo_inscricao',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $selected_inscricoes = get_post_meta($post->ID, 'sevo_secao_inscricoes', true);
        if (!is_array($selected_inscricoes)) {
            $selected_inscricoes = array();
        }
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label><?php _e('Selecione as inscrições:', 'sevo-eventos'); ?></label>
                <?php foreach ($inscricoes as $inscricao) : ?>
                    <div>
                        <input type="checkbox"
                               name="sevo_secao_inscricoes[]"
                               value="<?php echo $inscricao->ID; ?>"
                               <?php checked(in_array($inscricao->ID, $selected_inscricoes)); ?>>
                        <?php echo esc_html($inscricao->post_title); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_secao_info_meta_box($post) {
        wp_nonce_field('sevo_secao_info_nonce', 'sevo_secao_info_nonce');
        
        $data_inicio_insc = get_post_meta($post->ID, 'sevo_secao_data_inicio_inscricoes', true);
        $data_fim_insc = get_post_meta($post->ID, 'sevo_secao_data_fim_inscricoes', true);
        $data_inicio = get_post_meta($post->ID, 'sevo_secao_data_inicio', true);
        $data_fim = get_post_meta($post->ID, 'sevo_secao_data_fim', true);
        $evento_id = get_post_meta($post->ID, 'sevo_secao_evento_id', true);
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_secao_evento_id"><?php _e('Evento Relacionado:', 'sevo-eventos'); ?></label>
                <?php
                wp_dropdown_pages(array(
                    'post_type' => 'sevo_evento',
                    'selected' => $evento_id,
                    'name' => 'sevo_secao_evento_id',
                    'show_option_none' => __('Selecione um evento', 'sevo-eventos'),
                    'sort_column' => 'post_title'
                ));
                ?>
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_data_inicio_inscricoes"><?php _e('Início das Inscrições:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_secao_data_inicio_inscricoes" value="<?php echo esc_attr($data_inicio_insc); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_data_fim_inscricoes"><?php _e('Fim das Inscrições:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_secao_data_fim_inscricoes" value="<?php echo esc_attr($data_fim_insc); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_data_inicio"><?php _e('Início da Seção:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_secao_data_inicio" value="<?php echo esc_attr($data_inicio); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_data_fim"><?php _e('Fim da Seção:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_secao_data_fim" value="<?php echo esc_attr($data_fim); ?>">
            </div>
        </div>
        <?php
    }

    public function render_secao_config_meta_box($post) {
        $vagas = get_post_meta($post->ID, 'sevo_secao_vagas', true);
        $situacao = get_post_meta($post->ID, 'sevo_secao_situacao', true);
        $categoria = get_post_meta($post->ID, 'sevo_secao_categoria', true);
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_secao_vagas"><?php _e('Vagas:', 'sevo-eventos'); ?></label>
                <input type="number" name="sevo_secao_vagas" value="<?php echo esc_attr($vagas); ?>" min="1">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_situacao"><?php _e('Situação:', 'sevo-eventos'); ?></label>
                <select name="sevo_secao_situacao">
                    <option value="ativo" <?php selected($situacao, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($situacao, 'inativo'); ?>>Inativo</option>
                </select>
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_secao_categoria"><?php _e('Categoria:', 'sevo-eventos'); ?></label>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'sevo_secao_categoria',
                    'selected' => $categoria,
                    'name' => 'sevo_secao_categoria',
                    'show_option_none' => __('Selecione uma categoria', 'sevo-eventos')
                ));
                ?>
            </div>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['sevo_secao_info_nonce']) || 
            !wp_verify_nonce($_POST['sevo_secao_info_nonce'], 'sevo_secao_info_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array(
            'sevo_secao_evento_id',
            'sevo_secao_data_inicio_inscricoes',
            'sevo_secao_data_fim_inscricoes',
            'sevo_secao_data_inicio',
            'sevo_secao_data_fim',
            'sevo_secao_vagas',
            'sevo_secao_situacao',
            'sevo_secao_categoria'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

new Sevo_Secoes_CPT();
