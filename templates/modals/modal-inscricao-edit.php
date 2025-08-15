<?php
/**
 * Template do Modal de Edição de Inscrição
 * Este template é carregado via AJAX para editar uma inscrição
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se os dados da inscrição foram passados
if (!isset($inscricao) || !$inscricao) {
    // Usar toaster para exibir erro e fechar modal
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

// Buscar dados relacionados
$evento = get_post($inscricao->evento_id);
$usuario = get_userdata($inscricao->usuario_id);
$evento_data = get_post_meta($inscricao->evento_id, '_sevo_evento_data', true);
$evento_tipo_id = get_post_meta($inscricao->evento_id, '_sevo_evento_tipo_evento_id', true);
$tipo_evento = get_post($evento_tipo_id);

// Buscar organização
$organizacao_id = '';
$organizacao_nome = '';
if ($tipo_evento) {
    $organizacao_id = get_post_meta($tipo_evento->ID, '_sevo_tipo_evento_organizacao_id', true);
    if ($organizacao_id) {
        $organizacao = get_post($organizacao_id);
        $organizacao_nome = $organizacao ? $organizacao->post_title : '';
    }
}

// Status possíveis conforme PRD
$status_options = array(
    'solicitada' => 'Solicitada',
    'aceita' => 'Aceita',
    'rejeitada' => 'Rejeitada',
    'cancelada' => 'Cancelada'
);
?>

<div class="sevo-modal-content">
    <div class="sevo-modal-header">
        <h3>Editar Inscrição #<?php echo esc_html($inscricao->inscricao_id); ?></h3>
        <button type="button" class="sevo-modal-close" onclick="SevoDashboard.closeEditModal()">&times;</button>
    </div>
    
    <div class="sevo-modal-body">
        <form id="sevo-edit-inscricao-form" class="sevo-form">
            <input type="hidden" name="inscricao_id" value="<?php echo esc_attr($inscricao->inscricao_id); ?>">
            
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
                               value="<?php echo esc_attr($evento ? $evento->post_title : ''); ?>" 
                               class="sevo-form-control" readonly>
                    </div>
                    <div class="sevo-form-group">
                        <label for="edit-evento-data">Data do Evento</label>
                        <input type="text" id="edit-evento-data" name="evento_data" 
                               value="<?php echo esc_attr($evento_data ? date('d/m/Y', strtotime($evento_data)) : ''); ?>" 
                               class="sevo-form-control" readonly>
                    </div>
                </div>
                <div class="sevo-form-row">
                    <div class="sevo-form-group">
                        <label for="edit-tipo-evento">Tipo de Evento</label>
                        <input type="text" id="edit-tipo-evento" name="tipo_evento" 
                               value="<?php echo esc_attr($tipo_evento ? $tipo_evento->post_title : ''); ?>" 
                               class="sevo-form-control" readonly>
                    </div>
                    <div class="sevo-form-group">
                        <label for="edit-organizacao">Organização</label>
                        <input type="text" id="edit-organizacao" name="organizacao" 
                               value="<?php echo esc_attr($organizacao_nome); ?>" 
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
                        <input type="text" id="edit-data-inscricao" name="data_inscricao" 
                               value="<?php echo esc_attr(date('d/m/Y H:i', strtotime($inscricao->data_inscricao))); ?>" 
                               class="sevo-form-control" readonly>
                    </div>
                </div>
            </div>
            
            <!-- Comentário/Observação -->
            <div class="sevo-form-section">
                <h4>💬 Comentário da Alteração</h4>
                <div class="sevo-form-group">
                    <label for="edit-comentario">Comentário (opcional)</label>
                    <textarea id="edit-comentario" name="comentario" 
                              class="sevo-form-control" rows="3" 
                              placeholder="Adicione um comentário sobre esta alteração..."></textarea>
                    <small class="sevo-form-help">Este comentário será registrado no histórico da inscrição</small>
                </div>
            </div>
            
            <!-- Botões de Ação -->
            <div class="sevo-form-actions">
                <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoDashboard.closeEditModal()">
                    Cancelar
                </button>
                <button type="submit" class="sevo-btn sevo-btn-primary">
                    💾 Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.sevo-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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