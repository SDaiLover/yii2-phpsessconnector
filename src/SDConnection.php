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

/**
 * SDaiLover Connection Class.
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
class SDConnection extends \yii\db\Connection
{
    public function getSchema()
    {
        if (!isset($this->schemaMap['phpsessconnector']))
            $this->schemaMap['phpsessconnector'] = 'sdailover\yii\phpsessconnector\SDSchema';            
        return parent::getSchema();
    }

    public function createCommand($sql = null, $params = [])
    {
        $driver = $this->getDriverName();
        $commandClass = 'sdailover\yii\phpsessconnector\SDCommand';
        if ($driver==='phpsessconnector')
            $this->commandClass= $commandClass;
        if (!isset($this->commandMap['phpsessconnector']))
            $this->commandMap['phpsessconnector'] = $commandClass;
        return parent::createCommand($sql, $params);
    }
}