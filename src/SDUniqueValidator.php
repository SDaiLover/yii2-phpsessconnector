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
 * SDaiLover Unique Validator Class.
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
class SDUniqueValidator extends \yii\validators\UniqueValidator
{
    public function validateAttribute($model, $attribute)
    {
        $targetAttribute = $this->targetAttribute === null ? $attribute : $this->targetAttribute;
        if ($this->skipOnError) {
            foreach ((array)$targetAttribute as $k => $v) {
                if ($model->hasErrors(is_int($k) ? $v : $k)) {
                    return;
                }
            }
        }

        $rawConditions = $this->prepareConditions($targetAttribute, $model, $attribute);
        $conditions = [];

        foreach ($rawConditions as $key => $value) {
            if (is_array($value)) {
                $this->addError($model, $attribute, Yii::t('yii', '{attribute} is invalid.'));
                return;
            }
            $conditions[$key] = $value;
        }

        $targetClass = $this->getTargetClass($model);
        $db = $targetClass::getDb();
        $driver = $db->getDriverName();

        if ($driver!=='phpsessconnector')
            parent::validateAttribute($model, $attribute);
        $modelExists = $model::findAll($conditions);  
        if ($this->modelExists($modelExists, $model)) {
            if (is_array($targetAttribute) && count($targetAttribute) > 1) {
                $this->addComboNotUniqueError($model, $attribute);
            } else {
                $this->addError($model, $attribute, $this->message);
            }
        }
    }

    private function modelExists($modelExists, $model)
    {
        $primaryKey = $model::primaryKey();
        if (isset($primaryKey[0])) {
            $pk = $primaryKey[0];
            if (!empty($query->join) || !empty($query->joinWith)) {
                $pk = static::recordName() . '.' . $pk;
            }

            foreach($modelExists as $modelExist)
                return $modelExist->$pk !== $model->$pk;
        } else {
            throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
        }

        return false;
    }

    private function getTargetClass($model)
    {
        return $this->targetClass === null ? get_class($model) : $this->targetClass;
    }

    private function prepareConditions($targetAttribute, $model, $attribute)
    {
        if (is_array($targetAttribute)) {
            $conditions = [];
            foreach ($targetAttribute as $k => $v) {
                $conditions[$v] = is_int($k) ? $model->$v : $model->$k;
            }
        } else {
            $conditions = [$targetAttribute => $model->$attribute];
        }

        $targetModelClass = $this->getTargetClass($model);
        if (!is_subclass_of($targetModelClass, 'sdailover\components\phpsessconnector\SDActiveRecord')) {
            return $conditions;
        }

        return $this->applyTableAlias($targetModelClass::find(), $conditions);
    }

    private function addComboNotUniqueError($model, $attribute)
    {
        $attributeCombo = [];
        $valueCombo = [];
        foreach ($this->targetAttribute as $key => $value) {
            if (is_int($key)) {
                $attributeCombo[] = $model->getAttributeLabel($value);
                $valueCombo[] = '"' . $model->$value . '"';
            } else {
                $attributeCombo[] = $model->getAttributeLabel($key);
                $valueCombo[] = '"' . $model->$key . '"';
            }
        }
        $this->addError($model, $attribute, $this->message, [
            'attributes' => Inflector::sentence($attributeCombo),
            'values' => implode('-', $valueCombo),
        ]);
    }

    private function applyTableAlias($query, $conditions, $alias = null)
    {
        $prefixedConditions = [];
        foreach ($conditions as $columnName => $columnValue) {
            $prefixedConditions[$columnName] = $columnValue;
        }

        return $prefixedConditions;
    }
}