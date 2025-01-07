jQuery(document).ready(function($) {
    // Inicialização do plugin
    console.log('Sevo Eventos JS carregado');

    // Manipulador de eventos AJAX básico
    $(document).on('click', '.sevo-ajax-action', function(e) {
        e.preventDefault();
        
        var data = {
            action: 'sevo_eventos_action',
            _ajax_nonce: sevoEventos.nonce
        };

        $.post(sevoEventos.ajaxurl, data, function(response) {
            console.log('Resposta AJAX:', response);
        });
    });
});