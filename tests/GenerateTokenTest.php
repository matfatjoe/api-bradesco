<?php

use Matfatjoe\ApiBradesco\Exceptions\ForbiddenException;
use Matfatjoe\ApiBradesco\Exceptions\InvalidRequestException;

it("deve gerar um JWT com informações corretas", function () {
    $bankingBradesco = getBankingBradesco('123');

    $result = $bankingBradesco->generateJWT();
    $expected = file_get_contents('tests/AuxFiles/base64JWT.txt');
    expect($result)->toBe($expected);
});

it("deve gerar um JWT com informações incorretas", function () {
    $bankingBradesco = getBankingBradesco('111');

    $result = $bankingBradesco->generateJWT();
    $expected = file_get_contents('tests/AuxFiles/base64JWT.txt');
    expect($result)->not->toBe($expected);
});

it("deve gerar um hash assinado correto", function () {
    $bankingBradesco = getBankingBradesco('123');
    $jwtContent = file_get_contents('tests/AuxFiles/base64JWT.txt');
    $result = $bankingBradesco->createSignedHash($jwtContent);
    $expected = file_get_contents('tests/AuxFiles/base64SignedHash.txt');
    expect($result)->toBe($expected);
});

it("deve gerar um hash assinado incorreto passando um JWT incorreto", function () {
    $bankingBradesco = getBankingBradesco('123');
    $result = $bankingBradesco->createSignedHash(base64_encode('123'));
    $expected = file_get_contents('tests/AuxFiles/base64SignedHash.txt');
    expect($result)->not->toBe($expected);
});

it("deve retornar setar o token de acesso ao tentar gerar o token", function () {
    $bankingBradesco = getBankingBradesco('123');
    $clientToken = mockClientTokenBradescoSuccess();
    $bankingBradesco->setClientToken($clientToken);
    $bankingBradesco->gerarToken();
    $result = $bankingBradesco->getToken();
    $expected = 'xxxxxxxxxx';
    expect($result)->toBe($expected);
});

it("deve retornar levantar uma ForbiddenException ao tentar gerar o token", function () {
    $bankingBradesco = getBankingBradesco('123');
    $clientToken = mockClientTokenBradescoFailed();
    $bankingBradesco->setClientToken($clientToken);
    $bankingBradesco->gerarToken();
})->throws(ForbiddenException::class, 'Chave de acesso inválida');
