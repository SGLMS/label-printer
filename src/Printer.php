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

use JamesRCZ\HtmlBuilder\Html;

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
class Printer extends \Mpdf\Mpdf
{
    protected array               $pdfConfiguration = [
        'mode'          => "utf-8",
        'format'        => [100, 100],
        'margin_top'    => 4,
        'margin_bottom' => 4,
        'margin_left'   => 4,
        'margin_right'  => 4,
        'tempDir'       => TMPPATH  // Important
    ];
    protected int    $pageNumber = 1;
    protected array  $printLog   = [];
    public string $css;

    /**
     * Constructor
     *
     * @param ?array  $config  Config (MPDF configuration)
     * @param ?string $cssFile CSS File [Optional]
     **/
    public function __construct(?array $config = [], ?string $cssFile = null)
    {
        parent::__construct(
            $config ?
                array_merge($this->pdfConfiguration, $config) :
                $this->pdfConfiguration
        );
        if ($cssFile && is_file($cssFile)) {
            $this->css = file_get_contents($cssFile);
        } else {
            $this->css = '';
        }
        /* var_dump($this->css);die; */
        $this->WriteHTML(
            $this->css,
            \Mpdf\HTMLParserMode::HEADER_CSS
        );
    }

    /**
     * Add Label
     *
     * Make sure each label is printed in a separate page.
     *
     * @param $label \Sglms\LabelPrinter\Label
     *
     * @return array
     **/
    public function addLabel(\Sglms\LabelPrinter\Label $label): array
    {
        if (
            !$this->printLog
            || ($label->number != $this->printLog[array_key_last($this->printLog)])
        ) {
            $this->printLog[] = $label->number;
            $this->WriteHTML(
                Html::create(
                    'body',
                    div(
                        // "TODO: Temporary Content",
                        $label->render(),
                        $this->pageNumber > 1 ?
                            ['style' => 'page-break-before:always'] : []
                    )
                )
            );
            $this->pageNumber++;
        }
        return $this->printLog;
    }
}
