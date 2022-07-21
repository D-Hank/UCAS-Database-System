<?php
    $direct_ticket=$_POST['ticket'];
    $ticket1=$_POST['ticket1'];
    $ticket2=$_POST['ticket2'];
    $ticket_4=$_POST['ticket4'];
    $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

    $phone = $_COOKIE['phone'];
    
    if($ticket_4)
    {   //need for 4
        $query1 = 'SELECT count(*)+1 from ordering;';
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
        $o_num = pg_fetch_result($result1, 0, 0);
        
        $query0 =
        "
        SELECT DISTINCT
            station
        FROM
            train_4_ticket
        WHERE
            _rank=1;
        ";
        $result0 = pg_query($query0) or die('Query failed: ' . pg_last_error());
        $dstation = pg_fetch_result($result0, 0, 0);

        // get price and station_id
        $query2 =
        "
        SELECT DISTINCT
            f.price+5.0 AS price,
            st1.st_id AS dstation_id,
            st2.st_id AS astation_id
        FROM 
            train_4_ticket AS f,
            station AS st1,
            station AS st2
        WHERE
            f.ticket_id={$ticket_4} AND f.station='{$_COOKIE['astation']}' AND
            st1.st_name='{$dstation}' AND st2.st_name=f.station;
        ";
        $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
        $total_price = pg_fetch_result($result2, 0, 0);
        $dstation_id = pg_fetch_result($result2, 0, 1);
        $astation_id = pg_fetch_result($result2, 0, 2);

        // insert into ordering
        $query3 =
        "
        INSERT INTO ordering(o_id, o_pas_phone, o_price, o_buy_time, o_status)
        VALUES({$o_num}, '{$phone}', {$total_price}, NOW(), 'Normal');
        ";
        pg_query($query3) or die('Query failed: ' . pg_last_error());

        // select int_start, int_end
        $query4 =
        "
        SELECT
            t_int_start,
            t_int_end,
            t_train_num,
            t_start_date,
            t_which_seat,
            t_int_seat
        FROM 
            ticket
        WHERE
            t_id={$ticket_4};
        ";
        $result4 = pg_query($query4) or die('Query failed: ' . pg_last_error());
        $ticket_line = pg_fetch_array($result4, null, PGSQL_ASSOC);
        
        // update ticket status
        if($ticket_line['t_int_start']!=$dstation_id)
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line['t_train_num']}',
                '{$ticket_line['t_start_date']}', '{$ticket_line['t_which_seat']}',
                'Available', {$ticket_line['t_int_start']}, {$dstation_id}, '{$ticket_line['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        
        if($astation_id!=$ticket_line['t_int_end'])
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line['t_train_num']}',
                '{$ticket_line['t_start_date']}', '{$ticket_line['t_which_seat']}',
                'Available', {$astation_id}, {$ticket_line['t_int_end']}, '{$ticket_line['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        
        $query5 =
        "
        UPDATE ticket
        SET t_status='Sold', t_int_start={$dstation_id}, t_int_end={$astation_id}
        WHERE t_id={$ticket_4};
        ";
        pg_query($query5) or die('Query failed: ' . pg_last_error());

        $query6 =
        "
        INSERT INTO has_ticket(ht_ticket_id, ht_order_id)
        VALUES({$ticket_4}, {$o_num});
        ";
        pg_query($query6) or die('Query failed: ' . pg_last_error());
        pg_free_result($result1);
        pg_free_result($result2);
        pg_free_result($result4);
    }
    else if($direct_ticket)
    {  //direct train
        // get row number of ordering
        $query1 = 'SELECT count(*)+1 from ordering;';
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
        $o_num = pg_fetch_result($result1, 0, 0);
        
        // get price and station_id
        $query2 =
        "
        SELECT DISTINCT
            f.price+5.0 AS price,
            st1.st_id AS dstation_id,
            st2.st_id AS astation_id
        FROM 
            final_result AS f,
            station AS st1,
            station AS st2
        WHERE
            f.ticket_id={$direct_ticket} AND
            st1.st_name=f.dstation AND st2.st_name=f.astation; 
        ";
        $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
        $total_price = pg_fetch_result($result2, 0, 0);
        $dstation_id = pg_fetch_result($result2, 0, 1);
        $astation_id = pg_fetch_result($result2, 0, 2);
        
        // insert into ordering
        $query3 =
        "
        INSERT INTO ordering(o_id, o_pas_phone, o_price, o_buy_time, o_status)
        VALUES({$o_num}, '{$phone}', {$total_price}, NOW(), 'Normal');
        ";
        pg_query($query3) or die('Query failed: ' . pg_last_error());

        // select int_start, int_end
        $query4 =
        "
        SELECT
            t_int_start,
            t_int_end,
            t_train_num,
            t_start_date,
            t_which_seat,
            t_int_seat
        FROM 
            ticket
        WHERE
            t_id={$direct_ticket};
        ";
        $result4 = pg_query($query4) or die('Query failed: ' . pg_last_error());
        $ticket_line = pg_fetch_array($result4, null, PGSQL_ASSOC);
        
        // update ticket status
        if($ticket_line['t_int_start']!=$dstation_id)
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line['t_train_num']}',
                '{$ticket_line['t_start_date']}', '{$ticket_line['t_which_seat']}',
                'Available', {$ticket_line['t_int_start']}, {$dstation_id}, '{$ticket_line['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        if($astation_id!=$ticket_line['t_int_end'])
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line['t_train_num']}',
                '{$ticket_line['t_start_date']}', '{$ticket_line['t_which_seat']}',
                'Available', {$astation_id}, {$ticket_line['t_int_end']}, '{$ticket_line['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        
        $query5 =
        "
        UPDATE ticket
        SET t_status='Sold', t_int_start={$dstation_id}, t_int_end={$astation_id}
        WHERE t_id={$direct_ticket};
        ";
        pg_query($query5) or die('Query failed: ' . pg_last_error());

        $query6 =
        "
        INSERT INTO has_ticket(ht_ticket_id, ht_order_id)
        VALUES({$direct_ticket}, {$o_num});
        ";
        pg_query($query6) or die('Query failed: ' . pg_last_error());
        pg_free_result($result1);
        pg_free_result($result2);
        pg_free_result($result4);
    }
    else
    {
        $query1 = 'SELECT count(*)+1 from ordering;';
        $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
        $o_num = pg_fetch_result($result1, 0, 0);
        
        // get price and station_id
        $query2 =
        "
        SELECT DISTINCT
            t1.price+t2.price+10.0 AS price,
            st1.st_id AS dstation_id1,
            st2.st_id AS astation_id1,
            st3.st_id AS dstation_id2,
            st4.st_id AS astation_id2
        FROM 
            transfer_result1 AS t1,
            transfer_result2 AS t2,
            station AS st1,
            station AS st2,
            station AS st3,
            station AS st4
        WHERE
            t1.id=t2.id AND
            t1.ticket_id={$ticket1} AND t2.ticket_id={$ticket2} AND
            st1.st_name=t1.dstation AND st2.st_name=t1.astation AND
            st3.st_name=t2.dstation AND st4.st_name=t2.astation;
        ";
        $result2 = pg_query($query2) or die('Query failed: ' . pg_last_error());
        $total_price = pg_fetch_result($result2, 0, 0);
        $dstation_id1 = pg_fetch_result($result2, 0, 1);
        $astation_id1 = pg_fetch_result($result2, 0, 2);
        $dstation_id2 = pg_fetch_result($result2, 0, 3);
        $astation_id2 = pg_fetch_result($result2, 0, 4);
        
        // insert into ordering
        $query3 =
        "
        INSERT INTO ordering(o_id, o_pas_phone, o_price, o_buy_time, o_status)
        VALUES({$o_num}, '{$phone}', {$total_price}, NOW(), 'Normal');
        ";
        pg_query($query3) or die('Query failed: ' . pg_last_error());

        // select int_start, int_end
        $query4 =
        "
        SELECT
            t_int_start,
            t_int_end,
            t_train_num,
            t_start_date,
            t_which_seat,
            t_int_seat
        FROM 
            ticket
        WHERE
            t_id={$ticket1};
        ";
        $result4 = pg_query($query4) or die('Query failed: ' . pg_last_error());
        $ticket_line1 = pg_fetch_array($result4, null, PGSQL_ASSOC);

        $query42 =
        "
        SELECT
            t_int_start,
            t_int_end,
            t_train_num,
            t_start_date,
            t_which_seat,
            t_int_seat
        FROM 
            ticket
        WHERE
            t_id={$ticket2};
        ";
        $result42 = pg_query($query42) or die('Query failed: ' . pg_last_error());
        $ticket_line2 = pg_fetch_array($result42, null, PGSQL_ASSOC);
        
        // update ticket status
        if($ticket_line1['t_int_start']!=$dstation_id1)
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line1['t_train_num']}',
                '{$ticket_line1['t_start_date']}', '{$ticket_line1['t_which_seat']}',
                'Available', {$ticket_line1['t_int_start']}, {$dstation_id1}, '{$ticket_line1['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        if($astation_id1!=$ticket_line1['t_int_end'])
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line1['t_train_num']}',
                '{$ticket_line1['t_start_date']}', '{$ticket_line1['t_which_seat']}',
                'Available', {$astation_id1}, {$ticket_line1['t_int_end']}, '{$ticket_line1['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        if($ticket_line2['t_int_start']!=$dstation_id2)
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line2['t_train_num']}',
                '{$ticket_line2['t_start_date']}', '{$ticket_line2['t_which_seat']}',
                'Available', {$ticket_line2['t_int_start']}, {$dstation_id2}, '{$ticket_line2['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        if($astation_id2!=$ticket_line2['t_int_end'])
        {
            $queryt1 = 'SELECT count(*)+1 from ticket;';
            $resultt1 = pg_query($queryt1) or die('Query failed: ' . pg_last_error());
            $t_num = pg_fetch_result($resultt1, 0, 0);
            $insert1 =
            "
            INSERT INTO ticket(
                t_id, t_train_num, t_start_date, 
                t_which_seat, t_status, t_int_start,
                t_int_end, t_int_seat) 
            VALUES({$t_num}, '{$ticket_line2['t_train_num']}',
                '{$ticket_line2['t_start_date']}', '{$ticket_line2['t_which_seat']}',
                'Available', {$astation_id2}, {$ticket_line2['t_int_end']}, '{$ticket_line2['t_int_seat']}'
            );
            ";
            pg_query($insert1) or die('Query failed: ' . pg_last_error());
            pg_free_result($resultt1);
        }
        
        $query5 =
        "
        UPDATE ticket
        SET t_status='Sold', t_int_start={$dstation_id1}, t_int_end={$astation_id1}
        WHERE t_id={$ticket1};
        ";
        pg_query($query5) or die('Query failed: ' . pg_last_error());

        $query52 =
        "
        UPDATE ticket
        SET t_status='Sold', t_int_start={$dstation_id2}, t_int_end={$astation_id2}
        WHERE t_id={$ticket2};
        ";
        pg_query($query52) or die('Query failed: ' . pg_last_error());

        $query6 =
        "
        INSERT INTO has_ticket(ht_ticket_id, ht_order_id)
        VALUES({$ticket1}, {$o_num});
        INSERT INTO has_ticket(ht_ticket_id, ht_order_id)
        VALUES({$ticket2}, {$o_num});
        ";
        pg_query($query6) or die('Query failed: ' . pg_last_error());
        pg_free_result($result1);
        pg_free_result($result2);
        pg_free_result($result4);
        pg_free_result($result42);
    }
    pg_close($dbconn);
?>
<html>
    <head>
        <title>Booking</title>
        <link rel="stylesheet" type="text/css" href="dbms.css">
        <style>
            .more-text {
                text-align: center;
                line-height: 100px;
                font-size: 30px;
            }
            .background {
                height: 100%;
                background-image: linear-gradient(to bottom right, rgb(255, 231, 231), rgb(253, 255, 161),rgb(255, 187, 187));
            }
        </style>
    </head>
    <body>
        <div class="background">
            <div class="outer">
                <div class="title">
                    预订成功！
                </div>
                <div class="more-text">
                    <a href=login.php?phone=<?php echo $phone?>>返回首页</a>
                </div>
            </div>
        </div>
    </body>
</html>