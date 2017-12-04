# Roust

Roustは最も速い(そうであってほしい)URIルーターです。

## インストール

Roustをあなたのプロジェクトで使用する前に、
以下のコマンドをプロジェクト内で実行してください。

``` bash
$ composer require 'kento-oka/roust'
```

## 使い方

まずルーターのインスタンスを生成します。

```php
use Roust\Router;

$router = new Router();
```

### 基本的な使い方

```php
use Roust\Router;
use Request;    //  Implemented Psr\Http\Message\ServerRequestInterface

$router     = new Router();
$request    = new Request();

//  GET http://example.com/　にマッチします
$router->addRoute("GET", "/", [
    "controller"    => "index",
    "action"        => "index"
]);

//  GET http://example.com/users/　にマッチします
$router->addRoute("GET", "/users/", [
    "controller"    => "user",
    "action"        => "index"
]);

//  GET http://example.com/users/my/　と
//  POST http://example.com/users/my/　にマッチします
$router->addRoute(["GET", "POST"], "users/my/", [
    "controller"    => "user",
    "action"        => "mypage"
]);

//  GET http://example.com/users/123/　にマッチします
$router->addRoute("GET", "/users/{uid:[1-9][0-9]*}/", [
    "controller"    => "user",
    "action"        => "page"
]);

$result = $router->search($request->getMethod(), $request->getUri()->getPath());

switch($result["result"]){
    case Router::NOT_FOUND:
        // ... 404 Not Found
        break;
    case Router:METHOD_NOT_ALLOWED:
        $allowedMethods = $result["allowed"];
        // ... 405 Method Not Allowed
        break;
    case Router::FOUND:
        $params = $resul["params"];
        //  何らかの処理
        break;
}
```

### Defining route

ルーティングルールは`Router::addRoute()`で定義します。

第1引数は許可するHTTPメソッド。  
第2引数はマッチするURIのルール。
そして第3引数には追加パラメーターを定義できます。

第2引数の先頭のスラッシュは省略することができます。

```php
//  It matches GET http://example.com/
$router->addRoute("GET", "/", [
    "controller"    => "index",
    "action"        => "index"
]);

//  It matches GET http://example.com/users/
$router->addRoute("GET", "/users/", [
    "controller"    => "user",
    "action"        => "index"
]);

//  It matches GET http://example.com/users/my/ and POST http://example.com/users/my/
$router->addRoute(["GET", "POST"], "users/my/", [
    "controller"    => "user",
    "action"        => "mypage"
]);
```

`Router::get()`, `Router::post()`, `Router::put`, `Router::delete`メソッドは、
HTTPメソッドの指定を省略するために定義されています。

```php
$router->get("/users/", [
    "controller"    => "user",
    "action"        => "index"
]);
```

#### 正規表現の使用

構文`{id:regex}`を使用することで正規表現を埋め込むことができます。
*id* はパラメータ名、*regex* はPHPのpreg_match関数で使用する正規表現を指定します。

```php
$router->addRoute("GET", "/users/{uid:[1-9][0-9]*}/", [
    "controller"    => "user",
    "action"        => "page"
]);
```

もし第3引数で同名のパラメータが指定されている場合は、第2引数の値が優先されます。

#### ShortRegexの使用

IPアドレスにマッチするルーティングルールを追加するのに、
その正規表現を`Router::addRoute()`に記述する人はいないでしょう。
さらにそれがIPv4射影IPv6アドレスだった場合、
ルーティングの時点でIPv4に変換できると便利です。

ShortRegexはそれらを可能にします。

自然数だけに一致しINT型に変換するShortRegexは以下の通りです。

```php
$router->addShortRegex("d", new \Roust\Sregex\NaturalNumber());

$router->addroute("GET", "/users/{uid:|d}/", [
    "controller"    => "user",
    "action"        => "page"
]);
```

ShortRegexは`Router::addShortRegex()`で登録できます。

第1引数は修飾名。  
第2引数は`Roust\ShortRegexInterface`を実装したクラスのインスタンス。

#### グループ

ここまでの例で、いくつかの例に共通するパーツがありました。

user に関連するルールを例にとるとそれらのURIの先頭は必ず */users/*になり、
*controller* の値は *user*になっていました。

前の例程度では共通パーツを記述することはそれほど苦にはなりません。
しかし実際にはもっと多くのルールが必要になります。

Roustではこのようにルールをグループ化できます。

```php
$router->makePathGroup("/users", function($r){
    $r->get("/", [
        "controller"    => "user",
        "action"        => "index"
    ]);

    $r->makeParamsGroup(["Controller" => "user"], function($r){
        $r->get("/my/", [
            "action"    => "mypage"
        ]);

        $r->get("/{uid:[1-9][0-9]*}/", [
            "action"    => "page"
        ]);
    });
});
```

`Router::makePathGroup()`は第2引数のコールバック関数が実行される間だけ、
`Router::addRouter()`のルールの先頭に第1引数の値を追加します。

`Router::makeParamsGroup()`は`Router::addRoute()`で
上書き可能なパラメーターを追加します。

## Note

```php
$route->addShortRegex("d", new NaturalNumber());
$router->get("/users/{id:[1-9][0-9]*}/", []);
$router->get("/users/{id:|d}/profile/", []);

$router->search("GET", "/users/123/");          // Not Found
$router->search("GET", "/users/123/profile");   // Found
```

上の例では`/users/123/`はマッチしません。
これは現バージョンでの仕様ですが、今後修正していきます。

原因はURIの2段目を処理する際に、`{id:|d}`にマッチしてしまい、`{id:[1-9][0-9]*}`
のルールの検証が行われないからです。

検証は、文字列・ShortRegexそして正規表現の順番に行われるため、
現状では似通ったルールはどちらかの記事術方法に統一する必要があります。
