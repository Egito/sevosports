php
<?php
/**
 * Arquivo que contém as funções para o CPT sevo_secao.
 *
 * @package sevo
 */

/**
 * Registra o custom post type sevo_secao.
 */
function sevo_registrar_cpt_secao() {
    $labels = array(
        'name'               => _x( 'Seções', 'post type general name', 'sevo' ),
        'singular_name'      => _x( 'Seção', 'post type singular name', 'sevo' ),
        'menu_name'          => _x( 'Seções', 'admin menu', 'sevo' ),
        'name_admin_bar'     => _x( 'Seção', 'add new on admin bar', 'sevo' ),
        'add_new'            => _x( 'Adicionar Nova', 'secao', 'sevo' ),
        'add_new_item'       => __( 'Adicionar Nova Seção', 'sevo' ),
        'new_item'           => __( 'Nova Seção', 'sevo' ),
        'edit_item'          => __( 'Editar Seção', 'sevo' ),
        'view_item'          => __( 'Ver Seção', 'sevo' ),
        'all_items'          => __( 'Todas as Seções', 'sevo' ),
        'search_items'       => __( 'Buscar Seções', 'sevo' ),
        'parent_item_colon'  => __( 'Seções Pai:', 'sevo' ),
        'not_found'          => __( 'Nenhuma seção encontrada
.', 'sevo' ),
        'not_found_in_trash' => __( 'Nenhuma seção encontrada na lixeira.', 'sevo' ),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Descrição.', 'sevo' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'secao' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-clipboard',
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
    );

    register_post_type( 'sevo_secao', $args );
}
add_action( 'init', 'sevo_registrar_cpt_secao', 0 );

/**
 * Salva as meta informações do CPT de Seções.
 *
 * @param int $post_id O ID do post sendo salvo.
 */
function sevo_salvar_metabox_secao(int $post_id): void {
    //Verificar se o usuário tem permissão para editar o post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['sevo_secao_vagas'])) {
        $vagas = absint($_POST['sevo_secao_vagas']);
        $evento_id = get_post_meta($post_id, '_sevo_secao_evento_id', true);

        if ($evento_id) {
            $max_vagas_evento = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
            
            // Validar o número de vagas
            if ($vagas < 0 || $vagas > $max_vagas_evento) {
              // Exibir mensagem de erro
              add_action('admin_notices', function() use ($max_vagas_evento) {
                  echo '<div class="notice notice-error"><p>O número de vagas deve estar entre 0 e ' . $max_vagas_evento . '.</p></div>';
              });
              // Usar o valor máximo ou minimo de forma correta.
              if($vagas < 0){
                $vagas = 0;
              }else{
                $vagas = $max_vagas_evento;
              }
            }

            update_post_meta($post_id, '_sevo_secao_vagas', $vagas);
        }
    }
    
    if (isset($_POST['sevo_secao_evento_id'])) {
      $secao_evento_id = absint($_POST['sevo_secao_evento_id']);
      update_post_meta($post_id, '_sevo_secao_evento_id', $secao_evento_id);
    }

    if (isset($_POST['sevo_secao_data_inicio_inscricoes'])) {
        $secao_data_inicio_inscricoes = sanitize_text_field($_POST['sevo_secao_data_inicio_inscricoes']);
        update_post_meta($post_id, '_sevo_secao_data_inicio_inscricoes', $secao_data_inicio_inscricoes);
    }

    if (isset($_POST['sevo_secao_data_fim_inscricoes'])) {
        $secao_data_fim_inscricoes = sanitize_text_field($_POST['sevo_secao_data_fim_inscricoes']);
        update_post_meta($post_id, '_sevo_secao_data_fim_inscricoes', $secao_data_fim_inscricoes);
    }

    if (isset($_POST['sevo_secao_data_inicio_evento'])) {
        $secao_data_inicio_evento = sanitize_text_field($_POST['sevo_secao_data_inicio_evento']);
        update_post_meta($post_id, '_sevo_secao_data_inicio_evento', $secao_data_inicio_evento);
    }

    if (isset($_POST['sevo_secao_data_fim_evento'])) {
        $secao_data_fim_evento = sanitize_text_field($_POST['sevo_secao_data_fim_evento']);
        update_post_meta($post_id, '_sevo_secao_data_fim_evento', $secao_data_fim_evento);
    }

    if (isset($_POST['sevo_secao_categorias'])) {
        $secao_categorias = $_POST['sevo_secao_categorias'];
        update_post_meta($post_id, '_sevo_secao_categorias', $secao_categorias);
    }
}

add_action('save_post_sevo_secao', 'sevo_salvar_metabox_secao');

/**
 * Adiciona as metaboxes ao CPT de Seções.
 */
function sevo_adicionar_metabox_secao() {
    add_meta_box(
        'sevo_metabox_secao',
        'Detalhes da Seção',
        'sevo_exibir_metabox_secao',
        'sevo_secao',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'sevo_adicionar_metabox_secao');

/**
 * Exibe a metabox do CPT de Seções.
 *
 * @param WP_Post $post O objeto do post atual.
 */
function sevo_exibir_metabox_secao($post) {
    // Obter o máximo de vagas do evento
    $evento_id = get_post_meta($post->ID, '_sevo_secao_evento_id', true);
    if ($evento_id) {
        // Obter o máximo de vagas do evento
        $max_vagas_evento = get_post_meta($evento_id, '_sevo_evento_max_vagas', true);
    } else {
        $max_vagas_evento = 0;
    }
    // Campos de texto
    wp_nonce_field('sevo_salvar_metabox_secao', 'sevo_metabox_secao_nonce');

    // Campo sevo_secao_evento_id
    ?>
    <label for="sevo_secao_evento_id">Evento:</label>
    <select name="sevo_secao_evento_id" id="sevo_secao_evento_id">
        <option value="">Selecione um evento</option>
        <?php
        $eventos = get_posts(array('post_type' => 'sevo_evento', 'posts_per_page' => -1));
        foreach ($eventos as $evento) {
            $selected = get_post_meta($post->ID, '_sevo_secao_evento_id', true) == $evento->ID ? 'selected' : '';
            echo '<option value="' . $evento->ID . '" ' . $selected . '>' . $evento->post_title . '</option>';
        }
        ?>
    </select>
    <br><br>

    <label for="sevo_secao_vagas">Número de Vagas:</label>
    <input type="number" name="sevo_secao_vagas" id="sevo_secao_vagas" value="<?php echo esc_attr(get_post_meta($post->ID, '_sevo_secao_vagas', true)); ?>" />
    <?php if($max_vagas_evento){ ?>
        <p class="description">Máximo de vagas para este evento: <?php echo $max_vagas_evento; ?></p>
    <?php }else{ ?>
        <p class="description">Selecione um evento, para exibir o maximo de vagas disponiveis.</p>
    <?php } ?>
    <br><br>

    <label for="sevo_secao_data_inicio_inscricoes">Data de Início das Inscrições:</label>
    <input type="date" name="sevo_secao_data_inicio_inscricoes" id="sevo_secao_data_inicio_inscricoes" value="<?php echo esc_attr(get_post_meta($post->ID, '_sevo_secao_data_inicio_inscricoes', true)); ?>" />
    <br><br>

    <label for="sevo_secao_data_fim_inscricoes">Data de Fim das Inscrições:</label>
    <input type="date" name="sevo_secao_data_fim_inscricoes" id="sevo_secao_data_fim_inscricoes" value="<?php echo esc_attr(get_post_meta($post->ID, '_sevo_secao_data_fim_inscricoes', true)); ?>" />
    <br><br>

    <label for="sevo_secao_data_inicio_evento">Data de Início do Evento:</label>
    <input type="date" name="sevo_secao_data_inicio_evento" id="sevo_secao_data_inicio_evento" value="<?php echo esc_attr(get_post_meta($post->ID, '_sevo_secao_data_inicio_evento', true)); ?>" />
    <br><br>

    <label for="sevo_secao_data_fim_evento">Data de Fim do Evento:</label>
    <input type="date" name="sevo_secao_data_fim_evento" id="sevo_secao_data_fim_evento" value="<?php echo esc_attr(get_post_meta($post->ID, '_sevo_secao_data_fim_evento', true)); ?>" />
    <br><br>
    
    <label for="sevo_secao_categorias">Categorias:</label>
    <?php
      $terms = get_terms( array(
        'taxonomy' => 'sevo_categoria',
        'hide_empty' => false,
      ) );
    ?>
    <select name="sevo_secao_categorias[]" id="sevo_secao_categorias" multiple>
      <?php foreach ( $terms as $term ) : ?>
      <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php echo ( in_array( $term->term_id, (array) get_post_meta( $post->ID, '_sevo_secao_categorias', true ) ) ? 'selected' : '' ); ?>>
        <?php echo esc_html( $term->name ); ?>
      </option>
      <?php endforeach; ?>
    </select>
    <br><br>
    <?php
}
