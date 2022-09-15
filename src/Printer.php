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

/**
 * Label Class
 *
 * @category SGLMS_Library
 * @package  LabelPrinter
 * @author   Jaime C. Rubin-de-Celis <james@sglms.com>
 * @license  MIT https://sglms.com/license
 * @link     https://sglms.com/
 **/
class Printer extends \Mpdf\Mpdf
{
    protected array               $pdfConfiguration = [
        'mode'          => "utf-8",
        'format'        => [100,100],
        'margin_top'    => 5,
        'margin_bottom' => 5,
        'margin_left'   => 5,
        'margin_right'  => 5,
        'tempDir'       => TMPPATH
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
        .text-lg {font-size:24px;}
        .text-xl {font-size:32px;}
        .text-2xl {font-size:60px;}
        .w-full{width:100%;}
        .table-info {font-size:18px;font-family:Verdana,monospace;width:100%;}
        .table-info tr td:nth-child(1) {font-weight:bold;}
        .table-info tr td:nth-child(2) {font-weight:bold;text-align:right;}
    ";

    /**
     * Constructor
     *
     * @param $config MPDF Configuration & Options
     **/
    public function __construct(array $config = [])
    {
        parent::__construct($config ?: $this->pdfConfiguration);
        $this->WriteHTML(
            $this->css,
            \Mpdf\HTMLParserMode::HEADER_CSS
        );
    }

    /**
     * Add Label
     *
     * @param $label \Sglms\LabelPrinter\Label
     *
     * @return void
     **/
    public function addLabel(\Sglms\LabelPrinter\Label $label): void
    {
        $this->WriteHTML($label->render());
    }
}
