/**
 * JavaScript para o Dashboard de Inscrições
 * Plugin Sevo Eventos
 */

(function($) {
    'use strict';

    // Objeto principal do dashboard
    const SevoDashboard = {
        // Configurações
        config: {
            currentPage: 1,
            itemsPerPage: 50, // Aumentado para melhor performance
            totalItems: 0,
            hasMoreItems: true,
            sortBy: 'created_at',
            sortOrder: 'desc',
            isLoading: false,
            isLoadingMore: false,
            allInscricoes: [],
            filters: {
                evento_id: '',
                status: '',
                periodo: '', // Formato: YYYY-MM
                organizacao_id: '',
                tipo_evento_id: '',
                usuario: ''
            }
        },

        // Cache de elementos DOM
        elements: {},

        // Inicialização
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadInitialData();
            this.setupFilters();
        },

        // Cache dos elementos DOM
        cacheElements: function() {
            this.elements = {
                container: $('.sevo-dashboard-wrapper'),
                filtersToggle: $('#sevo-filters-toggle'),
                filtersContent: $('#sevo-filters-content'),
                filterForm: $('.sevo-filters-content'),
                tableContainer: $('.sevo-table-container'),
                table: $('#inscricoes-table'),
                tableBody: $('#inscricoes-tbody'),
                tableLoading: $('#table-loading'),
                noResults: $('#no-results'),
                infiniteLoading: $('#infinite-loading'),
                endOfList: $('#end-of-list'),

                modal: $('#confirmation-modal'),
                modalConfirm: $('#modal-confirm'),
                modalCancel: $('#modal-cancel'),
                modalTitle: $('#modal-title'),
                modalMessage: $('#modal-message'),
                modalInput: $('#modal-input-container'),

                stats: {
                    total: $('#stat-total'),
                    solicitadas: $('#stat-solicitadas'),
                    approved: $('#stat-approved'),
                    rejected: $('#stat-rejected'),
                    canceladas: $('#stat-canceladas')
                }
            };
        },

        // Vinculação de eventos
        bindEvents: function() {
            // Filtros simplificados - aplicação automática
            $('#filter-usuario').on('input', this.debounce(this.handleFilterChange.bind(this), 500));
            $('#filter-organizacao').on('change', this.handleFilterChange.bind(this));
            $('#filter-tipo-evento').on('change', this.handleFilterChange.bind(this));
            $('#filter-evento').on('change', this.handleFilterChange.bind(this));
            $('#filter-status').on('change', this.handleFilterChange.bind(this));
            $('#filter-periodo').on('change', this.handleFilterChange.bind(this)); // Período único YYYY-MM
            
            // Limpar filtros
            $('#clear-filters').on('click', this.resetFilters.bind(this));

            // Ações de cancelamento próprio
            $(document).on('click', '.cancel-own-btn', this.handleCancelOwn.bind(this));



            // Ordenação da tabela
            $(document).on('click', '#inscricoes-table th.sortable', this.handleSort.bind(this));

            // Ações da tabela
            $(document).on('click', '.approve-btn', this.handleApprove.bind(this));
            $(document).on('click', '.reject-btn', this.handleReject.bind(this));
            $(document).on('click', '.view-event-btn', this.handleViewEvent.bind(this));
            $(document).on('click', '.edit-inscricao-btn', this.handleEdit.bind(this));

            // Scroll infinito
            $(window).on('scroll', this.handleScroll.bind(this));

            // Modal
            this.elements.modalConfirm.on('click', this.confirmAction.bind(this));
            this.elements.modalCancel.on('click', this.closeModal.bind(this));
            $('.sevo-modal-close').on('click', this.closeModal.bind(this));
            $(document).on('click', '.sevo-modal-overlay', this.closeModal.bind(this));

            // Modal de edição
            $(document).on('click', '#save-edit-btn', this.saveEdit.bind(this));
            $(document).on('click', '#cancel-edit-btn', this.closeEditModal.bind(this));
            $(document).on('click', '.sevo-modal-close', this.closeEditModal.bind(this));
            $(document).on('click', '.sevo-modal-backdrop', this.closeEditModal.bind(this));

            // Toast
            $(document).on('click', '.sevo-toast-close', this.closeToast.bind(this));

            // Teclas de atalho
            $(document).on('keydown', this.handleKeyboard.bind(this));
        },

        // Configurar filtros iniciais
        setupFilters: function() {
            // Carregar opções dos filtros
            this.loadFilterOptions();
        },

        // Carregar opções dos filtros
        loadFilterOptions: function() {
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_get_filter_options',
                    nonce: sevoDashboardInscricoes.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populateFilterOptions(response.data);
                    }
                },
                error: () => {
                    console.error('Erro ao carregar opções de filtros');
                }
            });
        },

        // Popular opções dos filtros
        populateFilterOptions: function(options) {
            // Eventos
            const $eventoSelect = $('#filter-evento');
            $eventoSelect.empty().append('<option value="">Todos os eventos</option>');
            if (options.eventos) {
                options.eventos.forEach(evento => {
                    $eventoSelect.append(`<option value="${evento.id}">${this.escapeHtml(evento.evento_titulo)}</option>`);
                });
            }
            
            // Organizações
            const $orgSelect = $('#filter-organizacao');
            $orgSelect.empty().append('<option value="">Todas as organizações</option>');
            if (options.organizacoes) {
                options.organizacoes.forEach(org => {
                    $orgSelect.append(`<option value="${org.id}">${this.escapeHtml(org.organizacao_titulo)}</option>`);
                });
            }
            
            // Tipos de evento
            const $tipoSelect = $('#filter-tipo-evento');
            $tipoSelect.empty().append('<option value="">Todos os tipos</option>');
            if (options.tipos_evento) {
                options.tipos_evento.forEach(tipo => {
                    $tipoSelect.append(`<option value="${tipo.id}">${this.escapeHtml(tipo.tipo_evento_titulo)}</option>`);
                });
            }
            
            // Períodos disponíveis
            const $periodoSelect = $('#filter-periodo');
            $periodoSelect.empty().append('<option value="">Todos os períodos</option>');
            if (options.periodos) {
                options.periodos.forEach(periodo => {
                    const [ano, mes] = periodo.periodo.split('-');
                    const meses = {
                        '01': 'Jan', '02': 'Fev', '03': 'Mar', '04': 'Abr',
                        '05': 'Mai', '06': 'Jun', '07': 'Jul', '08': 'Ago',
                        '09': 'Set', '10': 'Out', '11': 'Nov', '12': 'Dez'
                    };
                    const label = `${meses[mes]} ${ano}`;
                    $periodoSelect.append(`<option value="${periodo.periodo}">${label}</option>`);
                });
            }
        },

        // Debounce helper para evitar muitas requisições
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Manipular mudança de filtros
        handleFilterChange: function(e) {
            this.applyFilters();
        },

        // Carregar dados iniciais
        loadInitialData: function() {
            console.log('loadInitialData chamada');
            this.loadStats();
            this.loadInscricoes();
        },

        // Aplicar filtros
        applyFilters: function() {
            // Coletar valores dos filtros
            this.config.filters = {
                evento_id: $('#filter-evento').val() || '',
                status: $('#filter-status').val() || '',
                periodo: $('#filter-periodo').val() || '', // YYYY-MM
                organizacao_id: $('#filter-organizacao').val() || '',
                tipo_evento_id: $('#filter-tipo-evento').val() || '',
                usuario: $('#filter-usuario').val().trim() || ''
            };

            // Remover filtros vazios
            Object.keys(this.config.filters).forEach(key => {
                if (!this.config.filters[key]) {
                    delete this.config.filters[key];
                }
            });

            console.log('Filtros aplicados:', this.config.filters);

            // Resetar scroll infinito
            this.resetPagination();
            this.loadInscricoes(true);
            this.loadStats();
        },

        // Resetar paginação
        resetPagination: function() {
            this.config.currentPage = 1;
            this.config.hasMoreItems = true;
            this.config.allInscricoes = [];
            this.elements.endOfList?.hide();
        },

        // Resetar filtros
        resetFilters: function(e) {
            e.preventDefault();
            
            // Limpar campos de filtro
            $('#filter-evento').val('');
            $('#filter-status').val('');
            $('#filter-periodo').val('');
            $('#filter-organizacao').val('');
            $('#filter-tipo-evento').val('');
            $('#filter-usuario').val('');
            
            // Limpar objeto de filtros
            this.config.filters = {};

            // Resetar paginação e recarregar
            this.resetPagination();
            this.loadInscricoes(true);
            this.loadStats();
        },



        // Refresh dos dados
        refreshData: function() {
            this.config.currentPage = 1;
            this.config.hasMoreItems = true;
            this.config.allInscricoes = [];
            this.loadInscricoes(true);
            this.loadStats();
            SevoToaster.showSuccess('Dados atualizados com sucesso!');
        },

        // Manipular ordenação
        handleSort: function(e) {
            const $th = $(e.currentTarget);
            const sortBy = $th.data('sort');

            if (this.config.sortBy === sortBy) {
                this.config.sortOrder = this.config.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.config.sortBy = sortBy;
                this.config.sortOrder = 'asc';
            }

            // Atualizar UI
            $('#inscricoes-table th').removeClass('sort-asc sort-desc');
            $th.addClass('sort-' + this.config.sortOrder);

            // Resetar e recarregar
            this.resetPagination();
            this.loadInscricoes(true);
        },



        // Carregar estatísticas
        loadStats: function() {
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_get_stats',
                    nonce: sevoDashboardInscricoes.nonce,
                    filters: this.config.filters
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    }
                },
                error: () => {
                    console.error('Erro ao carregar estatísticas');
                }
            });
        },

        // Atualizar estatísticas
        updateStats: function(stats) {
            this.elements.stats.total.text(stats.total || 0);
            this.elements.stats.solicitadas.text(stats.solicitadas || 0);
            this.elements.stats.approved.text(stats.approved || 0);
            this.elements.stats.rejected.text(stats.rejected || 0);
            this.elements.stats.canceladas.text(stats.canceladas || 0);
        },

        // Carregar inscrições (inicial)
        loadInscricoes: function(reset = true) {
            if (this.config.isLoading) return;

            if (reset) {
                this.resetPagination();
                $('.sevo-inscricoes-list').empty();
            }

            this.config.isLoading = true;
            this.showLoading();

            const ajaxData = {
                action: 'sevo_dashboard_get_inscricoes',
                nonce: sevoDashboardInscricoes.nonce,
                page: this.config.currentPage,
                per_page: this.config.itemsPerPage,
                sort_by: this.config.sortBy,
                sort_order: this.config.sortOrder,
                filters: this.config.filters
            };
            
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: (response) => {
                    this.config.isLoading = false;
                    this.hideLoading();

                    if (response.success) {
                        const inscricoes = response.data.inscricoes || [];
                        
                        if (reset) {
                            this.config.allInscricoes = inscricoes;
                            this.renderInscricoes(inscricoes, true);
                        } else {
                            this.config.allInscricoes = [...this.config.allInscricoes, ...inscricoes];
                            this.appendInscricoes(inscricoes);
                        }
                        
                        this.config.hasMoreItems = inscricoes.length === this.config.itemsPerPage;
                        
                        if (!this.config.hasMoreItems && this.config.allInscricoes.length > 0) {
                            this.showEndOfList();
                        }
                        
                        if (this.config.allInscricoes.length === 0) {
                            this.showNoResults();
                        }
                    } else {
                        this.showError(response.data || 'Erro ao carregar inscrições');
                    }
                },
                error: (xhr, status, error) => {
                    this.config.isLoading = false;
                    this.hideLoading();
                    this.showError('Erro de conexão: ' + error);
                }
            });
        },

        // Carregar mais inscrições (scroll infinito)
        loadMoreInscricoes: function() {
            if (this.config.isLoadingMore || !this.config.hasMoreItems || this.config.isLoading) {
                return;
            }

            this.config.isLoadingMore = true;
            this.config.currentPage++;
            this.showInfiniteLoading();

            const ajaxData = {
                action: 'sevo_dashboard_get_inscricoes',
                nonce: sevoDashboardInscricoes.nonce,
                page: this.config.currentPage,
                per_page: this.config.itemsPerPage,
                sort_by: this.config.sortBy,
                sort_order: this.config.sortOrder,
                filters: this.config.filters
            };
            
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: (response) => {
                    this.config.isLoadingMore = false;
                    this.hideInfiniteLoading();

                    if (response.success) {
                        const inscricoes = response.data.inscricoes || [];
                        
                        if (inscricoes.length > 0) {
                            this.config.allInscricoes = [...this.config.allInscricoes, ...inscricoes];
                            this.appendInscricoes(inscricoes);
                        }
                        
                        this.config.hasMoreItems = inscricoes.length === this.config.itemsPerPage;
                        
                        if (!this.config.hasMoreItems) {
                            this.showEndOfList();
                        }
                    }
                },
                error: () => {
                    this.config.isLoadingMore = false;
                    this.hideInfiniteLoading();
                    this.config.currentPage--; // Reverter em caso de erro
                }
            });
        },

        // Renderizar inscrições como cards
        renderInscricoes: function(inscricoes, reset = true) {
            const $container = $('.sevo-inscricoes-list');

            if (!inscricoes || inscricoes.length === 0) {
                if (reset) {
                    this.showNoResults();
                }
                return;
            }

            this.hideNoResults();

            if (reset) {
                $container.empty();
            }

            inscricoes.forEach(inscricao => {
                $container.append(this.createInscricaoCard(inscricao));
            });
        },

        // Adicionar inscrições ao final da lista
        appendInscricoes: function(inscricoes) {
            const $container = $('.sevo-inscricoes-list');
            
            inscricoes.forEach(inscricao => {
                $container.append(this.createInscricaoCard(inscricao));
            });
        },

        // Criar card de inscrição
        createInscricaoCard: function(inscricao) {
            const statusClass = 'status-' + (inscricao.status || 'indefinido');
            const statusLabels = {
                'solicitada': 'Solicitada',
                'aceita': 'Aceita',
                'rejeitada': 'Rejeitada',
                'cancelada': 'Cancelada'
            };
            const statusDisplay = statusLabels[inscricao.status] || 'Indefinido';
            
            const dataEvento = inscricao.data_inicio_evento ? 
                new Date(inscricao.data_inicio_evento).toLocaleDateString('pt-BR') : 'Data não definida';
            const dataInscricao = inscricao.created_at ? 
                new Date(inscricao.created_at).toLocaleDateString('pt-BR') + ' ' + 
                new Date(inscricao.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'}) : '';
            
            const canManage = sevoDashboardInscricoes.canManageAll;
            const currentUserId = sevoDashboardInscricoes.currentUserId;
            const canCancel = ['solicitada', 'aceita'].includes(inscricao.status) && 
                             inscricao.usuario_id == currentUserId;
            
            let actionsHtml = '';
            
            if (canManage) {
                if (inscricao.status === 'solicitada') {
                    actionsHtml += `
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-success approve-btn" 
                                data-inscricao-id="${inscricao.id}" title="Aprovar Inscrição">
                            <i class="dashicons dashicons-yes"></i>
                        </button>
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger reject-btn" 
                                data-inscricao-id="${inscricao.id}" title="Rejeitar Inscrição">
                            <i class="dashicons dashicons-no"></i>
                        </button>
                    `;
                }
                actionsHtml += `
                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                            data-evento-id="${inscricao.evento_id}" title="Ver Detalhes do Evento">
                        <i class="dashicons dashicons-visibility"></i>
                    </button>
                `;
                if (sevoDashboardInscricoes.canManageAll) {
                    actionsHtml += `
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-warning edit-inscricao-btn" 
                                data-inscricao-id="${inscricao.id}" title="Editar Inscrição">
                            <i class="dashicons dashicons-edit"></i>
                        </button>
                    `;
                }
            } else {
                actionsHtml += `
                    <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-info view-event-btn" 
                            data-evento-id="${inscricao.evento_id}" title="Ver Detalhes do Evento">
                        <i class="dashicons dashicons-visibility"></i>
                    </button>
                `;
                if (canCancel) {
                    actionsHtml += `
                        <button type="button" class="sevo-btn sevo-btn-sm sevo-btn-danger cancel-own-btn" 
                                data-inscricao-id="${inscricao.id}" title="Cancelar Minha Inscrição">
                            <i class="dashicons dashicons-dismiss"></i>
                        </button>
                    `;
                }
            }
            
            return `
                <div class="sevo-inscricao-card" data-inscricao-id="${inscricao.id}" data-status="${inscricao.status}">
                    <div class="sevo-card-image">
                        ${inscricao.evento_imagem ? 
                            `<img src="${inscricao.evento_imagem}" alt="${this.escapeHtml(inscricao.evento_titulo)}">` :
                            '<div class="sevo-card-placeholder"><i class="dashicons dashicons-calendar-alt"></i></div>'
                        }
                    </div>
                    
                    <div class="sevo-card-content">
                        <div class="sevo-card-header">
                            <h3 class="sevo-card-title">${this.escapeHtml(inscricao.evento_titulo || 'Evento')}</h3>
                            <span class="sevo-status-badge ${statusClass}">${statusDisplay}</span>
                        </div>
                        
                        <div class="sevo-card-info">
                            <div class="sevo-info-row">
                                <div class="sevo-info-item">
                                    <i class="dashicons dashicons-calendar"></i>
                                    <span title="${dataEvento}">${dataEvento}</span>
                                </div>
                                <div class="sevo-info-item">
                                    <i class="dashicons dashicons-building"></i>
                                    <span title="${this.escapeHtml(inscricao.organizacao_titulo || '')}">${this.escapeHtml(inscricao.organizacao_titulo || 'N/A')}</span>
                                </div>
                            </div>
                            
                            <div class="sevo-info-row">
                                <div class="sevo-info-item">
                                    <i class="dashicons dashicons-category"></i>
                                    <span title="${this.escapeHtml(inscricao.tipo_evento_titulo || '')}">${this.escapeHtml(inscricao.tipo_evento_titulo || 'N/A')}</span>
                                </div>
                                ${canManage ? `
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-admin-users"></i>
                                        <span title="${this.escapeHtml(inscricao.usuario_nome || '')}">${this.escapeHtml(inscricao.usuario_nome || 'N/A')}</span>
                                    </div>
                                ` : `
                                    <div class="sevo-info-item">
                                        <i class="dashicons dashicons-clock"></i>
                                        <span title="${dataInscricao}">${dataInscricao}</span>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                    
                    <div class="sevo-card-actions">
                        <div class="${canManage ? 'sevo-admin-actions' : 'sevo-user-actions'}">
                            ${actionsHtml}
                        </div>
                    </div>
                </div>
            `;
        },

        // Função para detectar scroll infinito otimizada
        handleScroll: function() {
            if (this.config.isLoadingMore || !this.config.hasMoreItems || this.config.isLoading) {
                return;
            }

            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            const threshold = 300; // Pixels antes do fim

            if (scrollTop + windowHeight >= documentHeight - threshold) {
                this.loadMoreInscricoes();
            }
        },

        // Helpers para loading states
        showLoading: function() {
            $('.sevo-table-loading').show();
            $('.sevo-inscricoes-list').hide();
        },

        hideLoading: function() {
            $('.sevo-table-loading').hide();
            $('.sevo-inscricoes-list').show();
        },

        showInfiniteLoading: function() {
            $('#infinite-loading').show();
        },

        hideInfiniteLoading: function() {
            $('#infinite-loading').hide();
        },

        showEndOfList: function() {
            $('#end-of-list').show();
        },

        showNoResults: function() {
            $('.sevo-no-inscricoes').show();
            $('.sevo-inscricoes-list').hide();
        },

        hideNoResults: function() {
            $('.sevo-no-inscricoes').hide();
            $('.sevo-inscricoes-list').show();
        },

        showError: function(message) {
            if (typeof SevoToaster !== 'undefined') {
                SevoToaster.showError(message);
            } else {
                alert('Erro: ' + message);
            }
        },

        // Helper para escape HTML
        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Renderizar linha de inscrição
        renderInscricaoRow: function(inscricao, template) {
            let html = template;

            // Substituir placeholders básicos
            html = html.replace(/{{inscricao_id}}/g, inscricao.id || inscricao.inscricao_id);
            html = html.replace(/{{usuario_nome}}/g, this.escapeHtml(inscricao.usuario_nome || ''));
            html = html.replace(/{{usuario_email}}/g, this.escapeHtml(inscricao.usuario_email || ''));
            html = html.replace(/{{evento_nome}}/g, this.escapeHtml(inscricao.evento_nome || inscricao.evento_titulo || ''));
            html = html.replace(/{{evento_data_formatted}}/g, this.formatDate(inscricao.evento_data));
            html = html.replace(/{{organizacao_nome}}/g, this.escapeHtml(inscricao.organizacao_nome || ''));
            html = html.replace(/{{tipo_evento_nome}}/g, this.escapeHtml(inscricao.tipo_evento_nome || ''));
            html = html.replace(/{{status}}/g, inscricao.status || '');
            html = html.replace(/{{status_label}}/g, this.getStatusLabel(inscricao.status));
            html = html.replace(/{{data_inscricao_formatted}}/g, this.formatDateTime(inscricao.data_inscricao));
            html = html.replace(/{{evento_id}}/g, inscricao.evento_id || '');

            // Substituir condicionais de status
            const isStatusSolicitada = inscricao.status === 'solicitada';
            
            // Processar if_status_solicitada
        const startTagSolicitada = '{{#if_status_solicitada}}';
        const endTagSolicitada = '{{/if_status_solicitada}}';
        let startIndex = html.indexOf(startTagSolicitada);
        while (startIndex !== -1) {
            const endIndex = html.indexOf(endTagSolicitada, startIndex);
            if (endIndex !== -1) {
                const fullMatch = html.substring(startIndex, endIndex + endTagSolicitada.length);
                const content = html.substring(startIndex + startTagSolicitada.length, endIndex);
                html = html.replace(fullMatch, isStatusSolicitada ? content : '');
                startIndex = html.indexOf(startTagSolicitada, startIndex);
            } else {
                break;
            }
        }
            
            // Processar if_status_not_solicitada
        const startTagNotSolicitada = '{{#if_status_not_solicitada}}';
        const endTagNotSolicitada = '{{/if_status_not_solicitada}}';
        startIndex = html.indexOf(startTagNotSolicitada);
        while (startIndex !== -1) {
            const endIndex = html.indexOf(endTagNotSolicitada, startIndex);
            if (endIndex !== -1) {
                const fullMatch = html.substring(startIndex, endIndex + endTagNotSolicitada.length);
                const content = html.substring(startIndex + startTagNotSolicitada.length, endIndex);
                html = html.replace(fullMatch, !isStatusSolicitada ? content : '');
                startIndex = html.indexOf(startTagNotSolicitada, startIndex);
            } else {
                break;
            }
        }

            return html;
        },

        // Carregar opções dos filtros
        loadFilterOptions: function() {
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_get_filter_options',
                    nonce: sevoDashboardInscricoes.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populateFilterOptions(response.data);
                    }
                },
                error: () => {
                    console.error('Erro ao carregar opções de filtro');
                }
            });
        },

        // Popular opções dos filtros
        populateFilterOptions: function(options) {
            // Eventos
            if (options.eventos) {
                const $select = $('#filter-evento');
                $select.empty().append('<option value="">Todos os eventos</option>');
                options.eventos.forEach(evento => {
                    $select.append(`<option value="${evento.id}">${this.escapeHtml(evento.evento_titulo)}</option>`);
                });
            }

            // Organizações
            if (options.organizacoes) {
                const $select = $('#filter-organizacao');
                $select.empty().append('<option value="">Todas as organizações</option>');
                options.organizacoes.forEach(org => {
                    $select.append(`<option value="${org.id}">${this.escapeHtml(org.organizacao_titulo)}</option>`);
                });
            }

            // Tipos de evento
            if (options.tipos_evento) {
                const $select = $('#filter-tipo-evento');
                $select.empty().append('<option value="">Todos os tipos</option>');
                options.tipos_evento.forEach(tipo => {
                    $select.append(`<option value="${tipo.id}">${this.escapeHtml(tipo.tipo_evento_titulo)}</option>`);
                });
            }

            // Períodos disponíveis
            if (options.periodos) {
                const $select = $('#filter-periodo');
                $select.empty().append('<option value="">Todos os períodos</option>');
                options.periodos.forEach(periodo => {
                    const [ano, mes] = periodo.periodo.split('-');
                    const meses = {
                        '01': 'Jan', '02': 'Fev', '03': 'Mar', '04': 'Abr',
                        '05': 'Mai', '06': 'Jun', '07': 'Jul', '08': 'Ago',
                        '09': 'Set', '10': 'Out', '11': 'Nov', '12': 'Dez'
                    };
                    const label = `${meses[mes]} ${ano}`;
                    $select.append(`<option value="${periodo.periodo}">${label}</option>`);
                });
            }
        },

        // Ações de aprovação/reprovação
        handleApprove: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            this.showActionModal('aprovar', inscricaoId, 'Aprovar Inscrição', 'Tem certeza que deseja aprovar esta inscrição?');
        },

        handleReject: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            this.showActionModal('rejeitar', inscricaoId, 'Rejeitar Inscrição', 'Tem certeza que deseja rejeitar esta inscrição?', true);
        },

        handleViewEvent: function(e) {
            e.preventDefault();
            const eventoId = $(e.currentTarget).data('evento-id');
            console.log('handleViewEvent - eventoId capturado:', eventoId);
            console.log('handleViewEvent - elemento clicado:', e.currentTarget);
            console.log('handleViewEvent - data attributes:', $(e.currentTarget).data());
            this.openEventModal(eventoId);
        },

        handleEdit: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            this.openEditModal(inscricaoId);
        },

        // Abre o modal do evento
        openEventModal: function(eventId) {
            console.log('openEventModal - eventId recebido:', eventId);
            console.log('openEventModal - tipo do eventId:', typeof eventId);
            console.log('openEventModal - eventId é válido?', eventId && eventId !== '' && eventId !== 'undefined');
            
            const modal = document.getElementById('sevo-event-modal');
            if (!modal) {
                console.error('Modal #sevo-event-modal não encontrado');
                return;
            }
            
            const modalContent = modal.querySelector('.sevo-modal-content');
            const loadingIndicator = modal.querySelector('.sevo-modal-loading');
            
            if (!modalContent || !loadingIndicator) {
                console.error('Elementos do modal não encontrados');
                return;
            }
            
            // Verifica se os dados AJAX estão disponíveis
            const ajaxData = window.sevoDashboardInscricoes;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis (sevoDashboardInscricoes)');
                SevoToaster.showError('Erro: Dados de configuração não encontrados');
                return;
            }
            
            // Mostra o modal e o loading
            modal.style.display = 'flex';
            modal.classList.add('show');
            loadingIndicator.style.display = 'block';
            modalContent.style.display = 'none';
            
            // Validar eventId antes da requisição
            if (!eventId || eventId === '' || eventId === 'undefined' || eventId === 'null') {
                console.error('eventId inválido:', eventId);
                SevoToaster.showError('Erro: ID do evento não fornecido.');
                modal.style.display = 'none';
                return;
            }
            
            // Faz a requisição AJAX
            $.ajax({
                url: ajaxData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_view',
                    evento_id: eventId,
                    nonce: ajaxData.eventViewNonce || ajaxData.nonce
                },
                success: function(response) {
                    console.log('Resposta AJAX recebida:', response);
                    if (response.success) {
                        modalContent.innerHTML = response.data.html;
                        loadingIndicator.style.display = 'none';
                        modalContent.style.display = 'block';
                    } else {
                        console.error('Erro ao carregar evento:', response.data);
                SevoToaster.showError('Erro ao carregar evento: ' + (response.data || 'Erro desconhecido'));
                        this.closeEventModal();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    SevoToaster.showError('Erro na requisição AJAX: ' + error);
                    this.closeEventModal();
                }.bind(this)
            });
        },

        // Fecha o modal do evento
        closeEventModal: function() {
            const modal = document.getElementById('sevo-event-modal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        },

        // Mostrar modal de ação
        showActionModal: function(action, inscricaoId, title, message, showComment = false) {
            this.currentAction = { action, inscricaoId };
            
            this.elements.modalTitle.text(title);
            this.elements.modalMessage.text(message);
            
            if (showComment) {
                this.elements.modalInput.show().find('textarea').val('');
            } else {
                this.elements.modalInput.hide();
            }
            
            this.elements.modal.show();
        },

        // Confirmar ação
        confirmAction: function() {
            if (!this.currentAction) return;

            const { action, inscricaoId } = this.currentAction;
            const comment = this.elements.modalInput.find('textarea').val();

            this.executeAction(action, inscricaoId, comment);
            this.closeModal();
        },

        // Executar ação
        executeAction: function(action, inscricaoId, comment = '') {
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_update_inscricao',
                    nonce: sevoDashboardInscricoes.nonce,
                    inscricao_id: inscricaoId,
                    new_status: this.getNewStatus(action),
                    comment: comment
                },
                success: (response) => {
                    if (response.success) {
                        SevoToaster.showSuccess(response.data.message);
                        this.loadInscricoes();
                        this.loadStats();
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao atualizar inscrição');
                    }
                },
                error: () => {
                    SevoToaster.showError('Erro de conexão ao atualizar inscrição');
                }
            });
        },

        // Obter novo status baseado na ação
        getNewStatus: function(action) {
            const statusMap = {
                'aprovar': 'aceita',
                'rejeitar': 'rejeitada'
            };
            return statusMap[action] || 'solicitada';
        },

        // Fechar modal
        closeModal: function() {
            this.elements.modal.hide();
            this.currentAction = null;
        },

        // Abrir modal de edição
        openEditModal: function(inscricaoId) {
            const modal = document.getElementById('sevo-edit-inscricao-modal');
            if (!modal) {
                console.error('Modal de edição não encontrado');
                return;
            }

            const modalContent = modal.querySelector('#sevo-edit-content');
            const loadingIndicator = modal.querySelector('#sevo-edit-loading');

            if (!modalContent || !loadingIndicator) {
                console.error('Elementos do modal de edição não encontrados');
                return;
            }

            // Mostra o modal e o loading
            modal.style.display = 'flex';
            modal.classList.add('show');
            loadingIndicator.style.display = 'block';
            modalContent.style.display = 'none';

            // Faz a requisição AJAX para carregar os dados da inscrição
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_get_inscricao_edit',
                    inscricao_id: inscricaoId,
                    nonce: sevoDashboardInscricoes.nonce
                },
                success: (response) => {
                    if (response.success) {
                        modalContent.innerHTML = response.data.html;
                        loadingIndicator.style.display = 'none';
                        modalContent.style.display = 'block';
                        this.currentEditId = inscricaoId;
                    } else {
                        console.error('Erro ao carregar dados da inscrição:', response.data);
                        SevoToaster.showError('Erro ao carregar dados da inscrição: ' + (response.data || 'Erro desconhecido'));
                        this.closeEditModal();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro na requisição AJAX:', error);
                    SevoToaster.showError('Erro na requisição: ' + error);
                    this.closeEditModal();
                }
            });
        },

        // Fechar modal de edição
        closeEditModal: function() {
            const modal = document.getElementById('sevo-edit-inscricao-modal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
            this.currentEditId = null;
        },

        // Salvar edição
        saveEdit: function() {
            if (!this.currentEditId) {
                console.error('ID da inscrição não encontrado');
                return;
            }

            const form = document.getElementById('edit-inscricao-form');
            if (!form) {
                console.error('Formulário de edição não encontrado');
                return;
            }

            // Validar formulário
            if (!this.validateEditForm(form)) {
                return;
            }

            // Coletar dados do formulário
            const formData = new FormData(form);
            const data = {
                action: 'sevo_dashboard_save_inscricao_edit',
                inscricao_id: this.currentEditId,
                nonce: sevoDashboardInscricoes.nonce
            };

            // Adicionar dados do formulário
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            // Mostrar loading no botão
            const saveBtn = document.getElementById('save-edit-btn');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Salvando...';
            saveBtn.disabled = true;

            // Fazer requisição AJAX
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        SevoToaster.showSuccess('Inscrição atualizada com sucesso!');
                        this.closeEditModal();
                        this.loadInscricoes();
                        this.loadStats();
                    } else {
                        SevoToaster.showError('Erro ao atualizar inscrição: ' + (response.data || 'Erro desconhecido'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro na requisição AJAX:', error);
                    SevoToaster.showError('Erro na requisição: ' + error);
                },
                complete: () => {
                    // Restaurar botão
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                }
            });
        },

        // Validar formulário de edição
        validateEditForm: function(form) {
            let isValid = true;
            const errors = [];

            // Limpar erros anteriores
            form.querySelectorAll('.sevo-field-error').forEach(error => {
                error.remove();
            });
            form.querySelectorAll('.sevo-field.error').forEach(field => {
                field.classList.remove('error');
            });

            // Validar campos obrigatórios
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    this.showFieldError(field, 'Este campo é obrigatório');
                    isValid = false;
                }
            });

            // Validar email
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value.trim())) {
                    this.showFieldError(emailField, 'Email inválido');
                    isValid = false;
                }
            }

            // Validar telefone (se presente)
            const phoneField = form.querySelector('input[name="telefone"]');
            if (phoneField && phoneField.value.trim()) {
                const phoneRegex = /^\(?\d{2}\)?[\s-]?\d{4,5}[\s-]?\d{4}$/;
                if (!phoneRegex.test(phoneField.value.trim())) {
                    this.showFieldError(phoneField, 'Telefone inválido');
                    isValid = false;
                }
            }

            return isValid;
        },

        // Mostrar erro em campo específico
        showFieldError: function(field, message) {
            const fieldContainer = field.closest('.sevo-field');
            if (fieldContainer) {
                fieldContainer.classList.add('error');
                
                const errorElement = document.createElement('div');
                errorElement.className = 'sevo-field-error';
                errorElement.textContent = message;
                
                fieldContainer.appendChild(errorElement);
            }
        },



        // Mostrar loading inicial
        showLoading: function() {
            this.elements.tableLoading.show();
            this.elements.tableBody.parent().hide();
            this.elements.noResults.hide();
            this.elements.infiniteLoading.hide();
            this.elements.endOfList.hide();
        },

        // Esconder loading inicial
        hideLoading: function() {
            this.elements.tableLoading.hide();
            this.elements.tableBody.parent().show();
        },

        // Mostrar loading do scroll infinito
        showInfiniteLoading: function() {
            this.elements.infiniteLoading.show();
            this.elements.endOfList.hide();
        },

        // Esconder loading do scroll infinito
        hideInfiniteLoading: function() {
            this.elements.infiniteLoading.hide();
        },

        // Mostrar indicador de fim da lista
        showEndOfList: function() {
            this.elements.endOfList.show();
            this.elements.infiniteLoading.hide();
        },

        // Mostrar sem resultados
        showNoResults: function() {
            this.elements.noResults.show();
            this.elements.tableBody.parent().hide();
        },

        // Esconder sem resultados
        hideNoResults: function() {
            this.elements.noResults.hide();
            this.elements.tableBody.parent().show();
        },

        // Mostrar erro
        showError: function(message) {
            SevoToaster.showError(message);
            this.showNoResults();
        },



        // Fechar toast
        closeToast: function() {
            $('.sevo-toast').fadeOut(300, function() {
                $(this).remove();
            });
        },

        // Manipular teclado
        handleKeyboard: function(e) {
            // ESC para fechar modal
            if (e.keyCode === 27) {
                this.closeModal();
                this.closeEditModal();
                this.closeToast();
            }

            // Enter para confirmar modal
            if (e.keyCode === 13 && this.elements.modal.is(':visible')) {
                e.preventDefault();
                this.confirmAction();
            }

            // Ctrl+S para salvar edição
            if (e.ctrlKey && e.keyCode === 83 && document.getElementById('sevo-edit-inscricao-modal').style.display === 'flex') {
                e.preventDefault();
                this.saveEdit();
            }
        },

        // Utilitários
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate: function(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        },

        formatDateTime: function(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        },

        getStatusLabel: function(status) {
            const labels = {
                'solicitada': 'Solicitada',
        'aceita': 'Aceita',
        'rejeitada': 'Rejeitada',
        'cancelada': 'Cancelada'
            };
            return labels[status] || status;
        },

        // Manipular cancelamento próprio
        handleCancelOwn: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            
            this.showModal(
                'Cancelar Inscrição',
                'Tem certeza que deseja cancelar sua inscrição? Esta ação não pode ser desfeita.',
                () => this.cancelOwnInscricao(inscricaoId)
            );
        },

        // Cancelar própria inscrição
        cancelOwnInscricao: function(inscricaoId) {
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sevo_dashboard_cancel_own_inscricao',
                    nonce: sevoDashboardInscricoes.nonce,
                    inscricao_id: inscricaoId
                },
                success: (response) => {
                    if (response.success) {
                        if (typeof SevoToaster !== 'undefined') {
                            SevoToaster.showSuccess('Inscrição cancelada com sucesso!');
                        }
                        this.refreshData();
                    } else {
                        this.showError(response.data || 'Erro ao cancelar inscrição');
                    }
                },
                error: () => {
                    this.showError('Erro de conexão ao cancelar inscrição');
                }
            });
        },

        // Mostrar modal de confirmação
        showModal: function(title, message, onConfirm) {
            $('#modal-title').text(title);
            $('#modal-message').text(message);
            $('#confirmation-modal').show();
            
            $('#modal-confirm').off('click').on('click', () => {
                this.closeModal();
                if (onConfirm) onConfirm();
            });
        },

        // Fechar modal
        closeModal: function() {
            $('#confirmation-modal').hide();
            $('#modal-confirm').off('click');
        }
    };

    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        console.log('Dashboard JS carregado');
        console.log('sevoDashboardInscricoes:', typeof sevoDashboardInscricoes !== 'undefined' ? sevoDashboardInscricoes : 'UNDEFINED');
        console.log('Elementos encontrados:', $('.sevo-dashboard-wrapper').length);
    
    if ($('.sevo-dashboard-wrapper').length) {
        console.log('Inicializando dashboard de inscrições...');
        SevoDashboard.init();
    } else {
        console.log('Elemento .sevo-dashboard-wrapper não encontrado');
        }
    });

    // Expor globalmente para debug
    window.SevoDashboard = SevoDashboard;

})(jQuery);