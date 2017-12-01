<?php
/**
 * Roust
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <oka.kento0311@gmail.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */

use Roust\Router;

/**
 *
 */
class RouterTest extends \PHPUnit\Framework\TestCase{
    
    public $router;
    
    public function setup(){
        $router = new Router();
        
        $router->addShortRegex("d", new Roust\SRegex\NaturalNumber());
        
        $router->addRoute(["GET", "HEAD"], "", [
            "controller"    => "index",
            "action"        => "index"
        ]);
        
        $router->makePathGroup("/users", function($r){
            $r->makeParamsGroup(["controller" => "user"], function($r){
                $r->get("/my/", [
                    "action"    => "mypage"
                ]);

                $r->makePathGroup("/{uid:|d}", function($r){
                    $r->get("/", [
                        "controller" => "users",
                        "action"     => "index",
                        "uid"       => "uid"
                    ]);
                    $r->get("/{page:[1-9][0-9]*}/", [
                        "action"    => "index"
                    ]);
                });
            });
        });
        
        $router->post("/users/{uid:|d}/profile/", [
            "controller"    => "user",
            "action"        => 2,
        ]);
        
        $this->router   = $router;
    }
    
    /**
     * @dataProvider resultDataProvider
     */
    public function testRoutingResult($method, $path, $expected){
        $this->assertEquals($expected, $this->router->search($method, $path)["result"]);
    }
    
    /**
     * @dataProvider allowedDataProvider
     */
    public function testRoutingAllowed($method, $path, $expected){
        $this->assertEquals($expected, $this->router->search($method, $path)["allowed"]);
    }
    
    /**
     * @dataProvider paramsDataProvider
     */
    public function testRoutingPArams($method, $path, $expected){
        $this->assertEquals($expected, $this->router->search($method, $path)["params"]);
    }
    
    
    public function resultDataProvider(){
        return [
            ["GET", "/", Router::FOUND],
            ["GET", "", Router::FOUND],
            ["GET", "/users/my/", Router::FOUND],
            ["GET", "users/123/", Router::FOUND],
            ["GET", "users/123/3/", Router::FOUND],
            ["POST", "/users/456/profile/", Router::FOUND],
            ["POST", "/", Router::METHOD_NOT_ALLOWED],
            ["GET", "/users/789/profile/", Router::METHOD_NOT_ALLOWED],
            ["GET", "/users/0123", Router::NOT_FOUND],
            ["GET", "users/123/1c/", Router::NOT_FOUND],
            ["GET", "users", Router::NOT_FOUND],
            ["GET", "users/456/profile", Router::NOT_FOUND],
        ];
    }
    
    public function allowedDataProvider(){
        return [
            ["GET", "/", ["GET", "HEAD"]],
            ["GET", "", ["GET", "HEAD"]],
            ["GET", "/users/my/", ["GET"]],
            ["GET", "users/123/", ["GET"]],
            ["GET", "users/123/3/", ["GET"]],
            ["POST", "/users/456/profile/", ["POST"]],
            ["POST", "/", ["GET", "HEAD"]],
            ["GET", "/users/789/profile/", ["POST"]],
            ["GET", "/users/0123", []],
            ["GET", "/users/123/1c/", []],
            ["GET", "users", []],
            ["GET", "users/456/profile", []],
        ];
    }
    
    public function paramsDataProvider(){
        return [
            ["GET", "/", [
                "controller"    => "index",
                "action"        => "index"
            ]],
            ["GET", "", [
                "controller"    => "index",
                "action"        => "index"
            ]],
            ["GET", "/users/my/", [
                "controller"    => "user",
                "action"        => "mypage"
            ]],
            ["GET", "users/123/", [
                "controller"    => "users",
                "action"        => "index",
                "uid"           => 123
            ]],
            ["GET", "users/123/3/", [
                "controller"    => "user",
                "action"        => "index",
                "uid"           => 123,
                "page"          => "3"
            ]],
            ["POST", "/users/456/profile/", [
                "controller"    => "user",
                "action"        => "profile",
                "uid"            => 456 
            ]],
            ["POST", "/", []],
            ["GET", "/users/789/profile/", []],
            ["GET", "/users/0123", []],
            ["GET", "users", []],
            ["GET", "users/456/profile", []],
        ];
    }
}