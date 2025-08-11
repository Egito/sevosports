<?php
/**
 * Lógica de integração com o plugin Asgaros Forum.
 *
 * @package Sevo_Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Forum_Integration {

    private $org_post_type = SEVO_ORG_POST_TYPE;
    private $tipo_evento_post_type = SEVO_TIPO_EVENTO_POST_TYPE;
    private $evento_post_type = SEVO_EVENTO_POST_TYPE;

    public function __construct() {
        // Hooks para criar as estruturas do fórum
        add_action('save_post_' . $this->org_post_type, array($this, 'create_forum_category_for_organization'), 10, 2);
        add_action('save_post_' . $this->tipo_evento_post_type, array($this, 'create_forum_for_event_type'), 10, 2);
        add_action('save_post_' . $this->evento_post_type, array($this, 'handle_event_forum_creation_and_topics'), 10, 3);
    }

     /**
     * Cria uma categoria no Asgaros Forum para uma nova organização.
     */
    public function create_forum_category_for_organization($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        // Verifica se a categoria já foi criada para evitar duplicatas
        if (get_post_meta($post_id, '_sevo_forum_category_id', true)) {
            return;
        }

        $category_id = class_exists('AsgarosForum') ? AsgarosForum::add_category(array(
            'name' => $post->post_title,
            'description' => 'Fórum de discussão para a organização ' . $post->post_title,
        )) : 0;

        if ($category_id) {
            update_post_meta($post_id, '_sevo_forum_category_id', $category_id);
        }
    }

    /**
     * Cria um fórum no Asgaros para um novo tipo de evento.
     */
    public function create_forum_for_event_type($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        if (get_post_meta($post_id, '_sevo_forum_forum_id', true)) {
            return;
        }

        $org_id = get_post_meta($post_id, '_sevo_tipo_evento_organizacao_id', true);
        if (!$org_id) {
            return;
        }

        $category_id = get_post_meta($org_id, '_sevo_forum_category_id', true);
        if (!$category_id) {
            return; // Categoria da organização ainda não existe
        }

        $forum_id = class_exists('AsgarosForum') ? AsgarosForum::add_forum(array(
            'name' => $post->post_title,
            'description' => 'Discussões sobre o tipo de evento: ' . $post->post_title,
            'parent_id' => $category_id
        )) : 0;

        if ($forum_id) {
            update_post_meta($post_id, '_sevo_forum_forum_id', $forum_id);
        }
    }

    /**
     * Gerencia a criação de sub-fórum e tópicos automáticos para um evento.
     */
    public function handle_event_forum_creation_and_topics($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        // Etapa 1: Criar o sub-fórum se for um evento novo
        if (!$update) {
            $this->create_sub_forum_for_event($post_id, $post);
        }
        
        // Etapa 2: Verificar se datas foram alteradas e criar tópicos de notificação
        $this->create_notification_topics($post_id);
    }

    /**
     * Cria o sub-fórum para um novo evento.
     */
    private function create_sub_forum_for_event($post_id, $post) {
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        if (!$tipo_evento_id) {
            return;
        }

        $forum_id = get_post_meta($tipo_evento_id, '_sevo_forum_forum_id', true);
        if (!$forum_id) {
            return;
        }

        $sub_forum_id = class_exists('AsgarosForum') ? AsgarosForum::add_forum(array(
            'name' => $post->post_title,
            'description' => 'Tópicos de discussão para o evento: ' . $post->post_title,
            'parent_id' => $forum_id
        )) : 0;

        if ($sub_forum_id) {
            update_post_meta($post_id, '_sevo_forum_subforum_id', $sub_forum_id);
        }
    }

    /**
     * Cria tópicos de notificação quando datas importantes são alteradas.
     */
    private function create_notification_topics($post_id) {
        $sub_forum_id = get_post_meta($post_id, '_sevo_forum_subforum_id', true);
        $author_id = get_post_field('post_author', $post_id);
        $evento_url = get_permalink($post_id);
        $evento_title = get_the_title($post_id);

        if (!$sub_forum_id || !$author_id || !class_exists('AsgarosForum')) {
            return;
        }

        // Mapeamento de campos de data para títulos e conteúdos dos tópicos
        $date_fields = array(
            '_sevo_evento_data_inicio_inscricoes' => array(
                'title' => 'Período de Inscrição Definido!',
                'content' => 'As inscrições para o evento "[evento_titulo]" estarão abertas de [data_inicio] até [data_fim]. <br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            ),
            '_sevo_evento_data_inicio_evento' => array(
                'title' => 'Data do Evento Marcada!',
                'content' => 'O evento "[evento_titulo]" está agendado para acontecer de [data_inicio] a [data_fim]. Prepare-se! <br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            )
        );

        // Tratamento para datas de inscrição
        $data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
        $last_posted_inicio_insc = get_post_meta($post_id, '_topic_posted_inicio_insc', true);

        if ($data_inicio_insc && $data_inicio_insc !== $last_posted_inicio_insc) {
            $data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
            $config = $date_fields['_sevo_evento_data_inicio_inscricoes'];
            $topic_title = str_replace('[evento_titulo]', $evento_title, $config['title']);
            $topic_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_insc)), date_i18n('d/m/Y', strtotime($data_fim_insc)), $evento_url],
                $config['content']
            );
            
            AsgarosForum::add_topic($sub_forum_id, $author_id, $topic_title, $topic_content);
            update_post_meta($post_id, '_topic_posted_inicio_insc', $data_inicio_insc);
        }

        // Tratamento para datas do evento
        $data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $last_posted_inicio_evento = get_post_meta($post_id, '_topic_posted_inicio_evento', true);

        if ($data_inicio_evento && $data_inicio_evento !== $last_posted_inicio_evento) {
            $data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);
            $config = $date_fields['_sevo_evento_data_inicio_evento'];
            $topic_title = str_replace('[evento_titulo]', $evento_title, $config['title']);
            $topic_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_evento)), date_i18n('d/m/Y', strtotime($data_fim_evento)), $evento_url],
                $config['content']
            );

            AsgarosForum::add_topic($sub_forum_id, $author_id, $topic_title, $topic_content);
            update_post_meta($post_id, '_topic_posted_inicio_evento', $data_inicio_evento);
        }
    }
}

new Sevo_Forum_Integration();