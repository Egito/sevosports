<?php
/**
 * Template do modal para visualizar detalhes de eventos.
 * Versão atualizada para usar tabelas customizadas.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verifica se o evento foi passado
if (!isset($evento) || !$evento) {
    echo '<p>Evento não encontrado.</p>';
    return;
}

// Busca dados do tipo de evento e organização
global $wpdb;
$tipo_evento_data = null;
$organizacao_data = null;

if ($evento->tipo_evento_id) {
    $tipo_evento_model = new Sevo_Tipo_Evento_Model();
    $tipo_evento_data = $tipo_evento_model->get_with_organizacao($evento->tipo_evento_id);
}

// Formatação de datas
$data_criacao_formatada = $evento->data_criacao ? date_i18n('d/m/Y H:i', strtotime($evento->data_criacao)) : 'N/A';
$data_inicio_insc_formatada = $evento->data_inicio_inscricao ? date_i18n('d/m/Y H:i', strtotime($evento->data_inicio_inscricao)) : 'N/A';
$data_fim_insc_formatada = $evento->data_fim_inscricao ? date_i18n('d/m/Y H:i', strtotime($evento->data_fim_inscricao)) : 'N/A';
$data_evento_formatada = $evento->data_evento ? date_i18n('d/m/Y', strtotime($evento->data_evento)) : 'N/A';
$hora_evento_formatada = $evento->hora_evento ? date_i18n('H:i', strtotime($evento->hora_evento)) : 'N/A';

// Status formatado
$status_labels = [
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
    'cancelado' => 'Cancelado'
];
$status_formatado = $status_labels[$evento->status] ?? 'Desconhecido';
$status_class = 'status-' . $evento->status;

// Busca inscrições do evento
$inscricao_model = new Sevo_Inscricao_Model();
$inscricoes = $inscricao_model->get_by_evento($evento->id);

$total_inscricoes = count($inscricoes);

// Verifica se o usuário atual tem inscrição
$user_id = get_current_user_id();
$user_inscricao = null;
$user_inscricao_status = null;

if ($user_id) {
    foreach ($inscricoes as $inscricao) {
        if ($inscricao->user_id == $user_id) {
            $user_inscricao = $inscricao;
            $user_inscricao_status = $inscricao->status;
            break;
        }
    }
}

// Lógica de status da inscrição
$hoje = new DateTime();
$inicio_insc = $evento->data_inicio_inscricao ? new DateTime($evento->data_inicio_inscricao) : null;
$fim_insc = $evento->data_fim_inscricao ? new DateTime($evento->data_fim_inscricao) : null;
$status_inscricao = ($inicio_insc && $fim_insc && $hoje >= $inicio_insc && $hoje <= $fim_insc) ? 'abertas' : 'fechadas';

// Verifica permissões
$can_edit = sevo_check_user_permission('edit_evento');
$can_inscribe = is_user_logged_in() && $status_inscricao === 'abertas';
?>

<div class="sevo-modal-evento-view">
    <!-- Cabeçalho do Modal -->
    <div class="sevo-modal-header-evento">
        <div class="header-left">
            <h3 class="sevo-modal-title"><?php echo esc_html($evento->titulo); ?></h3>
            
            <div class="sevo-modal-status-bar">
                <?php if ($status_inscricao === 'abertas'): ?>
                    <span class="sevo-status-badge status-ativo">Inscrições Abertas</span>
                <?php else: ?>
                    <span class="sevo-status-badge status-inativo">Inscrições Fechadas</span>
                <?php endif; ?>
                
                <?php if ($user_inscricao_status): ?>
                    <span class="sevo-user-status status-<?php echo esc_attr($user_inscricao_status); ?>">
                        <?php 
                        switch($user_inscricao_status) {
                            case 'aceita': echo 'Inscrito'; break;
                            case 'solicitada': echo 'Aguardando Aprovação'; break;
                            case 'rejeitada': echo 'Inscrição Rejeitada'; break;
                            case 'cancelada': echo 'Inscrição Cancelada'; break;
                            default: echo 'Status: ' . $user_inscricao_status;
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($evento->imagem_url): ?>
            <img src="<?php echo esc_url($evento->imagem_url); ?>" alt="<?php echo esc_attr($evento->titulo); ?>" class="sevo-modal-image">
        <?php endif; ?>
    </div>

    <!-- Corpo do Modal -->
    <div class="sevo-modal-body-evento">
        <!-- Container Superior: Dados do Evento e Lista de Inscrições -->
        <div class="sevo-modal-top-container">
            <!-- Lado Esquerdo: Dados do Evento -->
            <div class="sevo-modal-evento-dados">
                <h4><i class="fas fa-info-circle"></i> Informações do Evento</h4>
                
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-building"></i> Organização:</strong>
                    <span><?php echo esc_html($tipo_evento_data ? $tipo_evento_data->organizacao_titulo : 'N/D'); ?></span>
                </div>
                
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-groups"></i> Inscrições:</strong>
                    <span><?php echo esc_html($total_inscricoes); ?> / <?php echo $evento->vagas ? esc_html($evento->vagas) : '∞'; ?></span>
                </div>
                
                <?php if ($evento->local): ?>
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-location"></i> Local:</strong>
                    <span><?php echo esc_html($evento->local); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($evento->data_inicio_inscricao && $evento->data_fim_inscricao): ?>
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-calendar-alt"></i> Período de Inscrições:</strong>
                    <span><?php echo $data_inicio_insc_formatada; ?> - <?php echo $data_fim_insc_formatada; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($evento->data_evento): ?>
                <div class="sevo-info-item">
                    <strong><i class="dashicons dashicons-flag"></i> Data do Evento:</strong>
                    <span><?php echo $data_evento_formatada; ?> às <?php echo $hora_evento_formatada; ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Lado Direito: Lista de Inscrições -->
            <div class="sevo-modal-inscricoes-lista">
                <h4><i class="fas fa-users"></i> Lista de Inscritos</h4>
                <div class="sevo-inscricoes-container">
                    <?php if (!empty($inscricoes)) : ?>
                        <div class="sevo-inscricoes-list">
                            <?php foreach ($inscricoes as $inscricao) : ?>
                                <?php 
                                $user_name = $inscricao->user_name ?: 'Usuário não encontrado';
                                
                                // Define a classe CSS baseada no status
                                $status_class = '';
                                $status_text = '';
                                switch($inscricao->status) {
                                    case 'confirmada':
                                        $status_class = 'status-aceita';
                                        $status_text = 'Confirmada';
                                        break;
                                    case 'pendente':
                                        $status_class = 'status-solicitada';
                                        $status_text = 'Pendente';
                                        break;
                                    case 'rejeitada':
                                        $status_class = 'status-rejeitada';
                                        $status_text = 'Rejeitada';
                                        break;
                                    case 'cancelada':
                                        $status_class = 'status-cancelada';
                                        $status_text = 'Cancelada';
                                        break;
                                    default:
                                        $status_class = 'status-indefinido';
                                        $status_text = ucfirst($inscricao->status ?: 'Indefinido');
                                }
                                ?>
                                <div class="sevo-inscricao-item">
                                    <span class="sevo-inscricao-nome"><?php echo esc_html($user_name); ?></span>
                                    <span class="sevo-inscricao-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="sevo-no-items">Nenhuma inscrição encontrada para este evento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Container Inferior: Descrição/Regras -->
        <div class="sevo-modal-bottom-container">
            <div class="sevo-modal-description-content">
                <?php if ($evento->descricao): ?>
                    <div class="sevo-description-section">
                        <h5>Descrição do Evento</h5>
                        <?php echo wp_kses_post(wpautop($evento->descricao)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($evento->regras): ?>
                    <div class="sevo-regras-section">
                        <h5>Regras do Evento</h5>
                        <?php echo wp_kses_post(wpautop($evento->regras)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
