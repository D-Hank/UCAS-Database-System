<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <head>
        <title>Book your ticket</title>
    </head>
    <body>
<?php
    $line = $_GET["line"];
    $array = unserialize(urldecode($line));
    $length = count($array);
    
    if($length<=10) //direct train
    {
        $query1 =
        "CREATE view book_direct AS
        SELECT
            ticket_id AS ticket_id,
            which_seat AS which_seat,
            train_num AS train_num,
            date '{$array['ddate']}' AS ddate,
            depart_time AS depart_time,
            dstation AS dstation,
            date(date '{$array['ddate']}'+depart_time+total_time) AS adate,
            depart_time+total_time AS atime,
            astation AS astation,
            seat_type AS seat_type,
            price AS price
        FROM
            final_result
        WHERE
            train_num='{$array['train_num']}'AND dstation='{$array['dstation']}' AND
            astation='{$array['astation']}' AND seat_type='{$array['seat_type']}';
        ";
        echo $query1 . "<br>";
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
?>
        车次1
        <table border="1">
			<tr>
                <td>车票编号</td><td>座位号</td>
				<td>车次</td><td>出发日期</td><td>出发时间</td><td>出发站</td>
                <td>到达日期</td><td>到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td>
<?php
        $seat_set=array();
		while ($line = pg_fetch_array($result1, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
                if($key=='which_seat')
                {
                    $seat_set[]=$value;
                }
				echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
?>
			</tr>
		</table>
        注：订票费 5元/车票<br>
        请选择需要订购的座位：
        <form action="order.php" method="post">
            <select name="role">
<?php
        foreach($seat_set as $which_seat)
        {
            echo "<option value='{$which_seat}'>'{$which_seat}'</option>";
        }
        pg_free_result($result1);
        
        pg_close($dbconn);
    }
?>
            </select>
            <input type="submit" value="confirm">
        </form>
    </body>
</html>