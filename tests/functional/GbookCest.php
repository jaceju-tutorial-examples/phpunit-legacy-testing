<?php
use \FunctionalTester;

class GbookCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryToCreatePost(FunctionalTester $I)
    {
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
    }

    public function tryToCreateInvalidPost(FunctionalTester $I)
    {
        $I->wantTo('create an invalid post');
        $I->amOnPage('/');

        $I->click('button[type=submit]');
        $I->seeCurrentUrlEquals('/');
        $I->see("Field 'nickname' is required.");
        $I->see("Field 'message' is required.");
    }
}
