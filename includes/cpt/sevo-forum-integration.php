<?php
/**
 * Lógica de integração com o plugin Asgaros Forum.
 * Versão atualizada para usar tabelas customizadas externas.
 *
 * @package Sevo_Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sevo_Forum_Integration {

    private $wpdb;
    private $org_model;
    private $tipo_evento_model;
    private $evento_model;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Carregar os models
        $this->org_model = new Sevo_Organizacao_Model();
        $this->tipo_evento_model = new Sevo_Tipo_Evento_Model();
        $this->evento_model = new Sevo_Evento_Model();
        
        // Hooks customizados para as tabelas externas
        add_action('sevo_organizacao_created', array($this, 'create_forum_category_for_organization'), 10, 1);
        add_action('sevo_organizacao_updated', array($this, 'update_forum_category_for_organization'), 10, 2);
        add_action('sevo_tipo_evento_created', array($this, 'create_forum_for_event_type'), 10, 1);
        add_action('sevo_tipo_evento_updated', array($this, 'update_forum_for_event_type'), 10, 2);
        add_action('sevo_evento_created', array($this, 'handle_event_forum_creation_and_topics'), 10, 1);
        add_action('sevo_evento_updated', array($this, 'handle_event_forum_update'), 10, 2);
    }

     /**
     * Cria uma categoria no Asgaros Forum para uma organização (tabela externa).
     */
    public function create_forum_category_for_organization($organizacao_id) {
        if (!class_exists('AsgarosForum')) {
            return;
        }

        $organizacao = $this->org_model->find($organizacao_id);
        if (!$organizacao || $organizacao->status !== 'ativo') {
            return;
        }

        global $asgarosforum;
        $existing_category_id = $this->get_organization_forum_category_id($organizacao_id);
        $organization_name = $organizacao->titulo;
        
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
                    // Categoria não existe mais, remover referência
                    $this->delete_organization_forum_category_id($organizacao_id);
                }
            }
        }

        // Verificar se já existe uma categoria com este nome (evitar duplicatas)
        $existing_term = get_term_by('name', $organization_name, 'asgarosforum-category');
        if ($existing_term) {
            $this->update_organization_forum_category_id($organizacao_id, $existing_term->term_id);
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
            $this->update_organization_forum_category_id($organizacao_id, $category_id);
        }
    }

    /**
     * Atualiza categoria do fórum quando organização é atualizada
     */
    public function update_forum_category_for_organization($organizacao_id, $old_data) {
        $this->create_forum_category_for_organization($organizacao_id);
    }

    /**
     * Cria um fórum no Asgaros para um tipo de evento (tabela externa).
     */
    public function create_forum_for_event_type($tipo_evento_id) {
        if (!class_exists('AsgarosForum')) {
            return;
        }

        $tipo_evento = $this->tipo_evento_model->find($tipo_evento_id);
        if (!$tipo_evento || $tipo_evento->status !== 'ativo') {
            return;
        }

        $org_id = $tipo_evento->organizacao_id;
        if (!$org_id) {
            return;
        }

        $category_id = $this->get_organization_forum_category_id($org_id);
        if (!$category_id) {
            return; // Categoria da organização ainda não existe
        }

        global $asgarosforum;
        $existing_forum_id = $this->get_tipo_evento_forum_id($tipo_evento_id);
        $event_type_name = $tipo_evento->titulo;
        
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
                    // Fórum não existe mais, remover referência
                    $this->delete_tipo_evento_forum_id($tipo_evento_id);
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
            $this->update_tipo_evento_forum_id($tipo_evento_id, $forum_id);
        }
    }

    /**
     * Atualiza fórum quando tipo de evento é atualizado
     */
    public function update_forum_for_event_type($tipo_evento_id, $old_data) {
        $this->create_forum_for_event_type($tipo_evento_id);
    }

    /**
     * Gerencia a criação de tópicos automáticos para um evento (tabela externa).
     */
    public function handle_event_forum_creation_and_topics($evento_id) {
        if (!class_exists('AsgarosForum')) {
            return;
        }
        
        $evento = $this->evento_model->find($evento_id);
        if (!$evento || $evento->status !== 'ativo') {
            return;
        }
        
        // Verificar se o evento tem um tópico associado, se não tiver, criar
        $topic_id = $this->get_evento_forum_topic_id($evento_id);
        if (!$topic_id) {
            $this->create_event_topic($evento_id, $evento);
        }
        
        // Verificar se datas foram alteradas e criar tópicos de notificação
        $this->create_notification_topics($evento_id);
    }

    /**
     * Gerencia atualizações do fórum quando evento é atualizado
     */
    public function handle_event_forum_update($evento_id, $old_data) {
        $this->handle_event_forum_creation_and_topics($evento_id);
    }

    /**
     * Cria o tópico inicial para o evento no fórum (tabela externa).
     */
    private function create_event_topic($evento_id, $evento) {
        $tipo_evento_id = $evento->tipo_evento_id;
        if (!$tipo_evento_id) {
            return;
        }

        $forum_id = $this->get_tipo_evento_forum_id($tipo_evento_id);
        if (!$forum_id) {
            return;
        }

        global $asgarosforum;
        $event_name = $evento->titulo;
        $event_description = $evento->descricao;
        $author_id = get_current_user_id() ?: 1; // Usar usuário atual ou admin padrão
        
        // Criar novo tópico usando a instância do AsgarosForum
        $topic_ids = null;
        if (class_exists('AsgarosForum')) {
            if ($asgarosforum && method_exists($asgarosforum->content, 'insert_topic')) {
                $topic_content = $this->generate_event_topic_content_external($evento_id, $evento);
                
                $topic_ids = $asgarosforum->content->insert_topic(
                    $forum_id, // forum_id
                    $event_name, // topic name
                    $topic_content, // topic content
                    $author_id // author_id
                );
            }
        }

        if ($topic_ids && isset($topic_ids->topic_id)) {
            $this->update_evento_forum_topic_id($evento_id, $topic_ids->topic_id);
        }
    }

    /**
     * Gera o conteúdo do tópico do evento (tabela externa).
     */
    private function generate_event_topic_content_external($evento_id, $evento) {
        $content = "Este é o tópico oficial do evento. Aqui você pode acompanhar as inscrições e discussões relacionadas ao evento.\n\n";
        
        if (!empty($evento->imagem_url)) {
            $content .= "![Imagem do Evento](" . $evento->imagem_url . ")\n\n";
        }
        
        if (!empty($evento->descricao)) {
            $content .= "**Descrição:**\n" . $evento->descricao . "\n\n";
        }
        
        if (!empty($evento->data_inicio_evento)) {
            $content .= "**Data de Início:** " . date('d/m/Y H:i', strtotime($evento->data_inicio_evento)) . "\n";
        }
        
        if (!empty($evento->data_fim_evento)) {
            $content .= "**Data de Fim:** " . date('d/m/Y H:i', strtotime($evento->data_fim_evento)) . "\n";
        }
        
        $content .= "\n**Para mais detalhes e inscrições, acesse o dashboard do sistema.**";
        
        return $content;
    }

    /**
     * Cria posts de notificação no tópico do evento quando datas importantes são alteradas (tabela externa).
     */
    private function create_notification_topics($evento_id) {
        $topic_id = $this->get_evento_forum_topic_id($evento_id);
        $evento = $this->evento_model->find($evento_id);
        
        if (!$topic_id || !$evento || !class_exists('AsgarosForum')) {
            return;
        }

        $author_id = get_current_user_id() ?: 1; // Usar usuário atual ou admin padrão
        $evento_title = $evento->titulo;

        // Tratamento para datas de inscrição
        $data_inicio_insc = $evento->data_inicio_inscricoes;
        $last_posted_inicio_insc = $this->get_evento_forum_metadata($evento_id, 'topic_posted_inicio_insc');

        if ($data_inicio_insc && $data_inicio_insc !== $last_posted_inicio_insc) {
            $data_fim_insc = $evento->data_fim_inscricoes;
            $post_content = "<strong>Período de Inscrição Definido!</strong><br><br>As inscrições para o evento \"" . $evento_title . "\" estarão abertas de " . date_i18n('d/m/Y', strtotime($data_inicio_insc)) . " até " . date_i18n('d/m/Y', strtotime($data_fim_insc)) . ".<br><br>Para mais detalhes, acesse o dashboard do sistema.";
            
            // Criar post no tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_post')) {
                    $asgarosforum->content->insert_post($topic_id, $post_content, $author_id);
                    $this->update_evento_forum_metadata($evento_id, 'topic_posted_inicio_insc', $data_inicio_insc);
                }
            }
        }

        // Tratamento para datas do evento
        $data_inicio_evento = $evento->data_inicio_evento;
        $last_posted_inicio_evento = $this->get_evento_forum_metadata($evento_id, 'topic_posted_inicio_evento');

        if ($data_inicio_evento && $data_inicio_evento !== $last_posted_inicio_evento) {
            $data_fim_evento = $evento->data_fim_evento;
            $post_content = "<strong>Data do Evento Marcada!</strong><br><br>O evento \"" . $evento_title . "\" está agendado para acontecer de " . date_i18n('d/m/Y', strtotime($data_inicio_evento)) . " a " . date_i18n('d/m/Y', strtotime($data_fim_evento)) . ". Prepare-se!<br><br>Para mais detalhes, acesse o dashboard do sistema.";
            
            // Criar post no tópico usando a instância do AsgarosForum
            if (class_exists('AsgarosForum')) {
                global $asgarosforum;
                if ($asgarosforum && method_exists($asgarosforum->content, 'insert_post')) {
                    $asgarosforum->content->insert_post($topic_id, $post_content, $author_id);
                    $this->update_evento_forum_metadata($evento_id, 'topic_posted_inicio_evento', $data_inicio_evento);
                }
            }
        }
    }

    // ========= Métodos auxiliares para gerenciar metadados do fórum nas tabelas externas =========

    /**
     * Obtém o ID da categoria do fórum de uma organização
     */
    private function get_organization_forum_category_id($organizacao_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT meta_value FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'organizacao' AND entity_id = %d AND meta_key = 'forum_category_id'",
            $organizacao_id
        ));
    }

    /**
     * Atualiza o ID da categoria do fórum de uma organização
     */
    private function update_organization_forum_category_id($organizacao_id, $category_id) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'organizacao' AND entity_id = %d AND meta_key = 'forum_category_id'",
            $organizacao_id
        ));

        if ($existing) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array('meta_value' => $category_id),
                array('id' => $existing),
                array('%s'),
                array('%d')
            );
        } else {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array(
                    'entity_type' => 'organizacao',
                    'entity_id' => $organizacao_id,
                    'meta_key' => 'forum_category_id',
                    'meta_value' => $category_id
                ),
                array('%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Remove o ID da categoria do fórum de uma organização
     */
    private function delete_organization_forum_category_id($organizacao_id) {
        $this->wpdb->delete(
            $this->wpdb->prefix . 'sevo_forum_metadata',
            array(
                'entity_type' => 'organizacao',
                'entity_id' => $organizacao_id,
                'meta_key' => 'forum_category_id'
            ),
            array('%s', '%d', '%s')
        );
    }

    /**
     * Obtém o ID do fórum de um tipo de evento
     */
    private function get_tipo_evento_forum_id($tipo_evento_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT meta_value FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'tipo_evento' AND entity_id = %d AND meta_key = 'forum_id'",
            $tipo_evento_id
        ));
    }

    /**
     * Atualiza o ID do fórum de um tipo de evento
     */
    private function update_tipo_evento_forum_id($tipo_evento_id, $forum_id) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'tipo_evento' AND entity_id = %d AND meta_key = 'forum_id'",
            $tipo_evento_id
        ));

        if ($existing) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array('meta_value' => $forum_id),
                array('id' => $existing),
                array('%s'),
                array('%d')
            );
        } else {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array(
                    'entity_type' => 'tipo_evento',
                    'entity_id' => $tipo_evento_id,
                    'meta_key' => 'forum_id',
                    'meta_value' => $forum_id
                ),
                array('%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Remove o ID do fórum de um tipo de evento
     */
    private function delete_tipo_evento_forum_id($tipo_evento_id) {
        $this->wpdb->delete(
            $this->wpdb->prefix . 'sevo_forum_metadata',
            array(
                'entity_type' => 'tipo_evento',
                'entity_id' => $tipo_evento_id,
                'meta_key' => 'forum_id'
            ),
            array('%s', '%d', '%s')
        );
    }

    /**
     * Obtém o ID do tópico do fórum de um evento
     */
    private function get_evento_forum_topic_id($evento_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT meta_value FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'evento' AND entity_id = %d AND meta_key = 'forum_topic_id'",
            $evento_id
        ));
    }

    /**
     * Atualiza o ID do tópico do fórum de um evento
     */
    private function update_evento_forum_topic_id($evento_id, $topic_id) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'evento' AND entity_id = %d AND meta_key = 'forum_topic_id'",
            $evento_id
        ));

        if ($existing) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array('meta_value' => $topic_id),
                array('id' => $existing),
                array('%s'),
                array('%d')
            );
        } else {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array(
                    'entity_type' => 'evento',
                    'entity_id' => $evento_id,
                    'meta_key' => 'forum_topic_id',
                    'meta_value' => $topic_id
                ),
                array('%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Obtém metadado do fórum de um evento
     */
    private function get_evento_forum_metadata($evento_id, $meta_key) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT meta_value FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'evento' AND entity_id = %d AND meta_key = %s",
            $evento_id,
            $meta_key
        ));
    }

    /**
     * Atualiza metadado do fórum de um evento
     */
    private function update_evento_forum_metadata($evento_id, $meta_key, $meta_value) {
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}sevo_forum_metadata WHERE entity_type = 'evento' AND entity_id = %d AND meta_key = %s",
            $evento_id,
            $meta_key
        ));

        if ($existing) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array('meta_value' => $meta_value),
                array('id' => $existing),
                array('%s'),
                array('%d')
            );
        } else {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'sevo_forum_metadata',
                array(
                    'entity_type' => 'evento',
                    'entity_id' => $evento_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value
                ),
                array('%s', '%d', '%s', '%s')
            );
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
        $topic_id = $this->get_evento_forum_topic_id($evento_id);
        
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