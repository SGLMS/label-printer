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
        .mono{font-family:monospace;}
        .border{border: 1px solid black;}
        .m-1{margin: .1em;}
        .p-1{padding: .1em;}
        .text-2xs {font-size:8px;}
        .text-xs {font-size:10px;}
        .text-sm {font-size:12px;}
        .text-xl {font-size:36px;}
        .text-2xl {font-size:42px;}
        .w-full{width:100%;}
    ";

    public int      $productid;
    public string   $productName;
    public string   $clientid;
    public string   $clientName;

    /**
     * Constructor
     *
     * @param $number     Number
     * @param $productid  Product Identifier
     * @param $clientid   Client Identifier [Optional]
     * @param $clientName Client [Optional]
     **/
    public function __construct(
        int $number,
        int $productid = 0,
        ?string $clientid = null,
        ?string $clientName = null
    ) {
        $this->number       = $number;
        $this->clientid     = $clientid ?? '1';
        $this->clientName   = $clientName ?? $this->clientid;
        $this->productid    = $productid ?? 1;
        $this->gtin         = Gtin::create($this->productid, $this->clientid, 2);
        $this->gs1          = new Gs1(
            "(01)" . $this->gtin
        );
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
                'src'   => $this->gs1->getBarcodeSource(1, 16),
                'style' => "width: 8cm;",
                'class' => "w-full"
            ]
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
                [_("Client ID"), div($this->clientid, 'bold')],
                [_("Client"), div($this->clientName, 'bold')],
                [_("Product ID"), div((string) $this->productid, 'bold')],
                [_("Product"), div(substr((string) $this->productName, 0, 42), 'bold')],
                [
                    _("GTIN"),
                    (string) HtmlBuilder::create('img', null, ['src'=> $this->gtin->getBarcodeSource(2, 24)])
                    . div((string) $this->gtin, 'bold center text-sm mono')
                ],
            ],
            [],
            [],
            'w-full text-sm'
        );
        return $table;
    }

    /**
     * Generate Label (PDF)
     *
     * @return \Mpdf\Mpdf
     **/
    public function generatePDF()
    {
        $pdf = new \Mpdf\Mpdf($this->pdfConfiguration);

        $pdf->WriteHTML($this->css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML(div((string) $this->getLabelBarcode(), "center"));
        $pdf->WriteHTML(div((string) $this->number, "bold mono center border text-2xl m-1"));
        $pdf->WriteHTML($this->getInfoTable());
        $pdf->WriteHTML(
            div(
                div("GS1-128 : ", 'text-xs')
                . div((string) $this->getGs1Barcode(), "center")
                . div((string) $this->gs1, 'text-xs bold center mono'),
                'border p-1'
            )
        );
        $pdf->WriteHTML("----------------");
        $pdf->WriteHTML(div("SGLMS Ingeniería y Gestión", "text-2xs"));

        return $pdf;
    }
}
