<?php
/**
 * Template do modal para visualizar detalhes de inscrições.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar se os dados da inscrição foram passados
if (!isset($inscricao) || !$inscricao) {
    echo '<p>Inscrição não encontrada.</p>';
    return;
}

// Buscar dados relacionados usando modelos
$evento_model = new Sevo_Evento_Model();
$evento = $evento_model->get_with_relations($inscricao->evento_id);
$evento = $evento ? $evento[0] : null; // get_with_relations retorna array

$usuario = get_userdata($inscricao->user_id);

if (!$evento || !$usuario) {
    echo '<p>Dados relacionados não encontrados.</p>';
    return;
}

// Status formatado
$status_labels = [
    'pendente' => 'Pendente',
    'confirmada' => 'Confirmada',
    'cancelada' => 'Cancelada',
    'rejeitada' => 'Rejeitada'
];
$status_formatado = $status_labels[$inscricao->status] ?? 'Desconhecido';
$status_class = 'status-' . $inscricao->status;

// Formatação de datas
$data_inscricao_formatada = $inscricao->created_at ? date_i18n('d/m/Y H:i', strtotime($inscricao->created_at)) : 'N/A';
$data_evento_formatada = $evento->data_evento ? date_i18n('d/m/Y', strtotime($evento->data_evento)) : 'N/A';
$hora_evento_formatada = $evento->hora_evento ? date_i18n('H:i', strtotime($evento->hora_evento)) : 'N/A';
?>

<div class="sevo-modal-content">
    <div class="sevo-modal-header">
        <h3>📋 Detalhes da Inscrição #<?php echo esc_html($inscricao->id); ?></h3>
        <button type="button" class="sevo-modal-close" onclick="SevoInscricoesAdmin.closeModal()">&times;</button>
    </div>
    
    <div class="sevo-modal-body">
        <!-- Status da Inscrição -->
        <div class="sevo-status-header">
            <span class="sevo-status-badge <?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($status_formatado); ?>
            </span>
            <span class="sevo-date-info">
                Inscrito em: <?php echo esc_html($data_inscricao_formatada); ?>
            </span>
        </div>
        
        <!-- Informações do Usuário -->
        <div class="sevo-info-section">
            <h4>👤 Informações do Usuário</h4>
            <div class="sevo-info-grid">
                <div class="sevo-info-item">
                    <strong>Nome:</strong>
                    <span><?php echo esc_html($usuario->display_name); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Email:</strong>
                    <span><?php echo esc_html($usuario->user_email); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Login:</strong>
                    <span><?php echo esc_html($usuario->user_login); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Data de Cadastro:</strong>
                    <span><?php echo date_i18n('d/m/Y', strtotime($usuario->user_registered)); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Informações do Evento -->
        <div class="sevo-info-section">
            <h4>🎯 Informações do Evento</h4>
            <div class="sevo-info-grid">
                <div class="sevo-info-item">
                    <strong>Nome do Evento:</strong>
                    <span><?php echo esc_html($evento->titulo); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Tipo de Evento:</strong>
                    <span><?php echo esc_html($evento->tipo_titulo); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Organização:</strong>
                    <span><?php echo esc_html($evento->organizacao_titulo); ?></span>
                </div>
                <div class="sevo-info-item">
                    <strong>Data do Evento:</strong>
                    <span><?php echo esc_html($data_evento_formatada . ' às ' . $hora_evento_formatada); ?></span>
                </div>
                <?php if ($evento->local_evento): ?>
                <div class="sevo-info-item sevo-info-full">
                    <strong>Local:</strong>
                    <span><?php echo esc_html($evento->local_evento); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($evento->max_participantes): ?>
                <div class="sevo-info-item">
                    <strong>Máximo de Participantes:</strong>
                    <span><?php echo esc_html($evento->max_participantes); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Observações -->
        <?php if ($inscricao->observacoes): ?>
        <div class="sevo-info-section">
            <h4>💬 Observações</h4>
            <div class="sevo-observacoes">
                <?php echo wp_kses_post(wpautop($inscricao->observacoes)); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Descrição do Evento -->
        <?php if ($evento->descricao): ?>
        <div class="sevo-info-section">
            <h4>📝 Descrição do Evento</h4>
            <div class="sevo-evento-descricao">
                <?php echo wp_kses_post(wpautop($evento->descricao)); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="sevo-modal-footer">
        <button type="button" class="sevo-btn sevo-btn-secondary" onclick="SevoInscricoesAdmin.closeModal()">
            Fechar
        </button>
        <button type="button" class="sevo-btn sevo-btn-primary" onclick="SevoInscricoesAdmin.editInscricao(<?php echo esc_attr($inscricao->id); ?>)">
            Editar
        </button>
    </div>
</div>

<style>
.sevo-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.sevo-status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 12px;
    text-transform: uppercase;
}

.sevo-status-badge.status-pendente {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.sevo-status-badge.status-confirmada {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.sevo-status-badge.status-cancelada {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sevo-status-badge.status-rejeitada {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sevo-date-info {
    color: #666;
    font-size: 14px;
}

.sevo-info-section {
    margin-bottom: 25px;
}

.sevo-info-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 5px;
}

.sevo-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.sevo-info-item {
    display: flex;
    flex-direction: column;
}

.sevo-info-item.sevo-info-full {
    grid-column: 1 / -1;
}

.sevo-info-item strong {
    color: #333;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 3px;
}

.sevo-info-item span {
    color: #555;
    font-size: 14px;
}

.sevo-observacoes,
.sevo-evento-descricao {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #0073aa;
}

.sevo-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    background: #f1f1f1;
    border-top: 1px solid #ddd;
}

.sevo-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.sevo-btn-primary {
    background: #0073aa;
    color: white;
}

.sevo-btn-primary:hover {
    background: #005a87;
}

.sevo-btn-secondary {
    background: #f1f1f1;
    color: #333;
    border: 1px solid #ddd;
}

.sevo-btn-secondary:hover {
    background: #e1e1e1;
}

@media (max-width: 768px) {
    .sevo-info-grid {
        grid-template-columns: 1fr;
    }
    
    .sevo-status-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>