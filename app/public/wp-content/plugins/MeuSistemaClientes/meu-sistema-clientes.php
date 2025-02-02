<?php
/*
Plugin Name: Meu Sistema Clientes
Plugin URI: 
Description: Sistema de gerenciamento de clientes
Version: 1.0.0
Author: Felipe
Author URI: 
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: meu-sistema-clientes
*/

if (!defined('ABSPATH')) {
    exit;
}

// Plugin activation hook
register_activation_hook(__FILE__, 'msc_activate_plugin');

function msc_update_database()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'msc_clientes';

    // Verificar se os campos existem
    $row = $wpdb->get_row("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table_name}' AND COLUMN_NAME = 'login_wp'");

    if (!$row) {
        // Adicionar novos campos
        $wpdb->query("ALTER TABLE {$table_name} 
            ADD COLUMN login_wp varchar(100) DEFAULT NULL,
            ADD COLUMN senha_wp varchar(100) DEFAULT NULL,
            ADD COLUMN login_hospedagem varchar(100) DEFAULT NULL,
            ADD COLUMN senha_hospedagem varchar(100) DEFAULT NULL,
            ADD COLUMN observacoes text DEFAULT NULL");

        if ($wpdb->last_error) {
            error_log('Erro ao adicionar campos na tabela de clientes: ' . $wpdb->last_error);
        }
    }
}

function msc_activate_plugin()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabela de clientes
    $sql_clientes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_clientes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome varchar(255) NOT NULL,
        telefone varchar(50) NOT NULL,
        data_cadastro datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabela de serviços
    $sql_servicos = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_servicos (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome varchar(100) NOT NULL,
        descricao text,
        valor decimal(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabela de propostas
    $sql_propostas = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_propostas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cliente_id mediumint(9) NOT NULL,
        titulo varchar(200) NOT NULL,
        descricao text,
        valor_total decimal(10,2) DEFAULT 0.00,
        status varchar(20) DEFAULT 'pendente',
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        data_modificacao datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY cliente_id (cliente_id)
    ) $charset_collate;";

    // Tabela de itens da proposta
    $sql_proposta_itens = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_proposta_itens (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        proposta_id mediumint(9) NOT NULL,
        servico_id mediumint(9) NOT NULL,
        quantidade int(11) NOT NULL DEFAULT 1,
        valor_unitario decimal(10,2) NOT NULL,
        desconto decimal(10,2) DEFAULT 0.00,
        PRIMARY KEY  (id),
        KEY proposta_id (proposta_id),
        KEY servico_id (servico_id)
    ) $charset_collate;";

    // Tabela de Kanban
    $sql_kanban = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_kanban (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        cliente_id bigint(20) NOT NULL,
        titulo varchar(255) NOT NULL,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY cliente_id (cliente_id),
        FOREIGN KEY (cliente_id) REFERENCES {$wpdb->prefix}msc_clientes(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Tabela de colunas do Kanban
    $sql_colunas = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_kanban_colunas (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        kanban_id bigint(20) NOT NULL,
        titulo varchar(255) NOT NULL,
        ordem int NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY kanban_id (kanban_id),
        FOREIGN KEY (kanban_id) REFERENCES {$wpdb->prefix}msc_kanban(id) ON DELETE CASCADE
    ) $charset_collate;";

    // Tabela de cartões do Kanban
    $sql_cartoes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}msc_kanban_cartoes (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        coluna_id bigint(20) NOT NULL,
        titulo varchar(255) NOT NULL,
        descricao text,
        responsavel varchar(255),
        prazo date,
        ordem int NOT NULL DEFAULT 0,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY coluna_id (coluna_id),
        FOREIGN KEY (coluna_id) REFERENCES {$wpdb->prefix}msc_kanban_colunas(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_clientes);
    dbDelta($sql_servicos);
    dbDelta($sql_propostas);
    dbDelta($sql_proposta_itens);
    dbDelta($sql_kanban);
    dbDelta($sql_colunas);
    dbDelta($sql_cartoes);

    // Atualizar a estrutura da tabela para adicionar os novos campos
    msc_update_database();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'msc_deactivate_plugin');

function msc_deactivate_plugin()
{
    // Limpar dados se necessário
}

// Incluir arquivos administrativos
$admin_files = [
    'pagina-principal.php',
    'adicionar-cliente.php',
    'listar-clientes.php',
    'servicos.php',
    'propostas.php',
    'adicionar-proposta.php',
    'gerar-pdf.php',
    'configuracoes.php',
    'kanban.php',
    'kanban-view.php'
];

foreach ($admin_files as $file) {
    $file_path = plugin_dir_path(__FILE__) . 'includes/admin/' . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
        error_log('Arquivo carregado com sucesso: ' . $file);
    } else {
        error_log('ERRO: Arquivo não encontrado: ' . $file);
    }
}

// Adicionar menu ao WordPress admin
add_action('admin_menu', 'msc_admin_menu');

function msc_admin_menu()
{
    // Verificar se o usuário tem permissão
    if (!current_user_can('manage_options')) {
        return;
    }

    add_menu_page(
        'Meu Sistema Clientes',
        'Meu Sistema Clientes',
        'manage_options',
        'meu-sistema-clientes',
        'msc_render_pagina_principal',
        'dashicons-groups',
        30
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Página Principal',
        'Página Principal',
        'manage_options',
        'meu-sistema-clientes',
        'msc_render_pagina_principal'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Adicionar Cliente',
        'Adicionar Cliente',
        'manage_options',
        'msc-adicionar-cliente',
        'msc_render_adicionar_cliente'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Listar Clientes',
        'Listar Clientes',
        'manage_options',
        'msc-listar-clientes',
        'msc_render_listar_clientes'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Serviços',
        'Serviços',
        'manage_options',
        'msc-servicos',
        'msc_render_servicos'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Propostas',
        'Propostas',
        'manage_options',
        'msc-propostas',
        'msc_render_propostas'
    );

    // Página oculta para adicionar/editar proposta
    add_submenu_page(
        null, // null significa que não aparecerá no menu
        'Adicionar/Editar Proposta',
        'Adicionar/Editar Proposta',
        'manage_options',
        'msc-adicionar-proposta',
        'msc_render_adicionar_proposta'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Kanban',
        'Kanban',
        'manage_options',
        'msc-kanban',
        'msc_exibir_pagina_kanban'
    );

    // Página oculta para visualização do Kanban
    add_submenu_page(
        null,
        'Visualizar Kanban',
        'Visualizar Kanban',
        'manage_options',
        'msc-kanban-view',
        'msc_exibir_kanban_view'
    );

    add_submenu_page(
        'meu-sistema-clientes',
        'Configurações',
        'Configurações',
        'manage_options',
        'msc-configuracoes',
        'msc_render_configuracoes'
    );
}

// Incluir estilos administrativos
add_action('admin_enqueue_scripts', 'msc_enqueue_admin_styles');

function msc_enqueue_admin_styles($hook)
{
    if (strpos($hook, 'meu-sistema-clientes') !== false) {
        wp_enqueue_style(
            'msc-admin-styles',
            plugins_url('assets/css/admin-style.css', __FILE__),
            array(),
            '1.0.0'
        );
    }

    // jQuery UI para drag and drop
    wp_enqueue_script('jquery-ui-sortable');

    // Estilos do Kanban
    wp_enqueue_style(
        'msc-kanban-styles',
        plugins_url('assets/css/kanban.css', __FILE__),
        array(),
        '1.0.0'
    );
}

// Adicionar action AJAX
add_action('wp_ajax_msc_gerar_pdf', 'msc_ajax_gerar_pdf');
function msc_ajax_gerar_pdf()
{
    if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'gerar_pdf_proposta')) {
        wp_die('Acesso inválido');
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-msc-pdf-generator.php';
    $generator = new MSC_PDF_Generator();
    $generator->gerar_pdf_proposta(intval($_GET['id']));
    exit;
}

// Initialize plugin
add_action('plugins_loaded', 'msc_init_plugin');

function msc_init_plugin()
{
    // Plugin initialization code here
}

// Adicionar actions AJAX
add_action('wp_ajax_msc_excluir_kanban', 'msc_ajax_excluir_kanban');
add_action('wp_ajax_msc_get_cartao', 'msc_ajax_get_cartao');
add_action('wp_ajax_msc_salvar_cartao', 'msc_ajax_salvar_cartao');
add_action('wp_ajax_msc_excluir_cartao', 'msc_ajax_excluir_cartao');
add_action('wp_ajax_msc_atualizar_cartao_posicao', 'msc_ajax_atualizar_cartao_posicao');

function msc_ajax_excluir_kanban()
{
    check_ajax_referer('msc_excluir_kanban', 'nonce');
    global $wpdb;

    $kanban_id = intval($_POST['kanban_id']);

    // Excluir cartões
    $wpdb->query($wpdb->prepare("
        DELETE c FROM {$wpdb->prefix}msc_kanban_cartoes c
        JOIN {$wpdb->prefix}msc_kanban_colunas col ON c.coluna_id = col.id
        WHERE col.kanban_id = %d
    ", $kanban_id));

    // Excluir colunas
    $wpdb->delete($wpdb->prefix . 'msc_kanban_colunas', array('kanban_id' => $kanban_id));

    // Excluir kanban
    $wpdb->delete($wpdb->prefix . 'msc_kanban', array('id' => $kanban_id));

    wp_send_json_success();
}

function msc_ajax_get_cartao()
{
    check_ajax_referer('msc_get_cartao', 'nonce');
    global $wpdb;

    $cartao_id = intval($_POST['cartao_id']);
    $cartao = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}msc_kanban_cartoes WHERE id = %d
    ", $cartao_id));

    wp_send_json_success($cartao);
}

function msc_ajax_salvar_cartao()
{
    check_ajax_referer('msc_salvar_cartao', 'nonce');
    global $wpdb;

    $cartao_id = isset($_POST['cartao_id']) ? intval($_POST['cartao_id']) : 0;
    $dados = array(
        'coluna_id' => intval($_POST['coluna_id']),
        'titulo' => sanitize_text_field($_POST['titulo']),
        'descricao' => sanitize_textarea_field($_POST['descricao']),
        'responsavel' => sanitize_text_field($_POST['responsavel']),
        'prazo' => sanitize_text_field($_POST['prazo'])
    );

    if ($cartao_id) {
        $wpdb->update(
            $wpdb->prefix . 'msc_kanban_cartoes',
            $dados,
            array('id' => $cartao_id)
        );
    } else {
        $wpdb->insert($wpdb->prefix . 'msc_kanban_cartoes', $dados);
    }

    wp_send_json_success();
}

function msc_ajax_excluir_cartao()
{
    check_ajax_referer('msc_excluir_cartao', 'nonce');
    global $wpdb;

    $cartao_id = intval($_POST['cartao_id']);
    $wpdb->delete($wpdb->prefix . 'msc_kanban_cartoes', array('id' => $cartao_id));

    wp_send_json_success();
}

function msc_ajax_atualizar_cartao_posicao()
{
    check_ajax_referer('msc_atualizar_cartao_posicao', 'nonce');
    global $wpdb;

    $cartao_id = intval($_POST['cartao_id']);
    $coluna_id = intval($_POST['coluna_id']);
    $nova_ordem = array_map('intval', $_POST['nova_ordem']);

    // Atualizar coluna do cartão
    $wpdb->update(
        $wpdb->prefix . 'msc_kanban_cartoes',
        array('coluna_id' => $coluna_id),
        array('id' => $cartao_id)
    );

    // Atualizar ordem dos cartões
    foreach ($nova_ordem as $ordem => $id) {
        $wpdb->update(
            $wpdb->prefix . 'msc_kanban_cartoes',
            array('ordem' => $ordem),
            array('id' => $id)
        );
    }

    wp_send_json_success();
}
