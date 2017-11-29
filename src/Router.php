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
 * URIルーティングのメインクラス。
 * 
 * @todo    use cache.
 */
class Router{
    
    const STR   = "str";
    const REG   = "reg";
    const SREG  = "sreg";
    const END   = "end";
    const ERR   = "err";
    
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
     * Add new params group.
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
    public function addRoute(string $methods, string $path, array $params = []){
        $methods    = array_unique(
            array_filter(
                is_array($methods) ? $methods : [$methods],
                function($v){
                    return is_string($v) && !empty($v);
                }
            )
        );
        
        if(empty($methods)){
            throw new \InvalidArguentException("");
        }
        
        $records    = Parser::parse($this->prefix . $path);
        $node       = &$this->routes;
        $end        = [];

        for($i = 1; !$records->isEmpty(); ++$i){
            $record = $records->dequeue();

            switch($record["type"]){
                case self::STR:
                    if(!isset($node[self::STR])){
                        $node[self::STR]    = [];
                    }

                    if(!isset($node[self::STR][$record["val"]])){
                        $node[self::STR][$record["val"]]    = [];
                    }

                    $node   = &$node[self::STR][$record["val"]];

                    break;
                case self::SREG:
                    if(!isset($node[self::SREG])){
                        $node[self::SREG]   = [];
                    }

                    if(!isset($node[self::SREG][$record["val"]])){
                        $node[self::SREG][$record["val"]]   = [];
                    }

                    $node   = &$node[self::SREG][$record["val"]];

                    $end[$record["key"]]    = $i;
                    break;
                case self::REG:
                    if(!isset($node[self::REG])){
                        $node[self::REG]    = [];
                    }

                    if(!isset($node[self::REG][$record["val"]])){
                        $node[self::REG][$record["val"]]    = [];
                    }

                    $node   = &$node[self::REG][$record["val"]];

                    $end[$record["key"]]    = $i;
                    break;
                case self::ERR:
                default:
                    throw new \LogicException;
            }
        }

        if(!isset($node[self::END])){
            $node[self::END]    = [];
        }
        
        foreach($methods as $method){
            $node[self::END][strtoupper($method)]   = ($end + $params + $this->params);
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
     * @throws  InvalidArgumentException
     *
     * @return  string[]|null
     *      If the routing is successful, the parameter list will be returned,
     *      otherwise null will be returned.
     */
    public function search(string $method, string $path): Result{
        $records    = Parser::rawParse($path);
        $method     = strtoupper($method);
        $node       = $this->routes;
        $uri        = [];
        $i          = 0;
        
        while(!$records->isEmpty()){
            $uri[++$i]  = $records->dequeue();
            
            if(isset($node[self::STR][$uri[$i]])){
                $node   = $node[self::STR][$uri[$i]];
                continue;
            }
            
            if(isset($node[self::SREG])){
                foreach($node[self::SREG] as $key => $next){
                    if(isset($this->shortRegex[$key])){
                        if($this->shortRegex[$key]->match($uri[$i])){
                            $uri[$i]    = $this->shortRegex[$key]->convert($uri[$i]);
                            $node       = $next;
                            continue 2;
                        }
                    }
                }
            }

            if(isset($node[self::REG])){
                foreach($node[self::REG] as $reg => $next){
                    if((bool)preg_match("`\A$reg\z`", $uri[$i])){
                        $node   = $next;
                        continue 2;
                    }
                }
            }
            
            $node   = null;
            break;
        }
        
        if($node !== null){
            if(isset($node[self::END][$method])){
                $params = array_map(
                    function($v) use ($uri){
                        if(is_int($v)){
                            $v  = $uri[$v] ?? null;
                        }

                        return $v;
                    },
                    $node[self::END][$method]
                );

                return new Result(Result::FOUND, array_keys($node[self::END]), $params);
            }else if(isset($node[self::END]) && count($node[self::END] > 0)){
                return new Result(Result::METHOD_NOT_ALLOWED, array_keys($node[self::END]));
            }
        }
        
        return new Result(Result::NOT_FOUND);
    }
}