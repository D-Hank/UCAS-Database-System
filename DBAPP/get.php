<?php
$host='localhost';
$dbname = 'test';
$user = 'ubuntu';
$port = '5434';
$passwd = 'ubuntu';

echo $_POST["city_name"];

$dbconn = pg_connect("host=$host dbname=$dbname user=$user port=$port password=$passwd") or die('Connection failed: '.pg_last_error());

$query = 'SELECT st_name, st_city FROM station';
$result = pg_query($query) or die('Query failed: '.pg_last_error());

echo "<table\n>";
while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    echo "\t<tr>\n";
    foreach ($line as $col_value) {
        echo "\t\t<td>$col_value</td>\n";
    }
    echo "\t</tr>\n";
}
echo "</table>\n";

pg_free_result($result);
pg_close($dbconn);

?>