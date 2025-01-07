<?php
/**
 * CPT Sevo Organização
 */

// Registro da taxonomia sevo_org
function registrar_taxonomia_sevo_org() {
    $labels = array(
        'name'                       => _x( 'Organizações', 'taxonomy general name', 'sevo-eventos' ),
        'singular_name'              => _x( 'Organização', 'taxonomy singular name', 'sevo-eventos' ),
        'search_items'               => __( 'Buscar organizações', 'sevo-eventos' ),
        'all_items'                  => __( 'Todas as organizações', 'sevo-eventos' ),
        'parent_item'                => __( 'Organização pai', 'sevo-eventos' ),
        'parent_item_colon'          => __( 'Organização pai:', 'sevo-eventos' ),
        'edit_item'                  => __( 'Editar organização', 'sevo-eventos' ),
        'update_item'                => __( 'Atualizar organização', 'sevo-eventos' ),
        'add_new_item'               => __( 'Adicionar nova organização', 'sevo-eventos' ),
        'new_item_name'              => __( 'Nome da nova organização', 'sevo-eventos' ),
        'menu_name'                  => __( 'Organizações', 'sevo-eventos' ),
    );
    $args = array(
        'hierarchical'          => true, // Define como hierárquica (como categorias)
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'organizacao' ), // Define o slug
    );
    register_taxonomy( 'sevo_org', array( 'sevo_evento' ), $args );
}
add_action( 'init', 'registrar_taxonomia_sevo_org', 0 );


// Registro do CPT sevo_org
function registrar_sevo_org_cpt() {
    $labels = array(
        'name'                  => _x( 'Organizações', 'Post Type General Name', 'sevo-eventos' ),
        'singular_name'         => _x( 'Organização', 'Post Type Singular Name', 'sevo-eventos' ),
        'menu_name'             => __( 'Organizações', 'sevo-eventos' ),
        'name_admin_bar'        => __( 'Organização', 'sevo-eventos' ),
        'archives'              => __( 'Arquivos de organizações', 'sevo-eventos' ),
        'attributes'            => __( 'Atributos da organização', 'sevo-eventos' ),
        'parent_item_colon'     => __( 'Organização pai:', 'sevo-eventos' ),
        'all_items'             => __( 'Todas as organizações', 'sevo-eventos' ),
        'add_new_item'          => __( 'Adicionar nova organização', 'sevo-eventos' ),
        'add_new'               => __( 'Nova organização', 'sevo-eventos' ),
        'new_item'              => __( 'Nova organização', 'sevo-eventos' ),
        'edit_item'             => __( 'Editar organização', 'sevo-eventos' ),
        'update_item'           => __( 'Atualizar organização', 'sevo-eventos' ),
        'view_item'             => __( 'Ver organização', 'sevo-eventos' ),
        'view_items'            => __( 'Ver organizações', 'sevo-eventos' ),
        'search_items'          => __( 'Buscar organizações', 'sevo-eventos' ),
        'not_found'             => __( 'Não encontrado', 'sevo-eventos' ),
        'not_found_in_trash'    => __( 'Não encontrado na lixeira', 'sevo-eventos' ),
        'featured_image'        => __( 'Imagem destacada', 'sevo-eventos' ),
        'set_featured_image'    => __( 'Definir imagem destacada', 'sevo-eventos' ),
        'remove_featured_image' => __( 'Remover imagem destacada', 'sevo-eventos' ),
        'use_featured_image'    => __( 'Usar como imagem destacada', 'sevo-eventos' ),
        'insert_into_item'      => __( 'Inserir na organização', 'sevo-eventos' ),
        'uploaded_to_this_item' => __( 'Enviado para esta organização', 'sevo-eventos' ),
        'items_list'            => __( 'Lista de organizações', 'sevo-eventos' ),
        'items_list_navigation' => __( 'Navegação da lista de organizações', 'sevo-eventos' ),
        'filter_items_list'     => __( 'Filtrar lista de organizações', 'sevo-eventos' ),
    );
    $args = array(
        'label'                 => __( 'Organização', 'sevo-eventos' ),
        'description'           => __( 'CPT para Organizações', 'sevo-eventos' ),
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
    register_post_type( 'sevo_org', $args );
}
add_action( 'init', 'registrar_sevo_org_cpt', 0 );


// Adicionando meta boxes para sevo_org (Exemplo - adicione seus meta boxes aqui)
function adicionar_meta_boxes_sevo_org() {
    //Exemplo de meta box
    add_meta_box( 'sevo_org_contato', 'Contato', 'renderizar_meta_box_contato', 'sevo_org', 'side' );
}
add_action( 'add_meta_boxes', 'adicionar_meta_boxes_sevo_org' );

// Função para renderizar o meta box de contato
function renderizar_meta_box_contato( $post ) {
    $contato = get_post_meta( $post->ID, 'sevo_org_contato', true );
    echo '<input type="text" name="sevo_org_contato" value="' . esc_attr( $contato ) . '">';
}

// Função para salvar os dados dos meta boxes de sevo_org
function salvar_meta_boxes_sevo_org( $post_id ) {
    // Verifica se é uma auto-save routine (sem salvar dados)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Verifica permissões
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Salva os dados dos meta boxes
    if ( isset( $_POST['sevo_org_contato'] ) ) {
        update_post_meta( $post_id, 'sevo_org_contato', sanitize_text_field( $_POST['sevo_org_contato'] ) );
    }
}
add_action( 'save_post_sevo_org', 'salvar_meta_boxes_sevo_org' );

?>
