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
            itemsPerPage: 10,
            totalItems: 0,
            totalPages: 0,
            sortBy: 'data_inscricao',
            sortOrder: 'desc',
            isLoading: false,
            filters: {
                evento: '',
                status: '',
                ano: '',
                mes: '',
                organizacao: '',
                tipo_evento: '',
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
                container: $('.sevo-dashboard-inscricoes'),
                filtersToggle: $('#sevo-filters-toggle'),
                filtersContent: $('#sevo-filters-content'),
                filterForm: $('.sevo-filters-content'),
                tableContainer: $('.sevo-table-container'),
                table: $('#inscricoes-table'),
                tableBody: $('#inscricoes-tbody'),
                tableLoading: $('#table-loading'),
                noResults: $('#no-results'),
                pagination: $('#pagination-container'),
                itemsPerPageSelect: $('#per-page-select'),
                refreshBtn: $('#refresh-table'),
                tableInfo: $('#results-info'),
                modal: $('#confirmation-modal'),
                toast: $('#notification-toast'),
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
            // Toggle de filtros
            this.elements.filtersToggle.on('click', this.toggleFilters.bind(this));

            // Aplicar filtros
            $('#apply-filters').on('click', this.applyFilters.bind(this));
            $('#clear-filters').on('click', this.resetFilters.bind(this));

            // Itens por página
            this.elements.itemsPerPageSelect.on('change', this.changeItemsPerPage.bind(this));

            // Refresh
            this.elements.refreshBtn.on('click', this.refreshData.bind(this));

            // Ordenação da tabela
            $(document).on('click', '#inscricoes-table th.sortable', this.handleSort.bind(this));

            // Ações da tabela
            $(document).on('click', '.approve-btn', this.handleApprove.bind(this));
            $(document).on('click', '.reject-btn', this.handleReject.bind(this));
            $(document).on('click', '.revert-btn', this.handleRevert.bind(this));
            $(document).on('click', '.view-btn', this.handleView.bind(this));
            $(document).on('click', '.view-event-btn', this.handleViewEvent.bind(this));

            // Paginação
            $(document).on('click', '#pagination-container button[data-page]', this.changePage.bind(this));

            // Modal
            this.elements.modalConfirm.on('click', this.confirmAction.bind(this));
            this.elements.modalCancel.on('click', this.closeModal.bind(this));
            $('.sevo-modal-close').on('click', this.closeModal.bind(this));
            $(document).on('click', '.sevo-modal-overlay', this.closeModal.bind(this));

            // Toast
            $(document).on('click', '.sevo-toast-close', this.closeToast.bind(this));

            // Teclas de atalho
            $(document).on('keydown', this.handleKeyboard.bind(this));
        },

        // Configurar filtros iniciais
        setupFilters: function() {
            // Carregar opções dos filtros
            this.loadFilterOptions();

            // Estado inicial dos filtros (fechado)
            this.elements.filtersContent.hide();
        },

        // Carregar dados iniciais
        loadInitialData: function() {
            console.log('loadInitialData chamada');
            this.loadStats();
            this.loadInscricoes();
        },

        // Toggle dos filtros
        toggleFilters: function(e) {
            e.preventDefault();
            const $toggle = this.elements.filtersToggle;
            const $content = this.elements.filtersContent;

            if ($content.is(':visible')) {
                $content.slideUp(300);
                $toggle.removeClass('active');
            } else {
                $content.slideDown(300);
                $toggle.addClass('active');
            }
        },

        // Aplicar filtros
        applyFilters: function(e) {
            e.preventDefault();
            
            // Coletar valores dos filtros, garantindo que valores vazios sejam tratados
            this.config.filters = {
                evento_id: $('#filter-evento').val() || '',
                status: $('#filter-status').val() || '',
                ano: $('#filter-ano').val() || '',
                mes: $('#filter-mes').val() || '',
                organizacao_id: $('#filter-organizacao').val() || '',
                tipo_evento_id: $('#filter-tipo-evento').val() || '',
                usuario: $('#filter-usuario').val() || ''
            };

            // Remover filtros vazios para evitar problemas na query
            Object.keys(this.config.filters).forEach(key => {
                if (this.config.filters[key] === '' || this.config.filters[key] === null || this.config.filters[key] === undefined) {
                    delete this.config.filters[key];
                }
            });

            console.log('Filtros aplicados:', this.config.filters); // Debug

            // Resetar para primeira página
            this.config.currentPage = 1;

            // Recarregar dados
            this.loadInscricoes();
            this.loadStats();
        },

        // Resetar filtros
        resetFilters: function(e) {
            e.preventDefault();
            
            // Limpar campos de filtro
            $('#filter-evento').val('');
            $('#filter-status').val('');
            $('#filter-ano').val('');
            $('#filter-mes').val('');
            $('#filter-organizacao').val('');
            $('#filter-tipo-evento').val('');
            $('#filter-usuario').val('');
            
            // Limpar filtros
            this.config.filters = {
                evento_id: '',
                status: '',
                ano: '',
                mes: '',
                organizacao_id: '',
                tipo_evento_id: '',
                usuario: ''
            };

            // Resetar página
            this.config.currentPage = 1;

            // Recarregar dados
            this.loadInscricoes();
            this.loadStats();
        },

        // Alterar itens por página
        changeItemsPerPage: function() {
            this.config.itemsPerPage = parseInt(this.elements.itemsPerPageSelect.val());
            this.config.currentPage = 1;
            this.loadInscricoes();
        },

        // Refresh dos dados
        refreshData: function() {
            this.loadInscricoes();
            this.loadStats();
            this.showToast('Dados atualizados com sucesso!', 'success');
        },

        // Manipular ordenação
        handleSort: function(e) {
            const $th = $(e.currentTarget);
            const sortBy = $th.data('sort');

            if (this.config.sortBy === sortBy) {
                // Alternar ordem
                this.config.sortOrder = this.config.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                // Nova coluna
                this.config.sortBy = sortBy;
                this.config.sortOrder = 'asc';
            }

            // Atualizar UI
            $('#inscricoes-table th').removeClass('sort-asc sort-desc');
            $th.addClass('sort-' + this.config.sortOrder);

            // Recarregar dados
            this.config.currentPage = 1;
            this.loadInscricoes();
        },

        // Mudar página
        changePage: function(e) {
            e.preventDefault();
            const page = parseInt($(e.currentTarget).data('page'));
            if (page !== this.config.currentPage && page >= 1 && page <= this.config.totalPages) {
                this.config.currentPage = page;
                this.loadInscricoes();
            }
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

        // Carregar inscrições
        loadInscricoes: function() {
            console.log('loadInscricoes chamada');
            if (this.config.isLoading) {
                console.log('Já está carregando, retornando...');
                return;
            }

            console.log('Iniciando carregamento de inscrições...');
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
            
            console.log('Dados da requisição AJAX:', ajaxData);
            console.log('URL AJAX:', sevoDashboardInscricoes.ajaxUrl);
            
            $.ajax({
                url: sevoDashboardInscricoes.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                beforeSend: function() {
                    console.log('Iniciando requisição AJAX...');
                },
                success: (response) => {
                    this.config.isLoading = false;
                    this.hideLoading();

                    console.log('Response:', response); // Debug

                    if (response.success) {
                        this.renderInscricoes(response.data.inscricoes);
                        this.updatePagination(response.data);
                        this.updateTableInfo(response.data);
                    } else {
                        console.error('Error response:', response);
                        this.showError(response.data || 'Erro ao carregar inscrições');
                    }
                },
                error: (xhr, status, error) => {
                    this.config.isLoading = false;
                    this.hideLoading();
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    this.showError('Erro de conexão ao carregar inscrições: ' + error + ' (Status: ' + xhr.status + ')');
                }
            });
        },

        // Renderizar inscrições
        renderInscricoes: function(inscricoes) {
            const $tbody = this.elements.tableBody;
            const template = $('#inscricao-row-template').html();

            if (!inscricoes || inscricoes.length === 0) {
                this.showNoResults();
                return;
            }

            this.hideNoResults();
            this.elements.pagination.show();

            let html = '';
            inscricoes.forEach(inscricao => {
                html += this.renderInscricaoRow(inscricao, template);
            });

            $tbody.html(html);
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
                    $select.append(`<option value="${evento.id}">${this.escapeHtml(evento.titulo)}</option>`);
                });
            }

            // Organizações
            if (options.organizacoes) {
                const $select = $('#filter-organizacao');
                $select.empty().append('<option value="">Todas as organizações</option>');
                options.organizacoes.forEach(org => {
                    $select.append(`<option value="${org.id}">${this.escapeHtml(org.nome)}</option>`);
                });
            }

            // Tipos de evento
            if (options.tipos_evento) {
                const $select = $('#filter-tipo-evento');
                $select.empty().append('<option value="">Todos os tipos</option>');
                options.tipos_evento.forEach(tipo => {
                    $select.append(`<option value="${tipo.id}">${this.escapeHtml(tipo.nome)}</option>`);
                });
            }

            // Usuários (apenas para admins)
            if (options.usuarios) {
                const $select = $('#filter-usuario');
                $select.empty().append('<option value="">Todos os usuários</option>');
                options.usuarios.forEach(usuario => {
                    $select.append(`<option value="${usuario.id}">${this.escapeHtml(usuario.nome)}</option>`);
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

        handleRevert: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            this.showActionModal('reverter', inscricaoId, 'Reverter Status', 'Tem certeza que deseja reverter o status desta inscrição para "Solicitada"?');
        },

        handleView: function(e) {
            e.preventDefault();
            const inscricaoId = $(e.currentTarget).data('inscricao-id');
            // Implementar visualização dos detalhes da inscrição
            console.log('Ver detalhes da inscrição:', inscricaoId);
        },

        handleViewEvent: function(e) {
            e.preventDefault();
            const eventoId = $(e.currentTarget).data('evento-id');
            // Implementar visualização do evento
            window.open(`/evento/${eventoId}`, '_blank');
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
                        this.showToast(response.data.message, 'success');
                        this.loadInscricoes();
                        this.loadStats();
                    } else {
                        this.showToast(response.data || 'Erro ao atualizar inscrição', 'error');
                    }
                },
                error: () => {
                    this.showToast('Erro de conexão ao atualizar inscrição', 'error');
                }
            });
        },

        // Obter novo status baseado na ação
        getNewStatus: function(action) {
            const statusMap = {
                'aprovar': 'aceita',
                'rejeitar': 'rejeitada',
                'reverter': 'solicitada'
            };
            return statusMap[action] || 'solicitada';
        },

        // Fechar modal
        closeModal: function() {
            this.elements.modal.hide();
            this.currentAction = null;
        },

        // Atualizar paginação
        updatePagination: function(pagination) {
            this.config.totalItems = pagination.total;
            this.config.totalPages = pagination.pages;
            this.config.currentPage = pagination.current;

            const $pagination = this.elements.pagination;
            let html = '';

            // Botão anterior
            const prevDisabled = pagination.current <= 1 ? 'disabled' : '';
            html += `<button ${prevDisabled} data-page="${pagination.current - 1}">‹ Anterior</button>`;

            // Páginas
            const startPage = Math.max(1, pagination.current - 2);
            const endPage = Math.min(pagination.pages, pagination.current + 2);

            if (startPage > 1) {
                html += `<button data-page="1">1</button>`;
                if (startPage > 2) {
                    html += `<span class="page-ellipsis">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const active = i === pagination.current ? 'active' : '';
                html += `<button class="${active}" data-page="${i}">${i}</button>`;
            }

            if (endPage < pagination.pages) {
                if (endPage < pagination.pages - 1) {
                    html += `<span class="page-ellipsis">...</span>`;
                }
                html += `<button data-page="${pagination.pages}">${pagination.pages}</button>`;
            }

            // Botão próximo
            const nextDisabled = pagination.current >= pagination.pages ? 'disabled' : '';
            html += `<button ${nextDisabled} data-page="${pagination.current + 1}">Próximo ›</button>`;

            $pagination.html(html);
        },

        // Atualizar informações da tabela
        updateTableInfo: function(data) {
            const start = ((data.current - 1) * this.config.itemsPerPage) + 1;
            const end = Math.min(start + this.config.itemsPerPage - 1, data.total);
            
            this.elements.tableInfo.text(
                `Mostrando ${start} a ${end} de ${data.total} inscrições`
            );
        },

        // Mostrar loading
        showLoading: function() {
            this.elements.tableLoading.show();
            this.elements.tableBody.parent().hide();
            this.elements.noResults.hide();
            this.elements.pagination.hide();
        },

        // Esconder loading
        hideLoading: function() {
            this.elements.tableLoading.hide();
            this.elements.tableBody.parent().show();
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
            this.showToast(message, 'error');
            this.showNoResults();
        },

        // Mostrar toast
        showToast: function(message, type = 'info') {
            const $toast = this.elements.toast;
            
            $toast.removeClass('success error warning info').addClass(type);
            $toast.find('.sevo-toast-message').text(message);
            $toast.show();

            // Auto-hide após 5 segundos
            setTimeout(() => {
                this.closeToast();
            }, 5000);
        },

        // Fechar toast
        closeToast: function() {
            this.elements.toast.hide();
        },

        // Manipular teclado
        handleKeyboard: function(e) {
            // ESC para fechar modal
            if (e.keyCode === 27) {
                this.closeModal();
                this.closeToast();
            }

            // Enter para confirmar modal
            if (e.keyCode === 13 && this.elements.modal.is(':visible')) {
                e.preventDefault();
                this.confirmAction();
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
        }
    };

    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        console.log('Dashboard JS carregado');
        console.log('sevoDashboardInscricoes:', typeof sevoDashboardInscricoes !== 'undefined' ? sevoDashboardInscricoes : 'UNDEFINED');
        console.log('Elementos encontrados:', $('.sevo-dashboard-inscricoes').length);
        
        if ($('.sevo-dashboard-inscricoes').length) {
            console.log('Inicializando SevoDashboard...');
            SevoDashboard.init();
        } else {
            console.log('Elemento .sevo-dashboard-inscricoes não encontrado');
        }
    });

    // Expor globalmente para debug
    window.SevoDashboard = SevoDashboard;

})(jQuery);