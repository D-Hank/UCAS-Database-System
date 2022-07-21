<?php

include ("data.php");

$sdate = $_POST['sdate'];
$edate = $_POST['edate'];
$phone = $_POST['phone'];

$dbconn = pg_connect("host={$host} dbname={$dbname} user={$user} port={$port} password={$passwd}") or die(pg_last_error());

$query =
"SELECT
	o.o_id AS order_id,
	t.t_start_date AS depart_date,
	t.t_train_num AS train_num,
	st1.st_name AS depart_station,
	st2.st_name AS arrive_station,
	o.o_price AS price,
	o.o_status AS order_status,
	o.o_buy_time AS buy_time
FROM
	ordering AS o,
	has_ticket AS ht,
	ticket AS t,
	scheduler AS sc1,
	scheduler AS sc2,
	station AS st1,
	station AS st2
WHERE
	o.o_id=ht.ht_order_id AND ht.ht_ticket_id=t.t_id AND
	t.t_start_date>='{$sdate}' AND t.t_start_date<='{$edate}' AND
	t.t_int_start=sc1.sc_station_id AND t.t_train_num=sc1.sc_train_num AND
	sc1.sc_station_id=st1.st_id AND
	t.t_int_end=sc2.sc_station_id AND t.t_train_num=sc2.sc_train_num AND
	sc2.sc_station_id=st2.st_id AND o.o_pas_phone='{$phone}'
";
?>

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <body>
        <div>您选择的订单日期是：<?php echo "{$sdate} - {$edate}"; ?></div>
        <?php
            $result = pg_query($query) or die("Query failed: ".pg_last_error());
            $table = pg_fetch_all($result);
			pg_freeresult($result);
			pg_close($dbconn);
        ?>
		<table style="text-align: middle; width: 90%;">
			<tr>
				<td>订单编号</td> <td>车次</td> <td>出发时间</td> <td>出发站</td> <td>到达站</td> <td>总票价</td> <td>订单时间</td> <td>订单状态</td> <td>操作</td>
			</tr>
			<?php
				$last_order = "0";
				$len = count($table);
				$can_cancel = true;
				for($i = 0; $i < $len; $i ++){
					$row = $table[$i];
					echo "<tr>";
					echo "\t<td>"; echo ($row['order_id'] != $last_order) ? $row['order_id'] : ' '; echo "</td>";

					echo "\t<td>";
					echo "<a href=4_train.php?train={$row['train_num']}&date={$row['depart_date']}>";
					echo "{$row['train_num']}";
					echo "</a>";
					echo "</td>";
					echo "\t<td name='depart_id_{$row['order_id']}'>{$row['depart_date']}</td>";

					echo "\t<td> {$row['depart_station']} </td>";
					echo "\t<td> {$row['arrive_station']} </td>";

					if($row['order_id'] != $last_order){
						echo "\t<td> {$row['price']} </td>";
						echo "\t<td> {$row['buy_time']} </td>";
						echo "\t<td> {$row['order_status']} </td>";
						if($row['order_status'] == "Normal"){
							echo "\t<td> <input type='button' id='confirm' onclick='check_cancel({$row['order_id']})' value=取消订单></input> </td>";
						}else{
							echo "\t<td> </td>";
						}
					}else{
						echo "\t<td> </td>";
						echo "\t<td> </td>";
						echo "\t<td> </td>";
						echo "\t<td> </td>";
					}

					$last_order = $row['order_id'];
					echo "</tr>\n";
				}
			?>

			<script>
				// A simple check
				function check_cancel(order_id){
					var all_depart = document.getElementsByName("depart_id_" + order_id);
					var len = all_depart.length;
					var count = 0;
					var now = new Date();
					for(var i = 0; i < len; i++){
						var depart_date = new Date(all_depart[i].innerHTML);
						if(depart_date < now){
							count++;
						}
					}

					if(count < len){
						alert("无法取消当天前的订单！");
					}else{
						if(confirm("确定要取消该订单吗？")){
							location.href = "8_cancel.php?order_id=" + order_id;
						}
					}
				}
			</script>
    </body>
</html>
