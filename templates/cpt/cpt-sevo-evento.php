<?php
/**
 * CPT Sevo Evento
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Eventos_CPT {
    public function __construct() {
        add_action('init', array($this, 'register_cpt'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    public function register_cpt() {
        $labels = array(
            'name'                  => _x('Eventos', 'Post Type General Name', 'sevo-eventos'),
            'singular_name'         => _x('Evento', 'Post Type Singular Name', 'sevo-eventos'),
            'menu_name'             => __('Eventos', 'sevo-eventos'),
            'name_admin_bar'        => __('Evento', 'sevo-eventos'),
            'archives'              => __('Arquivos de eventos', 'sevo-eventos'),
            'attributes'            => __('Atributos do evento', 'sevo-eventos'),
            'parent_item_colon'     => __('Evento pai:', 'sevo-eventos'),
            'all_items'             => __('Todos os eventos', 'sevo-eventos'),
            'add_new_item'          => __('Adicionar novo evento', 'sevo-eventos'),
            'add_new'               => __('Novo evento', 'sevo-eventos'),
            'new_item'              => __('Novo evento', 'sevo-eventos'),
            'edit_item'             => __('Editar evento', 'sevo-eventos'),
            'update_item'           => __('Atualizar evento', 'sevo-eventos'),
            'view_item'             => __('Ver evento', 'sevo-eventos'),
            'view_items'            => __('Ver eventos', 'sevo-eventos'),
            'search_items'          => __('Buscar eventos', 'sevo-eventos'),
            'not_found'             => __('Não encontrado', 'sevo-eventos'),
            'not_found_in_trash'    => __('Não encontrado na lixeira', 'sevo-eventos'),
            'featured_image'        => __('Imagem destacada', 'sevo-eventos'),
            'set_featured_image'    => __('Definir imagem destacada', 'sevo-eventos'),
            'remove_featured_image' => __('Remover imagem destacada', 'sevo-eventos'),
            'use_featured_image'    => __('Usar como imagem destacada', 'sevo-eventos'),
            'insert_into_item'      => __('Inserir no evento', 'sevo-eventos'),
            'uploaded_to_this_item' => __('Enviado para este evento', 'sevo-eventos'),
            'items_list'            => __('Lista de eventos', 'sevo-eventos'),
            'items_list_navigation' => __('Navegação da lista de eventos', 'sevo-eventos'),
            'filter_items_list'     => __('Filtrar lista de eventos', 'sevo-eventos'),
        );

        $args = array(
            'label'                 => __('Evento', 'sevo-eventos'),
            'description'           => __('CPT para Eventos', 'sevo-eventos'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'taxonomies'            => array('sevo_org'),
            'hierarchical'          => false,
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
        register_post_type('sevo_evento', $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'sevo_evento_info',
            __('Informações do Evento', 'sevo-eventos'),
            array($this, 'render_evento_info_meta_box'),
            'sevo_evento',
            'normal',
            'high'
        );

        add_meta_box(
            'sevo_evento_config',
            __('Configurações do Evento', 'sevo-eventos'),
            array($this, 'render_evento_config_meta_box'),
            'sevo_evento',
            'side'
        );

        add_meta_box(
            'sevo_evento_secoes',
            __('Seções do Evento', 'sevo-eventos'),
            array($this, 'render_secoes_meta_box'),
            'sevo_evento',
            'normal',
            'high'
        );
    }

    public function render_secoes_meta_box($post) {
        $secoes = get_posts(array(
            'post_type' => 'sevo_secao',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $selected_secoes = get_post_meta($post->ID, 'sevo_evento_secoes', true);
        if (!is_array($selected_secoes)) {
            $selected_secoes = array();
        }
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label><?php _e('Selecione as seções:', 'sevo-eventos'); ?></label>
                <?php foreach ($secoes as $secao) : ?>
                    <div>
                        <input type="checkbox"
                               name="sevo_evento_secoes[]"
                               value="<?php echo $secao->ID; ?>"
                               <?php checked(in_array($secao->ID, $selected_secoes)); ?>>
                        <?php echo esc_html($secao->post_title); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public function render_evento_info_meta_box($post) {
        wp_nonce_field('sevo_evento_info_nonce', 'sevo_evento_info_nonce');
        
        $data_inicio = get_post_meta($post->ID, 'sevo_evento_data_inicio', true);
        $data_fim = get_post_meta($post->ID, 'sevo_evento_data_fim', true);
        $local = get_post_meta($post->ID, 'sevo_evento_local', true);
        $organizador = get_post_meta($post->ID, 'sevo_evento_organizador', true);
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_evento_data_inicio"><?php _e('Data de Início:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_evento_data_inicio" value="<?php echo esc_attr($data_inicio); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_evento_data_fim"><?php _e('Data de Término:', 'sevo-eventos'); ?></label>
                <input type="date" name="sevo_evento_data_fim" value="<?php echo esc_attr($data_fim); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_evento_local"><?php _e('Local:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_evento_local" value="<?php echo esc_attr($local); ?>">
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_evento_organizador"><?php _e('Organizador:', 'sevo-eventos'); ?></label>
                <input type="text" name="sevo_evento_organizador" value="<?php echo esc_attr($organizador); ?>">
            </div>
        </div>
        <?php
    }

    public function render_evento_config_meta_box($post) {
        $situacao = get_post_meta($post->ID, 'sevo_evento_situacao', true);
        $estilo = get_post_meta($post->ID, 'sevo_evento_estilo', true);
        $vagas = get_post_meta($post->ID, 'sevo_evento_vagas', true);
        ?>
        <div class="sevo-meta-box">
            <div class="sevo-meta-field">
                <label for="sevo_evento_situacao"><?php _e('Situação:', 'sevo-eventos'); ?></label>
                <select name="sevo_evento_situacao">
                    <option value="ativo" <?php selected($situacao, 'ativo'); ?>>Ativo</option>
                    <option value="inativo" <?php selected($situacao, 'inativo'); ?>>Inativo</option>
                </select>
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_evento_estilo"><?php _e('Estilo:', 'sevo-eventos'); ?></label>
                <select name="sevo_evento_estilo">
                    <option value="grupo" <?php selected($estilo, 'grupo'); ?>>Grupo</option>
                    <option value="individual" <?php selected($estilo, 'individual'); ?>>Individual</option>
                </select>
            </div>
            
            <div class="sevo-meta-field">
                <label for="sevo_evento_vagas"><?php _e('Vagas Máximas:', 'sevo-eventos'); ?></label>
                <input type="number" name="sevo_evento_vagas" value="<?php echo esc_attr($vagas); ?>" min="1">
            </div>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['sevo_evento_info_nonce']) || 
            !wp_verify_nonce($_POST['sevo_evento_info_nonce'], 'sevo_evento_info_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array(
            'sevo_evento_data_inicio',
            'sevo_evento_data_fim',
            'sevo_evento_local',
            'sevo_evento_organizador',
            'sevo_evento_situacao',
            'sevo_evento_estilo',
            'sevo_evento_vagas'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

new Sevo_Eventos_CPT();
