<?php
ini_set('auto_detect_line_endings', true); // Ensures proper line ending detection on different OS

// --- Configuration ---
$csvFile = 'C:/xampp/htdocs/working/07_rename_flatplan/flatplan.csv'; // Path to your flatplan CSV
$sourceDir = 'C:/xampp/htdocs/working/07_rename_flatplan/source_images'; // Directory containing original images (e.g., 01_001.jpg, 01_002.jpg, etc.)
$outputDir = 'C:/xampp/htdocs/working/07_rename_flatplan/renamed_images'; // Directory where renamed images will be saved (e.g., fp_0001.jpg)
$blankImage = 'C:/xampp/htdocs/working/07_rename_flatplan/white.jpg'; // Path to your blank white.jpg image file

// --- Setup Output Directory ---
if (!file_exists($outputDir)) {
    if (!mkdir($outputDir, 0777, true)) {
        die("Error: Failed to create output directory: $outputDir\n");
    }
}

// --- CSV Processing ---
if (($handle = fopen($csvFile, 'r')) !== false) {
    // Skip header row if the CSV has one (e.g., "flatplan番号,ノンブル,固定,連番画像")
    // Uncomment the next line if your CSV includes a header.
    // fgetcsv($handle); 

    while (($row = fgetcsv($handle)) !== false) {
        // Ensure the row has enough columns based on the expected CSV structure
        // flatplan番号 is $row[0], 連番画像 is $row[3]
        if (count($row) < 4) {
            echo "Skipping malformed row (not enough columns): " . implode(',', $row) . "<br>";
            continue;
        }

        $flatplanNumber = trim($row[0]); // Get flatplan番号 (e.g., "001", "002")
        // Remove BOM if present
        $flatplanNumber = preg_replace('/^\xEF\xBB\xBF/', '', $flatplanNumber);
        $imageIdentifier = trim($row[3]); // Get 連番画像 (e.g., "白", "01_001")

        // --- Input Validation for flatplanNumber ---
        // Ensure flatplanNumber is a valid number and not empty
        if (empty($flatplanNumber) || !ctype_digit($flatplanNumber)) {
            echo "Skipping row due to invalid flatplan number: " . implode(',', $row) . "<br>";
            continue;
        }

        // --- Determine Source Path ---
        $srcPath = '';
        if ($imageIdentifier === '白') {
            $srcPath = $blankImage;
        } else {
            // Assumes image identifiers like '01_001' correspond to '01_001.jpg' in sourceDir
            $srcPath = $sourceDir . '/' . $imageIdentifier . '.jpg';
        }

        // --- Determine Destination Path ---
        // New naming convention: fp_0001.jpg, fp_0002.jpg, etc.
        $dstName = sprintf("fp_%04d.jpg", (int)$flatplanNumber);
        $dstPath = $outputDir . '/' . $dstName;

        // --- File Copy Logic ---
        if (!file_exists($srcPath)) {
            echo "ERROR: Source file not found for flatplan #$flatplanNumber ($imageIdentifier): $srcPath<br>";
            echo "----------------------------------------<br>";
            continue; // Skip to the next row if source is missing
        }

        if (copy($srcPath, $dstPath)) {
            echo "Copied: $imageIdentifier → $dstName<br>";
            echo "  (Source: $srcPath)<br>";
            echo "  (Destination: $dstPath)<br>";
            echo "----------------------------------------<br>";
        } else {
            echo "ERROR: Failed to copy $imageIdentifier to $dstName<br>";
            echo "  (Source: $srcPath)<br>";
            echo "  (Destination: $dstPath)<br>";
            echo "----------------------------------------<br>";
        }
    }

    fclose($handle);
    echo "Image renaming process completed.<br>";

} else {
    echo "ERROR: Failed to open CSV file: $csvFile<br>";
}
?>