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
                
                $wpdb->insert(
                    $wpdb->prefix . 'msc_proposta_itens',
                    array(
                        'proposta_id' => $proposta_id,
                        'servico_id' => intval($servico_id),
                        'quantidade' => intval($_POST['quantidade'][$key]),
                        'valor_unitario' => floatval(str_replace(',', '.', $_POST['valor_unitario'][$key])),
                        'desconto' => floatval(str_replace(',', '.', $_POST['desconto'][$key]))
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
        <h1><?php echo $proposta ? 'Editar Proposta' : 'Nova Proposta'; ?></h1>
        
        <?php echo $mensagem; ?>
        
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
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente->id; ?>" 
                                    <?php echo ($proposta && $proposta->cliente_id == $cliente->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($cliente->nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="msc-form-row">
                        <label for="titulo">Título da Proposta *</label>
                        <input type="text" id="titulo" name="titulo" required 
                               value="<?php echo $proposta ? esc_attr($proposta->titulo) : ''; ?>" 
                               class="regular-text">
                    </div>

                    <div class="msc-form-row">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="4" 
                                  class="large-text"><?php echo $proposta ? esc_textarea($proposta->descricao) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="msc-card">
                <div class="msc-card-header">
                    <h2>Serviços</h2>
                    <button type="button" id="adicionar-servico" class="button">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        Adicionar Serviço
                    </button>
                </div>
                
                <div class="msc-card-body">
                    <div id="servicos-lista">
                        <?php if ($itens_proposta): ?>
                            <?php foreach ($itens_proposta as $item): ?>
                                <div class="servico-item">
                                    <select name="servico_id[]" required class="servico-select">
                                        <option value="">Selecione um serviço</option>
                                        <?php foreach ($servicos as $servico): ?>
                                            <option value="<?php echo $servico->id; ?>" 
                                                data-valor="<?php echo $servico->valor; ?>"
                                                <?php echo ($item->servico_id == $servico->id) ? 'selected' : ''; ?>>
                                                <?php echo esc_html($servico->nome); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="servico-campos">
                                        <input type="number" name="quantidade[]" value="<?php echo $item->quantidade; ?>" 
                                               min="1" required placeholder="Quantidade" class="small-text quantidade">
                                        <input type="number" name="valor_unitario[]" value="<?php echo $item->valor_unitario; ?>" 
                                               step="0.01" required placeholder="Valor Unitário" class="small-text valor-unitario">
                                        <input type="number" name="desconto[]" value="<?php echo $item->desconto; ?>" 
                                               step="0.01" placeholder="Desconto" class="small-text desconto">
                                        <button type="button" class="button-link remove-servico">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="servico-item">
                                <select name="servico_id[]" required class="servico-select">
                                    <option value="">Selecione um serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?php echo $servico->id; ?>" 
                                            data-valor="<?php echo $servico->valor; ?>">
                                            <?php echo esc_html($servico->nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="servico-campos">
                                    <input type="number" name="quantidade[]" value="1" 
                                           min="1" required placeholder="Quantidade" class="small-text quantidade">
                                    <input type="number" name="valor_unitario[]" value="" 
                                           step="0.01" required placeholder="Valor Unitário" class="small-text valor-unitario">
                                    <input type="number" name="desconto[]" value="0" 
                                           step="0.01" placeholder="Desconto" class="small-text desconto">
                                    <button type="button" class="button-link remove-servico">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="msc-form-actions">
                <button type="submit" name="msc_salvar_proposta" class="button button-primary">
                    <?php echo $proposta ? 'Atualizar Proposta' : 'Criar Proposta'; ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-propostas'); ?>" class="button">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <style>
    .servico-item {
        background: #f9f9f9;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 4px;
    }

    .servico-item:hover {
        background: #f5f5f5;
    }

    .servico-select {
        width: 100%;
        margin-bottom: 10px;
    }

    .servico-campos {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .servico-campos input {
        flex: 1;
    }

    .remove-servico {
        color: #dc3232;
    }

    .remove-servico:hover {
        color: #dc3232;
    }

    .msc-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .msc-form-actions {
        margin-top: 20px;
        padding: 15px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            }
        });

        // Atualizar valor unitário ao selecionar serviço
        $(document).on('change', '.servico-select', function() {
            var valor = $(this).find(':selected').data('valor');
            $(this).closest('.servico-item').find('.valor-unitario').val(valor);
        });
    });
    </script>
    <?php
}
