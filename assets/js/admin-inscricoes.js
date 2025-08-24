/**
 * JavaScript para administração de Inscrições
 * Gerencia operações CRUD usando tabelas customizadas
 */

jQuery(document).ready(function($) {
    
    // Variáveis globais
    let currentPage = 1;
    let currentFilters = {};
    
    // Inicialização
    init();
    
    function init() {
        console.log('SevoInscricaoAdmin: Inicializando...');
        loadInscricoesList();
        loadEventosSelect();
        bindEvents();
    }
    
    function bindEvents() {
        // Modal events
        $('#sevo-add-inscricao-btn').on('click', showAddModal);
        $('.sevo-modal-close, #sevo-inscricao-cancel').on('click', hideModal);
        $('#sevo-inscricao-save').on('click', saveInscricao);
        
        // List events
        $(document).on('click', '.sevo-edit-inscricao', editInscricao);
        $(document).on('click', '.sevo-delete-inscricao', deleteInscricao);
        $(document).on('click', '.sevo-aceitar-inscricao', aceitarInscricao);
        $(document).on('click', '.sevo-rejeitar-inscricao', rejeitarInscricao);
        $(document).on('click', '.sevo-cancelar-inscricao', cancelarInscricao);
        $(document).on('click', '.sevo-page-btn', changePage);
        
        // Filter events
        $('#sevo-apply-filters').on('click', applyFilters);
        $('#sevo-clear-filters').on('click', clearFilters);
        
        // Close modal on outside click
        $(window).on('click', function(event) {
            if (event.target.id === 'sevo-inscricao-modal') {
                hideModal();
            }
        });
    }
    
    function showAddModal() {
        $('#sevo-inscricao-modal-title').text('Nova Inscrição');
        $('#sevo-inscricao-form')[0].reset();
        $('#inscricao-id').val('');
        loadEventosForForm();
        $('#sevo-inscricao-modal').show();
    }
    
    function hideModal() {
        $('#sevo-inscricao-modal').hide();
    }
    
    function saveInscricao() {
        const formData = {
            action: $('#inscricao-id').val() ? 'sevo_update_inscricao' : 'sevo_create_inscricao',
            nonce: sevoInscricaoAdmin.nonce,
            id: $('#inscricao-id').val(),
            evento_id: $('#inscricao-evento-id').val(),
            usuario_id: $('#inscricao-usuario-id').val(),
            status: $('#inscricao-status').val(),
            observacoes: $('#inscricao-observacoes').val()
        };
        
        // Validação básica
        if (!formData.evento_id || !formData.usuario_id) {
            showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
            return;
        }
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    hideModal();
                    loadInscricoesList();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function editInscricao() {
        const id = $(this).data('id');
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_get_inscricao',
                nonce: sevoInscricaoAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    const inscricao = response.data;
                    $('#sevo-inscricao-modal-title').text('Editar Inscrição');
                    $('#inscricao-id').val(inscricao.id);
                    $('#inscricao-evento-id').val(inscricao.evento_id);
                    $('#inscricao-usuario-id').val(inscricao.usuario_id);
                    $('#inscricao-status').val(inscricao.status);
                    $('#inscricao-observacoes').val(inscricao.observacoes);
                    
                    loadEventosForForm(inscricao.evento_id);
                    $('#sevo-inscricao-modal').show();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function deleteInscricao() {
        if (!confirm(sevoInscricaoAdmin.strings.confirm_delete)) {
            return;
        }
        
        const id = $(this).data('id');
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_delete_inscricao',
                nonce: sevoInscricaoAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadInscricoesList();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function aceitarInscricao() {
        if (!confirm(sevoInscricaoAdmin.strings.confirm_accept)) {
            return;
        }
        
        const id = $(this).data('id');
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_aceitar_inscricao',
                nonce: sevoInscricaoAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadInscricoesList();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function rejeitarInscricao() {
        if (!confirm(sevoInscricaoAdmin.strings.confirm_reject)) {
            return;
        }
        
        const id = $(this).data('id');
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_rejeitar_inscricao',
                nonce: sevoInscricaoAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadInscricoesList();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function cancelarInscricao() {
        if (!confirm(sevoInscricaoAdmin.strings.confirm_cancel)) {
            return;
        }
        
        const id = $(this).data('id');
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_cancelar_inscricao',
                nonce: sevoInscricaoAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadInscricoesList();
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function loadInscricoesList() {
        console.log('SevoInscricaoAdmin: Carregando inscrições...');
        const data = {
            action: 'sevo_list_inscricoes',
            nonce: sevoInscricaoAdmin.nonce,
            page: currentPage,
            per_page: 20,
            ...currentFilters
        };
        
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('SevoInscricaoAdmin: Resposta recebida:', response);
                if (response.success) {
                    $('#sevo-inscricao-list-container').html(response.data.html);
                } else {
                    showNotification(response.data || sevoInscricaoAdmin.strings.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('SevoInscricaoAdmin: Erro AJAX:', status, error);
                showNotification(sevoInscricaoAdmin.strings.error, 'error');
            }
        });
    }
    
    function loadEventosSelect() {
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_get_eventos_select',
                nonce: sevoInscricaoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#filter-evento').html(response.data.html);
                }
            }
        });
    }
    
    function loadEventosForForm(selectedId = null) {
        $.ajax({
            url: sevoInscricaoAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'sevo_get_eventos_select',
                nonce: sevoInscricaoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#inscricao-evento-id').html(response.data.html);
                    if (selectedId) {
                        $('#inscricao-evento-id').val(selectedId);
                    }
                }
            }
        });
    }
    
    function changePage() {
        currentPage = parseInt($(this).data('page'));
        loadInscricoesList();
    }
    
    function applyFilters() {
        currentFilters = {
            status: $('#filter-status').val(),
            evento_id: $('#filter-evento').val()
        };
        currentPage = 1;
        loadInscricoesList();
    }
    
    function clearFilters() {
        $('#filter-status').val('');
        $('#filter-evento').val('');
        currentFilters = {};
        currentPage = 1;
        loadInscricoesList();
    }
    
    function showNotification(message, type = 'info') {
        // Remove notificações existentes
        $('.sevo-notification').remove();
        
        const notificationClass = type === 'error' ? 'notice-error' : 'notice-success';
        const notification = $(`
            <div class="notice ${notificationClass} is-dismissible sevo-notification">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dispensar este aviso.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notification);
        
        // Auto-remove após 5 segundos
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Bind dismiss button
        notification.find('.notice-dismiss').on('click', function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
});