<?php
$dcity = $_GET["dcity"];
$acity = $_GET["acity"];
$dtime = $_GET["time"];
$ddate = $_GET["date"];

function ftype($s)
{
    return '\'' . $s . '\'';
}

$dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

$drop = array(
'
drop view transfer_final_sum_result;','
drop view transfer_sum_result2;','
drop table transfer_result2;','
drop view transfer_sum_result1;','
drop table transfer_result1;','
drop view transfer_train_with_rank_ddate;','
drop view pre_ddate_transfer_same_station;','
drop table good_transfer_train_same_station;','
drop view transfer_train_same_station;','
drop view sum_final_result;','
drop view final_result;','
drop view direct_train_with_rank_ddate;','
drop view pre_ddate;','
drop table good_direct_train;','
drop view direct_train;','
drop view train_same_city;','
drop view pass_end;','
drop view pass_start;','
drop view ticket_rank;'
);

foreach($drop as $drop_item)
{
	pg_query($drop_item);
}



$view1 = 
'
CREATE VIEW ticket_rank AS
SELECT
    t.t_id AS ticket_id,
    sc1.sc_rank AS start_rank,
    sc2.sc_rank AS end_rank
FROM
    ticket AS t,
    scheduler AS sc1,
    scheduler AS sc2
WHERE
    t.t_int_start=sc1.sc_station_id AND t.t_int_end=sc2.sc_station_id AND
    t.t_train_num=sc1.sc_train_num AND t.t_train_num=sc2.sc_train_num;

CREATE VIEW pass_start AS
SELECT 
	sc.sc_train_num AS train_num, 
	st.st_name AS station_name, 
	st.st_id AS station_id
FROM 
	station AS st, 
	scheduler AS sc
WHERE 
	st.st_id=sc.sc_station_id AND st.st_city=' . ftype($dcity) . ';


CREATE VIEW pass_end AS
SELECT 
	sc.sc_train_num AS train_num, 
	st.st_name AS station_name, 
	st.st_id AS station_id
FROM 
	station AS st, 
	scheduler AS sc
WHERE 
	st.st_id=sc.sc_station_id AND st.st_city=' . ftype($acity) . ';


CREATE VIEW train_same_city AS
SELECT 
	sc1.sc_train_num AS train_num1, 
	sc2.sc_train_num AS train_num2, 
	pa1.station_id AS dstation_id, 
	pa2.station_id AS astation_id, 
	sc1.sc_station_id AS mstation_id1, 
	sc2.sc_station_id AS mstation_id2, 
	pa1.station_name AS dstation, 
	pa2.station_name AS astation, 
	st1.st_name AS mstation1, 
	st2.st_name AS mstation2
FROM 
	pass_start AS pa1, 
	pass_end AS pa2, 
	scheduler AS sc1, 
	scheduler AS sc2, 
	station AS st1, 
	station AS st2
WHERE
	pa1.train_num=sc1.sc_train_num AND pa2.train_num=sc2.sc_train_num AND
	sc1.sc_station_id=st1.st_id AND sc2.sc_station_id=st2.st_id AND
	st1.st_city=st2.st_city;


CREATE VIEW direct_train AS
SELECT 
	ts.train_num1 AS train_num, 
	ts.dstation AS dstation, 
	ts.astation AS astation, 
	ts.dstation_id AS dstation_id, 
	ts.astation_id AS astation_id
FROM 
	train_same_city AS ts
WHERE 
	ts.train_num1=ts.train_num2 AND ts.mstation1=ts.mstation2
GROUP BY 
	ts.train_num1, ts.dstation, ts.astation, ts.dstation_id, ts.astation_id;
';

$table =
'
create table good_direct_train(
    train_num varchar(5) NOT NULL,
    dstation varchar(20) NOT NULL,
    astation varchar(20) NOT NULL,
    dstation_id integer NOT NULL,
    astation_id integer NOT NULL,
    seat_type S_T_SEAT NOT NULL,
    price decimal(7,2) NOT NULL,
    depart_time time NOT NULL,
    arrive_time time NOT NULL,
    total_time INTERVAL NOt NULL
);

INSERT INTO good_direct_train
SELECT 
	dt.train_num AS train_num, 
	dt.dstation AS dstation, 
	dt.astation AS astation, 
	dt.dstation_id AS dstation_id, 
	dt.astation_id AS astation_id, 
	s1.s_seat_type AS seat_type,
	s2.s_total_price-s1.s_total_price AS price,
	sc1.sc_depart_time AS depart_time, 
    sc2.sc_arrive_time AS arrive_time,
	(CASE
    	WHEN sc1.sc_depart_time-sc1.sc_arrive_time>=\'00:00\'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time)
     	WHEN sc1.sc_depart_time-sc1.sc_arrive_time<\'00:00\'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time+ INTERVAL \'24:00:00\')
     END
    ) AS total_time
FROM 
	direct_train AS dt, 
	scheduler AS sc1, 
	scheduler AS sc2, 
	seat AS s1, 
	seat AS s2
WHERE 
	dt.train_num=sc1.sc_train_num AND dt.dstation_id=sc1.sc_station_id AND
	dt.train_num=sc2.sc_train_num AND dt.astation_id=sc2.sc_station_id AND
	sc1.sc_rank<=sc2.sc_rank AND
	sc1.sc_depart_time>= ' . ftype($dtime) . ' AND
	sc1.sc_station_id=s1.s_station_id AND s1.s_train_num=sc1.sc_train_num AND
    (s1.s_total_price>0.0 OR sc1.sc_rank=1) AND
	sc2.sc_station_id=s2.s_station_id AND s2.s_train_num=sc2.sc_train_num AND
    (s2.s_total_price>0.0 OR sc2.sc_rank=1) AND
    s1.s_seat_type=s2.s_seat_type;
';

$view2 = 
'
CREATE VIEW pre_ddate AS
SELECT 
	gd.train_num AS train_num,
    gd.dstation AS dstation,
	(CASE
    	WHEN sc.sc_depart_time-sc.sc_arrive_time>=\'00:00\'
     	THEN date(date ' . ftype($ddate) . '+sc.sc_arrive_time-sc.sc_total_time)
     	WHEN sc.sc_depart_time-sc.sc_arrive_time<\'00:00\'
     	THEN date(date ' . ftype($ddate) . '-INTERVAL \'24:00:00\'+sc.sc_arrive_time-sc.sc_total_time)
     END
    ) AS ddate
FROM 
	scheduler AS sc,
	good_direct_train AS gd
WHERE
	sc.sc_train_num=gd.train_num AND sc.sc_station_id=gd.dstation_id
GROUP BY
    gd.train_num, gd.dstation, sc.sc_depart_time, sc.sc_arrive_time, sc.sc_total_time;


create view direct_train_with_rank_ddate AS
SELECT
	gd.train_num AS train_num, 
	gd.dstation AS dstation,
	gd.astation AS astation,
	gd.seat_type AS seat_type,
    gd.price AS price,
    gd.depart_time AS depart_time,
    gd.total_time AS total_time,
	sc1.sc_rank AS start_rank,
    sc2.sc_rank AS end_rank,
    pd.ddate AS ddate
FROM 
	good_direct_train AS gd, 
	scheduler AS sc1, 
	scheduler AS sc2,
    pre_ddate AS pd
WHERE 
	sc1.sc_station_id=gd.dstation_id AND sc1.sc_train_num=gd.train_num AND 	
	sc2.sc_station_id=gd.astation_id AND sc2.sc_train_num=gd.train_num AND
    gd.train_num=pd.train_num AND gd.dstation=pd.dstation;


create view final_result AS
SELECT 
	t.t_id AS ticket_id,
    t.t_train_num AS train_num,
    dtw.dstation AS dstation,
    dtw.astation AS astation,
	dtw.ddate AS ddate,
    dtw.depart_time AS depart_time,
    dtw.total_time AS total_time,
    t.t_int_seat AS seat_type,
    t.t_which_seat AS which_seat,
    dtw.price AS price
FROM 
	ticket AS t,
    ticket_rank AS tr,
    direct_train_with_rank_ddate AS dtw
WHERE 
	tr.ticket_id=t.t_id AND 
    t.t_train_num=dtw.train_num AND 
    t.t_start_date=dtw.ddate AND t.t_int_seat=dtw.seat_type AND
	tr.start_rank<=dtw.start_rank AND tr.end_rank>=dtw.end_rank AND
    t.t_status=\'Available\';


create view sum_final_result AS
SELECT
    train_num,
    dstation,
    astation,
    depart_time,
    total_time,
    seat_type,
    price,
    count(*) AS ticket_left
FROM
    final_result
GROUP BY
    train_num, dstation, astation, depart_time, total_time, seat_type, price
ORDER BY
    price asc, total_time asc, depart_time asc;
';

$view3 =
'
CREATE VIEW transfer_train_same_station AS
SELECT * 
FROM train_same_city 
WHERE 
    train_num1<>train_num2 AND
    mstation1<>dstation AND mstation2<>astation;
';

$table2 =
'
CREATE table good_transfer_train_same_station(
    id integer primary key,
    train1 varchar(5) NOT NULL,
    train2 varchar(5) NOT NULL,
    dstation varchar(20) NOT NULL,
    astation varchar(20) NOT NULL,
    mstation1 varchar(20) NOT NULL,
    mstation2 varchar(20) NOT NULL,
    dstation_id integer NOT NULL,
    astation_id integer NOT NULL,
    mstation_id1 integer NOT NULL,
    mstation_id2 integer NOT NULL,
    seat_type1 S_T_SEAT NOT NULL,
    price1 decimal(7, 2) NOT NULL,
    seat_type2 S_T_SEAT NOT NULL,
    price2 decimal(7, 2) NOT NULL,
    depart_time1 time NOT NULL,
    total_time1 INTERVAL NOT NULL,
    depart_time2 time NOT NULL,
    total_time2 INTERVAL NOT NULL,
    transfer_time INTERVAL NOT NULL
);

INSERT INTO good_transfer_train_same_station
SELECT DISTINCT
    row_number() over(order by ts.train_num1) AS id,
	ts.train_num1 AS train1, 
	ts.train_num2 AS train2, 
	ts.dstation AS dstation, 
	ts.astation AS astation, 
	ts.mstation1 AS mstation1,
    ts.mstation2 AS mstation2, 
	ts.dstation_id AS dstation_id, 
	ts.astation_id AS astation_id, 
	ts.mstation_id1 AS mstation_id1,
    ts.mstation_id2 AS mstation_id2,
	s2.s_seat_type AS seat_type1,
	s2.s_total_price-s1.s_total_price AS price1,
	s3.s_seat_type AS seat_type2,
	s4.s_total_price-s3.s_total_price AS price2,
    sc1.sc_depart_time AS depart_time1,
	(CASE
    	WHEN sc1.sc_depart_time-sc1.sc_arrive_time>=\'00:00\'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time)
     	WHEN sc1.sc_depart_time-sc1.sc_arrive_time<\'00:00\'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time+INTERVAL \'24:00:00\')
     END
    ) AS total_time1,
    sc3.sc_depart_time AS depart_time2,
    (CASE
    	WHEN sc3.sc_depart_time-sc3.sc_arrive_time>=\'00:00\'
     	THEN sc4.sc_total_time-sc3.sc_total_time
     		 -(sc3.sc_depart_time-sc3.sc_arrive_time)
     	WHEN sc3.sc_depart_time-sc3.sc_arrive_time<\'00:00\'
     	THEN sc4.sc_total_time-sc3.sc_total_time
     		 -(sc3.sc_depart_time-sc3.sc_arrive_time+INTERVAL \'24:00:00\')
     END
    ) AS total_time2,
    (CASE
    	WHEN sc3.sc_depart_time-sc2.sc_arrive_time>\'00:00\'
     	THEN sc3.sc_depart_time-sc2.sc_arrive_time
     	WHEN sc3.sc_depart_time-sc2.sc_arrive_time<\'00:00\'
     	THEN sc3.sc_depart_time-sc2.sc_arrive_time+INTERVAL \'24:00:00\'
     END
    ) AS transfer_time
FROM 
	transfer_train_same_station AS ts, 
	scheduler AS sc1, 	-- 第一趟车上车站
	scheduler AS sc2, 	-- 第一趟车下车站
	scheduler AS sc3, 	-- 第二趟车上车站
	scheduler AS sc4,	-- 第二趟车下车站
	seat AS s1,
	seat AS s2,
	seat AS s3,
	seat AS s4
WHERE 
	ts.train_num1=sc1.sc_train_num AND ts.dstation_id=sc1.sc_station_id AND
	ts.train_num1=sc2.sc_train_num AND ts.mstation_id1=sc2.sc_station_id AND
	ts.train_num2=sc3.sc_train_num AND ts.mstation_id2=sc3.sc_station_id AND
	ts.train_num2=sc4.sc_train_num AND ts.astation_id=sc4.sc_station_id AND
	( 
        (ts.mstation_id2=ts.mstation_id1 AND
         (sc3.sc_depart_time-sc2.sc_arrive_time>=\'1:00:00\') AND
         (sc3.sc_depart_time-sc2.sc_arrive_time<=\'4:00:00\') OR
         (sc3.sc_depart_time-sc2.sc_arrive_time+\'24:00:00\'>=\'1:00:00\') AND
         (sc3.sc_depart_time-sc2.sc_arrive_time+\'24:00:00\'<=\'4:00:00\')
        ) OR
        (
         (sc3.sc_depart_time-sc2.sc_arrive_time>=\'2:00:00\') AND
         (sc3.sc_depart_time-sc2.sc_arrive_time<=\'4:00:00\') OR
         (sc3.sc_depart_time-sc2.sc_arrive_time+\'24:00:00\'>=\'2:00:00\') AND
         (sc3.sc_depart_time-sc2.sc_arrive_time+\'24:00:00\'<=\'4:00:00\')
        )
    ) AND
	sc1.sc_depart_time>='. ftype($dtime) .' AND
	sc1.sc_train_num=s1.s_train_num AND sc1.sc_station_id=s1.s_station_id AND
	sc2.sc_train_num=s2.s_train_num AND sc2.sc_station_id=s2.s_station_id AND
	sc3.sc_train_num=s3.s_train_num AND sc3.sc_station_id=s3.s_station_id AND
	sc4.sc_train_num=s4.s_train_num AND sc4.sc_station_id=s4.s_station_id AND
	(s1.s_total_price>0.0 OR sc1.sc_rank=1) AND
	(s2.s_total_price>0.0 OR sc2.sc_rank=1) AND
	(s3.s_total_price>0.0 OR sc3.sc_rank=1) AND
	(s4.s_total_price>0.0 OR sc4.sc_rank=1) AND
	s2.s_seat_type=s1.s_seat_type AND s4.s_seat_type=s3.s_seat_type AND
    sc1.sc_rank<sc2.sc_rank AND sc3.sc_rank<sc4.sc_rank;
';

$view4 = 
'
CREATE VIEW pre_ddate_transfer_same_station AS
SELECT DISTINCT
    gt.train1 AS train1,
    gt.dstation AS dstation1,
	(CASE
    	WHEN sc1.sc_depart_time-sc1.sc_arrive_time>=\'00:00\'
     	THEN date(date '. ftype($ddate) .'+sc1.sc_arrive_time-sc1.sc_total_time)
     	WHEN sc1.sc_depart_time-sc1.sc_arrive_time<\'00:00\'
     	THEN date(date '. ftype($ddate) .'-INTERVAL \'24:00:00\'+sc1.sc_arrive_time-sc1.sc_total_time)
     END
    ) AS ddate1,
    gt.train2 AS train2,
    gt.mstation2 AS dstation2,
    (CASE
    	WHEN sc2.sc_depart_time-sc2.sc_arrive_time>=\'00:00\'
     	THEN date(date '. ftype($ddate) .'+sc1.sc_depart_time+gt.total_time1+
                  gt.transfer_time-(sc2.sc_depart_time-sc2.sc_arrive_time)
                  -sc2.sc_total_time)
     	WHEN sc2.sc_depart_time-sc2.sc_arrive_time<\'00:00\'
     	THEN date(date '. ftype($ddate) .'+sc1.sc_depart_time+gt.total_time1+
                  gt.transfer_time-(INTERVAL \'24:00:00\'+sc2.sc_depart_time-
                  sc2.sc_arrive_time)-sc2.sc_total_time)
     END
    ) AS ddate2
FROM 
	good_transfer_train_same_station AS gt,
	scheduler AS sc1,
	scheduler AS sc2
WHERE
	sc1.sc_train_num=gt.train1 AND sc1.sc_station_id=gt.dstation_id AND
	sc2.sc_train_num=gt.train2 AND sc2.sc_station_id=gt.mstation_id2;

--transfer_time add into total_time2
create view transfer_train_with_rank_ddate AS
SELECT
    gt.id AS id,
	gt.train1 AS train1, 
    gt.train2 AS train2,
	gt.dstation AS dstation,
	gt.astation AS astation,
	gt.mstation1 AS mstation1,
    gt.mstation2 AS mstation2,
    gt.seat_type1 AS seat_type1,
    gt.seat_type2 AS seat_type2,
    gt.price1 AS price1,
    gt.price2 AS price2,
    gt.depart_time1 AS depart_time1,
    gt.depart_time2 As depart_time2,
    gt.total_time1 AS total_time1,
    gt.total_time2 AS total_time2,
    gt.transfer_time AS transfer_time,
    sc1.sc_rank AS start_rank1,
    sc2.sc_rank AS end_rank1,
    sc3.sc_rank AS start_rank2,
    sc4.sc_rank AS end_rank2,
    pd.ddate1 AS ddate1,
    pd.ddate2 As ddate2
FROM 
	good_transfer_train_same_station AS gt, 
	scheduler AS sc1, 
	scheduler AS sc2,
    scheduler AS sc3,
    scheduler AS sc4,
    pre_ddate_transfer_same_station AS pd
WHERE 
	sc1.sc_station_id=gt.dstation_id AND sc1.sc_train_num=gt.train1 AND 	
    sc2.sc_station_id=gt.mstation_id1 AND sc2.sc_train_num=gt.train1 AND
	sc3.sc_station_id=gt.mstation_id2 AND sc3.sc_train_num=gt.train2 AND
    sc4.sc_station_id=gt.astation_id AND sc4.sc_train_num=gt.train2 AND
    gt.train1=pd.train1 AND gt.dstation=pd.dstation1 AND 
    gt.train2=pd.train2 AND gt.mstation2=pd.dstation2;
';

$table3=
'
create table transfer_result1(
    id integer NOT NULL,
    ticket_id integer NOT NULL,
    train_num varchar(5) NOT NULL,
    dstation varchar(20) NOT NULL,
    astation varchar(20) NOT NULL,
    ddate date NOT NULL,
    depart_time time NOT NULL,
    total_time INTERVAL NOT NULL,
    transfer_time INTERVAL NOT NULL,
    seat_type S_T_SEAT NOT NULL,
    which_seat T_WHICH NOT NULL,
    price decimal(7, 2) NOT NULL
);

INSERT INTO transfer_result1
SELECT DISTINCT --换乘前半趟的票与后半趟无关
    dtw.id AS id,
    t.t_id AS ticket_id,
    t.t_train_num AS train_num,
    dtw.dstation AS dstation,
    dtw.mstation1 AS astation,
    dtw.ddate1 AS ddate,
    dtw.depart_time1 AS depart_time,
    dtw.total_time1 AS total_time,
    dtw.transfer_time AS transfer_time,
    t.t_int_seat AS seat_type,
    t.t_which_seat AS which_seat,
    dtw.price1 AS price
FROM 
	ticket AS t,
    ticket_rank AS tr,
    transfer_train_with_rank_ddate AS dtw
WHERE 
	tr.ticket_id=t.t_id AND 
    t.t_train_num=dtw.train1 AND 
    t.t_start_date=dtw.ddate1 AND t.t_int_seat=dtw.seat_type1 AND
	tr.start_rank<=dtw.start_rank1 AND tr.end_rank>=dtw.end_rank1 AND
    t.t_status=\'Available\';

create table transfer_result2(
    id integer NOT NULL,
    ticket_id integer NOT NULL,
    train_num varchar(5) NOT NULL,
    dstation varchar(20) NOT NULL,
    astation varchar(20) NOT NULL,
    ddate date NOT NULL,
    depart_time time NOT NULL,
    total_time INTERVAL NOT NULL,
    transfer_time INTERVAL NOT NULL,
    seat_type S_T_SEAT NOT NULL,
    which_seat T_WHICH NOT NULL,
    price decimal(7, 2) NOT NULL
);

INSERT INTO transfer_result2
SELECT DISTINCT --换乘后半趟的票与前半趟无关
    dtw.id AS id,
    t.t_id AS ticket_id,
    t.t_train_num AS train_num,
    dtw.mstation2 AS dstation,
    dtw.astation AS astation,
    dtw.ddate2 AS ddate,
    dtw.depart_time2 AS depart_time,
    dtw.total_time2 AS total_time,
    dtw.transfer_time AS transfer_time,
    t.t_int_seat AS seat_type,
    t.t_which_seat AS which_seat,
    dtw.price2 AS price
FROM 
	ticket AS t,
    ticket_rank AS tr,
    transfer_train_with_rank_ddate AS dtw
WHERE 
	tr.ticket_id=t.t_id AND 
    t.t_train_num=dtw.train2 AND 
    t.t_start_date=dtw.ddate2 AND t.t_int_seat=dtw.seat_type2 AND
	tr.start_rank<=dtw.start_rank2 AND tr.end_rank>=dtw.end_rank2 AND
    t.t_status=\'Available\';
';

$view5=
'
create view transfer_sum_result1 AS
SELECT
    id,
    train_num,
    dstation,
    astation,
    depart_time,
    ddate,
    total_time,
	transfer_time,
    seat_type,
    price,
    count(*) AS ticket_left
FROM
    transfer_result1
GROUP BY
	transfer_time, id, train_num, dstation, astation, depart_time, total_time, seat_type, price, ddate;


create view transfer_sum_result2 AS
SELECT
    id,
    train_num,
    dstation,
    astation,
    depart_time,
    ddate,
    total_time,
	transfer_time,
    seat_type,
    price,
    count(*) AS ticket_left
FROM
    transfer_result2
GROUP BY
	transfer_time, id, train_num, dstation, astation, depart_time, total_time, seat_type, price, ddate;


create view transfer_final_sum_result AS
SELECT 
	tr1.train_num AS train1,
	tr1.dstation AS dstation1,
	tr1.astation AS astation1,
	date ' . ftype($ddate) .' AS ddate1,
	tr1.depart_time AS depart_time1,
	tr1.total_time AS total_time1,
	tr1.seat_type AS seat_type1,
	tr1.price AS price1,
	tr1.transfer_time AS transfer_time,
	tr2.train_num AS train2,
	tr2.dstation AS dstation2,
	tr2.astation AS astation2,
	date(date' . ftype($ddate) . '+tr1.depart_time+tr2.total_time+tr1.total_time+tr1.transfer_time) AS ddate2,
	tr2.depart_time AS depart_time2,
	tr2.total_time AS total_time2,
	tr2.seat_type AS seat_type2,
	tr2.price AS price2,
	( CASE
		WHEN tr1.ticket_left<tr2.ticket_left
		THEN tr1.ticket_left
		ELSE tr2.ticket_left
	  END
	) AS ticket_left
FROM 
	transfer_sum_result1 AS tr1,
	transfer_sum_result2 AS tr2
WHERE 
	tr1.id=tr2.id
ORDER BY
	(tr1.price+tr2.price) asc,
	(tr1.total_time+tr2.total_time+tr1.transfer_time) asc,
	tr1.depart_time asc,
	tr2.depart_time asc;
';

pg_query($view1) or die('Query failed: ' . pg_last_error());
pg_query($table) or die('Query failed: ' . pg_last_error());
pg_query($view2) or die('Query failed: ' . pg_last_error());
pg_query($view3) or die('Query failed: ' . pg_last_error());
pg_query($table2) or die('Query failed: ' . pg_last_error());
pg_query($view4) or die('Query failed: ' . pg_last_error());
pg_query($table3) or die('Query failed: ' . pg_last_error());
pg_query($view5) or die('Query failed: ' . pg_last_error());

$query1 = 
'
SELECT * FROM sum_final_result LIMIT 10;
';

$query2 = 
'
SELECT * FROM transfer_final_sum_result LIMIT 10;
';
$result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
$result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());

?>
<!DOCTYPE html>
<html>
	<title>Find your train</title>
	<body>
		出发城市：<?php echo $dcity?> <br>
		到达城市：<?php echo $acity?> <br>
		出发日期：<?php echo $ddate?> <br>
		出发时间：<?php echo $dtime?> <br>
		<a href=back.php?dcity=<?php echo $acity?>&acity=<?php echo $dcity?>&date=<?php echo $ddate?>&time=<?php echo $dtime?>>
		返程订票</a><br>
		直达列车：<br>
		<table border="1">
			<tr>
				<td>车次</td><td>出发站</td><td>到达站</td><td>出发时间</td><td>总耗时</td><td>座位类型</td><td>票价</td><td>余票</td>
	<?php
		while ($line = pg_fetch_array($result1, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
				if($key=='ticket_left')
				{
					$str = base64_encode(serialize($line));
					echo '<td><a href=test2.php?line=' . $str . '>' . $value . '</a></td>';
				}
				else
					echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
	?>
			</tr>
		</table>
		
		换乘列车：<br>
		<table border="1">
			<tr>
				<td>车次1</td><td>出发站1</td><td>到达站1</td><td>出发日期1</td><td>出发时间1</td><td>总耗时1</td><td>座位类型1</td><td>票价1</td><td>换乘时间</td>
				<td>车次2</td><td>出发站2</td><td>到达站2</td><td>出发日期2</td><td>出发时间2</td><td>总耗时2</td><td>座位类型2</td><td>票价2</td><td>总余票</td>
	<?php
		while ($line = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
			echo "<tr>";
			
			foreach($line as $key=>$value)
			{
				if($key=='ticket_left')
				{
					$str = base64_encode(serialize($line));
					echo '<td><a href=test2.php?line=' . $str . '>' . $value . '</a></td>';
				}
				else
					echo '<td>' . $value . '</td>';
			}
			echo "</tr>";
		}
	?>
			</tr>
		</table>
	</body>
</html>
<?php

pg_free_result($result1);
pg_free_result($result2);

pg_close($dbconn);

?>