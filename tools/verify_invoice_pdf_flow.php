<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Models\ClientInvoice;
use App\Models\InvoiceSetting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$invoice = ClientInvoice::query()->with(['client', 'project'])->latest('id')->first();
if (!$invoice) {
    fwrite(STDERR, "No invoice found to validate.\n");
    exit(1);
}

$admin = User::query()
    ->whereIn('role', ['owner', 'admin', 'manager'])
    ->orderByDesc('id')
    ->first();

if (!$admin) {
    fwrite(STDERR, "No admin/owner/manager user found for controller access checks.\n");
    exit(1);
}

$controller = app(DashboardController::class);
$settings = InvoiceSetting::query()->firstOrCreate([], [
    'include_tax_on_pdf' => false,
    'tax_rate_percent' => 0,
]);

$makeRequest = static function (User $user): Request {
    $request = Request::create('/', 'GET');
    $request->setUserResolver(static fn (): User => $user);
    return $request;
};

$runDownloadCheck = static function (string $label, $response): void {
    $contentType = (string) $response->headers->get('content-type', '');
    $disposition = (string) $response->headers->get('content-disposition', '');

    if (stripos($contentType, 'application/pdf') === false) {
        throw new RuntimeException($label . ': expected PDF content-type, got ' . $contentType);
    }

    if (stripos($disposition, 'attachment;') === false) {
        throw new RuntimeException($label . ': expected attachment download disposition.');
    }

    echo $label . " download response OK\n";
};

$renderAndCheckTax = static function (string $view, array $data, bool $shouldContainTax, string $label): void {
    $html = view($view, $data)->render();
    $hasTaxLabel = str_contains($html, 'Tax (');

    if ($hasTaxLabel !== $shouldContainTax) {
        throw new RuntimeException($label . ': tax row expectation failed.');
    }

    echo $label . ' tax row ' . ($hasTaxLabel ? 'present' : 'hidden') . "\n";
};

$invoice->refresh()->loadMissing(['client', 'project']);

// Tax ON
$settings->include_tax_on_pdf = true;
$settings->tax_rate_percent = 13.0;
$settings->save();

$subtotal = round((float) $invoice->amount, 2);
$taxAmount = round(($subtotal * 13.0) / 100, 2);
$total = round($subtotal + $taxAmount, 2);

$adminResponse = $controller->adminInvoicePdfDownload($makeRequest($admin), $invoice);
$userResponse = $controller->userInvoicePdfDownload($makeRequest($admin), $invoice);

$runDownloadCheck('Admin (tax on)', $adminResponse);
$runDownloadCheck('User (tax on)', $userResponse);

$baseData = [
    'invoice' => $invoice,
    'client' => $invoice->client,
    'project' => $invoice->project,
    'subtotal' => $subtotal,
    'includeTax' => true,
    'taxRate' => 13.0,
    'taxAmount' => $taxAmount,
    'total' => $total,
    'brandName' => 'Maccento Real Estate Media',
    'brandPhone' => '+1 (514) 951-9141',
    'brandEmail' => (string) config('mail.from.address', 'info@maccento.ca'),
];

$renderAndCheckTax('admin.pdf.invoice', $baseData, true, 'Admin template (tax on)');
$renderAndCheckTax('user.pdf.invoice', $baseData, true, 'User template (tax on)');

// Tax OFF
$settings->include_tax_on_pdf = false;
$settings->tax_rate_percent = 13.0;
$settings->save();

$adminResponseOff = $controller->adminInvoicePdfDownload($makeRequest($admin), $invoice);
$userResponseOff = $controller->userInvoicePdfDownload($makeRequest($admin), $invoice);

$runDownloadCheck('Admin (tax off)', $adminResponseOff);
$runDownloadCheck('User (tax off)', $userResponseOff);

$baseData['includeTax'] = false;
$baseData['taxAmount'] = 0.0;
$baseData['total'] = $subtotal;

$renderAndCheckTax('admin.pdf.invoice', $baseData, false, 'Admin template (tax off)');
$renderAndCheckTax('user.pdf.invoice', $baseData, false, 'User template (tax off)');

echo "Invoice PDF validation completed successfully.\n";
