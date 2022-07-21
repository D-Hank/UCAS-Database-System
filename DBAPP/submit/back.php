<?php
    $dcity=$_GET['dcity'];
    $acity=$_GET['acity'];
    $ddate=$_GET['ddate'];
    $back_date=date('Y-m-d', strtotime($ddate)+3600*24);
?>
<!DOCTYPE html>
<meta charset="UTF-8">
<html>

<head>
    <title>Check your journal</title>
    <link rel="stylesheet" type="text/css" href="dbms.css">
    <style>
        .background {
            height: 100%;
            background-image: linear-gradient(to right bottom, rgb(209, 209, 209), rgb(190, 74, 194), rgb(66, 46, 179));
        }

        .title {
            line-height: 140px;
            text-align: center;
            font-size: 45px;
            font-weight: bold;
        }

        .submit {
            border: none;
            outline: none;
            background-image: linear-gradient(to right, rgb(207, 194, 255), rgb(250, 181, 181));
            margin-top: 10%;
            margin-left: 20%;
            width: 60%;
            font-size: 17px;
            color: white;
            padding: 17px;
            border-radius: 10px;
        }

        .input-text {
            outline: 0;
            border: 0;
            border-bottom: 1px solid rgb(201, 201, 201);
            background-color: white;
            padding: 4px;
            font-size: 15px;
            width: 66%;
            margin-bottom: 5%;
            margin-left: 17%;
        }
    </style>
</head>
<script>
    function nowtime() {
        var date = new Date();
        date.setDate(date.getDate() + 1);
        var ntime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        date.setDate(date.getDate() - 1);
        var ptime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        date.setDate(date.getDate() + 6);
        var atime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        document.getElementById("date").min = ptime;
        document.getElementById("date").max = atime;
    }

    function prezero(d) {
        return d < 10 ? ('0' + d) : d;
    }
</script>

<body onload="nowtime()">
    <div class="background">
        <div class="outer">
            <div class="title">
            Let's go
            </div> <br>
            <form action="journal.php" method="post" class="input-form">
                <input type="text" name="dcity" placeholder="From" class="input-text" value="<?php echo $dcity ?>">
                <br>
                <input type="text" name="acity" placeholder="To" class="input-text" value="<?php echo $acity ?>">
                <br>
                <input type="date" name="date" id="date" class="input-text" value="<?php echo $back_date ?>">
                <input type="time" name="time" value="00:00" class="input-text">
                <!-- html5-->
                <input type="submit" value="submit" class="submit">
            </form>
        </div>
    </div>
</body>

</html>