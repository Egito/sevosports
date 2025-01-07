<?php
/**
 * CPT Sevo Seção
 *
 * @package Sevo_Eventos
 */

defined('ABSPATH') || exit;

if (!function_exists('registrar_taxonomia_secao_categoria')) {

// Registrar taxonomia de categorias para seções
function registrar_taxonomia_secao_categoria() {
    $labels = array(
        'name'              => 'Categorias de Seção',
        'singular_name'     => 'Categoria de Seção',
        'search_items'      => 'Buscar Categorias',
        'all_items'         => 'Todas as Categorias',
        'parent_item'       => 'Categoria Pai',
        'parent_item_colon' => 'Categoria Pai:',
        'edit_item'         => 'Editar Categoria',
        'update_item'       => 'Atualizar Categoria',
        'add_new_item'      => 'Adicionar Nova Categoria',
        'new_item_name'     => 'Nova Categoria',
        'menu_name'         => 'Categorias'
    );

    $args = array(
        'hierarchical'      => true,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column'=> true,
        'query_var'        => true,
        'rewrite'          => array('slug' => 'secao-categoria'),
        'show_in_rest'     => true
    );

    register_taxonomy('secao-categoria', 'sevo-secoes', $args);

    // Adicionar termos padrão se ainda não existirem
    if (!term_exists('iniciante', 'secao-categoria')) {
        wp_insert_term('Iniciante', 'secao-categoria');
    }
    if (!term_exists('intermediario', 'secao-categoria')) {
        wp_insert_term('Intermediário', 'secao-categoria');
    }
    if (!term_exists('avancado', 'secao-categoria')) {
        wp_insert_term('Avançado', 'secao-categoria');
    }
    if (!term_exists('livre', 'secao-categoria')) {
        wp_insert_term('Livre', 'secao-categoria');
    }
}
add_action('init', 'registrar_taxonomia_secao_categoria', 0);

// Registro da taxonomia sevo_evento para sevo_secao
function registrar_taxonomia_sevo_evento_secao() {
    $labels = array(
        'name'                       => _x( 'Eventos', 'taxonomy general name', 'sevo-eventos' ),
        'singular_name'              => _x( 'Evento', 'taxonomy singular name', 'sevo-eventos' ),
        'search_items'               => __( 'Buscar eventos', 'sevo-eventos' ),
        'all_items'                  => __( 'Todos os eventos', 'sevo-eventos' ),
        'parent_item'                => __( 'Evento pai', 'sevo-eventos' ),
        'parent_item_colon'          => __( 'Evento pai:', 'sevo-eventos' ),
        'edit_item'                  => __( 'Editar evento', 'sevo-eventos' ),
        'update_item'                => __( 'Atualizar evento', 'sevo-eventos' ),
        'add_new_item'               => __( 'Adicionar novo evento', 'sevo-eventos' ),
        'new_item_name'              => __( 'Nome do novo evento', 'sevo-eventos' ),
        'menu_name'                  => __( 'Eventos', 'sevo-eventos' ),
    );
    $args = array(
        'hierarchical'          => true, // Define como hierárquica (como categorias)
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'evento' ), // Define o slug
    );
    register_taxonomy( 'sevo_evento', array( 'sevo_secao' ), $args );
}
add_action( 'init', 'registrar_taxonomia_sevo_evento_secao', 0 );


// Registro do CPT sevo_secao
function registrar_sevo_secao_cpt() {
    $labels = array(
        'name'                  => _x( 'Seções', 'Post Type General Name', 'sevo-eventos' ),
        'singular_name'         => _x( 'Seção', 'Post Type Singular Name', 'sevo-eventos' ),
        'menu_name'             => __( 'Seções', 'sevo-eventos' ),
        'name_admin_bar'        => __( 'Seção', 'sevo-eventos' ),
        'archives'              => __( 'Arquivos de seções', 'sevo-eventos' ),
        'attributes'            => __( 'Atributos da seção', 'sevo-eventos' ),
        'parent_item_colon'     => __( 'Seção pai:', 'sevo-eventos' ),
        'all_items'             => __( 'Todas as seções', 'sevo-eventos' ),
        'add_new_item'          => __( 'Adicionar nova seção', 'sevo-eventos' ),
        'add_new'               => __( 'Nova seção', 'sevo-eventos' ),
        'new_item'              => __( 'Nova seção', 'sevo-eventos' ),
        'edit_item'             => __( 'Editar seção', 'sevo-eventos' ),
        'update_item'           => __( 'Atualizar seção', 'sevo-eventos' ),
        'view_item'             => __( 'Ver seção', 'sevo-eventos' ),
        'view_items'            => __( 'Ver seções', 'sevo-eventos' ),
        'search_items'          => __( 'Buscar seções', 'sevo-eventos' ),
        'not_found'             => __( 'Não encontrado', 'sevo-eventos' ),
        'not_found_in_trash'    => __( 'Não encontrado na lixeira', 'sevo-eventos' ),
        'featured_image'        => __( 'Imagem destacada', 'sevo-eventos' ),
        'set_featured_image'    => __( 'Definir imagem destacada', 'sevo-eventos' ),
        'remove_featured_image' => __( 'Remover imagem destacada', 'sevo-eventos' ),
        'use_featured_image'    => __( 'Usar como imagem destacada', 'sevo-eventos' ),
        'insert_into_item'      => __( 'Inserir na seção', 'sevo-eventos' ),
        'uploaded_to_this_item' => __( 'Enviado para esta seção', 'sevo-eventos' ),
        'items_list'            => __( 'Lista de seções', 'sevo-eventos' ),
        'items_list_navigation' => __( 'Navegação da lista de seções', 'sevo-eventos' ),
        'filter_items_list'     => __( 'Filtrar lista de seções', 'sevo-eventos' ),
    );
    $args = array(
        'label'                 => __( 'Seção', 'sevo-eventos' ),
        'description'           => __( 'CPT para Seções de Evento', 'sevo-eventos' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
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
    register_post_type( 'sevo_secao', $args );
}
add_action( 'init', 'registrar_sevo_secao_cpt', 0 );


// Adicionando meta boxes para sevo_secao
function adicionar_meta_boxes_sevo_secao() {
    add_meta_box( 'sevo_secao_datas', 'Datas', 'renderizar_meta_box_datas', 'sevo_secao', 'normal' );
    add_meta_box( 'sevo_secao_vagas', 'Vagas', 'renderizar_meta_box_vagas', 'sevo_secao', 'side' );
    add_meta_box( 'sevo_secao_situacao', 'Situação', 'renderizar_meta_box_situacao', 'sevo_secao', 'side' );
    add_meta_box( 'sevo_secao_categoria', 'Categoria', 'renderizar_meta_box_categoria', 'sevo_secao', 'side' );
}
add_action( 'add_meta_boxes', 'adicionar_meta_boxes_sevo_secao' );

// Funções para renderizar os meta boxes
function renderizar_meta_box_datas($post) {
    wp_nonce_field('sevo_secao_meta_box', 'sevo_secao_meta_box_nonce');
    
    // Recuperar valores existentes
    $data_inicio_insc = get_post_meta($post->ID, '_sevo_secao_data_inicio_inscricoes', true);
    $data_fim_insc = get_post_meta($post->ID, '_sevo_secao_data_fim_inscricoes', true);
    $data_inicio_evento = get_post_meta($post->ID, '_sevo_secao_data_inicio_evento', true);
    $data_fim_evento = get_post_meta($post->ID, '_sevo_secao_data_fim_evento', true);
    
    // Exibir campos
    echo '<div class="sevo-meta-box">';
    echo '<label for="sevo_secao_data_inicio_inscricoes">Data Início Inscrições:</label>';
    echo '<input type="date" id="sevo_secao_data_inicio_inscricoes" name="sevo_secao_data_inicio_inscricoes" value="'.esc_attr($data_inicio_insc).'">';
    
    echo '<label for="sevo_secao_data_fim_inscricoes">Data Fim Inscrições:</label>';
    echo '<input type="date" id="sevo_secao_data_fim_inscricoes" name="sevo_secao_data_fim_inscricoes" value="'.esc_attr($data_fim_insc).'">';
    
    echo '<label for="sevo_secao_data_inicio_evento">Data Início Evento:</label>';
    echo '<input type="date" id="sevo_secao_data_inicio_evento" name="sevo_secao_data_inicio_evento" value="'.esc_attr($data_inicio_evento).'">';
    
    echo '<label for="sevo_secao_data_fim_evento">Data Fim Evento:</label>';
    echo '<input type="date" id="sevo_secao_data_fim_evento" name="sevo_secao_data_fim_evento" value="'.esc_attr($data_fim_evento).'">';
    echo '</div>';
}

function renderizar_meta_box_vagas($post) {
    $vagas = get_post_meta($post->ID, '_sevo_secao_vagas', true);
    echo '<input type="number" min="0" id="sevo_secao_vagas" name="sevo_secao_vagas" value="'.esc_attr($vagas).'">';
}

function renderizar_meta_box_situacao($post) {
    $situacao = get_post_meta($post->ID, '_sevo_secao_situacao', true);
    echo '<select id="sevo_secao_situacao" name="sevo_secao_situacao">';
    echo '<option value="aberto" '.selected($situacao, 'aberto', false).'>Aberto</option>';
    echo '<option value="fechado" '.selected($situacao, 'fechado', false).'>Fechado</option>';
    echo '<option value="cancelado" '.selected($situacao, 'cancelado', false).'>Cancelado</option>';
    echo '</select>';
}

function renderizar_meta_box_categoria($post) {
    $categoria = get_post_meta($post->ID, '_sevo_secao_categoria', true);
    echo '<select id="sevo_secao_categoria" name="sevo_secao_categoria">';
    echo '<option value="iniciante" '.selected($categoria, 'iniciante', false).'>Iniciante</option>';
    echo '<option value="intermediario" '.selected($categoria, 'intermediario', false).'>Intermediário</option>';
    echo '<option value="avancado" '.selected($categoria, 'avancado', false).'>Avançado</option>';
    echo '<option value="livre" '.selected($categoria, 'livre', false).'>Livre</option>';
    echo '</select>';
}


// Função para salvar os dados dos meta boxes de sevo_secao
function salvar_meta_boxes_sevo_secao($post_id) {
    // Verificar se é um autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verificar o nonce
    if (!isset($_POST['sevo_secao_meta_box_nonce']) || !wp_verify_nonce($_POST['sevo_secao_meta_box_nonce'], 'sevo_secao_meta_box')) {
        return;
    }

    // Verificar as permissões
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Verificar se é o tipo de post correto
    if (get_post_type($post_id) !== 'sevo_secao') {
        return;
    }

    // Validar e salvar o evento
    if (isset($_POST['sevo_secao_evento_id'])) {
        $evento_id = absint($_POST['sevo_secao_evento_id']);
        update_post_meta($post_id, '_sevo_secao_evento_id', $evento_id);
    }

    // Validar e salvar as datas
    $datas = array(
        '_sevo_secao_data_inicio_inscricoes' => 'sevo_secao_data_inicio_inscricoes',
        '_sevo_secao_data_fim_inscricoes' => 'sevo_secao_data_fim_inscricoes',
        '_sevo_secao_data_inicio_evento' => 'sevo_secao_data_inicio_evento',
        '_sevo_secao_data_fim_evento' => 'sevo_secao_data_fim_evento'
    );

    foreach ($datas as $meta_key => $field_name) {
        if (isset($_POST[$field_name])) {
            $date = sanitize_text_field($_POST[$field_name]);
            if (validate_date($date)) {
                update_post_meta($post_id, $meta_key, $date);
            }
        }
    }

    // Validar e salvar as vagas
    if (isset($_POST['sevo_secao_vagas'])) {
        $vagas = absint($_POST['sevo_secao_vagas']);
        $evento_id = get_post_meta($post_id, '_sevo_secao_evento_id', true);
        
        if ($evento_id) {
            $max_vagas_evento = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
            
            // Garantir que o número de vagas não exceda o máximo do evento
            if ($vagas > $max_vagas_evento) {
                $vagas = $max_vagas_evento;
            }
            
            // Permitir 0 vagas
            if ($vagas < 0) {
                $vagas = 0;
            }
            
            update_post_meta($post_id, '_sevo_secao_vagas', $vagas);
        }
    }

    // Função AJAX para obter o número máximo de vagas
    add_action('wp_ajax_obter_max_vagas_evento', 'obter_max_vagas_evento');
    function obter_max_vagas_evento() {
        check_ajax_referer('sevo_secao_nonce', 'security');
        
        if (!isset($_POST['evento_id'])) {
            wp_send_json_error('Evento ID não informado');
        }
        
        $evento_id = absint($_POST['evento_id']);
        $max_vagas = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
        
        if (!$max_vagas) {
            wp_send_json_error('Número máximo de vagas não encontrado');
        }
        
        wp_send_json_success(array(
            'max_vagas' => $max_vagas
        ));
    }
}

function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

add_action('save_post_sevo_secao', 'salvar_meta_boxes_sevo_secao');

?>
