<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir Dompdf
require_once(plugin_dir_path(__FILE__) . '../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

class MSC_PDF_Generator
{
    public function gerar_pdf_proposta($proposta_id)
    {
        global $wpdb;

        // Buscar a URL da assinatura das configurações
        $assinatura_path = get_option('msc_assinatura_responsavel', '');

        // Converter o caminho do arquivo em base64 para incluir diretamente no HTML
        $assinatura_base64 = '';
        if (!empty($assinatura_path)) {
            $assinatura_path = str_replace('\\', '/', $assinatura_path);
            if (file_exists($assinatura_path)) {
                $assinatura_type = pathinfo($assinatura_path, PATHINFO_EXTENSION);
                $assinatura_data = file_get_contents($assinatura_path);
                if ($assinatura_data !== false) {
                    $assinatura_base64 = 'data:image/' . $assinatura_type . ';base64,' . base64_encode($assinatura_data);
                    error_log('Assinatura convertida para base64 com sucesso');
                } else {
                    error_log('Erro ao ler o arquivo da assinatura: ' . $assinatura_path);
                }
            } else {
                error_log('Arquivo da assinatura não encontrado: ' . $assinatura_path);
            }
        } else {
            error_log('Caminho da assinatura está vazio');
        }

        // Debug - Verificar a URL da assinatura
        error_log('URL da assinatura: ' . $assinatura_path);

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

        // Buscar cláusulas da proposta
        $clausulas = get_option('msc_clausulas_padrao', array());

        // Configurar opções do DomPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultMediaType', 'screen');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultFont', 'helvetica');
        $options->set('chroot', [
            ABSPATH,
            WP_CONTENT_DIR,
            plugin_dir_path(__FILE__),
        ]);

        // Inicializar DomPDF com as opções
        $dompdf = new Dompdf($options);

        // Caminho absoluto da imagem da capa
        $caminho_capa = site_url('/wp-content/plugins/MeuSistemaClientes/assets/images/capa.png');

        // Gerar HTML do PDF
        $html = $this->renderizar_html_proposta($proposta, $itens, $clausulas, $caminho_capa, $assinatura_base64);

        // Carregar HTML no Dompdf
        $dompdf->loadHtml($html);

        // Definir o tamanho do papel e a orientação
        $dompdf->setPaper('A4', 'portrait');

        // Renderizar o PDF
        $dompdf->render();

        // Enviar o PDF para o navegador
        $dompdf->stream('proposta_' . $proposta_id . '.pdf', array('Attachment' => 0));
    }

    private function renderizar_html_proposta($proposta, $itens, $clausulas, $caminho_capa, $assinatura_base64)
    {
        ob_start();
?>
        <html>

        <head>
            <style>
                @page {
                    size: A4;
                    margin: 0;
                }

                body {
                    font-family: Helvetica, sans-serif;
                    margin: 0;
                    padding: 0;
                }

                /* Página da capa */
                .capa {
                    width: 210mm;
                    height: 297mm;
                    background: url('<?php echo $caminho_capa; ?>') no-repeat center center;
                    background-size: cover;
                    position: relative;
                }

                /* Posicionamento dos textos */
                .texto {
                    position: absolute;
                    width: 100%;
                    text-align: left;
                    color: white;
                    font-weight: bold;
                }

                .proposta {
                    top: 22mm;
                    left: 25mm;
                    font-size: 12pt;
                }

                .titulo {
                    top: 90mm;
                    left: 25mm;
                    font-size: 50pt;
                    font-weight: bold;
                }

                .data {
                    top: 225mm;
                    left: 140mm;
                    font-size: 12pt;
                    color: #E3002B;
                }

                .ano {
                    top: 232mm;
                    left: 140mm;
                    font-size: 18pt;
                    font-weight: bold;
                    color: #000;
                }

                .autor {
                    top: 255mm;
                    left: 25mm;
                    font-size: 12pt;
                    color: #E3002B;
                }

                .nome {
                    top: 260mm;
                    left: 25mm;
                    font-size: 12pt;
                    font-weight: bold;
                    color: #000;
                }

                /* Forçar quebra de página após capa */
                .pagina {
                    padding: 20px;
                    page-break-before: always;
                }

                .header {
                    background-color: #343a40;
                    color: white;
                    text-align: center;
                    padding: 10px;
                    margin-bottom: 20px;
                }

                .section {
                    margin-bottom: 20px;
                    padding: 15px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    background-color: white;
                    page-break-inside: avoid;
                }

                .table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }

                .table th,
                .table td {
                    border: 1px solid #ddd;
                    padding: 10px;
                    text-align: left;
                }

                .table th {
                    background-color: #007bff;
                    color: white;
                }

                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    text-align: center;
                    padding: 10px;
                    font-size: 10pt;
                    border-top: 1px solid #ddd;
                }

                .titulo-proposta {
                    font-size: 15pt;
                    font-weight: bold;
                    text-align: center;
                    color: #2C3E50;
                    /* Cor azul escuro para elegância */
                    text-transform: uppercase;
                    border-bottom: 3px solid #007bff;
                    /* Linha azul para destacar o título */
                    padding-bottom: 5px;
                    margin-bottom: 15px;
                }

                .descricao-proposta {
                    font-size: 12pt;
                    color: #333;
                    /* Cinza escuro para facilitar a leitura */
                    line-height: 1.6;
                    /* Melhor espaçamento entre linhas */
                    text-align: justify;
                    margin-bottom: 20px;
                }
            </style>
        </head>

        <body>
            <!-- Página da Capa -->
            <div class="capa">
                <div class="texto proposta">PROPOSTA Nº <?php echo str_pad($proposta->id, 5, '0', STR_PAD_LEFT); ?></div>
                <div class="texto titulo">Projeto de Desenvolvimento</div>
                <div class="texto data" style="margin-top: 110px;">28 de janeiro de 2025</div>
                <div class="texto ano" style="margin-top: 110px;">2025</div>
                <div class="texto autor">Apresentado por</div>
                <div class="texto nome">Felipe Velêz</div>
                <div class="footer">
                    <p>Soluções em Software</p>
                </div>
            </div>

            <!-- Página 2 - Conteúdo da proposta -->
            <div class="pagina">

                <div class="section">
                    <h2 class="titulo-proposta" style="margin-top: 60px;"><?php echo $proposta->titulo; ?></h2>
                    <p class="descricao-proposta"><?php echo nl2br($proposta->descricao); ?></p>
                </div>
                <div class="section">
                    <h3>PRODUTOS E SERVIÇOS</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Qtd</th>
                                <th>Valor Unit.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalGeral = 0; // Variável para armazenar o total geral
                            foreach ($itens as $item):
                                $totalItem = $item->quantidade * $item->valor_unitario; // Total por item
                                $totalGeral += $totalItem; // Soma do total geral
                            ?>
                                <tr>
                                    <td><?php echo $item->servico_nome; ?></td>
                                    <td><?php echo $item->quantidade; ?></td>
                                    <td>R$ <?php echo number_format($item->valor_unitario, 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($totalItem, 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Total Geral:</strong></td>
                                <td><strong>R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="section">
                        <h3>Cláusulas da Proposta</h3>
                        <p><strong>Validade da Proposta:</strong> <?php echo $clausulas['validade']; ?></p>
                        <p><strong>Forma de Pagamento:</strong> <?php echo nl2br($clausulas['pagamento']); ?></p>
                        <p><strong>Prazo de Execução:</strong> <?php echo nl2br($clausulas['prazo_execucao']); ?></p>
                        <p><strong>Observações:</strong> <?php echo nl2br($clausulas['observacoes']); ?></p>
                    </div>

                    <!-- Adicionar Assinatura e Data -->
                    <div class="section" style="margin-top: 50px; text-align: center;">
                        <p><strong>Assinatura do Responsável:</strong></p>
                        <?php if (!empty($assinatura_base64)): ?>
                            <img src="<?php echo $assinatura_base64; ?>" alt="Assinatura do Responsável" style="max-width: 150px; max-height: 50px; margin: 10px auto; display: block;">
                        <?php else: ?>
                            <p style="font-style: italic; color: #555;">Assinatura não configurada.</p>
                        <?php endif; ?>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y'); ?></p>
                    </div>

                </div>
            </div>

            <div class="footer">
                <p> Soluções em Software</p>
            </div>
        </body>

        </html>
<?php
        return ob_get_clean();
    }
}



class MSC_PDF_Generator_Proposta
{
    private $pdf_generator;

    public function __construct()
    {
        $this->pdf_generator = new MSC_PDF_Generator();
    }

    public function gerar_pdf_proposta($proposta_id)
    {
        $this->pdf_generator->gerar_pdf_proposta($proposta_id);
    }
}

function msc_gerar_pdf_proposta($proposta_id)
{
    $pdf_generator = new MSC_PDF_Generator_Proposta();
    $pdf_generator->gerar_pdf_proposta($proposta_id);
}
