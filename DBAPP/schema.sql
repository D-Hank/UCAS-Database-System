/* Notice: when initializing, past few days' tickets should be added! */

CREATE TABLE station (
    st_id  INTEGER PRIMARY KEY,
    st_name VARCHAR(20) NOT NULL,
    st_city VARCHAR(20) NOT NULL
);

/* Note: ArriveTime for start station? */
/* Default: ArriveTime == DepartTime*/
CREATE TYPE SC_BOOL AS ENUM ('True', 'False');
CREATE TABLE scheduler (
    sc_station_id INTEGER NOT NULL,
    sc_train_num VARCHAR(5) NOT NULL,
    sc_arrive_time TIME NOT NULL,
    sc_depart_time TIME NOT NULL,
    sc_total_time INTERVAL NOT NULL,
    sc_rank INTEGER NOT NULL,
    sc_is_end SC_BOOL NOT NULL,
    PRIMARY KEY (sc_station_id, sc_train_num),
    FOREIGN KEY (sc_station_id) REFERENCES station(st_id),
    CHECK (sc_rank >= 1)
);

/* Price=0.00 for '-' (start station included, but for start, rank=True)
  Otherwise, Price=0.00 is not for sale */
CREATE TYPE S_T_SEAT AS ENUM ('H', 'S', 'HU', 'HM', 'HL', 'SU', 'SL');
CREATE TABLE seat (
    s_station_id INTEGER NOT NULL,
    s_train_num VARCHAR(5) NOT NULL,
    s_seat_type S_T_SEAT NOT NULL,
    s_total_price DECIMAL(7, 2) NOT NULL,
    PRIMARY KEY (s_station_id, s_train_num, s_seat_type),
    FOREIGN KEY (s_station_id) REFERENCES station(st_id),
    FOREIGN KEY (s_station_id, s_train_num) REFERENCES scheduler(sc_station_id, sc_train_num),
    CHECK (s_total_price >= 0.00)
);

CREATE TYPE T_WHICH AS ENUM ('A', 'B', 'C', 'D', 'E');
CREATE TYPE T_STATUS AS ENUM ('Available', 'Sold', 'Unavailable');
CREATE TABLE ticket (
    t_id INTEGER PRIMARY KEY,
    t_train_num VARCHAR(5) NOT NULL,
    t_start_date DATE NOT NULL,
    t_which_seat T_WHICH NOT NULL,
    t_status T_STATUS NOT NULL,
    t_int_start INTEGER NOT NULL,
    t_int_end INTEGER NOT NULL,
    t_int_seat S_T_SEAT NOT NULL,
    FOREIGN KEY (t_int_start) REFERENCES station(st_id),
    FOREIGN KEY (t_int_end) REFERENCES station(st_id),
    FOREIGN KEY (t_int_start, t_train_num) REFERENCES scheduler(sc_station_id, sc_train_num),
    FOREIGN KEY (t_int_end, t_train_num) REFERENCES scheduler(sc_station_id, sc_train_num)
);

/* Notice: consistency should be guaranteed! */
CREATE TABLE passenger (
    p_phone CHAR(11) PRIMARY KEY,
    p_user VARCHAR(20) UNIQUE,
    p_name VARCHAR(20) NOT NULL
);

CREATE TYPE O_STATUS AS ENUM ('Normal', 'Canceled');
CREATE TABLE ordering (
    o_id INTEGER PRIMARY KEY,
    o_pas_phone CHAR(11) NOT NULL,
    o_price DECIMAL(7, 2) NOT NULL,
    o_buy_time TIMESTAMP NOT NULL,
    o_status O_STATUS NOT NULL,
    FOREIGN KEY (o_pas_phone) REFERENCES passenger(p_phone),
    CHECK (o_price >= 0.00)
);

CREATE TABLE has_ticket (
    ht_ticket_id INTEGER NOT NULL,
    ht_order_id INTEGER NOT NULL,
    PRIMARY KEY (ht_ticket_id, ht_order_id),
    FOREIGN KEY (ht_ticket_id) REFERENCES ticket(t_id),
    FOREIGN KEY (ht_order_id) REFERENCES ordering(o_id)
);
