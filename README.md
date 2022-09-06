# GS1 Label Printer

Label (with barcodes) generator and printer compatible with GS1-128 and GTIN-14.

We use this library for our own projects. It is not intended for general use; it is specific to our needs (ex. label format and size -10x10-), but it is easy to change/adapt.

## Usage

```php
use Sglms\LabelPrinter\Label;
use Sglms\LabelPrinter\Printer;

define('TMPPATH', '/tmp'); // ¡¡¡IMPORTANT!!!

// Parameters: Item (Pallet) Number, Product Reference Number, Company Prefix
$label              = new Label(12345678, 555, '777');
$label->clientName  = "SGLMS";
$label->productName = "Producto para Demostración";
$label->sku = "PRDCTNN-XX-DS-12345678-123456789000000";

// Get additional product information from GS1-128
$label->fromGS1("(01)999912345678(37)18(3302)987654(3102)123456");

// Create the printer
$printer = new Printer();

// Add labels
$printer->addLabel($label);

$label2  = new Label(987654, 555, '777');
$printer->addLabel($label2);

$printer->output();
```

![barcode](resources/output.png "Generated barcode")

Download the [PDF](resources/output.pdf).
