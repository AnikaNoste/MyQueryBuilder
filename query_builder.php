<?php

class QueryBuilder
{
	private $conn;
	private $select = [field=>[], table=>[], altNameTable=>[], altNameField=>[]];
	private $distinct = false;
	private $join = [table_1=>[], table_2=>[], value_1=>[], value_2=>[], operand=>[]];
	private $orderBy = [field=>[], operand=>[]];
    private $where = [];
    private $insert = [table=>null, field=>[], value=>[]];
	private $deleteTable;
	private $update = [table=>null, field=>[], value=>[]];
	private $sql;
	private $error = [
		info=>"Внимание! Возникла проблема с методом",
		zero=>"Недостаточное количество введенных параметров", 
		type=>"Неподходящий тип введенных параметров", 
		format=>"Неподходящий формат введенных параметров"];

    public function __construct($params)  
    {
		$db_host = $params['host'];
		$db_name = $params['db'];
		$db_username = $params['user'];
		$db_password = $params['password'];
		try {
			$this->conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password); 
			$this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $warning) {
			try {
				$this->conn = new PDO("pgsql:host=$db_host;dbname=$db_name", $db_username, $db_password); 
				$this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $warning) {
				try {
					$this->conn = new PDO("sqlsrv:server=$db_host;Database=$db_name", $db_username, $db_password); 
					$this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch (PDOException $warning) {
					print "Внимание! Возникла проблема при соединение с сервером <br>".$warning->getMessage();
					exit();
				}
			}
		}
    } 

    function select($select = null) //select([['поле', 'таблица', 'краткое именование таблицы'], [...], ...])
    {
        try {
			if ($select == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_array($select)) {
				throw new Exception($this->error['type']);
			} else {
				for ($i = 0; $i < count($select); $i++) {
					if (!is_array($select[$i])
						or count($select[$i]) != 3
						or !is_string($select[$i][0])
						or !is_string($select[$i][1])
						or !is_string($select[$i][2])) {
						throw new Exception($this->error['format']);
					}
					else{
						for ($i = 0; $i < count($select); $i++) {
							array_push($this->select['field'], $select[$i][0]);
							array_push($this->select['table'], $select[$i][1]);
							array_push($this->select['altNameTable'], $select[$i][2]);
						}
					}
					
				}
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." select() <br>".$e->getMessage();
			exit();
		}
    }
	
    function nameFild($altNameField = null) //nameFild(['Измененные названия полей', ..])
    {
        try {
			if ($altNameField == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_array($altNameField)) {
				throw new Exception($this->error['type']);
			} elseif (count($altNameField) != count($this->select['field'])) {
				throw new Exception($this->error['format']);
			} else {
				for ($i = 0; $i < count($altNameField); $i++) {
					if (!is_string($altNameField[$i])) {
						throw new Exception($this->error['format']);
					}
					else{
						$this->select['altNameField'] = $this->select['altNameField'] + $altNameField;
					}
				}
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." nameFild() <br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function distinct() //удалить повторяющиеся строки
    {
        $this->distinct = true;
        return $this;
    }

    function join($table_1 = null, $value_1 = null, $table_2 = null, $value_2 = null, $operand = null) 
    { 
		// join('таблица 1', 'поле таблицы 1', 'таблица 2', 'поле таблицы 2', 'тип join')
        try {
			if (!is_string($table_1)
				or !is_string($value_1)
				or !is_string($table_2)
				or !is_string($value_2)
				or !($operand == 'inner' 
				or $operand == 'left outer' 
				or $operand == 'right outer' 
				or $operand == 'full outer' 
				or $operand == 'cross')) {
				throw new Exception($this->error['format']);
			} else {
				$this->join['table_1'] = $table_1;
				$this->join['table_2'] = $table_2;
				$this->join['value_1'] = $value_1;
				$this->join['value_2'] = $value_2;
				$this->join['operand'] = $operand;
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." join() <br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function orderBy($order = null) // orderBy([['поле', 'тип сортировки'], [...] ... ]); 
    {
        try {
			if ($order == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_array($order)) {
				throw new Exception($this->error['type']);
			} else {
				for ($i = 0; $i < count($order); $i++) {
					if (!is_array($order[$i])
						or !(count($order[$i]) == 2
						or is_string($order[$i][0])
						or $order[$i][1] == 'DESC' 
						or $order[$i][1] == 'ASC')) {
						throw new Exception($this->error['format']);
					} else {
						array_push($this->orderBy['field'], $order[$i][0]);
						array_push($this->orderBy['operand'], $order[$i][1]);
					}
				}
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." orderBy()!<br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function where($where = null) //where([['соединение AND или OR', [['поле', 'оператор', 'значение'], ... ]] ...])
    {
		try {
			if ($where == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_array($where)) {
				throw new Exception($this->error['type']);
			} else {
				for ($i = 0; $i < count($where); $i++) {
					if (!is_array($where[$i])
						or !is_string($where[$i][0]) 
						or !($where[$i][0] == 'OR' 
						or $where[$i][0] == 'AND') 
						or !is_array($where[$i][1])) {
						throw new Exception($this->error['format']);
					} else {
						$this->where = [];
						for ($q = 0; $q < count($where); $q++) {
							array_push($this->where, ['operand' => $where[$q][0], 'where' => []]);
							for ($k = 0; $k < count($where[$q][1]); $k++) { 
								if (($where[$q][1][$k][1] == '<>' or $where[$q][1][$k][1] == '='
									or $where[$q][1][$k][1] == '<' or $where[$q][1][$k][1] == '>'
									or $where[$q][1][$k][1] == 'like' or ($where[$q][1][$k][1] == 'is' 
									and ($where[$q][1][$k][2] == 'null' or $where[$q][1][$k][2] == 'not null')))
									and count($where[$q][1][$k]) == 3) {
									array_push(
										$this->where[$q]['where'], [
											'field' => $where[$q][1][$k][0],
											'operand' => $where[$q][1][$k][1],
											'value' => $where[$q][1][$k][2]
											]
										);
								}
								elseif ($where[$q][1][$k][1] == 'between' and count($where[$q][1][$k]) == 4)
								{
									array_push(
										$this->where[$q]['where'], 
										['field' => $where[$q][1][$k][0],'operand' => $where[$q][1][$k][1],
										'value_1' => $where[$q][1][$k][2],'value_2' => $where[$q][1][$k][3]]
									);
								}
								else {
									throw new Exception($this->error['format']);
								}
							}
						}
					}
				}
			}
			return $this;
		} catch (Exception $e) {
			print print $this->error['info']." where()!<br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function insert($table = null, $field = null, $values = null) 
	{ 
		//insert('таблица', ['поле 1', 'поле 2', ...], [ ['значение 1', 'значение 2', ... ], [...] ]);
        try {
			if ($table == null or $field == null or $values == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_string($table) and !is_array($field) and !is_array($values)) {
				throw new Exception($this->error['type']);
			} else {
				$this->insert['table'] = $table;//запоминаем таблицу
				for ($i = 0; $i < count($values); $i++) {
					if (!is_string($field[$i]) or !is_array($values[$i]) or count($field) != count($values[$i])) {
						throw new Exception("Введены некорректные параметры");
					} else {
						array_push($this->insert['value'], []); //создаем группу значений
						for ($j = 0; $j < count($values[$i]); $j++) {
							if (!is_string($values[$i][$j])) {
								throw new Exception("Введены некорректные параметры");
							}
							else{
								array_push($this->insert['value'][$i], $values[$i][$j]);  //запоминаем значения
							}
						}
					}
				}
				for ($i = 0; $i < count($field); $i++) {
					array_push($this->insert['field'], $field[$i]);//запоминаем поля
				}
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." insert()!<br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function delete($table = null, $where = null) //delete('таблица', условия); //удаление
    {
        //delete('таблица', условия); //удаление
		try {
			if ($table == null or $where == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_string($table)) {
				throw new Exception($this->error['type']);
			} else {
				$this->deleteTable = $table; //запоминаем таблицу
				$this->where($where);
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." orderBy()!<br>".$e->getMessage();
			exit();
		}
        return $this;
    }
 
    function update($table = null, $update = null) //update('таблица', [['поле', 'значение'], [...]])
    {
        //update('таблица', [['поле', 'значение'], [...]])
        try {
			if ($table == null or $update == null) {
				throw new Exception($this->error['zero']);
			} elseif (!is_string($table) or !is_array($update)) {
				throw new Exception($this->error['type']);
			} else {
				$this->update['table'] = $table; //запоминаем таблицу
				for ($i = 0; $i < count($update); $i++) { //рассматриваем каждую группу необходимых изменений
					if (!is_array($update[$i]) or count($update[$i]) != 2 
						or !is_string($update[$i][0]) or !is_string($update[$i][1])) {
						throw new Exception($this->error['format']);
					}
					else{
						array_push($this->update['field'], $update[$i][0]);//запоминаем поля
						array_push($this->update['value'], $update[$i][1]);//запоминаем значения
					}
				}
			}
			return $this;
		} catch (Exception $e) {
			print $this->error['info']." orderBy()!<br>".$e->getMessage();
			exit();
		}
        return $this;
    }

    function createSQL() //составление запроса
    {
        $sql=null;
		if ($this->select['field'][0]!=null) {
			$sql = 'SELECT';
			if ($this->distinct == true) {
				$sql = $sql . ' ' . 'DISTINCT';
			}
			if (is_array($this->select['altNameField'])) {
				for ($i = 0; $i < count($this->select['altNameField']); $i++) {
					$ar_str_select[] = $this->select['altNameTable'][$i]  .
						'.' . $this->select['field'][$i] .
						' ' . 'AS' . 
						' `' . $this->select['altNameField'][$i] . '`';
				}
				$str_select = implode(' , ', $ar_str_select);
				$sql = $sql . ' ' . $str_select;
			} else {
				for ($i = 0; $i < count($this->select); $i++) {
					$ar_str_select[] = $this->select['altNameTable'][$i] .
						'.' . $this->select['field'][$i];
				}
				$str_select = implode(' , ', $ar_str_select);
				$sql = $sql . ' ' . $str_select;
			}
			for ($i = 0; $i < count($this->select['field']); $i++) {
				if ($this->join['table_1']!=null) {
					if ($this->join['table_2'] == $this->select['table'][$i]) {
						$joinTable = $this->select['table'][$i] . ' AS ' . $this->select['altNameTable'][$i];
						$joinValue_2 = $this->select['altNameTable'][$i] . '.' . $this->join['value_2'];
					} else {
						$ar_from_select[] = $this->select['table'][$i] . ' AS ' . $this->select['altNameTable'][$i];
					}
					if ($this->join['table_1'] == $this->select['table'][$i]) {
						$joinValue_1 = $this->select['altNameTable'][$i] . '.' . $this->join['value_1'];
					}
				} else {
					$ar_from_select[] = $this->select['table'][$i] . ' AS ' . $this->select['altNameTable'][$i];
				}
			}
			$str_from = implode(', ', array_unique($ar_from_select));
			$sql = $sql . ' FROM ' . $str_from;
			if ($this->join['table_1']!=null) {
				$sql = $sql . ' ' . $this->join['operand'] . ' ' .
					' JOIN ' . $joinTable . ' ON ' . $joinValue_1 . ' = ' . $joinValue_2;
			}
			if (is_array($this->where)) {
				for ($q = 0; $q < count($this->where); $q++) {
					$ar_str_where = [];
					for ($k = 0; $k < count($this->where[$q]); $k++) {
						if ($this->where[$q]['where'][$k]['operand'] == 'is') {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] .
								'` IS ' . $this->where[$q]['where'][$k]['value'];
						} elseif ($this->where[$q]['where'][$k]['operand'] == 'between') {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` BETWEEN \'' .
								$this->where[$q]['where'][$k]['value_1'] . '\' AND \'' .
								$this->where[$q]['where'][$k]['value_2'] . '\'';
						} else {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` ' .
								$this->where[$q]['where'][$k]['operand'] .
								' \'' . $this->where[$q]['where'][$k]['value'] . '\'';
						}
					}
					$ar_where[] = implode(' ' . $this->where[$q]['operand'] . ' ', $ar_str_where);
				}
				$str_where = implode(') AND ( ', $ar_where);
				$sql = $sql . ' ' . 'WHERE (' . $str_where . ')';
			}
			if (is_array($this->orderBy['field'])) {
				$sql = $sql . ' ' . 'ORDER BY';
				for ($i = 0; $i < count($this->orderBy['field']); $i++) {
					$ar_str_order[] = ' ' . '`' . $this->orderBy['field'][$i] . 
						'` ' . $this->orderBy['operand'][$i] . ' ';
				}
				$str_order = implode(' , ', $ar_str_order);
				$sql = $sql . " " . $str_order;
			}
		} elseif ($this->insert['table']!=null) {
			for ($i = 0; $i < count($this->insert['field']); $i++) {
				$ar_insert_field[] = '`' . $this->insert['field'][$i] . '`';
			}
			for ($i = 0; $i < count($this->insert['value']); $i++) {
				for ($j = 0; $j < count($this->insert['value'][$i]); $j++) {
					$ar_insert_value[$i][] = '\'' . $this->insert['value'][$i][$j] . '\'';
				}
			}
			for ($i = 0; $i < count($this->insert['value']); $i++) {
				$insert_value[$i] = implode(' , ', $ar_insert_value[$i]);
			}
			$insert_field = implode(' , ', $ar_insert_field);
			$insert_value = implode(' ), ( ', $insert_value);
			$sql = 'INSERT INTO ' . $this->insert['table']
				. ' (' . $insert_field . ') VALUES (' . $insert_value . ')';

		} elseif ($this->deleteTable!=null) {
			for ($q = 0; $q < count($this->where); $q++) {
				$ar_str_where = [];
				for ($k = 0; $k < count($this->where[$q]); $k++) {
					if ($this->where[$q]['where'][$k]['operand'] == 'is') {
						$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] .
							'` IS ' . $this->where[$q]['where'][$k]['value'];
					} elseif ($this->where[$q]['where'][$k]['operand'] == 'between') {
						$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` BETWEEN \'' .
							$this->where[$q]['where'][$k]['value_1'] . '\' AND \'' .
							$this->where[$q]['where'][$k]['value_2'] . '\'';
					} else {
						$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` ' .
							$this->where[$q]['where'][$k]['operand'] .
							' \'' . $this->where[$q]['where'][$k]['value'] . '\'';
					}
				}
				$ar_where[] = implode(' ' . $this->where[$q]['operand'] . ' ', $ar_str_where);
			}
			$str_where = implode(') AND ( ', $ar_where);
			$sql = 'DELETE FROM ' . $this->deleteTable . ' ' . 'WHERE (' . $str_where . ')';
		} elseif ($this->update['table']!=null) {
			for ($i = 0; $i < count($this->update['value']); $i++) {
				$ar_str_update[] = '`' . $this->update['field'][$i] . '` = \'' . $this->update['value'][$i]. '\'';
			}
			$str_update = implode(' , ', $ar_str_update);
			$sql = 'UPDATE ' . $this->update['table'] . ' SET ' . $str_update;
			if (is_array($this->where)) {
				for ($q = 0; $q < count($this->where); $q++) {
					$ar_str_where = [];
					for ($k = 0; $k < count($this->where[$q]); $k++) {
						if ($this->where[$q]['where'][$k]['operand'] == 'is') {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] .
								'` IS ' . $this->where[$q]['where'][$k]['value'];
						} elseif ($this->where[$q]['where'][$k]['operand'] == 'between') {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` BETWEEN \'' .
								$this->where[$q]['where'][$k]['value_1'] . '\' AND \'' .
								$this->where[$q]['where'][$k]['value_2'] . '\'';
						} else {
							$ar_str_where[] = '`' . $this->where[$q]['where'][$k]['field'] . '` ' .
								$this->where[$q]['where'][$k]['operand'] .
								' \'' . $this->where[$q]['where'][$k]['value'] . '\'';
						}
					}
					$ar_where[] = implode(' ' . $this->where[$q]['operand'] . ' ', $ar_str_where);
				}
				$str_where = implode(') AND ( ', $ar_where);
				$sql = $sql . ' ' . 'WHERE (' . $str_where . ')';
			}
		}
		$this->sql = $sql;
        return $this->sql;
    }

    function request()
    {
		try {
			$this->conn->query($this->sql) ;
			$this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $warning) {
			try {
				$this->sql = str_replace("`", "\"", $this->sql);	
				$this->conn->query($this->sql) ;
				$this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $warning) {
				print "Внимание! Возникла проблема при осуществлении запроса <br>".$warning->getMessage();
				exit();
			}
		}
		$result=$this->sql;
        return $result;
    }
}



