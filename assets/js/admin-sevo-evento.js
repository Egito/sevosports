jQuery(document).ready(function($) {
    'use strict';

    // Seleciona os elementos do DOM
    const tipoEventoDropdown = $('#sevo_evento_tipo_evento_id');
    const vagasInput = $('#sevo_evento_vagas');
    const vagasInfo = $('#vagas-info');

    /**
     * Atualiza o limite de vagas com base no Tipo de Evento selecionado.
     */
    function atualizarLimiteDeVagas() {
        const selectedOption = tipoEventoDropdown.find('option:selected');
        const maxVagas = selectedOption.data('max-vagas');

        if (maxVagas && maxVagas > 0) {
            // Define o atributo 'max' no campo de vagas
            vagasInput.attr('max', maxVagas);
            // Atualiza o texto de ajuda
            vagasInfo.text('O número de vagas não pode exceder ' + maxVagas + ' (limite do Tipo de Evento).');

            // Se o valor atual for maior que o novo máximo, ajusta-o
            if (parseInt(vagasInput.val()) > maxVagas) {
                vagasInput.val(maxVagas);
            }
        } else {
            // Se nenhum tipo de evento com limite for selecionado, remove o limite
            vagasInput.removeAttr('max');
            vagasInfo.text('O número de vagas não pode exceder o limite definido no Tipo de Evento.');
        }
    }

    // Adiciona o listener para o evento de mudança no dropdown
    tipoEventoDropdown.on('change', function() {
        atualizarLimiteDeVagas();
    });

    // Executa a função uma vez no carregamento da página para definir o estado inicial
    atualizarLimiteDeVagas();
});