// Funções globais para os modais
function openOrgViewModal(orgId) {
    console.log('openOrgViewModal chamada com parâmetros:', { orgId: orgId, tipo: typeof orgId });
    
    const modal = jQuery('#sevo-org-modal');
    const modalContent = jQuery('#sevo-modal-content');
    
    console.log('Elementos encontrados:', { modal: modal.length, modalContent: modalContent.length });
    
    modalContent.html('<div class="sevo-spinner"></div>');
    modal.removeClass('hidden').addClass('show').css('display', 'flex');
    // Adiciona classe no body para prevenir conflitos com header do tema
    jQuery('body').addClass('modal-open');

    jQuery.ajax({
        url: sevoOrgsDashboard.ajax_url,
        type: 'POST',
        data: {
            action: 'sevo_get_org_details',
            nonce: sevoOrgsDashboard.nonce,
            org_id: orgId,
        },
        success: function(response) {
            if (response.success) {
                modalContent.html(response.data.html);
            } else {
                modalContent.html('<p class="text-red-500 text-center">Ocorreu um erro ao carregar os detalhes da organização.</p>');
            }
        },
        error: function() {
            modalContent.html('<p class="text-red-500 text-center">Erro de comunicação. Por favor, tente novamente.</p>');
        }
    });
}

function openOrgFormModal(orgId = null) {
    console.log('openOrgFormModal chamada com parâmetros:', { orgId: orgId, tipo: typeof orgId });
    
    const modal = jQuery('#sevo-org-modal');
    const modalContent = jQuery('#sevo-modal-content');
    
    console.log('Elementos encontrados:', { modal: modal.length, modalContent: modalContent.length });
    
    modalContent.html('<div class="sevo-spinner"></div>');
    modal.removeClass('hidden').addClass('show').css('display', 'flex');
    // Adiciona classe no body para prevenir conflitos com header do tema
    jQuery('body').addClass('modal-open');

    jQuery.ajax({
        url: sevoOrgsDashboard.ajax_url,
        type: 'POST',
        data: {
            action: 'sevo_get_org_form',
            nonce: sevoOrgsDashboard.nonce,
            org_id: orgId || 0,
        },
        success: function(response) {
            if (response.success) {
                modalContent.html(response.data.html);
            } else {
                modalContent.html('<p class="text-red-500 text-center">Erro: ' + response.data + '</p>');
            }
        },
        error: function() {
            modalContent.html('<p class="text-red-500 text-center">Erro de comunicação. Por favor, tente novamente.</p>');
        }
    });
}

// Objeto global para o dashboard de organizações
window.SevoOrgsDashboard = {
    viewOrg: function(orgId) {
        console.log('SevoOrgsDashboard.viewOrg chamado com:', orgId);
        openOrgViewModal(orgId);
    },
    editOrg: function(orgId) {
        console.log('SevoOrgsDashboard.editOrg chamado com:', orgId);
        openOrgFormModal(orgId);
    }
};

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

    const modal = $('#sevo-org-modal');
    const modalContent = $('#sevo-modal-content');
    const closeButton = $('#sevo-modal-close');
    const dashboardContainer = $('.sevo-dashboard-wrapper');

    // Botão "Criar Nova Organização"
    dashboardContainer.on('click', '#sevo-create-org-button', function() {
        openOrgFormModal();
    });

    // Abre o modal ao clicar num cartão de organização (visualização)
    $(document).on('click', '.org-card', function(e) {
        // Previne a abertura do modal se clicou em um botão
        if ($(e.target).closest('.card-actions').length > 0) {
            return;
        }
        const orgId = $(this).data('org-id');
        openOrgViewModal(orgId);
    });

    // As funções openOrgViewModal e openOrgFormModal agora estão no escopo global

    // Event listener para o botão de editar no modal de visualização
    modal.on('click', '.sevo-button-edit', function(e) {
        e.preventDefault();
        const orgId = $(this).data('org-id');
        openOrgFormModal(orgId);
    });

    // Event listener para submeter o formulário de organização
    modal.on('submit', '#sevo-org-form', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const saveButton = $(this).find('#sevo-save-org-button');
        const originalText = saveButton.text();
        
        // Determina se é criação ou edição
        const orgId = formData.get('org_id');
        const action = orgId && orgId !== '0' ? 'sevo_update_organizacao' : 'sevo_create_organizacao';
        
        // Adiciona os dados AJAX necessários
        formData.append('action', action);
        formData.append('nonce', sevoOrgsDashboard.nonce);
        
        saveButton.text('A guardar...').prop('disabled', true);

        $.ajax({
            url: sevoOrgsDashboard.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    closeModal();
                    // Armazena a mensagem de sucesso para mostrar após o reload
                    SevoToaster.storeForReload('Organização alterada com sucesso!', 'success');
                    location.reload(); // Recarrega a página para mostrar as alterações
                } else {
                    SevoToaster.showError('Erro: ' + response.data);
                    saveButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                SevoToaster.showError('Erro de comunicação. Por favor, tente novamente.');
                saveButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Event listener para o botão cancelar no formulário
    modal.on('click', '#sevo-cancel-button', function() {
        closeModal();
    });

    // Função para abrir modal com proteção anti-conflito
    function showModal() {
        modal.removeClass('hidden').addClass('show').css('display', 'flex');
        $('body').addClass('modal-open');
    }
    
    // Função para fechar o modal
    function closeModal() {
        modal.addClass('hidden').removeClass('show').css('display', 'none');
        modalContent.html(''); // Limpa o conteúdo para a próxima abertura
        $('body').removeClass('modal-open');
    }
    
    // Expor closeModal globalmente para uso em eventos onclick
    window.closeModal = closeModal;

    // Eventos para fechar o modal
    closeButton.on('click', closeModal);

    // Fecha o modal ao clicar no overlay
    modal.on('click', '.sevo-modal-overlay', function() {
        closeModal();
    });

    // Fecha o modal se o utilizador clicar fora da área de conteúdo
    modal.on('click', function(e) {
        if ($(e.target).is(modal)) {
            closeModal();
        }
    });

    // Fecha o modal se o utilizador pressionar a tecla "Escape"
    $(document).on('keyup', function(e) {
        if (e.key === "Escape") {
            closeModal();
        }
    });

});
