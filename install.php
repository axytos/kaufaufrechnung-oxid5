<?php

$moduleDir = __DIR__ . '/../..';
$vendorMetadataPath = __DIR__ . '/../vendormetadata.php';
$vendorMetadataContent = <<<'EOF'
<?php

/**
 * Vendor Metadata
 */

/**
 * Metadata version
 */
$sVendorMetadataVersion = '1.0';
EOF;

if (false === file_put_contents($vendorMetadataPath, $vendorMetadataContent)) {
    exit(1);
}

if (!chdir($moduleDir)) {
    exit(1);
}

exec('composer config repositories.kaufaufrechnung-oxid5 path ./axytos/kaufaufrechnung');

exec('composer require axytos/kaufaufrechnung-oxid5 --no-plugins');
