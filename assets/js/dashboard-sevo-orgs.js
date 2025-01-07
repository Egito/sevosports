jQuery(document).ready(function($) {
    // Inicializa o dashboard de organizações
    function initDashboard() {
        // Adiciona classe ao body para estilos específicos
        $('body').addClass('sevo-org-dashboard');

        // Evento para o filtro de proprietários
        $('#proprietario-filter').on('change', function() {
            $(this).closest('form').submit();
        });
    }

    // Inicializa quando o DOM estiver pronto
    initDashboard();
});