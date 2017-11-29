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
namespace Roust\SRegex;

/**
 * 自然数に一致し、値をint型に変換する。
 */
class NuturalNumber implements Roust\ShortRegexInterface{
    
    /**
     * @inheritdoc
     */
    public function match(string $str): bool{
        return (bool)preg_match("`\A[1-9][0-9]*\z`", $str);
    }
    
    /**
     * @inheritdoc
     */
    public function convert(string $str){
        return (int)$str;
    }
}