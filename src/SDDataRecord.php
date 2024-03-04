<?php
/**
 * SDaiLover PHPSessionConnector for Yii2 Framework.
 * 
 * Runtime database helper to choose PHP Session or another 
 * Database without change structure model of Yii2 application packages.
 * 
 * @link      https://www.sdailover.com
 * @email     teams@sdailover.com
 * @copyright Copyright (c) ID 2024 SDaiLover. All rights reserved.
 * @license   https://www.sdailover.com/license.html
 * This software using Yii Framework has released under the terms of the BSD License.
 */

namespace sdailover\yii\phpsessconnector;
/**
 * Copyright (c) ID 2024 SDaiLover (https://www.sdailover.com).
 * All rights reserved.
 *
 * Licensed under the Clause BSD License, Version 3.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.sdailover.com/license.html
 *
 * This software is provided by the SDAILOVER and
 * CONTRIBUTORS "AS IS" and Any Express or IMPLIED WARRANTIES, INCLUDING,
 * BUT NOT LIMITED TO, the implied warranties of merchantability and
 * fitness for a particular purpose are disclaimed in no event shall the
 * SDaiLover or Contributors be liable for any direct,
 * indirect, incidental, special, exemplary, or consequential damages
 * arising in anyway out of the use of this software, even if advised
 * of the possibility of such damage.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Yii;
use yii\base\InvalidArgumentException;

/**
 * SDaiLover Data Record Class.
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
class SDDataRecord extends \yii\base\BaseObject
{
    public $modelClass;

    public $primaryKey = [];
    public $foreignKeys = [];
    public $uniques = [];
    public $checks = [];

    public $columns = [];

    public $_schemas = [];

    public function __construct($schema = [])
    {
        $this->setSchemas($schema);
        parent::__construct($schema);
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (parent::canGetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        try {
            return $this->hasSchema($name);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (parent::canSetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        try {
            return $this->hasSchema($name);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __get($name)
    {
        if (isset($this->_schemas[$name]) || array_key_exists($name, $this->_schemas)) {
            return $this->_schemas[$name];
        }

        if ($this->hasSchema($name)) {
            return null;
        }

        $value = parent::__get($name);
        return $value;
    }

    public function __set($name, $value)
    {
        if ($this->hasSchema($name)) {
            $this->_schemas[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __isset($name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __unset($name)
    {
        if ($this->hasSchema($name)) {
            unset($this->_schemas[$name]);
        }
    }

    public function hasSchema($name)
    {
        return isset($this->_schemas[$name]) || property_exists($this, $name);
    }

    public function getSchema($name)
    {
        return isset($this->_schemas[$name]) ? $this->_schemas[$name] : null;
    }

    public function setSchema($name, $value)
    {
        if ($this->hasSchema($name)) {
            $this->_schemas[$name] = $value;
        } else {
            throw new InvalidArgumentException(get_class($this) . ' has no schema named "' . $name . '".');
        }
    }

    public function getSchemas()
    {
        return isset($this->_schemas[$name]) ? $this->_schemas[$name] : null;
    }

    public function setSchemas($schemas)
    {
        foreach ($schemas as $name=>$value){
            $this->setSchema($name, $value);
        }
    }
}