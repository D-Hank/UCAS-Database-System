<?php
    $line = $_GET['line'];
    $array = unserialize(urldecode($line));
    foreach($array as $key=>$value)
    {
        echo $key . '=>' . $value . '<br>';
    }
?>