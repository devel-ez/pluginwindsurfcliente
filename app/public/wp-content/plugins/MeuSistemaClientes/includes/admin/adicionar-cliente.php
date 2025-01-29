<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_render_adicionar_cliente() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_clientes';
    $mensagem = '';
    $cliente_adicionado = false;

    // Processar o formulário se foi enviado
    if (isset($_POST['msc_adicionar_cliente'])) {
        // Validação e sanitização
        $nome = sanitize_text_field($_POST['nome']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $login_wp = sanitize_text_field($_POST['login_wp']);
        $senha_wp = sanitize_text_field($_POST['senha_wp']);
        $login_hospedagem = sanitize_text_field($_POST['login_hospedagem']);
        $senha_hospedagem = sanitize_text_field($_POST['senha_hospedagem']);
        $observacoes = sanitize_textarea_field($_POST['observacoes']);
        
        // Debug - mostrar dados recebidos
        error_log('Dados recebidos do formulário:');
        error_log("Nome: $nome");
        error_log("Telefone: $telefone");
        
        // Validações básicas
        $erros = [];
        if (empty($nome)) $erros[] = "Nome é obrigatório.";
        if (empty($telefone)) $erros[] = "Telefone é obrigatório.";
        
        if (empty($erros)) {
            // Preparar dados para inserção
            $dados = array(
                'nome' => $nome,
                'telefone' => $telefone,
                'login_wp' => $login_wp,
                'senha_wp' => $senha_wp,
                'login_hospedagem' => $login_hospedagem,
                'senha_hospedagem' => $senha_hospedagem,
                'observacoes' => $observacoes,
                'data_cadastro' => current_time('mysql')
            );
            
            // Debug - mostrar SQL que será executada
            error_log('Dados para inserção:');
            error_log(print_r($dados, true));
            
            // Inserir no banco de dados
            $resultado = $wpdb->insert(
                $table_name,
                $dados,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($resultado === false) {
                error_log('Erro ao inserir cliente: ' . $wpdb->last_error);
                $mensagem = '<div class="notice notice-error is-dismissible">';
                $mensagem .= '<p>Erro ao adicionar cliente. Detalhes do erro:</p>';
                $mensagem .= '<p>' . esc_html($wpdb->last_error) . '</p>';
                $mensagem .= '</div>';
            } else {
                $novo_id = $wpdb->insert_id;
                error_log('Cliente inserido com sucesso. ID: ' . $novo_id);
                
                // Verificar se o registro foi realmente inserido
                $cliente = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $novo_id));
                if ($cliente) {
                    error_log('Dados do cliente inserido:');
                    error_log(print_r($cliente, true));
                    $mensagem = '<div class="notice notice-success is-dismissible">';
                    $mensagem .= '<p>Cliente adicionado com sucesso!</p>';
                    $mensagem .= '</div>';
                    $cliente_adicionado = true;
                    $_POST = []; // Limpar o formulário
                } else {
                    error_log('Cliente não encontrado após inserção');
                    $mensagem = '<div class="notice notice-error is-dismissible"><p>Erro ao verificar o cadastro do cliente.</p></div>';
                }
            }
        } else {
            $mensagem = '<div class="notice notice-error is-dismissible"><p>' . implode('<br>', $erros) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <div class="msc-dashboard">
            <div class="msc-header">
                <h1>Adicionar Novo Cliente</h1>
                <p>Preencha os dados do novo cliente</p>
            </div>
            
            <?php echo $mensagem; ?>

            <?php if ($cliente_adicionado): ?>
                <div class="msc-card">
                    <div class="msc-success-actions">
                        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            Voltar para Tela Inicial
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-adicionar'); ?>" class="button">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Adicionar Outro Cliente
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-listar'); ?>" class="button">
                            <span class="dashicons dashicons-list-view"></span>
                            Ver Todos os Clientes
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="msc-card">
                    <form method="post" class="msc-form">
                        <div class="msc-form-row">
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" required 
                                   value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="telefone">Telefone *</label>
                            <input type="tel" id="telefone" name="telefone" required 
                                   value="<?php echo isset($_POST['telefone']) ? esc_attr($_POST['telefone']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="login_wp">Login WordPress</label>
                            <input type="text" id="login_wp" name="login_wp" 
                                   value="<?php echo isset($_POST['login_wp']) ? esc_attr($_POST['login_wp']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="senha_wp">Senha WordPress</label>
                            <input type="text" id="senha_wp" name="senha_wp" 
                                   value="<?php echo isset($_POST['senha_wp']) ? esc_attr($_POST['senha_wp']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="login_hospedagem">Login Hospedagem</label>
                            <input type="text" id="login_hospedagem" name="login_hospedagem" 
                                   value="<?php echo isset($_POST['login_hospedagem']) ? esc_attr($_POST['login_hospedagem']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="senha_hospedagem">Senha Hospedagem</label>
                            <input type="text" id="senha_hospedagem" name="senha_hospedagem" 
                                   value="<?php echo isset($_POST['senha_hospedagem']) ? esc_attr($_POST['senha_hospedagem']) : ''; ?>" 
                                   class="regular-text">
                        </div>

                        <div class="msc-form-row">
                            <label for="observacoes">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="4" 
                                      class="large-text"><?php echo isset($_POST['observacoes']) ? esc_textarea($_POST['observacoes']) : ''; ?></textarea>
                        </div>

                        <div class="msc-form-row">
                            <button type="submit" name="msc_adicionar_cliente" class="button button-primary">Salvar Cliente</button>
                            <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes'); ?>" class="button">Cancelar</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .msc-success-actions {
        display: flex;
        gap: 10px;
        padding: 20px 0;
    }

    .msc-success-actions .button {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        height: auto;
    }

    .msc-success-actions .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    </style>
    <?php
}
