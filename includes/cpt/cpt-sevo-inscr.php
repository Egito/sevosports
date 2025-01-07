<?php
// Registro do CPT sevo_inscr
function registrar_sevo_inscr_cpt() {
    $labels = array(
        'name'                  => _x( 'Inscrições', 'Post Type General Name', 'sevo-eventos' ),
        'singular_name'         => _x( 'Inscrição', 'Post Type Singular Name', 'sevo-eventos' ),
        'menu_name'             => __( 'Inscrições', 'sevo-eventos' ),
        'name_admin_bar'        => __( 'Inscrição', 'sevo-eventos' ),
        'archives'              => __( 'Arquivos de inscrições', 'sevo-eventos' ),
        'attributes'            => __( 'Atributos da inscrição', 'sevo-eventos' ),
        'parent_item_colon'     => __( 'Inscrição pai:', 'sevo-eventos' ),
        'all_items'             => __( 'Todas as inscrições', 'sevo-eventos' ),
        'add_new_item'          => __( 'Adicionar nova inscrição', 'sevo-eventos' ),
        'add_new'               => __( 'Nova inscrição', 'sevo-eventos' ),
        'new_item'              => __( 'Nova inscrição', 'sevo-eventos' ),
        'edit_item'             => __( 'Editar inscrição', 'sevo-eventos' ),
        'update_item'           => __( 'Atualizar inscrição', 'sevo-eventos' ),
        'view_item'             => __( 'Ver inscrição', 'sevo-eventos' ),
        'view_items'            => __( 'Ver inscrições', 'sevo-eventos' ),
        'search_items'          => __( 'Buscar inscrições', 'sevo-eventos' ),
        'not_found'             => __( 'Não encontrado', 'sevo-eventos' ),
        'not_found_in_trash'    => __( 'Não encontrado na lixeira', 'sevo-eventos' ),
        'featured_image'        => __( 'Imagem destacada', 'sevo-eventos' ),
        'set_featured_image'    => __( 'Definir imagem destacada', 'sevo-eventos' ),
        'remove_featured_image' => __( 'Remover imagem destacada', 'sevo-eventos' ),
        'use_featured_image'    => __( 'Usar como imagem destacada', 'sevo-eventos' ),
        'insert_into_item'      => __( 'Inserir na inscrição', 'sevo-eventos' ),
        'uploaded_to_this_item' => __( 'Enviado para esta inscrição', 'sevo-eventos' ),
        'items_list'            => __( 'Lista de inscrições', 'sevo-eventos' ),
        'items_list_navigation' => __( 'Navegação da lista de inscrições', 'sevo-eventos' ),
        'filter_items_list'     => __( 'Filtrar lista de inscrições', 'sevo-eventos' ),
    );
    $args = array(
        'label'                 => __( 'Inscrição', 'sevo-eventos' ),
        'description'           => __( 'CPT para Inscrições em Seções', 'sevo-eventos' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'custom-fields' ), // 'title' para o título fixo
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
    register_post_type( 'sevo_inscr', $args );
}
add_action( 'init', 'registrar_sevo_inscr_cpt', 0 );


// Adicionando meta boxes para sevo_inscr
function adicionar_meta_boxes_sevo_inscr() {
    add_meta_box( 'sevo_inscr_secao', 'Seção', 'renderizar_meta_box_secao', 'sevo_inscr', 'side' );
    add_meta_box( 'sevo_inscr_usuario', 'Usuário', 'renderizar_meta_box_usuario', 'sevo_inscr', 'side' );
    add_meta_box( 'sevo_inscr_data_cancelamento', 'Data do Cancelamento', 'renderizar_meta_box_data_cancelamento', 'sevo_inscr', 'side' );
}
add_action( 'add_meta_boxes', 'adicionar_meta_boxes_sevo_inscr' );


// Funções para renderizar os meta boxes de sevo_inscr
function renderizar_meta_box_secao( $post ) {
    $secao_id = get_post_meta( $post->ID, 'sevo_inscr_secao', true );
    wp_dropdown_posts( array(
        'post_type' => 'sevo_secao',
        'selected' => $secao_id,
        'show_option_none' => 'Selecione uma seção',
    ) );
}

function renderizar_meta_box_usuario( $post ) {
    $usuario_id = get_post_meta( $post->ID, 'sevo_inscr_usuario', true );
    wp_dropdown_users( array( 'selected' => $usuario_id ) );
}

function renderizar_meta_box_data_cancelamento( $post ) {
    $data_cancelamento = get_post_meta( $post->ID, 'sevo_inscr_data_cancelamento', true );
    echo '<input type="date" name="sevo_inscr_data_cancelamento" value="' . esc_attr( $data_cancelamento ) . '">';
}


// Salva os dados dos meta boxes de sevo_inscr
function salvar_meta_boxes_sevo_inscr( $post_id ) {
    // Verifica se é uma auto-save routine (sem salvar dados)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Verifica permissões
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['sevo_inscr_secao'] ) ) {
        update_post_meta( $post_id, 'sevo_inscr_secao', intval( $_POST['sevo_inscr_secao'] ) );
    }
    if ( isset( $_POST['sevo_inscr_usuario'] ) ) {
        update_post_meta( $post_id, 'sevo_inscr_usuario', intval( $_POST['sevo_inscr_usuario'] ) );
    }
    if ( isset( $_POST['sevo_inscr_data_cancelamento'] ) ) {
        update_post_meta( $post_id, 'sevo_inscr_data_cancelamento', sanitize_text_field( $_POST['sevo_inscr_data_cancelamento'] ) );
    }
}
add_action( 'save_post_sevo_inscr', 'salvar_meta_boxes_sevo_inscr' );


// Define o título automaticamente ao criar uma nova inscrição
add_action( 'save_post_sevo_inscr', 'definir_titulo_inscricao', 10, 2 );
function definir_titulo_inscricao( $post_id, $post ) {
    if ( $post->post_status == 'auto-draft' ) {
        $titulo = 'Inscrição solicitada em ' . date( 'd/m/Y' );
        wp_update_post( array( 'ID' => $post_id, 'post_title' => $titulo ) );
    }
}

?>
