/**
 * JavaScript para o Dashboard de Eventos
 * Controla filtros, modais e interações
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

    // Objeto principal do Dashboard de Eventos
    const SevoEventosDashboard = {
        
        init: function() {
            this.bindEvents();
            this.loadFilterOptions();

            // Garante que o modal não herde contextos de transform do tema
            // Movendo-o para diretamente sob <body> evita deslocamento fora da viewport
            const $modalRoot = $('#sevo-event-modal');
            if ($modalRoot.length && !$modalRoot.parent().is('body')) {
                $modalRoot.appendTo('body');
            }
        },

        // Vincula eventos
        bindEvents: function() {
            // Clique nos cards para abrir o modal
            $(document).on('click', '.sevo-event-card, .sevo-evento-card, .sevo-card.evento-card', function(e) {
                if ($(e.target).closest('.card-actions, .sevo-card-actions').length > 0) {
                    return;
                }
                const $card = $(e.target).closest('[data-event-id], [data-evento-id]');
                const eventId = $card.data('event-id') || $card.data('evento-id');
                if (!eventId) return;
                SevoEventosDashboard.openEventModal(eventId);
            });

            // Botão de visualizar evento
            $(document).on('click', '.btn-view-event', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.openEventModal(eventId);
            });

            // Botão de editar evento
            $(document).on('click', '.btn-edit-event', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.editEvent(eventId);
            });

            // Botão de criar novo evento
            $(document).on('click', '#sevo-create-evento-button', function(e) {
                e.preventDefault();
                SevoEventosDashboard.editEvent(0); // 0 para novo evento
            });

            // Fechar modal
            $(document).on('click', '#sevo-evento-view-modal-close, .sevo-modal-backdrop, .sevo-modal-overlay', function(e) {
                if (e.target === e.currentTarget) {
                    SevoEventosDashboard.closeEventModal();
                }
            });

            // Navegação por teclado
            $(document).on('keydown', function(e) {
                if ($('#sevo-event-modal').is(':visible')) {
                    if (e.key === 'Escape') {
                        SevoEventosDashboard.closeEventModal();
                    }
                }
                if ($('#sevo-evento-form-modal-container').is(':visible')) {
                    if (e.key === 'Escape') {
                        SevoEventosDashboard.closeEventFormModal();
                    }
                }
            });

            // Botão de inscrição
            $(document).on('click', '.btn-inscribe-event', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.inscribeToEvent(eventId);
            });

            // Botão de cancelar inscrição
            $(document).on('click', '.btn-cancel-inscription', function(e) {
                e.preventDefault();
                const inscricaoId = $(e.currentTarget).data('inscricao-id');
                SevoEventosDashboard.cancelInscricao(inscricaoId);
            });

            // Event listener para submeter formulário de evento
            $(document).on('submit', '#sevo-evento-form', function(e) {
                e.preventDefault();
                SevoEventosDashboard.submitEventForm(e.target);
            });

            // Event listeners para filtros
            $(document).on('change', '.sevo-filter-select', function() {
                SevoEventosDashboard.applyFilters();
            });

            $(document).on('click', '.sevo-clear-filters-btn', function(e) {
                e.preventDefault();
                SevoEventosDashboard.clearFilters();
            });

            // Event listener para fechar modal de formulário
            $(document).on('click', '#sevo-evento-form-modal-close', function(e) {
                e.preventDefault();
                SevoEventosDashboard.closeEventFormModal();
            });

            // Event listener para cancelar edição
            $(document).on('click', '#sevo-cancel-evento-button', function() {
                SevoEventosDashboard.closeEventFormModal();
            });

            // Event listener para fechar modal clicando no backdrop
            $(document).on('click', '#sevo-evento-form-modal-container', function(e) {
                if (e.target === e.currentTarget) {
                    SevoEventosDashboard.closeEventFormModal();
                }
            });

            // Botão de editar evento no modal
            $(document).on('click', '.sevo-edit-evento-modal, .sevo-modal-button[data-event-id]', function(e) {
                e.preventDefault();
                const eventId = $(e.currentTarget).data('event-id');
                SevoEventosDashboard.editEvent(eventId);
            });
        },

        // Carrega opções dos filtros
        loadFilterOptions: function() {
            // Carrega organizações
            this.loadFilterOption('organizacao', '#filter-organizacao');
            // Carrega tipos de evento
            this.loadFilterOption('tipo_evento', '#filter-tipo');
        },

        // Carrega opções de um filtro específico
        loadFilterOption: function(filterType, selectElement) {
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_load_filter_options',
                    filter_type: filterType,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $select = $(selectElement);
                        const currentValue = $select.val();
                        
                        // Limpa opções existentes (exceto a primeira)
                        $select.find('option:not(:first)').remove();
                        
                        // Adiciona novas opções
                        response.data.options.forEach(function(option) {
                            $select.append(`<option value="${option.value}">${option.label}</option>`);
                        });
                        
                        // Restaura valor selecionado se ainda existir
                        if (currentValue) {
                            $select.val(currentValue);
                        }
                    }
                },
                error: function() {
                    console.error('Erro ao carregar opções do filtro:', filterType);
                }
            });
        },

        // Aplica filtros
        applyFilters: function() {
            const filters = {
                organizacao: $('#filter-organizacao').val(),
                tipo_evento: $('#filter-tipo').val(),
                status: $('#filter-status').val()
            };

            $('#sevo-eventos-loading').show();
            $('#eventos-container').hide();

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_filter_eventos_dashboard',
                    organizacao: filters.organizacao,
                    tipo_evento: filters.tipo_evento,
                    status: filters.status,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    $('#sevo-eventos-loading').hide();
                    $('#eventos-container').show();
                    
                    if (response.success) {
                        $('#eventos-container').html(response.data.html);
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao aplicar filtros.');
                    }
                },
                error: function() {
                    $('#sevo-eventos-loading').hide();
                    $('#eventos-container').show();
                    SevoToaster.showError('Erro ao aplicar filtros.');
                }
            });
        },

        // Limpa filtros
        clearFilters: function() {
            $('.sevo-filter-select').val('');
            this.applyFilters();
        },

        // Abre modal do evento
        openEventModal: function(eventId) {
            const $modal = $('#sevo-event-modal');
            const $loading = $modal.find('.sevo-modal-loading');
            const $content = $modal.find('.sevo-modal-content');

            $('body').addClass('sevo-modal-open');
            $modal.addClass('show').css('display', 'flex');
            $loading.show();
            $content.empty();

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_view',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    $loading.hide();
                    if (response.success) {
                        $content.html(response.data.html);
                        SevoEventosDashboard.centerEventModal();
                    } else {
                        $content.html('<p>Erro ao carregar evento.</p>');
                        SevoToaster.showError(response.data || 'Erro ao carregar evento.');
                        SevoEventosDashboard.centerEventModal();
                    }
                },
                error: function() {
                    $loading.hide();
                    $content.html('<p>Erro ao carregar evento.</p>');
                    SevoToaster.showError('Erro ao carregar evento.');
                    SevoEventosDashboard.centerEventModal();
                }
            });
        },

        // Fecha modal do evento
        closeEventModal: function() {
            $('body').removeClass('sevo-modal-open');
            $('#sevo-event-modal .sevo-modal-container').attr('style', '');
            $('#sevo-event-modal').removeClass('show').css('display', 'none');
        },

        // Edita evento
        editEvent: function(eventId) {
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_evento_form',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    const $modal = $('#sevo-event-modal');
                    const $loading = $modal.find('.sevo-modal-loading');
                    const $content = $modal.find('.sevo-modal-content');
                    $('body').addClass('sevo-modal-open');
                    $modal.addClass('show').css('display', 'flex');
                    $loading.hide();
                    if (response.success) {
                        $content.html(response.data.html);
                        SevoEventosDashboard.initImageUpload();
                        SevoEventosDashboard.initEditFormUI();
                        SevoEventosDashboard.centerEventModal();
                    } else {
                        $content.html('<p>Erro ao carregar formulário.</p>');
                        SevoToaster.showError(response.data || 'Erro ao carregar formulário.');
                        SevoEventosDashboard.centerEventModal();
                    }
                },
                error: function() {
                    const $modal = $('#sevo-event-modal');
                    const $content = $modal.find('.sevo-modal-content');
                    $('body').addClass('sevo-modal-open');
                    $modal.addClass('show').css('display', 'flex');
                    $content.html('<p>Erro ao carregar formulário.</p>');
                    SevoToaster.showError('Erro ao carregar formulário.');
                    SevoEventosDashboard.centerEventModal();
                }
            });
        },

        // Fecha modal de formulário
        closeEventFormModal: function() {
            SevoEventosDashboard.closeEventModal();
        },

        // Inicializa upload de imagem para modal de evento
        initImageUpload: function() {
            // Clique no botão de upload do evento
            $(document).off('click', '#evento-upload-image-btn').on('click', '#evento-upload-image-btn', function(e) {
                e.preventDefault();
                $('#evento-image-file-input').click();
            });
            
            // Clique no placeholder da imagem do evento
            $(document).off('click', '#evento-image-placeholder').on('click', '#evento-image-placeholder', function(e) {
                e.preventDefault();
                $('#evento-image-file-input').click();
            });
            
            // Mudança no input de arquivo do evento
            $(document).off('change', '#evento-image-file-input').on('change', '#evento-image-file-input', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validação do tipo de arquivo
                    if (!file.type.startsWith('image/')) {
                        SevoToaster.showError('Por favor, selecione apenas arquivos de imagem.');
                        return;
                    }
                    
                    // Validação do tamanho (máximo 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        SevoToaster.showError('A imagem deve ter no máximo 5MB.');
                        return;
                    }
                    
                    // Preview da imagem
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const $preview = $('#evento-image-preview-container');
                        const $uploadBtn = $('#evento-upload-image-btn');
                        
                        // Adiciona a imagem de preview
                        $preview.html(`
                            <img src="${e.target.result}" alt="Preview da imagem" id="evento-preview-image">
                            <button type="button" class="sevo-remove-image" id="evento-remove-image-btn" title="Remover imagem">×</button>
                        `);
                        
                        // Atualiza o texto do botão
                        $uploadBtn.html('<i class="dashicons dashicons-upload"></i> Alterar Imagem');
                        
                        // Limpa o campo hidden para que o PHP processe o arquivo
                        $('#evento_imagem_url').val('');
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Remover imagem do evento
            $(document).off('click', '#evento-remove-image-btn, #evento-remove-image-action').on('click', '#evento-remove-image-btn, #evento-remove-image-action', function(e) {
                e.preventDefault();
                
                const $preview = $('#evento-image-preview-container');
                const $uploadBtn = $('#evento-upload-image-btn');
                const $fileInput = $('#evento-image-file-input');
                const $imageUrlInput = $('#evento_imagem_url');
                
                // Limpa os inputs
                $fileInput.val('');
                $imageUrlInput.val('');
                
                // Restaura o placeholder
                $preview.html(`
                    <div class="sevo-image-placeholder" id="evento-image-placeholder">
                        <i class="dashicons dashicons-camera"></i>
                        <p>Clique para carregar uma imagem</p>
                        <small>Recomendado: 800x400 pixels</small>
                    </div>
                `);
                
                // Atualiza o texto do botão
                $uploadBtn.html('<i class="dashicons dashicons-upload"></i> Carregar Imagem');
            });
        },

        centerEventModal: function() {
            const $container = $('#sevo-event-modal .sevo-modal-container');
            if ($container.length === 0) return;
            // Centraliza o modal sempre em relação à viewport e garante rolagem interna
            $container.css({
                position: 'fixed',
                left: '50%',
                top: '50%',
                transform: 'translate(-50%, -50%)',
                maxHeight: 'calc(100vh - 40px)'
            });
        },

        // Submete formulário de evento
        submitEventForm: function(form) {
            const $form = $(form);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Salvando...');

            // Criar FormData para suportar upload de arquivo
            const formData = new FormData($form[0]);
            formData.append('action', 'sevo_save_evento');
            formData.append('nonce', sevoEventosDashboard.nonce);

            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        SevoEventosDashboard.closeEventFormModal();
                        
                        // Armazena mensagem para mostrar após reload
                        sessionStorage.setItem('sevo_toaster_message', JSON.stringify({
                            type: 'success',
                            message: response.data.message
                        }));
                        
                        // Recarrega a página para mostrar as mudanças
                        location.reload();
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao salvar evento.');
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                    SevoToaster.showError('Erro ao salvar evento.');
                }
            });
        },

        initEditFormUI: function() {
            const $modal = $('#sevo-event-modal');
            const $form = $modal.find('#sevo-evento-form');
            const $save = $modal.find('#sevo-save-evento-button');
            const $tabs = $modal.find('.sevo-tab');
            const $contents = $modal.find('.sevo-tab-content');

            $tabs.off('click').on('click', function() {
                const tab = $(this).data('tab');
                $tabs.removeClass('active');
                $(this).addClass('active');
                $contents.removeClass('active');
                $contents.filter(`[data-tab="${tab}"]`).addClass('active');
            });

            const validate = function() {
                const titulo = $form.find('#evento_titulo').val().trim();
                const tipo = $form.find('#evento_tipo_evento_id').val();
                const status = $form.find('#evento_status').val();
                const dii = $form.find('#evento_data_inicio_inscricao').val();
                const dfi = $form.find('#evento_data_fim_inscricao').val();
                const de = $form.find('#evento_data_inicio').val();
                const df = $form.find('#evento_data_fim').val();

                let ok = true;
                ok = ok && titulo.length >= 3;
                ok = ok && tipo && tipo !== '';
                ok = ok && status && status !== '';
                ok = ok && dii && dfi && de && df;
                if (ok) {
                    const diid = new Date(dii);
                    const dfid = new Date(dfi);
                    const ded = new Date(de);
                    const dfd = new Date(df);
                    ok = ok && diid <= dfid && ded <= dfd && dfid <= ded;
                }
                $save.prop('disabled', !ok);
                return ok;
            };

            $form.find('input, select, textarea').on('input change', validate);
            validate();

            $form.off('submit').on('submit', function(e) {
                if (!validate()) {
                    e.preventDefault();
                    SevoToaster.showError('Preencha os campos obrigatórios corretamente.');
                }
            });
        },

        // Inscreve em evento
        inscribeToEvent: function(eventId) {
            // Encontra o botão de inscrição para este evento
            const inscribeBtn = document.querySelector(`.btn-inscribe-event[data-event-id="${eventId}"]`);
            
            $.ajax({
                url: sevoEventosDashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_inscribe_evento',
                    evento_id: eventId,
                    nonce: sevoEventosDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SevoToaster.showSuccess(response.data.message);
                        
                        // Atualiza o botão dinamicamente
                        if (inscribeBtn && response.data.inscricao_id) {
                            // Muda para botão de cancelar inscrição
                            inscribeBtn.className = 'btn-cancel-inscription';
                            inscribeBtn.innerHTML = '<i class="dashicons dashicons-dismiss"></i>';
                            inscribeBtn.title = 'Cancelar Inscrição';
                            
                            // Remove o data-event-id e adiciona data-inscricao-id
                            inscribeBtn.removeAttribute('data-event-id');
                            inscribeBtn.setAttribute('data-inscricao-id', response.data.inscricao_id);
                        }
                    } else {
                        SevoToaster.showError(response.data || 'Erro ao se inscrever.');
                    }
                },
                error: function() {
                    SevoToaster.showError('Erro ao se inscrever.');
                }
            });
        },

        // Cancela inscrição
        cancelInscricao: function(inscricaoId) {
            SevoPopup.confirm('Tem certeza que deseja cancelar sua inscrição?', {
                title: 'Cancelar Inscrição',
                confirmText: 'Sim, cancelar',
                cancelText: 'Não, manter'
            }).then(confirmed => {
                if (!confirmed) {
                    return;
                }

                // Encontra o botão de cancelamento para esta inscrição
                const cancelBtn = document.querySelector(`.btn-cancel-inscription[data-inscricao-id="${inscricaoId}"]`);
                
                $.ajax({
                    url: sevoEventosDashboard.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sevo_cancel_inscricao',
                        inscricao_id: inscricaoId,
                        nonce: sevoEventosDashboard.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            SevoToaster.showSuccess(response.data.message);
                            
                            // Atualiza o botão dinamicamente
                            if (cancelBtn && response.data.evento_id) {
                                // Muda para botão de inscrição
                                cancelBtn.className = 'btn-inscribe-event';
                                cancelBtn.innerHTML = '<i class="dashicons dashicons-plus-alt"></i>';
                                cancelBtn.title = 'Inscrever-se';
                                
                                // Remove o data-inscricao-id e adiciona data-event-id
                                cancelBtn.removeAttribute('data-inscricao-id');
                                cancelBtn.setAttribute('data-event-id', response.data.evento_id);
                            }
                        } else {
                            SevoToaster.showError(response.data || 'Erro ao cancelar inscrição.');
                        }
                    },
                    error: function() {
                        SevoToaster.showError('Erro ao cancelar inscrição.');
                    }
                });
            });
        }
    };

    // Torna o objeto disponível globalmente
    window.SevoEventosDashboard = SevoEventosDashboard;

    // Inicializa quando o documento estiver pronto
    SevoEventosDashboard.init();

    // === FUNCIONALIDADE DO CARROSSEL DE EVENTOS ===
    
    /**
     * Classe para gerenciar carrosseis de eventos
     */
    class SevoEventCarousel {
        constructor(container) {
            this.container = container;
            this.track = container.querySelector('.sevo-carousel-track');
            this.prevBtn = container.querySelector('.carousel-btn.prev-btn');
            this.nextBtn = container.querySelector('.carousel-btn.next-btn');
            this.currentPosition = 0;
            this.cardWidth = 240;
            this.gap = 16; // 1rem em pixels
            this.visibleCards = 1;
            this.totalCards = 0;
            this.autoPlayInterval = null;
            this.autoPlayDelay = 5000; // 5 segundos
            this.isAutoPlayEnabled = true;
            
            this.init();
        }
        
        init() {
            if (!this.track) return;
            
            this.calculateDimensions();
            this.bindEvents();
            this.updateButtons();
            
            // Inicia autoplay se habilitado
            if (this.isAutoPlayEnabled) {
                this.startAutoPlay();
            }
        }
        
        calculateDimensions() {
            const containerWidth = this.container.offsetWidth;
            const firstCard = this.track && this.track.children && this.track.children[0] ? this.track.children[0] : null;
            if (firstCard) {
                this.cardWidth = firstCard.getBoundingClientRect().width;
            }
            this.visibleCards = Math.floor(containerWidth / (this.cardWidth + this.gap));
            this.visibleCards = Math.max(1, this.visibleCards); // Pelo menos 1 card visível
            
            this.totalCards = this.track.children.length;
            
            // Ajusta posição se necessário
            const maxPosition = Math.max(0, this.totalCards - this.visibleCards);
            if (this.currentPosition > maxPosition) {
                this.currentPosition = maxPosition;
            }
            
            this.updatePosition();
        }
        
        bindEvents() {
            // Navegação por clique
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => {
                    this.prev();
                    this.resetAutoPlay();
                });
            }
            
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => {
                    this.next();
                    this.resetAutoPlay();
                });
            }
            
            // Navegação por roda do mouse
            this.container.addEventListener('wheel', (e) => {
                e.preventDefault();
                if (e.deltaY > 0) {
                    this.next();
                } else {
                    this.prev();
                }
                this.resetAutoPlay();
            }, { passive: false }); // passive: false because we call preventDefault
            
            // Navegação por toque/swipe
            let startX = 0;
            let isDragging = false;
            
            this.track.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isDragging = true;
                this.pauseAutoPlay();
            }, { passive: true }); // passive: true because we don't call preventDefault here
            
            this.track.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
            }, { passive: false }); // passive: false because we call preventDefault
            
            this.track.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                
                const endX = e.changedTouches[0].clientX;
                const diff = startX - endX;
                
                if (Math.abs(diff) > 50) { // Mínimo de 50px para considerar swipe
                    if (diff > 0) {
                        this.next();
                    } else {
                        this.prev();
                    }
                }
                
                isDragging = false;
                this.resetAutoPlay();
            }, { passive: true }); // passive: true because we don't call preventDefault here
            
            // Redimensionamento da janela
            window.addEventListener('resize', () => {
                this.recalculateAndAdjust();
            });
            
            // Pausa autoplay ao hover
            this.container.addEventListener('mouseenter', () => {
                this.pauseAutoPlay();
            });
            
            this.container.addEventListener('mouseleave', () => {
                if (this.isAutoPlayEnabled) {
                    this.startAutoPlay();
                }
            });
        }
        
        prev() {
            if (this.currentPosition > 0) {
                this.currentPosition--;
                this.updatePosition();
                this.updateButtons();
            } else if (this.totalCards > this.visibleCards) {
                // Scroll infinito - vai para o final
                this.currentPosition = this.totalCards - this.visibleCards;
                this.updatePosition();
                this.updateButtons();
            }
        }
        
        next() {
            const maxPosition = this.totalCards - this.visibleCards;
            if (this.currentPosition < maxPosition) {
                this.currentPosition++;
                this.updatePosition();
                this.updateButtons();
            } else if (this.totalCards > this.visibleCards) {
                // Scroll infinito - volta para o início
                this.currentPosition = 0;
                this.updatePosition();
                this.updateButtons();
            }
        }
        
        updatePosition() {
            const translateX = -(this.currentPosition * (this.cardWidth + this.gap));
            this.track.style.transform = `translateX(${translateX}px)`;
        }
        
        updateButtons() {
            if (!this.prevBtn || !this.nextBtn) return;
            
            if (this.totalCards <= this.visibleCards) {
                // Se todos os cards cabem na tela, desabilita ambos os botões
                this.prevBtn.disabled = true;
                this.nextBtn.disabled = true;
            } else {
                // Com scroll infinito, os botões nunca ficam desabilitados
                this.prevBtn.disabled = false;
                this.nextBtn.disabled = false;
            }
        }
        
        recalculateAndAdjust() {
            // Debounce para evitar muitos cálculos durante redimensionamento
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.calculateDimensions();
                this.updateButtons();
            }, 150);
        }
        
        // Métodos para adicionar/remover eventos dinamicamente
        addEvent(eventHtml) {
            this.track.insertAdjacentHTML('beforeend', eventHtml);
            this.totalCards++;
            this.calculateDimensions();
            this.updateButtons();
        }
        
        removeEvent(index) {
            const card = this.track.children[index];
            if (card) {
                card.remove();
                this.totalCards--;
                this.calculateDimensions();
                this.updateButtons();
            }
        }
        
        // Método para atualizar um carrossel específico
        updateCarousel(newHtml) {
            this.track.innerHTML = newHtml;
            this.totalCards = this.track.children.length;
            this.currentPosition = 0;
            this.calculateDimensions();
            this.updateButtons();
        }
        
        // Método para navegar para um card específico
        goToCard(index) {
            const maxPosition = this.totalCards - this.visibleCards;
            this.currentPosition = Math.min(Math.max(0, index), maxPosition);
            this.updatePosition();
            this.updateButtons();
        }
        
        // === FUNCIONALIDADE DE AUTO-PLAY ===
        
        enableAutoPlay() {
            this.isAutoPlayEnabled = true;
            this.startAutoPlay();
        }
        
        disableAutoPlay() {
            this.isAutoPlayEnabled = false;
            this.stopAutoPlay();
        }
        
        startAutoPlay() {
            if (!this.isAutoPlayEnabled || this.totalCards <= this.visibleCards) return;
            
            this.stopAutoPlay(); // Para qualquer autoplay existente
            
            this.autoPlayInterval = setInterval(() => {
                this.next();
            }, this.autoPlayDelay);
        }
        
        stopAutoPlay() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
                this.autoPlayInterval = null;
            }
        }
        
        pauseAutoPlay() {
            this.stopAutoPlay();
        }
        
        resetAutoPlay() {
            if (this.isAutoPlayEnabled) {
                this.stopAutoPlay();
                // Reinicia após um delay
                setTimeout(() => {
                    if (this.isAutoPlayEnabled) {
                        this.startAutoPlay();
                    }
                }, 2000);
            }
        }
    }
    
    // Inicializa carrosseis quando o documento estiver pronto
    function initCarousels() {
        const carousels = document.querySelectorAll('.sevo-carousel-container');
        carousels.forEach(container => {
            new SevoEventCarousel(container);
        });
    }
    
    // Inicializa carrosseis
    initCarousels();
    
    // Reinicializa carrosseis após atualizações AJAX
    $(document).ajaxComplete(function() {
        // Pequeno delay para garantir que o DOM foi atualizado
        setTimeout(initCarousels, 100);
    });
    
    // Gerenciamento de upload de imagem
    function initImageUpload() {
        // Clique no botão de upload
        $(document).on('click', '#sevo-upload-btn', function(e) {
            e.preventDefault();
            $('#featured_image').click();
        });
        
        // Clique no placeholder da imagem
        $(document).on('click', '#sevo-image-placeholder', function(e) {
            e.preventDefault();
            $('#featured_image').click();
        });
        
        // Mudança no input de arquivo
        $(document).on('change', '#featured_image', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validação do tipo de arquivo
                if (!file.type.startsWith('image/')) {
                    SevoToaster.showError('Por favor, selecione apenas arquivos de imagem.');
                    return;
                }
                
                // Validação do tamanho (máximo 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    SevoToaster.showError('A imagem deve ter no máximo 5MB.');
                    return;
                }
                
                // Preview da imagem
                const reader = new FileReader();
                reader.onload = function(e) {
                    const $preview = $('#sevo-image-preview');
                    const $placeholder = $('#sevo-image-placeholder');
                    const $uploadBtn = $('#sevo-upload-btn');
                    
                    // Remove placeholder se existir
                    $placeholder.remove();
                    
                    // Adiciona a imagem de preview
                    $preview.html(`
                        <img src="${e.target.result}" alt="Preview da imagem" id="sevo-preview-img">
                        <button type="button" class="sevo-remove-image" id="sevo-remove-image">&times;</button>
                    `);
                    
                    // Atualiza o texto do botão
                    $uploadBtn.text('Alterar Imagem');
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Remover imagem
        $(document).on('click', '#sevo-remove-image', function(e) {
            e.preventDefault();
            
            const $preview = $('#sevo-image-preview');
            const $uploadBtn = $('#sevo-upload-btn');
            const $fileInput = $('#featured_image');
            const $imageIdInput = $('#featured_image_id');
            
            // Limpa os inputs
            $fileInput.val('');
            $imageIdInput.val('');
            
            // Restaura o placeholder
            $preview.html(`
                <div class="sevo-image-placeholder" id="sevo-image-placeholder">
                    <span>Clique para adicionar imagem</span>
                </div>
            `);
            
            // Atualiza o texto do botão
            $uploadBtn.text('Adicionar Imagem');
        });
    }
    
    // Inicializa upload de imagem
    initImageUpload();
    
    // Torna a classe disponível globalmente para uso externo
    window.SevoEventCarousel = SevoEventCarousel;
    
    // Torna o objeto principal disponível globalmente
    window.SevoEventosDashboard = SevoEventosDashboard;
    
    // Alias para compatibilidade com templates que usam SevoEventosAdmin
    window.SevoEventosAdmin = {
        closeModal: SevoEventosDashboard.closeEventFormModal
    };
    
});
