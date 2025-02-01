<?php
if (!defined('ABSPATH')) {
    exit;
}

// Classe para a tabela de clientes
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class MSC_Clientes_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'cliente',
            'plural'   => 'clientes',
            'ajax'     => false
        ]);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'msc_clientes';

        // Definir colunas
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Paginação
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Ordenação
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) 
            ? $_REQUEST['orderby'] 
            : 'data_cadastro';
        
        $order = (isset($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), ['ASC', 'DESC'])) 
            ? strtoupper($_REQUEST['order']) 
            : 'DESC';

        // Query principal
        $query = $wpdb->prepare(
            "SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        // Total de itens
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$table_name}");

        // Configurar itens
        $this->items = $wpdb->get_results($query, ARRAY_A);

        // Configurar paginação
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function get_columns() {
        return [
            'cb'                => '<input type="checkbox" />',
            'nome'              => 'Nome',
            'telefone'          => 'Telefone',
            'login_wp'          => 'Login WP',
            'senha_wp'          => 'Senha WP',
            'login_hospedagem'  => 'Login Hospedagem',
            'senha_hospedagem'  => 'Senha Hospedagem',
            'observacoes'       => 'Observações',
            'data_cadastro'     => 'Data de Cadastro'
        ];
    }

    public function get_sortable_columns() {
        return [
            'nome'              => ['nome', true],
            'telefone'          => ['telefone', false],
            'login_wp'          => ['login_wp', false],
            'login_hospedagem'  => ['login_hospedagem', false],
            'data_cadastro'     => ['data_cadastro', false]
        ];
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'data_cadastro':
                return wp_date('d/m/Y H:i', strtotime($item[$column_name]));
            default:
                return $item[$column_name];
        }
    }

    public function column_nome($item) {
        $actions = [
            'edit'   => sprintf(
                '<a href="?page=meu-sistema-clientes-editar&id=%s">Editar</a>',
                $item['id']
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s" onclick="return confirm(\'Tem certeza que deseja excluir este cliente?\')">Excluir</a>',
                $_REQUEST['page'],
                $item['id']
            )
        ];

        return sprintf(
            '<strong>%1$s</strong>%2$s',
            $item['nome'],
            $this->row_actions($actions)
        );
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => 'Excluir'
        ];
    }

    public function no_items() {
        echo 'Nenhum cliente encontrado.';
    }
}

function msc_render_listar_clientes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msc_clientes';
    
    // Processar ações em massa
    if (isset($_POST['action']) && $_POST['action'] == 'bulk-delete' 
        && isset($_POST['bulk-delete']) && is_array($_POST['bulk-delete'])) {
        
        foreach ($_POST['bulk-delete'] as $id) {
            $wpdb->delete(
                $table_name,
                ['id' => intval($id)],
                ['%d']
            );
        }
        echo '<div class="notice notice-success is-dismissible"><p>Clientes excluídos com sucesso!</p></div>';
    }
    
    // Processar exclusão individual
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $wpdb->delete(
            $table_name,
            ['id' => intval($_GET['id'])],
            ['%d']
        );
        echo '<div class="notice notice-success is-dismissible"><p>Cliente excluído com sucesso!</p></div>';
    }

    // Criar instância da tabela
    $table = new MSC_Clientes_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Clientes Cadastrados</h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=meu-sistema-clientes-adicionar')); ?>" class="page-title-action">Adicionar Novo</a>
        <hr class="wp-header-end">
        
        <form method="post">
            <?php
            $table->search_box('Pesquisar', 'search_id');
            $table->display();
            ?>
        </form>
    </div>
    <?php
}
