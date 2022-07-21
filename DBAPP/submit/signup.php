<?php
$user = $_POST["user"];
$phone = $_POST["phone"];
$name = $_POST["name"];
function fixtype($s)
{
    return '\'' . $s . '\'';
}
?>
<!DOCTYPE html>
<meta charset="UTF-8">
<html>
    <head>
        <title>thanks for signup</title>
        <link rel="stylesheet" type="text/css" href="dbms.css">
        <style>
            .background-f {
                height: 100%;
                background-image: linear-gradient(to bottom right, rgb(209, 209, 209), rgb(187, 26, 26), rgb(41, 41, 41));
            }

            .background-t {
                height: 100%;
                background-image: linear-gradient(to bottom right, rgb(209, 209, 209), rgb(64, 141, 99), rgb(41, 41, 41));
            }

            .more-text {
                font-size: 17px;
                text-align: left;
                line-height: 45px;
                padding-top: 30%;
                padding-left: 15%;
            }
        </style>
    </head>
    
    <?php 
        if($user == "" || $phone == "")
        {
    ?>
        <div class="background-f">
            <div class="outer">
                <div class="more-text">
                    Your user or phone should not be empty <br>
                    Back to 
                    <a href="signup.html">sign up page</a>
                    and try again
                </div>
            </div>
        </div>
    <?php
        }
        else
        {
            $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
                or die('Could not connect : ' . pg_last_error());
            $query = "INSERT INTO passenger(p_name, p_phone, p_user) 
                    VALUES (" . fixtype($name) . "," . fixtype($phone) . "," . fixtype($user) . ")";
            $result = pg_query($query);
            // or die('ERROR : ' . pg_last_error())
            
    ?>
        <div class="background-t">
            <div class="outer">
                <div class="more-text">
    <?php
            if(!$result)
            {
    ?>
                    Maybe the user or the phone already exists<br>
                    Please <a href="signup.html">try again</a> 
                    or <a href="login.html">log in</a>
    <?php   
            }
            else
            {
    ?>

                    Welcome <?php echo $name; ?> <br>
                    Your user is <?php echo $user; ?> <br>
                    Your phone is <?php echo $phone; ?> <br>
                    Your role is passenger <br>
                    Back to <a href="login.html">log in page</a> 
                </div>
            </div>
        </div>
    <?php 
            }
        } 
    ?>
</html>