jQuery(document).ready(function($) {
    'use strict';

    const modal = $('#sevo-org-modal');
    const modalContent = $('#sevo-modal-content');
    const closeButton = $('#sevo-modal-close');
    const dashboardContainer = $('.sevo-orgs-dashboard-container');

    // Abre o modal ao clicar num cartão de organização
    dashboardContainer.on('click', '.org-card', function() {
        const orgId = $(this).data('org-id');

        // Mostra o modal com um spinner de carregamento
        modalContent.html('<div class="sevo-spinner"></div>');
        modal.removeClass('hidden');

        // Faz a chamada AJAX para buscar os detalhes da organização
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
                    // Insere o HTML recebido no corpo do modal
                    modalContent.html(response.data.html);
                } else {
                    modalContent.html('<p class="text-red-500 text-center">Ocorreu um erro ao carregar os detalhes da organização.</p>');
                }
            },
            error: function() {
                modalContent.html('<p class="text-red-500 text-center">Erro de comunicação. Por favor, tente novamente.</p>');
            }
        });
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
