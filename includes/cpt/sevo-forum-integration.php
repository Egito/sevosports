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
     * Cria ou atualiza uma categoria no Asgaros Forum para uma organização.
     */
    public function create_forum_category_for_organization($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }

        global $asgarosforum;
        $existing_category_id = get_post_meta($post_id, '_sevo_forum_category_id', true);
        $organization_name = $post->post_title;
        
        // Se já existe uma categoria, verificar se precisa atualizar o nome
        if ($existing_category_id) {
            $category = get_term($existing_category_id, 'asgarosforum-category');
            if ($category && !is_wp_error($category)) {
                if ($category && is_object($category) && property_exists($category, 'name')) {
                    // Verificar se o nome mudou
                    if ($category->name !== $organization_name) {
                        // Atualizar o nome da categoria
                        wp_update_term($existing_category_id, 'asgarosforum-category', array(
                            'name' => $organization_name,
                            'description' => 'Categoria para discussões da organização: ' . $organization_name,
                            'slug' => sanitize_title($organization_name)
                        ));
                    }
                    return; // Categoria existe e foi atualizada se necessário
                } else {
                    // Categoria não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_category_id');
                }
            }
        }

        // Verificar se já existe uma categoria com este nome (evitar duplicatas)
        $existing_term = get_term_by('name', $organization_name, 'asgarosforum-category');
        if ($existing_term) {
            update_post_meta($post_id, '_sevo_forum_category_id', $existing_term->term_id);
            return;
        }

        // Criar nova categoria
        $category_result = wp_insert_term(
            $organization_name,
            'asgarosforum-category',
            array(
                'description' => 'Categoria para discussões da organização: ' . $organization_name,
                'slug' => sanitize_title($organization_name)
            )
        );
        
        $category_id = 0;
        if (!is_wp_error($category_result)) {
            $category_id = $category_result['term_id'];
            // Adicionar metadados da categoria
            update_term_meta($category_id, 'category_access', 'everyone');
            update_term_meta($category_id, 'order', 1);
        }

        if ($category_id) {
            update_post_meta($post_id, '_sevo_forum_category_id', $category_id);
        }
    }

    /**
     * Cria ou atualiza um fórum no Asgaros para um tipo de evento.
     */
    public function create_forum_for_event_type($post_id, $post) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
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

        global $asgarosforum;
        $existing_forum_id = get_post_meta($post_id, '_sevo_forum_forum_id', true);
        $event_type_name = $post->post_title;
        
        // Se já existe um fórum, verificar se precisa atualizar o nome
        if ($existing_forum_id && class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'get_forum')) {
                $forum = $asgarosforum->content->get_forum($existing_forum_id);
                if ($forum && is_object($forum) && property_exists($forum, 'name')) {
                    // Verificar se o nome mudou
                    if ($forum->name !== $event_type_name) {
                        // Atualizar o nome do fórum usando consulta SQL direta
                        $asgarosforum->db->update(
                            $asgarosforum->tables->forums,
                            array(
                                'name'         => $event_type_name,
                                'description'  => 'Discussões sobre o tipo de evento: ' . $event_type_name,
                                'icon'         => 'fas fa-calendar',
                                'sort'         => 1,
                                'forum_status' => 'normal',
                                'parent_id'    => $category_id,
                                'parent_forum' => 0,
                            ),
                            array('id' => $existing_forum_id),
                            array('%s', '%s', '%s', '%d', '%s', '%d', '%d'),
                            array('%d')
                        );
                    }
                    return; // Fórum existe e foi atualizado se necessário
                } else {
                    // Fórum não existe mais, remover meta
                    delete_post_meta($post_id, '_sevo_forum_forum_id');
                }
            }
        }

        // Criar novo fórum usando a instância do AsgarosForum
        $forum_id = 0;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_forum')) {
                $forum_id = $asgarosforum->content->insert_forum(
                    $category_id,
                    $event_type_name,
                    'Discussões sobre o tipo de evento: ' . $event_type_name,
                    0, // parent_forum
                    'fas fa-calendar', // icon
                    1, // order
                    'normal' // status
                );
            }
        }

        if ($forum_id) {
            update_post_meta($post_id, '_sevo_forum_forum_id', $forum_id);
        }
    }

    /**
     * Gerencia a criação de tópicos automáticos para um evento.
     */
    public function handle_event_forum_creation_and_topics($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish' || !class_exists('AsgarosForum')) {
            return;
        }
        
        // Verificar se o evento tem um tópico associado, se não tiver, criar
        $topic_id = get_post_meta($post_id, '_sevo_forum_topic_id', true);
        if (!$topic_id) {
            $this->create_event_topic($post_id, $post);
        }
        
        // Verificar se datas foram alteradas e criar tópicos de notificação
        $this->create_notification_topics($post_id);
    }

    /**
     * Cria o tópico inicial para o evento no fórum.
     */
    private function create_event_topic($post_id, $post) {
        $tipo_evento_id = get_post_meta($post_id, '_sevo_evento_tipo_evento_id', true);
        if (!$tipo_evento_id) {
            return;
        }

        $forum_id = get_post_meta($tipo_evento_id, '_sevo_forum_forum_id', true);
        if (!$forum_id) {
            return;
        }

        global $asgarosforum;
        $event_name = $post->post_title;
        $event_description = get_post_meta($post_id, '_sevo_evento_descricao', true);
        $author_id = $post->post_author;
        
        // Criar novo tópico usando a instância do AsgarosForum
        $topic_ids = null;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_topic')) {
                $topic_content = $this->generate_event_topic_content($post_id, $event_description);
                
                $topic_ids = $asgarosforum->content->insert_topic(
                    $forum_id, // forum_id
                    $event_name, // topic name
                    $topic_content, // topic content
                    $author_id // author_id
                );
            }
        }

        if ($topic_ids && isset($topic_ids->topic_id)) {
            update_post_meta($post_id, '_sevo_forum_topic_id', $topic_ids->topic_id);
        }
    }

    /**
     * Gera o conteúdo do tópico do evento.
     */
    private function generate_event_topic_content($post_id, $event_description) {
        $evento_url = get_permalink($post_id);
        $data_inicio = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $data_fim = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);
        $local = get_post_meta($post_id, '_sevo_evento_local', true);
        
        $content = "Este é o tópico oficial do evento. Aqui você pode acompanhar as inscrições e discussões relacionadas ao evento.\n\n";
        
        // Incluir imagem destacada do evento se existir
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
            if ($image_url) {
                $content .= "![Imagem do Evento](" . $image_url . ")\n\n";
            }
        }
        
        if (!empty($event_description)) {
            $content .= "**Descrição:**\n" . $event_description . "\n\n";
        }
        
        if (!empty($data_inicio)) {
            $content .= "**Data de Início:** " . date('d/m/Y H:i', strtotime($data_inicio)) . "\n";
        }
        
        if (!empty($data_fim)) {
            $content .= "**Data de Fim:** " . date('d/m/Y H:i', strtotime($data_fim)) . "\n";
        }
        
        if (!empty($local)) {
            $content .= "**Local:** " . $local . "\n";
        }
        
        $content .= "\n**Para mais detalhes e inscrições, acesse:** [" . get_the_title($post_id) . "](" . home_url() . ")";
        
        return $content;
    }

    /**
     * Cria posts de notificação no tópico do evento quando datas importantes são alteradas.
     */
    private function create_notification_topics($post_id) {
        $topic_id = get_post_meta($post_id, '_sevo_forum_topic_id', true);
        $author_id = get_post_field('post_author', $post_id);
        $evento_url = get_permalink($post_id);
        $evento_title = get_the_title($post_id);

        if (!$topic_id || !$author_id || !class_exists('AsgarosForum')) {
            return;
        }

        // Mapeamento de campos de data para conteúdos dos posts de notificação
        $date_fields = array(
            '_sevo_evento_data_inicio_inscricoes' => array(
                'content' => '<strong>Período de Inscrição Definido!</strong><br><br>As inscrições para o evento "[evento_titulo]" estarão abertas de [data_inicio] até [data_fim].<br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            ),
            '_sevo_evento_data_inicio_evento' => array(
                'content' => '<strong>Data do Evento Marcada!</strong><br><br>O evento "[evento_titulo]" está agendado para acontecer de [data_inicio] a [data_fim]. Prepare-se!<br><br>Para mais detalhes, acesse a página do evento: <a href="[evento_url]">clique aqui</a>.'
            )
        );

        // Tratamento para datas de inscrição
        $data_inicio_insc = get_post_meta($post_id, '_sevo_evento_data_inicio_inscricoes', true);
        $last_posted_inicio_insc = get_post_meta($post_id, '_topic_posted_inicio_insc', true);

        if ($data_inicio_insc && $data_inicio_insc !== $last_posted_inicio_insc) {
            $data_fim_insc = get_post_meta($post_id, '_sevo_evento_data_fim_inscricoes', true);
            $config = $date_fields['_sevo_evento_data_inicio_inscricoes'];
            $post_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_insc)), date_i18n('d/m/Y', strtotime($data_fim_insc)), $evento_url],
                $config['content']
            );
            
            // Criar post no tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_post')) {
                    $asgarosforum->content->insert_post($topic_id, $post_content, $author_id);
                    update_post_meta($post_id, '_topic_posted_inicio_insc', $data_inicio_insc);
                }
            }
        }

        // Tratamento para datas do evento
        $data_inicio_evento = get_post_meta($post_id, '_sevo_evento_data_inicio_evento', true);
        $last_posted_inicio_evento = get_post_meta($post_id, '_topic_posted_inicio_evento', true);

        if ($data_inicio_evento && $data_inicio_evento !== $last_posted_inicio_evento) {
            $data_fim_evento = get_post_meta($post_id, '_sevo_evento_data_fim_evento', true);
            $config = $date_fields['_sevo_evento_data_inicio_evento'];
            $post_content = str_replace(
                ['[evento_titulo]', '[data_inicio]', '[data_fim]', '[evento_url]'],
                [$evento_title, date_i18n('d/m/Y', strtotime($data_inicio_evento)), date_i18n('d/m/Y', strtotime($data_fim_evento)), $evento_url],
                $config['content']
            );

            // Criar post no tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_post')) {
                    $asgarosforum->content->insert_post($topic_id, $post_content, $author_id);
                    update_post_meta($post_id, '_topic_posted_inicio_evento', $data_inicio_evento);
                }
            }
        }
    }
    
    /**
     * Adiciona um comentário de log de inscrição no tópico do evento
     */
    public function add_inscription_log_comment($evento_id, $comment_content) {
        if (!class_exists('AsgarosForum')) {
            return false;
        }
        
        // Buscar o ID do tópico do evento
        $topic_id = get_post_meta($evento_id, '_sevo_forum_topic_id', true);
        
        if (!$topic_id) {
            return false;
        }
        
        // Usar o autor do sistema (admin) para posts automáticos
        $author_id = 1; // ID do admin
        
        global $asgarosforum;
        if ($asgarosforum && method_exists($asgarosforum->content, 'insert_post')) {
            return $asgarosforum->content->insert_post($topic_id, $comment_content, $author_id);
        }
        
        return false;
    }
}

/**
 * Função global para adicionar comentários de log de inscrição
 */
function sevo_add_inscription_log_comment($evento_id, $comment_content) {
    $forum_integration = new Sevo_Forum_Integration();
    return $forum_integration->add_inscription_log_comment($evento_id, $comment_content);
}