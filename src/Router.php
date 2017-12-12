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
 * @todo    use cache.
 */
class Router{
    
    const FOUND                 = "Found";
    const NOT_FOUND             = "NotFound";
    const METHOD_NOT_ALLOWED    = "MethodNotAllowed";
    
    const TYPE_STR  = "str";
    const TYPE_REG  = "reg";
    const TYPE_SREG = "sreg";
    const TYPE_END  = "end";
    const TYPE_ERR  = "err";
    
    const TYPECONF = [
        self::TYPE_STR  => 1,       //  1: 子を持つノードとして指定できる
        self::TYPE_REG  => 1 | 2,   //  2: パラメーターに登録する
        self::TYPE_SREG => 1 | 2,
        self::TYPE_END  => 16,
        self::TYPE_ERR  => 0
    ];
    
    /**
     * Regex applied to parameters without regex.
     */
    const STD_REG   = "([a-zA-Z0-9.-_~]|%[0-9a-f][0-9a-f]|[!$&-,:;=@])+";
    
    /**
     * Routing tree.
     *
     * @var array[]
     */
    private $routes = [];
    
    /**
     * Group of prefix.
     * 
     * @var string
     */
    private $prefix = "";
    
    /**
     * Group of params.
     * 
     * @var mixed[]
     */
    private $params = [];
    
    /**
     * Sregex list.
     * 
     * @var ShortRegexInterface[]
     */
    private $shortRegex = [];
    
    /**
     * Add new regex shortcut object.
     * 
     * @param   string  $key
     *      Shortcut key.
     * 
     * @param   ShortRegexInterface $sreg
     *      regex shortcut object.
     * 
     * @return  void
     */
    public function addShortRegex(string $key, ShortRegexInterface $sreg){
        $this->shortRegex[$key]   = $sreg;
    }
    
    /**
     * Add new prefix group.
     * 
     * @param   string  $prefix
     *      Prefix string.
     * @param   callable    $callback
     *      Callback function.
     * 
     * @return  void
     */
    public function makePathGroup(string $prefix, callable $callback){
        $prev           = $this->prefix;
        $this->prefix   = $prev . $prefix;

        $callback($this);

        $this->prefix   = $prev;
    }
    
    /**
     * Add new param group.
     * 
     * @param   string[]    $params
     *      Parameters array.
     * @param   callable    $callback
     *      Callback function.
     * 
     * @return  void
     */
    public function makeParamsGroup(array $params, callable $callback){
        $prev           = $this->params;
        $this->params   = array_merge($prev, $params);

        $callback($this);

        $this->params   = $prev;
    }
    
    /**
     * Add routing rule.
     * 
     * @param   string|string[] $methods
     *      Matching method.
     * @param   string  $path
     *      Matching request URI.
     * @param   mixed[] $params
     *      Additional parameters.
     * 
     * @throw   \InvalidArgumentException
     * 
     * @return  void
     */
    public function addRoute($methods, string $path, array $params = []){        
        $records    = self::parse($this->prefix . $path);
        $node       = &$this->routes;
        $end        = [];
        $i          = 0;
        
        foreach($records as $record){
            if(isset(self::TYPECONF[$record[0]])
                && (self::TYPECONF[$record[0]] & 1)
                && isset($record[1])
            ){
                if(!isset($node[$record[0]][$record[1]])){
                    $node[$record[0]][$record[1]]  = [];
                }
                
                $node   = &$node[$record[0]][$record[1]];
                
                if((self::TYPECONF[$record[0]] & 2) && isset($record[2])){
                    $end[$record[2]]    = $i;
                }
                
                ++$i;
            }else{
                throw new \LogicException();
            }
        }
        
        $methods    = array_unique(
            array_filter(
                is_array($methods) ? $methods : [$methods],
                function($v){
                    return is_string($v) && !empty($v);
                }
            )
        );
        
        foreach($methods as $method){
            $node[self::TYPE_END][strtoupper($method)]   = ($end + $params + $this->params);
        }
    }
    
    /**
     * Add routing rule with GET method.
     * 
     * @param   string  $path
     * @param   mixed   $params
     * 
     * @return  void
     */
    public function get(string $path, array $params){
        $this->addRoute("GET", $path, $params);
    }

    /**
     * Add routing rule with POST method.
     * 
     * @param   string  $path
     * @param   mixed   $params
     * 
     * @return  void
     */
    public function post(string $path, array $params){
        $this->addRoute("POST", $path, $params);
    }

    /**
     * Add routing rule with PUT method.
     * 
     * @param   string  $path
     * @param   mixed   $params
     * 
     * @return  void
     */
    public function put(string $path, array $params){
        $this->addRoute("PUT", $path, $params);
    }

    /**
     * Add routing rule with DELETE method.
     * 
     * @param   string  $path
     * @param   mixed   $params
     * 
     * @return  void
     */
    public function delete(string $path, array $params){
        $this->addRoute("DELETE", $path, $params);
    }
    
    /**
     * Search for routing rules that match the request URI.
     *
     * @param   string  $method
     *      Request method.
     * @param   string  $path
     *      Request URI.
     *
     * @return  mixed[]
     */
    public function search(string $method, string $path){
        $records    = self::splitSlash($path);
        $method     = strtoupper($method);
        $node       = $this->routes;
        
        foreach($records as &$record){
            if(isset($node[self::TYPE_STR][$record])){
                $node   = $node[self::TYPE_STR][$record];
                continue;
            }
            
            if(isset($node[self::TYPE_SREG])){
                foreach($node[self::TYPE_SREG] as $key => $next){
                    if(isset($this->shortRegex[$key])){
                        if($this->shortRegex[$key]->match($record)){
                            $record = $this->shortRegex[$key]->convert($record);
                            $node   = $next;
                            continue 2;
                        }
                    }
                }
            }

            if(isset($node[self::TYPE_REG])){
                foreach($node[self::TYPE_REG] as $reg => $next){
                    if((bool)preg_match("`\A$reg\z`", $record)){
                        $node   = $next;
                        continue 2;
                    }
                }
            }
            
            $node   = null;
            break;
        }
        
        if($node !== null){
            if(isset($node[self::TYPE_END][$method])){
                $params = array_map(
                    function($v) use ($records){
                        if(is_int($v)){
                            $v  = $records[$v] ?? null;
                        }

                        return $v;
                    },
                    $node[self::TYPE_END][$method]
                );

                return $this->generateResult(Router::FOUND, array_keys($node[self::TYPE_END]), $params);
            }else if(isset($node[self::TYPE_END]) && count($node[self::TYPE_END] > 0)){
                return $this->generateResult(Router::METHOD_NOT_ALLOWED, array_keys($node[self::TYPE_END]));
            }
        }
        
        return $this->generateResult(Router::NOT_FOUND);
    }
    
    /**
     * Create routing result array.
     * 
     * @param   mixed   $result
     *      Router::FOUND, Router::NOT_FOUND or Router::METHOD_NOT_ALLOWED
     * @param   string  $allowed
     *      Allowed method list.
     * @param   mixed   $params
     *      Routing parameters.
     * 
     * @return  mixed[]
     */
    protected function generateResult($result = self::NOT_FOUND, array $allowed = [], array $params = []){
        return [
            "result"    => $result,
            "allowed"   => $allowed,
            "params"    => $params
        ];
    }
    
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

        if(strpos($str, "{") === 0){
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
        return [Router::TYPE_STR, $str];
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
            "/\A\{(?<key>[a-zA-Z_][a-zA-Z0-9_]*)(?::(?<reg>\|[a-zA-Z_][a-zA-Z0-9_]*|[^|].*?))?\}\z/",
            $str, $match)
        ){
            throw new \InvalidArgumentException("\"$str\" is not regex block.");
        }
        
        if(isset($match["reg"]) && strpos($match["reg"], "|") === 0){
            return [
                Router::TYPE_SREG,
                substr($match["reg"], 1, strlen($match["reg"]) - 1),
                $match["key"]
            ];
        }

        return [
            Router::TYPE_REG,
            $match["reg"] ?? self::STD_REG,
            $match["key"]
        ];
    }
}