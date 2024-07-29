<?php

require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\GdEscposImage;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Experimental\Unifont\UnifontPrintBuffer;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Get the request body
$request_body = file_get_contents("php://input");


// Decode the JSON data
$data = json_decode($request_body, true);

// Check if 'body' is present in the data and is a non-empty array
if (isset($data['payload']) && is_array($data['payload']) && !empty($data['payload'])) {
    // Call the handlePrint function with printer and body
    handlePrint($data['printer'], $data['payload'], $data['textSpecial'], $data['key']);
    return
        exit;
} else {
    // Echo an error message if body is missing or empty
    http_response_code(400);
    echo "A non-empty 'payload' array must be provided in the data.";
    exit;
}

function handlePrint($printerSharedName, $data, $textSpecial, $key)
{
    try {
        ob_start();

        $printerName = $printerSharedName ?? 'comerciandoPrinter';
        /* Fill in your own connector here */
        $connector = new WindowsPrintConnector($printerName);

        /* Start the printer */
        $printer = new Printer($connector);

        // Use Unifont to render text
        if ($textSpecial === true) {
            $unifontBuffer = new UnifontPrintBuffer("./unifont.hex");
            $printer->setPrintBuffer($unifontBuffer);
        }

        handlePrintCommands($printer, $data, $key);

        // $printer->close();

        http_response_code(200);

        // Prepare the response object
        $response = array('ok' => true); // Assuming it's successful

        // Convert the response object to JSON
        $json_response = json_encode($response);

        // Set the response header to indicate JSON content type
        header('Content-Type: application/json');

        // Echo the JSON response
        echo $json_response;
        ob_end_flush();
    } catch (Exception $e) {
        // Handle any exceptions that occur during printing
        http_response_code(500);
        echo json_encode(['error' => 'An error ocurred']);
    }
}

/*
 *   @param Printer $printer the printer object
 *   @param array $commands An array of command objects.
 *   Each command object should have 'type' and 'data' fields.
 *  'type' should be a string representing the command type.
 *   'data' should be a string representing the command data (optional).
 */
function handlePrintCommands($printer, $commands, $key)
{
    foreach ($commands as $command) {
        // Skip commands without a type
        if (!isset($command['type'])) {
            continue;
        }

        $type = $command['type'];
        $data = isset($command['payload']) ? $command['payload'] : null;
        $extraData = isset($command['extraData']) ? $command['extraData'] : null;

        switch ($type) {
            case 'commands':
                handlePrintCommands($printer, $data, $key);
                break;
            case 'setFontA':
                $printer->setFont(Printer::FONT_A);
                break;
            case 'setFontB':
                $printer->setFont(Printer::FONT_B);
                break;
            case 'setFontC':
                $printer->setFont(Printer::FONT_C);
                break;
            case 'text':
                $printer->text($data);
                break;
            case 'textAsian':
                $printer->textChinese($data);
                break;
            case 'justify':
                switch ($data) {
                    case 'justifyCenter':
                        $printer->setJustification(Printer::JUSTIFY_CENTER);
                        break;
                    case 'justifyLeft':
                        $printer->setJustification(Printer::JUSTIFY_LEFT);
                        break;
                    case 'justifyRight':
                        $printer->setJustification(Printer::JUSTIFY_RIGHT);
                        break;
                }
                break;
            case 'printBase64Image':
                if ($data !== null) {
                    $base64_image = preg_replace('/^data:image\/(png|jpeg|jpg|gif);base64,/', '', $data);
                    // Decode base64 image
                    $image_data = base64_decode($base64_image);
                    // Create image resource from string
                    $image = @imagecreatefromstring($image_data);
                    if ($image !== false) {
                        $logo = new GdEscposImage();
                        $logo->readImageFromGdResource($image);
                        switch ($extraData) {
                            case 'IMG_DEFAULT':
                                $printer->bitImage($logo, Printer::IMG_DEFAULT);
                                break;
                            case 'IMG_DOUBLE_HEIGHT':
                                $printer->bitImage($logo, Printer::IMG_DOUBLE_HEIGHT);
                                break;
                            case 'IMG_DOUBLE_WIDTH':
                                $printer->bitImage($logo, Printer::IMG_DOUBLE_WIDTH);
                                break;
                            default:
                                $printer->bitImage($logo);
                                break;
                        }
                    }
                }
                break;
            case 'selectPrintMode':
                // Translate data to Printer constant
                switch ($data) {
                    case 'MODE_DOUBLE_WIDTH':
                        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
                        break;
                    case 'MODE_DOUBLE_HEIGHT':
                        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
                        break;
                    case 'MODE_EMPHASIZED':
                        $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
                        break;
                    case 'MODE_FONT_A':
                        $printer->selectPrintMode(Printer::MODE_FONT_A);
                        break;
                    case 'MODE_FONT_B':
                        $printer->selectPrintMode(Printer::MODE_FONT_B);
                        break;
                    case 'MODE_UNDERLINE':
                        $printer->selectPrintMode(Printer::MODE_UNDERLINE);
                        break;
                    default:
                        $printer->selectPrintMode();
                        break;
                }
                break;
            case 'qrCode':
                switch ($data['model']) {
                    case 'QR_MODEL_1':
                        $printer->qrCode($data['content'], Printer::QR_ECLEVEL_L, $data['size'], Printer::QR_MODEL_1);
                        break;
                    case 'QR_MODEL_2':
                        $printer->qrCode($data['content'], Printer::QR_ECLEVEL_L, $data['size'], Printer::QR_MODEL_2);
                        break;
                    case 'QR_MICRO':
                        $printer->qrCode($data['content'], Printer::QR_ECLEVEL_L, $data['size'], Printer::QR_MICRO);
                        break;
                    default:
                        $printer->qrCode($data['content'], Printer::QR_ECLEVEL_L, $data['size'], Printer::QR_MODEL_2);
                        break;
                }
                break;
            case 'barcode':
                switch ($extraData) {
                    case 'BARCODE_UPCA':
                        $printer->barcode($data, Printer::BARCODE_UPCA);
                        break;
                    case 'BARCODE_UPCE':
                        $printer->barcode($data, Printer::BARCODE_UPCE);
                        break;
                    case 'BARCODE_JAN13':
                        $printer->barcode($data, Printer::BARCODE_JAN13);
                        break;
                    case 'BARCODE_JAN8':
                        $printer->barcode($data, Printer::BARCODE_JAN8);
                        break;
                    case 'BARCODE_CODE39':
                        $printer->barcode($data, Printer::BARCODE_CODE39);
                        break;
                    case 'BARCODE_ITF':
                        $printer->barcode($data, Printer::BARCODE_ITF);
                        break;
                    case 'BARCODE_CODABAR':
                        $printer->barcode($data, Printer::BARCODE_CODABAR);
                        break;
                    default:
                        $printer->barcode($data, Printer::BARCODE_CODE39);
                        break;
                }
                break;
            case 'setBarcodeHeight':
                $printer->setBarcodeHeight($data);
                break;
            case 'setBarcodeWidth':
                $printer->setBarcodeWidth($data);
                break;
            case 'setEmphasis':
                // Translate data to Printer constant
                if ($data) {
                    $printer->setEmphasis(true);
                } else {
                    $printer->setEmphasis(false);
                }
                break;
            case 'feed':
                if ($data) {
                    $printer->feed($data);
                } else {
                    $printer->feed();
                }
                break;
            case 'cut':
                $printer->cut();
                break;
            case 'pulse':
                $printer->pulse();
                break;
            /*  case 'close':
                 $printer->close();
                 break; */
            default:
                // Handle unknown command type
                break;
        }
    }
    /* Remember that piracy is bad and more so in small projects */
    /* Don't be stingy and collaborate with the project, thank you :D */
    if (!validateKey($key)) {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(2);
        $printer->setEmphasis(true);
        $printer->text("THANKS FOR USE MY LIBRARY / PLUGIN\n");
        $printer->text("TO REMOVE THIS WATERMARK CONTACT ME\n");
        $printer->text("GITHUB: yayidg22\n");
        $printer->text("GMAIL: yayidg2010@gmail.com\n");
        $printer->text("WEB: escpos-printermanager.netlify.app\n");
        $printer->setEmphasis(false);
        $printer->feed(2);
    }
    $printer->cut();
    $printer->close();
}

function validateKey($key)
{
    if ($key === null) {
        return false;
    }
    try {
        $decoded = JWT::decode($key, new Key("GENERATE_THE_KEY_IS_SO_EASY_LOLÑ", 'HS256'));
        return true;
    } catch (ExpiredException $e) {
        return false;
    }
}