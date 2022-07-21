<?php

include ("data.php");

$phone = $_GET['phone'];

$dbconn = pg_connect("host={$host} dbname={$dbname} user={$user} port={$port} password={$passwd}") or die(pg_last_error());

$query = "SELECT * FROM ordering WHERE o_pas_phone='{$phone}';";

$result = pg_query($query);
$table = pg_fetch_all($result);
pg_freeresult($result);
pg_close($dbconn);

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <body>
        <div>手机号为<?php echo $phone; ?>的用户订单如下:</div>
        <table>
            <tr>
                <td>订单编号</td> <td>订单总价</td> <td>订单时间</td> <td>订单状态</td>
            </tr>
            <?php
                $len = count($table);
                for($i = 0; $i < $len; $i ++){
                    $row = $table[$i];
                    echo "<tr>";
                    echo "\t<td>{$row['o_id']}</td>";
                    echo "\t<td>{$row['o_price']}</td>";
                    echo "\t<td>{$row['o_buy_time']}</td>";
                    echo "\t<td>{$row['o_status']}</td>";
                    echo "</tr>\n";
                }
            ?>
        </table>
    </body>
</html>
