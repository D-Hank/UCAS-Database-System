<?php

include ("data.php");

$dbconn = pg_connect("host={$host} dbname={$dbname} user={$user} port={$port} password={$passwd}") or die(pg_last_error());

$query = "SELECT * FROM passenger;";

$result = pg_query($query);
$table = pg_fetch_all($result);
pg_freeresult($result);
pg_close($dbconn);

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <body>
        <div>已经注册的用户:</div>
        <table>
            <tr>
                <td>用户名</td> <td>姓名</td> <td>手机号</td> <td>操作</td>
            </tr>
            <?php
                $len = count($table);
                for($i = 0; $i < $len; $i ++){
                    $row = $table[$i];
                    echo "<tr>";
                    echo "\t<td>{$row['p_user']}</td>";
                    echo "\t<td>{$row['p_name']}</td>";
                    echo "\t<td>{$row['p_phone']}</td>";
                    echo "\t<td>";
                    echo "<a href=9_userorder.php?phone={$row['p_phone']}>查看订单</a>";
                    echo "</td>";
                    echo "</tr>\n";
                }
            ?>
        </table>
    </body>
</html>
