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
 */
class Parser{
    
    /**
     * Regex applied to parameters without regex.
     */
    const STD_REG   = "([a-zA-Z0-9.-_~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])+";
    
    /**
     * Parse URI definitions containing regex.
     * 
     * @param   string  $path
     * 
     * @return  array[]
     */
    public static function parse(string $path){
        $path   = strpos($path, "/") === 0 ? substr($path, 1) : $path;
        $return = [];
        $str    = "";
        $i      = 0;
        
        foreach(str_split($path) as $char){
            if($char === "/"){
                if(strpos($str, "{") === 0){
                    $return[$i++]   = static::regRecord($str);
                }else{
                    $return[$i++]   = static::strRecord($str);
                }
                $str    = "";
            }else{
                $str    = $str . $char;
            }
        }

        if($char === "/"){
            $return[$i]   = static::strRecord("");
        }elseif(strpos($str, "{") === 0){
            $return[$i]   = static::regRecord($str);
        }else{
            $return[$i]   = static::strRecord($str);
        }
        
        return $return;
    }
    
    /**
     * Parse the request URI.
     * 
     * @param   string  $path
     * 
     * @return  array[]
     */
    public static function splitSlash(string $path){
        $path   = strpos($path, "/") === 0 ? substr($path, 1) : $path;
        $return = [];
        $str    = "";
        $i      = 0;
        
        foreach(str_split($path) as $char){
            if($char === "/"){
                $return[$i++]   = $str;
                $str            = "";
            }else{
                $str    = $str . $char;
            }
        }
        
        $return[$i] = $char === "/" ? "" : $str;
        
        return $return;
    }
    
    /**
     * Generate string record.
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
     * Generate regex record.
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
        
        if((bool)preg_match("/\A\|([a-z]+)\z/", $reg, $matchRegs)){
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