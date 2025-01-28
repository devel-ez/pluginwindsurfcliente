<?php
if (!defined('ABSPATH')) {
    exit;
}

// Incluir TCPDF e classe customizada
require_once(plugin_dir_path(__FILE__) . '../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once(plugin_dir_path(__FILE__) . 'class-msc-pdf-custom.php');

class MSC_PDF_Generator {
    private $pdf;

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

        // Buscar cláusulas da proposta
        $clausulas = get_option('msc_clausulas_padrao', array());

        // Inicializar TCPDF customizado
        $this->pdf = new MSC_TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Configurar documento
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor(get_option('blogname'));
        $this->pdf->SetTitle('Proposta - ' . $proposta->titulo);

        // Configurar margens
        $this->pdf->SetMargins(15, 50, 15);
        $this->pdf->SetHeaderMargin(20);
        $this->pdf->SetFooterMargin(25);

        // Habilitar header e footer
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);

        // Adicionar página
        $this->pdf->AddPage();

        // Definir fonte padrão
        $this->pdf->SetFont('helvetica', '', 12);

        // Número da proposta e data
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 10, 'PROPOSTA Nº ' . str_pad($proposta_id, 5, '0', STR_PAD_LEFT), 0, 1, 'R');
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 10, 'Data: ' . date('d/m/Y'), 0, 1, 'R');
        $this->pdf->Ln(10);

        // Título da proposta com estilo moderno
        $this->pdf->SetFillColor(41, 128, 185);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 20);
        $this->pdf->Cell(0, 15, 'PROPOSTA COMERCIAL', 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);

        // Box com informações do cliente
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'DADOS DO CLIENTE', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 12);
        
        $this->pdf->RoundedRect(15, $this->pdf->GetY(), 180, 40, 3.50, '1111', 'DF', array(), array(245, 245, 245));
        $this->pdf->Ln(5);
        
        $this->pdf->Cell(30, 7, 'Nome:', 0, 0, 'L');
        $this->pdf->Cell(0, 7, $proposta->cliente_nome, 0, 1, 'L');
        
        if ($proposta->cliente_email) {
            $this->pdf->Cell(30, 7, 'Email:', 0, 0, 'L');
            $this->pdf->Cell(0, 7, $proposta->cliente_email, 0, 1, 'L');
        }
        
        if ($proposta->cliente_telefone) {
            $this->pdf->Cell(30, 7, 'Telefone:', 0, 0, 'L');
            $this->pdf->Cell(0, 7, $proposta->cliente_telefone, 0, 1, 'L');
        }
        
        if ($proposta->cliente_endereco) {
            $this->pdf->Cell(30, 7, 'Endereço:', 0, 0, 'L');
            $this->pdf->Cell(0, 7, $proposta->cliente_endereco, 0, 1, 'L');
        }
        
        $this->pdf->Ln(10);

        // Detalhes da proposta
        $this->pdf->SetFillColor(41, 128, 185);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, $proposta->titulo, 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('helvetica', '', 12);
        
        if ($proposta->descricao) {
            $this->pdf->Ln(5);
            $this->pdf->MultiCell(0, 7, $proposta->descricao, 0, 'L');
            $this->pdf->Ln(5);
        }

        // Tabela de serviços com design moderno
        if (!empty($itens)) {
            $this->pdf->Ln(5);
            $this->pdf->SetFillColor(41, 128, 185);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFont('helvetica', 'B', 14);
            $this->pdf->Cell(0, 10, 'SERVIÇOS E PRODUTOS', 0, 1, 'L', true);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('helvetica', '', 12);

            // Cabeçalho da tabela
            $this->pdf->SetFillColor(245, 245, 245);
            $this->pdf->Cell(80, 8, 'Serviço', 1, 0, 'L', true);
            $this->pdf->Cell(25, 8, 'Qtd', 1, 0, 'C', true);
            $this->pdf->Cell(35, 8, 'Valor Unit.', 1, 0, 'R', true);
            $this->pdf->Cell(35, 8, 'Total', 1, 1, 'R', true);

            // Itens da tabela com cores alternadas
            $total_geral = 0;
            $linha = 0;
            foreach ($itens as $item) {
                $fill = $linha % 2 == 0;
                if (empty($item->valor_unitario)) {
                    $item->valor_unitario = $item->valor_padrao;
                }

                $total_item = ($item->quantidade * $item->valor_unitario);
                if (!empty($item->desconto)) {
                    $total_item -= $item->desconto;
                }
                $total_geral += $total_item;

                $this->pdf->SetFillColor(252, 252, 252);
                $this->pdf->Cell(80, 8, $item->servico_nome, 1, 0, 'L', $fill);
                $this->pdf->Cell(25, 8, $item->quantidade, 1, 0, 'C', $fill);
                $this->pdf->Cell(35, 8, 'R$ ' . number_format($item->valor_unitario, 2, ',', '.'), 1, 0, 'R', $fill);
                $this->pdf->Cell(35, 8, 'R$ ' . number_format($total_item, 2, ',', '.'), 1, 1, 'R', $fill);

                if (!empty($item->desconto)) {
                    $this->pdf->SetFont('helvetica', 'I', 10);
                    $this->pdf->Cell(140, 6, 'Desconto:', 0, 0, 'R');
                    $this->pdf->Cell(35, 6, '- R$ ' . number_format($item->desconto, 2, ',', '.'), 0, 1, 'R');
                    $this->pdf->SetFont('helvetica', '', 12);
                }
                $linha++;
            }

            // Total geral com destaque
            $this->pdf->SetFillColor(41, 128, 185);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->SetFont('helvetica', 'B', 12);
            $this->pdf->Cell(140, 10, 'TOTAL GERAL:', 1, 0, 'R', true);
            $this->pdf->Cell(35, 10, 'R$ ' . number_format($total_geral, 2, ',', '.'), 1, 1, 'R', true);
            $this->pdf->SetTextColor(0, 0, 0);
        }

        // Cláusulas e condições com estilo moderno
        $this->pdf->Ln(10);
        $this->pdf->SetFillColor(41, 128, 185);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'CONDIÇÕES GERAIS', 0, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFont('helvetica', '', 12);

        if (!empty($clausulas)) {
            $this->pdf->Ln(5);
            if (isset($clausulas['validade'])) {
                $this->pdf->MultiCell(0, 7, '• Validade da proposta: ' . $clausulas['validade'], 0, 'L');
            }
            if (isset($clausulas['pagamento'])) {
                $this->pdf->MultiCell(0, 7, '• Forma de pagamento: ' . $clausulas['pagamento'], 0, 'L');
            }
            if (isset($clausulas['prazo_execucao'])) {
                $this->pdf->MultiCell(0, 7, '• Prazo de execução: ' . $clausulas['prazo_execucao'], 0, 'L');
            }
            if (isset($clausulas['observacoes'])) {
                $this->pdf->Ln(5);
                $this->pdf->MultiCell(0, 7, $clausulas['observacoes'], 0, 'L');
            }
        }

        // Local e data
        $this->pdf->Ln(20);
        $this->pdf->SetFont('helvetica', '', 12);
        $this->pdf->Cell(0, 7, get_option('blogname') . ', ' . date('d/m/Y'), 0, 1, 'C');

        // Assinaturas com linhas estilizadas
        $this->pdf->Ln(20);
        $this->pdf->SetDrawColor(41, 128, 185);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Cell(85, 0, '', 'T', 0, 'C');
        $this->pdf->Cell(20, 0, '', 0, 0, 'C');
        $this->pdf->Cell(85, 0, '', 'T', 1, 'C');
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(85, 5, 'Responsável', 0, 0, 'C');
        $this->pdf->Cell(20, 5, '', 0, 0, 'C');
        $this->pdf->Cell(85, 5, 'Cliente', 0, 1, 'C');

        // Enviar PDF para o navegador
        $this->pdf->Output('proposta_' . $proposta_id . '.pdf', 'I');
        exit;
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
