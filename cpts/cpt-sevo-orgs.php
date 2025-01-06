php
<?php
// Não permitir acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Registrar o tipo de post personalizado (CPT) para Organizações
function sevo_register_orgs_cpt() {
    $labels = array(
        'name'                  => _x('Organizações', 'Post type general name', 'sevosports'),
        'singular_name'         => _x('Organização', 'Post type singular name', 'sevosports'),
        'menu_name'             => _x('Organizações', 'Admin Menu text', 'sevosports'),
        'name_admin_bar'        => _x('Organização', 'Add New on Toolbar', 'sevosports'),
        'add_new'               => __('Adicionar Nova', 'sevosports'),
        'add_new_item'          => __('Adicionar Nova Organização', 'sevosports'),
        'new_item'              => __('Nova Organização', 'sevosports'),
        'edit_item'             => __('Editar Organização', 'sevosports'),
        'view_item'             => __('Ver Organização', 'sevosports'),
        'all_items'             => __('Todas as Organizações', 'sevosports'),
        'search_items'          => __('Pesquisar Organizações', 'sevosports'),
        'parent_item_colon'     => __('Organizações Pais:', 'sevosports'),
        'not_found'             => __('Nenhuma organização encontrada.', 'sevosports'),
        'not_found_in_trash'    => __('Nenhuma organização encontrada na lixeira.', 'sevosports'),
        'featured_image'        => _x('Imagem da Organização', '
Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'sevosports'),
        'set_featured_image'    => _x('Definir imagem da organização', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'sevosports'),
        'remove_featured_image' => _x('Remover imagem da organização', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'sevosports'),
        'use_featured_image'    => _x('Usar como imagem da organização', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'sevosports'),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'query_var'             => true,
        'rewrite'               => array('slug' => 'sevo_orgs'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => null,
        'menu_icon'             => 'dashicons-groups', // Ícone do menu
        'supports'              => array('title', 'thumbnail'),
    );

    register_post_type('sevo_orgs', $args);
}
add_action('init', 'sevo_register_orgs_cpt');

// Adicionar metabox para o campo Proprietário
function sevo_orgs_add_owner_metabox() {
    add_meta_box('sevo_orgs_owner', __('Proprietário', 'sevosports'), 'sevo_orgs_owner_metabox_callback', 'sevo_orgs', 'normal', 'high');
}
add_action('add_meta_boxes', 'sevo_orgs_add_owner_metabox');

// Callback para o metabox do Proprietário
function sevo_orgs_owner_metabox_callback($post) {
    $owner = get_post_meta($post->ID, '_sevo_orgs_owner', true);
    $users = get_users();

    echo '<select name="sevo_orgs_owner">';
    echo '<option value="">' . __('Selecionar Proprietário', 'sevosports') . '</option>';
    foreach ($users as $user) {
        $selected = selected($owner, $user->ID, false);
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>';
}

// Salvar o valor do Proprietário
function sevo_orgs_save_owner_metabox($post_id) {
    if (array_key_exists('sevo_orgs_owner', $_POST)) {
        update_post_meta($post_id, '_sevo_orgs_owner', $_POST['sevo_orgs_owner']);
    }
}
add_action('save_post', 'sevo_orgs_save_owner_metabox');

// Adicionar metabox para o campo Status
function sevo_orgs_add_status_metabox() {
    add_meta_box('sevo_orgs_status', __('Status', 'sevosports'), 'sevo_orgs_status_metabox_callback', 'sevo_orgs', 'side', 'default');
}
add_action('add_meta_boxes', 'sevo_orgs_add_status_metabox');

// Callback para o metabox do Status
function sevo_orgs_status_metabox_callback($post) {
    $status = get_post_meta($post->ID, '_sevo_orgs_status', true);
    ?>
    <select name="sevo_orgs_status">
        <option value="active" <?php selected($status, 'active'); ?>><?php _e('Ativo', 'sevosports'); ?></option>
        <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inativo', 'sevosports'); ?></option>
    </select>
    <?php
}

// Salvar o valor do Status
function sevo_orgs_save_status_metabox($post_id) {
    if (array_key_exists('sevo_orgs_status', $_POST)) {
        update_post_meta($post_id, '_sevo_orgs_status', $_POST['sevo_orgs_status']);
    }
}
add_action('save_post', 'sevo_orgs_save_status_metabox');

// Função para converter e redimensionar a imagem destacada
function sevo_orgs_convert_and_resize_thumbnail($post_id) {
    // Verificar se é um CPT 'sevo_orgs'
    if (get_post_type($post_id) !== 'sevo_orgs') {
        return;
    }

    // Verificar se há uma imagem destacada
    if (!has_post_thumbnail($post_id)) {
        return;
    }

    // Obter o ID da imagem destacada
    $thumbnail_id = get_post_thumbnail_id($post_id);
    // Obter o caminho do arquivo da imagem destacada
    $file_path = get_attached_file($thumbnail_id);

    // Obter informações sobre a imagem
    $file_info = pathinfo($file_path);
    $file_name = wp_basename($file_path, '.' . $file_info['extension']);

    // Verificar se o arquivo é uma imagem
    $allowed_mime_types = array('image/jpeg', 'image/gif', 'image/png', 'image/webp');
    $file_mime_type = mime_content_type($file_path);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        return;
    }

    // Converter para PNG
    $image = wp_get_image_editor($file_path);
    if (is_wp_error($image)) {
        return; // Não foi possível carregar a imagem
    }

    // Redimensionar a imagem
    $image->resize(300, 300);

    // Defin
ir o novo caminho do arquivo
    $new_file_path = $file_info['dirname'] . '/' . $file_name . '-resized.png';

    // Salvar a imagem como PNG
    $image->save($new_file_path, 'image/png');

    // Inserir nova imagem na biblioteca de mídia
    $attachment = array(
        'guid'           => content_url() . '/uploads/' . wp_basename($new_file_path),
        'post_mime_type' => 'image/png',
        'post_title'     => preg_replace('/\.[^.]+$/', '', wp_basename($new_file_path)),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $new_file_path, $post_id);

    if (!is_wp_error($attach_id)) {
        // Configurar dados da imagem
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $new_file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Atualizar a imagem destacada do post
        set_post_thumbnail($post_id, $attach_id);

         // Remover a imagem original
         wp_delete_attachment($thumbnail_id, true);

    }

}
add_action('save_post', 'sevo_orgs_convert_and_resize_thumbnail');

?>
