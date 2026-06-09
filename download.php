<?php

// Whitelist of allowed files
$allowed_files = [
    // Lead Magnet
    'lead_magnet'          => 'PTComm_5_Scripts_pack.pdf',
    'lead_magnet_mobile'   => 'PTComm_5_Scripts_pack_mobile.pdf',
    
    // Tripwire Desk Kit
    'desk_kit'             => 'PTComm_Printable_Desk_Kit.pdf',
    'desk_kit_mobile'      => 'PTComm_Printable_Desk_Kit_mobile.pdf',
    
    // Legacy fallbacks
    'scripts'              => '5_Scripts_for_Hard_Conversations.pdf',
    'hep'                  => 'HEP_Adherence_Cheat_Sheet.pdf'
];

// Check if ?file= parameter exists
if (!isset($_GET['file'])) {
    echo "No file specified.";
    exit;
}

$key = $_GET['file'];

// Validate file key
if (!array_key_exists($key, $allowed_files)) {
    echo "Invalid file.";
    exit;
}

$filepath = 'private/' . $allowed_files[$key];

// Serve file
if (file_exists($filepath)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $allowed_files[$key] . '"');
    readfile($filepath);
    exit;
} else {
    http_response_code(404);
    echo "File not found.";
    exit;
}
?>