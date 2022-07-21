<?php
$user = $_POST["user"];
$phone = $_POST["phone"];
$role = $_POST["role"];
$phone_php = $_GET['phone'];
function fixtype($s)
{
    return '\'' . $s . '\'';
}
if($phone_php)
{
    $phone=$phone_php;
}
setcookie('phone', $phone);
?>
<!DOCTYPE html>
<meta charset="UTF-8">
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
        if($role == "passenger" || $phone_php)
        {
            $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
                or die('Could not connect : ' . pg_last_error());
            $query = "SELECT COUNT(*) FROM passenger WHERE " . "p_user=" . fixtype($user) . " and " .
                    "p_phone=" . fixtype($phone);
            $result = pg_query($query);
            $num = pg_fetch_result($result, 0, 0);
            if($num == 1 || $phone_php)
            {       
    ?>
                <div class="background-p">
                    <div class="text-outer">
                        <div class="single-text">
                            Welcome!
                        </div>
                        <br>
                        <div class="single-text">
                            Look up your train, please <a href="4_input.html">CLICK HERE.</a>
                        </div>
                        <br>
                        <div class="single-text">
                            Look up the train between two cities, please <a href="journal.html">CLICK HERE.</a>
                        </div>
                        <br>
                        <div class="single-text">
                            Look up your order, please <a href=8_order.php?phone=<?php echo $phone; ?>>CLICK HERE.</a>
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
        else if($role == "admin" && $user == "root" && $phone == "123")
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
                <div class="single-text">
                    welcome ROOT !
                </div> <br>
                <div class="single-text">
                    The total orders : <?php echo $total_order; ?>
                </div><br>
                <div class="single-text">
                    The total price : <?php echo $total_price; ?>
                </div>
                <br>
                <div class="single-text">
                The top 10 trains : <a href="9_top10.php">CLICK HERE</a>
                </div>    
                <br>
                <div class="single-text">
                    The user list : <a href="9_userlist.php">CLICK HERE</a>
                </div>
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