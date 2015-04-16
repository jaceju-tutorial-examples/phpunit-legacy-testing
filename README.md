# Legacy code testing with Codeception

* 舊有程式最難搞
* 無法單元測試
 1. 邏輯混雜
 2. 必須依賴伺服器環境

## 測試種類

* 驗收測試
* 功能測試
* 單元測試

### 驗收測試 (Acceptance Testing)

想像一下用戶們是怎麼使用你的程式？他們既看不到你用的程式框架，也不知道資料儲存在哪裡，他們只能打開瀏覽器，依照 (也可能不遵守) 程式的介面去操作。

驗收測試其實就是從用戶的角度來測試我們的程式，不管內部運作再複雜，驗收測試關心的只是功能正不正確，結果是不是用戶所預期的而已。因為可能需要用到實際的瀏覽器和資料庫，所以驗收測試的速度會慢一些；但是也因為運行在真實的環境，所以它能夠測試瀏覽器才有辦法達到的行為，例如 JavaScript 等。

### 功能測試 (Functional Testing)

如何快速且不依賴伺服器環境，而能夠測試我們程式的行為？這正是功能測試的重點。功能測試提供我們有關 Web 環境、資料庫系統的模擬，讓它們能夠回饋我們程式測試的結果。也因為環境是模擬的關係，功能測試只能著重在程式內部的運作邏輯，會使得程式在實際運作時可能有其他問題發生。

### 單元測試 (Unit Testing)

單元測試是用來測試一些可被獨立測試的方法或函式，它們通常功能單純，不需要跟其他功能聯合起來透過某些一連串的特定行為來產生有用的結果。因為它們有著需要獨立測試的特性，所以適合用來測試框架核心或是函式庫。然而單元與單元之間不能有關連性，因此也無法得知彼此之間是否有所影響，必須依賴良好的介面設計。

## Legacy 專案說明

* 主要功能為留言板
* 輸入暱稱和留言訊息後就可以留言
* 沒有輸入內容的話會提示錯誤訊息
* 單一 php 檔案，混雜 PHP 程式碼和 HTML
* 使用 PDO 操作資料庫

## 初始化專案

* Codeception 可以在某種程度上解決 legacy code 的功能測試或驗收測試

```bash
composer require "codeception/codeception:~2.0.12"
```

```bash
alias c=./vendor/bin/codecept
```

* 方便後續指令操作

```bash
c bootstrap
```

* `bootstrap` 會建立 `tests` 資料夾及必要的測試設定檔與類別檔

啟用 [`PhpBrowser`](http://codeception.com/docs/modules/PhpBrowser) 模組

編輯 `tests/functional.suite.yml`

```yaml
class_name: FunctionalTester
modules:
    enabled: [Filesystem, FunctionalHelper, PhpBrowser]
    config:
        PhpBrowser:
            url: 'http://localhost:9999/'
```

* PhpBrowser 模組可以模擬一個瀏覽器，連線到指定的伺服器，並用程式介面來操作

```bash
c build
```

* 每次新增模組後都要重新 `build`

```bash
php -S localhost:9999 -t . &
```

* 需要啟動測試用的伺服器，也可以是外部網址

## 確認功能

* 如果沒有功能說明，就必須直接從瀏覽器確認
* 使用者可以：
 1. 建立一個留言
 2. 留言未正確填寫時會有錯誤訊息

## 功能一：建立一個留言

```bash
c generate:cept functional CreatePost
```

```bash
c run functional
```

編輯 `tests/functional/CreatePostCept.php`

```php
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
```

* `amOnPage` ：瀏覽指定的相對網址
* `fillField` ：在指定輸入欄位中填值，可以用 CSS selector / XPath / Label 等來指定欄位
* `click` ：按下指定的按鈕或連結
* `seeCurrentUrlEquals` ：預期接下來會導向的網址
* `see` ：在目前的畫面會看到什麼文字
* 其他方法可參考： [PhpBrowser](http://codeception.com/docs/modules/PhpBrowser)

```bash
c run functional
```

* 到這邊其實算驗收測試，因為我們不知道伺服器端發生什麼變化
* 一般會驗證資料庫中的內容來確認動作有正確被執行

## 加入資料庫支援

* 建立一個可以被完整重建的測試資料庫
* 可以透過 dump 線上資料庫來做測試

```
mysqldump -uroot -p --add-drop-database --databases --skip-comments gbook > tests/_data/dump.sql
```

編輯 `codeception.yml`

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

* `populate` ：每次測試前要重建資料庫 (匯入 dump 檔)
* `cleanup` ：每次測試後要還原資料庫 (匯入 dump 檔)
* 視狀況決定要用 `populate` 還是 `cleanup` ；如果是測試用資料庫就無所謂，可以兩個都設為 `true`

啟用 [`Db`](http://codeception.com/docs/modules/Db) 模組

編輯 `tests/functional.suite.yml`

```yaml
class_name: FunctionalTester
modules:
    enabled: [Filesystem, FunctionalHelper, PhpBrowser, Db]
```

```
c build
```

編輯 `tests/functional/CreatePostCept.php`

```php
// ...
$I->seeInDatabase('post', [
    'nickname' => $nickname,
    'message'  => $message,
]);
```

* `seeInDatabase` ：確認資料有存在於指定的資料表中

```bash
c run functional
```

## 建立 Cest 類型的測試

* 類別式的測試
* 需要有前後置作業 (`_before` / `_after`)
* 需要有共用的 fixtrues

```bash
c generate:cest functional Gbook
```

編輯 `tests/functional/GbookCest.php`

```php
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
```

* 將 `tests/functional/CreatePostCept.php` 的內容複製到 `tryToCreatePost` 方法中

```bash
c run functional GbookCest
```

* 可以刪除 `tests/functional/CreatePostCept.php`

## 功能二：留言未正確填寫時會有錯誤訊息

編輯 `tests/functional/GbookCest.php`

```php
    public function tryToCreateInvalidPost(FunctionalTester $I)
    {
        $I->wantTo('create an invalid post');
        $I->amOnPage('/');

        $I->click('button[type=submit]');
        $I->seeCurrentUrlEquals('/');
        $I->see("Field 'nickname' is required.");
        $I->see("Field 'message' is required.");
    }
```

* 一般會直接從瀏覽器畫面來得知有什麼結果
* 有時需要從程式碼觀察功能可能會出現的資訊
