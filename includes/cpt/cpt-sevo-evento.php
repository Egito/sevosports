<?php
/**
 * CPT Sevo Evento
 */

// Função para registrar o CPT sevo_evento
function registrar_sevo_evento_cpt() {
    $labels = array(
        'name'                  => _x( 'Eventos', 'Post Type General Name', 'sevo-eventos' ),
        'singular_name'         => _x( 'Evento', 'Post Type Singular Name', 'sevo-eventos' ),
        'menu_name'             => __( 'Eventos', 'sevo-eventos' ),
        'name_admin_bar'        => __( 'Evento', 'sevo-eventos' ),
        'archives'              => __( 'Arquivos de eventos', 'sevo-eventos' ),
        'attributes'            => __( 'Atributos do evento', 'sevo-eventos' ),
        'parent_item_colon'     => __( 'Evento pai:', 'sevo-eventos' ),
        'all_items'             => __( 'Todos os eventos', 'sevo-eventos' ),
        'add_new_item'          => __( 'Adicionar novo evento', 'sevo-eventos' ),
        'add_new'               => __( 'Novo evento', 'sevo-eventos' ),
        'new_item'              => __( 'Novo evento', 'sevo-eventos' ),
        'edit_item'             => __( 'Editar evento', 'sevo-eventos' ),
        'update_item'           => __( 'Atualizar evento', 'sevo-eventos' ),
        'view_item'             => __( 'Ver evento', 'sevo-eventos' ),
        'view_items'            => __( 'Ver eventos', 'sevo-eventos' ),
        'search_items'          => __( 'Buscar eventos', 'sevo-eventos' ),
        'not_found'             => __( 'Não encontrado', 'sevo-eventos' ),
        'not_found_in_trash'    => __( 'Não encontrado na lixeira', 'sevo-eventos' ),
        'featured_image'        => __( 'Imagem destacada', 'sevo-eventos' ),
        'set_featured_image'    => __( 'Definir imagem destacada', 'sevo-eventos' ),
        'remove_featured_image' => __( 'Remover imagem destacada', 'sevo-eventos' ),
        'use_featured_image'    => __( 'Usar como imagem destacada', 'sevo-eventos' ),
        'insert_into_item'      => __( 'Inserir no evento', 'sevo-eventos' ),
        'uploaded_to_this_item' => __( 'Enviado para este evento', 'sevo-eventos' ),
        'items_list'            => __( 'Lista de eventos', 'sevo-eventos' ),
        'items_list_navigation' => __( 'Navegação da lista de eventos', 'sevo-eventos' ),
        'filter_items_list'     => __( 'Filtrar lista de eventos', 'sevo-eventos' ),
    );
    $args = array(
        'label'                 => __( 'Evento', 'sevo-eventos' ),
        'description'           => __( 'CPT para Eventos', 'sevo-eventos' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'taxonomies'            => array( 'sevo_org' ), // Relacionamento com sevo_org
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
    register_post_type( 'sevo_evento', $args );
}
add_action( 'init', 'registrar_sevo_evento_cpt', 0 );


// Função para adicionar os meta boxes
function adicionar_meta_boxes_sevo_evento() {
    add_meta_box( 'sevo_evento_situacao', 'Situação', 'renderizar_meta_box_situacao', 'sevo_evento', 'side' );
    add_meta_box( 'sevo_evento_estilo', 'Estilo', 'renderizar_meta_box_estilo', 'sevo_evento', 'side' );
    add_meta_box( 'sevo_evento_vagas', 'Vagas Máximas', 'renderizar_meta_box_vagas', 'sevo_evento', 'side' );
}
add_action( 'add_meta_boxes', 'adicionar_meta_boxes_sevo_evento' );


// Funções para renderizar os meta boxes
function renderizar_meta_box_situacao( $post ) {
    $situacao = get_post_meta( $post->ID, 'sevo_evento_situacao', true );
    ?>
    <select name="sevo_evento_situacao">
        <option value="ativo" <?php selected( $situacao, 'ativo' ); ?>>Ativo</option>
        <option value="inativo" <?php selected( $situacao, 'inativo' ); ?>>Inativo</option>
    </select>
    <?php
}

function renderizar_meta_box_estilo( $post ) {
    $estilo = get_post_meta( $post->ID, 'sevo_evento_estilo', true );
    ?>
    <select name="sevo_evento_estilo">
        <option value="grupo" <?php selected( $estilo, 'grupo' ); ?>>Grupo</option>
        <option value="individual" <?php selected( $estilo, 'individual' ); ?>>Individual</option>
    </select>
    <?php
}

function renderizar_meta_box_vagas( $post ) {
    $vagas = get_post_meta( $post->ID, 'sevo_evento_vagas', true );
    echo '<input type="number" name="sevo_evento_vagas" value="' . esc_attr( $vagas ) . '" min="1">';
}


// Função para salvar os dados dos meta boxes
function salvar_meta_boxes_sevo_evento( $post_id ) {
    // Verifica se é uma auto-save routine (sem salvar dados)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Verifica permissões
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Salva os dados dos meta boxes
    if ( isset( $_POST['sevo_evento_situacao'] ) ) {
        update_post_meta( $post_id, 'sevo_evento_situacao', sanitize_text_field( $_POST['sevo_evento_situacao'] ) );
    }
    if ( isset( $_POST['sevo_evento_estilo'] ) ) {
        update_post_meta( $post_id, 'sevo_evento_estilo', sanitize_text_field( $_POST['sevo_evento_estilo'] ) );
    }
    if ( isset( $_POST['sevo_evento_vagas'] ) ) {
        update_post_meta( $post_id, 'sevo_evento_vagas', intval( $_POST['sevo_evento_vagas'] ) );
    }
}
add_action( 'save_post_sevo_evento', 'salvar_meta_boxes_sevo_evento' );

?>
