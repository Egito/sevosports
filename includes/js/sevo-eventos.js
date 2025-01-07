jQuery(document).ready(function($) {
    // Validação de datas e vagas ao alterar os campos
    $('#sevo_secao_inicio_inscricoes, #sevo_secao_fim_inscricoes, #sevo_secao_inicio_secao, #sevo_secao_fim_secao, #sevo_secao_vagas').change(function() {
        validar_secao();
    });

    function validar_secao() {
        var data = {
            'action': 'sevo_eventos_validar_secao',
            'inicio_inscricoes': $('#sevo_secao_inicio_inscricoes').val(),
            'fim_inscricoes': $('#sevo_secao_fim_inscricoes').val(),
            'inicio_secao': $('#sevo_secao_inicio_secao').val(),
            'fim_secao': $('#sevo_secao_fim_secao').val(),
            'vagas': $('#sevo_secao_vagas').val(),
            'evento': $('#sevo_secao_evento').val() // ID do evento selecionado
        };

        $.post(ajaxurl, data, function(response) {
            // Exibe a mensagem de erro ou sucesso
            $('#sevo_secao_validation_message').html(response);
        });
    }
});
