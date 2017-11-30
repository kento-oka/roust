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
use Roust\Parser;
use Roust\Result;

/**
 *
 */
class RouterTest extends \PHPUnit\Framework\TestCase{
    
    public $router;
    
    public function setup(){
        $router = new Router();
        
        $router->addRoute(["GET", "HEAD"], "", [
            "controller"    => "index",
            "action"        => "index"
        ]);
        
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
                    "controller"    => "users",
                    "action"        => "page"
                ]);
            });
        });
        
        $router->post("/users/{id:[1-9][0-9]*}/profile/", [
            "controller"    => "user",
            "action"        => 3
        ]);
        
        $this->router   = $router;
    }
    
    /**
     * @dataProvider resultDataProvider
     */
    public function testRoutingResult($method, $path, $expected){
        $this->assertEquals($expected, $this->router->search($method, $path)->getResult());
    }
    
    public function resultDataProvider(){
        return [
            ["GET", "/", Result::FOUND],
            ["GET", "", Result::FOUND],
            ["GET", "users/", Result::FOUND],
            ["GET", "/users/my/", Result::FOUND],
            ["GET", "users/123/", Result::FOUND],
            ["POST", "/users/456/profile/", Result::FOUND],
            ["POST", "/", Result::METHOD_NOT_ALLOWED],
            ["GET", "/users/789/profile/", Result::METHOD_NOT_ALLOWED],
            ["GET", "/users/0123", Result::NOT_FOUND],
            ["GET", "users", Result::NOT_FOUND],
            ["GET", "users/456/profile", Result::NOT_FOUND],
        ];
    }
}