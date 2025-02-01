<?php
if (!defined('ABSPATH')) {
    exit;
}

function msc_get_estatisticas_clientes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_clientes';
    
    $total = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    
    $mes_atual = wp_date('Y-m-01 00:00:00');
    $novos_mes = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name WHERE data_cadastro >= %s",
            $mes_atual
        )
    );
    
    return [
        'total' => $total,
        'ativos' => $total, // Por enquanto todos são considerados ativos
        'novos_mes' => $novos_mes
    ];
}

function msc_render_pagina_principal() {
    $estatisticas = msc_get_estatisticas_clientes();
    ?>
    <div class="wrap">
        <h1>Sistema de Clientes</h1>
        <p>Gerencie seus clientes de forma simples e eficiente</p>

        <!-- Estatísticas -->
        <div class="msc-stats-container">
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['total']; ?></span>
                <span class="msc-stat-label">Total de Clientes</span>
            </div>
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['ativos']; ?></span>
                <span class="msc-stat-label">Clientes Ativos</span>
            </div>
            <div class="msc-stat-box">
                <span class="msc-stat-number"><?php echo $estatisticas['novos_mes']; ?></span>
                <span class="msc-stat-label">Novos este mês</span>
            </div>
        </div>

        <!-- Cards de Ações -->
        <div class="msc-cards-container">
            <!-- Novo Cliente -->
            <div class="msc-card">
                <div class="msc-card-header">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <h2>Novo Cliente</h2>
                </div>
                <p>Adicione um novo cliente ao sistema com todos os detalhes necessários.</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-adicionar'); ?>" class="msc-button">
                    Adicionar Cliente
                </a>
            </div>

            <!-- Lista de Clientes -->
            <div class="msc-card">
                <div class="msc-card-header">
                    <span class="dashicons dashicons-list-view"></span>
                    <h2>Lista de Clientes</h2>
                </div>
                <p>Visualize, edite e gerencie todos os seus clientes cadastrados.</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-listar'); ?>" class="msc-button">
                    Ver Todos
                </a>
            </div>

            <!-- Serviços -->
            <div class="msc-card">
                <div class="msc-card-header">
                    <span class="dashicons dashicons-hammer"></span>
                    <h2>Serviços</h2>
                </div>
                <p>Gerencie os serviços oferecidos e seus respectivos valores.</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-servicos'); ?>" class="msc-button">
                    Gerenciar Serviços
                </a>
            </div>

            <!-- Propostas -->
            <div class="msc-card">
                <h2><span class="dashicons dashicons-media-text"></span> Propostas</h2>
                <p>Gerencie suas propostas comerciais. Crie, edite e gere PDFs das propostas.</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-propostas'); ?>" class="button button-primary">
                    Gerenciar Propostas
                </a>
            </div>

            <!-- Relatórios -->
            <div class="msc-card">
                <div class="msc-card-header">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <h2>Relatórios</h2>
                </div>
                <p>Acesse relatórios e estatísticas sobre seus clientes.</p>
                <a href="#" class="msc-button">Ver Relatórios</a>
            </div>

            <!-- Configurações -->
            <div class="msc-card">
                <h2><span class="dashicons dashicons-admin-settings"></span> Configurações da Proposta</h2>
                <p>Configure as cláusulas padrão e outras configurações das propostas geradas em PDF.</p>
                <a href="<?php echo admin_url('admin.php?page=meu-sistema-clientes-configuracoes'); ?>" class="button button-primary">
                    Configurar Proposta
                </a>
            </div>
        </div>
    </div>

    <style>
    .msc-stats-container {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .msc-stat-box {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        flex: 1;
        text-align: center;
    }

    .msc-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #2271b1;
        margin-bottom: 5px;
    }

    .msc-stat-label {
        color: #50575e;
    }

    .msc-cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .msc-card {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .msc-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .msc-card-header .dashicons {
        font-size: 24px;
        width: 24px;
        height: 24px;
        margin-right: 10px;
        color: #2271b1;
    }

    .msc-card-header h2 {
        margin: 0;
        font-size: 1.3em;
        color: #1d2327;
    }

    .msc-card p {
        margin: 0 0 20px 0;
        color: #50575e;
    }

    .msc-button {
        display: inline-block;
        padding: 8px 12px;
        background: #2271b1;
        color: #fff;
        text-decoration: none;
        border-radius: 3px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .msc-button:hover {
        background: #135e96;
        color: #fff;
    }

    .msc-button.secondary {
        background: #f0f0f1;
        color: #2271b1;
    }

    .msc-button.secondary:hover {
        background: #dcdcde;
        color: #135e96;
    }
    </style>
    <?php
}
