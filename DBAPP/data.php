<?php

$dbms = 'psql';
$host = 'localhost';
$port = '5434';
$dbname = 'test';
$user = 'ubuntu';
$passwd = 'ubuntu';

$root_path = "./";
$tblpath = $root_path . "table/";
$station_path = $tblpath . "station.tbl";
$inst = "\"\\copy station from '{$station_path}' with csv;\"";

$log_path = $root_path . "update.log";
$py_path = $root_path . "preprocess.py";

?>