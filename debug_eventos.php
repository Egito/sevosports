<?php
// Debug script para verificar eventos
require_once('wp-config.php');
require_once('wp-load.php');

// Definir constantes se não estiverem definidas
if (!defined('SEVO_EVENTO_POST_TYPE')) {
    define('SEVO_EVENTO_POST_TYPE', 'sevo-evento');
}

echo "<h2>Debug - Eventos Cadastrados</h2>";

// Buscar todos os eventos
$eventos = get_posts(array(
    'post_type' => SEVO_EVENTO_POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => -1
));

echo "<p>Total de eventos encontrados: " . count($eventos) . "</p>";

if (count($eventos) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Título</th><th>Data Início Inscrições</th><th>Data Fim Inscrições</th><th>Data Início Evento</th><th>Data Fim Evento</th></tr>";
    
    foreach ($eventos as $evento) {
        $data_inicio_inscricoes = get_post_meta($evento->ID, '_sevo_evento_data_inicio_inscricoes', true);
        $data_fim_inscricoes = get_post_meta($evento->ID, '_sevo_evento_data_fim_inscricoes', true);
        $data_inicio_evento = get_post_meta($evento->ID, '_sevo_evento_data_inicio_evento', true);
        $data_fim_evento = get_post_meta($evento->ID, '_sevo_evento_data_fim_evento', true);
        
        echo "<tr>";
        echo "<td>" . $evento->ID . "</td>";
        echo "<td>" . $evento->post_title . "</td>";
        echo "<td>" . ($data_inicio_inscricoes ?: 'N/A') . "</td>";
        echo "<td>" . ($data_fim_inscricoes ?: 'N/A') . "</td>";
        echo "<td>" . ($data_inicio_evento ?: 'N/A') . "</td>";
        echo "<td>" . ($data_fim_evento ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Nenhum evento encontrado.</p>";
}

echo "<h3>Data de hoje: " . date('Y-m-d') . "</h3>";

// Testar consulta de inscrições abertas
echo "<h3>Teste - Inscrições Abertas</h3>";
$today = date('Y-m-d');
$inscricoes_abertas = new WP_Query(array(
    'post_type' => SEVO_EVENTO_POST_TYPE,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_sevo_evento_data_inicio_inscricoes',
            'value' => $today,
            'compare' => '<=',
            'type' => 'DATE'
        ),
        array(
            'key' => '_sevo_evento_data_fim_inscricoes',
            'value' => $today,
            'compare' => '>=',
            'type' => 'DATE'
        )
    )
));

echo "<p>Eventos com inscrições abertas: " . $inscricoes_abertas->found_posts . "</p>";
?>