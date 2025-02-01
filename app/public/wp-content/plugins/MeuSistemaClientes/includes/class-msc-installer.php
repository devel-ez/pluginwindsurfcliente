<?php
if (!defined('ABSPATH')) {
    exit;
}

class MSC_Installer {
    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de itens da proposta
        $table_proposta_itens = $wpdb->prefix . 'msc_proposta_itens';
        $sql_proposta_itens = "CREATE TABLE IF NOT EXISTS $table_proposta_itens (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposta_id bigint(20) NOT NULL,
            servico_id bigint(20) NOT NULL,
            quantidade int(11) NOT NULL DEFAULT 1,
            valor_unitario decimal(10,2) NOT NULL,
            desconto decimal(10,2) DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY proposta_id (proposta_id),
            KEY servico_id (servico_id)
        ) $charset_collate;";

        // Tabela de serviços
        $table_servicos = $wpdb->prefix . 'msc_servicos';
        $sql_servicos = "CREATE TABLE IF NOT EXISTS $table_servicos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            descricao text,
            valor decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Tabela de clientes
        $table_clientes = $wpdb->prefix . 'msc_clientes';
        $sql_clientes = "CREATE TABLE IF NOT EXISTS $table_clientes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            telefone varchar(50) NOT NULL,
            login_wp varchar(100),
            senha_wp varchar(100),
            login_hospedagem varchar(100),
            senha_hospedagem varchar(100),
            observacoes text,
            data_cadastro datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_proposta_itens);
        dbDelta($sql_servicos);
        dbDelta($sql_clientes);
    }
}
