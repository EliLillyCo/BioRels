<?php
if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=array();
try{
$GN_ENTRY_ID=$USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID'];

$MODULE_DATA['GENE_SEQ_LOC']=getGeneSeqLoc($GN_ENTRY_ID);
$MODULE_DATA['TRANSCRIPTS']=getListTranscripts($GN_ENTRY_ID);
}catch(Exception $e)

{
  if (strpos($e->getMessage(),'Error while running query')!==false)$MODULE_DATA['ERROR']="Unable to retrieve gene & transcripts sequences information";
  else $MODULE_DATA['ERROR']=$e->getMessage();

}
?>
