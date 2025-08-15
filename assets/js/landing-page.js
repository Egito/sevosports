/**
 * JavaScript para a Landing Page de Eventos
 * Controla os carrosséis, navegação e interações
 */

jQuery(document).ready(function($) {
    'use strict';

    // Verifica se há mensagem de toaster armazenada após reload
    const storedMessage = sessionStorage.getItem('sevo_toaster_message');
    if (storedMessage) {
        try {
            const messageData = JSON.parse(storedMessage);
            // Remove a mensagem do storage
            sessionStorage.removeItem('sevo_toaster_message');
            // Mostra o toaster após um pequeno delay para garantir que a página carregou
            setTimeout(function() {
                if (messageData.type === 'success') {
                    SevoToaster.showSuccess(messageData.message);
                } else if (messageData.type === 'error') {
                    SevoToaster.showError(messageData.message);
                } else if (messageData.type === 'info') {
                    SevoToaster.showInfo(messageData.message);
                }
            }, 500);
        } catch (e) {
            // Remove mensagem corrompida
            sessionStorage.removeItem('sevo_toaster_message');
        }
    }

    // Objeto principal da Landing Page
    const SevoLandingPage = {
        carousels: {},
        
        init: function() {
            this.initCarousels();
            this.bindEvents();
            this.loadInitialContent();
        },

        // Inicializa todos os carrosséis
        initCarousels: function() {
            $('.sevo-carousel-container').each((index, container) => {
                const $container = $(container);
                const section = $container.data('section');
                
                this.carousels[section] = {
                    container: $container,
                    track: $container.find('.sevo-carousel-track'),
                    prevBtn: $container.find('.sevo-carousel-prev'),
                    nextBtn: $container.find('.sevo-carousel-next'),
                    indicators: $container.siblings('.sevo-carousel-indicators'),
                    currentPage: 1,
                    totalPages: 1,
                    cardsPerView: this.getCardsPerView(),
                    isLoading: false
                };
            });
        },

        // Determina quantos cards mostrar por vez baseado na tela
        getCardsPerView: function() {
            const width = $(window).width();
            if (width <= 480) return 1;
            if (width <= 768) return 2;
            if (width <= 1200) return 3;
            return 4;
        },

        // Vincula eventos
        bindEvents: function() {
            // Botões de navegação do carrossel
            $(document).on('click', '.sevo-carousel-prev', function(e) {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                SevoLandingPage.navigateCarousel(section, 'prev');
            });

            $(document).on('click', '.sevo-carousel-next', function(e) {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                SevoLandingPage.navigateCarousel(section, 'next');
            });

            // Clique nos cards para abrir o modal
            $(document).on('click', '.sevo-card.evento-card', function(e) {
                // Previne a abertura do modal se clicou em um botão
                if ($(e.target).closest('.sevo-card-actions').length > 0) {
                    return;
                }
                const eventId = $(e.currentTarget).data('event-id');
                SevoLandingPage.openEventModal(eventId);
            });

            // Botão de visualizar evento
            $(document).on('click', '.sevo-view-evento', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoLandingPage.openEventModal(eventId);
            });

            // Botão de editar evento no card (redireciona)
            $(document).on('click', '.sevo-edit-evento', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoLandingPage.editEvent(eventId);
            });

            // Fechar modal
            $(document).on('click', '#sevo-evento-view-modal-close, .sevo-modal-backdrop', function(e) {
                if (e.target === e.currentTarget) {
                    SevoLandingPage.closeEventModal();
                }
            });

            // Indicadores do carrossel
            $(document).on('click', '.sevo-carousel-indicator', function(e) {
                const $indicator = $(e.currentTarget);
                const section = $indicator.closest('.sevo-carousel-indicators').data('section');
                const page = $indicator.data('page');
                SevoLandingPage.goToPage(section, page);
            });

            // Redimensionamento da janela
            $(window).on('resize', function() {
                SevoLandingPage.handleResize();
            });

            // Navegação por teclado
            $(document).on('keydown', function(e) {
                if ($('#sevo-evento-view-modal').hasClass('hidden')) return;
                
                if (e.key === 'Escape') {
                    SevoLandingPage.closeEventModal();
                }
            });

            // Botão de inscrição
            $(document).on('click', '.sevo-inscribe-evento', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoLandingPage.inscribeToEvent(eventId);
            });

            // Botão de cancelar inscrição
            $(document).on('click', '.sevo-cancel-inscricao', function(e) {
                e.preventDefault();
                const inscricaoId = $(e.currentTarget).data('inscricao-id');
                SevoLandingPage.cancelInscricao(inscricaoId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', function(e) {
                e.preventDefault();
                SevoLandingPage.submitEventForm(e.target);
            });

            // Event listeners para filtros
            $(document).on('change', '.sevo-filter-select', function() {
                SevoLandingPage.applyFilters();
            });

            $(document).on('click', '.sevo-clear-filters-btn', function(e) {
                e.preventDefault();
                SevoLandingPage.clearFilters();
            });

            // Event listener para fechar modal de formulário
            $(document).on('click', '#sevo-evento-form-modal-close', function(e) {
                e.preventDefault();
                SevoLandingPage.closeEventFormModal();
            });

            // Event listener para cancelar edição
            $(document).on('click', '#sevo-cancel-evento-button', function() {
                SevoLandingPage.closeEventFormModal();
            });

            // Event listener para fechar modal clicando no backdrop
            $(document).on('click', '#sevo-evento-form-modal-container', function(e) {
                if (e.target === e.currentTarget) {
                    SevoLandingPage.closeEventFormModal();
                }
            });

            // Botão de editar evento
            $(document).on('click', '.sevo-edit-evento-modal, .sevo-modal-button[data-event-id]', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoLandingPage.editEvent(eventId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', function(e) {
                e.preventDefault();
                SevoLandingPage.submitEventForm(e.target);
            });
        },

        // Carrega o conteúdo inicial de todos os carrosséis
        loadInitialContent: function() {
            Object.keys(this.carousels).forEach(section => {
                this.loadCarouselContent(section, 1);
            });
            this.loadFilterOptions();
        },

        // Carrega conteúdo do carrossel via AJAX
        loadCarouselContent: function(section, page = 1) {
            const carousel = this.carousels[section];
            if (!carousel || carousel.isLoading) return;

            // Verifica se os dados AJAX estão disponíveis
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis para carrossel');
                return;
            }

            carousel.isLoading = true;
            this.showLoading(true);

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_load_carousel_eventos',
                    nonce: ajaxData.nonce,
                    section_type: section,
                    page: page
                },
                success: (response) => {
                    if (response.success) {
                        if (page === 1) {
                            carousel.track.html(response.data.items);
                        } else {
                            carousel.track.append(response.data.items);
                        }
                        
                        carousel.currentPage = response.data.currentPage;
                        carousel.totalPages = response.data.totalPages;
                        
                        this.updateCarouselControls(section);
                        this.updateIndicators(section);
                        this.updateCarouselPosition(section);
                    } else {
                        console.error('Erro ao carregar eventos do carrossel:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao carregar eventos:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                },
                complete: () => {
                    carousel.isLoading = false;
                    this.showLoading(false);
                }
            });
        },

        // Navega no carrossel
        navigateCarousel: function(section, direction) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            const newPage = direction === 'next' ? 
                carousel.currentPage + 1 : 
                carousel.currentPage - 1;

            if (newPage < 1 || newPage > carousel.totalPages) return;

            this.goToPage(section, newPage);
        },

        // Vai para uma página específica
        goToPage: function(section, page) {
            const carousel = this.carousels[section];
            if (!carousel || page < 1 || page > carousel.totalPages) return;

            carousel.currentPage = page;
            this.updateCarouselPosition(section);
            this.updateCarouselControls(section);
            this.updateIndicators(section);
        },

        // Atualiza a posição do carrossel
        updateCarouselPosition: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            const cardWidth = 100 / carousel.cardsPerView;
            const translateX = -(carousel.currentPage - 1) * (cardWidth * carousel.cardsPerView);
            
            carousel.track.css('transform', `translateX(${translateX}%)`);
        },

        // Atualiza os controles do carrossel
        updateCarouselControls: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            carousel.prevBtn.prop('disabled', carousel.currentPage <= 1);
            carousel.nextBtn.prop('disabled', carousel.currentPage >= carousel.totalPages);
        },

        // Atualiza os indicadores
        updateIndicators: function(section) {
            const carousel = this.carousels[section];
            if (!carousel) return;

            let indicatorsHtml = '';
            for (let i = 1; i <= carousel.totalPages; i++) {
                const activeClass = i === carousel.currentPage ? 'active' : '';
                indicatorsHtml += `<div class="sevo-carousel-indicator ${activeClass}" data-page="${i}"></div>`;
            }
            
            carousel.indicators.html(indicatorsHtml);
        },

        // Abre o modal do evento
        openEventModal: function(eventId) {
            console.log('SevoLandingPage.openEventModal chamado com ID:', eventId);
            
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
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis (sevoLandingPage)');
                alert('Erro: Dados de configuração não encontrados');
                return;
            }
            
            console.log('Dados AJAX:', ajaxData);
            
            // Mostra o modal e o loading
            modal.style.display = 'flex';
            loadingIndicator.style.display = 'block';
            modalContent.style.display = 'none';
            
            // Faz a requisição AJAX
            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_view',
                    event_id: eventId,
                    nonce: ajaxData.nonce
                },
                beforeSend: function() {
                    console.log('Enviando requisição AJAX para:', ajaxData.ajax_url);
                    console.log('Dados:', {
                        action: 'sevo_get_evento_view',
                        event_id: eventId,
                        nonce: ajaxData.nonce
                    });
                },
                success: function(response) {
                    console.log('Resposta AJAX recebida:', response);
                    if (response.success) {
                        modalContent.innerHTML = response.data.html;
                        loadingIndicator.style.display = 'none';
                        modalContent.style.display = 'block';
                    } else {
                        console.error('Erro ao carregar evento:', response.data);
                        alert('Erro ao carregar evento: ' + (response.data || 'Erro desconhecido'));
                        this.closeEventModal();
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Erro na requisição AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        xhr: xhr
                    });
                    alert('Erro na requisição AJAX: ' + error);
                    this.closeEventModal();
                }.bind(this)
            });
        },

        // Fecha o modal do evento
        closeEventModal: function() {
            const modal = document.getElementById('sevo-event-modal');
            modal.style.display = 'none';
        },

        // Mostra/esconde indicador de loading
        showLoading: function(show) {
            const $loading = $('#sevo-landing-loading');
            if (show) {
                $loading.show();
            } else {
                $loading.hide();
            }
        },

        // Lida com redimensionamento da tela
        handleResize: function() {
            const newCardsPerView = this.getCardsPerView();
            
            Object.keys(this.carousels).forEach(section => {
                const carousel = this.carousels[section];
                if (carousel.cardsPerView !== newCardsPerView) {
                    carousel.cardsPerView = newCardsPerView;
                    this.updateCarouselPosition(section);
                }
            });
        },

        // Inscrever-se em um evento
        inscribeToEvent: function(eventId) {
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                alert('Erro: Dados de configuração não encontrados');
                return;
            }

            if (!confirm('Deseja se inscrever neste evento?')) {
                return;
            }

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_inscribe_evento',
                    event_id: eventId,
                    nonce: ajaxData.nonce
                },
                beforeSend: function() {
                    $('.sevo-inscribe-evento').prop('disabled', true).text('Processando...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Inscrição realizada com sucesso!');
                        // Recarrega o modal para mostrar o novo status
                        this.openEventModal(eventId);
                    } else {
                        alert('Erro ao realizar inscrição: ' + (response.data || 'Erro desconhecido'));
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Erro na inscrição:', error);
                    alert('Erro na inscrição: ' + error);
                },
                complete: function() {
                    $('.sevo-inscribe-evento').prop('disabled', false).html('<i class="dashicons dashicons-plus-alt"></i> Inscrever-se');
                }
            });
        },

        // Cancelar inscrição
        cancelInscricao: function(inscricaoId) {
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                alert('Erro: Dados de configuração não encontrados');
                return;
            }

            if (!confirm('Deseja cancelar sua inscrição neste evento?')) {
                return;
            }

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_cancel_inscricao',
                    inscricao_id: inscricaoId,
                    nonce: ajaxData.nonce
                },
                beforeSend: function() {
                    $('.sevo-cancel-inscricao').prop('disabled', true).text('Processando...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Inscrição cancelada com sucesso!');
                        // Recarrega o modal para mostrar o novo status
                        const eventId = response.data.event_id;
                        this.openEventModal(eventId);
                    } else {
                        alert('Erro ao cancelar inscrição: ' + (response.data || 'Erro desconhecido'));
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('Erro ao cancelar inscrição:', error);
                    alert('Erro ao cancelar inscrição: ' + error);
                },
                complete: function() {
                    $('.sevo-cancel-inscricao').prop('disabled', false).html('<i class="dashicons dashicons-no"></i> Cancelar Inscrição');
                }
            });
        },

        // Editar evento
        editEvent: function(eventId) {
            this.showLoading(true);
            
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                SevoToaster.showError('Erro: Dados de configuração não encontrados');
                this.showLoading(false);
                return;
            }
            
            const data = {
                action: 'sevo_get_evento_form',
                event_id: eventId,
                nonce: ajaxData.nonce
            };
            
            fetch(ajaxData.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(data => {
                this.showLoading(false);
                if (data.success) {
                    // Fechar modal de visualização
                    this.closeEventModal();
                    
                    // Abrir modal de edição
                    this.openEventFormModal(eventId, data.data.html);
                } else {
                    SevoToaster.showError('Erro ao carregar formulário: ' + (data.data || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                SevoToaster.showError('Erro ao carregar formulário de edição.');
            })
            .finally(() => {
                this.showLoading(false);
            });
        },

        // Abrir modal de formulário de evento
        openEventFormModal: function(eventId, html) {
            const modalContainer = document.getElementById('sevo-evento-form-modal-container');
            if (modalContainer) {
                modalContainer.innerHTML = html;
                modalContainer.style.display = 'flex';
                document.body.classList.add('sevo-modal-open');
            }
        },

        // Fechar modal de formulário de evento
        closeEventFormModal: function() {
            const modalContainer = document.getElementById('sevo-evento-form-modal-container');
            if (modalContainer) {
                modalContainer.style.display = 'none';
                document.body.classList.remove('sevo-modal-open');
            }
        },

        // Submeter formulário de evento
        submitEventForm: function(form) {
            const formData = new FormData(form);
            const saveButton = form.querySelector('#sevo-save-evento-button');
            const originalText = saveButton ? saveButton.textContent : 'Salvar';
            
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                SevoToaster.showError('Erro: Dados de configuração não encontrados');
                return;
            }
            
            // Adiciona os dados AJAX necessários
            formData.append('action', 'sevo_save_evento');
            formData.append('nonce', ajaxData.nonce);
            
            if (saveButton) {
                saveButton.textContent = 'A guardar...';
                saveButton.disabled = true;
            }

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Fechar modal de edição
                        const modalContainer = document.getElementById('sevo-evento-form-modal-container');
                        if (modalContainer) {
                            modalContainer.style.display = 'none';
                            document.body.classList.remove('sevo-modal-open');
                        }
                        // Armazena a mensagem de sucesso para mostrar após o reload
                        SevoToaster.storeForReload('Evento salvo com sucesso!', 'success');
                        // Recarregar a página para mostrar as alterações
                        location.reload();
                    } else {
                        SevoToaster.showError('Erro: ' + response.data);
                        if (saveButton) {
                            saveButton.textContent = originalText;
                            saveButton.disabled = false;
                        }
                    }
                },
                error: function() {
                    SevoToaster.showError('Erro de comunicação. Por favor, tente novamente.');
                    if (saveButton) {
                        saveButton.textContent = originalText;
                        saveButton.disabled = false;
                    }
                }
            });
        },

        // Carrega opções dos filtros
        loadFilterOptions: function() {
            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis para filtros');
                return;
            }

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_filter_options',
                    nonce: ajaxData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.populateFilterOptions(response.data);
                    } else {
                        console.error('Erro ao carregar opções dos filtros:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao carregar opções dos filtros:', error);
                }
            });
        },

        // Popula as opções dos filtros
        populateFilterOptions: function(data) {
            // Organizações
            const $orgSelect = $('#sevo-filter-organizacao');
            if ($orgSelect.length && data.organizacoes) {
                $orgSelect.empty().append('<option value="">Todas as organizações</option>');
                data.organizacoes.forEach(org => {
                    $orgSelect.append(`<option value="${org.id}">${org.nome}</option>`);
                });
            }

            // Tipos de evento
            const $tipoSelect = $('#sevo-filter-tipo');
            if ($tipoSelect.length && data.tipos_evento) {
                $tipoSelect.empty().append('<option value="">Todos os tipos</option>');
                data.tipos_evento.forEach(tipo => {
                    $tipoSelect.append(`<option value="${tipo.id}">${tipo.nome}</option>`);
                });
            }

            // Anos para inscrições
            const $inscricaoSelect = $('#sevo-filter-inscricao');
            if ($inscricaoSelect.length && data.anos_inscricao) {
                $inscricaoSelect.empty().append('<option value="">Qualquer período</option>');
                data.anos_inscricao.forEach(ano => {
                    $inscricaoSelect.append(`<option value="${ano}">${ano}</option>`);
                });
            }

            // Anos para eventos
            const $eventoSelect = $('#sevo-filter-evento');
            if ($eventoSelect.length && data.anos_evento) {
                $eventoSelect.empty().append('<option value="">Qualquer período</option>');
                data.anos_evento.forEach(ano => {
                    $eventoSelect.append(`<option value="${ano}">${ano}</option>`);
                });
            }
        },

        // Aplica os filtros
        applyFilters: function() {
            const filters = {
                organizacao: $('#sevo-filter-organizacao').val() || '',
                tipo: $('#sevo-filter-tipo').val() || '',
                inscricao: $('#sevo-filter-inscricao').val() || '',
                evento: $('#sevo-filter-evento').val() || ''
            };

            const ajaxData = window.sevoLandingPage || window.sevoLandingPageData;
            if (!ajaxData) {
                console.error('Dados AJAX não disponíveis para filtros');
                return;
            }

            this.showLoading(true);

            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_filter_eventos',
                    nonce: ajaxData.nonce,
                    filters: filters
                },
                success: (response) => {
                    if (response.success) {
                        this.updateFilteredContent(response.data);
                    } else {
                        console.error('Erro ao aplicar filtros:', response.data);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erro ao aplicar filtros:', error);
                },
                complete: () => {
                    this.showLoading(false);
                }
            });
        },

        // Atualiza o conteúdo filtrado
        updateFilteredContent: function(data) {
            // Atualiza cada seção com os eventos filtrados
            Object.keys(this.carousels).forEach(section => {
                const carousel = this.carousels[section];
                if (data[section]) {
                    carousel.track.html(data[section].items);
                    carousel.currentPage = data[section].currentPage || 1;
                    carousel.totalPages = data[section].totalPages || 1;
                    this.updateCarouselControls(section);
                    this.updateIndicators(section);
                    this.updateCarouselPosition(section);
                }
            });
        },

        // Limpa todos os filtros
        clearFilters: function() {
            $('.sevo-filter-select').val('');
            this.loadInitialContent();
        }
    };

    // Inicializa a Landing Page
    SevoLandingPage.init();

    // Torna o objeto global para debug
    window.SevoLandingPage = SevoLandingPage;
});