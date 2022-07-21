<?php

//include ("data.php");

$order_id = $_GET['order_id'];

$db_conn = pg_connect("dbname=tpch user=dbms password=dbms") or die(pg_last_error());

$front =
"SELECT
	t1.t_id AS ticket_id_this,
	t2.t_id AS ticket_id_front,
	t2.t_int_start AS front_int_start
FROM
	ticket AS t1,
	ticket AS t2,
	has_ticket AS ht
WHERE
    -- given back
	ht.ht_order_id={$order_id} AND ht.ht_ticket_id=t1.t_id AND
    -- front
	t2.t_int_end=t1.t_int_start AND t2.t_status='Available' AND
    t2.t_int_seat=t1.t_int_seat AND t2.t_which_seat=t1.t_which_seat AND
    t2.t_train_num=t1.t_train_num AND t2.t_start_date=t1.t_start_date
ORDER BY
    t1.t_id;
";

$behind =
"SELECT
	t1.t_id AS ticket_id_this,
	t2.t_id AS ticket_id_behind,
	t2.t_int_end AS behind_int_end
FROM
	ticket AS t1,
	ticket AS t2,
	has_ticket AS ht
WHERE
    -- given back
	ht.ht_order_id={$order_id} AND ht.ht_ticket_id=t1.t_id AND
    -- behind
	t2.t_int_start=t1.t_int_end AND t2.t_status='Available' AND
    t2.t_int_seat=t1.t_int_seat AND t2.t_which_seat=t1.t_which_seat AND
    t2.t_train_num=t1.t_train_num AND t2.t_start_date=t1.t_start_date
ORDER BY
    t1.t_id;
";

$front_result = pg_query($front);
$behind_result = pg_query($behind);

$front_ticket = pg_fetch_all($front_result);
$behind_ticket = pg_fetch_all($behind_result);

pg_freeresult($front_result);
pg_freeresult($behind_result);

$num_front = ($front_ticket != false) ? count($front_ticket) : 0;
$num_behind = ($behind_ticket != false) ? count($behind_ticket) : 0;

// Drop front and behind
for($i = 0; $i < $num_front; $i ++){
    $update =
    "UPDATE ticket
    SET t_status='Unavailable'
    WHERE t_id={$front_ticket[$i]['ticket_id_front']};
    ";
    $result = pg_query($update);
    pg_freeresult($result);
}

for($i = 0; $i < $num_behind; $i ++){
    $update =
    "UPDATE ticket
    SET t_status='Unavailable'
    WHERE t_id={$behind_ticket[$i]['ticket_id_behind']};
    ";
    $result = pg_query($update);
    pg_freeresult($result);
}

$current =
"SELECT
    ht.ht_ticket_id AS this_ticket_id,
    t.t_int_start AS this_int_start,
    t.t_int_end AS this_int_end
FROM
    has_ticket AS ht,
    ticket AS t
WHERE
    ht.ht_order_id={$order_id} AND ht.ht_ticket_id=t.t_id
ORDER BY
    ht.ht_ticket_id
;
";
$current_result = pg_query($current);
$current_ticket = pg_fetch_all($current_result);
pg_freeresult($current_result);
$num_current = count($current_ticket);

for($i = 0; $i < $num_current; $i ++){
    $new_start = $current_ticket[$i]['this_int_start'];
    $new_end = $current_ticket[$i]['this_int_end'];
    
    // Find a front ticket for this ticket
    for($j = 0; $j < $num_front; $j ++){
        if($front_ticket[$j]['ticket_id_this'] == $current_ticket[$i]['this_ticket_id']){
            $new_start = $front_ticket[$j]['front_int_start'];
            break;
        }
    }

    // Find a behind ticket for this ticket
    for($j = 0; $j < $num_behind; $j ++){
        if($behind_ticket[$j]['ticket_id_this'] == $current_ticket[$i]['this_ticket_id']){
            $new_end = $behind_ticket[$j]['behind_int_end'];
            break;
        }
    }

    $update =
    "UPDATE
        ticket
    SET
        t_status='Unavailable'
    WHERE
        t_id={$current_ticket[$i]['this_ticket_id']};
    ";
    pg_query($update);

    $query1 =
    "
    SELECT
        t_train_num,
        t_start_date,
        t_which_seat,
        t_int_seat
    FROM 
        ticket
    WHERE
        t_id={$current_ticket[$i]['this_ticket_id']};
    ";
    $result1 = pg_query($query1) or die('Query failed: ' . pg_last_error());
    $ticket_line = pg_fetch_array($result1, null, PGSQL_ASSOC);

    $query0 =
    "SELECT count(*)+1 FROM ticket;";
    $result0 = pg_query($query0) or die('Query failed: ' . pg_last_error());
    $id = pg_fetch_result($result0, 0, 0);

    $insert =
    "
    INSERT INTO ticket(
        t_id, t_train_num, t_start_date, 
        t_which_seat, t_status, t_int_start,
        t_int_end, t_int_seat) 
    VALUES({$id}, '{$ticket_line['t_train_num']}',
        '{$ticket_line['t_start_date']}', '{$ticket_line['t_which_seat']}',
        'Available', {$new_start}, {$new_end}, '{$ticket_line['t_int_seat']}'
    );
    ";

    pg_query($insert) or die('Query failed: ' . pg_last_error());
}

$update =
"UPDATE ordering
SET o_status='Canceled'
WHERE o_id={$order_id}
;
";

$result = pg_query($update);
pg_free_result($result);
pg_close($db_conn);

?>

<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <body>
        <?php
            echo "取消成功！点击确认返回首页";
        ?>
        <a href=login.php?phone=<?php echo $_COOKIE['phone']?>>确定</a>
    </body>
</html>
