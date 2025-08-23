<?php
/**
 * Template do formulário modal para criar/editar inscrições.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Determina se é edição ou criação
$is_editing = isset($inscricao) && $inscricao;

if ($is_editing) {
    // Buscar dados relacionados usando modelos
    $evento_model = new Sevo_Evento_Model();
    $evento = $evento_model->get_with_relations($inscricao->evento_id);
    $evento = $evento ? $evento[0] : null; // get_with_relations retorna array
    
    $usuario = get_userdata($inscricao->user_id);
    
    if (!$evento || !$usuario) {
        echo '<script>'
            . 'if (typeof SevoToaster !== "undefined") {'
            . 'SevoToaster.showError("Dados da inscrição não encontrados.");'
            . '}'
            . 'if (typeof SevoDashboard !== "undefined" && typeof SevoDashboard.closeEditModal === "function") {'
            . 'setTimeout(function() { SevoDashboard.closeEditModal(); }, 100);'
            . '}'
            . '</script>';
        return;
    }
} else {
    // Carregar eventos para o select
    $evento_model = new Sevo_Evento_Model();
$eventos = $evento_model->get_with_relations();
    
    // Carregar usuários para o select
    $users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
}

// Status possíveis conforme PRD
$status_options = array(
    'solicitada' => 'Solicitada',
    'aceita' => 'Aceita',
    'rejeitada' => 'Rejeitada',
    'cancelada' => 'Cancelada'
);
?>

<div class="sevo-modal-overlay" onclick="SevoDashboard.closeEditModal()"></div>
<div class="sevo-modal-content">
    <div class="sevo-modal-header">
        <h3><?php echo $is_editing ? 'Editar Inscrição #' . esc_html($inscricao->id) : 'Nova Inscrição'; ?></h3>
        <button type="button" class="sevo-modal-close" onclick="SevoDashboard.closeEditModal()">&times;</button>
    </div>
    
    <div class="sevo-modal-body">
        <form id="sevo-edit-inscricao-form" class="sevo-form">
            <?php if ($is_editing): ?>
                <input type="hidden" name="inscricao_id" value="<?php echo esc_attr($inscricao->id); ?>">
            <?php endif; ?>
            
            <?php if ($is_editing): ?>
                <!-- Informações do Usuário -->
                <div class="sevo-form-section">
                    <h4>👤 Informações do Usuário</h4>
                    <div class="sevo-form-row">
                        <div class="sevo-form-group">
                            <label for="edit-usuario-nome">Nome do Usuário</label>
                            <input type="text" id="edit-usuario-nome" name="usuario_nome" 
                                   value="<?php echo esc_attr($usuario ? $usuario->display_name : ''); ?>" 
                                   class="sevo-form-control" readonly>
                            <small class="sevo-form-help">Nome do usuário não pode ser alterado</small>
                        </div>
                        <div class="sevo-form-group">
                            <label for="edit-usuario-email">Email do Usuário</label>
                            <input type="email" id="edit-usuario-email" name="usuario_email" 
                                   value="<?php echo esc_attr($usuario ? $usuario->user_email : ''); ?>" 
                                   class="sevo-form-control" readonly>
                            <small class="sevo-form-help">Email do usuário não pode ser alterado</small>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Evento -->
                <div class="sevo-form-section">
                    <h4>🎯 Informações do Evento</h4>
                    <div class="sevo-form-row">
                        <div class="sevo-form-group">
                            <label for="edit-evento-nome">Nome do Evento</label>
                            <input type="text" id="edit-evento-nome" name="evento_nome" 
                                   value="<?php echo esc_attr($evento ? $evento->titulo : ''); ?>" 
                                   class="sevo-form-control" readonly>
                        </div>
                        <div class="sevo-form-group">
                            <label for="edit-evento-data">Data do Evento</label>
                            <input type="text" id="edit-evento-data" name="evento_data" 
                                   value="<?php echo esc_attr($evento && $evento->data_inicio ? date('d/m/Y', strtotime($evento->data_inicio)) : ''); ?>" 
                                   class="sevo-form-control" readonly>
                        </div>
                    </div>
                    <div class="sevo-form-row">
                        <div class="sevo-form-group">
                            <label for="edit-tipo-evento">Tipo de Evento</label>
                            <input type="text" id="edit-tipo-evento" name="tipo_evento" 
                                   value="<?php echo esc_attr($evento ? $evento->tipo_titulo : ''); ?>" 
                                   class="sevo-form-control" readonly>
                        </div>
                        <div class="sevo-form-group">
                            <label for="edit-organizacao">Organização</label>
                            <input type="text" id="edit-organizacao" name="organizacao" 
                                   value="<?php echo esc_attr($evento ? $evento->organizacao_titulo : ''); ?>" 
                                   class="sevo-form-control" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Status da Inscrição -->
                <div class="sevo-form-section">
                    <h4>📋 Status da Inscrição</h4>
                    <div class="sevo-form-row">
                        <div class="sevo-form-group">
                            <label for="edit-status">Status *</label>
                            <select id="edit-status" name="status" class="sevo-form-control" required>
                                <?php foreach ($status_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($inscricao->status, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="sevo-form-help">Selecione o novo status da inscrição</small>
                        </div>
                        <div class="sevo-form-group">
                            <label for="edit-data-inscricao">Data da Inscrição</label>
                            <input type="text" id="edit-data-inscricao" name="created_at" 
                                   value="<?php echo esc_attr(date('d/m/Y H:i', strtotime($inscricao->created_at))); ?>" 
                                   class="sevo-form-control" readonly>
                        </div>
                    </div>
                </div>
                
                <!-- Observações -->
                <div class="sevo-form-section">
                    <h4>💬 Observações</h4>
                    <div class="sevo-form-group">
                        <label for="edit-observacoes">Observações</label>
                        <textarea id="edit-observacoes" name="observacoes" 
                                  class="sevo-form-control" rows="3" 
                                  placeholder="Observações sobre a inscrição..."><?php echo esc_textarea($inscricao->observacoes); ?></textarea>
                        <small class="sevo-form-help">Observações sobre esta inscrição</small>
                    </div>
                </div>
            <?php else: ?>
                <!-- Formulário para nova inscrição -->
                <div class="sevo-form-section">
                    <h4>👤 Selecionar Usuário</h4>
                    <div class="sevo-form-group">
                        <label for="new-user-id">Usuário *</label>
                        <select id="new-user-id" name="user_id" class="sevo-form-control" required>
                            <option value="">Selecione um usuário...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="sevo-form-section">
                    <h4>🎯 Selecionar Evento</h4>
                    <div class="sevo-form-group">
                        <label for="new-evento-id">Evento *</label>
                        <select id="new-evento-id" name="evento_id" class="sevo-form-control" required>
                            <option value="">Selecione um evento...</option>
                            <?php foreach ($eventos as $evento_option): ?>
                                <option value="<?php echo esc_attr($evento_option->id); ?>">
                                    <?php echo esc_html($evento_option->organizacao_titulo . ' - ' . $evento_option->tipo_titulo . ' - ' . $evento_option->titulo); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="sevo-form-section">
                    <h4>📋 Status e Observações</h4>
                    <div class="sevo-form-row">
                        <div class="sevo-form-group">
                            <label for="new-status">Status *</label>
                            <select id="new-status" name="status" class="sevo-form-control" required>
                                <?php foreach ($status_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected('pendente', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="sevo-form-group">
                        <label for="new-observacoes">Observações</label>
                        <textarea id="new-observacoes" name="observacoes" 
                                  class="sevo-form-control" rows="3" 
                                  placeholder="Observações sobre a inscrição..."></textarea>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Botões de Ação -->
            <div class="sevo-form-actions">
                <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoDashboard.closeEditModal()">
                    Cancelar
                </button>
                <button type="submit" class="sevo-btn sevo-btn-primary">
                    <?php echo $is_editing ? '💾 Salvar Alterações' : '➕ Criar Inscrição'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* === OVERLAY PARA FECHAR MODAL === */
.sevo-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    cursor: pointer;
}

.sevo-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    position: relative;
    z-index: 1000;
}

.sevo-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.sevo-modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.25rem;
}

.sevo-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.sevo-modal-close:hover {
    background: #e9ecef;
    color: #333;
}

.sevo-modal-body {
    padding: 20px;
}

.sevo-form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.sevo-form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.sevo-form-section h4 {
    margin: 0 0 15px 0;
    color: #495057;
    font-size: 1.1rem;
    font-weight: 600;
}

.sevo-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.sevo-form-group {
    display: flex;
    flex-direction: column;
}

.sevo-form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
    font-size: 0.9rem;
}

.sevo-form-control {
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.sevo-form-control:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
}

.sevo-form-control[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
}

.sevo-form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 4px;
}

.sevo-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.sevo-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.sevo-btn-primary {
    background: #007cba;
    color: white;
}

.sevo-btn-primary:hover {
    background: #005a87;
}

.sevo-btn-secondary {
    background: #6c757d;
    color: white;
}

.sevo-btn-secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .sevo-form-row {
        grid-template-columns: 1fr;
    }
    
    .sevo-modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .sevo-form-actions {
        flex-direction: column;
    }
}
</style>