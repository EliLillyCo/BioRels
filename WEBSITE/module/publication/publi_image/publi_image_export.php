<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

$smtm=$DB_CONN->prepare('SELECT * from pmc_fulltext_file where pmc_entry_id = (SELECT pmc_entry_id from pmc_entry where pmc_id = :pmc_id) AND file_id=:file_id');
$smtm->execute(['pmc_id'=>$USER_INPUT['PAGE']['VALUE'],'file_id'=>$USER_INPUT['PARAMS'][0] ]);
$res=$smtm->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as &$entry)
{
//echo "A";
    header("Content-type: ".$entry['mime_type']);
    echo stream_get_contents($entry['file_content']);
    
}
exit;


?>