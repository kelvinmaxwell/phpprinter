<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

require_once 'vendor/autoload.php';
require_once 'Item.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;
use SimpleSoftwareIO\QrCode\Generator;


    try {

        $qrCodeGenerator = new Generator();
    // Get the print data
    $data = $_POST['print_data'];
    $branch=$_POST['branch'];
    $user=$_POST['user'];
          $qrCodeData = $qrCodeGenerator->size(200)->format('png')->generate('https://karimax-ms.com');

            // Create a temporary file to store the QR code image
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

            // Save the QR code data to the temporary file
            file_put_contents($tmpFilePath, $qrCodeData);

            $totalsh = 0.0;
            $totaltax = 0.0;
            // retreive all records from db
           
           
           

            foreach ($data as $totals) {
            

                $totalsh += $totals['UnitPrice'] * $totals['Quantity'];
                $totaltax+=number_format((($totals['tax'])*($totals['Quantity']*$totals['UnitPrice']))/($totals['tax']+100),2);

            }
            $connector = new FilePrintConnector("/dev/usb/lp0");

            $items = [];;
            foreach ($data as $row) {
                $items[] = new Item($row['Name'], $row['Quantity'], $row['UnitPrice'], number_format((($row['tax'])*($row['Quantity']*$row['UnitPrice']))/($row['tax']+100),2), 'item');

            }
            /* Information for the receipt */

            $list_header = new item('item', 'qty', 'price', 'Tax', 'header');
            $subtotal = new item('Subtotal', $totalsh-$totaltax, '', '', 'totals');
            $tax = new item('A local tax', $totaltax, '', '', 'totals');
            $total = new item('Total', $totalsh, false, '', 'totals');
            /* Date is kept the same for testing */
            $date = date('l jS \of F Y h:i:s A');


            /* Start the printer */

            $printer = new Printer($connector);


            $printer->setJustification(Printer::JUSTIFY_CENTER);
            /* Name of shop */
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text($branch . "\n");
            $printer->selectPrintMode();
            $printer->text("served By " . $user. "\n");
            $printer->feed();

            /* Title of receipt */
            $printer->setEmphasis(true);
            $printer->text("SALES INVOICE\n");
            $printer->setEmphasis(false);

            /* Items */
            $printer->feed();
            $printer->setEmphasis(true);
            $printer->text($list_header);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(false);
            foreach ($items as $item) {
                $printer->text($item);
            }
            $printer->setEmphasis(true);
            $printer->feed(1);
            $printer->text($subtotal);
            $printer->setEmphasis(false);
            $printer->feed();

            /* Tax and total */
            $printer->text($tax);
            $printer->setEmphasis(true);
            $printer->text($total);


            /* Footer */
            $printer->selectPrintMode();
            $printer->feed(2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Thank you for shopping at " .$branch . "\n");
            $printer->text("For more , please visit karimax.com\n");
            $printer->feed(2);
            $printer->text($date . "\n");
            $printer->feed(2);


            $qrCodeData = $qrCodeGenerator->size(200)
                ->format('png')
                ->generate('https://karimax-ms');
            $tmpFilePath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

            // Save the QR code image to the temporary file
            file_put_contents($tmpFilePath, $qrCodeData);

            $image = EscposImage::load($tmpFilePath);
            $printer->bitImage($image);
            /* Cut the receipt and open the cash drawer */
            $printer->cut();
            $printer->pulse();

            $printer->close();

            


           header('Content-Type: application/json');

// Prepare the data to be encoded as JSON
$data = ['success' => true,'message'=>'printing'];

// Convert the data to JSON format
$jsonResponse = json_encode($data);

// Output the JSON response
echo $jsonResponse;
        } catch (Exception $e) {

            header('Content-Type: application/json');

// Prepare the data to be encoded as JSON
$data = ['success' => false];

// Convert the data to JSON format
$jsonResponse = json_encode($data);

// Output the JSON response
echo $jsonResponse;
}

