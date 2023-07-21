<?php

/**
 * SGLMS Label Printer
 *
 * PHP Version 8.2
 *
 * @category SGLMS_Library
 * @package  LabelPrinter
 * @author   Jaime C. Rubin-de-Celis <james@sglms.com>
 * @license  MIT (https://sglms.com/license)
 * @link     https://sglms.com
 **/

use Sglms\LabelPrinter\Label;
use Sglms\LabelPrinter\Printer;
use function \JamesRCZ\HtmlBuilder\div;

require "vendor/autoload.php";

define('TMPPATH', '/../tmp/'); // ¡¡¡IMPORTANT!!!
define('APP', "SGLMS Label Printer");

$label              = new Label(123456789, 555);

$label->clientName  = "OWNER NAME OR IDENTIFICATION";
$label->productName = "Product Name (this can be a long, long, long, very long, very long, very long name)";
$label->sku         = "PRDCTNN-XX-DS-12345678-123456789";
$label->generator   = "SGLMS TAILOR-MADE SOFTWARE SOLUTIONS";

// Get additional product information from GS1-128
$label->fromGS1("(01)11230000456781(37)18(3302)987654(3102)123456");

// Uncomment to render html!
// echo div($label->render(), ['style' => "width:100mm;height:100mm;border:1px solid gray;padding:4px; text-align:center;font-family:sans-serif;"]);
// die;

// Create the printer (PDF)
// Sizes (default) 100mmx100mm (100x70mm)
// Other sizes will work, but you'll need to adjust font sizes and spacers.
$printer = new Printer(
    [
        'format' =>  [100, 100],
    ],
    // 'resources/theme.css'
);

// Add labels
$printer->addLabel($label);

// One line
$label1  = new Label(987654321, 0, 'ClientID', 'Product Name', 'Client Name', '-5day');
$printer->addLabel($label1);

// Missing values
$label2  = new Label(11235813);
$printer->addLabel($label2);

$printer->output();
