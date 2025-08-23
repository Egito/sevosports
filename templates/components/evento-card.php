<?php
/**
 * Template para card de evento
 * 
 * @package Sevo_Eventos
 * @version 3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variável $evento deve estar disponível no contexto
if (!isset($evento)) {
    return;
}

// Formatação de datas
$data_inicio_formatada = $evento->data_inicio_evento ? date_i18n('d/m/Y', strtotime($evento->data_inicio_evento)) : 'N/A';
$data_fim_formatada = $evento->data_fim_evento ? date_i18n('d/m/Y', strtotime($evento->data_fim_evento)) : 'N/A';
$data_inicio_insc_formatada = $evento->data_inicio_inscricoes ? date_i18n('d/m/Y', strtotime($evento->data_inicio_inscricoes)) : 'N/A';
$data_fim_insc_formatada = $evento->data_fim_inscricoes ? date_i18n('d/m/Y', strtotime($evento->data_fim_inscricoes)) : 'N/A';

// Status das inscrições
$hoje = new DateTime();
$inicio_insc = $evento->data_inicio_inscricoes ? new DateTime($evento->data_inicio_inscricoes) : null;
$fim_insc = $evento->data_fim_inscricoes ? new DateTime($evento->data_fim_inscricoes) : null;

$status_inscricao = 'Fechadas';
if ($inicio_insc && $fim_insc) {
    if ($hoje < $inicio_insc) {
        $status_inscricao = 'Em breve';
    } elseif ($hoje >= $inicio_insc && $hoje <= $fim_insc) {
        $status_inscricao = 'Abertas';
    }
}

$status_class = strtolower(str_replace(' ', '-', $status_inscricao));
?>

<div class="sevo-evento-card" data-evento-id="<?php echo esc_attr($evento->id); ?>">
    <div class="sevo-card-header">
        <?php if ($evento->imagem_url): ?>
            <div class="sevo-card-image">
                <img src="<?php echo esc_url($evento->imagem_url); ?>" alt="<?php echo esc_attr($evento->titulo); ?>">
            </div>
        <?php endif; ?>
        
        <div class="sevo-card-status">
            <span class="sevo-status-badge sevo-status-<?php echo esc_attr($evento->status); ?>">
                <?php echo esc_html(ucfirst($evento->status)); ?>
            </span>
            <span class="sevo-inscricao-badge sevo-inscricao-<?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($status_inscricao); ?>
            </span>
        </div>
    </div>
    
    <div class="sevo-card-body">
        <h3 class="sevo-card-title"><?php echo esc_html($evento->titulo); ?></h3>
        
        <?php if ($evento->descricao): ?>
            <p class="sevo-card-description">
                <?php echo wp_trim_words(wp_strip_all_tags($evento->descricao), 20, '...'); ?>
            </p>
        <?php endif; ?>
        
        <div class="sevo-card-meta">
            <?php if (isset($evento->tipo_evento_titulo)): ?>
                <div class="sevo-meta-item">
                    <span class="sevo-meta-label">Tipo:</span>
                    <span class="sevo-meta-value"><?php echo esc_html($evento->tipo_evento_titulo); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($evento->organizacao_titulo)): ?>
                <div class="sevo-meta-item">
                    <span class="sevo-meta-label">Organização:</span>
                    <span class="sevo-meta-value"><?php echo esc_html($evento->organizacao_titulo); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="sevo-meta-item">
                <span class="sevo-meta-label">Data do Evento:</span>
                <span class="sevo-meta-value">
                    <?php if ($data_inicio_formatada === $data_fim_formatada): ?>
                        <?php echo esc_html($data_inicio_formatada); ?>
                    <?php else: ?>
                        <?php echo esc_html($data_inicio_formatada . ' a ' . $data_fim_formatada); ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="sevo-meta-item">
                <span class="sevo-meta-label">Inscrições:</span>
                <span class="sevo-meta-value">
                    <?php echo esc_html($data_inicio_insc_formatada . ' a ' . $data_fim_insc_formatada); ?>
                </span>
            </div>
            
            <?php if ($evento->vagas): ?>
                <div class="sevo-meta-item">
                    <span class="sevo-meta-label">Vagas:</span>
                    <span class="sevo-meta-value">
                        <?php 
                        $inscricoes_aceitas = isset($evento->inscricoes_aceitas) ? $evento->inscricoes_aceitas : 0;
                        echo esc_html($inscricoes_aceitas . '/' . $evento->vagas);
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="sevo-card-actions">
        <button type="button" class="sevo-btn sevo-btn-secondary" 
                onclick="SevoEventosDashboard.openEventModal(<?php echo esc_attr($evento->id); ?>)">
            <i class="dashicons dashicons-visibility"></i> Visualizar
        </button>
        
        <button type="button" class="sevo-btn sevo-btn-primary" 
                onclick="SevoEventosDashboard.editEvent(<?php echo esc_attr($evento->id); ?>)">
            <i class="dashicons dashicons-edit"></i> Editar
        </button>
        
        <button type="button" class="sevo-btn sevo-btn-danger" 
                onclick="alert('Função de exclusão em desenvolvimento')">
            <i class="dashicons dashicons-trash"></i> Excluir
        </button>
    </div>
</div>