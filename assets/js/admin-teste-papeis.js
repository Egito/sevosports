/**
 * JavaScript para a funcionalidade de teste de papéis
 * 
 * @package Sevo_Eventos
 * @version 1.0
 */

(function($) {
    'use strict';
    
    var SevoTestePapeis = {
        
        init: function() {
            this.bindEvents();
            this.loadSelectOptions();
        },
        
        bindEvents: function() {
            $('#sevo-test-permissions-form').on('submit', this.handleFormSubmit.bind(this));
        },
        
        loadSelectOptions: function() {
            this.loadUsers();
            this.loadOrganizations();
        },
        
        loadUsers: function() {
            $.ajax({
                url: sevoTestePapeis.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_users_select',
                    nonce: sevoTestePapeis.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $select = $('#test_user_id');
                        $select.empty().append('<option value="">Selecione um usuário</option>');
                        
                        $.each(response.data, function(index, user) {
                            $select.append('<option value="' + user.value + '">' + user.label + '</option>');
                        });
                    }
                },
                error: function() {
                    alert('Erro ao carregar usuários');
                }
            });
        },
        
        loadOrganizations: function() {
            $.ajax({
                url: sevoTestePapeis.ajax_url,
                type: 'POST',
                data: {
                    action: 'sevo_get_organizations_select',
                    nonce: sevoTestePapeis.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $select = $('#test_organization_id');
                        $select.empty().append('<option value="">Selecione uma organização</option>');
                        
                        $.each(response.data, function(index, org) {
                            $select.append('<option value="' + org.value + '">' + org.label + '</option>');
                        });
                    }
                },
                error: function() {
                    alert('Erro ao carregar organizações');
                }
            });
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'sevo_test_user_permissions',
                nonce: sevoTestePapeis.nonce,
                user_id: $('#test_user_id').val(),
                organization_id: $('#test_organization_id').val(),
                role: $('#test_role').val()
            };
            
            if (!formData.user_id || !formData.organization_id || !formData.role) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            this.showLoading();
            
            $.ajax({
                url: sevoTestePapeis.ajax_url,
                type: 'POST',
                data: formData,
                success: this.handleTestResults.bind(this),
                error: function() {
                    alert('Erro ao executar teste de permissões');
                    this.hideLoading();
                }.bind(this)
            });
        },
        
        handleTestResults: function(response) {
            this.hideLoading();
            
            if (response.success) {
                this.displayResults(response.data);
            } else {
                alert('Erro: ' + response.data);
            }
        },
        
        displayResults: function(data) {
            var html = this.buildResultsHTML(data);
            $('#sevo-test-results-content').html(html);
            $('#sevo-test-results').show();
            
            // Scroll para os resultados
            $('html, body').animate({
                scrollTop: $('#sevo-test-results').offset().top - 50
            }, 500);
        },
        
        buildResultsHTML: function(data) {
            var html = '';
            
            // Informações do teste
            html += '<div class="sevo-test-info">';
            html += '<h3>Informações do Teste</h3>';
            html += '<div class="sevo-test-info-grid">';
            html += '<div><strong>Usuário:</strong> ' + data.user_info.name + ' (' + data.user_info.email + ')</div>';
            html += '<div><strong>Organização:</strong> ' + data.organization_info.titulo + '</div>';
            html += '<div><strong>Papel Testado:</strong> ' + data.role.charAt(0).toUpperCase() + data.role.slice(1) + '</div>';
            html += '</div>';
            html += '</div>';
            
            // Organizações
            html += this.buildTableSection('Organizações', data.organizations, [
                { key: 'titulo', label: 'Título' },
                { key: 'status', label: 'Status' },
                { key: 'can_view', label: 'Visualizar', type: 'access' },
                { key: 'can_edit', label: 'Editar', type: 'access' },
                { key: 'can_delete', label: 'Excluir', type: 'access' }
            ]);
            
            // Tipos de Evento
            html += this.buildTableSection('Tipos de Evento', data.tipos_evento, [
                { key: 'titulo', label: 'Título' },
                { key: 'organizacao', label: 'Organização' },
                { key: 'status', label: 'Status' },
                { key: 'can_view', label: 'Visualizar', type: 'access' },
                { key: 'can_edit', label: 'Editar', type: 'access' },
                { key: 'can_delete', label: 'Excluir', type: 'access' }
            ]);
            
            // Eventos
            html += this.buildTableSection('Eventos', data.eventos, [
                { key: 'titulo', label: 'Título' },
                { key: 'tipo_evento', label: 'Tipo' },
                { key: 'organizacao', label: 'Organização' },
                { key: 'status', label: 'Status' },
                { key: 'can_view', label: 'Visualizar', type: 'access' },
                { key: 'can_edit', label: 'Editar', type: 'access' },
                { key: 'can_delete', label: 'Excluir', type: 'access' }
            ]);
            
            // Permissões Específicas
            html += this.buildPermissionsSection('Permissões Específicas', data.permissions);
            
            return html;
        },
        
        buildTableSection: function(title, data, columns) {
            var html = '<div class="sevo-test-section">';
            html += '<h3>' + title + ' (' + data.length + ' registros)</h3>';
            
            if (data.length === 0) {
                html += '<p>Nenhum registro encontrado.</p>';
            } else {
                html += '<div class="sevo-test-table-container">';
                html += '<table class="sevo-test-table">';
                
                // Cabeçalho
                html += '<thead><tr>';
                columns.forEach(function(col) {
                    html += '<th>' + col.label + '</th>';
                });
                html += '</tr></thead>';
                
                // Corpo
                html += '<tbody>';
                data.forEach(function(item) {
                    html += '<tr>';
                    columns.forEach(function(col) {
                        var value = item[col.key];
                        if (col.type === 'access') {
                            var accessClass = value ? 'access-yes' : 'access-no';
                            var accessText = value ? 'Sim' : 'Não';
                            html += '<td class="' + accessClass + '">' + accessText + '</td>';
                        } else {
                            html += '<td>' + (value || '-') + '</td>';
                        }
                    });
                    html += '</tr>';
                });
                html += '</tbody>';
                
                html += '</table>';
                html += '</div>';
            }
            
            html += '</div>';
            return html;
        },
        
        buildPermissionsSection: function(title, permissions) {
            var html = '<div class="sevo-test-section">';
            html += '<h3>' + title + '</h3>';
            
            html += '<div class="sevo-test-table-container">';
            html += '<table class="sevo-test-table">';
            html += '<thead><tr><th>Permissão</th><th>Acesso</th></tr></thead>';
            html += '<tbody>';
            
            permissions.forEach(function(perm) {
                var accessClass = perm.has_access ? 'access-yes' : 'access-no';
                var accessText = perm.has_access ? 'Sim' : 'Não';
                html += '<tr>';
                html += '<td>' + perm.permission + '</td>';
                html += '<td class="' + accessClass + '">' + accessText + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div></div>';
            
            return html;
        },
        
        showLoading: function() {
            var $button = $('#sevo-test-permissions-form button[type="submit"]');
            $button.prop('disabled', true).html('<i class="dashicons dashicons-update-alt"></i> Testando...');
        },
        
        hideLoading: function() {
            var $button = $('#sevo-test-permissions-form button[type="submit"]');
            $button.prop('disabled', false).html('<i class="dashicons dashicons-search"></i> Testar Permissões');
        }
    };
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        SevoTestePapeis.init();
    });
    
})(jQuery);