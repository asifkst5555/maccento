<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$source = __DIR__ . '/../resources/docs/client-system-overview.html';
$targetDir = __DIR__ . '/../public/docs';
$target = $targetDir . '/Maccento-System-Overview.pdf';

if (!is_file($source)) {
    fwrite(STDERR, "Source HTML not found: {$source}\n");
    exit(1);
}

if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Unable to create target directory: {$targetDir}\n");
    exit(1);
}

$html = file_get_contents($source);
if ($html === false) {
    fwrite(STDERR, "Unable to read source HTML.\n");
    exit(1);
}

$dompdf = new Dompdf\Dompdf([
    'isRemoteEnabled' => false,
]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();

if (file_put_contents($target, $dompdf->output()) === false) {
    fwrite(STDERR, "Unable to write PDF: {$target}\n");
    exit(1);
}

fwrite(STDOUT, "PDF generated: {$target}\n");
