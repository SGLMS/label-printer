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
        .text-2xl {font-size:64px;}
        .w-full{width:100%;}
        .w-1/2{width:%0%;}
        .table-info {font-size:18px;font-family:monospace;width:100%;}
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
        ?string    $date        = 'now'
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
     * Date Formatter
     *
     * PHP 8.1
     *
     * @param \DateTime $date     Date string
     * @param mixed     $locale   Locale
     * @param string    $timezone Timezone
     *
     * @return \IntlDateFormatter
     */
    public static function dateFormatter(
        \DateTime|\DateTimeImmutable $date,
        ?string $locale   = 'es_CL',
        ?string $timezone = 'UTC'
    ): string {
        $lang       = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $acceptLang = ['es'];
        $lang       = in_array($lang, $acceptLang) ? $lang : $locale;
        $formatter  = new \IntlDateFormatter($lang, 2, -1, $timezone);
        return $formatter->format($date->getTimestamp());
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
                'style' => "width: 8.75cm; height: .8cm;",
            ]
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
            div((string) $this->getGs1Barcode(), 'text-center p-1')
            . div((string) $this->gs1, 'text-center text-2xs font-semibold'),
            'text-sm border'
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
                'style' => "width: 8.75cm; height: 0.6cm;",
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
            . div((string) HtmlBuilder::create('img', null, ['src'=> $this->gtin->getBarcodeSource(2, 24)]), 'text-center')
            . div((string) $this->gtin, 'font-semibold text-center text-sm mono'),
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
     * @param $maxProdCharWidth Line width
     *
     * @return string
     **/
    public function render(?int $maxProdCharWidth = 80)
    {
        $this->html = HtmlBuilder::create('div', null, 'label');
        $this->html->addContent(
            div(
                (string) $this->getLabelBarcode(),
                'barcode text-center'
            )
        );
        $this->html->addContent(
            div(
                (string) $this->number,
                'number text-center font-bold border m-1 line-fit' . (strlen((string) $this->number) > 9 ? ' text-xl ' : ' text-3xl ')
            )
        );
        $this->html->addContent(
            div(
                substr($this->clientName, 0, 32),
                [
                    'class' => 'text-center text-xl line-fit font-bold',
                ]
            )
        );
        $this->html->addContent(
            div(
                sprintf(
                    "%s",
                    substr($this->productName, 0, $maxProdCharWidth),
                ),
                [
                    'class' => 'text-center text-lg font-bold line-fit',
                    'style' => 'margin:4px 0;'
                ]
            )
        );
        $this->html->addContent(
            div(
                div(
                    self::dateFormatter($this->date),
                    ['class'=>"text-center font-bold text-lg"]
                ),
                [
                    'class' => 'p-1',
                    'style' => "border-top:1px dashed black;"
                ]
            )
        );
        if ($this->sku != $this->number) {
            $this->html->addContent(
                div(
                    div(_("SKU"), ['style'=>"width:20%; float:left;"])
                    . div(
                        $this->sku,
                        ['style'=>"width:79%;float:left;font-weight:bold;"]
                    ),
                    [
                        'class' => 'text-sm text-right',
                        'style' => "border-top:1px dotted black;"
                    ]
                )
            );
        } else {
            $this->html->addContent(div("-"));
        }
        if ($this->units) {
            $this->html->addContent(
                div(
                    div(_("Units / Weight"), ['style'=>"width:35%; float:left;"])
                    .div(
                        $this->units . " x "
                        . round($this->weight / $this->units, 1). "Kg"
                        . " = "
                        . span($this->weight . "Kg", 'text-right font-bold'),
                        ['class' => "text-right",'style'=>"width:64%;float:left;"]
                    ),
                    [
                        'class' => 'text-sm',
                        'style' => "border-top:1px dotted black;"
                    ]
                )
            );
        } else {
            $this->html->addContent(div("-"));
        }
        /* $this->html->addContent($this->getGtinTag()); */
        $this->html->addContent($this->getGs1Tag());
        $this->html->addContent(
            div(
                $this->generator
                . " / " . APP,
                [
                    'style' => 'font-size:8px; text-align:right;',
                ]
            )
        );
        return $this->html;
    }
}
