<?php

//include ("data.php");

$dbconn = pg_connect("dbname=tpch user=dbms password=dbms") or die(pg_last_error());

$query =
"SELECT
    t.t_train_num AS train_num,
    count(*) AS number
FROM
    ordering AS o,
    has_ticket AS ht,
    ticket AS t
WHERE
    o.o_status='Normal' AND
    o.o_id=ht.ht_order_id AND
    ht.ht_ticket_id=t.t_id
GROUP BY
    t.t_train_num
ORDER BY
    number DESC
LIMIT 10;
";

$result = pg_query($query);
$table = pg_fetch_all($result);
pg_freeresult($result);
pg_close($dbconn);

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <body>
        <div>最热门的10个车次是:</div>
        <table>
            <tr>
                <td>排名</td> <td>车次</td> <td>订单数</td>
            </tr>
            <?php
                $len = count($table);
                for($i = 0; $i < $len; $i ++){
                    $row = $table[$i];
                    echo "<tr>";
                    echo "\t<td>";
                    echo ($i + 1);
                    echo "</td>";
                    echo "<td>";
                    echo $row["train_num"];
                    echo "</td>";
                    echo "<td>";
                    echo $row["number"];
                    echo "</td>";
                    echo "</tr>\n";
                }
            ?>
        </table>
    </body>
</html>
