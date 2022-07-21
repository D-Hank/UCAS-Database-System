<?php
$dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());

$query0 = 'SELECT * FROM passenger LIMIT 5;';
if(!pg_query($query0))
{
    $create1 = 'CREATE TABLE station(
                st_id INTEGER PRIMARY KEY,
                st_name VARCHAR(20) NOT NULL,
                st_city VARCHAR(20) NOT NULL
                );';
    $result1 = pg_query($create1) or die('Create failed: ' . pg_last_error());
    echo "create station success!<br>";
    $copy1 = 'copy station(st_id,
            st_name, st_city) from
            \'/var/www/html/mycode/table/station.tbl\'
            with (format csv);';
    $result2 = pg_query($copy1) or die('Copy failed: ' . pg_last_error());
    echo "add data to station success!<br>";
    
    pg_free_result($result1);
    pg_free_result($result2);
}

$query1 = 'SELECT * FROM station LIMIT 5;';
if(!pg_query($query1))
{
    $create1 = 'CREATE TABLE station(
                st_id INTEGER PRIMARY KEY,
                st_name VARCHAR(20) NOT NULL,
                st_city VARCHAR(20) NOT NULL
                );';
    $result1 = pg_query($create1) or die('Create failed: ' . pg_last_error());
    echo "create station success!<br>";
    $copy1 = 'copy station(st_id,
            st_name, st_city) from
            \'/var/www/html/mycode/table/station.tbl\'
            with (format csv);';
    $result2 = pg_query($copy1) or die('Copy failed: ' . pg_last_error());
    echo "add data to station success!<br>";
    
    pg_free_result($result1);
    pg_free_result($result2);
}

$query2 = 'SELECT * FROM scheduler LIMIT 5;';
if(!pg_query($query2))
{
    //CREATE TYPE SC_BOOL AS ENUM (\'True\', \'False\');
    $create1 = '
                CREATE TABLE scheduler(
                sc_station_id INTEGER NOT NULL,
                sc_train_num VARCHAR(5) NOT NULL,
                sc_arrive_time TIME NOT NULL,
                sc_depart_time TIME NOT NULL,
                sc_total_time INTERVAL NOT NULL,
                sc_rank INTEGER NOT NULL,
                sc_is_end SC_BOOL NOT NULL,
                PRIMARY KEY (sc_station_id, sc_train_num),
                FOREIGN KEY (sc_station_id) REFERENCES station(st_id),
                CHECK (sc_rank>=1)
                );';
    $result1 = pg_query($create1) or die('Create failed: ' . pg_last_error());
    echo "create scheduler success! <br>";
    $copy1 = 'copy scheduler(sc_station_id,
            sc_train_num, sc_arrive_time,
            sc_depart_time, sc_total_time,
            sc_rank, sc_is_end) from
            \'/var/www/html/mycode/table/scheduler.tbl\'
            with (format csv);';
    $result2 = pg_query($copy1) or die('Copy failed: ' . pg_last_error());
    echo "add data to scheduler success!<br>";

    pg_free_result($result1);
    pg_free_result($result2);
}

$query3 = 'SELECT * FROM seat LIMIT 5;';
if(!pg_query($query3))
{
    //CREATE TYPE S_T_SEAT AS ENUM (\'H\', \'S\', \'HU\', \'HM\', \'HL\', \'SU\', \'SL\');
    $create1 = '
                CREATE TABLE seat(
                s_station_id INTEGER NOT NULL,
                s_train_num VARCHAR(5) NOT NULL,
                s_seat_type S_T_SEAT NOT NULL,
                s_total_price DECIMAL(7, 2) NOT NULL,
                PRIMARY KEY (s_station_id, s_train_num, s_seat_type),
                FOREIGN KEY (s_station_id) REFERENCES station(st_id),
                FOREIGN KEY (s_station_id, s_train_num) REFERENCES scheduler(sc_station_id, sc_train_num),
                CHECK (s_total_price >= 0.00)
                );';
    $result1 = pg_query($create1) or die('Create failed: ' . pg_last_error());
    echo "create seat success! <br>";
    $copy1 = 'copy seat(s_station_id,
            s_train_num, s_seat_type,
            s_total_price) from
            \'/var/www/html/mycode/table/seat.tbl\'
            with (format csv);';
    $result2 = pg_query($copy1) or die('Copy failed: ' . pg_last_error());
    echo "add data to seat success!<br>";

    pg_free_result($result1);
    pg_free_result($result2);
}

$query4 = 'SELECT * FROM ticket LIMIT 5;';
if(!pg_query($query4))
{
    //CREATE TYPE T_WHICH AS ENUM (\'A\', \'B\', \'C\', \'D\', \'E\');
    //CREATE TYPE T_STATUS AS ENUM (\'Available\', \'Sold\', \'Unavailable\');
    $create1 = '
                CREATE TABLE ticket(
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
                );';
    $result1 = pg_query($create1) or die('Create failed: ' . pg_last_error());
    echo "create ticket success! <br>";
    $copy1 = 'copy ticket(
            t_id, t_train_num, t_start_date,
            t_which_seat, t_status, t_int_start,
            t_int_end, t_int_seat
            ) from
            \'/var/www/html/mycode/table/ticket.tbl\'
            with (format csv);';
    $result2 = pg_query($copy1) or die('Copy failed: ' . pg_last_error());
    echo "add data to ticket success!<br>";

    pg_free_result($result1);
    pg_free_result($result2);
}
echo "All the table has been created !";
pg_close($dbconn);
?>