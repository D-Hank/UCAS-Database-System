<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <head>
        <title>Book your ticket</title>
    </head>
    <body>
<?php
    $line = $_GET["line"];
    $seat_type = $_GET["seat_type"];
    $astation = $_GET["astation"];
    $phone = $_COOKIE['phone'];
    $array = unserialize(urldecode($line));
    $length = count($array);
    
    $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());
    if($seat_type && $astation)
    { // need4
        setcookie('astation', $astation);
        
        $query1 =
        "
        SELECT DISTINCT
            train_num,
            ddate,
            station
        FROM
            train_4_ticket
        WHERE
            _rank=1;
        ";
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
        $train_num = pg_fetch_result($result1, 0, 0);
        $ddate = pg_fetch_result($result1, 0, 1);
        $dstation = pg_fetch_result($result1, 0, 2);
        
        $query2 =
        "
        SELECT
            ticket_id AS ticket_id,
            which_seat AS which_seat,
            '{$train_num}' AS train_num,
            date '{$ddate}' AS ddate,
            depart_time AS depart_time,
            '{$dstation}' AS dstation,
            date(date '{$ddate}'+depart_time+total_time) AS adate,
            depart_time+total_time AS atime,
            station AS astation,
            seat_type AS seat_type,
            price AS price
        FROM
            train_4_ticket
        WHERE
            station='{$astation}' AND seat_type='{$seat_type}'
        ORDER BY
            which_seat asc;
        ";
        $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
?>
        车次1
        <table border="1">
			<tr>
                <td>车票编号</td><td>座位号</td>
				<td>车次</td><td>出发日期</td><td>出发时间</td><td>出发站</td>
                <td>到达日期</td><td>到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td>
            </tr>
<?php
        $ticket_set=array();
        $price=0.0;
		while ($line = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
                if($key=='ticket_id')
                {
                    $ticket_set[]=$value;
                }
                if($key=='price')
                {
                    $price=$value;
                }
				echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
?>
		</table>
        注：订票费 5元/车票<br>
        总票价：<?php echo $price+5.0; ?>元 <br>
        请选择需要订购的车票编号：
        <form action="confirm.php" method="post">
            <select name="ticket4">
<?php
        foreach($ticket_set as $which_seat)
        {
            echo "<option value='{$which_seat}'>{$which_seat}</option>";
        }
?>
            </select>
            <input type="submit" value="confirm">
        </form>
        <br>
        <a href=login.php?phone=<?php echo $phone?>>取消预订</a>
<?php
        pg_free_result($result1);
        pg_free_result($result2);
    }
    else if($length<=10) //direct train
    {
        $query1 =
        "
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
            astation='{$array['astation']}' AND seat_type='{$array['seat_type']}'
        ORDER BY
            which_seat asc;
        ";
        
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
?>
        车次1
        <table border="1">
			<tr>
                <td>车票编号</td><td>座位号</td>
				<td>车次</td><td>出发日期</td><td>出发时间</td><td>出发站</td>
                <td>到达日期</td><td>到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td>
            </tr>
<?php
        $ticket_set=array();
        $price=0.0;
		while ($line = pg_fetch_array($result1, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
                if($key=='ticket_id')
                {
                    $ticket_set[]=$value;
                }
                if($key=='price')
                {
                    $price=$value;
                }
				echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
?>
		</table>
        注：订票费 5元/车票<br>
        总票价：<?php echo $price+5.0; ?>元 <br>
        请选择需要订购的车票编号：
        <form action="confirm.php" method="post">
            <select name="ticket">
<?php
        foreach($ticket_set as $which_seat)
        {
            echo "<option value='{$which_seat}'>{$which_seat}</option>";
        }
?>
            </select>
            <input type="submit" value="confirm">
        </form>
        <br>
        <a href=login.php?phone=<?php echo $phone?>>取消预订</a>
<?php
        pg_free_result($result1);
    }
    else
    {   //transfer train
        $query1 =
        "
        SELECT DISTINCT
            t1.ticket_id AS ticket_id,
            t1.which_seat AS which_seat,
            t1.train_num AS train,
            date '{$array['ddate1']}' AS ddate,
            t1.depart_time AS depart_time,
            t1.dstation AS dstation,
            date(date '{$array['ddate1']}'+t1.depart_time+t1.total_time) AS adate,
            t1.depart_time+t1.total_time AS atime,
            t1.astation AS astation,
            t1.seat_type AS seat_type,
            t1.price AS price
        FROM
            transfer_result1 as t1,
            transfer_result2 as t2
        WHERE
            t1.id=t2.id AND
            t1.train_num='{$array['train1']}'AND t1.dstation='{$array['dstation1']}' AND
            t1.astation='{$array['astation1']}' AND t1.seat_type='{$array['seat_type1']}' AND
            t2.train_num='{$array['train2']}'AND t2.dstation='{$array['dstation2']}' AND
            t2.astation='{$array['astation2']}' AND t2.seat_type='{$array['seat_type2']}'
        ORDER BY
            t1.which_seat asc;
        ";
        $query2 =
        "
        SELECT DISTINCT
            t2.ticket_id AS ticket_id,
            t2.which_seat AS which_seat,
            t2.train_num AS train,
            date '{$array['ddate2']}' AS ddate,
            t2.depart_time AS depart_time,
            t2.dstation AS dstation,
            date(date '{$array['ddate2']}'+t2.depart_time+t2.total_time) AS adate,
            t2.depart_time+t2.total_time AS atime,
            t2.astation AS astation,
            t2.seat_type AS seat_type,
            t2.price AS price
        FROM
            transfer_result1 as t1,
            transfer_result2 as t2
        WHERE
            t1.id=t2.id AND
            t1.train_num='{$array['train1']}'AND t1.dstation='{$array['dstation1']}' AND
            t1.astation='{$array['astation1']}' AND t1.seat_type='{$array['seat_type1']}' AND
            t2.train_num='{$array['train2']}'AND t2.dstation='{$array['dstation2']}' AND
            t2.astation='{$array['astation2']}' AND t2.seat_type='{$array['seat_type2']}'
        ORDER BY
            t2.which_seat asc;
        ";
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
        $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
?>
        车次1
        <table border="1">
			<tr>
                <td>车票编号</td><td>座位号</td>
				<td>车次</td><td>出发日期</td><td>出发时间</td><td>出发站</td>
                <td>到达日期</td><td>到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td>
            </tr>
<?php
        $ticket_set1=array();
        $price1=0.0;
		while ($line = pg_fetch_array($result1, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
                if($key=='ticket_id')
                {
                    $ticket_set1[]=$value;
                }
                if($key=='price')
                {
                    $price1=$value;
                }
				echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
?> 
        </table>
        车次2
        <table border="1">
			<tr>
                <td>车票编号</td><td>座位号</td>
				<td>车次</td><td>出发日期</td><td>出发时间</td><td>出发站</td>
                <td>到达日期</td><td>到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td>
            </tr>
<?php
        $ticket_set2=array();
        $price2=0.0;
		while ($line = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
                if($key=='ticket_id')
                {
                    $ticket_set2[]=$value;
                }
                if($key=='price')
                {
                    $price2=$value;
                }
				echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
?>
        </table>
        注：订票费 5元/车票<br>
        总票价：<?php echo $price1+$price2+10.0; ?>元 <br>
        请选择需要订购的车票编号：
        <form action="confirm.php" method="post">
        <select name="ticket1">
<?php
        foreach($ticket_set1 as $which_seat)
        {
            echo "<option value='{$which_seat}'>{$which_seat}</option>";
        }   
?>
        </select>
        <select name="ticket2">
<?php
        foreach($ticket_set2 as $which_seat)
        {
            echo "<option value='{$which_seat}'>{$which_seat}</option>";
        }   
?>
        </select>
        <input type="submit" value="confirm">
        </form>
        <br>
        <a href=login.php?phone=<?php echo $phone?>>取消预订</a>
<?php
        pg_free_result($result1);
        pg_free_result($result2);
    }   
    pg_close($dbconn);
?>

    </body>
</html>