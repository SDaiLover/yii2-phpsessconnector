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
use yii\base\NotSupportedException;
use yii\db\ConstraintFinderInterface;
use yii\db\ConstraintFinderTrait;
use yii\di\Instance;
use yii\db\TableSchema;

/**
 * SDaiLover Schema Class.
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
class SDSchema extends \yii\db\Schema implements ConstraintFinderInterface
{
    use ConstraintFinderTrait;
    private $_model;

    public function insert($table, $columns)
    {
        $command = $this->db->createCommand()->insert($table, $columns);
        if (!$command->execute()) {
            return false;
        }
        $tableSchema = $this->getTableSchema($table);
        $result = [];
        foreach ($tableSchema->primaryKey as $name) {
            $result[$name] = isset($columns[$name]) ? $columns[$name] : $tableSchema->columns[$name]->defaultValue;
        }
        
        return $result;
    }
    
    public function getQueryBuilder()
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    protected function resolveTableNames($table, $name)
    {
        $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = $parts[1];
        } else {
            $table->schemaName = $this->defaultSchema;
            $table->name = $name;
        }

        $table->fullName = $table->schemaName !== $this->defaultSchema ? $table->schemaName . '.' . $table->name : $table->name;
    }

    protected function prepareTableColumn($table)
    {
        $class = new \ReflectionClass($this->_model);
        $columns = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $name = $property->getName();
                $columns[$name] = $name;
            }
        }
        $table->columns = $columns;
    }

    protected function prepareTableKeys($table)
    {
        if (method_exists($this->_model,'attributeKey') && 
            is_callable([$this->_model,'attributeKey'])) {
                $reflect = new \ReflectionMethod($this->_model,'attributeKey');
                if ($reflect->isStatic()) {
                    $table->primaryKey = call_user_func([$this->_model,'attributeKey']);
                }
        } else {
            $columns = array_values($table->columns);
            $table->primaryKey = isset($columns[0]) ? [$columns[0]] : [];
        }
    }

    protected function loadTableSchema($tableName)
    {
        $this->_model = Instance::ensure($tableName, $tableName::className());
        if ($this->_model!==null)
        {
            $reflect = new \ReflectionClass($this->_model);
            $name = $reflect->getShortName();
            $table = new TableSchema();
            $this->resolveTableNames($table, $name);
            $this->prepareTableColumn($table);
            $this->prepareTableKeys($table);
            return $table;
        }
        
        return null;
    }

    protected function loadTablePrimaryKey($tableName)
    {
        return $this->loadTableConstraints($tableName, 'primaryKey');
    }
    
    protected function loadTableForeignKeys($tableName)
    {
        return $this->loadTableConstraints($tableName, 'foreignKeys');
    }

    protected function loadTableUniques($tableName)
    {
        return $this->loadTableConstraints($tableName, 'uniques');
    }

    protected function loadTableChecks($tableName)
    {
        return $this->loadTableConstraints($tableName, 'checks');
    }

    protected function loadTableIndexes($tableName)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    protected function loadTableDefaultValues($tableName)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    private function loadTableConstraints($tableName, $returnType)
    {
        $result = [
            'primaryKey' => null,
            'foreignKeys' => [],
            'uniques' => [],
            'checks' => [],
        ];

        $this->_model = Instance::ensure($tableName, $tableName::className());
        if ($this->_model!==null)
        {
            if (method_exists($this->_model,'attributeKey') && 
                is_callable([$this->_model,'attributeKey'])) {
                    $reflect = new \ReflectionMethod($this->_model,'attributeKey');
                    if ($reflect->isStatic()) {
                        $result['primaryKey'] = call_user_func([$this->_model,'attributeKey']);
                    }
            } else {
                $columns = array_values($table->columns);
                $result['primaryKey'] = isset($columns[0]) ? [$columns[0]] : [];
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }
}