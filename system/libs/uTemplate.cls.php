<?php

/*
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Ska testa om denna kan fungera
 * Description of uTemplate
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class uTemplate {
    
    private $data = array();
    
    private $template;
    
    function load($file) {
        if (is_file($file)) {
            $this->template = file_get_contents($file);
        }
    }
    
    function __get($name) {
        return $this->data[$name];
    }
    
    function __set($name, $value) {
        if (is_array($value)) {
            $this->data[$name] = $value;
        } else {
            $this->data[$name].= $value;
        }
    }
    
    function publish() {
        extract($this->data);
        eval("?>".$this->template."<?");
    }
    
}
