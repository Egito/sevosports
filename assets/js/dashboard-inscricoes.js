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
            itemsPerPage: 25,
            totalItems: 0,
            hasMoreItems: true,
            sortBy: 'data_inscricao',
            sortOrder: 'desc',
            isLoading: false,
            isLoadingMore: false,
            allInscricoes: [],
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
                infiniteLoading: $('#infinite-loading'),
                endOfList: $('#end-of-list'),

                modal: $('#confirmation-modal'),

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

            // Resetar scroll infinito
            this.config.currentPage = 1;
            this.config.hasMoreItems = true;
            this.config.allInscricoes = [];

            // Recarregar dados
            this.loadInscricoes(true);
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

            // Recarregar dados com scroll infinito
            this.config.currentPage = 1;
            this.config.hasMoreItems = true;
            this.config.allInscricoes = [];
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
            if (this.config.isLoading) {
                return;
            }

            if (reset) {
                this.config.currentPage = 1;
                this.config.allInscricoes = [];
                this.config.hasMoreItems = true;
                this.elements.endOfList.hide();
            }

            this.config.isLoading = true;
            // Forçar apresentação da tabela
            this.elements.tableLoading.hide();
            this.elements.table.show();

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
                            this.config.allInscricoes = this.config.allInscricoes.concat(inscricoes);
                            this.appendInscricoes(inscricoes);
                        }
                        
                        // Verificar se há mais itens
                        this.config.hasMoreItems = inscricoes.length === this.config.itemsPerPage;
                        
                        if (!this.config.hasMoreItems) {
                            this.elements.endOfList.show();
                        }
                    } else {
                        this.showError(response.data || 'Erro ao carregar inscrições');
                    }
                },
                error: (xhr, status, error) => {
                    this.config.isLoading = false;
                    this.hideLoading();
                    this.showError('Erro de conexão ao carregar inscrições: ' + error);
                }
            });
        },

        // Carregar mais inscrições (scroll infinito)
        loadMoreInscricoes: function() {
            if (this.config.isLoadingMore || !this.config.hasMoreItems) {
                return;
            }

            this.config.isLoadingMore = true;
            this.config.currentPage++;
            this.elements.infiniteLoading.show();

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
                    this.elements.infiniteLoading.hide();

                    if (response.success) {
                        const inscricoes = response.data.inscricoes || [];
                        
                        if (inscricoes.length > 0) {
                            this.config.allInscricoes = this.config.allInscricoes.concat(inscricoes);
                            this.appendInscricoes(inscricoes);
                        }
                        
                        // Verificar se há mais itens
                        this.config.hasMoreItems = inscricoes.length === this.config.itemsPerPage;
                        
                        if (!this.config.hasMoreItems) {
                            this.elements.endOfList.show();
                        }
                    }
                },
                error: () => {
                    this.config.isLoadingMore = false;
                    this.elements.infiniteLoading.hide();
                    this.config.currentPage--; // Reverter página em caso de erro
                }
            });
        },

        // Renderizar inscrições
        renderInscricoes: function(inscricoes, reset = true) {
            const $tbody = this.elements.tableBody;
            const template = $('#inscricao-row-template').html();

            if (!inscricoes || inscricoes.length === 0) {
                if (reset) {
                    this.showNoResults();
                }
                return;
            }

            this.hideNoResults();
            this.elements.table.show();

            if (reset) {
                let html = '';
                inscricoes.forEach(inscricao => {
                    html += this.renderInscricaoRow(inscricao, template);
                });
                $tbody.html(html);
            }
        },

        // Adicionar inscrições ao final da lista (scroll infinito)
        appendInscricoes: function(inscricoes) {
            const $tbody = this.elements.tableBody;
            const template = $('#inscricao-row-template').html();

            let html = '';
            inscricoes.forEach(inscricao => {
                html += this.renderInscricaoRow(inscricao, template);
            });

            $tbody.append(html);
        },

        // Função para detectar scroll infinito
        handleScroll: function() {
            if (this.config.isLoadingMore || !this.config.hasMoreItems) {
                return;
            }

            const scrollTop = $(window).scrollTop();
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            const threshold = 200; // Pixels antes do fim da página

            if (scrollTop + windowHeight >= documentHeight - threshold) {
                this.loadMoreInscricoes();
            }
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
                    event_id: eventId,
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