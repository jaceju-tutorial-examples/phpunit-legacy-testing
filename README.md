# Legacy code testing with Codeception

## Init

```bash
composer require "codeception/codeception:~2.0.12"
```

```bash
alias c=./vendor/bin/codecept
```

```bash
c bootstrap
```

[edit] `tests/acceptance.suite.yml`

enable module [`PhpBrowser`](http://codeception.com/docs/modules/PhpBrowser)

```yaml
class_name: AcceptanceTester
modules:
    enabled:
        - PhpBrowser
        - AcceptanceHelper
    config:
        PhpBrowser:
            url: 'http://localhost:9999/'
```

```bash
c build
```

## First Cept test

```bash
c generate:cept acceptance CreatePost
```

```bash
php -S localhost:9999 -t . &
```

```bash
c run acceptance
```

[edit] `tests/acceptance/CreatePostCept.php`

```php
$I = new AcceptanceTester($scenario);
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
```

```bash
c run acceptance
```

## Database Support

dump original database:

```
mysqldump -uroot -p --add-drop-database --databases --skip-comments gbook > tests/_data/dump.sql
```

[edit] `codeception.yml`

```yaml
modules:
    config:
        Db:
            dsn: 'mysql:host=localhost;dbname=gbook'
            user: 'gbook'
            password: 'secret'
            dump: tests/_data/dump.sql
            populate: true # should the dump be loaded before test suite is started.
            cleanup: true # should the dump be reloaded after each test
```

[edit] `tests/acceptance.suite.yml`

enable module [`Db`](http://codeception.com/docs/modules/Db)

```yaml
class_name: AcceptanceTester
modules:
    enabled:
        - PhpBrowser
        - AcceptanceHelper
        - Db
```

```
c build
```

[edit] `tests/acceptance/CreatePostCept.php`

```php
// ...
$I->seeInDatabase('post', [
    'nickname' => $nickname,
    'message'  => $message,
]);
```

## Cest

```bash
c generate:cest acceptance Gbook
```

[edit] `tests/acceptance/GbookCest.php`

```php
use \AcceptanceTester;

class GbookCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToCreatePost(AcceptanceTester $I)
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
}
```

```bash
c run acceptance GbookCest
```

## Other test

[edit] `tests/acceptance/GbookCest.php`

```php
    public function tryToCreateInvalidPost(AcceptanceTester $I)
    {
        $I->wantTo('create an invalid post');
        $I->amOnPage('/');

        $I->click('button[type=submit]');
        $I->seeCurrentUrlEquals('/');
        $I->see("Field 'nickname' is required.");
        $I->see("Field 'message' is required.");
    }
```