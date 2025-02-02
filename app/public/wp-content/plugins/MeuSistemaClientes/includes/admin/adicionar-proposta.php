<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_render_adicionar_proposta() {
    global $wpdb;
    $mensagem = '';
    
    // Buscar todos os clientes para o select
    $clientes = $wpdb->get_results(
        "SELECT id, nome FROM {$wpdb->prefix}msc_clientes ORDER BY nome ASC"
    );
    
    // Buscar todos os serviços para o select
    $servicos = $wpdb->get_results(
        "SELECT id, nome, valor, descricao FROM {$wpdb->prefix}msc_servicos ORDER BY nome ASC"
    );

    // Buscar proposta para edição se necessário
    $proposta = null;
    $itens_proposta = array();
    if (isset($_GET['id'])) {
        $proposta = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}msc_propostas WHERE id = %d",
            intval($_GET['id'])
        ));

        if ($proposta) {
            $itens_proposta = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}msc_proposta_itens WHERE proposta_id = %d",
                $proposta->id
            ));
        }
    }

    // Processar o formulário
    if (isset($_POST['msc_salvar_proposta']) && check_admin_referer('msc_salvar_proposta')) {
        $cliente_id = intval($_POST['cliente_id']);
        $titulo = sanitize_text_field($_POST['titulo']);
        $descricao = sanitize_textarea_field($_POST['descricao']);
        
        $dados_proposta = array(
            'cliente_id' => $cliente_id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'status' => 'pendente',
            'data_criacao' => current_time('mysql'),
            'data_modificacao' => current_time('mysql')
        );

        if (isset($_POST['proposta_id']) && !empty($_POST['proposta_id'])) {
            // Atualizar proposta existente
            $wpdb->update(
                $wpdb->prefix . 'msc_propostas',
                $dados_proposta,
                array('id' => intval($_POST['proposta_id'])),
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            $proposta_id = intval($_POST['proposta_id']);
            
            // Remover itens antigos
            $wpdb->delete($wpdb->prefix . 'msc_proposta_itens', array('proposta_id' => $proposta_id));
        } else {
            // Criar nova proposta
            $wpdb->insert(
                $wpdb->prefix . 'msc_propostas',
                $dados_proposta,
                array('%d', '%s', '%s', '%s', '%s', '%s')
            );
            $proposta_id = $wpdb->insert_id;
        }

        // Inserir itens da proposta
        if ($proposta_id && isset($_POST['servico_id']) && is_array($_POST['servico_id'])) {
            foreach ($_POST['servico_id'] as $key => $servico_id) {
                if (empty($servico_id)) continue;
                
                // Configuração do desconto com validação
                $wpdb->insert(
                    $wpdb->prefix . 'msc_proposta_itens',
                    array(
                        'proposta_id' => $proposta_id,
                        'servico_id' => intval($servico_id),
                        'quantidade' => intval($_POST['quantidade'][$key]),
                        'valor_unitario' => floatval(str_replace(',', '.', $_POST['valor_unitario'][$key])),
                        'desconto' => isset($_POST['desconto'][$key]) ? floatval(str_replace(',', '.', $_POST['desconto'][$key])) : 0
                    ),
                    array('%d', '%d', '%d', '%f', '%f')
                );

                            }
        }

        // Em vez de redirecionar, definir uma flag de sucesso
        $proposta_salva = true;
        $mensagem = '<div class="notice notice-success is-dismissible"><p>Proposta salva com sucesso! Redirecionando...</p></div>';
    }

    // Se a proposta foi salva, adicionar script de redirecionamento
    if (isset($proposta_salva) && $proposta_salva) {
        ?>
        <script type="text/javascript">
            setTimeout(function() {
                window.location.href = '<?php echo admin_url('admin.php?page=meu-sistema-clientes-propostas'); ?>';
            }, 1000);
        </script>
        <?php
    }
    ?>
    <div class="wrap">
        <h1 class="page-title"><?php echo $proposta ? 'Editar Proposta' : 'Nova Proposta'; ?></h1>
        <div class="notice-container"><?php echo $mensagem; ?></div>
        <form method="post" class="msc-form">
            <?php wp_nonce_field('msc_salvar_proposta'); ?>
            <?php if ($proposta): ?>
                <input type="hidden" name="proposta_id" value="<?php echo esc_attr($proposta->id); ?>">
            <?php endif; ?>
            <div class="msc-card">
                <div class="msc-card-header">
                    <h2>Informações Básicas</h2>
                </div>
                <div class="msc-card-body">
                    <div class="msc-form-row">
                        <label for="cliente_id">Cliente *</label>
                        <select id="cliente_id" name="cliente_id" required class="regular-text">
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo esc_attr($cli->id); ?>" 
                                    <?php echo ($proposta && $proposta->cliente_id == $cli->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($cli->nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="msc-form-row">
                        <label for="titulo">Título *</label>
                        <input type="text" id="titulo" name="titulo" required class="regular-text" 
                               value="<?php echo $proposta ? esc_attr($proposta->titulo) : ''; ?>" 
                               placeholder="Título da Proposta">
                    </div>
                    <div class="msc-form-row">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" class="regular-text" 
                                  placeholder="Descrição da Proposta"><?php echo $proposta ? esc_textarea($proposta->descricao) : ''; ?></textarea>
                    </div>
                    <div class="msc-form-row">
                        <h3>Serviços</h3>
                        <div id="servicos-lista">
                            <?php if ($itens_proposta): ?>
                                <?php foreach ($itens_proposta as $item): ?>
                                    <div class="servico-item">
                                        <div class="msc-form-row">
                                            <label for="servico_id">Serviço *</label>
                                            <select id="servico_id" name="servico_id[]" required class="regular-text" onchange="preencherValorUnitario(this)">
                                                <option value="">Selecione um serviço</option>
                                                <?php foreach ($servicos as $servico): ?>
                                                    <option value="<?php echo esc_attr($servico->id); ?>" data-valor="<?php echo esc_attr($servico->valor); ?>" 
                                                        <?php echo ($item->servico_id == $servico->id) ? 'selected' : ''; ?>>
                                                        <?php echo esc_html($servico->nome); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="msc-form-row">
                                            <label for="quantidade">Quantidade *</label>
                                            <input type="number" name="quantidade[]" value="<?php echo $item->quantidade; ?>" 
                                                   min="1" class="regular-text" onchange="calcularTotal()">
                                        </div>
                                        <div class="msc-form-row">
                                            <label for="valor_unitario">Valor Unitário *</label>
                                            <input type="text" name="valor_unitario[]" value="<?php echo $item->valor_unitario; ?>" 
                                                   class="regular-text" onchange="calcularTotal()">
                                        </div>
                                        <div class="msc-form-row">
                                            <label for="total_servico">Total do Serviço</label>
                                            <input type="text" name="total_servico[]" class="regular-text" readonly value="<?php echo number_format($item->quantidade * $item->valor_unitario, 2, ',', '.'); ?>">
                                        </div>
                                        <button type="button" class="button-link remove-servico">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="servico-item">
                                    <div class="msc-form-row">
                                        <label for="servico_id">Serviço *</label>
                                        <select id="servico_id" name="servico_id[]" required class="regular-text" onchange="preencherValorUnitario(this)">
                                            <option value="">Selecione um serviço</option>
                                            <?php foreach ($servicos as $servico): ?>
                                                <option value="<?php echo esc_attr($servico->id); ?>" data-valor="<?php echo esc_attr($servico->valor); ?>">
                                                    <?php echo esc_html($servico->nome); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="msc-form-row">
                                        <label for="quantidade">Quantidade *</label>
                                        <input type="number" name="quantidade[]" value="1" 
                                               min="1" class="regular-text" onchange="calcularTotal()">
                                    </div>
                                    <div class="msc-form-row">
                                        <label for="valor_unitario">Valor Unitário *</label>
                                        <input type="text" name="valor_unitario[]" class="regular-text" onchange="calcularTotal()">
                                    </div>
                                    <div class="msc-form-row">
                                        <label for="total_servico">Total do Serviço</label>
                                        <input type="text" name="total_servico[]" class="regular-text" readonly>
                                    </div>
                                    <button type="button" class="button-link remove-servico">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="adicionar-servico" class="button">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            Adicionar Serviço
                        </button>
                    </div>
                    <div class="msc-form-row">
                        <label>Total Geral</label>
                        <input type="text" id="total_geral" class="regular-text" readonly>
                    </div>
                    <div class="msc-form-actions">
                        <button type="submit" name="msc_salvar_proposta" class="button button-primary">
                            <?php echo $proposta ? 'Atualizar Proposta' : 'Criar Proposta'; ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-propostas'); ?>" class="button">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <style>
    .msc-card {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
        padding: 20px;
    }
    .msc-card-header {
        background-color: #f1f1f1;
        border-bottom: 1px solid #ddd;
        padding: 10px;
        font-weight: bold;
    }
    .msc-form-row {
        margin-bottom: 15px;
    }
    .msc-form-row label {
        display: block;
        margin-bottom: 5px;
    }
    .msc-form-actions {
        text-align: right;
    }
    .notice-container {
        margin-bottom: 20px;
    }
    .servico-item {
        background: #f9f9f9;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 4px;
    }

    .servico-item:hover {
        background: #f5f5f5;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Função para adicionar novo serviço
        $('#adicionar-servico').click(function() {
            var template = $('#servicos-lista .servico-item:first').clone();
            template.find('input').val('');
            template.find('select').val('');
            $('#servicos-lista').append(template);
        });

        // Remover serviço
        $(document).on('click', '.remove-servico', function() {
            if ($('#servicos-lista .servico-item').length > 1) {
                $(this).closest('.servico-item').remove();
                calcularTotal();
            }
        });
    });

    function preencherValorUnitario(select) {
        const valor = select.options[select.selectedIndex].dataset.valor;
        const servicoItem = select.closest('.servico-item');
        servicoItem.querySelector('input[name="valor_unitario[]"]').value = valor;
        calcularTotal(); // Recalcular total após preencher o valor
    }

    function calcularTotal() {
        let totalGeral = 0;
        const servicos = document.querySelectorAll('.servico-item');
        servicos.forEach(servico => {
            const quantidade = servico.querySelector('input[name="quantidade[]"]').value;
            const valorUnitario = servico.querySelector('input[name="valor_unitario[]"]').value.replace(',', '.');
            const totalServico = quantidade * valorUnitario;
            servico.querySelector('input[name="total_servico[]"]').value = totalServico.toFixed(2);
            totalGeral += totalServico;
        });
        document.getElementById('total_geral').value = totalGeral.toFixed(2);
    }
    </script>
    <?php
}
