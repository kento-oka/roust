# Roust

Roust is the fastest (wishful) URI router.

> *NOTE*: I cant speak engilish. so I used GoogleTranslate.

## Install

Before using Roust in your project, execute it command in your project:

``` bash
$ composer require 'kento-oka/roust'
```

## Usage

First of all, create an instance of router.

```php
use Roust\Router;

$router = new Router();
```

### Basic usage

```php
use Roust\Router;
use Request;    //  Implemented Psr\Http\Message\ServerRequestInterface

$router     = new Router();
$request    = new Request();

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

//  It matches GET http://example.com/users/123/
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
        //  Do something
        break;
}
```

### Defining route

Routing rules are defined by `Router::addRoute()`.

The first argument is an HTTP method to allow.  
The second argument is a URI that matches.  
And you can specify additional parameters for the third argument. 

You can omit the leading slash in the second argument.

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

Methods such as `Router::get()`, `Router::post()`, `Router::put()`, and
`Router::delete()` are defined to omit specification of the HTTP method.

```php
$router->get("/users/", [
    "controller"    => "user",
    "action"        => "index"
]);
```

#### Use regex

Use the `{id:regex}` syntax to embed regular expression.
*id* specifies the parameter name, *regex* specifies the regular expression
to be used in PHP's preg_match().

```php
$router->addRoute("GET", "/users/{uid:[1-9][0-9]*}/", [
    "controller"    => "user",
    "action"        => "page"
]);
```

Even if the same parameter name is specified with the third argument,
the value of second argument takes precedence.

#### Use short regex

For example, if you add a routing rule that matches IP address,
there would no one to write that regex in `Router::addRoute()`.
Furthermore, if it is IPv4-mapped IPv6 address,
it is useful if it can be converted to IPv4 at the routing stage.

Short regex that makes it possible.

Consider short regex which matches only natural number
and converts parameter to int type.

```php
$router->addShortRegex("d", new \Roust\Sregex\NaturalNumber());

$router->addroute("GET", "/users/{uid:|d}/", [
    "controller"    => "user",
    "action"        => "page"
]);
```

Short regex can be registered with `Router::addShortRegex()`.

The first argument is an qualified name.  
The second argument will pass an instance of the class implementing
`Roust\ShortRegexInterface`.

#### Group

In the example so far, there were parts common to some rules.

Taking the rule related to the user as an example, the head of URI is
neccessarily '*/users/*' and the value of '*controller*' is '*user*'.

It is not hard to write common parts with the previous example.
But Actually more rules are needed.

In Roust you can summarize the grouping of rules in this way:

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

`Router::makePathGroup()` adds value of the first argument to the 
beginning of the URI of the rule added with `Router::addRoute()` only while
the second argument callback is executed.

In `Router::makePAramsGroup()`, add parameters.
This added parameters can be overwriten with `Router::addRoute()`.

## Note

```php
$route->addShortRegex("d", new NaturalNumber());
$router->get("/users/{id:[1-9][0-9]*}/", []);
$router->get("/users/{id:|d}/profile/", []);

$router->search("GET", "/users/123/");          // Not Found
$router->search("GET", "/users/123/profile");   // Found
```

String > Short Regex > Regex