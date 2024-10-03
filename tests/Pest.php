<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Matfatjoe\ApiBradesco\BankingBradesco;

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function getBankingBradesco($clientKey)
{
    $bankingBradesco = new BankingBradesco([
        'client_key' => $clientKey,
        'endpoints' => BankingBradesco::HOMOLOGATION_ENV,
        'cert_path' => 'tests/AuxFiles/test_cert.pem',
        'cert_pass' => ''
    ]);

    return $bankingBradesco;
}

function mockClientTokenBradescoSuccess()
{
    $handler = new MockHandler();

    $handler->append(
        new Response(
            status: 200,
            body: '{"access_token":"xxxxxxxxxx", "token_type": "Bearer", "expires_in": 1111111}'
        )
    );

    return new Client([
        'handler' => $handler
    ]);
}

function mockClientTokenBradescoFailed()
{
    $queue = [new Response(
        status: 403,
        body: '{"code": "104","message": "invalid signature","details": null}'
    )];
    return new Client([
        'handler' => MockHandler::createWithMiddleware($queue),
    ]);
}
