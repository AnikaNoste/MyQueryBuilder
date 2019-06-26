<?php

class query_builder
{
    private $conn;
    private $array = [];
    private $error = [];
    private $sql;

     public function __construct($params)  
    {
        $this->conn = mysqli_connect($params['host'], $params['user'], $params[null], $params['db']);
        if (mysqli_errno($this->conn) > 0) {
            die(mysqli_error($this->conn));
        }
    } 

    function select($select) //select([['поле', 'таблица', 'краткое именование таблицы'], [...], ...])
    {
        $error = [];
        // проверка форматов входных данных
        // select([['поле', 'таблица', 'краткое именование таблицы'], [...], ...])
        if (is_array($select)) {
            for ($i = 0; $i < count($select); $i++) {
                if (!is_array($select[$i])
                    or count($select[$i]) != 3
                    or !is_string($select[$i][0])
                    or !is_string($select[$i][1])
                    or !is_string($select[$i][2])) {
                    $error[] = 1; //запоминается ошибка
                }
            }
        } else {
            $error[] = 1; //запоминается ошибка
        }
        if (in_array(1, $error)) { //если во входных данных обнаружена хотя бы одна ошибка, добавить ее в базу данных
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in select "]; //запоминается ошибка
        } else {
            $sel = array('selField', 'selTable', 'selNameTable');
            $this->array = $this->array + ['select' => []];
            for ($i = 0; $i < count($select); $i++) {
                $this->array['select'] = $this->array['select'] + [$i => []];
                for ($j = 0; $j < 3; $j++) {
                    $this->array['select'][$i] = $this->array['select'][$i] + [$sel[$j] => $select[$i][$j]];
                }
            }
        }
        return $this;
    }

    function nameFild($name_fild) //nameFild(['Измененные названия полей', ..])
    {
        //nameFild(['Измененные названия полей', ..])
        $error = [];
        if (is_array($name_fild)) {
            for ($i = 0; $i < count($name_fild); $i++) {
                if (!is_string($name_fild[$i])) {
                    $error[] = 1;
                }
            }
        } else {
            $error[] = 1;
        }
        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in select "]; //запоминается ошибка
        } else {
            $this->array = $this->array + ['name_fild' => $name_fild];
        }
        return $this;
    }

    function distinct() //удалить повторяющиеся строки
    {
        $this->array = $this->array + ['distinct' => 'distinct'];
        return $this;
    }

    function join($table_1, $value_1, $table_2, $value_2, $join) // join('таблица 1', 'поле таблицы 1', 'таблица 2', 'поле таблицы 2', 'тип join')
    {
        //join('таблица 1', 'поле таблицы 1', 'таблица 2', 'поле таблицы 2', 'тип join') //объединение таблиц
        $error = [];
        if (is_string($table_1)
            and is_string($value_1)
            and is_string($table_2)
            and is_string($value_2)
            and ($join == 'inner' or $join == 'left outer' or $join == 'right outer' or $join == 'full outer' or $join == 'cross')) {
            $error[] = 0;
        } else {
            $error[] = 1;
        }
        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in join "]; //запоминается ошибка
        } else {
            $this->array = $this->array + ['join' => []];
            $this->array['join'] = $this->array['join'] + ['table_1' => $table_1];
            $this->array['join'] = $this->array['join'] + ['table_2' => $table_2];
            $this->array['join'] = $this->array['join'] + ['value_1' => $value_1];
            $this->array['join'] = $this->array['join'] + ['value_2' => $value_2];
            $this->array['join'] = $this->array['join'] + ['join' => $join];
        }
        return $this;
    }

    function orderBy($order) // orderBy([['поле', 'тип сортировки'], [...] ... ]); 
    {
        //orderBy([['поле', 'тип сортировки'], [...] ... ]); //выбор сортировки
        $error = [];
        if (is_array($order)) {
            for ($i = 0; $i < count($order); $i++) {
                if (is_array($order[$i])
                    and count($order[$i]) == 2
                    and is_string($order[$i][0])
                    and ($order[$i][1] == 'DESC' or $order[$i][1] == 'ASC')) {
                    $error[] = 0;
                } else {
                    $error[] = 1;
                }
            }
        } else {
            $error[] = 1;
        }
        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in orderBy "]; //запоминается ошибка
        } else {
            $this->array = $this->array + ['order' => []]; // создание массива
            $ord = array('orderField', 'orderBy');
            for ($i = 0; $i < count($order); $i++) {
                $this->array['order'] = $this->array['order'] + [$i => []];
                for ($j = 0; $j < 2; $j++) {
                    $this->array['order'][$i] = $this->array['order'][$i] + [$ord[$j] => $order[$i][$j]];
                }
            }
        }
        return $this;
    }

    function where($where) //where([['соединение AND или OR', [['поле', 'оператор', 'значение'], ... ]] ...])
    {
        //where([['соединение AND или OR', [['поле', 'оператор', 'значение'], ... ]] ...])
        $error = [];
        // проверка форматов входных данных
        if (!is_array($where)) {
            $error[] = 1;
        } else {
            for ($i = 0; $i < count($where); $i++) {
                if (!is_array($where[$i])
                    or !is_string($where[$i][0]) // если соедниение AND или OR не строкового типа
                    or ($where[$i][0] != 'OR' and $where[$i][0] != 'AND') //если соединение не AND и не OR
                    or !is_array($where[$i][1])) // или если условия не в массиве
                {
                    $error[] = 1; //запоминается ошибка
                }
            }
        }

        if (in_array(1, $error)) { // если обнаружны ошибки
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in where "];
        } else  // если ошибок не обнаружно
        {
            $this->array = $this->array + ['where' => []];

            for ($q = 0; $q < count($where); $q++) { //рассматриваем каждую часть оператора where
                $this->array['where'] = $this->array['where'] + [$q => []]; //создаем массив для данной части
                $this->array['where'][$q] = $this->array['where'][$q] + ['operand' => $where[$q][0]]; //запоминаем соединение всех условий:  AND или OR
                $this->array['where'][$q] = $this->array['where'][$q] + ['where' => []]; //создаем массив для условий данной части

                for ($k = 0; $k < count($where[$q][1]); $k++) { //рассматриваем каждое условие данной части оператора where
                    if (($where[$q][1][$k][1] == '<>'
                            or $where[$q][1][$k][1] == '='
                            or $where[$q][1][$k][1] == '<'
                            or $where[$q][1][$k][1] == '>'
                            or $where[$q][1][$k][1] == 'like') // если вписан один из данных операторов
                        and count($where[$q][1][$k]) == 3) // и всего в массиве 3 значения
                    {
                        $this->array['where'][$q]['where'] = $this->array['where'][$q]['where'] + [$k => []];
                        $wh = ['field', 'operand', 'value'];
                        for ($j = 0; $j < 3; $j++) { //сохраняем условия
                            $this->array['where'][$q]['where'][$k] = $this->array['where'][$q]['where'][$k] + [$wh[$j] => $where[$q][1][$k][$j]];
                        }
                    } // иначе
                    elseif ($where[$q][1][$k][1] == 'between' // если оператор between
                        and count($where[$q][1][$k]) == 4) // и дано 4 значения в массиве
                    {
                        $this->array['where'][$q]['where'] = $this->array['where'][$q]['where'] + [$k => []];
                        $wh = ['field', 'operand', 'value_1', 'value_2'];
                        for ($j = 0; $j < 4; $j++) { //сохраняем условия
                            $this->array['where'][$q]['where'][$k] = $this->array['where'][$q]['where'][$k] + [$wh[$j] => $where[$q][1][$k][$j]];
                        }
                    } // иначе
                    elseif ($where[$q][1][$k][1] == 'is' // если оператор is
                        and count($where[$q][1][$k]) == 3 // если дано 3 значения в массиве
                        and ($where[$q][1][$k][2] == 'null' or $where[$q][1][$k][2] == 'not null')) //и если значения null или not null
                    {
                        $this->array['where'][$q]['where'] = $this->array['where'][$q]['where'] + [$k => []];
                        $wh = array('field', 'operand', 'value');
                        for ($j = 0; $j < 3; $j++) { //сохраняем условия
                            $this->array['where'][$q]['where'][$k] = $this->array['where'][$q]['where'][$k] + [$wh[$j] => $where[$q][1][$k][$j]];
                        }
                    } // иначе генерируем ошибку
                    else {
                        $this->array['error'] = $this->array['error'] + [count($this->array['error']) => "erroneous amount of input data in where "];
                    }
                }
            }
        }
        return $this;
    }

    function insert($table, $field, $values) //insert('таблица', ['поле 1', 'поле 2', ...], [ ['знаение 1', 'значение 2', ... ], [...] ]);
    {
        //insert('таблица', ['поле 1', 'поле 2', ...], [ ['знаение 1', 'значение 2', ... ], [...] ]);
        $error = [];
        if (!is_string($table) //если таблица имеет не строковый формат
            and !is_array($field) //если поля не в массиве
            and !is_array($values)) //если значения не в массиве
        {
            $error[] = 1; // запоминаем ошибку
        } else {
            for ($i = 0; $i < count($field); $i++) {
                if (!is_string($field[$i]) //если данное поле имеет не строковый формат
                    or !is_array($values[$i]) //если значения данного поля не в массиве
                    or count($field) != count($values[$i])) //или если количество полей не равно количеству значений в каждом массиве
                {
                    $error[] = 1; // запоминаем ошибку
                } else {
                    for ($j = 0; $j < count($values[$i]); $j++) {
                        if (!is_string($values[$i][$j])) //если значения в каждом массиве не имеют строковый формат
                        {
                            $error[] = 1; // запоминаем ошибку
                        }
                    }
                }
            }
        }

        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in insert "];
        } else {
            $this->array = $this->array + ['insert' => []];
            $this->array['insert'] = $this->array['insert'] + ['table' => $table]; //запоминаем таблицу
            $this->array['insert'] = $this->array['insert'] + ['field' => []]; // создаем массив для полей
            $this->array['insert'] = $this->array['insert'] + ['value' => []]; //создаем массив для групп значений
            for ($i = 0; $i < count($field); $i++) {
                $this->array['insert']['field'] = $this->array['insert']['field'] + [$i => $field[$i]]; //запоминаем поля
            }
            for ($i = 0; $i < count($values); $i++) {
                $this->array['insert']['value'] = $this->array['insert']['value'] + [$i => []]; //создаем группу значений
                for ($j = 0; $j < count($values[0]); $j++) {
                    $this->array['insert']['value'][$i] = $this->array['insert']['value'][$i] + [$j => $values[$i][$j]]; //запоминаем значения
                }
            }
        }
        return $this;
    }

    function delete($table, $where) //delete('таблица', условия); //удаление
    {
        //delete('таблица', условия); //удаление
        $error = [];
        if (!is_string($table)) //если таблица не имеет строковый формат
        {
            $error[] = 1; // запоминаем ошибку
        }

        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in insert "];
        } else {
            $this->array = $this->array + ['delete' => []];
            $this->array['delete'] = $this->array['delete'] + ['table' => $table]; //запоминаем таблицу
            $this->where($where);
        }
        return $this;
    }
 
    function update($table, $update) //update('таблица', [['поле', 'значение'], [...]])
    {
        //update('таблица', [['поле', 'значение'], [...]])
        $error = [];
        if (!is_string($table) //если таблица имеет не строковый формат
            or !is_array($update))  // или если необходимые изменения не в виде групп в массиве
        {
            $error[] = 1;// запоминаем ошибку
        } else {
            for ($i = 0; $i < count($update); $i++) { //рассматриваем каждую группу необходимых изменений
                if (!is_array($update[$i])  //если группа - не массив
                    or count($update[$i]) != 2 //или если в группе не 2 элемента
                    or !is_string($update[$i][0]) //или если оба элемента не строковые
                    or !is_string($update[$i][1])) {
                    $error[] = 1;// запоминаем ошибку
                }
            }
        }
        if (in_array(1, $error)) {
            $this->error = $this->error + [count($this->error) => "erroneous amount of input data in update "];
        } else {
            $this->array = $this->array + ['update' => []];
            $this->array['update'] = $this->array['update'] + ['table' => $table]; //запоминаем таблицу
            $this->array['update'] = $this->array['update'] + ['update' => []]; // создаем массив для групп необходимых изменений
            for ($k = 0; $k < count($update); $k++) {//рассматриваем каждую группу необходимых изменений
                {
                    $this->array['update']['update'] = $this->array['update']['update'] + [$k => []];
                    $wh = array('field', 'value');
                    for ($j = 0; $j < 2; $j++) { //запоминаем поле и значение в каждой группе
                        $this->array['update']['update'][$k] = $this->array['update']['update'][$k] + [$wh[$j] => $update[$k][$j]];
                    }
                }
            }
        }
        return $this;
    }

    function createSQL() //составление запроса
    {
        if (count($this->error) == 0) {
            $sql=null;
            if (array_key_exists('select', $this->array)) {
                $sql = 'SELECT';

                if (array_key_exists('distinct', $this->array)) {
                    $sql = $sql . ' ' . 'DISTINCT';
                }

                if (array_key_exists('name_fild', $this->array) && count($this->array['name_fild']) == count($this->array)) {
                    for ($i = 0; $i < count($this->array['name_fild']); $i++) {
                        $ar_str_select[] =
                            $this->array['select'][$i]['selNameTable'] .
                            '.' .
                            $this->array['select'][$i]['selField'] .
                            ' ' . 'AS' . ' ' .
                            '`' . $this->array['name_fild'][$i] . '`';
                    }
                    $str_select = implode(' , ', $ar_str_select);
                    $sql = $sql . ' ' . $str_select;
                } else {
                    for ($i = 0; $i < count($this->array['select']); $i++) {
                        $ar_str_select[] =
                            $this->array['select'][$i]['selNameTable'] .
                            '.' .
                            $this->array['select'][$i]['selField'];
                    }
                    $str_select = implode(' , ', $ar_str_select);
                    $sql = $sql . ' ' . $str_select;
                }

                for ($i = 0; $i < count($this->array['select']); $i++) {
                    if (array_key_exists('join', $this->array)) {
                        if ($this->array['join']['table_2'] == $this->array['select'][$i]['selTable']) {
                            $joinTable = $this->array['select'][$i]['selTable'] . ' AS ' . $this->array['select'][$i]['selNameTable'];
                            $joinValue_2 = $this->array['select'][$i]['selNameTable'] . '.' . $this->array['join']['value_2'];
                        } else {
                            $ar_from_select[] = $this->array['select'][$i]['selTable'] . ' AS ' . $this->array['select'][$i]['selNameTable'];
                        }
                        if ($this->array['join']['table_1'] == $this->array['select'][$i]['selTable']) {
                            $joinValue_1 = $this->array['select'][$i]['selNameTable'] . '.' . $this->array['join']['value_1'];
                        }
                    } else {
                        $ar_from_select[] = $this->array['select'][$i]['selTable'] . ' AS ' . $this->array['select'][$i]['selNameTable'];
                    }
                }

                $str_from = implode(', ', array_unique($ar_from_select));
                $sql = $sql . ' FROM ' . $str_from;

                if (array_key_exists('join', $this->array)) {
                    $sql =
                        $sql . ' ' . $this->array['join']['join'] . ' ' .
                        ' JOIN ' . $joinTable .
                        ' ON ' . $joinValue_1 . ' = ' . $joinValue_2;
                }

                if (array_key_exists('where', $this->array)) {
                    for ($q = 0; $q < count($this->array['where']); $q++) {
                        $ar_str_where = [];
                        for ($k = 0; $k < count($this->array['where'][$q]['where']); $k++) {

                            if ($this->array['where'][$q]['where'][$k]['operand'] == 'is') {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] .
                                    '` IS ' . $this->array['where'][$q]['where'][$k]['value'];
                            } elseif ($this->array['where'][$q]['where'][$k]['operand'] == 'between') {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] . '` BETWEEN "' .
                                    $this->array['where'][$q]['where'][$k]['value_1'] . '" AND "' .
                                    $this->array['where'][$q]['where'][$k]['value_2'] . '"';
                            } else {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] . '` ' .
                                    $this->array['where'][$q]['where'][$k]['operand'] .
                                    ' "' . $this->array['where'][$q]['where'][$k]['value'] . '"';
                            }
                        }
                        $ar_where[] = implode(' ' . $this->array['where'][$q]['operand'] . ' ', $ar_str_where);
                    }
                    $str_where = implode(') AND ( ', $ar_where);
                    $sql = $sql . ' ' . 'WHERE (' . $str_where . ')';
                }
                if (array_key_exists('order', $this->array)) {
                    $sql = $sql . ' ' . 'ORDER BY';
                    for ($i = 0; $i < count($this->array['order']); $i++) {
                        $ar_str_order[] =
                            ' ' . '`' . $this->array['order'][$i]['orderField'] . '`' .
                            ' ' . $this->array['order'][$i]['orderBy'] . ' ';
                    }
                    $str_order = implode(' , ', $ar_str_order);
                    $sql = $sql . " " . $str_order;

                }

            } elseif (array_key_exists('insert', $this->array)) {
                for ($i = 0; $i < count($this->array['insert']['field']); $i++) {
                    $ar_insert_field[] = '`' . $this->array['insert']['field'][$i] . '`';
                }
                for ($i = 0; $i < count($this->array['insert']['value']); $i++) {
                    for ($j = 0; $j < count($this->array['insert']['value'][$i]); $j++) {
                        $ar_insert_value[$i][] = '"' . $this->array['insert']['value'][$i][$j] . '"';
                    }
                }
                for ($i = 0; $i < count($this->array['insert']['value']); $i++) {
                    $insert_value[$i] = implode(' , ', $ar_insert_value[$i]);
                }
                $insert_field = implode(' , ', $ar_insert_field);
                $insert_value = implode(' ), ( ', $insert_value);

                $sql =
                    'INSERT INTO '
                    . $this->array['insert']['table']
                    . ' ('
                    . $insert_field
                    . ') VALUES ('
                    . $insert_value
                    . ')';

            } elseif (array_key_exists('delete', $this->array)) {
                for ($q = 0; $q < count($this->array['where']); $q++) {
                    $ar_str_where = [];
                    for ($k = 0; $k < count($this->array['where'][$q]['where']); $k++) {

                        if ($this->array['where'][$q]['where'][$k]['operand'] == 'is') {
                            $ar_str_where[] =
                                '`' . $this->array['where'][$q]['where'][$k]['field'] .
                                '` IS ' . $this->array['where'][$q]['where'][$k]['value'];
                        } elseif ($this->array['where'][$q]['where'][$k]['operand'] == 'between') {
                            $ar_str_where[] =
                                '`' . $this->array['where'][$q]['where'][$k]['field'] . '` BETWEEN "' .
                                $this->array['where'][$q]['where'][$k]['value_1'] . '" AND "' .
                                $this->array['where'][$q]['where'][$k]['value_2'] . '"';
                        } else {
                            $ar_str_where[] =
                                '`' . $this->array['where'][$q]['where'][$k]['field'] . '` ' .
                                $this->array['where'][$q]['where'][$k]['operand'] .
                                ' "' . $this->array['where'][$q]['where'][$k]['value'] . '"';
                        }
                    }
                    $ar_where[] = implode(' ' . $this->array['where'][$q]['operand'] . ' ', $ar_str_where);
                }
                $str_where = implode(') AND ( ', $ar_where);
                $sql =
                    'DELETE FROM '
                    . $this->array['delete']['table']
                    . ' ' . 'WHERE (' . $str_where . ')';

            } elseif (array_key_exists('update', $this->array)) {
                for ($i = 0; $i < count($this->array['update']['update']); $i++) {
                    $ar_str_update[] =
                        '`' . $this->array['update']['update'][$i]['field'] . '`'. 
						' = "' . $this->array['update']['update'][$i]['value']. '"';
                }
                $str_update = implode(' , ', $ar_str_update);
                $sql =
                    'UPDATE '
                    . $this->array['update']['table']
                    . ' SET ' .
                    $str_update;

                if (array_key_exists('where', $this->array)) {
                    for ($q = 0; $q < count($this->array['where']); $q++) {
                        $ar_str_where = [];
                        for ($k = 0; $k < count($this->array['where'][$q]['where']); $k++) {

                            if ($this->array['where'][$q]['where'][$k]['operand'] == 'is') {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] .
                                    '` IS ' . $this->array['where'][$q]['where'][$k]['value'];
                            } elseif ($this->array['where'][$q]['where'][$k]['operand'] == 'between') {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] . '` BETWEEN "' .
                                    $this->array['where'][$q]['where'][$k]['value_1'] . '" AND "' .
                                    $this->array['where'][$q]['where'][$k]['value_2'] . '"';
                            } else {
                                $ar_str_where[] =
                                    '`' . $this->array['where'][$q]['where'][$k]['field'] . '` ' .
                                    $this->array['where'][$q]['where'][$k]['operand'] .
                                    ' "' . $this->array['where'][$q]['where'][$k]['value'] . '"';
                            }
                        }
                        $ar_where[] = implode(' ' . $this->array['where'][$q]['operand'] . ' ', $ar_str_where);
                    }
                    $str_where = implode(') AND ( ', $ar_where);
                    $sql = $sql . ' ' . 'WHERE (' . $str_where . ')';
                }
            }
            $this->sql = $sql;
        }
        return $this->sql;
    }

    function request()
    {
            if ($this->sql==null) {
                $error=null;
                for ($i = 0; $i < count($this->error); $i++) {
                    $error = $error . "<br>" . $this->error[$i];
                }
                $result = $error;
            }
            else{
                mysqli_query($this->conn, $this->sql);
                $result=$this->sql;
            }
        return $result;
    }
}



