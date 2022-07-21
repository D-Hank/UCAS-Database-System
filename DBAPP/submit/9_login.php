<?php

//include ("data.php");

$log_user = "root";
$log_phone = "123";
$log_role = "admin";
function fixtype($s)
{
    return '\'' . $s . '\'';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Welcome!</title>
        <link rel="stylesheet" type="text/css" href="dbms.css">
        <style>
            .background-p {
                height: 100%;
                background-image: linear-gradient(to right, rgb(206, 217, 255), rgb(255, 203, 244));
            }

            .background-r {
                height: 100%;
                background-image: linear-gradient(to right, rgb(27, 27, 228), rgb(41, 41, 41));
            }
        </style>
    </head>
    <body>
    <?php
        if($log_role == "passenger")
        {
            $dbconn = pg_connect("host={$host} dbname={$dbname} user={$user} port={$port} password={$passwd}")
                or die('Could not connect : ' . pg_last_error());
            $query = "SELECT COUNT(*) FROM passenger WHERE " . "p_user=" . fixtype($log_user) . " and " .
                    "p_phone=" . fixtype($log_phone);
            $result = pg_query($query);
            $num = pg_fetch_result($result, 0, 0);
            if($num == 1)
            {
    ?>
                <div class="background-p">
                    <div class="text-outer">
                        <div class="single-text">
                            Welcome <?php echo $log_user ?> !
                        </div>
                        <br>
                        <div class="single-text">
                            Look up your train, please <a href="train.html">CLICK HERE.</a>
                        </div>
                        <br>
                        <div class="single-text">
                            Look up the train between two cities, please <a href="journal.html">CLICK HERE.</a>
                        </div>
                        <br>
                        <div class="single-text">
                            Look up your order, please <a href="order.html">CLICK HERE.</a>
                        </div>
                        <br>
                    </div>
                </div>
    <?php
            }
            else
            {
    ?>
                <div class="background-p">
                    <div class="text-outer">
                        <div class="single-text">
                            The user can not match the phone, please
                            <a href="login.html">TRY AGAGIN</a>
                            or <a href="signup.html">SIGN UP</a>
                        </div>
                    </div>
                </div>
    <?php
            }
            pg_free_result($result);
            pg_close($dbconn);
        }
        else if($log_role == "admin" && $log_user == "root" && $log_phone == "123")
        {
            $dbconn = pg_connect("dbname=tpch user=dbms password=dbms");
            $query ="SELECT count(*) AS total_order FROM ordering WHERE o_status='Normal';";
            $result = pg_query($query);
            $total_order = pg_fetch_all($result)[0]['total_order'];
            pg_freeresult($result);

            $query = "SELECT sum(o_price) AS total_price FROM ordering WHERE o_status='Normal';";
            $result = pg_query($query);
            $total_price = pg_fetch_all($result)[0]['total_price'];
            pg_freeresult($result);

            pg_close($dbconn);
    ?>
            <div class="background-p">
                <div class="text-outer">
                    welcome ROOT ! <br>
                    The total orders : <?php echo $total_order; ?><br>
                    The total price : <?php echo $total_price; ?><br>
                    The top 10 trains : <a href="9_top10.php">CLICK HERE</a>
                    <br>
                    The user list : <a href="9_userlist.php">CLICK HERE</a>
                </div>
            </div>
    <?php
        }
        else
        {
    ?>
            <div class="text-outer">
                <div class="single-text">
                    The user can not match the phone, please
                    <a href="login.html">TRY AGAGIN</a>
                    or <a href="signup.html">SIGN UP</a>
                </div>
            </div>
    <?php
        }
    ?>
    </body>
</html>