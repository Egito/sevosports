php
<?php
    // Define a função callback do shortcode
    function sevo_orgs_shortcode_callback() {
        // Parâmetros da query
        $args = array(
            'post_type' => 'sevo_orgs',
            'posts_per_page' => -1 // -1 para exibir todos os posts
        );

        // Criando a query
        $query = new WP_Query($args);

        // Verifica se há resultados
        if ($query->have_posts()) {
            // Inicia o buffer de saída
            ob_start();

            // Inclui o template para exibir as organizações
            include plugin_dir_path(__FILE__) . '../templates/sevo-orgs-template.php';

            // Retorna o conteúdo do buffer
            return ob_get_clean();
        } else {
            return '<p>' . __('Nenhuma organização encontrada.', 'sevosports') . '</p>';
        }
    }

    // Registrar o shortcode
    add_shortcode('sevo-orgs', 'sevo_orgs_shortcode_callback');
?>
