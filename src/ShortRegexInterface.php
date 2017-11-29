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
 * Interface used to define regex shortcut implementation.
 */
interface ShortRegexInterface{
    
    /**
     * Check if the passed string matches this shortcut.
     * 
     * @param   string  $str
     * 
     * @return  bool
     */
    public function match(string $str): bool;
    
    /**
     * Convert the passed string to the desired format and return it.
     * 
     * @param   string  $str
     * 
     * @return  mixed
     */
    public function convert(string $str);
}