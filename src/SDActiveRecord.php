<?php
/**
 * SDaiLover PhpDbSession for Yii Framework
 *
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.sdailover.com,
 *              https://www.stephanusdai.web.id
 * @license   : https://www.sdailover.com/license.html
 * @copyright : (c) ID 2023-2024 SDaiLover. All rights reserved.
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
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * SDaiLover PhpSession Active Record Component
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
abstract class SDActiveRecord extends \yii\db\ActiveRecord
{
    private static $queryClass;

    abstract static function loadTable();

    public static function sessionName()
    {
        $reflect = new \ReflectionClass(get_called_class());
        return $reflect->getShortName();
    }

    public static function recordName()
    {
        $driver = static::getDb()->getDriverName();
        return $driver!=='phpsessconnector' ? static::tableName() : get_called_class();
    }

    public static function find()
    {
        return static::getInstanceQuery(true);
    }

    public static function findBySql($sql, $params = [])
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    protected static function findByCondition($condition)
    {
        $query = static::find();

        if (!ArrayHelper::isAssociative($condition)) {
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $pk = $primaryKey[0];
                if (!empty($query->join) || !empty($query->joinWith)) {
                    $pk = static::recordName() . '.' . $pk;
                }
                $condition = [$pk => $condition];
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
        }

        return $query->andWhere($condition);
    }

    public function equals($record)
    {
        if ($this->isNewRecord || $record->isNewRecord) {
            return false;
        }

        return static::recordName() === $record->tableName() && $this->getPrimaryKey() === $record->getPrimaryKey();
    }

    public static function getTableSchema()
    {
        $tableSchema = static::getDb()
            ->getSchema()
            ->getTableSchema(static::recordName());

        if ($tableSchema === null) {
            throw new InvalidConfigException('The table does not exist: ' . static::recordName());
        }

        return $tableSchema;
    }
    
    public static function populateRecord($record, $row)
    {
        $driver = static::getDb()->getDriverName();
        if ($driver!=='phpsessconnector') {
            parent::populateRecord($record, $row);
        } else {
            $columns = static::getTableSchema()->columns;
            foreach ($row as $name => $value) {
                if (isset($columns[$name]) && property_exists($record,$name)) {
                    $row[$name] = $value;
                    $record->$name = $value;
                    $record->setAttribute($name, $value);
                } elseif ($record->canSetProperty($name)) {
                    $record->$name = $value;
                }
            }
            $record->setOldAttributes($record->getAttributes());
        }
    }

    protected static function getInstanceQuery($reset=false)
    {
        if (self::$queryClass===null || $reset) {
            $driver = static::getDb()->getDriverName();
            $queryClass = $driver==='phpsessconnector' ? SDActiveQuery::className() : ActiveQuery::className();
            self::$queryClass = Yii::createObject($queryClass, [get_called_class()]);
            static::loadTable();
        }
        return self::$queryClass;
    }

    protected static function records($attributes, $reset=false)
    {
        $driver = static::getDb()->getDriverName();
        $queryName = $driver==='phpsessconnector' ? SDActiveQuery::className() : ActiveQuery::className();
        $queryClass = Yii::createObject($queryName, [get_called_class()]);
        $records = $queryClass->all();
        if ((is_array($records) && !isset($records[0])) || $reset) {
            foreach($attributes as $attribute)
            {
                $model = Yii::createObject(get_called_class());
                $model->attributes = $attribute;
                $model->save(false);
            }
        }
    }

    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->isTransactional(parent::OP_INSERT)) {
            return $this->insertInternal($attributes);
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $result = $this->insertInternal($attributes);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        $driver = static::getDb()->getDriverName();
        if ($driver!=='phpsessconnector')
            return parent::save($runValidation, $attributeNames);
        if ($this->getIsNewRecord()) {
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $pk = $primaryKey[0];
                if (!empty($query->join) || !empty($query->joinWith)) {
                    $pk = static::recordName() . '.' . $pk;
                }
                
                if (($this->getAttribute($pk) === null || empty($this->getAttribute($pk))) && property_exists($this, $pk)) {
                    if ($this->$pk === null || empty($this->$pk)) {
                        $this->$pk = count(static::find()->all()) + 1;
                        $this->setAttribute($pk, count(static::find()->all()) + 1);
                    }
                }
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
            return $this->insert($runValidation, $attributeNames);
        }

        return $this->update($runValidation, $attributeNames) !== false;
    }
    
    protected function insertInternal($attributes = null)
    {
        $driver = static::getDb()->getDriverName();
        if ($driver!=='phpsessconnector')
            return parent::insertInternal($attributes);

        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (($primaryKeys = static::getDb()->schema->insert(static::recordName(), $values)) === false) {
            return false;
        }
            

        foreach ($primaryKeys as $name => $value) {
            $this->setAttribute($name, $value);
            $values[$name] = $value;
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    public static function updateAll($attributes, $condition = '', $params = [])
    {
        $command = static::getDb()->createCommand();
        $command->update(static::recordName(), $attributes, $condition, $params);

        return $command->execute();
    }

    public static function updateAllCounters($counters, $condition = '', $params = [])
    {
        $n = 0;
        foreach ($counters as $name => $value) {
            $counters[$name] = new Expression("[[$name]]+:bp{$n}", [":bp{$n}" => $value]);
            $n++;
        }
        $command = static::getDb()->createCommand();
        $command->update(static::recordName(), $counters, $condition, $params);

        return $command->execute();
    }

    public static function deleteAll($condition = null, $params = [])
    {
        $command = static::getDb()->createCommand();
        $command->delete(static::recordName(), $condition, $params);

        return $command->execute();
    }

    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        $rowAttributes = $this->getAttributes();
        $oldAttributes = $this->getOldAttributes();
        if ($oldAttributes === null) {
            foreach ($rowAttributes as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($rowAttributes as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, $oldAttributes) || $value !== $oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }
}