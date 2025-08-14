jQuery(document).ready(function($) {
    'use strict';

    const modal = $('#sevo-org-modal');
    const modalContent = $('#sevo-modal-content');
    const closeButton = $('#sevo-modal-close');
    const dashboardContainer = $('.sevo-orgs-dashboard-container');

    // Botão "Criar Nova Organização"
    dashboardContainer.on('click', '#sevo-create-org-button', function() {
        openOrgFormModal();
    });

    // Abre o modal ao clicar num cartão de organização (visualização)
    dashboardContainer.on('click', '.org-card', function() {
        const orgId = $(this).data('org-id');
        openOrgViewModal(orgId);
    });

    // Função para abrir modal de visualização
    function openOrgViewModal(orgId) {
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

        $.ajax({
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

    // Função para abrir modal de formulário (criar/editar)
    function openOrgFormModal(orgId = null) {
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

        $.ajax({
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

    // Event listener para o botão de editar no modal de visualização
    modal.on('click', '.sevo-button-edit', function(e) {
        e.preventDefault();
        const orgId = $(this).attr('href').match(/post=(\d+)/)[1];
        openOrgFormModal(orgId);
    });

    // Event listener para submeter o formulário de organização
    modal.on('submit', '#sevo-org-form', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const saveButton = $(this).find('#sevo-save-org-button');
        const originalText = saveButton.text();
        
        // Adiciona os dados AJAX necessários
        formData.append('action', 'sevo_save_org');
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
                    location.reload(); // Recarrega a página para mostrar as alterações
                } else {
                    alert('Erro: ' + response.data);
                    saveButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Erro de comunicação. Por favor, tente novamente.');
                saveButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Event listener para o botão cancelar no formulário
    modal.on('click', '#sevo-cancel-button', function() {
        closeModal();
    });

    // Função para fechar o modal
    function closeModal() {
        modal.addClass('hidden');
        modalContent.html(''); // Limpa o conteúdo para a próxima abertura
    }

    // Eventos para fechar o modal
    closeButton.on('click', closeModal);

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
