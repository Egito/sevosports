<?php
/**
 * CPT Sevo Evento - Nova versão usando tabelas customizadas
 * Esta versão substitui o sistema de CPT do WordPress por tabelas customizadas
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Evento_CPT_New {
    
    private $model;
    private $tipo_model;
    
    public function __construct() {
        $this->model = new Sevo_Evento_Model();
        $this->tipo_model = new Sevo_Tipo_Evento_Model();
        
        // Hooks para o admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sevo_create_evento', array($this, 'ajax_create_evento'));
        add_action('wp_ajax_sevo_update_evento', array($this, 'ajax_update_evento'));
        add_action('wp_ajax_sevo_delete_evento', array($this, 'ajax_delete_evento'));
        add_action('wp_ajax_sevo_get_evento', array($this, 'ajax_get_evento'));
        add_action('wp_ajax_sevo_list_eventos', array($this, 'ajax_list_eventos'));
        add_action('wp_ajax_sevo_get_tipos_evento_select', array($this, 'ajax_get_tipos_evento_select'));
        
        // Enqueue scripts para admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Adiciona menu no admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sevo-eventos',
            __('Eventos', 'sevo-eventos'),
            __('Eventos', 'sevo-eventos'),
            'manage_options',
            'sevo-eventos-list',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Página de administração dos eventos
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Eventos', 'sevo-eventos'); ?>
                <button type="button" class="page-title-action" id="sevo-add-evento-btn">
                    <?php _e('Adicionar Novo', 'sevo-eventos'); ?>
                </button>
            </h1>
            
            <div id="sevo-evento-list-container">
                <!-- Lista será carregada via AJAX -->
            </div>
            
            <!-- Modal para criar/editar evento -->
            <div id="sevo-evento-modal" class="sevo-modal" style="display: none;">
                <div class="sevo-modal-content">
                    <div class="sevo-modal-header">
                        <h2 id="sevo-evento-modal-title"><?php _e('Novo Evento', 'sevo-eventos'); ?></h2>
                        <span class="sevo-modal-close">&times;</span>
                    </div>
                    <div class="sevo-modal-body">
                        <form id="sevo-evento-form">
                            <input type="hidden" id="evento-id" name="id" value="">
                            
                            <div class="sevo-form-group">
                                <label for="evento-nome"><?php _e('Nome:', 'sevo-eventos'); ?></label>
                                <input type="text" id="evento-nome" name="nome" required>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="evento-descricao"><?php _e('Descrição:', 'sevo-eventos'); ?></label>
                                <textarea id="evento-descricao" name="descricao" rows="4"></textarea>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="evento-tipo-id"><?php _e('Tipo de Evento:', 'sevo-eventos'); ?></label>
                                <select id="evento-tipo-id" name="tipo_evento_id" required>
                                    <option value=""><?php _e('Selecione um tipo de evento', 'sevo-eventos'); ?></option>
                                    <!-- Opções carregadas via AJAX -->
                                </select>
                            </div>
                            
                            <div class="sevo-form-row">
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-data-inicio-inscricao"><?php _e('Início das Inscrições:', 'sevo-eventos'); ?></label>
                                    <input type="datetime-local" id="evento-data-inicio-inscricao" name="data_inicio_inscricao" required>
                                </div>
                                
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-data-fim-inscricao"><?php _e('Fim das Inscrições:', 'sevo-eventos'); ?></label>
                                    <input type="datetime-local" id="evento-data-fim-inscricao" name="data_fim_inscricao" required>
                                </div>
                            </div>
                            
                            <div class="sevo-form-row">
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-data-inicio"><?php _e('Início do Evento:', 'sevo-eventos'); ?></label>
                                    <input type="datetime-local" id="evento-data-inicio" name="data_inicio" required>
                                </div>
                                
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-data-fim"><?php _e('Fim do Evento:', 'sevo-eventos'); ?></label>
                                    <input type="datetime-local" id="evento-data-fim" name="data_fim" required>
                                </div>
                            </div>
                            
                            <div class="sevo-form-row">
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-vagas"><?php _e('Número de Vagas:', 'sevo-eventos'); ?></label>
                                    <input type="number" id="evento-vagas" name="vagas" min="1" required>
                                </div>
                                
                                <div class="sevo-form-group sevo-form-half">
                                    <label for="evento-status"><?php _e('Status:', 'sevo-eventos'); ?></label>
                                    <select id="evento-status" name="status">
                                        <option value="rascunho"><?php _e('Rascunho', 'sevo-eventos'); ?></option>
                                        <option value="publicado"><?php _e('Publicado', 'sevo-eventos'); ?></option>
                                        <option value="cancelado"><?php _e('Cancelado', 'sevo-eventos'); ?></option>
                                        <option value="finalizado"><?php _e('Finalizado', 'sevo-eventos'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="evento-local"><?php _e('Local:', 'sevo-eventos'); ?></label>
                                <input type="text" id="evento-local" name="local">
                            </div>
                            
                            <div class="sevo-form-group">
                                <label for="evento-observacoes"><?php _e('Observações:', 'sevo-eventos'); ?></label>
                                <textarea id="evento-observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="sevo-modal-footer">
                        <button type="button" class="button button-secondary" id="sevo-evento-cancel"><?php _e('Cancelar', 'sevo-eventos'); ?></button>
                        <button type="button" class="button button-primary" id="sevo-evento-save"><?php _e('Salvar', 'sevo-eventos'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .sevo-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .sevo-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 800px;
            border-radius: 4px;
            max-height: 90vh;
            overflow-y: auto;
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
        
        .sevo-form-row {
            display: flex;
            gap: 15px;
        }
        
        .sevo-form-half {
            flex: 1;
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
        
        .sevo-evento-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .sevo-evento-table th,
        .sevo-evento-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .sevo-evento-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
        
        .sevo-evento-actions {
            white-space: nowrap;
        }
        
        .sevo-evento-actions button {
            margin-right: 5px;
        }
        
        .status-rascunho {
            color: #666;
            font-weight: bold;
        }
        
        .status-publicado {
            color: #46b450;
            font-weight: bold;
        }
        
        .status-cancelado {
            color: #dc3232;
            font-weight: bold;
        }
        
        .status-finalizado {
            color: #00a0d2;
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue scripts para admin
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'sevo-eventos_page_sevo-eventos-list') {
            return;
        }
        
        wp_enqueue_script(
            'sevo-evento-admin',
            SEVO_EVENTOS_PLUGIN_URL . 'assets/js/admin-eventos.js',
            array('jquery'),
            SEVO_EVENTOS_VERSION,
            true
        );
        
        wp_localize_script('sevo-evento-admin', 'sevoEventoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sevo_evento_nonce'),
            'strings' => array(
                'confirm_delete' => __('Tem certeza que deseja excluir este evento?', 'sevo-eventos'),
                'error' => __('Erro ao processar solicitação.', 'sevo-eventos'),
                'success_create' => __('Evento criado com sucesso!', 'sevo-eventos'),
                'success_update' => __('Evento atualizado com sucesso!', 'sevo-eventos'),
                'success_delete' => __('Evento excluído com sucesso!', 'sevo-eventos')
            )
        ));
    }
    
    /**
     * AJAX: Criar evento
     */
    public function ajax_create_evento() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $data = array(
            'nome' => sanitize_text_field($_POST['nome']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'tipo_evento_id' => absint($_POST['tipo_evento_id']),
            'data_inicio_inscricao' => sanitize_text_field($_POST['data_inicio_inscricao']),
            'data_fim_inscricao' => sanitize_text_field($_POST['data_fim_inscricao']),
            'data_inicio' => sanitize_text_field($_POST['data_inicio']),
            'data_fim' => sanitize_text_field($_POST['data_fim']),
            'vagas' => absint($_POST['vagas']),
            'local' => sanitize_text_field($_POST['local']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->model->create($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Evento criado com sucesso!', 'sevo-eventos'),
                'id' => $result
            ));
        } else {
            wp_send_json_error(__('Erro ao criar evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Atualizar evento
     */
    public function ajax_update_evento() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $data = array(
            'nome' => sanitize_text_field($_POST['nome']),
            'descricao' => sanitize_textarea_field($_POST['descricao']),
            'tipo_evento_id' => absint($_POST['tipo_evento_id']),
            'data_inicio_inscricao' => sanitize_text_field($_POST['data_inicio_inscricao']),
            'data_fim_inscricao' => sanitize_text_field($_POST['data_fim_inscricao']),
            'data_inicio' => sanitize_text_field($_POST['data_inicio']),
            'data_fim' => sanitize_text_field($_POST['data_fim']),
            'vagas' => absint($_POST['vagas']),
            'local' => sanitize_text_field($_POST['local']),
            'observacoes' => sanitize_textarea_field($_POST['observacoes']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        $result = $this->model->update($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Evento atualizado com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao atualizar evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Excluir evento
     */
    public function ajax_delete_evento() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $result = $this->model->delete($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Evento excluído com sucesso!', 'sevo-eventos')
            ));
        } else {
            wp_send_json_error(__('Erro ao excluir evento.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Obter evento
     */
    public function ajax_get_evento() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $id = absint($_POST['id']);
        $evento = $this->model->find($id);
        
        if ($evento) {
            // Converter datas para formato datetime-local
            $evento->data_inicio_inscricao = date('Y-m-d\TH:i', strtotime($evento->data_inicio_inscricao));
            $evento->data_fim_inscricao = date('Y-m-d\TH:i', strtotime($evento->data_fim_inscricao));
            $evento->data_inicio = date('Y-m-d\TH:i', strtotime($evento->data_inicio));
            $evento->data_fim = date('Y-m-d\TH:i', strtotime($evento->data_fim));
            
            wp_send_json_success($evento);
        } else {
            wp_send_json_error(__('Evento não encontrado.', 'sevo-eventos'));
        }
    }
    
    /**
     * AJAX: Listar eventos
     */
    public function ajax_list_eventos() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        
        $eventos = $this->model->get_with_related_data_paginated($page, $per_page);
        
        ob_start();
        ?>
        <table class="sevo-evento-table">
            <thead>
                <tr>
                    <th><?php _e('Nome', 'sevo-eventos'); ?></th>
                    <th><?php _e('Tipo de Evento', 'sevo-eventos'); ?></th>
                    <th><?php _e('Organização', 'sevo-eventos'); ?></th>
                    <th><?php _e('Data do Evento', 'sevo-eventos'); ?></th>
                    <th><?php _e('Vagas', 'sevo-eventos'); ?></th>
                    <th><?php _e('Status', 'sevo-eventos'); ?></th>
                    <th><?php _e('Ações', 'sevo-eventos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eventos['data'])): ?>
                    <?php foreach ($eventos['data'] as $evento): ?>
                        <tr>
                            <td><?php echo esc_html($evento->nome); ?></td>
                            <td><?php echo esc_html($evento->tipo_evento_nome ?: '-'); ?></td>
                            <td><?php echo esc_html($evento->organizacao_nome ?: '-'); ?></td>
                            <td>
                                <?php 
                                echo esc_html(date('d/m/Y H:i', strtotime($evento->data_inicio)));
                                if ($evento->data_fim !== $evento->data_inicio) {
                                    echo ' - ' . esc_html(date('d/m/Y H:i', strtotime($evento->data_fim)));
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($evento->vagas); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($evento->status); ?>">
                                    <?php echo esc_html(ucfirst($evento->status)); ?>
                                </span>
                            </td>
                            <td class="sevo-evento-actions">
                                <button type="button" class="button button-small sevo-edit-evento" data-id="<?php echo esc_attr($evento->id); ?>">
                                    <?php _e('Editar', 'sevo-eventos'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete sevo-delete-evento" data-id="<?php echo esc_attr($evento->id); ?>">
                                    <?php _e('Excluir', 'sevo-eventos'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7"><?php _e('Nenhum evento encontrado.', 'sevo-eventos'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($eventos['total_pages'] > 1): ?>
            <div class="sevo-pagination">
                <?php for ($i = 1; $i <= $eventos['total_pages']; $i++): ?>
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
    
    /**
     * AJAX: Obter tipos de evento para select
     */
    public function ajax_get_tipos_evento_select() {
        check_ajax_referer('sevo_evento_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'sevo-eventos'));
        }
        
        $tipos = $this->tipo_model->get_for_select();
        
        ob_start();
        echo '<option value="">' . __('Selecione um tipo de evento', 'sevo-eventos') . '</option>';
        foreach ($tipos as $tipo) {
            echo '<option value="' . esc_attr($tipo->id) . '">' . esc_html($tipo->nome) . '</option>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}