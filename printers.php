<?php
require __DIR__ . '/vendor/autoload.php';
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

function getListOfSharedPrinters()
{
    // Array to store shared printer names
    $sharedPrinters = [];

    try {
        // Get shared printer names using WMI
        $wmi = new COM("winmgmts:{impersonationLevel=impersonate}//./root/cimv2");
        $query = "SELECT * FROM Win32_Printer WHERE Shared=True";
        $results = $wmi->ExecQuery($query);

        // Extract shared printer names from WMI results
        foreach ($results as $printer) {
            $sharedPrinterName = $printer->ShareName;
            if (!empty($sharedPrinterName)) {
                $sharedPrinters[] = $sharedPrinterName;

                // Output shared printer name to server error log
                error_log("Shared Printer Name: " . $sharedPrinterName);
            }
        }
    } catch (Exception $e) {
        throw new Exception('Failed to get list of shared printers: ' . $e->getMessage());
    }

    return $sharedPrinters;
}

// Get the list of shared printers
$sharedPrinters = getListOfSharedPrinters();

// Return the list of shared printer names as JSON
header('Content-Type: application/json');
echo json_encode($sharedPrinters);
exit;