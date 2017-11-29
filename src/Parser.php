<?php
/**
 * Roust
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <oka.kento0311@gmail.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */
namespace Roust;

/**
 * パスをパースする。
 */
class Parser{
    
    /**
     * 正規表現を省略したパラメータを指定した場合に適用される正規表現。
     */
    const STD_REG   = "([a-zA-Z0-9.-_~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])+";
    
    /**
     * 正規表現などを含むパス定義をパースする。
     * 
     * @param   string  $path
     * 
     * @return  \SplQueue
     */
    public static function parse(string $path){
        $path   = strpos($path, "/") === 0 ? substr($path, 1) : $path;

        $queue  = new \SplQueue();
        $str    = "";
        
        foreach(preg_split("//u", $path, -1, PREG_SPLIT_NO_EMPTY) as $char){
            if($char === "/"){
                if(strpos($str, "{") === 0){
                    $queue->enqueue(static::regRecord($str));
                }else{
                    $queue->enqueue(static::strRecord($str));
                }
                $str    = "";
            }else{
                $str    = $str . $char;
            }
        }

        if(substr($path, strlen($path) - 1) === "/"){
            $queue->enqueue(static::strRecord(""));
        }elseif(strpos($str, "{") === 0){
            $queue->enqueue(static::regRecord($str));
        }else{
            $queue->enqueue(static::strRecord($str));
        }
        
        return $queue;
    }
    
    /**
     * リクエストURLパスをパースする。
     * 
     * @param   string  $path
     * 
     * @return  \SplQueue
     */
    public static function rawParse(string $path){
        $path   = strpos($path, "/") === 0 ? substr($path, 1) : $path;

        $queue  = new \SplQueue();
        $str    = "";
        
        foreach(preg_split("//u", $path, -1, PREG_SPLIT_NO_EMPTY) as $char){
            if($char === "/"){
                $queue->enqueue($str);
                $str    = "";
            }else{
                $str    = $str . $char;
            }
        }

        if(substr($path, strlen($path) - 1) === "/"){
            $queue->enqueue("");
        }else{
            $queue->enqueue($str);
        }
        
        return $queue;
    }
    
    /**
     * 文字列レコードを生成する。
     * 
     * @param   string  $str
     * 
     * @return  mixed[]
     */
    protected static function strRecord(string $str){
        return [
            "type"  => Router::STR,
            "val"   => $str
        ];
    }
    
    /**
     * 正規表現レコードを生成する。
     * 
     * @param   string  $str
     * 
     * @return  mixed[]
     */
    protected static function regRecord(string $str){
        if(!(bool)preg_match(
            "/\A\{(?<key>[a-zA-Z_][a-zA-Z0-9_]*)(?::(?<reg>.+?))?\}\z/",
            $str, $match)
        ){
            return [
                "type"  => Router::ERR,
                "msg"   => "Invalid regex block."
            ];
        }
        
        $reg    = $match["reg"] ?? self::STD_REG;
        
        if((bool)preg_match("/\A`([a-z]+)\z/", $reg, $matchRegs)){
            return [
                "type"  => Router::SREG,
                "val"   => $matchRegs[1],
                "key"   => $match["key"]
            ];
        }else{
            return [
                "type"  => Router::REG,
                "val"   => $reg,
                "key"   => $match["key"]
            ];
        }
    }
}