<?php
    $dbconn = pg_connect("dbname=tpch user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());
    $query='SELECT * FROM station limit 1;';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());
    $line = pg_fetch_array($result, null, PGSQL_ASSOC);

    $str = base64_encode(serialize($line));
    echo $str . "<br>";
    echo '<a href=test2.php?line=' . $str . '>test</a>';
?>