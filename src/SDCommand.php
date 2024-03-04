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
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * SDaiLover Command Class.
 * 
 * @author    : Stephanus Bagus Saputra,
 *              ( 戴 Dai 偉 Wie 峯 Funk )
 * @email     : wiefunk@stephanusdai.web.id
 * @contact   : https://t.me/wiefunkdai
 * @support   : https://opencollective.com/wiefunkdai
 * @link      : https://www.stephanusdai.web.id
 */
class SDCommand extends \yii\db\Command
{
    public $db;
    public $query;
    public $conditions;

    private $_columns = [];
    private $_records;
    private $_hasExcuted = false;
    private $_model;
    private $_session;
    private $_queries;

    public function init()
    {
        $this->startSession();
    }

    public function build($query)
    {
        $model = $query->modelClass;
        $this->query = $query;
        return $this->run($model, $query->where);
    }

    public function run($model, $condition)
    {
        $this->_model = is_string($model) ? Instance::ensure($model, $model::className()) : $model;
        $this->conditions = $condition;
        return $this;
    }

    public function startSession()
    {
        $session = Yii::$app->session;
        if ($session->isActive) {
            $session->open();
        }
        $this->_session = $session;
    }

    public function stopSession()
    {
        $session = Yii::$app->session;
        if ($session->isActive) {
            $session->close();
        }
        $this->_session = null;
    }

    public function clear()
    {
        $session = Yii::$app->session;
        $session->removeAll();
        $this->stopSession();
        $this->startSession();
    }

    protected function getRecords()
    {
        $name = $this->getSessionName();
        return $this->_session->has($name) ? $this->_session->get($name) : [];
    }

    public function bindValue($name, $value, $dataType = null)
    {
        return $this;
    }

    public function bindValues($values)
    {
        $this->_hasExcuted = true;
        if (empty($values)) {
            return $this;
        }

        return $this;
    }

    public function query()
    {
        return $this->queryInternal('');
    }

    public function queryAll($fetchMode = null)
    {
        return $this->queryInternal('fetchAll', $fetchMode);
    }
    
    public function queryOne($fetchMode = null)
    {
        return $this->queryInternal('fetch', $fetchMode);
    }
    
    public function queryScalar()
    {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return $result;
    }
    
    public function queryColumn()
    {
        return $this->queryInternal('fetchAll', \PDO::FETCH_COLUMN);
    }

    public function delete($table, $condition = '', $params = [])
    {
        $this->run($table, $condition);
        $models = $this->queryInternal('fetchAll');
        foreach($models as $model) {
            $this->deleteInternal($table, $model);
        }
        return $this->bindValues($params);
    }

    public function execute()
    {
        return $this->_hasExcuted;
    }

    protected function deleteInternal($table, $model)
    {
        $records = $this->getRecords();
        $tableName = $table::sessionName();

        if (isset($records[$tableName])) {
            $dataTable = $records[$tableName];
        } else {
            $dataTable = new SDDataRecord(['modelClass' => $table]);
        }
        $primaryKeys = $table::primaryKey();
        if (isset($primaryKeys[0])) {
            $pk = $primaryKeys[0];
            if(isset($model[$pk])) {
                $id=$model[$pk];
                if (isset($dataTable->columns[$id])) {
                    $columns = $dataTable->columns;
                    unset($columns[$id]);
                    $dataTable->columns=$columns;
                }
    
                $records[$tableName] = $dataTable;
                $name = $this->getSessionName();
                $this->_session->set($name, $records);
                $this->_records = $records;
            } else {
                throw new \yii\db\Exception('"' . $tableName . '" not found data.');
            }
        }
    }

    protected function queryInternal($method, $fetchMode = null)
    {
        $records = $this->getRecords();
        $tableName = $this->_model::sessionName();
        if (isset($records[$tableName])) {
            $record = $records[$tableName];
            $this->_columns = array_values($record->columns);
        }

        $this->_queries = $this->_columns;

        if (($conditions=$this->conditions) && $conditions!==null) {
            if (is_string($conditions)) {
                throw new InvalidConfigException('"' . get_called_class() . '" set condition is not supported.');
            }
            if (is_array($conditions) && isset($conditions[0]) &&
                (strcasecmp($conditions[0], 'and') === 0 || strcasecmp($conditions[0], 'or') === 0)) {
                $logical = $conditions[0];
                $this->_queries = $this->queryFilterCondition($conditions, null, $logical);
            } else {
                $this->_queries = $this->queryFindCondition($conditions);
            }
        }

        $allModels = $this->_queries;

        if ($method==='fetchAll') {
            return $allModels;
        } elseif ($method==='fetch') {
            return count($allModels) > 0 ? $allModels[0] : false;
        } elseif ($method==='fetchColumn') {
            return count($allModels);
        }
        return null;
    }

    public function excute()
    {
        return true;
    }

    public function insert($table, $columns)
    {
        $params = [];
        $primaryKeys = $table::primaryKey();
        if (isset($primaryKeys[0])) {
            $pk = $primaryKeys[0];
            if (isset($columns[$pk])) {
                $id = $columns[$pk];
                $keys = [];
                foreach($primaryKeys as $tag) {
                    if (isset($columns[$tag])) {
                        $keys[$tag] = $columns[$tag];
                    }
                }
                $this->insertInternal($table, $id, $keys, $columns);
            }
        } else {
            throw new \yii\db\Exception('"' . $table . '" must have a primary key.');
        }

        return $this->bindValues($params);
    }

    protected function insertInternal($table, $id, $keys=[], $columns=[])
    {
        $records = $this->getRecords();
        $tableName = $table::sessionName();

        if (isset($records[$tableName])) {
            $dataTable = $records[$tableName];
        } else {
            $dataTable = new SDDataRecord(['modelClass' => $table]);
        }
        if (!isset($dataTable->columns[$id])) {
            foreach($keys as $tag=>$value) {
                if (!isset($dataTable->primaryKey[$tag]))
                    $dataTable->primaryKey[$tag] = [];
                if (!in_array($value,$dataTable->primaryKey[$tag]))
                    array_push($dataTable->primaryKey[$tag],$value);
            }
            $dataTable->columns[$id] = $columns;
    
            $records[$tableName] = $dataTable;
            $name = $this->getSessionName();
            $this->_session->set($name, $records);
            $this->_records = $records;
        } else {
            throw new \yii\db\Exception('"' . $tableName . '" data has been in recorded.');
        }
    }

    public function update($table, $columns, $condition = '', $params = [])
    {
        $this->run($table, $condition);
        $models = $this->queryInternal('fetchAll');
        foreach($models as $model) {
            $this->updateInternal($table, $model, $columns);
        }
        return $this->bindValues($params);
    }

    protected function updateInternal($table, $model, $columns=[])
    {
        $records = $this->getRecords();
        $tableName = $table::sessionName();

        if (isset($records[$tableName])) {
            $dataTable = $records[$tableName];
        } else {
            $dataTable = new SDDataRecord(['modelClass' => $table]);
        }
        $primaryKeys = $table::primaryKey();
        if (isset($primaryKeys[0])) {
            $pk = $primaryKeys[0];
            if(isset($model[$pk])) {
                $id=$model[$pk];
                if (isset($dataTable->columns[$id])) {
                    $dataColumns = $dataTable->columns[$id];
                    $newColumns = array_merge($dataColumns,$columns);
                    $dataTable->columns[$id] = $newColumns;
                }
    
                $records[$tableName] = $dataTable;
                $name = $this->getSessionName();
                $this->_session->set($name, $records);
                $this->_records = $records;
            } else {
                throw new \yii\db\Exception('"' . $tableName . '" not found data.');
            }
        }
    }

    protected function queryFilterCondition($conditions, $queries=[], $logical='and')
    {
        $fields = []; 
        $queries = $this->isEmpty($queries) ? $this->_columns : $queries;
        if (is_array($conditions) && isset($conditions[0]) &&
                (strcasecmp($conditions[0], 'and') === 0 || strcasecmp($conditions[0], 'or') === 0)) {

            if($logical==='and') {
                foreach($queries as $query) {
                    $where = [];
                    foreach($conditions as $condition) {
                        if (is_array($condition) && isset($condition[0]) &&
                            (strcasecmp($condition[0], 'and') === 0 || strcasecmp($condition[0], 'or') === 0)) {
                            array_merge($fields, $this->queryFilterCondition($condition, $query, $condition[0]));
                        } elseif (is_array($condition)) {
                            array_push($where, $condition);
                        }
                    }

                    if (isset($where[0]) && count($where) > 0) {    
                        if ($this->queryFindCondition($where, '=', $query, true)) {
                            $fields[] = $query;
                        }
                    }
                }
            } else {
                if (count($conditions)===3) {
                    $oronequery = [];
                    $ortwoquery = [];
                    if (is_array($conditions[1]) && isset($conditions[1])) {
                        $where = [];
                        $orcondition = $conditions[1];
                        if (is_array($orcondition) && isset($orcondition[0]) &&
                            (strcasecmp($orcondition[0], 'and') === 0 || strcasecmp($orcondition[0], 'or') === 0)) {
                            $result = $this->queryFilterCondition($orcondition, $queries, $orcondition[0]);
                            if (count($result) > 0) {
                                array_merge($oronequery, $result);
                            }
                        } elseif (is_array($orcondition)) {
                            array_push($where, $orcondition);
                        }

                        if (isset($where[0]) && count($where) > 0) {
                            $oronequery = $this->queryFindCondition($where, '=', $queries);
                        }
                    }
                    
                    if (is_array($conditions[2]) && isset($conditions[2])) {
                        $where = [];
                        $orcondition = $conditions[2];
                        if (is_array($orcondition) && isset($orcondition[0]) &&
                            (strcasecmp($orcondition[0], 'and') === 0 || strcasecmp($orcondition[0], 'or') === 0)) {
                            $result = $this->queryFilterCondition($orcondition, $queries, $orcondition[0]);
                            if (count($result) > 0) {
                                array_merge($ortwoquery, $result);
                            }
                        } elseif (is_array($orcondition)) {
                            array_push($where, $orcondition);
                        }

                        if (isset($where[0]) && count($where) > 0) {
                            $ortwoquery = $this->queryFindCondition($where, '=', $queries);
                        }
                    }
                    if (!$this->isEmpty($oronequery)) {
                        $fields = array_merge($fields, $oronequery);
                    } elseif (!$this->isEmpty($ortwoquery)) {
                        $fields = array_merge($fields, $ortwoquery);
                    } else {
                        $fields = array_merge($oronequery, $ortwoquery);
                    }
                }
            }
        }

        return $fields;
    }

    protected function queryFindCondition($conditions, $operator='=', $queries=[], $check=false)
    {
        $fields = [];
        $queries = $this->isEmpty($queries) ? $this->_columns : $queries;
        if (is_array($queries) && (count($queries) > 0)) {
            if (isset($conditions[0]) && !is_array($conditions[0])) {
                $operator = $conditions[0];
                if ($operator=='between') {
                    if (isset($conditions[1]) && isset($conditions[2]) && isset($conditions[3])) {
                        $k=$conditions[1];
                        $start=$conditions[2];
                        $end=$conditions[3];
                        $conditions = [$k=>[[$start, $end]]];
                    }
                } elseif ($operator=='in') {
                    if (isset($conditions[1]) && isset($conditions[2])) {
                        $k=$conditions[1];
                        $value=$conditions[2];
                        if (is_string($conditions[2])) {
                            $conditions = [$k=>[[$value]]];
                        } else {
                            $conditions = [$k=>[$value]];
                        }
                    }
                } elseif (isset($conditions[1]) && isset($conditions[2])) {
                    $k=$conditions[1];
                    $v=$conditions[2];
                    $conditions = [$k=>$v];
                } else {
                    throw new InvalidConfigException('"' . get_called_class() . '" failed condition argument.');
                }
            }

            $validator = [];
            $where = $this->prepareCondition($conditions, $operator);
            if (isset($where[0])) {
                foreach($conditions as $k=>$v) {
                    if (is_array($v)) {
                        foreach($v as $i=>$v) {
                            $where[$k][$i] = [$operator];
                            if (is_array($v)) {
                                foreach($v as $t=>$v) {
                                    $where[$k][$i][] = $v;
                                }
                            } else {
                                $where[$k][$i][] = $v;
                            }
                            unset($where[$i]);
                        }
                    } else {
                        $where[$k][] = [$operator, $v];
                    }
                }
            }

            if (is_array($queries) && isset($queries[0])) {
                foreach($queries as $query) {
                    if ($this->queryWhereCondition($query, $where, $operator)) {
                        $fields[] = $query;
                        array_push($validator, 'true');
                    } else {
                        array_push($validator, 'false');
                    }
                }
            } else {
                if ($this->queryWhereCondition($queries, $where, $operator)) {
                    $fields[] = $queries;
                    array_push($validator, 'true');
                } else {
                    array_push($validator, 'false');
                }
            }
            if ($check!=false) {
                return (count(array_flip($validator)) === 1 && end($validator) === 'true');
            }
        }
        return $fields;
    }

    protected function queryWhereCondition($query, $conditions=[], $operator='=')
    {
        if (count($conditions) > 0) {
            $validator = [];
            foreach ($conditions as $name=>$values) { 
                foreach($values as $value) {  
                    if (is_array($value) && isset($value[0])) {
                        $operator = $value[0];
                    }

                    if(isset($query[$name])) {    
                        $data=$query[$name];
                        if ($operator=='between') {
                            $start=(is_array($value) && isset($value[1])) ? $value[1] : $value;
                            $end=(is_array($value) && isset($value[2])) ? $value[2] : $value;
                            if (($data >= $start && $data <= $end)) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } elseif ($operator=='like') {
                            $valreq = strtolower((is_array($value) && isset($value[1])) ? $value[1] : $value);
                            $keyword = strpos($valreq, '%')!==false ? $valreq : '%'.$valreq.'%';
                            $search = '/^'.str_replace('%', '.*?', $keyword).'$/';
                            if(preg_match($search, strtolower($data))) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } elseif ($operator=='in') {
                            $vals = [];
                            if (is_string($value)) 
                                $vals[] = $value;
                            elseif (is_array($value))
                            {
                                unset($value[0]);
                                $vals = $value;
                            }
                            foreach ($vals as $val) {
                                if ($data == $val) {
                                    array_push($validator, 'true');
                                }
                            }
                        } elseif ($operator=='!=' || $operator=='<>') {
                            $valreq = (is_array($value) && isset($value[1])) ? $value[1] : $value;
                            if ($data <> $valreq) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } elseif ($operator=='<=') {
                            $valreq = (is_array($value) && isset($value[1])) ? $value[1] : $value;
                            if ($data <= $valreq) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } elseif ($operator=='>=') {
                            $valreq = (is_array($value) && isset($value[1])) ? $value[1] : $value;
                            if ($data >= $valreq) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } elseif (($operator=='=' || $operator=='==')) {
                            $valreq = (is_array($value) && isset($value[1])) ? $value[1] : $value;
                            if ($data == $valreq) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        } else {
                            if ($data == $value) {
                                array_push($validator, 'true');
                            } else {
                                array_push($validator, 'false');
                            }
                        }
                    } else {
                        throw new InvalidConfigException('"' . get_called_class() . '" not found attribute "' . $name . '" on this model.');
                    }
                }
            }

            return (count(array_flip($validator)) === 1 && end($validator) === 'true');
        }
        
        return false;
    }

    private function prepareCondition($conditions=[], $operator='=') {
        $where = [];
        foreach($conditions as $k=>$v) {
            if (is_array($v)) {    
                if (isset($v[0])) {          
                    if ($operator != 'in' && isset($v[0]) && isset($v[1]) && isset($v[2]) &&
                        (strcasecmp($v[0], 'between') === 0 || strcasecmp($v[0], 'in') === 0
                        || strcasecmp($v[0], 'like') === 0 || strcasecmp($v[0], '<=') === 0 
                        || strcasecmp($v[0], '==') === 0 || strcasecmp($v[0], '<>') === 0
                        || strcasecmp($v[0], '!=') === 0 || strcasecmp($v[0], '>=') === 0 
                        || strcasecmp($v[0], '=') === 0)) {
                            $op = $v[0]; $key = $v[1]; $val = $v[2];
                            if (isset($v[3]) && ($op=='between' || $op=='in')) {
                                $where[$key][]=[$op, $val, $v[3]];
                            } else {
                                $where[$key][]=[$op, $val];
                            }
                            unset($v[0], $v[1], $v[2], $v[3]);
                    } elseif ($operator != 'in' && isset($v[0][0]) && isset($v[0][1]) && isset($v[0][2]) &&
                        (strcasecmp($v[0][0], 'between') === 0 || strcasecmp($v[0][0], 'in') === 0 
                        || strcasecmp($v[0][0], 'like') === 0 || strcasecmp($v[0][0], '<=') === 0 
                        || strcasecmp($v[0][0], '==') === 0 || strcasecmp($v[0][0], '<>') === 0 
                        || strcasecmp($v[0][0], '!=') === 0 || strcasecmp($v[0][0], '>=') === 0
                        || strcasecmp($v[0], '=') === 0)) {
                        $op = $v[0][0];
                        $key = $v[0][1];
                        $val = $v[0][2];
                        if (isset($v[3]) && ($op=='between' || $op=='in')) {
                            $where[$key][]=[$op, $val, $v[0][3]];
                        } else {
                            $where[$key][]=[$op, $val];
                        }
                        unset($v[0]);
                    }
                                        
                    foreach($v as $g=>$m) {
                        if (is_array($m)) {
                            $where[$g]=$m;
                        } else {
                            $where[$g][]=$m;
                        }
                    }
                } else {  
                    if (is_numeric($k)) {                      
                        foreach($v as $g=>$m) {
                            if (is_array($m)) {
                                $where[$g]=$m;

                            } else {
                                $where[$g][]=$m;
                            }
                        }
                    } else {
                        $where[$k]=$v;
                    }
                }
            } else {
                $where[$k][]=$v;
            }
        }
        return $where;
    }

    protected function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }

    protected function createDataModel($dataFields) {
        $allModels = [];
        if (count($dataFields) > 0)
        {
            foreach($dataFields as $fields) {
                $model = $this->_model::instantiate($fields);
                $model->load($fields, '');
                $model::populateRecord($model, $fields);
                $allModels[] = $model;
            }
        }
        return $allModels;
    }

    private function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        if (strtotime($date) !== false)
            return true;
        $datePart = \DateTime::createFromFormat($format, $date);
        return $datePart && $datePart->format($format) == $date;
    }

    private function getSessionName()
    {
        $dsn = $this->db->dsn;
        if (strncmp('phpsessconnector:', $dsn, 11) !== 0) {
            throw new InvalidConfigException('"' . get_called_class() . '" must have a session name on database configuration.');
        }
        return 'db' . ucfirst(substr($dsn, 11));
    }
}