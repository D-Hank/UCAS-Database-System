<!DOCTYPE html>
<html>

<head>
    <title>Look up your orders</title>
    <link rel="stylesheet" type="text/css" href="dbms.css">
</head>
<script>
    function nowtime() {
        var date = new Date();
        date.setDate(date.getDate() + 1);
        var ntime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        date.setDate(date.getDate() - 6);
        var ptime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        date.setDate(date.getDate() + 10);
        var atime = date.getFullYear() + '-' +
            prezero(date.getMonth() + 1) + '-' +
            prezero(date.getDate());
        document.getElementById("sdate").value = ntime;
        document.getElementById("sdate").min = ptime;
        document.getElementById("sdate").max = atime;
        document.getElementById("edate").value = ntime;
        document.getElementById("edate").min = ptime;
        document.getElementById("edate").max = atime;
    }

    function prezero(d) {
        return d < 10 ? ('0' + d) : d;
    }
</script>

<body onload="nowtime()">
    <div class="background">
        <div class="outer">
            <div class="title">
                Order
            </div>
            <form action="8_lookup.php" method="post" class="input-form">
                <input type="date" name="sdate" id="sdate" class="input-text">-<input type="date" name="edate" id="edate" class="input-text">
                <!-- html5-->
                <input type="hidden" name="phone" id="phone" class="input-text" value="<?php $phone = $_GET['phone']; echo $phone; ?>">
                <input type="submit" value="check" class="submit">
            </form>
            <br>
            <div class="more-text">
                Start date - End date.
            </div><br>
        </div>
    </div>
</body>

</html>