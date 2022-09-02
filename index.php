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

require "vendor/autoload.php";

$label              = new Label(12345678);
$label->clientName  = "SGLMS";
$label->clientid    = 9999;
$label->productid   = 12345678;
$label->productName = "Nombre (o lista) de Producto";
$label->generatePDF()->output();
