<?php
/**
 * OBJSIS V2 - Development Test: Update Server Mock
 * 
 * To test the updater, you can host this file locally and point $updateUrl in 
 * includes/updater_helper.php to its URL.
 */

header('Content-Type: application/json');

echo json_encode([
    "version" => "2.1.0",
    "url" => "https://github.com/DenisVargaeu/OBJSIS-V2/archive/refs/tags/v2.1.0.zip",
    "sql" => "",
    "notes" => "Mock Update for Testing:\n- Added test feature\n- UI improvements\n- Bug fixes"
]);
?>