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

/**
 *
 */
class ParserTest extends \PHPUnit\Framework\TestCase{
    
    /**
     * @dataProvider parseDataProvider
     */
    public function testParse($path, $expected){
        $result = Parser::parse($path);
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @dataProvider splitDataProvider
     */
    public function testSplitSlash($path, $expected){
        $result = Parser::splitSlash($path);
        
        $this->assertEquals($expected, $result);
    }    
    
    public function parseDataProvider(){
        return [
            [
                "/abc/def/ghi/",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::STR, "def"),
                    $this->record(Router::STR, "ghi"),
                    $this->record(Router::STR, "")
                ]
            ],
            [
                "abc/def/ghi/",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::STR, "def"),
                    $this->record(Router::STR, "ghi"),
                    $this->record(Router::STR, "")
                ]
            ],
            [
                "abc/def/ghi.jkl",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::STR, "def"),
                    $this->record(Router::STR, "ghi.jkl")
                ]
            ],
            [
                "abc/{id}",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::REG, Parser::STD_REG, "id")
                ]
            ],
            [
                "abc/{id:\d+}/",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::REG, "\d+", "id"),
                    $this->record(Router::STR, "")
                ]
            ],
            [
                "abc/{id:`d}",
                [
                    $this->record(Router::STR, "abc"),
                    $this->record(Router::SREG, "d", "id")
                ]
            ]
        ];
    }
    
    public function splitDataProvider(){
        return [
            ["/abc/def/ghi/", ["abc", "def", "ghi", ""]],
            ["abc/def/ghi/", ["abc", "def", "ghi", ""]],
            ["abc/def/ghi.jkl", ["abc", "def", "ghi.jkl"]],
            ["abc/123", ["abc", "123"]]
        ];
    }
    
    public function record(string $type, string $val, string $key = null){
        $return = [];
        $return["type"] = $type;
        $return["val"]  = $val;
        if($key !== null){
            $return["key"]  = $key;
        }
        return $return;
    }
    
    public function incrementArr(array $arr){
        $i      = 1;
        $return = [];
        foreach($arr as $value){
            $return[$i++]   = $value;
        }
        return $return;
    }
}