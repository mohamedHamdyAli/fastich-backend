<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use charlieuki\ReceiptPrinter\ReceiptPrinter;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Salla\ZATCA\GenerateQrCode;
use Salla\ZATCA\Tags\InvoiceDate;
use Salla\ZATCA\Tags\InvoiceTaxAmount;
use Salla\ZATCA\Tags\InvoiceTotalAmount;
use Salla\ZATCA\Tags\Seller;
use Salla\ZATCA\Tags\TaxNumber;

class PrintController extends Controller
{


    public function qrCode()
    {
        $nameArabic = "شاورما الك";
        $sellerName = "Bobs Basement Records";

        /* Set qr code */
        $QRCODE = (object)array();
        $generatedString = GenerateQrCode::fromArray([
            new Seller($nameArabic), // seller name
            new TaxNumber('100025906700003'), // seller tax number
            new InvoiceDate('2022-04-25T15:30:00Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
            new InvoiceTotalAmount('2100100.99'), // invoice total amount
            new InvoiceTaxAmount('315015.15'), // invoice tax amount
            // TODO :: Support others tags
        ])->toBase64();

        return $generatedString;

    }

    public function printpdf()
    {
        // echo  __DIR__ . '../../../';
        $vendor_path = base_path() . '/vendor';
        require_once $vendor_path . "/mike42/escpos-php/example/print-from-pdf.php";
    }

    public function printhtml($response)
    {
        // return $response->append('transactions')->toArray();
        // s$res = ($response);
        // $res = $response;
        // $array = (array) $res;
        // $prefix = chr(0) . '*' . chr(0);
        // return $return = $array[$prefix . 'content'];

        // return json_decode($response);
        // echo "<pre/>";
        // $obj = $response[0];
        // $id = $obj->id;
        // $business_id = $obj->business_id;
        // $transaction_date = $obj->transaction_date;
        // $location_id = $obj->location_id;
        // $payment_status = $obj->payment_status;
        // $discount_amount = $obj->discount_amount;

        // $printObj = $obj->print;
        // $location_name = $printObj->location_name;
        // $table_unit_price_label = $printObj->table_unit_price_label;
        // $table_subtotal_label = $printObj->table_subtotal_label;
        // $display_name = $printObj->display_name;
        // $address = $printObj->address;
        // $invoice_no_prefix = $printObj->invoice_no_prefix;
        // $invoice_heading = $printObj->invoice_heading;
        // $total_quantity_label = $printObj->total_quantity_label;
        // $invoice_date = $printObj->invoice_date;
        // $subtotal_label = $printObj->subtotal_label;
        // $subtotal = $printObj->subtotal;
        // $total_paid_label = $printObj->total_paid_label;
        // $total_paid = $printObj->total_paid;
        // $header_text = $printObj->header_text;
        // $footer_text = $printObj->footer_text;
        // print_r($response[0]);exit;
        echo "here";
        exit;
        $connector = new NetworkPrintConnector("192.168.8.103", 9100);

        try {
            $items = array(
                new item("Example item #1", "4.00"),
                new item("Another thing", "3.50"),
                new item("Something else", "1.00"),
                new item("A final item", "4.45"),
            );
            $subtotal = new item('Subtotal', '12.95');
            $tax = new item('A local tax', '1.30');
            $total = new item('Total', '14.25', true);
            $date = "Monday 6th of April 2015 02:56:25 PM";

            /* Start the printer */
            $printer = new Printer($connector);
            $printer->selectPrintMode();
            $printer->setPrintLeftMargin(1);
            // Print receipt headers
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->feed();
            $printer->text("-----------------------\n");

            // Print logo
            $logo = EscposImage::load("D:/laravel/receipt-printer-example/vendor/mike42/escpos-php/example/resources/escpos-php.png", false);
            /* Print top logo */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->graphics($logo);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->feed(2);
            $printer->selectPrintMode();
            $printer->text("testing\n");
            $printer->text("-----------------------\n");
            $printer->feed();
            /* Name of shop */
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text("ExampleMart Ltd.\n");
            $printer->selectPrintMode();
            $printer->text("Shop No. 42.\n");
            $printer->feed();

            /* Title of receipt */
            $printer->setEmphasis(true);
            $printer->text("SALES INVOICE\n");
            $printer->setEmphasis(false);

            /* Items */
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(true);
            $printer->text(new item('', '$'));
            $printer->setEmphasis(false);
            foreach ($items as $item) {
                $printer->text($item);
            }
            $printer->setEmphasis(true);
            $printer->text($subtotal);
            $printer->setEmphasis(false);
            $printer->feed();

            /* Tax and total */
            $printer->text($tax);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text($total);
            $printer->selectPrintMode();

            /* Set qr code */
            $QRCODE = (object)array();
            $QRCODE->VAT = "310099050100003";
            $QRCODE->SellerNmae = "Shawerma Elak";
            $QRCODE->timeStamp = "16-08-2022 01:51";
            $QRCODE->timeStamp = "3.00";
            $QRCODE->tax = "0.39";
            $generatedString = GenerateQrCode::fromArray([
                new Seller('Shawerma Elak'), // seller name
                new TaxNumber('310099050100003'), // seller tax number
                new InvoiceDate('2022-08-16T14:25:09Z'), // invoice date as Zulu ISO8601 @see https://en.wikipedia.org/wiki/ISO_8601
                new InvoiceTotalAmount('100.00'), // invoice total amount
                new InvoiceTaxAmount('20.00'), // invoice tax amount
                // TODO :: Support others tags
            ])->toBase64();

            /* Print Qr Code */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->qrCode($generatedString);

            /* Footer */
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Thank you for shopping at ExampleMart\n");
            $printer->text("For trading hours, please visit example.com\n");
            $printer->feed(2);
            $printer->text($date . "\n");

            $printer->release();
            $printer->cut();
            $printer->close();
        } finally {
            $printer->close();
        }

    }

    public function test()
    {
        // Set params
        $mid = '123123456';
        $store_name = 'YOURMART';
        $store_address = 'Mart Address';
        $store_phone = '1234567890';
        $store_email = 'yourmart@email.com';
        $store_website = 'yourmart.com';
        $tax_percentage = 10;
        $transaction_id = 'TX123ABC456';

        // Set items
        $items = [
            [
                'name' => 'French Fries (tera)',
                'qty' => 2,
                'price' => 65000,
            ],
            [
                'name' => 'Roasted Milk Tea (large)',
                'qty' => 1,
                'price' => 24000,
            ],
            [
                'name' => 'Honey Lime (large)',
                'qty' => 3,
                'price' => 10000,
            ],
            [
                'name' => 'Jasmine Tea (grande)',
                'qty' => 3,
                'price' => 8000,
            ],
        ];

        // Init printer
        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        // Set store info
        $printer->setStore($mid, $store_name, $store_address, $store_phone, $store_email, $store_website);

        // Add items
        foreach ($items as $item) {
            $printer->addItem(
                $item['name'],
                $item['qty'],
                $item['price']
            );
        }
        // Set tax
        $printer->setTax($tax_percentage);

        // Calculate total
        $printer->calculateSubTotal();
        $printer->calculateGrandTotal();

        // Set transaction ID
        $printer->setTransactionID($transaction_id);

        // Set qr code
        $printer->setQRcode([
            'tid' => $transaction_id,
        ]);

        // Print receipt
        $printer->printReceipt();
    }
}

class item
{
    private $name;
    private $price;
    private $dollarSign;

    public function __construct($name = '', $price = '', $dollarSign = false)
    {
        $this->name = $name;
        $this->price = $price;
        $this->dollarSign = $dollarSign;
    }

    public function __toString()
    {
        $rightCols = 20;
        $leftCols = 48;
        if ($this->dollarSign) {
            $leftCols = $leftCols / 2 - $rightCols / 2;
        }
        $left = str_pad($this->name, $leftCols);

        $sign = ($this->dollarSign ? '$ ' : '');
        $right = str_pad($sign . $this->price, $rightCols, ' ', STR_PAD_LEFT);
        return "$left$right\n";
    }
}
