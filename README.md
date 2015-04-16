# Legacy code testing with Codeception

* 舊有程式最難搞
* 無法單元測試
 1. 邏輯混雜
 2. 必須依賴伺服器環境

## Legacy 專案功能嗅探

有時候 Legacy 專案沒有功能規格說明，必須直接從瀏覽器確認。

所以先在背景啟動測試用的 Web 伺服器：

```bash
php -S localhost:9999 -t . &
```

實際操作留言板後，我們得知使用者可以：

1. 建立一個留言
2. 留言未正確填寫時會有錯誤訊息

查看 `index.php` 後，也可以得到以下資訊：

* 單一 php 檔案，混雜 PHP 程式碼和 HTML
* 使用 PDO 操作 sqlite 資料庫

## Codeception 介紹

Codeception 支援了以下數種測行模型：

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

### 為什麼用 Codeception

Codeception 可以在某種程度上解決 legacy code 測試上的困難，我們可以在測試機上執行 legacy code ，然後以 Codeception 以模擬瀏覽器的方式來存取輸出結果並測試功能流程。

```
Legacy Code <-> Testing Web Server <-> PhpBrowser <-> Codeception
```

接下來我們會用 Codeception 驗證舊專案的功能流程以及內部細節。

因為是功能測試，所以相關操作都會是與 `functional` 這個字有關的設定或指令。

## 初始化專案

在專案根目錄執行以下指令：

```bash
composer require "codeception/codeception:~2.0.12"
```

建立指令別名方便測試：

```bash
alias c=./vendor/bin/codecept
```

在專案中初始化 Codeception ：

```bash
c bootstrap
```

* `bootstrap` 會建立 `tests` 資料夾及必要的測試設定檔與類別檔

啟用 [`PhpBrowser`](http://codeception.com/docs/modules/PhpBrowser) 模組，讓 Codeception 可以接上測試用的伺服器。

編輯 `tests/functional.suite.yml` ：

```yaml
class_name: FunctionalTester
modules:
    enabled: [Filesystem, FunctionalHelper, PhpBrowser]
    config:
        PhpBrowser:
            url: 'http://localhost:9999/'
```

* PhpBrowser 模組可以模擬一個瀏覽器，連線到指定的伺服器，並用程式介面來操作
* `config` 中可以定義每個模組需要的設定值

當引用模組後，需要用 `build` 指令重新產生 `FunctionalTester` 類別：

```bash
c build
```

## 功能一：建立一個留言

Codeception 可以協助我們自動產生測試用的規格檔，有 `Cept` 與 `Cest` 兩種類型。

先從 `Cept` 類型的測試開始，執行指令：

```bash
c generate:cept functional CreatePost
```

這樣會在 `tests/functional` 目錄下建立一個 `CreatePostCept.php` 檔案。

然後要在 `CreatePostCept.php` 撰寫功能流程。

編輯 `tests/functional/CreatePostCept.php` ：

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

最後以 `run` 指令來執行測試，後面可以指定要執行的測試模式：

```bash
c run functional
```

當通過測試後，程式碼就有了基本的保障。接下來如果要改版或重構，就可以用這個測試去擴充或驗證。

## 加入資料庫支援

到這邊其實算驗收測試，因為我們不知道伺服器端發生什麼變化，一般會驗證資料庫中的內容來確認動作有正確被執行。

但因為 Legacy Code 是由測試伺服器執行，而非在 Codeception 的 process 中，所以無法直接得知資料庫中的狀況，因此要靠 `Db` 模組來連接測試用的資料庫。

```
Legacy Code <-> Testing Database Server <-> Db <-> Codeception
```

### 測試用資料庫

在測試時，我們希望測試資料庫是可以被重複使用的。因為 Legacy code 是使用 sqlite ，因此可以透過 dump 線上資料庫來做測試。

執行：

```
sqlite3 db.sqlite
```

進入 sqlite 的操作面後，輸入：

```
sqlite> .output dump.sql
sqlite> .dump post
sqlite> .exit
```

就能把目前的資料庫的 schema 輸出到 `dump.sql` 。

接著要在 Global 設定檔中設定好 `Db` 模組。

編輯 `codeception.yml` ：

```yaml
modules:
    config:
        Db:
            dsn: 'mysql:host=localhost;dbname=gbook'
            user: 'gbook'
            password: 'secret'
            dump: dump.sql
            populate: true # should the dump be loaded before test suite is started.
            cleanup: true # should the dump be reloaded after each test
```

* `populate` ：每次測試前要重建資料庫 (匯入 dump 檔)
* `cleanup` ：每次測試後要還原資料庫 (匯入 dump 檔)
* 視狀況決定要用 `populate` 還是 `cleanup` ；如果是測試用資料庫就無所謂，可以兩個都設為 `true`

並且啟用 [`Db`](http://codeception.com/docs/modules/Db) 模組。

編輯 `tests/functional.suite.yml` ：

```yaml
class_name: FunctionalTester
modules:
    enabled: [Filesystem, FunctionalHelper, PhpBrowser, Db]
```

要記得重新產生 `FunctionalTester` ：

```
c build
```

然後利用 `Db` 模組的 `seeInDatabase` 來確認是否有真的寫入資料庫。

編輯 `tests/functional/CreatePostCept.php` ：

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

Cest 是類別寫法的測試，每個函式都是一個測試，通常是需要以下狀況時使用：

* 需要有前後置作業 (`_before` / `_after`)
* 需要有共用的 fixtrues

在 `generate` 時改用 `cest` ：

```bash
c generate:cest functional Gbook
```

Cest 測試的寫法和 Cept 是差不多的，通常直接把 `*Cept.php` 的內容 (不含 `$I = new FunctionalTester($scenario);`) 複製到 `*Cest` 類別的方法裡就可以了。

編輯 `tests/functional/GbookCest.php` ：

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
* 可以刪除 `tests/functional/CreatePostCept.php`

只執行 `GbookCest` 類別裡的測試：

```bash
c run functional GbookCest
```

## 功能二：留言未正確填寫時會有錯誤訊息

在沒有對欄位輸入任何值即送出表單的話，應該會有錯誤訊息產生。

編輯 `tests/functional/GbookCest.php` ：

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

* 一般會直接從瀏覽器畫面來得知錯誤訊息
* 有時需要從程式碼得知錯誤訊息
