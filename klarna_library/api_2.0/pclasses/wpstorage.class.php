<?php
/**
 *  Copyright 2010 KLARNA AB. All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification, are
 *  permitted provided that the following conditions are met:
 *
 *     1. Redistributions of source code must retain the above copyright notice, this list of
 *        conditions and the following disclaimer.
 *
 *     2. Redistributions in binary form must reproduce the above copyright notice, this list
 *        of conditions and the following disclaimer in the documentation and/or other materials
 *        provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY KLARNA AB "AS IS" AND ANY EXPRESS OR IMPLIED
 *  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 *  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL KLARNA AB OR
 *  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 *  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  The views and conclusions contained in the software and documentation are those of the
 *  authors and should not be interpreted as representing official policies, either expressed
 *  or implied, of KLARNA AB.
 *
 * @package KlarnaAPI
 */

/**
 * WordPress e-Commerce storage class for KlarnaPClass
 *
 * This class is a WordPress e-Commerce implementation of the PCStorage interface.
 *
 * @package   KlarnaAPI
 * @link      http://integration.klarna.com/
 * @copyright Copyright (c) 2011 Klarna AB (http://klarna.com)
 */
class WPStorage extends PCStorage {

    /**
     * Class constructor
     */
    public function __construct() {
    }

    /**
     * Class destructor
     */
    public function __destruct() {
    }

    /**
     * @see PCStorage::clear()
     */
    public function clear($optionName) {
        update_option($optionName, '');
    }

    /**
     * @see PCStorage::load()
     */
    public function load($optionName) {
        $this->pclasses = get_option($optionName);
    }

    /**
     * @see PCStorage::save()
     */
    public function save($optionName) {
        update_option($optionName, $this->pclasses);
        if(get_option($optionName) != $this->pclasses) {
            $this->clear($optionName);
            throw new Exception("Failed to save KlarnaPClasses");
        }
    }
}
