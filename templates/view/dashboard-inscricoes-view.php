<?php
/**
 * Template para o Dashboard de Inscrições
 * Este template é incluído pelo shortcode [sevo_dashboard_inscricoes].
 */

if (!defined('ABSPATH')) {
    exit;
}

$can_manage_all = sevo_check_user_permission('manage_inscricoes');
$is_super_admin = is_super_admin();
$current_user = wp_get_current_user();
?>

<div class="sevo-dashboard-inscricoes" id="sevo-dashboard-inscricoes">
    <!-- Cards de Estatísticas -->
    <div class="sevo-dashboard-stats" id="sevo-dashboard-stats">
        <div class="sevo-stat-card sevo-stat-total">
            <div class="sevo-stat-icon">📊</div>
            <div class="sevo-stat-content">
                <div class="sevo-stat-number" id="stat-total">-</div>
                <div class="sevo-stat-label">Total de Inscrições</div>
            </div>
        </div>
        
        <div class="sevo-stat-card sevo-stat-solicitada">
            <div class="sevo-stat-icon">📝</div>
            <div class="sevo-stat-content">
                <div class="sevo-stat-number" id="stat-solicitada">-</div>
                <div class="sevo-stat-label">Solicitadas</div>
            </div>
        </div>
        
        <div class="sevo-stat-card sevo-stat-approved">
            <div class="sevo-stat-icon">✅</div>
            <div class="sevo-stat-content">
                <div class="sevo-stat-number" id="stat-approved">-</div>
                <div class="sevo-stat-label">Aprovadas</div>
            </div>
        </div>
        
        <div class="sevo-stat-card sevo-stat-rejected">
            <div class="sevo-stat-icon">❌</div>
            <div class="sevo-stat-content">
                <div class="sevo-stat-number" id="stat-rejected">-</div>
                <div class="sevo-stat-label">Reprovadas</div>
            </div>
        </div>
        
        <div class="sevo-stat-card sevo-stat-cancelada">
            <div class="sevo-stat-icon">🚫</div>
            <div class="sevo-stat-content">
                <div class="sevo-stat-number" id="stat-canceladas">-</div>
                <div class="sevo-stat-label">Canceladas</div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="sevo-dashboard-filters">
        <div class="sevo-filters-header">
            <h3>Filtros</h3>
            <button type="button" class="sevo-filters-toggle" id="sevo-filters-toggle">
                <span class="sevo-toggle-text">Mostrar Filtros</span>
                <span class="sevo-toggle-icon">▼</span>
            </button>
        </div>
        
        <div class="sevo-filters-content" id="sevo-filters-content" style="display: none;">
            <div class="sevo-filters-row">
                <div class="sevo-filter-group">
                    <label for="filter-evento">Evento:</label>
                    <select id="filter-evento" name="evento_id">
                        <option value="">Todos os eventos</option>
                    </select>
                </div>
                
                <div class="sevo-filter-group">
                    <label for="filter-status">Status:</label>
                    <select id="filter-status" name="status">
                        <option value="">Todos os status</option>
                        <option value="solicitada">Solicitada</option>
                        <option value="aceita">Aceita</option>
                        <option value="rejeitada">Rejeitada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                
                <div class="sevo-filter-group">
                    <label for="filter-ano">Ano:</label>
                    <select id="filter-ano" name="ano">
                        <option value="">Todos os anos</option>
                    </select>
                </div>
                
                <div class="sevo-filter-group">
                    <label for="filter-mes">Mês:</label>
                    <select id="filter-mes" name="mes">
                        <option value="">Todos os meses</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>
            </div>
            
            <?php if ($can_manage_all): ?>
            <div class="sevo-filters-row">
                <div class="sevo-filter-group">
                    <label for="filter-organizacao">Organização:</label>
                    <select id="filter-organizacao" name="organizacao_id">
                        <option value="">Todas as organizações</option>
                    </select>
                </div>
                
                <div class="sevo-filter-group">
                    <label for="filter-tipo-evento">Tipo de Evento:</label>
                    <select id="filter-tipo-evento" name="tipo_evento_id">
                        <option value="">Todos os tipos</option>
                    </select>
                </div>
                
                <div class="sevo-filter-group">
                    <label for="filter-usuario">Usuário:</label>
                    <input type="text" id="filter-usuario" name="usuario" placeholder="Nome ou email do usuário">
                </div>
                
                <div class="sevo-filter-group">
                    <!-- Espaço para alinhamento -->
                </div>
            </div>
            <?php endif; ?>
            
            <div class="sevo-filters-actions">
                <button type="button" class="sevo-btn sevo-btn-primary" id="apply-filters">Aplicar Filtros</button>
                <button type="button" class="sevo-btn sevo-btn-secondary" id="clear-filters">Limpar Filtros</button>
            </div>
        </div>
    </div>

    <!-- Tabela de Inscrições -->
    <div class="sevo-table-container">
        <div class="sevo-table-loading" id="table-loading">
            <div class="sevo-spinner"></div>
            <p>Carregando inscrições...</p>
        </div>
        
        <table class="sevo-inscricoes-table" id="inscricoes-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="inscricao_id">ID</th>
                    <?php if ($can_manage_all): ?>
                        <th class="sortable" data-sort="usuario_nome">Usuário</th>
                    <?php endif; ?>
                    <th class="sortable" data-sort="evento_nome">Evento</th>
                    <th class="sortable" data-sort="evento_data">Data do Evento</th>
                    <th class="sortable" data-sort="organizacao_nome">Organização</th>
                    <th class="sortable" data-sort="data_inscricao">Data da Inscrição</th>
                    <th class="no-sort">Ações</th>
                </tr>
            </thead>
            <tbody id="inscricoes-tbody">
                <!-- Conteúdo será carregado via AJAX -->
            </tbody>
        </table>
        
        <div class="sevo-no-results" id="no-results" style="display: none;">
            <p>Nenhuma inscrição encontrada com os filtros aplicados.</p>
        </div>
    </div>

    <!-- Indicador de carregamento para scroll infinito -->
    <div class="sevo-infinite-loading" id="infinite-loading" style="display: none;">
        <div class="sevo-spinner"></div>
        <p>Carregando mais inscrições...</p>
    </div>
    
    <!-- Indicador de fim da lista -->
    <div class="sevo-end-of-list" id="end-of-list" style="display: none;">
        <p>Todas as inscrições foram carregadas.</p>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="sevo-modal" id="confirmation-modal" style="display: none;">
    <div class="sevo-modal-overlay"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <h3 id="modal-title">Confirmar Ação</h3>
            <button type="button" class="sevo-modal-close" id="modal-close">×</button>
        </div>
        <div class="sevo-modal-content">
            <p id="modal-message">Tem certeza que deseja realizar esta ação?</p>
            <div class="sevo-modal-input" id="modal-input-container" style="display: none;">
                <label for="modal-input">Motivo (opcional):</label>
                <textarea id="modal-input" rows="3" placeholder="Digite o motivo..."></textarea>
            </div>
        </div>
        <div class="sevo-modal-footer">
            <button type="button" class="sevo-btn sevo-btn-secondary" id="modal-cancel">Cancelar</button>
            <button type="button" class="sevo-btn sevo-btn-primary" id="modal-confirm">Confirmar</button>
        </div>
    </div>
</div>



<script type="text/template" id="inscricao-row-template">
    <tr data-inscricao-id="{{inscricao_id}}">
        <td class="inscricao-id">{{inscricao_id}}</td>
        <?php if ($can_manage_all): ?>
            <td class="usuario-nome">{{usuario_nome}}</td>
        <?php endif; ?>
        <td class="evento-nome">{{evento_nome}}</td>
        <td class="evento-data">{{evento_data_formatted}}</td>
        <td class="organizacao-nome">{{organizacao_nome}}</td>
        <td class="data-inscricao">{{data_inscricao_formatted}}</td>
        <td class="acoes">
            <?php if ($can_manage_all): ?>
                <div class="action-buttons">
                    {{#if_status_solicitada}}
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-success approve-btn" 
                                data-inscricao-id="{{inscricao_id}}" title="Aprovar">
                            ✓
                        </button>
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger reject-btn" 
                                data-inscricao-id="{{inscricao_id}}" title="Reprovar">
                            ✗
                        </button>
                    {{/if_status_solicitada}}
                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                            data-evento-id="{{evento_id}}" title="Ver Detalhes do Evento">
                        👁
                    </button>
                    <?php if ($is_super_admin): ?>
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-warning edit-inscricao-btn" 
                                data-inscricao-id="{{inscricao_id}}" title="Editar Inscrição">
                            ✏️
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                        data-evento-id="{{evento_id}}" title="Ver Evento">
                    Ver Evento
                </button>
            <?php endif; ?>
        </td>
    </tr>
</script>

<!-- Modal do Evento -->
<div id="sevo-event-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-overlay"></div>
    <div class="sevo-modal-container">
        <div class="sevo-modal-header">
            <h2>Detalhes do Evento</h2>
            <button type="button" class="sevo-modal-close" onclick="SevoDashboard.closeEventModal()">&times;</button>
        </div>
        <div class="sevo-modal-loading" style="display: none;">
            <div class="sevo-loading-spinner"></div>
            <p>Carregando...</p>
        </div>
        <div class="sevo-modal-content"></div>
    </div>
</div>

<!-- Modal de Edição de Inscrição -->
<?php if ($is_super_admin): ?>
<div id="sevo-edit-inscricao-modal" class="sevo-modal" style="display: none;">
    <div class="sevo-modal-backdrop" onclick="SevoDashboard.closeEditModal()"></div>
    <div class="sevo-modal-dialog">
        <div class="sevo-loading" id="sevo-edit-loading">
            <div class="sevo-spinner"></div>
            <p>Carregando dados da inscrição...</p>
        </div>
        <div class="sevo-modal-content-area" id="sevo-edit-content" style="display: none;"></div>
    </div>
</div>
<?php endif; ?>