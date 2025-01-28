<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_render_configuracoes() {
    $mensagem = '';

    // Salvar configurações
    if (isset($_POST['msc_salvar_configuracoes'])) {
        if (check_admin_referer('msc_configuracoes')) {
            // Sanitizar e salvar cláusulas
            $clausulas = array(
                'validade' => sanitize_text_field($_POST['validade_proposta']),
                'pagamento' => wp_kses_post($_POST['forma_pagamento']),
                'prazo_execucao' => wp_kses_post($_POST['prazo_execucao']),
                'observacoes' => wp_kses_post($_POST['observacoes'])
            );
            
            update_option('msc_clausulas_padrao', $clausulas);
            $mensagem = '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
        }
    }

    // Buscar configurações atuais
    $clausulas = get_option('msc_clausulas_padrao', array(
        'validade' => '15 dias',
        'pagamento' => 'À vista ou em até 3x no cartão',
        'prazo_execucao' => 'Conforme acordo entre as partes',
        'observacoes' => "1. Os preços podem sofrer alterações sem aviso prévio\n2. Proposta sujeita à aprovação de crédito"
    ));
    ?>
    <div class="wrap">
        <h1>Configurações da Proposta</h1>
        
        <?php echo $mensagem; ?>
        
        <div class="msc-card">
            <h2>Cláusulas Padrão</h2>
            <p>Configure as cláusulas que aparecerão automaticamente em todas as propostas geradas em PDF. Você poderá personalizar estas cláusulas individualmente ao criar cada proposta.</p>
            
            <form method="post" class="msc-form">
                <?php wp_nonce_field('msc_configuracoes'); ?>
                
                <div class="msc-form-row">
                    <label for="validade_proposta">Validade da Proposta</label>
                    <input type="text" id="validade_proposta" name="validade_proposta" 
                           value="<?php echo esc_attr($clausulas['validade']); ?>" 
                           class="regular-text">
                    <p class="description">Ex: 15 dias, 30 dias, etc.</p>
                </div>

                <div class="msc-form-row">
                    <label for="forma_pagamento">Forma de Pagamento</label>
                    <textarea id="forma_pagamento" name="forma_pagamento" rows="3" 
                              class="large-text"><?php echo esc_textarea($clausulas['pagamento']); ?></textarea>
                    <p class="description">Descreva as formas de pagamento aceitas.</p>
                </div>

                <div class="msc-form-row">
                    <label for="prazo_execucao">Prazo de Execução</label>
                    <textarea id="prazo_execucao" name="prazo_execucao" rows="3" 
                              class="large-text"><?php echo esc_textarea($clausulas['prazo_execucao']); ?></textarea>
                    <p class="description">Defina o prazo padrão para execução dos serviços.</p>
                </div>

                <div class="msc-form-row">
                    <label for="observacoes">Observações Gerais</label>
                    <textarea id="observacoes" name="observacoes" rows="5" 
                              class="large-text"><?php echo esc_textarea($clausulas['observacoes']); ?></textarea>
                    <p class="description">Adicione observações gerais que aparecerão em todas as propostas.</p>
                </div>

                <div class="msc-form-row">
                    <button type="submit" name="msc_salvar_configuracoes" class="button button-primary">
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
