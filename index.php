<?php
require_once 'query_builder.php';
 require_once 'db.conf.php';

/* запрос данных
 select([['поле', 'таблица', 'краткое именование таблицы'], [...], ...])
 nameFild(['Измененные названия полей', ..])
 distinct -> удалить повторяющиеся строки
 join('таблица 1', 'поле таблицы 1', 'таблица 2', 'поле таблицы 2', 'тип join') //объединение таблиц
 where([['соединение AND или OR', [['поле', 'оператор', 'значение'], ... ]] ...])
 orderBy([['поле', 'тип сортировки'], [...] ... ]); //выбор сортировки 
*/

$query = new query_builder($db_config);
$query
    ->select([['name', 'users', 'u'], ['age', 'users', 'u'], ['text', 'messages', 'm']])//выбор полей
    ->nameFild(['Name', 'Age', 'Message'])//выбор новых имен полей
    ->distinct()//удаление повторяющихся строк
    ->join('users', 'id', 'messages', 'user_id', 'left outer')//объединение таблиц
    ->where([['AND', [['name', '<>', 'Anna'], ['text', 'is', 'null']]], ['OR', [['age', 'between', '14', '17'], ['age', 'between', '5', '6']]]])//условия
    ->orderBy([['age', 'ASC'], ['name', 'DESC']]) //сортировка
    ->createSQL(); //оставление sql запроса
$select_rez = $query->request(); //запрос
echo "Запрос данных: <br>"; 
echo $select_rez . "<br>"; //результаты запроса



//добавление строк
// insert('таблица', ['поле 1', 'поле 2', ...], [ ['значение 1', 'значение 2', ... ], [...] ]);

$inset = new query_builder($db_config);
$inset
    ->insert('users', ['age', 'name'], [["11", 'Kate'], ["17", 'Pit']])
    ->createSQL();
$insert_rez = $inset->request();
echo "<br> Добавление строк: <br>";
echo $insert_rez . "<br>";



//удаление строк
//delete('таблица', условия);

$delete = new query_builder($db_config);
$delete
    ->delete('users', [['AND', [['name', '=', '14'], ['age', 'between', '4', '10']]]]) //удаление
    ->createSQL();
$delete_rez = $delete->request();
echo "<br> Удаление строк: <br>";
echo $delete_rez . "<br>";



//изменение строк
//update('таблица', [['поле', 'значение'], [...]]);

$update = new query_builder($db_config);
$update
    ->update('users', [['age', '9'], ['name', 'Tom']])//изменения
    ->where([['AND', [['age', 'between', '5', '14'], ['name', '<>', 'Anna']]], ['OR', [['age', 'between', '1', '15'], ['name', '<>', 'Alex']]]]) //изменения
    ->createSQL();
$update_rez = $update->request();
echo " <br> Изменение строк: <br>";
echo $update_rez . "<br>"; 
?>