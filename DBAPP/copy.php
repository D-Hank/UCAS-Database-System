<?php

include ("data.php");

function exec_client_instruct($user, $dbname, $port, $inst){
    $command = "psql -p {$port} -U {$user} -d {$dbname} -c {$inst}";
    exec($command);
}

$inst = 
"\"INSERT INTO passenger VALUES ('12345678910', 'ucas', 'dhk');
UPDATE ticket SET t_status='Sold' where t_id=20476;
UPDATE ticket SET t_status='Sold', t_int_end=1049 where t_id=1;
INSERT INTO ticket VALUES (192676, 'G7024', '2022-05-11', 'A', 'Available', 1049, 481, 'H');
INSERT INTO ordering VALUES (1, '12345678910', 627.5, '2022-05-11 08:52:00', 'Normal');
INSERT INTO has_ticket VALUES (1, 1);
INSERT INTO has_ticket VALUES (20476, 1);
\"";
exec_client_instruct($user, $dbname, $port, $inst);

?>