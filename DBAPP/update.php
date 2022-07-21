<?php

include ("data.php");

function customError($errno, $errstr){ 
    echo "<b>Error:</b> [$errno] $errstr";
}
set_error_handler("customError");
//set error handler


//echo "Path : $log_path";
require "$log_path";

$ex = file_exists("./update.log");
$fp = fopen($log_path, "r+");
$update_interval = "+5 days";

// Last update time
$last_time = fgets($fp);
if($last_time == false){
    $last_time = date("Y-m-d");
}

$today = date("Y-m-d");
if($last_time < $today){
    // Notice: remember to delete \n
    $last_id = trim(fgets($fp));
    $start_date = date("Y-m-d", strtotime($update_interval, strtotime($last_time)));
    $end_date = date("Y-m-d", strtotime($update_interval, strtotime($today)));
    $update_script = "python3 preprocess.py --ticket=True --id={$last_id} --start={$start_date} --end={$end_date}";
    $new_id = exec($update_script);

    fseek($fp, 0, SEEK_SET);
    fputs($fp, $today."\n");
    fputs($fp, $new_id."\n");
}

fclose($fp);

?>