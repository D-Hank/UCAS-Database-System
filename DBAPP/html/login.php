<?php
$user = $_POST["user"];
$phone = $_POST["phone"];
$role = $_POST["role"];
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
        if($role == "passenger")
        {
            $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
                or die('Could not connect : ' . pg_last_error());
            $query = "SELECT COUNT(*) FROM passenger WHERE " . "p_user=" . fixtype($user) . " and " .
                    "p_phone=" . fixtype($phone);
            $result = pg_query($query);
            $num = pg_fetch_result($result, 0, 0);
            if($num == 1)
            {       
    ?>
                <div class="background-p">
                    <div class="text-outer">
                        <div class="single-text">
                            Welcome <?php echo $user ?> !
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
        else if($role == "admin" && $user == "root" && $phone == "123")
        {
    ?>
            <div class="background-p">
                <div class="text-outer">
                    welcome ROOT ! <br>
                    The total orders : 123.<br>
                    The total price : 123.<br>
                    The top 10 trains : <a href="top10.php">CLICK HERE</a>
                    <br>
                    The user list : <a href="userlist.php">CLICK HERE</a>
                    <br>
                    The order of user : <a href="order_user.php">CLICK HERE</a>
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