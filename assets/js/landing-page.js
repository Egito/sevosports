/**
 * JavaScript para a Landing Page de Eventos
 * Controla os carrosséis, navegação e interações
 */

jQuery(document).ready(function($) {
    'use strict';

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
            $(document).on('click', '.sevo-carousel-prev', (e) => {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                this.navigateCarousel(section, 'prev');
            });

            $(document).on('click', '.sevo-carousel-next', (e) => {
                const section = $(e.currentTarget).closest('.sevo-carousel-container').data('section');
                this.navigateCarousel(section, 'next');
            });

            // Clique nos cards para abrir o modal
            $(document).on('click', '.sevo-card.evento-card', (e) => {
                // Previne a abertura do modal se clicou em um botão
                if ($(e.target).closest('.sevo-card-actions').length > 0) {
                    return;
                }
                const eventId = $(e.currentTarget).data('event-id');
                this.openEventModal(eventId);
            });

            // Botão de visualizar evento
            $(document).on('click', '.sevo-view-evento', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                this.openEventModal(eventId);
            });

            // Botão de editar evento no card (redireciona)
            $(document).on('click', '.sevo-edit-evento', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                this.editEvent(eventId);
            });

            // Fechar modal
            $(document).on('click', '#sevo-evento-view-modal-close, .sevo-modal-backdrop', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeEventModal();
                }
            });

            // Indicadores do carrossel
            $(document).on('click', '.sevo-carousel-indicator', (e) => {
                const $indicator = $(e.currentTarget);
                const section = $indicator.closest('.sevo-carousel-indicators').data('section');
                const page = $indicator.data('page');
                this.goToPage(section, page);
            });

            // Redimensionamento da janela
            $(window).on('resize', () => {
                this.handleResize();
            });

            // Navegação por teclado
            $(document).on('keydown', (e) => {
                if ($('#sevo-evento-view-modal').hasClass('hidden')) return;
                
                if (e.key === 'Escape') {
                    this.closeEventModal();
                }
            });

            // Botão de inscrição
            $(document).on('click', '.sevo-inscribe-evento', (e) => {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                this.inscribeToEvent(eventId);
            });

            // Botão de cancelar inscrição
            $(document).on('click', '.sevo-cancel-inscricao', (e) => {
                e.preventDefault();
                const inscricaoId = $(e.currentTarget).data('inscricao-id');
                this.cancelInscricao(inscricaoId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', (e) => {
                e.preventDefault();
                this.submitEventForm(e.target);
            });

            // Event listener para fechar modal de edição
            $(document).on('click', '#sevo-evento-form-modal-close', this.closeEventFormModal.bind(this));

            // Event listener para cancelar edição
            $(document).on('click', '#sevo-cancel-evento-button', this.closeEventFormModal.bind(this));

            // Event listener para fechar modal clicando no backdrop
            $(document).on('click', '#sevo-evento-form-modal-container', function(e) {
                if (e.target === e.currentTarget) {
                    SevoLandingPage.closeEventFormModal();
                }
            });

            // Botão de editar evento
            $(document).on('click', '.sevo-edit-evento-modal', (e) => {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                this.editEvent(eventId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', (e) => {
                e.preventDefault();
                this.submitEventForm(e.target);
            });
        },

        // Carrega o conteúdo inicial de todos os carrosséis
        loadInitialContent: function() {
            Object.keys(this.carousels).forEach(section => {
                this.loadCarouselContent(section, 1);
            });
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
                alert('Erro: Dados de configuração não encontrados');
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
                    alert('Erro ao carregar formulário: ' + (data.data || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Erro ao carregar formulário de edição.');
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
                alert('Erro: Dados de configuração não encontrados');
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
                        alert('Evento salvo com sucesso!');
                        // Fechar modal de edição
                        const modalContainer = document.getElementById('sevo-evento-form-modal-container');
                        if (modalContainer) {
                            modalContainer.style.display = 'none';
                            document.body.classList.remove('sevo-modal-open');
                        }
                        // Recarregar a página para mostrar as alterações
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                        if (saveButton) {
                            saveButton.textContent = originalText;
                            saveButton.disabled = false;
                        }
                    }
                },
                error: function() {
                    alert('Erro de comunicação. Por favor, tente novamente.');
                    if (saveButton) {
                        saveButton.textContent = originalText;
                        saveButton.disabled = false;
                    }
                }
            });
        }
    };

    // Inicializa a Landing Page
    SevoLandingPage.init();

    // Torna o objeto global para debug
    window.SevoLandingPage = SevoLandingPage;
});