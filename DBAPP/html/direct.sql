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
	st.st_id=sc.sc_station_id AND st.st_city='北京';


CREATE VIEW pass_end AS
SELECT 
	sc.sc_train_num AS train_num, 
	st.st_name AS station_name, 
	st.st_id AS station_id
FROM 
	station AS st, 
	scheduler AS sc
WHERE 
	st.st_id=sc.sc_station_id AND st.st_city='上海';


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

--
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
    	WHEN sc1.sc_depart_time-sc1.sc_arrive_time>='00:00'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time)
     	WHEN sc1.sc_depart_time-sc1.sc_arrive_time<'00:00'
     	THEN sc2.sc_total_time-sc1.sc_total_time
     		 -(sc1.sc_depart_time-sc1.sc_arrive_time+ INTERVAL '24:00:00')
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
	sc1.sc_depart_time>= '12:00' AND
	sc1.sc_station_id=s1.s_station_id AND s1.s_train_num=sc1.sc_train_num AND
    (s1.s_total_price>0.0 OR sc1.sc_rank=1) AND
	sc2.sc_station_id=s2.s_station_id AND s2.s_train_num=sc2.sc_train_num AND
    (s2.s_total_price>0.0 OR sc2.sc_rank=1) AND
    s1.s_seat_type=s2.s_seat_type;

--view2
CREATE VIEW pre_ddate AS
SELECT 
	gd.train_num AS train_num,
    gd.dstation AS dstation,
	(CASE
    	WHEN sc.sc_depart_time-sc.sc_arrive_time>='00:00'
     	THEN date(date '2022-5-8'+sc.sc_arrive_time-sc.sc_total_time)
     	WHEN sc.sc_depart_time-sc.sc_arrive_time<'00:00'
     	THEN date(date '2022-5-8'-INTERVAL '24:00:00'+sc.sc_arrive_time-sc.sc_total_time)
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
    t.t_status='Available';


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


drop view sum_final_result;
drop view final_result;
drop view direct_train_with_rank_ddate;
drop view pre_ddate;
drop table good_direct_train;
drop view direct_train;
drop view train_same_city;
drop view pass_end;
drop view pass_start;
drop view ticket_rank;