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

use function JamesRCZ\HtmlBuilder\a;
use function JamesRCZ\HtmlBuilder\div;
use function JamesRCZ\HtmlBuilder\p;
use function JamesRCZ\HtmlBuilder\span;

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
        .text-center{text-align:center;}
        .line-fit {line-height:0.9;}
        .text-right{text-align:right;}
        .mono{font-family:monospace;}
        .border{border: 1px solid black;}
        .m-1{margin: .1em;}
        .p-1{padding: .1em;}
        .text-2xs {font-size:8px;}
        .text-xs {font-size:10px;}
        .text-sm {font-size:12px;}
        .text-base{font-size:16px;}
        .text-lg {font-size:24px;}
        .text-xl {font-size:32px;}
        .text-2xl {font-size:60px;}
        .w-full{width:100%;}
        .w-1/2{width:%0%;}
        .table-info {font-size:18px;font-family:Verdana,monospace;width:100%;}
        .table-info tr td:nth-child(1) {font-weight:bold;}
        .table-info tr td:nth-child(2) {font-weight:bold;text-align:right;}
    ";


    public int        $number;
    public int|string $productid;
    public string     $productName;
    public int|string $clientid;
    public string     $clientName;
    public string     $sku;
    public float      $weight = 0;
    public float      $units  = 0;
    public string     $generator = "SGLMS Label Printer";
    public \DateTime  $date;

    /**
     * Constructor
     *
     * @param $number      Number
     * @param $productid   Product Identifier
     * @param $clientid    Client Identifier [Optional]
     * @param $productName Product Name [Optional]
     * @param $clientName  Client [Optional]
     * @param $date        Date [Optional]
     **/
    public function __construct(
        int        $number,
        ?int       $productid   = 1,
        ?string    $clientid    = "CLIENTID",
        ?string    $productName = null,
        ?string    $clientName  = null,
        string     $date        = 'now'
    ) {
        $this->number       = $number;
        $this->clientid     = $clientid ?? '1';
        $this->clientName   = $clientName ?: (string) $this->clientid;
        $this->productid    = $productid ?? 1;
        $this->productName  = $productName ?? "PRODUCT NAME";
        $this->sku          = (string) $number;
        $this->gtin         = Gtin::create(
            $this->productid,
            (string) $this->clientid,
            'GTIN-14',
            2
        );
        $this->gs1          = new Gs1("(01)" . $this->gtin);
        $this->date         = (new \DateTime($date));
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
        $this->gtin     = new \Sglms\Gs1Gtin\Gtin((int) $this->gs1->gtin);
        $this->weight   = $this->gs1->grossWeight;
        $this->units    = $this->gs1->units;
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
                'style' => "width: 8.75cm; height: 1cm;",
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
                'style' => "width: 8.75cm; height: 1cm;",
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
            div((string) $this->getGs1Barcode(), 'center p-1')
            . div((string) $this->gs1, 'center text-2xs bold'),
            'text-sm border'
        );
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
        $this->html->addContent(
            div((string) $this->getLabelBarcode(), 'center')
        );
        $this->html->addContent(
            div(
                (string) $this->number,
                'center bold border m-1 line-fit' . (strlen((string) $this->number) > 9 ? ' text-xl ' : ' text-2xl ')
            )
        );
        $this->html->addContent(
            div(
                substr($this->clientName, 0, 48),
                [
                    'class' => 'text-center text-lg line-fit bold',
                    'style' => ''
                ]
            )
        );
        $this->html->addContent(
            div(
                sprintf(
                    "[%s] %s",
                    $this->productid,
                    substr($this->productName, 0, 48)
                ),
                [
                    'class' => 'text-center bold line-fit',
                    'style' => 'margin:5px 0;'
                ]
            )
        );
        $this->html->addContent(
            div(
                div(_("Date"), ['style'=>"width:20%; float:left;"])
                . div(
                    $this->date->format("Y-m-d"),
                    ['style'=>"width:79%;float:left;font-weight:bold;"]
                ),
                'border text-sm p-1'
            )
        );
        $this->html->addContent(
            div(
                div(_("SKU"), ['style'=>"width:20%; float:left;"])
                .div($this->sku, ['style'=>"width:79%;float:left;font-weight:bold;"]),
                'border text-sm p-1'
            )
        );
        if ($this->units) {
            $this->html->addContent(
                div(
                    div(_("Units / Weight"), ['style'=>"width:35%; float:left;"])
                    .div(
                        $this->units . " x "
                        . round($this->weight / $this->units, 1). "Kg"
                        . " = "
                        . span($this->weight . "Kg", 'bold'),
                        ['style'=>"width:64%;float:left;text-align:right;"]
                    ),
                    'border text-sm p-1'
                )
            );
        }
        /* $this->html->addContent($this->getGtinTag()); */
        $this->html->addContent($this->getGs1Tag());
        $this->html->addContent(div("__________", 'text-right text-2xs'));
        $this->html->addContent(
            div(
                $this->generator,
                [
                    'class' => 'text-xs bold text-right',
                ]
            )
        );
        return $this->html;
    }
}
