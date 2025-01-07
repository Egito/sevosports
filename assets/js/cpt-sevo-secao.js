jQuery(document).ready(function($) {
    // Função para atualizar o campo de vagas
    function atualizarCampoVagas(eventoId) {
        if (!eventoId) return;

        // Fazer requisição AJAX para obter o número máximo de vagas
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'obter_max_vagas_evento',
                evento_id: eventoId
            },
            success: function(response) {
                if (response.success) {
                    const maxVagas = response.data.max_vagas;
                    
                    // Atualizar o campo de vagas
                    const inputVagas = $('#sevo_secao_vagas');
                    
                    // Definir atributos do input
                    inputVagas.attr({
                        'min': 0,
                        'max': maxVagas,
                        'placeholder': `Máximo: ${maxVagas} vagas`
                    });
                    
                    // Atualizar valor atual se exceder o máximo
                    const vagasAtuais = parseInt(inputVagas.val());
                    if (vagasAtuais > maxVagas) {
                        inputVagas.val(maxVagas);
                    }
                    
                    // Exibir mensagem informativa
                    $('#vagas-info').remove();
                    inputVagas.after(`<p id="vagas-info" class="description">Vagas disponíveis no evento: ${maxVagas}</p>`);
                }
            }
        });
    }

    // Monitorar alterações no campo de evento
    $('#sevo_secao_evento_id').change(function() {
        const eventoId = $(this).val();
        atualizarCampoVagas(eventoId);
    });

    // Inicializar ao carregar a página
    const eventoInicial = $('#sevo_secao_evento_id').val();
    if (eventoInicial) {
        atualizarCampoVagas(eventoInicial);
    }
});