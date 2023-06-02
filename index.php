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

use Sglms\LabelPrinter\Label;
use Sglms\LabelPrinter\Printer;

require "vendor/autoload.php";

define('TMPPATH', '/tmp'); // ¡¡¡IMPORTANT!!!
define('APP', "SGLMS Label Printer");

$label              = new Label(123456, 555);
$label->clientName  = "PRODUCT OWNER NAME OR IDENTIFICATION";
$label->productName = "Product Name (this can be a long, long, long, very long, very long, very long name)";
$label->sku         = "PRDCTNN-XX-DS-12345678-123456789";
$label->generator   = "SGLMS TAILOR-MADE SOFTWARE SOLUTIONS";

// Get additional product information from GS1-128
$label->fromGS1("(01)11230000456781(37)18(3302)987654(3102)123456");

// Create the printer (PDF)
$printer = new Printer(
    [
        'format' =>  [100,100],
    ],
    'resources/theme.css'
);

// Add labels
$printer->addLabel($label);

// One line
$label1  = new Label(9876540, 0, 'ClientID', 'Product Name', 'Client Name', '-5day');
$printer->addLabel($label1);

// Missing values
$label2  = new Label(9876541);
$printer->addLabel($label2);

$printer->output();
