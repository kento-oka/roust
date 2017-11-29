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
 * ルーティングの結果を示すクラス。
 */
class Result{

    /**
     * Found matching route.
     */
    const FOUND                 = 0;
    
    /**
     * Route not found.
     */
    const NOT_FOUND             = 1;
    
    /**
     * Method not allowed.
     */
    const METHOD_NOT_ALLOWED    = 2;
    
    /**
     * Routing result.
     * 
     * @var int
     */
    private $result;
    
    /**
     * Allow methods.
     * 
     * @var string[]
     */
    private $allow;
    
    /**
     * URI parameters.
     *
     * @var mixed[]
     */
    private $params;
    
    /**
     * Construct the class.
     * 
     * @param   int $result
     *      Routing result.
     * @param   string[]    $allow
     *      Allow methods.
     * @param   mixed[] $params
     *      Routing params.
     * 
     * @return  void
     */
    public function __construct(int $result, array $allow = [], array $params = []){
        $this->result   = in_array($result, [self::FOUND, self::NOT_FOUND, self::METHOD_NOT_ALLOWED])
            ? $result : self::NOT_FOUND;
        $this->allow    = $allow;
        $this->params   = $params;
    }
    
    /**
     * Magic method for unknown property access.
     * 
     * @access  private
     */
    public function __get(string $key){
        if($this->result !== self::FOUND){
            throw new LogicException;
        }
        
        return $this->params[$key] ?? null;
    }

    /**
     * Magic method for unknown property access.
     * 
     * @access  private
     */
    public function __isset(string $key){
        if($this->result !== self::FOUND){
            throw new LogicException;
        }

        return array_key_exists($key, $this->params);
    }
    
    /**
     * ルーティングの結果を返す。
     * 
     * @return  mixed
     */
    public function getResult(){
        return $this->result;
    }
    
    /**
     * 一致したルーティングルールが許可するメソッド一覧
     * 
     * @return  string[]
     */
    public function getAllowed(){
        return $this->allow;
    }
    
    /**
     * ルーティングルールで設定されたパラメータ一覧
     * 
     * @return  mixed[]
     */
    public function getParams(){
        return $this->params;
    }
}