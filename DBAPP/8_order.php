<!DOCTYPE html>
<html>

<head>
    <title>Look up your orders</title>
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
    Look up your order here!<br> Start date - End date.<br>
    <form action="8_lookup.php" method="post">
        <input type="date" name="sdate" id="sdate">-<input type="date" name="edate" id="edate">
        <!-- html5-->
        <input type="hidden" name="phone" id="phone" value="<?php $phone = '12345678910'; echo $phone; ?>">
        <input type="submit" value="check">
    </form>
    <br>
</body>

</html>