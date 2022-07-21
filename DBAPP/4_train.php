<?php

include ("data.php");

if (count($_GET) > 0) {
	$INPUT_train_num = $_GET["train"];
	$INPUT_ddate = $_GET["date"];
}else{
	$INPUT_train_num = $_POST["train"];
	$INPUT_ddate = $_POST["date"];
}

echo "您查询的车次是：";
echo $INPUT_train_num;
echo "<br>";
echo "您选择的出发日期是：";
echo $INPUT_ddate;
echo "<br>";

$dbconn = pg_connect("host={$host} dbname={$dbname} user={$user} port={$port} password={$passwd}") or die(pg_last_error());

$view = 
"CREATE VIEW train_stations AS
SELECT
    st.st_name AS station, 
    sc.sc_rank AS _rank
FROM
    scheduler AS sc,
    station AS st
WHERE
    sc.sc_train_num = '$INPUT_train_num' AND
    sc.sc_station_id = st.st_id;
";

$drop = "DROP VIEW train_stations";

$result = pg_query($view);
// Duplicated
if($result == false){
	$result = pg_query($drop);
	$result = pg_query($view);
}

$query = 
"SELECT
	ts._rank AS _rank,
	ts.station AS station,
	sc3.sc_depart_time AS depart_time,
	sc3.sc_arrive_time AS arrive_time,
	s.s_seat_type AS seat_type,
	s.s_total_price AS price
FROM
	train_stations AS ts,
	scheduler AS sc1,
	scheduler AS sc2,
	scheduler AS sc3,
	ticket AS t,
	seat AS s
WHERE
	sc1.sc_station_id=t.t_int_start AND sc1.sc_train_num=t.t_train_num AND
	sc2.sc_station_id=t.t_int_end AND sc2.sc_train_num=t.t_train_num AND
	sc1.sc_rank=1 AND sc2.sc_rank>=ts._rank AND 
	t.t_status='Available' AND t.t_start_date='$INPUT_ddate' AND
    t.t_train_num='$INPUT_train_num' AND
-- sc1 for start, sc2 for middle, sc3 for seat
	sc3.sc_train_num='$INPUT_train_num' AND sc3.sc_rank=ts._rank AND
    sc3.sc_train_num=s.s_train_num AND sc3.sc_station_id=s.s_station_id AND
    t.t_int_seat=s.s_seat_type AND
-- choose tickets for sale
	(s.s_total_price > 0.0 OR sc3.sc_rank=1)
ORDER BY
	ts._rank ASC,
	s.s_seat_type DESC

";

$result = pg_query($query) or die('Query failed: '.pg_last_error());
$table = pg_fetch_all($result);

make_table($table);

pg_free_result($result);
pg_close($dbconn);

function make_table($table){
	$title = array("序号", "站名", "到达时间", "发车时间", "硬座/余", "软座/余", "硬卧上/余", "硬卧中/余", "硬卧下/余", "软卧上/余", "软卧下/余");
	echo "<table style=\"text-align: center; width: 90%\">\n";
	make_title($title);
	make_station($table);
	echo "</table>\n";
}

function make_title($title){
	echo "\t<tr>\n";
	foreach ($title as $elem) {
		echo "\t\t<td> $elem </td>\n";
	}
	echo "</tr>\n";
}

// The first 7 rows belong to the start station
function make_station($table){
	// Init
	$len = count($table); // At least 8
	$name = array("H", "S", "HU", "HM", "HL", "SU", "SL");
	$price = array();
	$rest = array();
	for($i = 0; $i <= 7; $i ++){
		$price[$name[$i]] = "0.00";
		$rest[$name[$i]] = 0;
	}

	$last_rank = "1";
	$last_station = $table[0]["station"];
	$last_arrive = "-";
	$last_depart = $table[0]["depart_time"];
	// Traverse the table
	for($i = 7; $i < $len; $i ++){
		$row = $table[$i];

		if($row["_rank"] != $last_rank){
			// A new rank, output the last row
			echo "\t<tr>\n";
			echo "\t\t<td> {$last_rank} </td>\n";
			echo "\t\t<td> {$last_station} </td>\n";
			if($last_rank == "1"){
				echo "\t\t<td> - </td>\n";
			}else{
				echo "\t\t<td> {$last_arrive} </td>\n";
			}
			echo "\t\t<td> {$last_depart} </td>\n";

			// Output the price and clear
			for($j = 0; $j < 7; $j ++){
				$price_temp = $price[$name[$j]];
				$rest_temp = $rest[$name[$j]];

				if($price_temp == "0.00"){
					echo "\t\t<td> - / - </td>\n";
				}else{
					echo "\t\t<td> <a href=\"book.php?astation={$last_station}&seat_type={$name[$j]}\"> {$price_temp} / {$rest_temp} </a> </td>\n";
				}
				$price[$name[$j]] = "0.00";
				$rest[$name[$j]] = 0;
			}
			echo "\t</tr>\n";

		}

		// Refresh
		$last_rank = $row["_rank"];
		$last_station = $row["station"];
		$last_arrive = $row["arrive_time"];
		$last_depart = $row["depart_time"];
		$price[$row["seat_type"]] = $row["price"];
		if((float)$row["price"] > 0.0){
			$rest[$row["seat_type"]] ++;
		}
	}

	// Last row
	echo "\t<tr>\n";
	echo "\t\t<td> {$last_rank} </td>\n";
	echo "\t\t<td> {$last_station} </td>\n";
	echo "\t\t<td> {$last_arrive} </td>\n";
	echo "\t\t<td> - </td>\n";
	for($j = 0; $j < 7; $j ++){
		$price_temp = $price[$name[$j]];
		$rest_temp = $rest[$name[$j]];

		if($price_temp == "0.00"){
			echo "\t\t<td> - / - </td>\n";
		}else{
			echo "\t\t<td> <a href=\"book.php?astation={$last_station}&seat_type={$name[$j]}\"> {$price_temp} / {$rest_temp} </a> </td>\n";
		}
	}
	echo "\t</tr>\n";
}

?>