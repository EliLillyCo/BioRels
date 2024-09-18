<?php



/// Get primary keys values for a given schema
/// Returns an array with the schema.table as key and an array of primary keys as value
function get_primary_keys($schema)
{
	$PRIMARY_KEYS=array();
	$res=runQuery("SELECT nspname, relname, a.attname FROM pg_index i JOIN pg_class c ON c.oid = i.indrelid JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum = any(i.indkey) JOIN pg_namespace n ON n.oid = c.relnamespace WHERE nspname = '".$schema."' AND indisprimary");
	foreach ($res as $line)
	{
		$PRIMARY_KEYS[$schema.'.'.$line['relname']][]=$line['attname'];
	
	}
	return $PRIMARY_KEYS;
}



function process_document($schema,$FILE_NAME)
{
	global $DB_CONN;
	
	/// Initiate a transaction:
	$DB_CONN->beginTransaction();
	try{
		/// Open the linearized file
		$fp=fopen($FILE_NAME,'r');
		if (!$fp)die("Unable to open file ".$FILE_NAME);


		$HEAD=array();
		$ID_MAP=array();
		$N=0;
		while(!feof($fp))
		{
			/// Getting the next line:
			$line=stream_get_line($fp,1000000,"\n");
			/// Ignore empty lines and comments
			if ($line==''||$line=='#')continue;
			
			$tab=explode("\t",$line);
			/// We should expect a TABLE line, otherwise it's an issue:
			if ($tab[0]!='TABLE') die('Unable to parse line '.$line.' - Expecting TABLE ');
			
			/// Get the table name:
			$TABLE=$tab[1];
			
			/// Data array:
			$N=0;
			$TABLE_DATA=array();

			/// Now we ready the block until END
			while(!feof($fp))
			{
				/// Get the current position in the file, so we can come back to it if needed
				$fpos=ftell($fp);
				///Gwt the next line
				$line=stream_get_line($fp,1000000,"\n");
				/// Ignore empty lines and comments
				if ($line==''||$line=='#')continue;
				/// If we reach the end of the block, we break
				if (substr($line,0,3)=='END')
				{
					$tab=explode("\t",$line);
					/// We should expect a END tabulation and the table name.
					/// IF we don't find the table name, it's an issue
					if ($tab[1]!=$TABLE)	die('Unable to parse line '.$line.' - Expecting END '.$TABLE);
					break;
				}
				/// We go back to the previous position
				fseek($fp,$fpos);
				/// We get the next line and process it with fgetcsv to have an array
				$tab=fgetcsv($fp);
				
				/// First line is the header
				if ($N==0)
				{
					$HEAD=$tab;
					$N++;
					continue;
				}
				$N++;
				/// Next lines are the data
				/// We should have the same number of columns as the header
				if (count($HEAD)!=count($tab))
				{
					print_r($HEAD);
					print_r($tab);
					throw new Exception('Invalid number of columns in '.$TABLE.' - '.count($HEAD).' vs '.count($tab));
				}
				/// We combine the header and the data to have an associative array header_key=>value
				$res=array_combine($HEAD,$tab);
				
				/// And we add it to the data array
				$TABLE_DATA[]=$res;

				if (count($TABLE_DATA)<50000)continue;
				/// Above 50K records, we insert the data
				echo "PROCESSING ".$TABLE." - ".count($TABLE_DATA)." records \n";
				insert_table_data($schema,$TABLE,$TABLE_DATA,$ID_MAP);
				echo "END PROCESSING ".$TABLE." - ".count($TABLE_DATA)." records \n";
				$TABLE_DATA=array();
			}
			/// We insert the remaining data
			echo "PROCESSING ".$TABLE." - ".count($TABLE_DATA)." records \n";
			insert_table_data($schema,$TABLE,$TABLE_DATA,$ID_MAP);
			echo "END PROCESSING ".$TABLE." - ".count($TABLE_DATA)." records \n";
		}
		fclose($fp);

		/// Commit the transaction
		$DB_CONN->commit();
	}catch(Exception $e)
	{
		//print_R($ID_MAP);
		/// Rollback the transaction if issue
		$DB_CONN->rollBack();
		echo ($e->getMessage());
	}
}

function quoteValues(&$VALUES)
{
	foreach ($VALUES as &$VALUE)
	{
		if ($VALUE=='NULL')continue;
		$VALUE="'".str_replace("'","''",$VALUE)."'";
	}

}




function insert_table_data($schema,$TABLE_NAME,&$TABLE_DATA,&$ID_MAP)
{
	global $DEBUG;
	global $NOT_NULL;
	global $JSON_PARENT_FOREIGN;
	global $PRIMARY_KEYS;
	global $KEYS;

	/// We need the columns that makes a record unique for that table, otherwise we cannot check if the record already exists
	if (!isset($KEYS[$TABLE_NAME])) throw new Exception("Unable to find keys for table ".$TABLE_NAME);

	/// We also need to know the primary keys:
	if (!isset($PRIMARY_KEYS[$schema.'.'.$TABLE_NAME])) throw new Exception("Unable to find primary keys for table ".$schema.'.'.$TABLE_NAME);
	$PRIMARY_KEY=$PRIMARY_KEYS[$schema.'.'.$TABLE_NAME];
	
	/// $IS_MAP is used to store the primary keys of the records we are inserting
	/// So that the next tables that depends on it can use the primary keys
	if (!isset($ID_MAP[$TABLE_NAME]))$ID_MAP[$TABLE_NAME]=array();


	/// We need to know the foreign keys for that table
	$PARENT_RULES=&$JSON_PARENT_FOREIGN['PARENT'][$schema.'.'.$TABLE_NAME];
	
	/// First things first, we need to convert any foreign key values defined in the file to the primary key of the parent table
	if ($PARENT_RULES!=null)	
		/// Loop through the data
		foreach ($TABLE_DATA as &$ENTRY)
		{
			/// Therefore we need to check the PARENT_RULES array
			foreach ($PARENT_RULES as $TBL_COL=>&$PARENT_INFO)
			{
				/// Not to worry about it if the value is NULL
				if ($ENTRY[$TBL_COL]==''||$ENTRY[$TBL_COL]=='NULL')continue;

				/// If the value is not NULL, we need to check if it's in the ID_MAP
				// Which means it has been inserted already
				/// If not, we throw an exception
				if (!isset($ID_MAP[$PARENT_INFO['TABLE']][$ENTRY[$TBL_COL]]))	
				{	
					throw new Exception('Unable to find mapping id for '.$TBL_COL.' While inserting '.$schema.'.'.$TABLE_NAME."\t".implode(";",$ENTRY));
				}
				/// Otherwise we replace the value by the primary key
				$ENTRY[$TBL_COL]=$ID_MAP[$PARENT_INFO['TABLE']][$ENTRY[$TBL_COL]];
			}
		}

	/// Now we break it down into chunks of 1000 records to get inserted	
	$CHUNKS=array_chunk($TABLE_DATA,1000);

	foreach ($CHUNKS as &$CHUNK) insert_table_data_chunk($CHUNK,$schema,$TABLE_NAME,$ID_MAP);
}



function insert_table_data_chunk(&$TABLE_DATA,$schema,$TABLE_NAME,&$ID_MAP)
{
	global $NOT_NULL;
	global $JSON_PARENT_FOREIGN;
	global $PRIMARY_KEYS;
	global $KEYS;
	global $DEBUG;

	/// Getting the primary keys specific to that table
 	$PRIMARY_KEY=$PRIMARY_KEYS[$schema.'.'.$TABLE_NAME];

	/// And the keys
	$TABLE_KEYS=&$KEYS[$TABLE_NAME];
	

	/// The first step is to check if the record is already in the database or not
	/// A challenge arise when some of the values are NULL since in a group query the NULL is not recognized
	/// Therefore, we need to do multiple queries depending on which set of column is null or not
	/// Example. Given 3 columns A B and C, in which B and C are nullable
	/// We can have 4 cases:
	/// A B C
	/// 1 1 1	=> (A,B,C) IN ((x,x,x),(x,x,x),(x,x,x))
	/// 1 1 0   => (A,B) IN ((x,x),(x,x),(x,x)) AND C IS NULL
	/// 1 0 1	=> (A,C) IN ((x,x),(x,x),(x,x)) AND B IS NULL
	/// 1 0 0	=> (A) IN ((x),(x),(x)) AND B IS NULL AND C IS NULL
	/// That's what query_params is about. It's an array with the query head as key and the values as values
	

	$QUERY_PARAMS=array();

	/// ID_PARAMS is used to map the different values represented as a hash to the position in the TABLE_DATA array
	/// This will help us to know which record we are dealing with when we get the result of the query
	$ID_PARAMS=array();

	foreach ($TABLE_DATA as $ENTRY_POS=>&$ENTRY)
	{
		/// The string ID is used to store the values of the record so we can hash it
		$STR_ID='';
		
		/// Temporary array to store the values
		$VALUES=array();
		foreach ($TABLE_KEYS as $KEY)
		{
			if (!isset($ENTRY[$KEY]))die('Key '.$KEY.' not found in entry '.$TABLE_NAME);
			/// The value is NULL? Special case applies:
			
			if ($ENTRY[$KEY]=='NULL')
			{
			//	echo $schema.'.'.$TABLE_NAME.'.'.$KEY."\n";
				$STR_ID.='|||';
				/// If the column is not nullable, then we need to replace it by either IS NULL or ='' depending on the type
				if (isset($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY]))
				{
					if ($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY][0])
					{
						$VALUES[$KEY]="''";
					}
					else 
					{
						//echo "NOT\n";
						$sub_q="NULL";
						$VALUES[$KEY]=$sub_q;
					}
				}
				else 
				{
					$VALUES[$KEY]="NULL";
				}
			}
			/// Otherwise easy enough $KEY=>$VALUE
			else 
			{
				$VALUES[$KEY]="'".str_replace("'","''",$ENTRY[$KEY])."'";
				$STR_ID.=$ENTRY[$KEY]."|||";
			}
		}

		/// Now we need to separate the values in 2 arrays, one for the values that are not NULL and one for the values that are NULL
		$STR_NULL=array();
		$STR_HEAD=array();$VALUE_HEAD=array();
		foreach ($VALUES as $KEY_V=>&$VALUE_V)
		{
			if ($VALUE_V!='NULL')
			{
				$STR_HEAD[]=$KEY_V;
				$VALUE_HEAD[]=$VALUE_V;
			}
			else $STR_NULL[]=$KEY_V.' IS NULL';
		}
		/// We build the query head
		$STR_LEN_NULL=implode(' AND ',$STR_NULL);
		if ($STR_LEN_NULL!='')$STR_LEN_NULL=$STR_LEN_NULL.' AND ';
		$STR_KEY_HEAD="SELECT * FROM ".$schema.'.'.$TABLE_NAME." WHERE  ".$STR_LEN_NULL.' ('.implode(",",$STR_HEAD).') IN (';

		/// We store the query head and the values
		$QUERY_PARAMS[$STR_KEY_HEAD][]='('.implode(",",$VALUE_HEAD).")";

		/// We store the position of the record in the TABLE_DATA array
		$ID_PARAMS[md5($STR_ID)]=$ENTRY_POS;
	}

	if ($DEBUG)print_R($QUERY_PARAMS);
	
	/// Now we run the queries
	/// N_FOUND is used to count the number of records found
	$N_FOUND=0;
	foreach ($QUERY_PARAMS as $STR_KEY_HEAD=>&$QUERY_VALUES)
	{
		/// Merge the query head with the values
		$query=$STR_KEY_HEAD.' '.implode(",",$QUERY_VALUES).")";
		$res=runQuery($query);
		if ($res===false)throw new Exception("Unable to run query ".$query);

		
		
		/// The record exists, we store the primary key
		foreach ($res as &$line)
		{
			
			/// So first we need to create the hash based on the value
			$str_k='';
			foreach ($TABLE_KEYS as $KEY)
			{
				if ($line[$KEY]=='NULL')$str_k.='|||';
				else
				$str_k.=$line[$KEY]."|||";
			}
			$hash=md5($str_k);
			//echo implode("\t",$line)."\t".$hash."\n";
			/// So we can find the corresponding position of the record in TABLE_DATA
			if (!isset($ID_PARAMS[$hash]))throw new Exception("Unable to find entry in ID_PARAMS");

			/// Now technically, we should not have multiple records found, so if DB_ID is already set, we throw an exception
			if (isset($TABLE_DATA[$ID_PARAMS[$hash]]['DB_ID']))
			{print_R($TABLE_DATA[$ID_PARAMS[$hash]]);
				throw new Exception("Multiple records found");
			}

			/// Reference to the record
			$ENTRY=&$TABLE_DATA[$ID_PARAMS[$hash]];
			//	echo "\t=>".$ID_PARAMS[$hash]."\t==>".implode("\t",$ENTRY)."\n";
			/// We store the primary key
			$ENTRY['DB_ID']=$line[$PRIMARY_KEY[0]];

			/// Then we need to store the primary key in the ID_MAP,
			/// which will help mapping for a given table the ID in the file to the ID in the database
			$str_k='';
			$db_k='';
			foreach ($PRIMARY_KEY as $K)
			{
				$str_k.=$ENTRY[$K].'||';
				$db_k.=$line[$K].'||';
			}
			if ($DEBUG)echo $TABLE_NAME.' '.substr($str_k,0,-2).' '.substr($db_k,0,-2)."\n";
			$ID_MAP[$TABLE_NAME][substr($str_k,0,-2)]=substr($db_k,0,-2);

			++$N_FOUND;
		}
	}

	echo "FOUND ".$N_FOUND."/".count($TABLE_DATA)." records\n";
	
	/// All records are found, we can return as the job is done
	if ($N_FOUND==count($TABLE_DATA))return;


	//// So we need to know the primary key for that table
	/// Get it's max value so we can increment it
	$MAX_PK=-1;
	$PK=null;
	if (count($PRIMARY_KEY)==1)
	{
		if ($DEBUG) echo "SELECT MAX(".$PRIMARY_KEY[0].") m FROM ".$schema.'.'.$TABLE_NAME."\n";
		$res=runQuery("SELECT MAX(".$PRIMARY_KEY[0].") m FROM ".$schema.'.'.$TABLE_NAME);
		if ($res[0]['m']!='')					$MAX_PK=$res[0]['m'];
		else $MAX_PK=0;
		$PK=$PRIMARY_KEY[0];
	}else throw new Exception("Multiple primary keys for table ".$schema.'.'.$TABLE_NAME);
	
	if ($DEBUG)echo "MAX PK : ".$MAX_PK."\t".$PK;

	/// Now we build the single query with multiple insert records
	$query="INSERT INTO ".$schema.'.'.$TABLE_NAME." (";
	/// The easiest is to create arrays of keys and values and then implode them
	
	$QUERY_KEYS=array();
	$RECORDS=array();

	foreach ($TABLE_DATA as $ENTRY_POS=>&$ENTRY)
	{
		if (isset($ENTRY['DB_ID']))continue;
		if ($DEBUG)print_R($ENTRY);

		/// First record to process, we extract the keys
		if ($QUERY_KEYS==array())
		{
			$QUERY_KEYS=array_keys($ENTRY);
		}

		/// Then the values:
		$VALUES=array();
		
		foreach ($ENTRY as $KEY=>&$VALUE)
		{
			/// Special case for NULL values, we need to check if the column is nullable
			/// and if it is, we need to replace it by NULL or ''
			
			if ($ENTRY[$KEY]=='NULL')
			{
				if (isset($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY]))
				{
					//print_R($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY]);
					if  ($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY][0])
					{
						$VALUES[]="''";
					}
					else 
					{
						if ($NOT_NULL[$schema.'.'.$TABLE_NAME.'.'.$KEY][1])$VALUES[]="NULL";
						else $VALUES[]="NULL";
					}
				}
				else $VALUES[]='NULL';
			}
			else if ($PK!=null && $PK==$KEY)
			{
				$MAX_PK++;
				$VALUES[]=$MAX_PK;
			}else  $VALUES[]="'".str_replace("'","''",$VALUE)."'";
		}
		/// concatenate the values into a string and add that to the record array
		$RECORDS[]="(".implode(",",$VALUES).")\n";
	}
	if ($RECORDS==array())return;

	/// Now we create the complete query and request to return everything
	$query.=implode(",",$QUERY_KEYS).") VALUES ".implode(",",$RECORDS)." RETURNING *";
	
	if ($DEBUG)echo $query."\n";
	
	$res=runQuery($query);
	if ($DEBUG)print_R($res);
	if ($res===false)throw new Exception("Unable to run query ".$query);
	
	//print_r($ID_PARAMS);
	foreach ($res as &$line)
	{
		if ($DEBUG)print_R($line);
		
		/// Similar to the previous case, we need to create a hash to find the corresponding record in TABLE_DATA
		$str_k='';
		foreach ($TABLE_KEYS as $KEY)
		{
			$str_k.=$line[$KEY]."|||";
		}
		$hash=md5($str_k);
		if (!isset($ID_PARAMS[$hash]))throw new Exception("Unable to find entry in ID_PARAMS");
		/// Reference to the record
		$ENTRY=&$TABLE_DATA[$ID_PARAMS[$hash]];
		/// Then we need to store the primary key in the ID_MAP,
			/// which will help mapping for a given table the ID in the file to the ID in the database
		$ID_MAP[$TABLE_NAME][$ENTRY[$PK]]=$line[$PK];
		if ($DEBUG)
		{
			print_R($ENTRY);
			echo $ENTRY[$PK].'>>>>'.$line[$PK]."\n";
			echo "#####\n";
		}
	}
	
	
	
}






function search_record($schema,$table,$column,$list)
{
	if ($list==array())return array();
	$list=array_unique($list);
	foreach ($list as &$val)$val=str_replace("'","''",$val);
	$RESULTS=array();
	$query="SELECT * FROM ".$schema.'.'.$table." WHERE ".$column." IN ('".implode("','",$list)."')";
	
	$RESULTS=runQuery($query);
	// if (count($list)>count($RESULTS))
	// {
	// 	print_r($list);
	// 	print_r($RESULTS);
	// 	die("Unable to find all the records in ".$table."-".$column."-".count($list)."-".count($RESULTS));	
	// }
	echo "\t".$table.'-'.$column."\t".count($list)."\t====>".count($RESULTS)."\n";
	return $RESULTS;

}


function break_down($CHILD_LINE,$TYPE)
{
	//prot_name_map<prot_name
	$curr_pos=0;
	$len=strlen($CHILD_LINE);
	$LIST=array();
	$N=0;
	if ($TYPE)$CURR_RULE='<';
	else 	  $CURR_RULE='>';
	do
	{
		$posL=strpos($CHILD_LINE,'<',$curr_pos);
		$posR=strpos($CHILD_LINE,'>',$curr_pos);
		if ($posL===false && $posR===false)
		{
			$LIST[]=array('RULE'=>substr($CHILD_LINE,$curr_pos),'VALUE'=>array(),'TYPE'=>$CURR_RULE);
			break;
		}
		if ($posL===false && $posR!==false)
		{
			$LIST[]=array('RULE'=>substr($CHILD_LINE,$curr_pos,$posR-$curr_pos),'VALUE'=>array(),'TYPE'=>$CURR_RULE);
			$CURR_RULE=substr($CHILD_LINE,$posR,1);
			$curr_pos=$posR+1;
		}
		else if ($posL!==false && $posR===false)
		{
			$LIST[]=array('RULE'=>substr($CHILD_LINE,$curr_pos,$posL-$curr_pos),'VALUE'=>array(),'TYPE'=>$CURR_RULE);
			$CURR_RULE=substr($CHILD_LINE,$posL,1);
			$curr_pos=$posL+1;
		}
		else
		{
			$pos=min($posL,$posR);
			$LIST[]=array('RULE'=>substr($CHILD_LINE,$curr_pos,$pos-$curr_pos),'VALUE'=>array(),'TYPE'=>$CURR_RULE);
			$CURR_RULE=substr($CHILD_LINE,$pos,1);
			$curr_pos=$pos+1;
		}
		++$N;
		
	}while($N<50);
	return $LIST;
}



function getNotNullCols($SCHEMA)
{
	global $GLB_VAR;
	$NOT_NULL=array();
	$res=runQuery("select table_name, column_name,data_type,is_nullable
		from information_schema.columns
		where table_schema = '".$SCHEMA."'");
		
	foreach ($res as $line)
	{
		
		$NOT_NULL[$SCHEMA.'.'.$line['table_name'].'.'.$line['column_name']]=array($line['is_nullable']!='YES',$line['data_type']);
		
	}

	return $NOT_NULL;
}






function json_to_csv($RESULTS,$HIERARCHY,$FILENAME)
{
	$RECORDS=array();
	linearize($RESULTS,$RECORDS);
	ksort($HIERARCHY);
	$fp=fopen($FILENAME,'w');
	foreach ($HIERARCHY as $LEVEL=>$LIST)
	{
		foreach ($LIST as $TABLE)
		{
			if (!isset($RECORDS[$TABLE]))continue;
			fputs($fp,"TABLE\t".$TABLE."\n");
			$HEADER=false;
			$REC_HASH=array();
			foreach ($RECORDS[$TABLE] as $RECORD)
			{
				if (isset($RECORD['TABLE']))unset($RECORD['TABLE']);
				$HASH=md5(implode("|",$RECORD));
				if (isset($REC_HASH[$HASH]))continue;
				$REC_HASH[$HASH]=true;
				if (!$HEADER)
				{
					fputcsv($fp,array_keys($RECORD));
					$HEADER=true;
				}
				foreach ($RECORD as $KEY=>&$VALUE)if ($VALUE=='')$VALUE='NULL';
				fputcsv($fp,$RECORD);
			}
			fputs($fp,"END TABLE\t".$TABLE."\n#\n#\n#\n");
			
		}
	}
	fclose($fp);
}

function linearize(&$RESULTS,&$RECORDS)
{
	foreach ($RESULTS as &$ENTRY)
	{
		$RECORDS[$ENTRY['RECORD']['TABLE']][]=$ENTRY['RECORD'];
		if (isset($ENTRY['PARENT']))
		foreach ($ENTRY['PARENT'] as $LEVEL=>&$PARENTS)
		{
			foreach ($PARENTS as $PARENT_TABLE=>&$PARENT_LIST)
			foreach ($PARENT_LIST as &$PARENT_ENTRY)
			{
				$RECORDS[$PARENT_TABLE][]=$PARENT_ENTRY;
			}	
		}
		if (isset($ENTRY['CHILD']))
		foreach ($ENTRY['CHILD'] as $LEVEL=>&$PARENTS)
		{
			foreach ($PARENTS as $PARENT_TABLE=>&$PARENT_LIST)
			foreach ($PARENT_LIST as &$PARENT_ENTRY)
			{
				$RECORDS[$PARENT_TABLE][]=$PARENT_ENTRY;
			}	
		}
		if (isset($ENTRY['SUB']))
		foreach ($ENTRY['SUB'] as &$SUB)
		{
			linearize($SUB,$RECORDS);
		}
	}
}


function loadAPIRules($TG_DIR,&$HIERARCHY,&$KEYS)
{
	$fp=fopen($TG_DIR.'/BACKEND/SCRIPT/BIORJ/BIORJ_RULES','r');
	if (!$fp)die("Unable to open BIORJ_RULES");
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		if ($tab[0]=='BLOCK')$BLOCKS[$tab[1]]=processBlock($fp);
		if ($tab[0]=='KEYS') 
		{
			while(!feof($fp))
			{
				$line=stream_get_line($fp,1000,"\n");
				if ($line=='')continue;
				if ($line=='END')break;
				$tab=array_values(array_filter(explode("\t",$line)));
				$KEYS[$tab[0]]=explode("|",$tab[1]);
				
			}		
		}
	}
	fclose($fp);


	$ORDERS=array();
	$ORDERS_SCORE=array();


	foreach ($BLOCKS as $TBL=>&$LIST)
	foreach ($LIST as $TYPE=>&$RULES)
	{
		if ($TYPE=='KEY')continue;
		$GROUP_RULES=array();
		foreach ($RULES as $RULE=>&$LIST)
		{
			$SUB_LIST=break_down($LIST,($TYPE)=='PARENT'?true:false);

			foreach ($SUB_LIST as $N=>$SUB_RULE)
			{
				$PARENT_RULE=$TBL;
				if ($N>0)$PARENT_RULE=$SUB_LIST[$N-1]['RULE'];
				$RULE_SET=$SUB_RULE['TYPE'].$SUB_RULE['RULE'];
				if (strpos($SUB_RULE['RULE'],':')!==false)
				{
					$tab=explode(':',$SUB_RULE['RULE']);
					$SUB_RULE['RULE']=$tab[0];
					if ($tab[1]=='P')$SUB_RULE['TYPE']=($SUB_RULE['TYPE']=='>'?'<':'>');
				}
				if (strpos($PARENT_RULE,':')!==false)
				{
					$tab=explode(':',$PARENT_RULE);
					$PARENT_RULE=$tab[0];
				}
			//	echo $SUB_RULE['RULE']." ".$PARENT_RULE."\t".$SUB_RULE['TYPE']."\n";
				if (!isset($GROUP_RULES[$N][$PARENT_RULE]))$GROUP_RULES[$N][$PARENT_RULE]=array();
				if (!in_array($RULE_SET,$GROUP_RULES[$N][$PARENT_RULE]))
				$GROUP_RULES[$N][$PARENT_RULE][]=$RULE_SET;

				if (!isset($ORDERS_SCORE[$PARENT_RULE]))$ORDERS_SCORE[$PARENT_RULE]=array(0,0);
				if (!isset($ORDERS_SCORE[$SUB_RULE['RULE']]))$ORDERS_SCORE[$SUB_RULE['RULE']]=array(0,0);


				if ($SUB_RULE['TYPE']=='>')
				{
					//echo "A\n";
					$ORDERS[$PARENT_RULE][]=$SUB_RULE['RULE'];
					$ORDERS_SCORE[$SUB_RULE['RULE']][0]++;
				}
				else 
				{
					//echo "B\n";
					$ORDERS[$SUB_RULE['RULE']][]=$PARENT_RULE;
					$ORDERS_SCORE[$PARENT_RULE][0]++;
				}
				

				
			}
		}
		$RULES=$GROUP_RULES;
		
	}
	// print_R($ORDERS_SCORE);
	// foreach ($ORDERS as $TBL_R=>$LIST_TBL)
	// foreach ($LIST_TBL as $TBLC)
	// {
	// 	echo $TBL_R." ".$TBLC."\n";
	// }
	
	$LEVELS=array();
	foreach ($ORDERS_SCORE as $TBL=>&$SCORE)
	if ($SCORE[0]==0)$LEVELS[0][]=$TBL;
	
	$CURR_LEVEL=1;
	$HAS_CHANGE=false;
	$N=0;
	do
	{
		$HAS_CHANGE=false;
		foreach ($ORDERS as $TBL_R=>$LIST_TBL)
		{
			if (!in_array($TBL_R,$LEVELS[$CURR_LEVEL-1]))continue;
			foreach ($LIST_TBL as $TBLC)
			{
				$LEVELS[$CURR_LEVEL][]=$TBLC;
				$ORDERS_SCORE[$TBLC][1]=$CURR_LEVEL;
				$HAS_CHANGE=true;
			}
		}	
		$CURR_LEVEL++;
		++$N;
		if ($N>50)break;
	}while($HAS_CHANGE);

	foreach ($ORDERS_SCORE as $TBL=>&$SCORE)
	{
		
		$HIERARCHY[$SCORE[1]][]=$TBL;
	}
	ksort($HIERARCHY);


	return $BLOCKS;
}

function processBlock(&$fp)
{
	$BLOCK=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		if ($line[0]=='#')continue;
		$tab=explode("\t",$line);
		if ($tab[0]=='END')break;
		$BLOCK[$tab[0]][]=$tab[1];
	}
	return $BLOCK;
}

function export_record($schema,$table,$column,array $LIST,&$ALL_RESULTS,$W_PARENT=true,$W_CHILD=true)
{
	global $BLOCKS;
	global $JSON_PARENT_FOREIGN;
	if (!isset($BLOCKS[$table]))die('Table not found in API_RULES: '.$table);
	echo "RUN export record ".$schema.'.'.$table."\n";
	$BLOCK=$BLOCKS[$table];
	
	$tmp=runQuery("SELECT * FROM ".$schema.'.'.$table." WHERE ".$column." IN (".implode(",",$LIST).")");
	
	if ($tmp==array())die ("No results");
	
	foreach ($tmp as $line)
	{
		$line['TABLE']=$table;
		$ALL_RESULTS[$table.'-'.$column.'-'.$line[$column]]=array('RECORD'=>$line);
	}
	
	echo "FOUND ".count($ALL_RESULTS)." results\n";
	if ($W_PARENT && isset($BLOCK['PARENT']))
	{
		echo "PROCESS PARENTS\n";
		
		foreach ($ALL_RESULTS as $tag_id => &$RESULT_ENTRY)
		{
			processParent($RESULT_ENTRY,$BLOCK,$schema,$tag_id);
		}
	}
	
	
	if ($W_CHILD && isset($BLOCK['CHILD']))
	{
		echo "PROCESS CHILD\n";
		foreach ($ALL_RESULTS as $tag_id=>&$RESULT_ENTRY)
		{
			processChild($RESULT_ENTRY,$BLOCK,$schema,$tag_id);
		}
		

	}



}


function processParent(&$RESULT_ENTRY,$BLOCK,$schema,$tag_id)
{
	foreach ($BLOCK['PARENT'] as $LEVEL=> $CHILD_LINE)
	{
		foreach ($CHILD_LINE as $PARENT_TABLE=>&$LIST_CHILD_TABLE)
		foreach ($LIST_CHILD_TABLE as $CHILD_TABLE)
		{
			$CURR_ORDER=substr($CHILD_TABLE,0,1);
			$CHILD_TABLE=substr($CHILD_TABLE,1);
			

			/// Two situations can arise for the table name:
			/// 1/ The table name is followed by a ':' and the letter E
			///		This means we want all the data for that entry, including parent and child tables
			/// 2/ The table name is followed by a ':' and a letter P
			///		This means that the parent and child tables are switched, usually because the parent table is a mapping table
			$SUB_CALL=false;
			$SWITCH_PARENT=false;
			if (strpos($CHILD_TABLE,':')!==false)
			{
				$tab=explode(':',$CHILD_TABLE);
				$CHILD_TABLE=$tab[0];
				//echo ">".$CHILD_TABLE."\n";
				if ($tab[1]=='E')$SUB_CALL=true;
				else if ($tab[1]=='P')$SWITCH_PARENT=true;
				else die("Unknown call type ".$tab[1]);
			}
			if (strpos($PARENT_TABLE,':')!==false)
			{
				$tab=explode(':',$PARENT_TABLE);
				$PARENT_TABLE=$tab[0];
				
			}

			echo "######## ".$PARENT_TABLE."\t".$CHILD_TABLE."\n";
			
			$PARENT_COL=array();$CHILD_INFO=null;
			$IS_PARENT=($CURR_ORDER=='>')?true:false;
			if ($SWITCH_PARENT)$IS_PARENT=!$IS_PARENT;
			$CHILD_INFO=findChildInfo($schema,$PARENT_TABLE,$CHILD_TABLE,$PARENT_COL,$IS_PARENT);
			
			if ($PARENT_COL==array())die("Unable to find parent info for |".$CHILD_TABLE.'|');
			if ($CHILD_INFO==null)die("Unable to find child info for ".$CHILD_TABLE);


			$INPUTS=array();
			echo $tag_id."\tPROCESS PARENT ".$LEVEL."\t".$PARENT_TABLE."\t".$CURR_ORDER."\t".$CHILD_TABLE."\t".implode("|",$PARENT_COL)."\n";
			
			/// First level -> Has to be the requested table
			if ($LEVEL==0)
			{
				
				foreach ($PARENT_COL as $CL)
				{
					if ($RESULT_ENTRY['RECORD'][$CL]!='')
					$INPUTS[]=$RESULT_ENTRY['RECORD'][$CL];
				}
			}
			else 
			{
				/// Otherwise it's the previous parent table
				foreach ($PARENT_COL as $CL)
				foreach ($RESULT_ENTRY['PARENT'][$LEVEL-1][$PARENT_TABLE] as &$COL_RESULTS)
				{
					
					if (isset($COL_RESULTS[$CL]))
					{
						$INPUTS[]=$COL_RESULTS[$CL];
					}
				}
			}
			if ($INPUTS==array())
			{
				/// We record we processed it and nothing was found but only if it was not already set
				if (!isset($RESULT_ENTRY['PARENT'][$LEVEL][$CHILD_TABLE]))
				$RESULT_ENTRY['PARENT'][$LEVEL][$CHILD_TABLE]=array();
				continue;
			}
			/// We add it.
			
			/// The results are in $T
			$T=search_record($schema,$CHILD_INFO['TABLE'],$CHILD_INFO['COLUMN'],$INPUTS);
		
			// We check if the array is already set, if not we create it
			if (!isset($RESULT_ENTRY['PARENT'][$LEVEL][$CHILD_TABLE]))
				$RESULT_ENTRY['PARENT'][$LEVEL][$CHILD_TABLE]=array();
			/// Whether it was already existing or not, we add the results
			foreach ($T as $line)
				$RESULT_ENTRY['PARENT'][$LEVEL][$CHILD_TABLE][]=$line;
			


			
			if ($SUB_CALL)
			{
				echo "INITIATE SUB FROM PARENT\t".$CHILD_INFO['TABLE']."\t".$CHILD_INFO['COLUMN']."\t".implode(",",$INPUTS)."\n";
				if (!isset($RESULT_ENTRY['SUB']))$RESULT_ENTRY['SUB']=array();
				$SUB=array();
				foreach ($INPUTS as $K_INPUT=>$TMP_INPUT)
				{
					if (isset($ENTRIES_COVERED[$CHILD_INFO['TABLE'].'-'.$CHILD_INFO['COLUMN'].'-'.$TMP_INPUT]))
					{
						unset($INPUTS[$K_INPUT]);
					}else $ENTRIES_COVERED[$CHILD_INFO['TABLE'].'-'.$CHILD_INFO['COLUMN'].'-'.$TMP_INPUT]=true;
				}
				if ($INPUTS==array())continue;
				export_record($schema,$CHILD_INFO['TABLE'],$CHILD_INFO['COLUMN'],$INPUTS,$SUB,true,true);
				
				foreach ($SUB as &$SUB_ENTRY)
				$RESULT_ENTRY['SUB'][$CHILD_TABLE][]=$SUB_ENTRY;
			}
		}
		
		
	}
	echo $tag_id."\tEND_PARENT\n";
	//print_R($RESULT_ENTRY);
	
}



function processChild(&$RESULT_ENTRY,$BLOCK,$schema,&$tag_id)
{
	global $ENTRIES_COVERED;
	
	foreach ($BLOCK['CHILD'] as $LEVEL=> $CHILD_LINE)
	{
		// echo "\n\n\n\n################################ LEVEL ".$LEVEL."\n";
		// print_R($CHILD_LINE);
		// print_R($RESULT_ENTRY);
		foreach ($CHILD_LINE as $PARENT_TABLE=>&$LIST_CHILD_TABLE)
		foreach ($LIST_CHILD_TABLE as $CHILD_TABLE)
		{
			$CURR_ORDER=substr($CHILD_TABLE,0,1);
			$CHILD_TABLE=substr($CHILD_TABLE,1);
			$SUB_CALL=false;
			if (strpos($CHILD_TABLE,':')!==false)
			{
				$tab=explode(':',$CHILD_TABLE);
				$CHILD_TABLE=$tab[0];
				//echo ">".$CHILD_TABLE."\n";
				$SUB_CALL=true;
			}
			//echo $LEVEL."\t".$PARENT_TABLE."\t".$CURR_ORDER."\t".$CHILD_TABLE."\n";
			$PARENT_COL=array();
			$CHILD_INFO=null;
			$CHILD_INFO=findChildInfo($schema,$PARENT_TABLE,$CHILD_TABLE,$PARENT_COL,($CURR_ORDER=='>')?true:false);
			if ($PARENT_COL==array())die("Unable to find child info for |".$CHILD_TABLE.'| FROM '.$PARENT_TABLE);
			// echo $tag_id."\tPROCESS CHILD ".$LEVEL."\t".$PARENT_TABLE."\t".$CURR_ORDER."\t".$CHILD_TABLE."\n";
			$INPUTS=array();
			if ($LEVEL==0)
			{
				foreach ($PARENT_COL as $CL)
				$INPUTS[]=$RESULT_ENTRY['RECORD'][$CL];
			}
			else 
			{
				foreach ($PARENT_COL as $CL)
				foreach ($RESULT_ENTRY['CHILD'][$LEVEL-1][$PARENT_TABLE] as &$COL_RESULTS)
				{
					if (isset($COL_RESULTS[$CL]))$INPUTS[]=$COL_RESULTS[$CL];
				}
			}
			// echo "INPUTS:";
			// print_R($INPUTS);
			if ($INPUTS==array())
			{
				if (!isset($RESULT_ENTRY['CHILD'][$LEVEL][$CHILD_TABLE]))
				$RESULT_ENTRY['CHILD'][$LEVEL][$CHILD_TABLE]=array();
				continue;
			}
			//echo "\t=>".$PARENT_COL."\t".$CHILD_INFO['TABLE'].":".$CHILD_INFO['COLUMN']."\n";
			//echo "RUN A\t".$LEVEL."\t".$CHILD_TABLE."\n";
			/// The results are in $T
			$T=search_record($schema,$CHILD_INFO['TABLE'],$CHILD_INFO['COLUMN'],$INPUTS);
			// We check if the array is already set, if not we create it
			if (!isset($RESULT_ENTRY['CHILD'][$LEVEL][$CHILD_TABLE]))
				$RESULT_ENTRY['CHILD'][$LEVEL][$CHILD_TABLE]=array();
			/// Whether it was already existing or not, we add the results
			foreach ($T as $line)
				$RESULT_ENTRY['CHILD'][$LEVEL][$CHILD_TABLE][]=$line;
			
			
			if ($SUB_CALL)
			{
				if (!isset($RESULT_ENTRY['SUB']))$RESULT_ENTRY['SUB']=array();
				foreach ($INPUTS as $K_INPUT=>$TMP_INPUT)
				{
					if (isset($ENTRIES_COVERED[$CHILD_INFO['TABLE'].'-'.$CHILD_INFO['COLUMN'].'-'.$TMP_INPUT]))
					{
						unset($INPUTS[$K_INPUT]);
					}else $ENTRIES_COVERED[$CHILD_INFO['TABLE'].'-'.$CHILD_INFO['COLUMN'].'-'.$TMP_INPUT]=true;
				}
				if ($INPUTS==array())continue;
				echo "INITIATE SUB FROM CHILD\t".$CHILD_INFO['TABLE']."\t".$CHILD_INFO['COLUMN']."\t".implode(",",$INPUTS)."\n";
				$SUB=array();
				export_record($schema,$CHILD_INFO['TABLE'],$CHILD_INFO['COLUMN'],$INPUTS,$SUB,true,true);
				foreach ($SUB as &$SUB_ENTRY)
				$RESULT_ENTRY['SUB'][$CHILD_TABLE][]=$SUB_ENTRY;
			}
		}
		
		
	}
	echo $tag_id."\tEND CHILD\n";

}


function findChildInfo($schema,$PARENT_TABLE,$CHILD_TABLE,&$PARENT_COL,$IS_PARENT=true)
{
//	echo "\t\t=>".$schema.'.'.$PARENT_TABLE."\t".$CHILD_TABLE."\n";
	global $JSON_PARENT_FOREIGN;
	if ($IS_PARENT)
	{
		$CHILD_DEPENDENCIES=&$JSON_PARENT_FOREIGN['CHILD'][$schema.'.'.$PARENT_TABLE];
		// echo "CHILD DEPENCNEIS:";
		// print_R($CHILD_DEPENDENCIES);
		if ($CHILD_DEPENDENCIES==null)return array();
		foreach ($CHILD_DEPENDENCIES as $TMP_PARENT_COL=>&$LIST_TBL)
		foreach ($LIST_TBL as $TBL_INFO)
		{
			if ($TBL_INFO['TABLE']!=$CHILD_TABLE)continue;
			$PARENT_COL[]=$TMP_PARENT_COL;
			return $TBL_INFO;
			
		}
	//	echo "####END FIND CHILD\n";
	}
	else
	{
		$CHILD_DEPENDENCIES=&$JSON_PARENT_FOREIGN['CHILD'][$schema.'.'.$CHILD_TABLE];
		// echo "CHILD DEPENCNEIS:";
		// print_r($CHILD_DEPENDENCIES);
		if ($CHILD_DEPENDENCIES==null)return array();
		$TBL_RESULTS=array();
		foreach ($CHILD_DEPENDENCIES as $TMP_PARENT_COL=>&$LIST_TBL)
		foreach ($LIST_TBL as $TBL_INFO)
		{
			//echo "TEST\t".$TMP_PARENT_COL."\t".implode("\t",$TBL_INFO)."\n";
			if ($TBL_INFO['TABLE']!=$PARENT_TABLE)continue;
			$PARENT_COL[]=$TBL_INFO['COLUMN'];
			
			
			$TBL_RESULTS=array('SCHEMA'=>$schema,'TABLE'=>$CHILD_TABLE,'COLUMN'=>$TMP_PARENT_COL);
			
		}
		return $TBL_RESULTS;
		
		//echo "####END FIND CHILD\n";
	}
	return array();
}

?>