<?php
$I = new FunctionalTester($scenario);
$I->wantTo('create post');
$I->amOnPage('/');

$rand = rand(1, 9999);
$nickname = 'Nickname ' . $rand;
$message  = 'Message ' . $rand;

$I->fillField("//input[@name='nickname']", $nickname);
$I->fillField(['name' => 'message'], $message);
$I->click('button[type=submit]');

$I->seeCurrentUrlEquals('/');
$I->see($message);
$I->seeInDatabase('post', [
    'nickname' => $nickname,
    'message'  => $message,
]);
