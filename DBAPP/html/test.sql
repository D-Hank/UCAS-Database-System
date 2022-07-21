create table good_direct_train2(
train varchar(5) NOT NULL,
dstation varchar(20) NOT NULL,
astation varchar(20) NOT NULL,
dstation_id integer NOT NULL,
astation_id integer NOT NULL,
seat_type S_T_SEAT NOT NULL,
price decimal(7,2) NOT NULL,
depart_time time NOT NULL,
total_time INTERVAL NOT NULL,
PRIMARY KEY(train, dstation, astation, seat_type)
);
create table pre_ddate2(
    train_num varchar(5) NOT NULL,
    ddate date NOT NULL,
    dstation varchar(20) NOT NULL,
    PRIMARY KEY(train_num, dstation)
);

INSERT INTO pre_ddate2
SELECT 
	gd.train AS train_num,
	(CASE
    	WHEN sc.sc_depart_time-sc.sc_arrive_time>='00:00'
     	THEN date(date '2022-5-11'+sc.sc_arrive_time-sc.sc_total_time)
     	WHEN sc.sc_depart_time-sc.sc_arrive_time<'00:00'
     	THEN date(date '2022-5-11'-INTERVAL '24:00:00'+sc.sc_arrive_time-sc.sc_total_time)
     END
    ) AS ddate,
    gd.dstation AS dstation
FROM 
	scheduler AS sc,
	good_direct_train2 AS gd
WHERE
	sc.sc_train_num=gd.train AND sc.sc_station_id=gd.dstation_id
GROUP BY
    gd.train, gd.dstation, sc.sc_depart_time, sc.sc_arrive_time, sc.sc_total_time;

create view test1 AS
SELECT 
	gd.train AS train, 
	gd.dstation AS dstation,
	gd.astation AS astation,
	gd.seat_type AS seat_type,
	sc1.sc_rank AS rank1,
    sc2.sc_rank AS rank2
FROM 
	good_direct_train2 AS gd, 
	scheduler AS sc1, 
	scheduler AS sc2
WHERE 
	sc1.sc_station_id=gd.dstation_id AND sc1.sc_train_num=gd.train AND 	
	sc2.sc_station_id=gd.astation_id AND sc2.sc_train_num=gd.train;

create view test2 AS
SELECT 
	t.t_id AS ticket_id,
    t.t_train_num AS train_num,
    t.t_int_seat As seat_type,
    t.t_which_seat AS which_seat,
    tr.start_rank AS rank1,
    tr.end_rank AS rank2
FROM 
	ticket AS t,
    ticket_rank AS tr
WHERE 
	tr.ticket_id=t.t_id AND 
	t.t_start_date='2022-5-9' AND 
	t.t_status='Available';


create view test3 AS
SELECT 
    t2.ticket_id AS ticket_id,
    t1.dstation AS dstation,
    t1.astation AS astation,
    t2.seat_type AS seat_type,
    t2.which_seat AS which_seat
FROM
    test1 AS t1,
    test2 AS t2
WHERE
    t1.train=t2.train_num AND t1.seat_type=t2.seat_type AND
    t2.rank1<=t1.rank1 AND t2.rank2>=t1.rank2;

create view test3 AS
SELECT 
	t.t_id AS ticket_id,
	gd.train AS train, 
	gd.dstation AS dstation,
	gd.astation AS astation,
	gd.seat_type AS seat_type,
	gd.depart_time AS depart_time,
	gd.total_time AS total_time,
    t.t_which_seat AS which_seat,
    gd.price AS price
FROM 
	good_direct_train2 AS gd, 
	scheduler AS sc1, 
	scheduler AS sc2, 
	ticket_rank AS tr, 
	ticket AS t, 
	seat AS s1, 
	seat AS s2,
	pre_ddate2 AS pd
WHERE 
	sc1.sc_station_id=gd.dstation_id AND sc1.sc_train_num=gd.train AND 	
	sc2.sc_station_id=gd.astation_id AND sc2.sc_train_num=gd.train AND 
	t.t_train_num=gd.train AND
	tr.ticket_id=t.t_id AND 
	sc1.sc_rank>=tr.start_rank AND sc2.sc_rank<=tr.end_rank AND
	t.t_start_date=pd.ddate AND t.t_train_num=pd.train_num AND
	t.t_status='Available' AND t.t_int_seat=gd.seat_type;