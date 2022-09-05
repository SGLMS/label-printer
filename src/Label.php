<?php
/**
 * SGLMS Label Printer
 *
 * PHP Version 8.1
 *
 * @category SGLMS_Library
 * @package  LabelPrinter
 * @author   Jaime C. Rubin-de-Celis <james@sglms.com>
 * @license  MIT (https://sglms.com/license)
 * @link     https://sglms.com
 **/

declare(strict_types=1);

namespace Sglms\LabelPrinter;

use JamesRCZ\HtmlBuilder\HtmlBuilder;
use JamesRCZ\HtmlBuilder\Html;
use Mpdf\Mpdf;
use Sglms\Gs1Gtin\Gtin;
use Sglms\Gs1Gtin\Gs1;

use function JamesRCZ\HtmlBuilder\div;

/**
 * Label Class
 *
 * @category SGLMS_Library
 * @package  LabelPrinter
 * @author   Jaime C. Rubin-de-Celis <james@sglms.com>
 * @license  MIT https://sglms.com/license
 * @link     https://sglms.com/
 **/
class Label
{
    protected \Sglms\Gs1Gtin\Gtin $gtin;
    protected \Sglms\Gs1Gtin\Gs1  $gs1;
    protected \JamesRCZ\HtmlBuilder\HtmlBuilder $html;
    protected array               $pdfConfiguration = [
        'mode'          => "utf-8",
        'format'        => [100,100],
        'margin_top'    => 5,
        'margin_bottom' => 5,
        'margin_left'   => 5,
        'margin_right'  => 5,
    ];
    protected string              $css = "
        .bold{font-weight:bold;}
        .center{text-align:center;}
        .text-right{text-align:right;}
        .mono{font-family:monospace;}
        .border{border: 1px solid black;}
        .m-1{margin: .1em;}
        .p-1{padding: .1em;}
        .text-2xs {font-size:8px;}
        .text-xs {font-size:10px;}
        .text-sm {font-size:12px;}
        .text-xl {font-size:36px;}
        .text-2xl {font-size:48px;}
        .w-full{width:100%;}
        .table-info {font-size:11px;font-family:monospace;width:100%;}
        .table-info tr td:nth-child(2) {font-weight:bold;}
        .table-info tr td:nth-child(3) {font-weight:bold;text-align:right;}
    ";


    public int      $productid;
    public string   $productName;
    public string   $clientid;
    public string   $clientName;
    public string   $sku;
    public string   $generator = "SGLMS Label Printer";
    public float    $weight = 0;

    /**
     * Constructor
     *
     * @param $number      Number
     * @param $productid   Product Identifier
     * @param $clientid    Client Identifier [Optional]
     * @param $productName Product Name [Optional]
     * @param $clientName  Client [Optional]
     **/
    public function __construct(
        int $number,
        int $productid,
        string $clientid,
        ?string $productName = null,
        ?string $clientName  = null
    ) {
        $this->number       = $number;
        $this->clientid     = $clientid ?? '1';
        $this->clientName   = $clientName ?? $this->clientid;
        $this->productid    = $productid ?? 1;
        $this->productName  = $productName ?? _("n/a");
        $this->sku          = '-';
        $this->gtin         = Gtin::create($this->productid, $this->clientid, 2);
        $this->gs1          = new Gs1("(01)" . $this->gtin . "(3102)0(3302)0(37)0");
    }

    /**
     * Load info from GS1 String
     *
     * @param $gs1 GS1-128 String
     *
     * @return void
     **/
    public function fromGS1(string $gs1)
    {
        $this->gs1      = new \Sglms\Gs1Gtin\Gs1($gs1);
        $this->gtin     = new \Sglms\Gs1Gtin\Gtin($this->gs1->gtin);
        $this->weight   = $this->gs1->grossWeight;
    }

    /**
     * Image (object) with Label Barcode
     *
     * @return \JamesRCZ\HtmlBuilder\HtmlBuilder
     **/
    protected function getLabelBarcode(): \JamesRCZ\HtmlBuilder\HtmlBuilder
    {
        $barcode = new \Picqer\Barcode\BarcodeGeneratorPNG();
        return HtmlBuilder::create(
            'img',
            null,
            [
                'src'   => "data:image/png;base64,"
                . base64_encode($barcode->getBarcode((string) $this->number, $barcode::TYPE_CODE_128)),
                'style' => "width: 8cm; height: 1.2cm;",
            ]
        );
    }

    /**
     * Image (object) with GS1 Barcode
     *
     * @return \JamesRCZ\HtmlBuilder\HtmlBuilder
     **/
    protected function getGs1Barcode(): \JamesRCZ\HtmlBuilder\HtmlBuilder
    {
        return HtmlBuilder::create(
            'img',
            null,
            [
                'src'   => $this->gs1->getBarcodeSource(1, 32),
                'style' => "width: 8cm;",
                'class' => "w-full"
            ]
        );
    }

    /**
     * Get GTIN Tag
     *
     * @return string
     **/
    protected function getGtinTag()
    {
        return div(
            div("GTIN-14:", 'text-xs')
            . div((string) HtmlBuilder::create('img', null, ['src'=> $this->gtin->getBarcodeSource(2, 24)]), 'center')
            . div((string) $this->gtin, 'bold center text-sm mono'),
            'text-sm border'
        );
    }

    /**
     * Get GS1 Tag
     *
     * @return string
     **/
    protected function getGs1Tag()
    {
        return div(
            div("GS1-128:", 'text-xs')
            . div((string) $this->getGs1Barcode(), 'center')
            . div((string) $this->gs1, 'center text-xs'),
            'text-sm border'
        );
    }

    /**
     * Build Table with Client Product information
     *
     * @return \JamesRCZ\HtmlBuilder\HtmlBuilder
     **/
    protected function getInfoTable()
    {
        $table = Html::table(
            [
                [
                    _("SKU"),
                    substr($this->sku, 0, 32),
                    '-'
                ],
                [
                    _("Product"),
                    substr($this->productName, 0, 32),
                    (string) $this->productid,
                ],
                [
                    _("Client"),
                    $this->clientName,
                    $this->clientid
                ],
                [
                    _("Weight")."/"._("Units"),
                    (string) $this->weight . _("(Gross)"),
                    $this->gs1->units
                ]
            ],
            [],
            [],
            'table-info'
        );
        return $table;
    }

    /**
     * Generate Label (PDF)
     *
     * @return \Mpdf\Mpdf
     **/
    public function getPDF()
    {
        $pdf = new \Mpdf\Mpdf($this->pdfConfiguration);
        $pdf->WriteHTML($this->css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML($this->render());

        return $pdf;
    }

    /**
     * Render Label
     *
     * @param $html Render pure HTML [false]
     *
     * @return string
     **/
    public function render(?bool $html = false)
    {
        $this->html = HtmlBuilder::create('div', null, 'mono');
        if ($html) {
            $this->html->setAttribute('style', "width:10cm;height:10cm;");
            $this->html->addContent(HtmlBuilder::create('style', $this->css));
        }
        $this->html->addContent(div((string) $this->getLabelBarcode(), 'center'));
        $this->html->addContent(div((string) $this->number, 'text-2xl center bold border m-1'));
        $this->html->addContent($this->getInfoTable());
        $this->html->addContent($this->getGtinTag());
        $this->html->addContent($this->getGs1Tag());
        $this->html->addContent(div("__________", 'text-right text-2xs'));
        $this->html->addContent(div($this->generator, "text-2xs text-right"));

        return $this->html;
    }
}
