<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$modules = [
    'auth' => ['Authentication', '`routes/web.php` separates admin (`web`) and shopper (`customer`) guards. One login screen offers both account types; customer registration creates a private cart. Customer login verifies a Turnstile token when configured.', 'The tests create separate admin/customer accounts and fake Cloudflare verification; no actual account is registered outside the test database.', 'External Turnstile is faked; live service and browser sign-in remain untested.'],
    'customers' => ['Customers', '`CustomerController` manages admin customer records; `CustomerProfileController` lets a shopper edit only their own profile. A saved address prefills checkout, while orders retain the address submitted at purchase.', 'Tests create customers and isolated carts, then exercise admin and shopper requests with valid and invalid addresses.', 'Full create/edit/delete validation combinations and large production datasets remain untested.'],
    'products' => ['Products', '`ProductController` publishes the catalogue and restricts edits to admins. It validates image type/size, stores uploads on the public disk and soft-deletes records so historical references remain.', 'Tests create products in SQLite and fake the public filesystem for uploads, replacements and missing-image fallbacks.', 'Real web-server upload and browser image rendering remain untested by this suite.'],
    'carts' => ['Carts', 'Each customer owns one cart. `CustomerCartController` checks items and stock at checkout, creates a pending order and payment, then clears only that customer’s cart in one transaction.', 'Tests set up customers, carts and priced product lines, including insufficient stock and another customer’s cart.', 'Admin cart reassignment/deletion and concurrent browser checkouts are not covered.'],
    'cart-items' => ['Cart Items', '`CartItemController` and `CustomerCartController` validate stock, use catalogue prices and recalculate the cart total. A repeated customer add increases one existing line; duplicate admin lines are rejected.', 'Tests create products with known stock and prices, cart lines, and separate customer identities before sending requests.', 'Concurrent writes under production MySQL are not exercised by SQLite tests.'],
    'orders' => ['Orders', '`OrderController` permits pending → processing → completed and cancellation before completion. `CustomerOrderController` scopes order history to the signed-in shopper; order completion is final.', 'Tests create pending/processing orders, products and payments, then verify allowed and rejected state changes and ownership.', 'All address/date edit combinations and real courier fulfilment remain untested.'],
    'order-items' => ['Order Items', '`OrderItemController` maintains order and payment totals when pending lines change. Paid or non-pending orders have locked lines so reserved stock cannot silently change.', 'Tests create pending orders and product lines, then check create/update/delete totals and attempts to edit reserved lines.', 'No automated real-world fulfilment or returns workflow.'],
    'payments' => ['Payments', '`PaymentController` keeps payment amounts equal to order totals. Marking a payment paid deducts stock once; reversing or deleting it restores stock before completion. Completed paid orders cannot release stock.', 'Tests create orders with priced lines and known stock, then send paid/reversed/invalid payment requests; no gateway is contacted.', 'No real payment gateway or refund processor; paid is entered by an administrator.'],
];

$directory = dirname(__DIR__).'/storage/app/test-reports';

if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
    fwrite(STDERR, "Config is cached; clear it before running isolated tests: php artisan config:clear\n");
    exit(1);
}

if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Could not create {$directory}\n");
    exit(1);
}

$report = [
    '# OLMS module test report',
    '',
    'Generated: '.date('Y-m-d H:i:s T'),
    'Environment: PHP '.PHP_VERSION.'; PHPUnit; SQLite in-memory (`phpunit.xml`). No live MySQL data is used.',
    '',
    '## Reading the results',
    '',
    'These are module-level Laravel feature tests, not isolated unit tests: they send HTTP requests and use a fresh SQLite test database. Run all tests with `composer test` or one module with the command below.',
    '',
    'Each case describes the expected behavior. **Pass** means its assertions observed that behavior; **Fail/Error** means it did not; **Skipped/Blocked** means no conclusion. A passing case does not prove untested behavior works. A case covering two modules may appear in both sections.',
    '',
    '| Module | Passed | Failed/Error | Skipped/Blocked | Result |',
    '| --- | ---: | ---: | ---: | --- |',
];
$sections = [];
$hasFailures = false;

foreach ($modules as $key => [$title, $implementation, $setup, $limits]) {
    $path = "{$directory}/{$key}.xml";
    if (is_file($path)) {
        unlink($path);
    }

    $process = new Process([PHP_BINARY, dirname(__DIR__).'/vendor/bin/phpunit', '--no-progress', '--group=module-'.$key, '--log-junit='.$path], dirname(__DIR__), [
        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => '',
    ]);
    $process->run();

    $cases = [];
    $passed = $failed = $skipped = 0;
    $xml = is_file($path) ? simplexml_load_file($path) : false;

    if ($xml !== false) {
        foreach ($xml->xpath('//testcase') ?: [] as $test) {
            $name = (string) $test['name'];
            $name = preg_replace('/^test_/', '', $name);
            $name = ucfirst(str_replace('_', ' ', $name));
            $problem = $test->failure[0] ?? $test->error[0] ?? null;

            if ($problem !== null) {
                $status = 'Fail/Error';
                $actual = trim((string) ($problem['message'] ?? $problem));
                $failed++;
            } elseif (isset($test->skipped[0])) {
                $status = 'Skipped';
                $actual = trim((string) ($test->skipped[0]['message'] ?? 'Not executed'));
                $skipped++;
            } else {
                $status = 'Pass';
                $assertions = (int) $test['assertions'];
                $source = str_replace('\\', '/', (string) $test['file']);
                $source = substr($source, strlen(str_replace('\\', '/', dirname(__DIR__))) + 1);
                $actual = "{$assertions} assertions passed (`{$source}:{$test['line']}`)";
                $passed++;
            }

            $cases[] = '| '.strtoupper($key).'-'.str_pad((string) count($cases) + 1, 3, '0', STR_PAD_LEFT)
                .' | '.str_replace('|', '\\|', $name).' | '.$status.' | '
                .str_replace(['|', "\r", "\n"], ['\\|', ' ', ' '], $actual).' |';
        }
    }

    if ($cases === []) {
        $skipped++;
        $cases[] = '| '.strtoupper($key).'-001 | No test results were produced | Blocked | Check PHPUnit output below |';
    }

    $hasFailures = $hasFailures || ! $process->isSuccessful() || $failed > 0 || $skipped > 0;
    $report[] = "| {$title} | {$passed} | {$failed} | {$skipped} | ".($failed || $skipped || ! $process->isSuccessful() ? 'Needs attention' : 'Pass').' |';
    $sections[] = "## {$title}\n\n**How it works:** {$implementation}\n\n**Test setup:** {$setup}\n\n**Not checked:** {$limits}\n\n"
        ."| Case | Expected behavior | Status | Actual result / reason |\n| --- | --- | --- | --- |\n"
        .implode("\n", $cases)."\n\n**Run individually:** `composer test:{$key}`\n\n"
        .(! $process->isSuccessful() ? "**Runner output:**\n\n```text\n".trim($process->getOutput().$process->getErrorOutput())."\n```\n" : '');

    echo "{$title}: {$passed} passed, {$failed} failed, {$skipped} skipped/blocked\n";
}

$report[] = '';
$report[] = implode("\n", $sections);
$report[] = '## Follow-up phases';
$report[] = '';
$report[] = 'Integration testing (cross-module workflows and MySQL concurrency) and system testing (browser, real web server, user acceptance) are separate phases. No code coverage percentage is reported without Xdebug/PCOV.';
$report[] = '';

$path = $directory.'/module-report.md';
file_put_contents($path, implode("\n", $report));
echo "Report: {$path}\n";

exit($hasFailures ? 1 : 0);
