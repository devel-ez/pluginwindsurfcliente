<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir Dompdf
require_once(plugin_dir_path(__FILE__) . '../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

class MSC_PDF_Generator {
    public function gerar_pdf_proposta($proposta_id) {
        global $wpdb;

        // Debug
        error_log('Gerando PDF para proposta ID: ' . $proposta_id);

        // Buscar dados da proposta
        $proposta = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone as cliente_telefone, c.endereco as cliente_endereco
             FROM {$wpdb->prefix}msc_propostas p
             LEFT JOIN {$wpdb->prefix}msc_clientes c ON p.cliente_id = c.id
             WHERE p.id = %d",
            $proposta_id
        ));

        if (!$proposta) {
            wp_die('Proposta não encontrada');
        }

        // Buscar itens da proposta
        $itens = $wpdb->get_results($wpdb->prepare(
            "SELECT pi.*, s.nome as servico_nome, s.descricao as servico_descricao, s.valor as valor_padrao
             FROM {$wpdb->prefix}msc_proposta_itens pi
             LEFT JOIN {$wpdb->prefix}msc_servicos s ON pi.servico_id = s.id
             WHERE pi.proposta_id = %d",
            $proposta_id
        ));

        error_log('SQL Query: ' . $wpdb->last_query);
        error_log('Itens encontrados: ' . print_r($itens, true));

        // Buscar cláusulas da proposta
        $clausulas = get_option('msc_clausulas_padrao', array());

        // Configurações do Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        // Gerar HTML do PDF
        $html = $this->renderizar_html_proposta($proposta, $itens, $clausulas);

        // Carregar HTML no Dompdf
        $dompdf->loadHtml($html);

        // (Opcional) Definir o tamanho do papel e a orientação
        $dompdf->setPaper('A4', 'portrait');

        // Renderizar o PDF
        $dompdf->render();

        // Enviar o PDF para o navegador
        $dompdf->stream('proposta_' . $proposta_id . '.pdf', array('Attachment' => 0));
    }

    private function renderizar_html_proposta($proposta, $itens, $clausulas) {
        ob_start();
        ?>
        <html>
        <head>
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
            <style>
                body { font-family: Helvetica, sans-serif; background-color: #f8f9fa; }
                .header { background-color: #343a40; color: white; text-align: center; padding: 5px; }
                .container { margin-top: 20px; }
                .table { width: 100%; margin-top: 20px; border: 1px solid #ddd; }
                .table th { background-color: #007bff; color: white; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #555; }
                h2 { margin-top: 20px; }
                h3 { margin-top: 15px; }
                .section { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background-color: white; }
                .badge-custom { background-color: #007bff; color: white; }
            </style>
        </head>
        <body>
            <header class="header">
                <h1>Proposta de desenvolvimento</h1>
                <p>
                    <span class="badge badge-light text-primary px-3 py-2">
                        Proposta nº <?php echo str_pad($proposta->id, 5, '0', STR_PAD_LEFT); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar-alt"></i> Data: <?php echo date('d/m/Y'); ?>
                    </span>
                </p>
            </header>
            <div class="container">
                <div class="section">
                    <h2><?php echo $proposta->titulo; ?></h2>
                    <p><?php echo nl2br($proposta->descricao); ?></p>
                </div>
                <div class="section">
                    <h3>DADOS DO CLIENTE</h3>
                    <p>Nome: <?php echo $proposta->cliente_nome; ?></p>
                    <?php if ($proposta->cliente_email): ?>
                    <p>Email: <?php echo $proposta->cliente_email; ?></p>
                    <?php endif; ?>
                    <?php if ($proposta->cliente_telefone): ?>
                    <p>Telefone: <?php echo $proposta->cliente_telefone; ?></p>
                    <?php endif; ?>
                    <?php if ($proposta->cliente_endereco): ?>
                    <p>Endereço: <?php echo $proposta->cliente_endereco; ?></p>
                    <?php endif; ?>
                </div>
                <div class="section">
                    <h3>PRODUTOS E SERVIÇOS</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Qtd</th>
                                <th>Valor Unit.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?php echo $item->servico_nome; ?></td>
                                <td><?php echo $item->quantidade; ?></td>
                                <td>R$ <?php echo number_format($item->valor_unitario, 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($item->quantidade * $item->valor_unitario, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="section">
                    <h3>CONDIÇÕES GERAIS</h3>
                    <?php if (!empty($clausulas)): ?>
                    <ul>
                    <?php foreach ($clausulas as $clausula => $valor): ?>
                        <li><?php echo ucfirst($clausula) . ': ' . $valor; ?></li>
                    <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer">
                <p>Obrigado por escolher nossos serviços!</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

class MSC_PDF_Generator_Proposta {
    private $pdf_generator;

    public function __construct() {
        $this->pdf_generator = new MSC_PDF_Generator();
    }

    public function gerar_pdf_proposta($proposta_id) {
        $this->pdf_generator->gerar_pdf_proposta($proposta_id);
    }
}

function msc_gerar_pdf_proposta($proposta_id) {
    $pdf_generator = new MSC_PDF_Generator_Proposta();
    $pdf_generator->gerar_pdf_proposta($proposta_id);
}
