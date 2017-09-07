<?php
/*Суммируем результаты запроса.*/

function do_query_sum($query, $sum)
{
    $query_res =  mysql_query($query);
    while ($row = mysql_fetch_assoc($query_res)) { 
	$sum = $sum + $row['sum'];
    }
    return $sum;
}

// function do_query_array($query, $res)
// {
//     $query_res =  mysql_query($query);
//     while ($row = mysql_fetch_assoc($query_res)) { 
// 	$res[] = $row;
//     }
//     return $res;
// }

?>
