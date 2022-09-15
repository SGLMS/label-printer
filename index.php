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

$label              = new Label(12345678901234, 555, '777');
$label->clientName  = "SGLMS";
$label->productName = "Producto para Demostración";
$label->sku         = "PRDCTNN-XX-DS-12345678-123456789000000";
$label->firm        = "SGLMS";

// Get additional product information from GS1-128
$label->fromGS1("(01)999912345678(37)18(3302)987654(3102)123456");

// Create the printer (PDF)
$printer = new Printer();

// Add labels
$printer->addLabel($label);
$printer->addPage();

$label2  = new Label(987654, 555, '777');
$printer->addLabel($label2);

$printer->output();
