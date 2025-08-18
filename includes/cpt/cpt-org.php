<?php
/**
 * CPT Sevo Organização - Nova versão usando tabelas customizadas
 * Esta versão substitui o sistema de CPT do WordPress por tabelas customizadas
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Orgs_CPT_New {
    
    private $model;
    
    public function __construct() {
        $this->model = new Sevo_Organizacao_Model();
        
        // Manter apenas os hooks necessários para o admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sevo_create_organizacao', array($this, 'ajax_create_organizacao'));
        add_action('wp_ajax_sevo_update_organizacao', array($this, 'ajax_update_organizacao'));
        add_action('wp_ajax_sevo_delete_organizacao', array($this, 'ajax_delete_organizacao'));
        add_action('wp_ajax_sevo_get_organizacao', array($this, 'ajax_get_organizacao'));
        add_action('wp_ajax_sevo_list_organizacoes', array($this, 'ajax_list_organizacoes'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sevo-eventos',
            __('Organizações', 'sevo-eventos'),
            __('Organizações', 'sevo-eventos'),
            'manage_options',
            'sevo-organizacoes',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página de administração das organizações
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Organizações', 'sevo-eventos'); ?>
                <button type="button" class="page-title-action" id="sevo-add-org-btn">
                    <?php _e('Adicionar Nova', 'sevo-eventos'); ?>
                </button>
            </h1>
            
            <div id="sevo-org-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <!-- Modal para criar/editar organização -->
            <div id="sevo-org-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h2 id="sevo-org-modal-title"><?php _e('Nova Organização', 'sevo-eventos'); ?></h2>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <?php 
                    // Incluir o template do modal
                    $organizacao = null; // Para modo de criação
                    include(SEVO_EVENTOS_PLUGIN_DIR . 'templates/modals/modal-org-edit.php');
                    ?>
                    <div class="sevo-modal-footer">
                        <button type="button" class="button button-secondary" id="sevo-org-cancel"><?php _e('Cancelar', 'sevo-eventos'); ?></button>
                        <button type="button" class="button button-primary" id="sevo-org-save"><?php _e('Salvar', 'sevo-eventos'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .sevo-modal {
            position: fixed;
            z-index: 9999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .sevo-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        
        .sevo-modal-header {
            padding: 15px 20px;
            background-color: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sevo-modal-header h2 {
            margin: 0;
        }
        
        .sevo-modal-close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .sevo-modal-close:hover {
            color: black;
        }
        
        .sevo-modal-body {
            padding: 20px;
        }
        
        .sevo-modal-footer {
            padding: 15px 20px;
            background-color: #f1f1f1;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        .sevo-form-group {
            margin-bottom: 15px;
        }
        
        .sevo-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .sevo-form-group input,
        .sevo-form-group select,
        .sevo-form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .sevo-org-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .sevo-org-table th,
        .sevo-org-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .sevo-org-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        
        .sevo-org-actions {
            white-space: nowrap;
        }
        
        .sevo-org-actions button {
            margin-right: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para admin
     */
    public function admin_enqueue_scripts($hook) {
        // Verificar se estamos na página de organizações
        if (strpos($hook, 'sevo-organizacoes') === false) {
            return;
        }
        
        // Script de organizações é carregado pelo shortcode
        // Removido daqui para evitar conflitos de carregamento duplo
    }
    
    /**
     * AJAX: Criar organização
     */
    public function ajax_create_organizacao() {
        check_ajax_referer('sevo_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $data = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'autor_id' => absint($_POST['autor_id']),
            'status' => sanitize_text_field($_POST['status']),
            'imagem_url' => $this->process_image_url($_POST['imagem_url'])
        );
        
        $result = $this->model->create_validated($data);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Organização criada com sucesso!', 'sevo-eventos'),
                'id' => $result['id']
            ));
        } else {
            wp_send_json_error(implode('. ', $result['errors']));
        }
    }
    
    /**
     * AJAX: Atualizar organização
     */
    public function ajax_update_organizacao() {
        check_ajax_referer('sevo_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['org_id']);
        $data = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'autor_id' => absint($_POST['autor_id']),
            'status' => sanitize_text_field($_POST['status']),
            'imagem_url' => $this->process_image_url($_POST['imagem_url'])
        );
        
        $result = $this->model->update_validated($id, $data);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Organização atualizada com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(implode('. ', $result['errors']));
        }
    }
    
    /**
     * Processa URL da imagem e redimensiona para 300x300
     */
    private function process_image_url($image_url) {
        if (empty($image_url)) {
            return '';
        }
        
        $image_url = esc_url_raw($image_url);
        
        // Se for uma URL externa, tentamos fazer o download e redimensionar
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            return $this->resize_external_image($image_url);
        }
        
        return $image_url;
    }
    
    /**
     * Redimensiona imagem externa para 300x300
     */
    private function resize_external_image($image_url) {
        // Verifica se a função de manipulação de imagem está disponível
        if (!function_exists('wp_get_image_editor')) {
            return $image_url; // Retorna URL original se não puder processar
        }
        
        // Faz o download da imagem
        $temp_file = download_url($image_url);
        
        if (is_wp_error($temp_file)) {
            return $image_url; // Retorna URL original se não conseguir baixar
        }
        
        // Carrega o editor de imagem
        $image_editor = wp_get_image_editor($temp_file);
        
        if (is_wp_error($image_editor)) {
            unlink($temp_file);
            return $image_url;
        }
        
        // Redimensiona para 300x300 (crop)
        $image_editor->resize(300, 300, true);
        
        // Gera nome único para o arquivo
        $upload_dir = wp_upload_dir();
        $filename = 'org-' . uniqid() . '.jpg';
        $new_file_path = $upload_dir['path'] . '/' . $filename;
        
        // Salva a imagem redimensionada
        $saved = $image_editor->save($new_file_path, 'image/jpeg');
        
        // Remove arquivo temporário
        unlink($temp_file);
        
        if (is_wp_error($saved)) {
            return $image_url;
        }
        
        // Retorna a URL da nova imagem
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * AJAX: Deletar organização
     */
    public function ajax_delete_organizacao() {
        check_ajax_referer('sevo_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->delete($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Organização excluída com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao excluir organização.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Obter organização
     */
    public function ajax_get_organizacao() {
        check_ajax_referer('sevo_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $organizacao = $this->model->find($id);
        
        if ($organizacao) {
            wp_send_json_success($organizacao);
        } else {
            wp_send_json_error(__('Organização não encontrada.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar organizações
     */
    public function ajax_list_organizacoes() {
        check_ajax_referer('sevo_org_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        
        $organizacoes = $this->model->paginate($page, $per_page);
        
        ob_start();
        ?>
        <table class="sevo-org-table">
            <thead>
                <tr>
                    <th><?php _e('Nome', 'sevo-eventos'); ?></th>
                    <th><?php _e('Autor', 'sevo-eventos'); ?></th>
                    <th><?php _e('Cidade', 'sevo-eventos'); ?></th>
                    <th><?php _e('Status', 'sevo-eventos'); ?></th>
                    <th><?php _e('Criado em', 'sevo-eventos'); ?></th>
                    <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($organizacoes['data'])): ?>
                    <?php foreach ($organizacoes['data'] as $org): ?>
                        <tr>
                            <td><?php echo esc_html($org->nome); ?></td>
                            <td>
                                <?php 
                                $autor = get_user_by('id', $org->autor_id);
                                echo $autor ? esc_html($autor->display_name) : '-';
                                ?>
                            </td>
                            <td><?php echo esc_html($org->cidade ?: '-'); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($org->status); ?>">
                                    <?php echo esc_html(ucfirst($org->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y H:i', strtotime($org->created_at))); ?></td>
                            <td class="sevo-org-actions">
                                <button type="button" class="button button-small sevo-edit-org" data-id="<?php echo esc_attr($org->id); ?>">
                                    <?php _e('Editar', 'sevo-eventos'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete sevo-delete-org" data-id="<?php echo esc_attr($org->id); ?>">
                                    <?php _e('Excluir', 'sevo-eventos'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('Nenhuma organização encontrada.', 'sevo-eventos'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($organizacoes['total_pages'] > 1): ?>
            <div class="sevo-pagination">
                <?php for ($i = 1; $i <= $organizacoes['total_pages']; $i++): ?>
                    <button type="button" class="button sevo-page-btn <?php echo $i === $page ? 'button-primary' : ''; ?>" data-page="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
}