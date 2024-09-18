<?php


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////// GENE ECOSYSTEM //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////// TAXON  //////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Taxon Search By NCBI Tax ID
// Function: get_taxon_by_tax_id
// Description: Search for a taxon by using its Tax ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Parameter: COMPLETE | Complete taxon information | boolean | false | optional | Default: false
// Return: Taxon ID, scientific name, common name, taxonomic lineage and child taxonomic entries
// Ecosystem: Genomics:Species
// Example: php biorels_api.php get_taxon_by_tax_id -TAX_ID 9606
// $[/API]
function get_taxon_by_tax_id(int $TAX_ID,$COMPLETE=false)
{
    $query="SELECT * FROM taxon WHERE tax_id='".$TAX_ID."'";
	$res=runQuery($query);
	if (!$COMPLETE) return $res;
	foreach ($res as &$line)
	{
		$line['CHILD']=runQuery("SELECT t.*,th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t
		 WHERE th1.taxon_id=".$line['taxon_id']." 
		 AND th1.level_left < th2.level_left
		 AND th1.level_right > th2.level_right
		 AND th2.taxon_id=t.taxon_id
		 ORDER BY th2.tax_level DESC
		 ");
		 $line['PARENT']=runQuery("SELECT t.*,th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t
		 WHERE th1.taxon_id=".$line['taxon_id']." 
		 AND th1.level_left > th2.level_left
		 AND th1.level_right < th2.level_right
		 AND th2.taxon_id=t.taxon_id
		 ORDER BY th2.tax_level ASC
		 ");
	}
	return $res;
}


// $[API]
// Title: Taxon Search By Scientific Name
// Function: get_taxon_by_scientific_name
// Description: Search for a taxon by using its scientific name
// Parameter: SCIENTIFIC_NAME | Scientific name | string | Homo sapiens | required
// Ecosystem: Genomics:Species
// Example: php biorels_api.php get_taxon_by_scientific_name -SCIENTIFIC_NAME
// $[/API]
function get_taxon_by_scientific_name($SCIENTIFIC_NAME)
{
	$query="SELECT * FROM taxon WHERE scientific_name='".$SCIENTIFIC_NAME."'";
	$res=runQuery($query);
	
	if ($res==array())
	{
		$query="SELECT * FROM taxon WHERE scientific_name LIKE '%".$SCIENTIFIC_NAME."%'";
		$res=runQuery($query);
	}
	foreach ($res as &$line)
	{
		$line['CHILD']=runQuery("SELECT t.*,th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t
		 WHERE th1.taxon_id=".$line['taxon_id']." 
		 AND th1.level_left < th2.level_left
		 AND th1.level_right > th2.level_right
		 AND th2.taxon_id=t.taxon_id
		 ORDER BY th2.tax_level DESC
		 ");
		 $line['PARENT']=runQuery("SELECT t.*,th2.tax_level FROM taxon_tree th1, taxon_tree th2, taxon t
		 WHERE th1.taxon_id=".$line['taxon_id']." 
		 AND th1.level_left > th2.level_left
		 AND th1.level_right < th2.level_right
		 AND th2.taxon_id=t.taxon_id
		 ORDER BY th2.tax_level ASC
		 ");
	}
	return $res;
}


// $[API]
// Title: Get taxon lineage by taxon ID
// Function: get_taxon_parent_lineage
// Description: Get the taxonomic lineage of a taxon by using its taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Return: Taxon ID, scientific name, common name, taxonomic lineage
// Ecosystem: Genomics:Species
// Example: php biorels_api.php get_taxon_parent_lineage -TAX_ID 9606
// $[/API]
function get_taxon_parent_lineage(int $TAX_ID)
{
		return runQuery("SELECT t.*,th2.tax_level FROM taxon tr,taxon_tree th1, taxon_tree th2, taxon t
		 WHERE th1.taxon_id=tr.taxon_id 
		 AND tr.tax_id='".$TAX_ID."'
		 AND th1.level_left > th2.level_left
		 AND th1.level_right < th2.level_right
		 AND th2.taxon_id=t.taxon_id
		 ORDER BY th2.tax_level ASC
		 ");
	
}

// $[API]
// Title: Get taxon lineage by taxon ID
// Function: get_taxon_child_lineage
// Description: Get the taxonomic lineage of a taxon by using its taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Parameter: DEPTH | Depth to add to the requested taxon's level | int | 2 | optional | Default: -1
// Return: Taxon ID, scientific name, common name, taxonomic lineage
// Ecosystem: Genomics:Species
// Example: php biorels_api.php get_taxon_child_lineage -TAX_ID 9606
// $[/API]
function get_taxon_child_lineage(int $TAX_ID, int $DEPTH=-1)
{
	$query="SELECT t.*,th2.tax_level FROM taxon tr,taxon_tree th1, taxon_tree th2, taxon t
	WHERE th1.taxon_id=tr.taxon_id 
	AND tr.tax_id='".$TAX_ID."'
	AND th1.level_left < th2.level_left
	AND th1.level_right > th2.level_right
	AND th2.taxon_id=t.taxon_id ";
	if ($DEPTH!=-1)$query.=" AND th2.tax_level = th1.tax_level+".$DEPTH;
	$query.= " ORDER BY th2.tax_level ASC
	";
	return runQuery($query);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// TAXON - LOCUS & GENE ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Get all chromosome by taxon ID
// Function: get_chromosome_for_taxon
// Description: Get all chromosome for a taxon by using its NCBI taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Return: Locus ID, locus name, taxon ID, scientific name, taxonomic identifier
// Ecosystem: Genomics:Locus & Gene|Species
// Example: php biorels_api.php get_chromosome_for_taxon -TAX_ID 9606
// $[/API]
function get_chromosome_for_taxon(int $TAX_ID)
{
	$query="SELECT c.* FROM chromosome c, taxon t
	WHERE c.taxon_id = t.taxon_id
	AND tax_id='".$TAX_ID."'";
	return runQuery($query);

}


// $[API]
// Title: Get all chromosome map for taxon ID
// Function: get_chromosome_map_for_taxon
// Description: Get all chromosome map for a taxon by using its NCBI taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Parameter: CHROMOSOME | Chromosome name | string | 1 |optional | Default: ''
// Parameter: ARM | Chromosome arm | string | p | optional | Default: ''
// Parameter: BAND | Chromosome band | string | 1 | optional | Default: ''
// Parameter: SUBBAND | Chromosome subband | string | 1 | optional | 
// Return: Group by chromosome name, returns chromosome map _id, chromosome number, map location, position, arm, ban, subband.
// Ecosystem: Genomics:Locus & Gene|Species
// Example: php biorels_api.php get_chromosome_map_for_taxon -TAX_ID 9606 -CHROMOSOME 1
// Example: php biorels_api.php get_chromosome_map_for_taxon -TAX_ID 9606 -ARM p
// $[/API]
function get_chromosome_map_for_taxon(int $TAX_ID, string $CHROMOSOME='',$ARM='',$BAND='',$SUBBAND='')
{
	$query="SELECT cm.*,chr_num FROM chromosome c, chr_map cm, taxon t
	WHERE c.taxon_id = t.taxon_id
	AND cm.chr_id = c.chr_id
	AND tax_id='".$TAX_ID."' ".
	(($CHROMOSOME!='')?'AND chr_num=\''.$CHROMOSOME.'\'':'').
	(($ARM!='')?'AND arm=\''.$ARM.'\'':'').
	(($BAND!='')?'AND band=\''.$BAND.'\'':'').
	(($SUBBAND!='')?'AND subband=\''.$SUBBAND.'\'':'');
	" ORDER BY chr_num,map_location ASC";
	
	$res=runQuery($query);
	$DATA=array();
	foreach ($res as $line)
	{
		$DATA[$line['chr_num']][]=$line;
	}
	return $DATA;
}





// $[API]
// Title: Get all gene for taxon ID
// Function: get_gene_for_taxon
// Description: Get all gene for a taxon by using its NCBI taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Return: Gene ID, gene symbol, gene name, taxon ID, scientific name, taxonomic identifier
// Ecosystem: Genomics:Locus & Gene|Species
// Example: php biorels_api.php get_gene_for_taxon -TAX_ID 9606
// Warning: Long query execution time
// $[/API]
function get_gene_for_taxon(int $TAX_ID)
{
	ini_set('memory_limit','500M');
	$query="SELECT chr_num,map_location, c.chr_id, cm.chr_map_id, ge.* FROM chr_gn_map cgm, gn_entry ge,chromosome c, chr_map cm, taxon t
	WHERE cgm.chr_map_id = cm.chr_map_id
	AND cgm.gn_entry_Id = ge.gn_Entry_Id
	AND c.taxon_id = t.taxon_id
	AND cm.chr_id = c.chr_id
	AND tax_id='".$TAX_ID."' ORDER BY chr_num,map_location ASC";
	$res=runQuery($query);
	$DATA=array();
	foreach ($res as $line)
	{
		$DATA[$line['chr_num']][$line['map_location']][]=$line;
	}
	return $DATA;
}



// $[API]
// Title: Get all gene for chromosome
// Function: get_gene_for_chromosome
// Description: Get all gene for a chromosome by using its NCBI taxon ID and chromosome name
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Parameter: CHROMOSOME | Chromosome name | string | 1 | required
// Parameter: MAP_LOCATION | Map location | string | 1p12 | optional
// Return: NCBI Gene ID, symbol ,full name, gene type, map location, chromosome number, status
// Ecosystem: Genomics:Locus & Gene|Species
// Example: php biorels_api.php get_gene_for_chromosome -TAX_ID 9606 -CHROMOSOME 1
// $[/API]
function get_gene_for_chromosome(int $TAX_ID,string $CHROMOSOME, $MAP_LOCATION='')
{
	$query="SELECT chr_num,map_location, c.chr_id, cm.chr_map_id, ge.* FROM chr_gn_map cgm, gn_entry ge,chromosome c, chr_map cm, taxon t
	WHERE cgm.chr_map_id = cm.chr_map_id
	AND cgm.gn_entry_Id = ge.gn_Entry_Id
	AND c.taxon_id = t.taxon_id
	AND cm.chr_id = c.chr_id
	AND tax_id='".$TAX_ID."' 
	AND chr_num='".$CHROMOSOME."' 
	".(($MAP_LOCATION!='')?'AND map_location=\''.$MAP_LOCATION.'\'':'').
	" ORDER BY chr_num,map_location ASC";
	$res=runQuery($query);
	$DATA=array();
	foreach ($res as $line)
	{
		$DATA[$line['chr_num']][$line['map_location']][]=$line;
	}
	return $DATA;
}



// $[API]
// Title: Get gene location by gene ID
// Function: get_gene_location
// Description: Get gene location by using its NCBI gene ID
// Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
// Return: Taxon ID, scientific name, taxonomic identifier, chromosome number, map location, gene ID, gene symbol, gene name, gene type, gene status
// Ecosystem: Genomics:Locus & Gene|Species
// Example: php biorels_api.php get_gene_location -GENE_ID 1017
// $[/API]
function get_gene_location(int $GENE_ID)
{
	$query="SELECT * FROM chr_gn_map cgm, gn_entry ge,chromosome c, chr_map cm, taxon t
	WHERE cgm.chr_map_id = cm.chr_map_id
	AND cgm.gn_entry_Id = ge.gn_Entry_Id
	AND c.taxon_id = t.taxon_id
	AND c.chr_id = cm.chr_id
	AND ge.gene_id='".$GENE_ID."' 
	ORDER BY chr_num,map_location ASC";
	$res=runQuery($query);
	return $res;

}



// $[API]
// Title: Gene Search By NCBI ID
// Function: get_gene_by_gene_id
// Description: Search for a gene by using its gene ID
// Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
// Ecosystem: Genomics:gene
// Example: php biorels_api.php get_gene_by_gene_id -GENE_ID 1017
// $[/API]
function get_gene_by_gene_id(int $GENE_ID)
{
    global $GLB_CONFIG;
    $query = "
            SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene_sp
            WHERE GENE_ID=$GENE_ID 
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
            ";

    $res = runQuery($query);


    if (count($res) == 0)  {

        $NEW_GENE_ID = '';
        $n = 0;
        do {

            ++$n;

            $res = runQuery("SELECT alt_gene_id,gn_entry_id FROM gn_history where gene_id=" . $GENE_ID);
            if ($res == array()) return false;
            
            if ($res[0]['gn_entry_id'] != '') {

                $NEW_GENE_ID = $res[0]['alt_gene_id'];
                break;
            } else if ($res[0]['alt_gene_id'] != '-') $GENE_ID = $res[0]['ALT_GENE_ID'];
            else break;
        } while ($n < 5);

        if ($NEW_GENE_ID == '') return false;
        $query = "SELECT SYMBOL,FULL_NAME,GENE_ID,GN_ENTRY_ID,STRING_AGG(SYN_VALUE,'|' ORDER BY SYN_VALUE ASC) as SYN_VALUE,SCIENTIFIC_NAME,TAX_ID
	 FROM MV_GENE WHERE GENE_ID=" . $NEW_GENE_ID . " GROUP BY SYMBOL,FULL_NAME,GENE_ID,GN_ENTRY_ID,SCIENTIFIC_NAME,TAX_ID";

        $res = runQuery($query);


       
    }

	if (count($res) > 0) {
		foreach ($res as &$l)
		$l['syn_value'] = explode("|", $l['syn_value']);
	}
	 return $res;
}




// $[API]
// Title: Gene Search By Symbol
// Function: get_gene_by_gene_symbol
// Description: Search for a gene by using its gene symbol
// Parameter: SYMBOL | Gene symbol | string | CDK2 | required
// Parameter: TAX_ID | Taxonomic identifier | array | 9606,10090 | optional
// Ecosystem: Genomics:gene
// Example: php biorels_api.php get_gene_by_gene_symbol -SYMBOL CDK2
// $[/API]
function get_gene_by_gene_symbol($SYMBOL,$TAX_ID=array())
{
    global $GLB_CONFIG;

	$STR_TAXON='';
	$TAXONS=array();
	var_dump($TAX_ID);
	if ($TAX_ID!=array()) 
	{
		foreach ($TAX_ID as $TAX) if (!is_numeric($TAX)) throw new Exception("Taxonomic identifier must be numeric");
		$TAXONS[]="'".implode("','",$TAX_ID)."'";
		$STR_TAXON=' AND tax_id IN ('.implode(",",$TAXONS).')';
	}
    $query = "SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene
            WHERE LOWER(symbol)='".strtolower($SYMBOL)."' ".$STR_TAXON."
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
            ";
	
    $res = runQuery($query);
	foreach ($res as $line)
	{
		$line['syn_value']=explode("|",$line['syn_value']);
	
	}

	return $res; 
}




// $[API]
// Title: Gene Search By Name
// Function: get_gene_by_gene_name
// Description: Search for a gene by using its gene name
// Parameter: NAME | Gene name | string | Cyclin-dependent kinase |required
// Parameter: TAX_ID | Taxonomic identifier | array | 9606,10090 | optional
// Ecosystem: Genomics:gene
// Example: php biorels_api.php get_gene_by_gene_name -NAME cyclin-dependent kinase 2
// $[/API]
function get_gene_by_gene_name($NAME,$TAX_ID=array())
{
    global $GLB_CONFIG;
	global $DB_CONN;
	$STR_TAXON='';
	$TAXONS=array();
	
	if ($TAX_ID!=array()) 
	{
		foreach ($TAX_ID as &$TAX)
		{
			if (!is_numeric($TAX)) throw new Exception("Taxonomic identifier must be numeric");
			$TAXONS[]="'".$TAX."'";
		} 
		$STR_TAXON=' AND tax_id IN ('.implode(",",$TAXONS).')';
		
	}
    $query = "SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
            FROM mv_gene
            WHERE full_name = '".strtolower($NAME)."' ".$STR_TAXON."
            GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
            ";

    $res = runQuery($query);

	if ($res==array())
	{
		$query = "SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
		FROM mv_gene
		WHERE LOWER(full_name) LIKE '%".strtolower($NAME)."%' ".$STR_TAXON."
		GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
		";

		$res = runQuery($query);
		
	}


	if ($res==array())
	{
		$query = "SELECT SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID
		FROM mv_gene
		WHERE LOWER(syn_value) LIKE '%".strtolower($NAME)."%' ".$STR_TAXON."
		GROUP BY SYMBOL, FULL_NAME, GENE_ID, GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID
		";

		$res = runQuery($query);
		
	}

	if ($res==array())
	{
    	$pos = strpos($NAME, '.');
		if ($pos !== false) $NAME = substr($NAME, 0, $pos);
		$NAME = $DB_CONN->quote($NAME);
		$query = "SELECT SYMBOL, FULL_NAME,GENE_ID, m.GN_ENTRY_ID, STRING_AGG(SYN_VALUE, '|' ORDER BY SYN_VALUE ASC) as SYN_VALUE, SCIENTIFIC_NAME, TAX_ID, GENE_SEQ_NAME
			FROM gene_seq GS, mv_gene m WHERE GENE_SEQ_NAME LIKE $NAME AND SYMBOL IS NOT NULL
			".$STR_TAXON."
			GROUP BY SYMBOL, FULL_NAME, GENE_ID, m.GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID, GENE_SEQ_NAME";
		$res = runQuery($query);
    

	}


	foreach ($res as $line)
	{
		$line['syn_value']=explode("|",$line['syn_value']);

	}	

	return $res; 
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////// GENOME ASSEMBLY  ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

// $[API]
// Title: Get all genome assembly
// Function: get_all_genome_assembly
// Description: Get all genome assembly
// Return: Genome assembly ID, taxon ID, scientific name, assembly name, assembly level, assembly type, assembly unit, assembly version, assembly date, assembly role, assembly role order, assembly role level, assembly role type, assembly role group, assembly role group order, assembly role group level, assembly role group type, assembly role group order
// Ecosystem: Genomics:genome assembly
// Example: php biorels_api.php get_all_genome_assembly
// $[/API]
function get_all_genome_assembly()
{
	$query="SELECT * FROM genome_assembly";
	$res= runQuery($query);
	foreach ($res as &$line)$line['annotation']=json_decode($line['annotation']);
	return $res;
}


// $[API]
// Title: Get genome assembly by taxon
// Function: get_genome_assembly_by_taxon
// Description: Get genome assembly by using its NCBI taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Return: Scientific name, chromosome number, taxon ID, chromosome sequence ID, chromosome sequence name, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, sequence length
// Ecosystem: Genomics:genome assembly
// Example: php biorels_api.php get_genome_assembly_by_taxon -TAX_ID 9606
// $[/API]
function get_genome_assembly_by_taxon(int $TAX_ID)
{

    
	$query="SELECT * 
	FROM genome_assembly g, taxon t 
	where t.taxon_id = g.taxon_id 
	AND tax_id = '".$TAX_ID."'";
	$res= runQuery($query);
	foreach ($res as &$line)$line['annotation']=json_decode($line['annotation']);
	return $res;

    $res=runQuery($query);
    return $res;

}


// $[API]
// Title: Get chromosome assembly by taxon
// Function: get_chromosome_assembly_by_taxon
// Description: Get chromosome sequence information reported for a given assembly by using its NCBI taxon ID
// Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
// Parameter: CHR_NUM | Chromosome number | string | 1 | optional
// Parameter: SEQ_ROLE | Sequence role | string | Scaffold | optional
// Parameter: CHR_SEQ_NAME | Chromosome sequence name: Genbank or refseq | string | HSCHR1_CTG9_UNLOCALIZED| optional
// Return: Scientific name, chromosome number, taxon ID, chromosome sequence ID, chromosome sequence name, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, sequence length
// Ecosystem: Genomics:genome assembly
// Example: php biorels_api.php get_chromosome_assembly_by_taxon -TAX_ID 9606
// Example: php biorels_api.php get_chromosome_assembly_by_taxon -TAX_ID 9606 -CHR_SEQ_NAME HSCHR1_CTG9_UNLOCALIZED
// $[/API]
function get_chromosome_assembly_by_taxon(int $TAX_ID, string $CHR_NUM='', string $SEQ_ROLE='', string $CHR_SEQ_NAME='')
{
	$query="SELECT scientific_name,chr_num,tax_Id, cs.chr_seq_Id,chr_seq_name, refseq_name,refseq_version,genbank_name,genbank_version,seq_Role,seq_len
    FROM taxon t, chromosome c, genome_assembly g, chr_seq cs
    where t.taxon_id = g.taxon_id 
    AND cs.chr_id = c.chr_id
    AND g.genome_assembly_id = cs.genome_assembly_id  ".
	(($CHR_NUM!='')?'AND chr_num=\''.$CHR_NUM.'\'':'').
	(($SEQ_ROLE!='')?'AND seq_role=\''.$SEQ_ROLE.'\'':'');

	$pos=strpos($CHR_SEQ_NAME,'.');
	
	if ($pos!==false)
	{
		
		$CHR_SEQ_NAME=substr($CHR_SEQ_NAME,0,$pos);
	}
	$query.=(($CHR_SEQ_NAME!='')?' AND (chr_seq_name=\''.$CHR_SEQ_NAME.'\' OR refseq_name=\''.$CHR_SEQ_NAME.'\' OR genbank_name=\''.$CHR_SEQ_NAME.'\') ':'').
		" AND tax_Id = '".$TAX_ID."' ORDER BY seq_role, chr_num ";

	$res=runQuery($query);
	
	return $res;
    
}



///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////// GENOMIC SEQUENCE  ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Search a chromosome position record by name and position. 
// Function: get_chromosome_position_info
// Description: Search by chromosome by providing either the chromosome name or the RefSeq or GenBank name, and the position on the chromosome.
// Parameter: CHR | chromsome name (1,2,3, X, Y, MT) or RefSeq Name or GenBank Name | string | 1 | required
// Parameter: CHR_POS | Chromosome position | int | 1 | required
// Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
// Return: Chromosome position, chromosome, chromosome sequence, RefSeq name, RefSeq version, GenBank name, GenBank version, sequence role, chromosome sequence name, assembly unit, nucleotide, position, scientific name, taxonomic identifier
// Ecosystem: Genomics:chromosome|chromosome position
// Example: php biorels_api.php get_chromosome_position_info -CHR 1 -CHR_POS 12
// Example: php biorels_api.php get_chromosome_position_info -CHR MT -CHR_POS 1000
// $[/API]
function get_chromosome_position_info($CHR,$CHR_POS, $TAX_ID='9606')
{
	$res=runQuery("SELECT CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TAX_ID = '".$TAX_ID."'
	AND (CHR_SEQ_NAME = '".$CHR."' OR REFSEQ_NAME = '".$CHR."' OR  GENBANK_NAME= '".$CHR."' )
	AND CHR_POS = ".$CHR_POS);
	return $res[0];
}






// $[API]
// Title:  Search a chromosome position record by transcript position records
// Function: get_chromosome_positions_info
// Description: Given a list of transcript_pos_id, search for the corresponding chromosome position records.
// Parameter: LIST | List of transcript_pos_id | array | 1,2,3,4,5 | required
// Ecosystem: Genomics:chromosome position|transcript|transcript position
// Example: php biorels_api.php get_chromosome_positions_info -LIST 1,2,3,4,5
// $[/API]
function get_chromosome_positions_info($LIST)
{
	$res=runQuery("SELECT TP.TRANSCRIPT_POS_ID,CSP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,CSP.NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T,TRANSCRIPT_POS TP
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID AND TRANSCRIPT_POS_ID IN (".implode(',',$LIST).')');
    $data=array();
    foreach ($res as $line)$data[$line['transcript_pos_id']]=$line;
	return $data;
}


# // $[API]
# // Title: Get chromosome sequence into fasta format
# // Function: get_chromosome_seq_to_fasta
# // Description: Get the chromosome sequence in fasta format by providing the chromosome name or the RefSeq or GenBank name, and the start and end position on the chromosome.
# // Parameter: CHR_NAME | Chromosome name (1,2,3, X, Y, MT) or RefSeq Name or GenBank Name | string | 1 |required
# // Parameter: CHR_POS_START | Starting position in the chromosome | int | 10 | required
# // Parameter: CHR_POS_END | Ending position in the chromosome | int | 1231 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Chromosome sequence in fasta format
# // Ecosystem: Genomics:chromosome|chromosome sequence
# // Example:  php biorels_api.php get_chromosome_seq_to_fasta -CHR_NAME 1 -CHR_POS_START 1 -CHR_POS_END 1000
# // Example:  php biorels_api.php get_chromosome_seq_to_fasta -CHR_NAME MT -CHR_POS_START 1 -CHR_POS_END 1000
# // $[/API]
function get_chromosome_seq_to_fasta($CHR_NAME, $CHR_POS_START, $CHR_POS_END, $TAX_ID='9606')
{
	$res=get_chromosome_assembly_by_taxon($TAX_ID, $CHR_NAME);
	if ($res == array())
	{
		$res=get_chromosome_assembly_by_taxon($TAX_ID, '','',$CHR_NAME);
		
		
		if ($res==array())return array();
	} 
	

	$SEQS=array();
	foreach ($res as $chr)
	{
		$SEQ= ">".$chr['chr_seq_name']."|".$chr['refseq_name'].".".$chr['refseq_version']."|".$chr['genbank_name'].".".$chr['genbank_version']."|".$chr['seq_role']."|".$chr['scientific_name']."|".$chr['tax_id']."\n";
	
	
		$CHR_SEQ_ID = $chr['chr_seq_id'];
		
		$STEP=50000;
		#print(f">{CHR_NAME} {CHR_POS_START}-{CHR_POS_END}")
		$CHR_POS=(int)$CHR_POS_START;
		$END_POS=(int)$CHR_POS_END;
		$str='';
		while ($CHR_POS < $END_POS)
		{
			$query = "SELECT NUCL
				FROM CHR_SEQ_POS
				WHERE CHR_SEQ_ID = ".$CHR_SEQ_ID."
				AND CHR_POS >= ".$CHR_POS."
				AND CHR_POS < ".($CHR_POS+$STEP);
			$res2 = runQuery($query);
			$str='';
			foreach ($res2 as $line)
			{
				$str.=$line['nucl'];
				$CHR_POS+=1;
				if ($CHR_POS >= $END_POS)
					break;
				if (strlen($str) == 100)
				{
					$SEQ.= $str."\n";
					$str='';
				}
			}
		}
		$SEQS[]=$SEQ.$str."\n";
	}
	return $SEQS;
}





# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ORTHOLOGS /////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get orthologs
# // Function: get_orthologs
# // Description: Get orthologs by providing the gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: ONLY_MAIN | Only main species | boolean | false | optional | Default: false
# // Return: Orthologs gene ID, gene symbol, gene name, species, taxonomic identifier
# // Ecosystem: Genomics:orthologs
# // Example: php biorels_api.php get_orthologs -GENE_ID 1017
# // Example: php biorels_api.php get_orthologs -GENE_ID 1017 -ONLY_MAIN true
# // $[/API]
function get_orthologs($GENE_ID, $ONLY_MAIN = false)
{

    $query = "SELECT DISTINCT COMP_GN_ENTRY_ID, COMP_SYMBOL,COMP_GENE_ID, COMP_GENE_NAME, COMP_SPECIES,COMP_TAX_ID
	 FROM (SELECT  MGS2.GN_ENTRY_ID AS COMP_GN_ENTRY_ID, MGS2.SYMBOL AS COMP_SYMBOL,
	  MGS2.GENE_ID as COMP_GENE_ID, MGS2.FULL_NAME as COMP_GENE_NAME, 
	  MGS2.SCIENTIFIC_NAME as COMP_SPECIES, MGS2.TAX_ID as COMP_TAX_ID 
	  FROM MV_GENE_SP MGS1, GN_REL GR, MV_GENE_SP MGS2 
	  WHERE MGS1.GN_ENTRY_Id = GR.GN_ENTRY_R_ID AND MGS2.GN_ENTRY_ID = GR.GN_ENTRY_C_ID 
	  AND MGS1.GENE_ID='" . $GENE_ID . "'";
    if ($ONLY_MAIN) $query .= " AND MGS2.TAX_ID IN ('9606','10116','10090','9913','9615','9541') ";
    $query .= " order by (case
             when MGS2.tax_id='9606' then 1
             when MGS2.tax_id='10116' then 2
             when MGS2.tax_id='10090' then 3
             when MGS2.tax_id='9913' then 4
             when MGS2.tax_id='9615' then 5
		     when MGS2.tax_id='9541' then 6 end
) asc, MGS2.TAX_ID  ASC) t";

    return runQuery($query);
}



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// TRANSCRIPT /////////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get a specific range of a transcript sequence
# // Function: getTranscriptSequenceRange
# // Description: Get a specific range of a transcript sequence by providing the transcript name, the start and end position on the transcript, and the type of range (DNA or RNA). For DNA it corresponds to position on the chromosome
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 |required
# // Parameter: START_POS | Starting position on the transcript | int | 1 | optional | Default: -1
# // Parameter: END_POS | Ending position on the transcript | int | 100 | optional | Default: -1
# // Parameter: TYPE | Range type: (DNA or RNA) | string | RNA | optional | Default: RNA
# // Return: Transcript sequence, translation, and alignment information
# // Ecosystem: Genomics:transcript
# // Example: php biorels_api.php getTranscriptSequenceRange -TRANSCRIPT_NAME NM_000546 -START_POS 1 -END_POS 100 -TYPE RNA
# // Example: php biorels_api.php getTranscriptSequenceRange -TRANSCRIPT_NAME NM_000546 -START_POS 7674205 -END_POS 7674225 -TYPE DNA
# // $[/API]
function getTranscriptSequenceRange($TRANSCRIPT_NAME, $START_POS=-1, $END_POS=-1, $TYPE='RNA')
{
    $pos = strpos($TRANSCRIPT_NAME, '.');
    if ($pos !== false) {
        $TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
        $TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
        if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric", ERR_TGT_USR);
    }
    
    global $DB_CONN;
    $TRANSCRIPT_NAME = $DB_CONN->quote($TRANSCRIPT_NAME);

    $query = "select T.TRANSCRIPT_ID,TRANSCRIPT_POS_ID,TP.NUCL,SEQ_POS,SEQ_POS_TYPE_ID,EXON_ID,CHR_POS,C.CHR_SEQ_POS_ID, C.nucl as CHR_NUCL
			FROM  TRANSCRIPT_POS TP LEFT JOIN CHR_SEQ_POS C ON C.CHR_SEQ_POS_id = TP.CHR_SEQ_POS_ID,
			 TRANSCRIPT T
			WHERE T.TRANSCRIPT_ID=TP.TRANSCRIPT_ID  
			  AND TRANSCRIPT_NAME=" . $TRANSCRIPT_NAME;
    if ($TYPE == "RNA")
	{
		if ($START_POS!=-1)$query .= ' AND SEQ_POS >=' . $START_POS ;
		if ($END_POS!=-1)  $query .= ' AND SEQ_POS <=' . $END_POS;
	} 
    else if ($TYPE == "DNA")
	{
		if ($START_POS!=-1)$query .= ' AND CHR_POS >=' . $START_POS ;
		if ($END_POS!=-1)$query .=   ' AND CHR_POS <=' . $END_POS;
	} 
    else throw new Exception("Range type must be either DNA or RNA", ERR_TGT_SYS);

    if ($pos !== false) $query .= ' AND TRANSCRIPT_VERSION=\'' . $TRANSCRIPT_VERSION . "'";

    $TMP = runQuery($query . ' ORDER BY SEQ_POS ASC');
    $TRANSCRIPT_ID = null;
    $TRANSCRIPT_POS_ID = array();
    if ($TMP == array()) return array();
	
    $ptypes_raw = runQuery("SELECT * FROM TRANSCRIPT_POS_TYPE");
    $PTYPE = array();
    foreach ($ptypes_raw as $PT)    $PTYPE[$PT['transcript_pos_type_id']] = $PT['transcript_pos_type'];
    $CURR_POS_TYPE = 0;

	$DATA_TRANSCRIPT = array();
    foreach ($TMP as $K => $POS_INFO) {
        $TRANSCRIPT_POS_ID[] = $POS_INFO['transcript_pos_id'];
        $POS_INFO['TYPE'] = $PTYPE[$POS_INFO['seq_pos_type_id']];
        unset($POS_INFO['seq_pos_type_id']);

        $DATA_TRANSCRIPT[$POS_INFO['transcript_id']]['SEQUENCE'][$POS_INFO['chr_pos']] = $POS_INFO;
    }

    if ($DATA_TRANSCRIPT == array()) return array();

	foreach ($DATA_TRANSCRIPT as $TRANSCRIPT_ID=>&$TR_DATA)
	{

		$TR_DATA['TRANSCRIPT']=runQuery("SELECT * FROM transcript WHERE TRANSCRIPT_ID=".$TRANSCRIPT_ID);
		$TR_DATA['TRANSLATION'] = runQuery("SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE
		T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
		AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND T.TRANSCRIPT_ID =" . $TRANSCRIPT_ID);

		$TR_DATA['ALIGN'] = array();
		$LIST_UNSEQ = array();
		$LIST_POS = array();
		foreach ($TR_DATA['TRANSLATION'] as &$entry) {
			$TR_DATA['ALIGN'][$entry['tr_protseq_al_id']] = array();
			$LIST_UNSEQ[$entry['prot_seq_id']] = array();
		}
		if (count($LIST_UNSEQ) == 0) continue;

		$tmp = runQuery('SELECT * FROM PROT_SEQ_POS WHERE PROT_SEQ_ID IN (' . implode(',', array_keys($LIST_UNSEQ)) . ') ORDER BY PROT_SEQ_ID, POSITION ASC');
		foreach ($tmp as $line) $LIST_POS[$line['prot_seq_pos_id']] = array($line['letter'], $line['position']);

		$tmp = runQuery('SELECT * FROM tr_protseq_pos_al WHERE TR_PROTSEQ_AL_ID IN (' . implode(',', array_keys($TR_DATA['ALIGN'])) . ') AND TRANSCRIPT_POS_ID IN (' . implode(',', $TRANSCRIPT_POS_ID) . ')');
		foreach ($tmp as $line) {
			$UP = &$LIST_POS[$line['prot_seq_pos_id']];
			$TR_DATA['ALIGN'][$line['tr_protseq_al_id']][$line['transcript_pos_id']] = array($UP[0], $UP[1], $line['triplet_pos']);
		}
	}
    return $DATA_TRANSCRIPT;
}



# // $[API]
# // Title: Search a transcript by name
# // Function: search_transcript
# // Description: Search for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 |required
# // Return: Transcript ID, transcript name, transcript version, start position, end position, sequence hash, gene sequence ID, chromosome sequence ID, support level, partial sequence, valid alignment, gene sequence name, gene sequence version, strand, gene entry ID, feature, biotype
# // Ecosystem: Genomics:transcript
# // Example: php biorels_api.php search_transcript -TRANSCRIPT_NAME NM_000546
# // $[/API]
function search_transcript($TRANSCRIPT_NAME)
{

	$pos = strpos($TRANSCRIPT_NAME, '.');
    if ($pos !== false) {
        $TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
        $TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
        if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric", ERR_TGT_USR);
    }
	$query = "SELECT 
	TRANSCRIPT_ID, TRANSCRIPT_NAME, TRANSCRIPT_VERSION, T.START_POS, T.END_POS,
	SB.SEQ_TYPE as BIOTYPE_NAME,SB.SO_ID as BIOTYPE_SO_ID, SB.SO_NAME as BIOTYPE_SO_NAME,
	SB.SO_DESCRIPTION as BIOTYPE_SO_DESC, SF.SEQ_TYPE as FEATURE_NAME,SF.SO_ID as FEATURE_SO_ID,
	SF.SO_NAME as FEATURE_SO_NAME, SF.SO_DESCRIPTION as FEATURE_SO_DESC, SUPPORT_LEVEL,
	GENE_SEQ_NAME,GENE_SEQ_VERSION, STRAND,GS.START_POS as GENE_START, GS.END_POS as GENE_END,
	GENE_ID,SYMBOL,FULL_NAME, GS.CHR_SEQ_ID
FROM GENE_SEQ GS 
LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID, TRANSCRIPT T 
LEFT JOIN (
	SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID 
	FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID
) SB ON SB.SEQ_BTYPE_ID=BIOTYPE_ID
LEFT JOIN (
	SELECT SO_ID,SO_NAME,SO_DESCRIPTION, SEQ_TYPE,SEQ_BTYPE_ID 
	FROM SEQ_BTYPE SB LEFT JOIN SO_ENTRY S ON SB.SO_ENTRY_ID =  S.SO_ENTRY_ID
) SF ON SF.SEQ_BTYPE_ID=FEATURE_ID
WHERE T.GENE_SEQ_ID = GS.GENE_SEQ_ID 
	AND  transcript_name = '".$TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND transcript_version='".$TRANSCRIPT_VERSION."'";
	return runQuery($query);

}




# // $[API]
# // Title: Get all exons for a transcript
# // Function: get_exon_location
# // Description: Get all exons for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: Exon ID, minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: php biorels_api.php get_exon_location -TRANSCRIPT_NAME NM_000546
# // $[/API]
function get_exon_location($TRANSCRIPT_NAME)
{
	$pos = strpos($TRANSCRIPT_NAME, '.');
	if ($pos !== false) {
		$TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
		$TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric", ERR_TGT_USR);
	}
	$query = "SELECT EXON_ID,MIN(SEQ_POS) as MIN_POS,MAX(SEQ_POS) as MAX_POS
	FROM TRANSCRIPT_POS TP, TRANSCRIPT T
	WHERE  T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
	AND TRANSCRIPT_NAME = '".$TRANSCRIPT_NAME."'";
	if ($pos !== false) $query .= " AND TRANSCRIPT_VERSION='" . $TRANSCRIPT_VERSION . "'";
	$query .= "GROUP BY EXON_ID ORDER BY EXON_ID ASC";
	return runQuery($query);
}


# // $[API]
# // Title: Get all exons and their location on the chromosome for a transcript
# // Function: get_exon_dna_location
# // Description: Get all exons for a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: Exon ID, minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: php biorels_api.php get_exon_dna_location -TRANSCRIPT_NAME NM_000546
# // $[/API]
function get_exon_dna_location($TRANSCRIPT_NAME)
{
	$pos=strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query="SELECT EXON_ID,MIN(CHR_POS) as MIN_POS,MAX(CHR_POS) as MAX_POS,CHR_SEQ_NAME
	FROM TRANSCRIPT_POS TP 
	LEFT JOIN CHR_SEQ_POS CSP ON CSP.CHR_SEQ_POS_ID = TP.CHR_SEQ_POS_ID
	LEFT JOIN CHR_SEQ CS ON CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID, TRANSCRIPT T
	WHERE  T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
	AND TRANSCRIPT_NAME='".$TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";
	$query.="GROUP BY EXON_ID,CHR_SEQ_NAME ORDER BY CHR_SEQ_NAME,EXON_ID ASC";
	return runQuery($query);
}



# // $[API]
# // Title: Get all regions (UTR, CDS) of a transcript
# // Function: get_region_transcript
# // Description: Get all regions (UTR, CDS) of a transcript by using its name
# // Parameter: TRANSCRIPT_NAME | Transcript name with or without version | string | NM_000546 | required
# // Return: region minimum position, maximum position
# // Ecosystem: Genomics:transcript
# // Example: php biorels_api.php get_region_transcript -TRANSCRIPT_NAME NM_000546
# // $[/API]
function get_region_transcript($TRANSCRIPT_NAME)
{
	$pos = strpos($TRANSCRIPT_NAME, '.');
	if ($pos !==false) {
		$TRANSCRIPT_VERSION = substr($TRANSCRIPT_NAME, $pos + 1);
		$TRANSCRIPT_NAME = substr($TRANSCRIPT_NAME, 0, $pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric");
	}
	$query = "SELECT transcript_pos_type, MIN(SEQ_POS) as MIN_POS, MAX(SEQ_POS) as MAX_POS
	FROM transcript_pos tp, transcript_pos_type tpt, transcript t
	WHERE tp.transcript_id = t.transcript_id
	AND tp.seq_pos_type_id = tpt.transcript_pos_type_id
	AND transcript_name = '" . $TRANSCRIPT_NAME . "'";
	if ($pos != false) $query .= " AND transcript_version='" . $TRANSCRIPT_VERSION . "'";
	$query .= " GROUP BY transcript_pos_type ORDER BY MIN_POS ASC";
	return runQuery($query);
}



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////// TRANSCRIPT - GENE & LOCUS ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all transcripts associated to a chromosome or a specific region of it
# // Function: get_transcript_by_chromosome
# // Description: Search for a transcript by using its chromosome name and optionally a specific region of it
# // Parameter: CHR_NAME | Chromosome name (1,2,3, X, Y, MT) | string | 1 | required
# // Parameter: CHR_POS_START | Starting position in the chromosome | int | 10 | optional | Default: -1
# // Parameter: CHR_POS_END | Ending position in the chromosome | int | 1000 | optional |  Default: -1
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Assembly name, assembly unit, chromosome sequence name, gene sequence name, gene sequence version, transcript name, transcript version, transcript ID
# // Ecosystem: Genomics:transcript|chromosome|taxon
# // Example: php biorels_api.php get_transcript_by_chromosome -CHR_NAME 1 -CHR_POS_START 1 -CHR_POS_END 1000
# // Example: php biorels_api.php get_transcript_by_chromosome -CHR_NAME MT
# // $[/API]
function get_transcript_by_chromosome($CHR_NAME, $CHR_POS_START=-1, $CHR_POS_END=-1, $TAX_ID='9606')
{
	$query = "SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
	FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G,TAXON TX
	WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
	AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
	AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
	AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
	AND TX.TAXON_ID = G.TAXON_ID
	AND CS.CHR_SEQ_NAME='".$CHR_NAME."'";
	if ($CHR_POS_START!=-1)$query.=" AND T.START_POS >= ".$CHR_POS_START;
	if ($CHR_POS_END!=-1)$query.=" AND T.END_POS <= ".$CHR_POS_END;
	$query.=" AND TAX_ID = '".$TAX_ID."'";
	return runQuery($query);
}



# // $[API]
# // Title: Get all transcripts associated to a specific locus
# // Function: get_transcript_by_locus
# // Description: Search for a transcript by using its locus
# // Parameter: CHR_MAP_LOCATION | Locus name | string | 1p36.33 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Assembly name, assembly unit, chromosome sequence name, gene sequence name, gene sequence version, transcript name, transcript version, transcript ID
# // Ecosystem: Genomics:transcript|locus|taxon
# // Example: php biorels_api.php get_transcript_by_locus -CHR_MAP_LOCATION 1p36.33
# // $[/API]
function get_transcript_by_locus($CHR_MAP_LOCATION, $TAX_ID='9606')
{
	$query = "SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
	FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_GN_MAP CGM, CHR_MAP CM, CHR_SEQ CS, CHROMOSOME C, GENOME_ASSEMBLY G,TAXON TX
	WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
	AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
	AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID
	AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
	AND GE.GN_ENTRY_ID = CGM.GN_ENTRY_ID
	AND CGM.CHR_MAP_ID = CM.CHR_MAP_ID
	AND C.CHR_ID = CM.CHR_ID
	AND C.TAXON_ID = TX.TAXON_ID
	AND CM.MAP_LOCATION='".$CHR_MAP_LOCATION."'
	AND TAX_ID = '".$TAX_ID."'";
	return runQuery($query);
}



# // $[API]
# // Title: Get all transcripts associated to a NCBI Gene ID
# // Function: get_transcript_by_gene_id
# // Description: Search for a transcript by using its gene ID
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype
# // Ecosystem: Genomics:transcript|gene
# // Example: php biorels_api.php get_transcript_by_gene_id -GENE_ID 1017
# // $[/API]
function get_transcript_by_gene_id($GENE_ID)
{
	$query = "SELECT transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype
        FROM transcript t
        LEFT JOIN seq_btype f ON f.seq_btype_id = feature_id
        LEFT JOIN seq_btype b on b.seq_btype_id = biotype_id, gene_seq gs, gn_entry ge 
        WHERE t.gene_seq_id = gs.gene_seq_id
        AND gs.gn_entry_Id = ge.gn_entry_Id
        AND  gene_id = ".$GENE_ID;
	return runQuery($query);
}



# // $[API]
# // Title: Get all transcripts and their sequence associated to a NCBI Gene ID
# // Function: get_transcripts_sequence_by_gene
# // Description: Given a NCBI Gene ID, provides information about all the transcripts associated with the gene, including the sequence of the transcripts.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Transcript ID, transcript name, transcript version, start position, end position, sequence hash, gene sequence ID, chromosome sequence ID, support level, partial sequence, valid alignment, gene sequence name, gene sequence version, strand, gene entry ID, feature, biotype
# // Ecosystem: Genomics:transcript|gene
# // Example: php biorels_api.php get_transcripts_sequence_by_gene -GENE_ID 1017
# // $[/API]
function get_transcripts_sequence_by_gene($GENE_ID)
{
    $query = "SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
			FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G
			WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
			AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
			AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID 
	  		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
			AND GENE_ID=" . $GENE_ID;

    $TMP = runQuery($query);
    $DATA = array();
    foreach ($TMP as $l) $DATA[$l['transcript_id']] = array('INFO' => $l, 'SEQ' => array());
    $query = 'SELECT TRANSCRIPT_POS_ID,NUCL,SEQ_POS, TRANSCRIPT_ID 
	FROM TRANSCRIPT_POS WHERE TRANSCRIPT_ID IN (' . implodE(',', array_keys($DATA)) . ') ORDER BY TRANSCRIPT_ID ASC,SEQ_POS ASC';
    $TMP = runQuery($query);
    foreach ($TMP as $l) $DATA[$l['transcript_id']]['SEQ'][] = $l;
    return $DATA;
}



# // $[API]
# // Title: Get all transcript sequences in Fasta associated to a NCBI Gene ID
# // Function: get_transcripts_sequence_in_fasta_by_gene
# // Description: Given a NCBI Gene ID, provides information about all the transcripts associated with the gene, including the sequence of the transcripts.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Transcript sequences in fasta format
# // Ecosystem: Genomics:transcript|gene
# // Example: php biorels_api.php get_transcripts_sequence_in_fasta_by_gene -GENE_ID 1017
# // $[/API]
function get_transcripts_sequence_in_fasta_by_gene($GENE_ID)
{
    $query = "SELECT ASSEMBLY_NAME, CS.ASSEMBLY_UNIT,CS.CHR_SEQ_NAME,GENE_SEQ_NAME,GENE_SEQ_VERSION, TRANSCRIPT_NAME,TRANSCRIPT_VERSION,TRANSCRIPT_ID
			FROM  TRANSCRIPT T, GENE_SEQ GS, GN_ENTRY GE, CHR_SEQ CS, GENOME_ASSEMBLY G
			WHERE  T.GENE_SEQ_ID = GS.GENE_SEQ_ID
			AND GS.CHR_SEQ_ID = CS.CHR_SEQ_ID
			AND CS.GENOME_ASSEMBLY_ID = G.GENOME_ASSEMBLY_ID 
	  		AND GS.GN_ENTRY_ID=GE.GN_ENTRY_ID
			AND GENE_ID=" . $GENE_ID;

    $TMP = runQuery($query);
    $DATA = array();
    foreach ($TMP as $l) $DATA[$l['transcript_id']] = array('INFO' => $l, 'SEQ' => '');
    $query = 'SELECT TRANSCRIPT_POS_ID,NUCL,SEQ_POS, TRANSCRIPT_ID 
	FROM TRANSCRIPT_POS WHERE TRANSCRIPT_ID IN (' . implodE(',', array_keys($DATA)) . ') ORDER BY TRANSCRIPT_ID ASC,SEQ_POS ASC';
    $TMP = runQuery($query);
    foreach ($TMP as $l) $DATA[$l['transcript_id']]['SEQ'].=  $l['nucl'];
    
	foreach ($DATA as &$l)
	{
		$l['FASTA']= ">".$l['INFO']['transcript_name'];
		if ($l['INFO']['transcript_version']!='')$l['FASTA'].= ".".$l['INFO']['transcript_version'];
		$l['FASTA'].= "|".$l['INFO']['assembly_name']."|".$l['INFO']['assembly_unit']."|".$l['INFO']['chr_seq_name']."|".$l['INFO']['gene_seq_name']."|".$l['INFO']['gene_seq_version']."\n";
		$l['FASTA'].= implode("\n",str_split($l['SEQ'],100))."\n";
	}
	return $DATA;
}





# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ///////////////////////////////////// TRANSCRIPT - SPECIES ////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all transcripts associated to a NCBI Tax id
# // Function: search_transcript_by_taxon
# // Description: Search for a transcript by using its taxonomic identifier
# // Parameter: TAX_ID | NCBI Taxon ID | int | 9606 | required
# // Return: transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash, gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment, gene_seq_name,gene_seq_version,strand, ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype, gene_id, symbol, chr_name, chr_map
# // Ecosystem: Genomics:transcript|taxon
# // Example: php biorels_api.php search_transcript_by_taxon -TAX_ID 9606
# // Warning: Long query execution time
# // $[/API]
function search_transcript_by_taxon($TAX_ID)
{
	$query = "SELECT transcript_id, transcript_name, transcript_version, t.start_pos,t.end_pos, seq_hash,
            gs.gene_seq_id, gs.chr_seq_id,support_level, partial_sequence, valid_alignment,
             gene_seq_name,gene_seq_version,strand,
                  ge.gn_entry_id, f.seq_type as feature, b.seq_Type as biotype,
                  gene_id, symbol, chr_num, map_location
          
        FROM transcript t
          LEFT JOIN seq_btype f ON f.seq_btype_id = feature_id
        LEFT JOIN seq_btype b on b.seq_btype_id = biotype_id, gene_seq gs, gn_entry ge, chr_gn_map cgm, chr_map cm, chromosome c, taxon tx 
		WHERE t.gene_seq_id = gs.gene_seq_id
        AND ge.gn_entry_id = cgm.gn_entry_id
        AND cgm.chr_map_id = cm.chr_map_id
        AND cm.chr_id = c.chr_id
        AND c.taxon_id = tx.taxon_id
		AND gs.gn_entry_Id = ge.gn_entry_Id
		AND  tax_id = '".$TAX_ID."'";
	return runQuery($query);
}





///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////// TRANSCRIPT - GENOMIC ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



// $[API]
// Title: Find transcript by chromosome position 
// Function: getTranscriptFromChr
// Description: First search for any gene sequence in which the provided chromosome position fall within its range. Then search for any transcript that is associated with the gene sequence.
// Parameter: CHR_SEQ_ID | Chromosome sequence id | int | 1 | required
// Parameter: CHR_SEQ_POS_ID | chromsome sequence position id | int | 1 | required
// Parameter: CHR_POSITION | chromosome position | int | 1000 | required
// Return: Transcript ID, gene sequence ID, gene sequence name, gene name, gene ID, transcript name, transcript version, strand, sequence position, exon ID, chromosome sequence position ID, transcript position type
// Ecosystem: Genomics:chromosome position|transcript
// Example: php biorels_api.php getTranscriptFromChr -CHR_SEQ_ID 1 -CHR_SEQ_POS_ID 1 -CHR_POSITION 1000
// $[/API]
function getTranscriptFromChr($CHR_SEQ_ID,$CHR_SEQ_POS_ID,$CHR_POSITION)
{
	$res=runQuery("SELECT transcript_id 
		FROM transcript t, gene_seq gs
		where gs.gene_seq_Id = t.gene_seq_id 
		AND gs.chr_seq_id = ".$CHR_SEQ_ID." 
		AND gs.start_pos <= ".$CHR_POSITION." 
		AND gs.end_pos >= ".$CHR_POSITION);
	$DATA=array();
	foreach ($res as $line)
	{
		$res2=runQuery("SELECT * 
		FROM gene_seq gs 
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id,
		transcript t,
		transcript_pos tp
		WHERE tp.transcript_id = t.transcript_id
		AND t.gene_seq_id = gs.gene_seq_Id
		AND chr_seq_pos_id = ".$CHR_SEQ_POS_ID. "
		AND tp.transcript_id = ".$line['transcript_id']);
		foreach ($res2 as $l)$DATA[]=$l;
	}
	return $DATA;
    
}




//  $[API]
//  Title:  Search a chromosome position record by transcript position records
//  Function: getChromosomePositionsInfoByTranscriptPosIds
//  Description: Given a list of transcript_pos_id, search for the corresponding chromosome position records.
//  Parameter: LIST | List of transcript_pos_id | array | 1,2,3,4,5 | required
//  Ecosystem: Genomics:chromosome position|transcript|transcript position
//  Example: php biorels_api.php getChromosomePositionsInfoByTranscriptPosIds -LIST 1,2,3,4,5
//  $[/API]
function getChromosomePositionsInfoByTranscriptPosIds($LIST)
{
	$query = "
		SELECT TP.TRANSCRIPT_POS_ID, CSP.CHR_SEQ_POS_ID, C.CHR_ID, CHR_NUM, CS.CHR_SEQ_ID, REFSEQ_NAME, REFSEQ_VERSION, GENBANK_NAME, GENBANK_VERSION, SEQ_ROLE, CHR_SEQ_NAME, ASSEMBLY_UNIT, CSP.NUCL, CHR_POS AS POSITION, SCIENTIFIC_NAME, TAX_ID
		FROM CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP, TAXON T, TRANSCRIPT_POS TP
		WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
		AND T.TAXON_ID = C.TAXON_ID
		AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID
		AND TRANSCRIPT_POS_ID IN (".implode(",",$LIST).")";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)$data[$line['transcript_pos_id']]=$line;
	return $data;
}


//  $[API]
//  Title: Search a chromosome position record by a list of transcript position 
//  Function: getChromosomeSeqInfoFromTranscriptPosition
//  Description: Given a list of transcript position and a transcript name, search for the corresponding chromosome position records.
//  Parameter: TRANSCRIPT_POSITION | List of transcript position ID | array | 1,2,3,4 | required
//  Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
//  Parameter: TRANSCRIPT_VERSION | Transcript version | string | | optional | Default: None
//  Ecosystem: Genomics:chromosome position|transcript
//  Example: php biorels_api.php getChromosomeSeqInfoFromTranscriptPosition -TRANSCRIPT_POSITION 1,2,3,4 -TRANSCRIPT_NAME NM_001798
//  $[/API]
function getChromosomeSeqInfoFromTranscriptPosition($TRANSCRIPT_POSITION, $TRANSCRIPT_NAME,$TRANSCRIPT_VERSION='')
{
	$query= "
     SELECT CSP.CHR_SEQ_POS_ID, C.CHR_ID,CHR_NUM, CS.CHR_SEQ_ID,
    REFSEQ_NAME,REFSEQ_VERSION,GENBANK_NAME,GENBANK_VERSION, SEQ_ROLE,CHR_SEQ_NAME,
     ASSEMBLY_UNIT,CSP.NUCL,CHR_POS As POSITION,SCIENTIFIC_NAME,TAX_ID
    FROM   CHROMOSOME C, CHR_SEQ CS, CHR_SEQ_POS CSP,TAXON T,TRANSCRIPT TT,TRANSCRIPT_POS TP
    WHERE C.CHR_ID = CS.CHR_ID AND CS.CHR_SEQ_ID = CSP.CHR_SEQ_ID
    AND T.TAXON_ID = C.TAXON_ID
    AND TT.transcript_id = TP.transcript_id
    AND TT.TRANSCRIPT_NAME='".$TRANSCRIPT_NAME."'";
	if ($TRANSCRIPT_VERSION!='')$query.=" AND TT.TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";
    $query.="
    AND TP.SEQ_POS IN (".implode(",",$TRANSCRIPT_POSITION).")
    AND TP.CHR_SEQ_POS_ID = CSP.CHR_SEQ_POS_ID;";   

	return runQuery($query);

}




///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// GENOMIC ECOSYSTEM - VARIANT  ////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Get variant information by its dbSNP identifier rsid
# // Function: search_variant_by_rsid
# // Description: Search for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | true | optional | Default: true
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | true | optional | Default: true
# // Parameter: WITH_GENE | Include gene information | boolean | true | optional | Default: true
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant
# // Example: php biorels_api.php search_variant_by_rsid -RSID rs12345
# // $[/API]
function search_variant_by_rsid($RSID, $WITH_ALLELES = true, $WITH_FREQUENCY = true,$WITH_GENE=true) 
{
	$VAL = $RSID;
    if (substr($RSID, 0, 2) == 'rs') $VAL = substr($RSID, 2);
    if (!is_numeric($VAL)) return array();

	$query = "SELECT * FROM VARIANT_ENTRY WHERE RSID = '".$VAL."'";
	$data['VARIANT']= runQuery($query);
	if ($WITH_ALLELES) $data['ALLELES']=get_variant_alleles_by_rsid($VAL);
	if ($WITH_FREQUENCY) $data['FREQUENCY']=get_alleles_frequency_by_rsid($VAL);
	if ($WITH_GENE) $data['GENE']=find_gene_from_variant($VAL);
	return $data;
}


# // $[API]
# // Title: Get variant information by its dbSNP identifier rsid
# // Function: get_variant_alleles_by_rsid
# // Description: List all variant alleles for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Variant alleles
# // Ecosystem: Genomics:variant
# // Example: php biorels_api.php get_variant_alleles_by_rsid -RSID rs12345
# // $[/API]
function get_variant_alleles_by_rsid($RSID)
{
	$VAL = $RSID;
    if (substr($RSID, 0, 2) == 'rs') $VAL = substr($RSID, 2);
    if (!is_numeric($VAL)) return array();

	$query = "SELECT tax_id, scientific_name, chr_seq_name, chr_pos, rsid, va_r.variant_seq as ref_all, va.variant_seq as alt_all, variant_name, so_name as SNP_TYPE
	FROM  variant_entry ve, variant_position vp, variant_change VC, variant_allele va_r, variant_allele va, VARIANT_TYPE VT
	LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID,
	chr_seq_pos csp, chr_seq cs, genome_assembly ga, taxon t
	where ve.variant_entry_id = vp.variant_entry_id
    ANd vp.variant_position_id = vc.variant_position_id 
    AND  vt.variant_Type_Id = vc.variant_type_id
    AND va.variant_allele_id = alt_all
    AND va_r.variant_allele_id = ref_all
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND csp.chr_seq_id = cs.chr_seq_id
	AND cs.genome_assembly_id = ga.genome_assembly_id
	AND ga.taxon_id = t.taxon_id
    AND rsid = ".$VAL;
    $res=runQuery($query); 
	return $res;
}


# // $[API]
# // Title: Get variant alleles frequency by its dbSNP identifier rsid
# // Function: get_alleles_frequency_by_rsid
# // Description: List all variant alleles frequency for a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Variant alleles frequency
# // Ecosystem: Genomics:variant
# // Example: php biorels_api.php get_alleles_frequency_by_rsid -RSID rs12345
# // $[/API]
function get_alleles_frequency_by_rsid($RSID)
{
	$VAL = $RSID;
	if (substr($RSID, 0, 2) == 'rs') $VAL = substr($RSID, 2);
	if (!is_numeric($VAL)) return array();

	$query = "SELECT rsid, va_r.variant_seq as ref_all, va.variant_seq as alt_all, variant_name, so_name as SNP_TYPE,
	ref_count,alt_count as tot_count,short_name,description, variant_Freq_study_name
	FROM  variant_entry ve, variant_position vp, variant_change VC, variant_allele va_r, variant_allele va, VARIANT_TYPE VT
	LEFT JOIN SO_ENTRY SO ON SO.SO_ENTRY_ID = VT.SO_ENTRY_ID,
	variant_Frequency vf, variant_freq_study vfs
	where ve.variant_entry_id = vp.variant_entry_id
    ANd vp.variant_position_id = vc.variant_position_id 
    AND  vt.variant_Type_Id = vc.variant_type_id
    AND va.variant_allele_id = alt_all
    AND va_r.variant_allele_id = ref_all
	AND vc.variant_change_id = vf.variant_change_id
	AND vf.variant_freq_study_id = vfs.variant_freq_study_id
    AND rsid = ".$VAL;
	$res=runQuery($query);
	return $res;
}



///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////// VARIANT - GENOMIC SEQUENCE //////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



// $[API]
// Title: Variant search in a chromosome range
// Function: getVariantsFromChromosomeRange
// Description: Given a chromsome, a position and a range around that position, search for any variants that fall within that range.
// Parameter: CHR_SEQ_NAME | Chromosome sequence name | string | 22 | required
// Parameter: START_POS | Start position | int | 25459492 | required
// Parameter: END_POS | End position | int | 25459492 | required
// Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
// Parameter: WITH_ALLELES | Include variant alleles | boolean | true | optional | Default: true
// Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | true | optional | Default: true
// Parameter: WITH_GENE | Include gene information | boolean | true | optional | Default: true
// Return: First level array with chromosome position as key and second level array with rsid as key and variant information as value
// Ecosystem: Genomics:genomic sequence|variant|gene
// Example: php biorels_api.php getVariantsFromChromosomeRange -CHR_SEQ_NAME 22 -START_POS 25459492 -END_POS 25459492
// $[/API]
function getVariantsFromChromosomeRange($CHR_SEQ_NAME,$START_POS,$END_POS,$TAX_ID='9606',$WITH_ALLELES=true,$WITH_FREQUENCY=true,$WITH_GENE=true)
{
	
	$query='SELECT DISTINCT rsid, chr_pos
	FROM variant_entry ve, variant_position vp, chr_seq_pos csp, chr_seq cs, genome_assembly ga, taxon t
	WHERE ve.variant_entry_id = vp.variant_entry_id
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND csp.chr_seq_id = cs.chr_seq_id
	AND cs.genome_assembly_id = ga.genome_assembly_id
	AND ga.taxon_id = t.taxon_id
	AND (chr_seq_name=\''.$CHR_SEQ_NAME.'\' OR refseq_name=\''.$CHR_SEQ_NAME.'\' OR genbank_name=\''.$CHR_SEQ_NAME."')
	AND chr_pos >= ".$START_POS."
	AND chr_pos <= ".$END_POS."
	AND tax_id = '".$TAX_ID."'";
	$list= runQuery($query);

	$data=array();
	foreach ($list as $e)$data[$e['chr_pos']][$e['rsid']]=search_variant_by_rsid($e['rsid'],$WITH_ALLELES,$WITH_FREQUENCY,$WITH_GENE);
	return $data;
}






/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// VARIANT - Gene & Locus //////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Find all genes by variant
# // Function: find_gene_from_variant
# // Description: Search for all genes that are located on a variant by using its dbSNP identifier rsid
# // Parameter: RSID | dbSNP identifier | string | rs12345 | required
# // Return: Gene symbol, gene full name, gene ID, gene entry ID, scientific name, taxonomic identifier, rsid
# // Ecosystem: Genomics:variant|gene
# // Example: php biorels_api.php find_gene_from_variant -RSID rs12345
# // $[/API]
function find_gene_from_variant($RSID)
{
    $VAL = $RSID;
    if (substr($RSID, 0, 2) == 'rs') $VAL = substr($RSID, 2);
    if (!is_numeric($VAL)) return array();

    $res = runQuery("SELECT DISTINCT SYMBOL, FULL_NAME, GENE_ID, sp.GN_ENTRY_ID, SCIENTIFIC_NAME, TAX_ID,rsid
	FROM mv_gene_sp sp, gene_seq gs, chr_seq_pos csp, variant_position vp, variant_entry ve
	WHERE gs.chr_seq_id =csp.chr_seq_id AND csp.chr_pos >= gs.start_pos 
	AND csp.chr_pos <= gs.end_pos 
	AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
	AND vp.variant_entry_id = ve.variant_entry_id
	AND sp.gn_entry_id = gs.gn_entry_id
	AND rsid = " . $VAL);
    return $res;
}



# // $[API]
# // Title: Find all variants by gene
# // Function: find_variant_from_gene
# // Description: Search for all variants that are associated with a gene by using its NCBI Gene ID
# // Description: This will search for all the DNA location of the given gene and then search for all the variants that fall within that range.
# // Description: Depending on the parameters, it will return the variant entry record, variant alleles, variant alleles frequency and gene information.
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | true | optional 
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | true | optional
# // Parameter: WITH_GENE | Include gene information | boolean | true | optional 
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant|gene
# // Example: php biorels_api.php find_variant_from_gene -GENE_ID 1017
# // $[/API]
function find_variant_from_gene($GENE_ID,$WITH_ALLELES=false,$WITH_FREQUENCY=false,$WITH_GENE=false)
{
	$res = runQuery("SELECT Start_pos,end_pos,chr_seq_id 
	FROM gn_entry sp, gene_seq gs
	WHERE sp.gn_entry_id = gs.gn_entry_id 
	AND gene_id = " . $GENE_ID);

	$RANGES=array();
	foreach ($res as $e)
	{
		if (!isset($RANGES[$e['chr_seq_id']]))$RANGES[$e['chr_seq_id']]=array('MIN'=>10000000000,'MAX'=>0);
		$RANGES[$e['chr_seq_id']]['MIN']=min($RANGES[$e['chr_seq_id']]['MIN'],$e['start_pos']);
		$RANGES[$e['chr_seq_id']]['MAX']=max($RANGES[$e['chr_seq_id']]['MAX'],$e['start_pos']);
		$RANGES[$e['chr_seq_id']]['MIN']=min($RANGES[$e['chr_seq_id']]['MIN'],$e['end_pos']);
		$RANGES[$e['chr_seq_id']]['MAX']=max($RANGES[$e['chr_seq_id']]['MAX'],$e['end_pos']);
	}

	$LIST=array();
	foreach ($RANGES as $CHR_SEQ_ID=>$RANGE)
	{
		for ($I=$RANGE['MIN'];$I<=$RANGE['MAX'];$I+=10000)
		{
			$MAX=min($I+10000,$RANGE['MAX']);
			
			$res = runQuery("SELECT DISTINCT rsid 
			FROM variant_entry ve, variant_position vp, chr_seq_pos csp
			WHERE ve.variant_entry_id = vp.variant_entry_id
			AND vp.chr_seq_pos_id = csp.chr_seq_pos_id
			AND csp.chr_seq_id = ".$CHR_SEQ_ID."
			AND csp.chr_pos >= ".$I."
			AND csp.chr_pos <= ".$MAX);
			
			foreach ($res as $line)
			{
				if (!isset($LIST[$line['rsid']]))$LIST[$line['rsid']]=array();
			}

		}
	}
	if (!$WITH_ALLELES && !$WITH_FREQUENCY && !$WITH_GENE)return array_keys($LIST);
	foreach ($LIST as $rsid=>&$e)
	echo json_encode(search_variant_by_rsid($rsid,$WITH_ALLELES,$WITH_FREQUENCY,$WITH_GENE),JSON_PRETTY_PRINT);
	return null;
}


# // $[API]
# // Title: List all variant types
# // Function: list_variant_type
# // Description: List all variant types
# // Return: Variant types
# // Ecosystem: Genomics:variant
# // Example: php biorels_api.php list_variant_type
# // $[/API]
function list_variant_type()
{
	return runQuery("SELECT DISTINCT variant_name FROM variant_type");
}

# // $[API]
# // Title: Find all variants by gene
# // Function: find_variant_type_from_gene
# // Description: Search for all variants that are associated with a gene by using its NCBI Gene ID and a specific variant type
# // Description: This will search for all the DNA location of the given gene and then search for all the variants that fall within that range.
# // Description: Depending on the parameters, it will return the variant entry record, variant alleles, variant alleles frequency and gene information.
# // Description: For the list of different variant types, call list_variant_type
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Parameter: SNP_TYPE | Variant type. Call list_variant_type to see the options | string | del | optional
# // Parameter: WITH_ALLELES | Include variant alleles | boolean | false | optional
# // Parameter: WITH_FREQUENCY | Include variant alleles frequency | boolean | false | optional
# // Parameter: WITH_GENE | Include gene information | boolean | false | optional
# // Return: Variant entry record. VARIANT-> Variant entry record, ALLELES-> Variant alleles, FREQUENCY-> Variant alleles frequency, GENE-> Gene information
# // Ecosystem: Genomics:variant|gene
# // Example: php biorels_api.php find_variant_type_from_gene -GENE_ID 1017 -SNP_TYPE 'del'
# // $[/API]
function find_variant_type_from_gene($GENE_ID,$SNP_TYPE=null,$WITH_ALLELES=false,$WITH_FREQUENCY=false,$WITH_GENE=false)
{
	$res = runQuery("SELECT Start_pos,end_pos,chr_seq_id 
	FROM gn_entry sp, gene_seq gs
	WHERE sp.gn_entry_id = gs.gn_entry_id 
	AND gene_id = " . $GENE_ID);

	$RANGES=array();
	foreach ($res as $e)
	{
		if (!isset($RANGES[$e['chr_seq_id']]))$RANGES[$e['chr_seq_id']]=array('MIN'=>10000000000,'MAX'=>0);
		$RANGES[$e['chr_seq_id']]['MIN']=min($RANGES[$e['chr_seq_id']]['MIN'],$e['start_pos']);
		$RANGES[$e['chr_seq_id']]['MAX']=max($RANGES[$e['chr_seq_id']]['MAX'],$e['start_pos']);
		$RANGES[$e['chr_seq_id']]['MIN']=min($RANGES[$e['chr_seq_id']]['MIN'],$e['end_pos']);
		$RANGES[$e['chr_seq_id']]['MAX']=max($RANGES[$e['chr_seq_id']]['MAX'],$e['end_pos']);
	}

	$VARIANT_TYPE_ID=null;
	if ($SNP_TYPE!=null)
	{
		$res=runQuery("SELECT * FROM variant_type where variant_name='".$SNP_TYPE."'");
		if ($res==array())
		{
			throw new Exception("The variant type ".$SNP_TYPE." does not exist. Here is the list of available variant types:\n".
			print_r(list_variant_type(),true));
			
		}
		$VARIANT_TYPE_ID=$res[0]['variant_type_id'];
	}

	$LIST=array();
	foreach ($RANGES as $CHR_SEQ_ID=>$RANGE)
	{
		for ($I=$RANGE['MIN'];$I<=$RANGE['MAX'];$I+=10000)
		{
			$MAX=min($I+100,$RANGE['MAX']);
			echo $I."\n";
			$res = runQuery("SELECT vp.variant_position_id
			FROM  variant_position vp, chr_seq_pos csp
			WHERE vp.chr_seq_pos_id = csp.chr_seq_pos_id
			AND csp.chr_seq_id = ".$CHR_SEQ_ID."
			AND csp.chr_pos >= ".$I."
			AND csp.chr_pos <= ".$MAX);
			$LIST_T=array();
			foreach ($res as $l)
			{
				$LIST_T[]=$l['variant_position_id'];
			}
			if ($LIST_T==array())continue;
			$query="SELECT DISTINCT rsid
			FROM variant_entry ve, variant_position vp, variant_change vc
			WHERE ve.variant_entry_id = vp.variant_entry_id
			AND vc.variant_position_id = vp.variant_position_id
			AND vp.variant_position_id IN (".implode(",",$LIST_T).")";
			if ($VARIANT_TYPE_ID!=null)
			$query.= " AND vc.variant_type_id = ".$VARIANT_TYPE_ID;
			$res = runQuery($query);
			
			foreach ($res as $line)
			{
				if (!isset($LIST[$line['rsid']]))$LIST[$line['rsid']]=array();
			}

		}
	}
	
	if (!$WITH_ALLELES && !$WITH_FREQUENCY && !$WITH_GENE)return array_keys($LIST);
	foreach ($LIST as $rsid=>&$e)
	$results[]= json_encode(search_variant_by_rsid($rsid,$WITH_ALLELES,$WITH_FREQUENCY,$WITH_GENE),JSON_PRETTY_PRINT);
	return $results;
}


/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// VARIANT - transcript //////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all variants associated to a specific transcript
# // Function: get_variant_for_transcript
# // Description: Search for all variants that are associated with a transcript by using its transcript name
# // Description: Can also search for variants that fall within a specific range of the transcript
# // Description: The list of allowed values for the TRANSCRIPT_VARIANT_IMPACT parameter are: 
# // Description: coding_sequence_variant, intron_variant, upstream_transcript_variant, 5_prime_UTR_variant, 
# // Description: 3_prime_UTR_variant, downstream_transcript_variant, splice_donor_variant, terminator_codon_variant
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_000546.5 | required
# // Parameter: START_POS | Start position | int | 1 | optional
# // Parameter: END_POS | End position | int | 10 | optional
# // Parameter: TRANSCRIPT_VARIANT_IMPACT | Transcript variant impact | string | coding_sequence_variant | optional
# // Return: Variant entry record
# // Ecosystem: Genomics:variant|transcript
# // Example: php biorels_api.php get_variant_for_transcript -TRANSCRIPT_NAME 'NM_000546.5'
# // $[/API]
function get_variant_for_transcript($TRANSCRIPT_NAME,$START_POS=null,$END_POS=null,$TRANSCRIPT_VARIANT_IMPACT=null)
{
	$pos=strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
	}
	$query = "SELECT transcript_id from transcript where transcript_name = '".$TRANSCRIPT_NAME."'";
	if (isset($TRANSCRIPT_VERSION))$query.=" AND transcript_version = '".$TRANSCRIPT_VERSION."'";
	$res=runQuery($query);
	$LIST_TR=array();
	foreach ($res as $line) $LIST_TR[]=$line['transcript_id'];
	if (count($LIST_TR)==0)return array();

	$LIST_INFO=array('ALL'=>array(),'POS_TYPE'=>array(),'SO'=>array(),'VARIANT'=>array());

	if ($TRANSCRIPT_VARIANT_IMPACT!=null)
	{
		$LIST_ALLOWED = array(
            "coding_sequence_variant",
            "intron_variant",
            "upstream_transcript_variant",
            "5_prime_UTR_variant",
            "3_prime_UTR_variant",
            "downstream_transcript_variant",
            "splice_donor_variant",
            "terminator_codon_variant",
            "genic_downstream_transcript_variant",
            "genic_upstream_transcript_variant"
        );
		if (!in_array($TRANSCRIPT_VARIANT_IMPACT,$LIST_ALLOWED))
		{
			throw new Exception("Here is the list of allowed values for the TRANSCRIPT_VARIANT_IMPACT parameter:\n".
			print_r($LIST_ALLOWED,true));
			
			
		}
	}

	$FILTER=-1;
	$res=runQuery("SELECT * FROM SO_ENTRY");
	foreach ($res as &$line)
	{
		 $LIST_INFO['SO'][$line['so_entry_id']]=$line['so_name'];
		if ($line['so_name']==$TRANSCRIPT_VARIANT_IMPACT)$FILTER=$line['so_entry_id'];
	}

	$query ="SELECT rsid, ref_all, alt_all, variant_Type_id, so_entry_id, tr_ref_all, tr_alt_all, nucl, seq_pos,seq_pos_type_id, exon_id, transcript_name, transcript_version
	FROM variant_entry ve, variant_position vp, variant_change vc, variant_transcript_map vtm
	LEFT JOIN transcript_pos tp ON tp.transcript_pos_id = vtm.transcript_pos_id, transcript t
	WHERE ve.variant_entry_id = vp.variant_entry_id
	AND vp.variant_position_id = vc.variant_position_id
	AND vc.variant_change_id = vtm.variant_change_id
	AND vtm.transcript_id = t.transcript_id
	AND t.transcript_id IN (".implode(",",$LIST_TR).")";
	if ($FILTER!=-1)$query.=" AND vtm.so_entry_id = ".$FILTER;
	if ($START_POS!=null || $END_POS!=null)
	{
		$query .= ' AND ( vtm.transcript_pos_id IS NULL OR (';
		if ($START_POS!=null && $END_POS!=null) $query .= ' seq_pos >= '.$START_POS.' AND seq_pos <= '.$END_POS;
		else if ($START_POS!=null)$query .= ' seq_pos >= '.$START_POS;
		else if ($END_POS!=null) $query .= '  seq_pos <= '.$END_POS;
		$query.= '))';
	}
	$data=runQuery($query);


	


	
	foreach ($data as &$line)
	{
		if ($line['ref_all']!='')$LIST_INFO['ALL'][$line['ref_all']]='';
		if ($line['alt_all']!='')$LIST_INFO['ALL'][$line['alt_all']]='';
		if ($line['tr_ref_all']!='')$LIST_INFO['ALL'][$line['tr_ref_all']]='';
		if ($line['tr_alt_all']!='')$LIST_INFO['ALL'][$line['tr_alt_all']]='';
		if ($line['seq_pos_type_id']!='')$LIST_INFO['POS_TYPE'][$line['seq_pos_type_id']]='';
		$line['transcript_variant_change']=$LIST_INFO['SO'][$line['so_entry_id']];
		if ($line['variant_type_id']!='')$LIST_INFO['VARIANT'][$line['variant_type_id']]='';
	}

	if (count($LIST_INFO['ALL'])!=0)
	{
		$res=runQuery("SELECT * FROM variant_allele WHERE variant_allele_id IN ('".implode("','",array_keys($LIST_INFO['ALL']))."')");
		foreach ($res as &$line) $LIST_INFO['ALL'][$line['variant_allele_id']]=$line['variant_seq'];
	}
	if (count($LIST_INFO['POS_TYPE'])!=0)
	{
		$res=runQuery("SELECT * FROM transcript_pos_type WHERE transcript_pos_type_id IN ('".implode("','",array_keys($LIST_INFO['POS_TYPE']))."')");
		foreach ($res as &$line) $LIST_INFO['POS_TYPE'][$line['transcript_pos_type_id']]=$line['transcript_pos_type'];
	}
	
	if (count($LIST_INFO['VARIANT'])!=0)
	{
		$res=runQuery("SELECT * FROM variant_Type WHERE variant_type_id IN ('".implode("','",array_keys($LIST_INFO['VARIANT']))."')");
		foreach ($res as &$line) $LIST_INFO['VARIANT'][$line['variant_type_id']]=$line['variant_name'];
	}
	
	foreach ($data as &$line)
	{
		$line['ref_all']=$LIST_INFO['ALL'][$line['ref_all']];
		$line['alt_all']=$LIST_INFO['ALL'][$line['alt_all']];
		$line['tr_ref_all']=$LIST_INFO['ALL'][$line['tr_ref_all']];
		$line['tr_alt_all']=$LIST_INFO['ALL'][$line['tr_alt_all']];
		$line['seq_pos_type_id']=$LIST_INFO['POS_TYPE'][$line['seq_pos_type_id']];
		
		$line['variant_type_id']=$LIST_INFO['VARIANT'][$line['variant_type_id']];
	}
	return $data;

}


/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// VARIANT - protein //////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get all variants associated to a specific protein sequence
# // Function: get_variant_for_protein
# // Description: Search for all variants that are associated with a protein by using its protein isoform id
# // Description: Can also search for variants that fall within a specific range of the protein
# // Description: The list of allowed values for the PROTEIN_VARIANT_IMPACT parameter are:
# // Description: stop_lost, missense_variant, stop_gained, synonymous_variant, inframe_indel, inframe_deletion
# // Parameter: PROTEIN_ISO_ID | Protein name | string | P04066-1 | required
# // Parameter: START_POS | Start position | int | 1 | optional
# // Parameter: END_POS | End position | int | 10 | optional
# // Parameter: PROTEIN_VARIANT_IMPACT | Protein variant impact | string | missense_variant | optional
# // Return: Variant entry record
# // Ecosystem: Genomics:variant;Proteomics:protein
# // Example: php biorels_api.php get_variant_for_protein -PROTEIN_ISO_ID 'P04066-1' -START_POS 1
# // $[/API]
function get_variant_for_protein($PROTEIN_ISO_ID,$START_POS=null,$END_POS=null,$PROTEIN_VARIANT_IMPACT=null)
{
	
	$query = "SELECT prot_seq_id from prot_seq where iso_id = '".$PROTEIN_ISO_ID."'";
	
	$res=runQuery($query);
	$LIST_TR=array();
	foreach ($res as $line) $LIST_TR[]=$line['prot_seq_id'];
	if (count($LIST_TR)==0)return array();

	$LIST_INFO=array('ALL'=>array(),'SO'=>array(),'VARIANT'=>array(),'PR_ALL'=>array());

	if ($PROTEIN_VARIANT_IMPACT!=null)
	{
		$LIST_ALLOWED = array(
            "stop_lost",
			"missense_variant",
			"stop_gained",
			"synonymous_variant",
			"inframe_indel",
			"inframe_deletion"
        );
		if (!in_array($PROTEIN_VARIANT_IMPACT,$LIST_ALLOWED))
		{
			echo "Here is the list of allowed values for the PROTEIN_VARIANT_IMPACT parameter:";
			print_r($LIST_ALLOWED);
			exit;
		}
	}

	$FILTER=-1;
	$res=runQuery("SELECT * FROM SO_ENTRY");
	foreach ($res as &$line)
	{
		 $LIST_INFO['SO'][$line['so_entry_id']]=$line['so_name'];
		if ($line['so_name']==$PROTEIN_VARIANT_IMPACT)$FILTER=$line['so_entry_id'];
	}

	$query ="SELECT rsid, ref_all, alt_all, variant_Type_id, vtm.so_entry_id as tr_impact, tr_ref_all, tr_alt_all,vpm.so_entry_id as pr_impact,prot_ref_all, prot_alt_all, position, letter, iso_id, iso_name
	FROM variant_entry ve, variant_position vp, variant_change vc, variant_transcript_map vtm, variant_protein_map vpm
	LEFT JOIN prot_seq_pos tp ON tp.prot_Seq_pos_id = vpm.prot_seq_pos_Id, prot_seq ps
	WHERE ve.variant_entry_id = vp.variant_entry_id
	AND vp.variant_position_id = vc.variant_position_id
	AND vc.variant_change_id = vtm.variant_change_id
	AND vtm.variant_transcript_id = vpm.variant_transcript_id 
	AND vpm.prot_seq_id = ps.prot_seq_id
	AND ps.prot_seq_id IN (".implode(",",$LIST_TR).")";
	if ($FILTER!=-1)$query.=" AND vtm.so_entry_id = ".$FILTER;
	if ($START_POS!=null || $END_POS!=null)
	{
		$query .= ' AND ( vpm.prot_seq_id IS NULL OR (';
		if ($START_POS!=null && $END_POS!=null) $query .= ' position >= '.$START_POS.' AND position <= '.$END_POS;
		else if ($START_POS!=null)$query .= ' position >= '.$START_POS;
		else if ($END_POS!=null) $query .= '  position <= '.$END_POS;
		$query.= '))';
	}
	$data=runQuery($query);


	


	
	foreach ($data as &$line)
	{
		if ($line['ref_all']!='')$LIST_INFO['ALL'][$line['ref_all']]='';
		if ($line['alt_all']!='')$LIST_INFO['ALL'][$line['alt_all']]='';
		if ($line['tr_ref_all']!='')$LIST_INFO['ALL'][$line['tr_ref_all']]='';
		if ($line['tr_alt_all']!='')$LIST_INFO['ALL'][$line['tr_alt_all']]='';
		if ($line['prot_ref_all']!='')$LIST_INFO['PR_ALL'][$line['prot_ref_all']]='';
		if ($line['prot_alt_all']!='')$LIST_INFO['PR_ALL'][$line['prot_alt_all']]='';
		$line['tr_impact']=$LIST_INFO['SO'][$line['tr_impact']];
		$line['pr_impact']=$LIST_INFO['SO'][$line['pr_impact']];
		if ($line['variant_type_id']!='')$LIST_INFO['VARIANT'][$line['variant_type_id']]='';
	}

	if (count($LIST_INFO['ALL'])!=0)
	{
		$res=runQuery("SELECT * FROM variant_allele WHERE variant_allele_id IN ('".implode("','",array_keys($LIST_INFO['ALL']))."')");
		foreach ($res as &$line) $LIST_INFO['ALL'][$line['variant_allele_id']]=$line['variant_seq'];
	}
	if (count($LIST_INFO['PR_ALL'])!=0)
	{
		$res=runQuery("SELECT * FROM biorels.variant_prot_allele WHERE variant_prot_allele_id  IN ('".implode("','",array_keys($LIST_INFO['PR_ALL']))."')");
		foreach ($res as &$line) $LIST_INFO['PR_ALL'][$line['variant_prot_allele_id']]=$line['variant_prot_seq'];
	}
	
	
	if (count($LIST_INFO['VARIANT'])!=0)
	{
		$res=runQuery("SELECT * FROM variant_Type WHERE variant_type_id IN ('".implode("','",array_keys($LIST_INFO['VARIANT']))."')");
		foreach ($res as &$line) $LIST_INFO['VARIANT'][$line['variant_type_id']]=$line['variant_name'];
	}
	
	foreach ($data as &$line)
	{
		$line['ref_all']=$LIST_INFO['ALL'][$line['ref_all']];
		$line['alt_all']=$LIST_INFO['ALL'][$line['alt_all']];
		$line['tr_ref_all']=$LIST_INFO['ALL'][$line['tr_ref_all']];
		$line['tr_alt_all']=$LIST_INFO['ALL'][$line['tr_alt_all']];
		$line['prot_ref_all']=$LIST_INFO['PR_ALL'][$line['prot_ref_all']];
		$line['prot_alt_all']=$LIST_INFO['PR_ALL'][$line['prot_alt_all']];
		
		$line['variant_type_id']=$LIST_INFO['VARIANT'][$line['variant_type_id']];
	}
	
	return $data;

}



///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// GLOBAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



// $[API]
// Title: Search source by name
// Function: source_search
// Description: Search for a source by using its name. First by exact match, then by partial match. Case insensitive.
// Parameter: source_name | Source name | string | UniProt | required
// Ecosystem: Global
// Example: php biorels_api.php source_search -source_name 'NCBI'
// Example: php biorels_api.php source_search -source_name 'unip'
// $[/API]
function source_search($source_name)
{
    $query="SELECT * FROM source where LOWER(source_name) ='".strtolower($source_name)."'";
    
    $res=runQuery($query);
    if (count($res)!=0)return $res;
    $query="SELECT * FROM source where LOWER(source_name) LIKE '%".strtolower($source_name)."%'";
    
    $res=runQuery($query);

    if (count($res)!=0)return $res;
    return array();
}







///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PROTEOMIC ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PROTEIN  //////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein information by protein identifier
# // Function: search_protein_by_identifier
# // Description: Search for a protein by using its identifier
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein record
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php search_protein_by_identifier -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function search_protein_by_identifier($IDENTIFIER)
{
	$query="SELECT * FROM prot_entry WHERE PROT_IDENTIFIER='".$IDENTIFIER."'";
	return runQuery($query);
}


# // $[API]
# // Title: Get protein information by protein accession
# // Function: search_protein_by_accession
# // Description: Search for a protein by using its UniProt accession. 
# // Description: By default, only search protein records that have the accession as primary.
# // Parameter: ACCESSION | Protein accession | string | P24941 | required
# // Parameter: IS_PRIMARY | Is primary accession | boolean | true | optional | Default: true
# // Return: Protein record
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php search_protein_by_accession -ACCESSION 'P24941'
# // Example: php biorels_api.php search_protein_by_accession -ACCESSION 'P24941' -IS_PRIMARY false
# // $[/API]
function search_protein_by_accession($ACCESSION, $IS_PRIMARY=true)
{
	$query="SELECT * FROM prot_entry PE, prot_ac A 
	WHERE pe.prot_entry_Id = a.prot_entry_id 
	AND is_primary = '".($IS_PRIMARY ? "T" : "F")."'
	AND ac='".$ACCESSION."'";
	return runQuery($query);
}


# // $[API]
# // Title: List all protein accessions for a protein
# // Function: get_protein_accession
# // Description: List all UniProt protein accessions for a protein
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein accession, is primary
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php get_protein_accession -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_protein_accession($IDENTIFIER)
{
	$query="SELECT AC,is_primary FROM prot_entry PE, prot_ac A 
	WHERE pe.prot_entry_Id = a.prot_entry_id 
	AND prot_identifier='".$IDENTIFIER."'";
	return runQuery($query);
}



# // $[API]
# // Title: Get protein names by protein identifier
# // Function: get_protein_names
# // Description: Get all protein names for a protein by using its identifier
# // Parameter: IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein name, protein name type
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php get_protein_names -IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_protein_names($IDENTIFIER)
{
	$query="SELECT A.*, pnm.* FROM prot_entry PE, prot_name A , prot_name_map pnm
	WHERE pe.prot_entry_Id = pnm.prot_entry_id 
	AND pnm.prot_name_id = a.prot_name_id
	AND prot_identifier='".$IDENTIFIER."'";
	return runQuery($query);
}

# // $[API]
# // Title: Search protein by protein name
# // Function: search_protein_by_name
# // Description: Search for a protein by using its name
# // Parameter: NAME | Protein name (Case sensitive) | string | Cyclin-dependent kinase 2 | required
# // Return: Protein record
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php search_protein_by_name -NAME 'Cyclin-dependent kinase 2'
# // $[/API]
function search_protein_by_name($NAME)
{
	$query="SELECT * FROM prot_entry PE, prot_name A , prot_name_map pnm
	WHERE pe.prot_entry_Id = pnm.prot_entry_id 
	AND pnm.prot_name_id = a.prot_name_id
	AND protein_name='".$NAME."'";
	return runQuery($query);
}



# // $[API]
# // Title: Search protein by EC Number
# // Function: search_protein_by_EC_number
# // Description: Search for a protein by the Enzyme Commission number
# // Description: The search is done by including the sub-levels of the EC number.
# // Parameter: EC | Enzyme commission number | string | 2.7.11.22 | required
# // Return: Protein record
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php search_protein_by_EC_number -EC '2.7.11.22'
# // Example: php biorels_api.php search_protein_by_EC_number -EC '2.7'
# // $[/API]
function search_protein_by_EC_number($EC)
{
	$query="SELECT * FROM prot_entry PE, prot_name A , prot_name_map pnm
	WHERE pe.prot_entry_Id = pnm.prot_entry_id 
	AND pnm.prot_name_id = a.prot_name_id
	AND ec_number LIKE '".$EC."%'";
	return runQuery($query);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// PROTEIN - GENE  ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein information by NBCI gene identifier
# // Function: get_protein_by_gene
# // Description: Search for a protein by using its NCBI gene identifier
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein record
# // Ecosystem: Genomics:gene;Proteomics:protein
# // Example: php biorels_api.php get_protein_by_gene -GENE_ID 1017
# // $[/API]
function get_protein_by_gene($GENE_ID)
{
	$query="SELECT * FROM prot_entry PE, gn_prot_map PGM, gn_entry GE
	WHERE pe.prot_entry_Id = pgm.prot_entry_id 
	AND GE.gn_entry_id = pgm.gn_entry_id
	AND gene_id='".$GENE_ID."'";
	return runQuery($query);
}


# // $[API]
# // Title: Get protein by gene symbol
# // Function: get_protein_by_gene_symbol
# // Description: Search for a protein by using its gene symbol
# // Parameter: SYMBOL | Gene symbol | string | CDK2 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional | Default: 9606
# // Return: Protein record
# // Ecosystem: Genomics:gene;Proteomics:protein
# // Example: php biorels_api.php get_protein_by_gene_symbol -SYMBOL 'CDK2'
# // $[/API]
function get_protein_by_gene_symbol($SYMBOL,$TAX_ID='9606')
{
	$data=get_gene_by_gene_symbol($SYMBOL,array($TAX_ID));
	if (count($data)==0) return array();
	foreach ($data as &$record)
	{
		$record['PROTEIN']=get_protein_by_gene($record['gene_id']);
	}
	return $data;
}



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// PROTEIN - TAXON  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Get protein by taxon
// Function: list_protein_by_taxon
// Description: Get all protein records for a given taxonomic identifier
// Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | required
// Return: Protein record
// Ecosystem: Proteomics:protein;Genomics:taxon
// Example: php biorels_api.php list_protein_by_taxon -TAX_ID 9606
// Warning: High volume
// $[/API]
function list_protein_by_taxon($TAX_ID)
{
	$query="SELECT pe.* FROM prot_entry PE, taxon t
	WHERE pe.taxon_id=t.taxon_id
	AND tax_id='".$TAX_ID."'";
	return runQuery($query);
}



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// PROTEIN SEQUENCE  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Get all protein sequence record by protein identifier
// Function: get_protein_sequences_for_entry
// Description: Search for a protein sequence by using the protein identifier
// Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
// Parameter: CANONICAL_ONLY | Is canonical sequence | boolean | false | optional | Default: false
// Return: Protein sequence record
// Ecosystem: Proteomics:protein|protein sequence
// Example: php biorels_api.php get_protein_sequences_for_entry -PROT_IDENTIFIER 'CDK2_HUMAN' -CANONICAL_ONLY false
// $[/API]
function get_protein_sequences_for_entry($PROT_IDENTIFIER, $CANONICAL_ONLY=false)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='".$PROT_IDENTIFIER."'";
	if ($CANONICAL_ONLY)$query.=" AND is_primary='T'";
	return runQuery($query);
}


// $[API]
// Title: Get protein sequence by protein identifier
// Function: search_protein_sequence_by_isoform_id
// Description: Search for a protein sequence by using the isoform identifier
// Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
// Return: Protein sequence record
// Ecosystem: Proteomics:protein|protein sequence
// Example: php biorels_api.php search_protein_sequence_by_isoform_id -ISOFORM_ID 'P24941-1'
// $[/API]
function search_protein_sequence_by_isoform_id($ISOFORM_ID)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  iso_id='".$ISOFORM_ID."'";
	return runQuery($query);
}



# // $[API]
# // Title: Get information for a protein isoform
# // Function: get_isoform_info
# // Description: Get information for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein isoform record
# // Ecosystem: Proteomics:protein|protein sequence
# // Example: php biorels_api.php get_isoform_info -ISOFORM_ID 'P24941-1'
# // $[/API]
function get_isoform_info($ISOFORM_ID)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe , taxon t
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND t.taxon_Id= pe.taxon_id
		AND  iso_id='".$ISOFORM_ID."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_seq_pos WHERE prot_seq_id=".$ENTRY['prot_seq_id'].' ORDER BY position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		
		$ENTRY['sequence']=$str;
	}
	return $DATA;
}

// $[API]
// Title: Get protein sequence in fasta by isoform
// Function: get_fasta_sequence
// Description: Retrieve fasta sequence of a protein isoform
// Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
// Return: Protein sequence in fasta format
// Ecosystem: Proteomics:protein|protein sequence
// Example: php biorels_api.php get_fasta_sequence -ISOFORM_ID 'P24941-1'
// $[/API]
function get_fasta_sequence($ISOFORM_ID)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  iso_id='".$ISOFORM_ID."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_seq_pos WHERE prot_seq_id=".$ENTRY['prot_seq_id'].' ORDER BY position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		$ENTRY['SEQUENCE']= ">".$ENTRY['prot_identifier']."|".$ENTRY['iso_id']."\n";
		$ENTRY['SEQUENCE'].= implode("\n",str_split($str,100))."\n";
	}
	return $DATA;
}


# // $[API]
# // Title: Get all protein sequences in fasta by protein identifier
# // Function: get_fasta_sequences
# // Description: Retrieve fasta sequences of a protein by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein sequences in fasta format
# // Ecosystem: Proteomics:protein|protein sequence
# // Example: php biorels_api.php get_fasta_sequences -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_fasta_sequences($PROT_IDENTIFIER)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='".$PROT_IDENTIFIER."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_seq_pos WHERE prot_seq_id=".$ENTRY['prot_seq_id'].' ORDER BY position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		$ENTRY['SEQUENCE']= ">".$ENTRY['prot_identifier']."|".$ENTRY['iso_id']."\n";
		$ENTRY['SEQUENCE'].= implode("\n",str_split($str,100))."\n";
	}
	return null;
}


# // $[API]
# // Title: Get all protein sequences in fasta for a given gene
# // Function: get_fasta_sequences_by_gene
# // Description: Retrieve fasta sequences of a protein by using the NCBI gene identifier
# // Parameter: NCBI_GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein sequences in fasta format
# // Ecosystem: Proteomics:protein|protein sequence;Genomics:gene
# // Example: php biorels_api.php get_fasta_sequences_by_gene -NCBI_GENE_ID 1017
# // $[/API]
function get_fasta_sequences_by_gene($NCBI_GENE_ID)
{
	$query="SELECT * 
		FROM prot_seq ps, prot_entry pe, gn_prot_map pgm, gn_entry ge
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND ge.gn_entry_id = pgm.gn_entry_id
		AND pe.prot_entry_Id = pgm.prot_entry_id 
		AND  gene_id='".$NCBI_GENE_ID."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_seq_pos WHERE prot_seq_id=".$ENTRY['prot_seq_id'].' ORDER BY position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		$ENTRY['SEQUENCE']= ">".$ENTRY['gene_id']."|".$ENTRY['symbol']."|".$ENTRY['prot_identifier']."|".$ENTRY['iso_id']."\n";
		$ENTRY['SEQUENCE'].= implode("\n",str_split($str,100))."\n";
	}
	return $DATA;
}



# // $[API]
# // Title: Get all features for a protein isoform
# // Function: get_isoform_feature
# // Description: Get all features for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Feature record
# // Ecosystem: Proteomics:protein sequence
# // Example: php biorels_api.php get_isoform_feature -ISOFORM_ID 'P24941-1'
# // $[/API]

function get_isoform_feature($ISOFORM_ID)
{
	$query = "SELECT pf.*, tag,pft.description FROM prot_seq ps, prot_feat pf, prot_feat_Type pft
	WHERE ps.prot_seq_id = pf.prot_seq_id
	and pf.prot_feat_type_id = pft.prot_feat_type_id
	AND iso_id='".$ISOFORM_ID."'";
	$tmp=runQuery($query);
	$data=array();
	foreach ($tmp as $t)
	{
		$data[$t['prot_feat_id']]=$t;
	}
	if (count($data)==0) return null;
	$query = "SELECT prot_feat_id, pmid,eco_id,eco_name,title, publication_date FROM prot_feat_pmid pfm, eco_Entry ee, pmid_entry pe
	 WHERE 
	 pfm.eco_entry_id = ee.eco_entry_id
	 AND pe.pmid_entry_id = pfm.pmid_entry_id
	 AND prot_feat_id IN (".implode(',',array_keys($data)).")";
	$tmp=runQuery($query);
	foreach ($tmp as $t)
	{
		$data[$t['prot_feat_id']]['PMID'][]=$t;
	}
	return $data;
}


# // $[API]
# // Title: Get all textual descriptions for a protein 
# // Function: get_protein_description
# // Description: Get all textual descriptions for a protein
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein description record
# // Ecosystem: Proteomics:protein
# // Example: php biorels_api.php get_protein_description -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_protein_description($PROT_IDENTIFIER)
{
	$query="SELECT desc_type,description FROM prot_entry PE, prot_desc A 
	WHERE pe.prot_entry_Id = a.prot_entry_id 
	AND prot_identifier='".$PROT_IDENTIFIER."'";
	return runQuery($query);

}


///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// PROTEIN DOMAIN  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: Get all protein domain record by protein identifier
// Function: get_protein_domains_for_entry
// Description: Search for a protein domain by using the protein identifier
// Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
// Return: Protein domain record
// Ecosystem: Proteomics:protein|protein domain
// Example: php biorels_api.php get_protein_domains_for_entry -PROT_IDENTIFIER 'CDK2_HUMAN' 
// $[/API]
function get_protein_domains_for_entry($PROT_IDENTIFIER)
{
	$query="SELECT * 
		FROM prot_dom ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='".$PROT_IDENTIFIER."'";
	return runQuery($query);
}


// $[API]
// Title: Get protein domain by protein identifier
// Function: search_protein_domain_by_domain_name
// Description: Search for a protein domain by using the d identifier
// Parameter: DOMAIN_NAME | Name of domain | string | Protein kinase | required
// Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | optional
// Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional
// Return: Protein domain record
// Ecosystem: Proteomics:protein|protein domain
// Example: php biorels_api.php search_protein_domain_by_domain_name -DOMAIN_NAME 'Protein kinase' -PROT_IDENTIFIER 'CDK2_HUMAN'
// Example: php biorels_api.php search_protein_domain_by_domain_name -DOMAIN_NAME 'Protein kinase'  -TAX_ID 9606
// $[/API]
function search_protein_domain_by_domain_name($DOMAIN_NAME,$PROT_IDENTIFIER='',$TAX_ID='')
{
	$query="SELECT * 
		FROM prot_dom ps, prot_entry pe ";
		if ($TAX_ID != '') $query .=' , taxon t ';
	$query.= "WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  domain_name='".$DOMAIN_NAME."'";
		if ($PROT_IDENTIFIER!='')
	$query.=" AND  prot_identifier='".$PROT_IDENTIFIER."'";
	if ($TAX_ID !='')
	{
		$query.=" AND pe.taxon_id=t.taxon_id
		AND tax_id='".$TAX_ID."'";
	}
	return runQuery($query);
}




# // $[API]
# // Title: Get all protein domains in fasta by protein identifier
# // Function: get_fasta_domains
# // Description: Retrieve fasta domains of a protein by using the protein identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Protein domains in fasta format
# // Ecosystem: Proteomics:protein|protein domain
# // Example: php biorels_api.php get_fasta_domains -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_fasta_domains($PROT_IDENTIFIER)
{
	$query="SELECT * 
		FROM prot_dom ps, prot_entry pe 
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND  prot_identifier='".$PROT_IDENTIFIER."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_dom_seq pds, prot_seq_pos psp 
		WHERE psp.prot_seq_pos_id = pds.prot_Seq_pos_id 
		AND prot_dom_id=".$ENTRY['prot_dom_id'].' ORDER BY pds.position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		$ENTRY['SEQUENCE']= ">".$ENTRY['prot_identifier']."|".$ENTRY['domain_type']."|".$ENTRY['domain_name']."|".$ENTRY['pos_start']."|".$ENTRY['pos_end']."\n";
		$ENTRY['SEQUENCE'].= implode("\n",str_split($str,100))."\n";
	}
	return $DATA;
}


# // $[API]
# // Title: Get all protein domains in fasta for a given gene
# // Function: get_fasta_domains_by_gene
# // Description: Retrieve fasta domains of a protein by using the NCBI gene identifier
# // Parameter: NCBI_GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Protein domains in fasta format
# // Ecosystem: Proteomics:protein|protein domain;Genomics:gene
# // Example: php biorels_api.php get_fasta_domains_by_gene -NCBI_GENE_ID 1017
# // $[/API]
function get_fasta_domains_by_gene($NCBI_GENE_ID)
{
	$query="SELECT * 
		FROM prot_dom ps, prot_entry pe, gn_prot_map pgm, gn_entry ge
		WHERE pe.prot_entry_Id = ps.prot_entry_id 
		AND ge.gn_entry_id = pgm.gn_entry_id
		AND pe.prot_entry_Id = pgm.prot_entry_id 
		AND  gene_id='".$NCBI_GENE_ID."'";
	$DATA=runQuery($query);
	if (count($DATA)==0) return null;
	foreach ($DATA as &$ENTRY)
	{
	
		$res=runQuery("SELECT * FROM prot_dom_seq pds, prot_seq_pos psp 
		WHERE psp.prot_seq_pos_id = pds.prot_Seq_pos_id 
		AND prot_dom_id=".$ENTRY['prot_dom_id'].' ORDER BY pds.position ASC');
		$str='';
		if (count($res)==0) continue;
		foreach ($res as $l)$str.=$l['letter'];
		$ENTRY['SEQUENCE']= ">".$ENTRY['gene_id']."|".$ENTRY['symbol']."|".$ENTRY['prot_identifier']."|".$ENTRY['domain_type']."|".$ENTRY['domain_name']."|".$ENTRY['pos_start']."|".$ENTRY['pos_end']."\n";
		$ENTRY['SEQUENCE'].= implode("\n",str_split($str,100))."\n";
	}
	return $DATA;
}




///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////// PROTEIN SEQUENCE ALIGNMENT  ////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: list all protein sequence alignment by protein isoform
# // Function: list_protein_sequence_alignment
# // Description: Get all protein sequence alignment for a protein isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein sequence alignment record
# // Ecosystem: Proteomics:protein|protein sequence alignment
# // Example: php biorels_api.php list_protein_sequence_alignment -ISOFORM_ID 'P24941-1'
# // $[/API]
function list_protein_sequence_alignment($ISOFORM_ID)
{
	$query="SELECT pc.iso_name,pc.iso_id, pc.is_primary, pc.description, perc_sim,perc_identity,length,e_value,bit_score,perc_sim_com,perc_identity_com
	 FROM prot_seq ps, prot_seq_al psa, prot_seq pc
	WHERE ps.prot_seq_id = psa.prot_seq_ref_id
	AND pc.prot_seq_id = psa.prot_seq_comp_id
	AND  ps.iso_id='".$ISOFORM_ID."'";
	return runQuery($query);
}


# // $[API]
# // Title: Get protein sequence alignment by protein isoform
# // Function: get_protein_sequence_alignment
# // Description: Get the protein sequence alignment for a protein isoform
# // Parameter: REF_ISO_ID | Reference isoform identifier | string | P24941-1 | required
# // Parameter: COMP_ISO_ID | Comparison isoform identifier | string | P24941-1 | required
# // Return: Protein sequence alignment record
# // Ecosystem: Proteomics:protein|protein sequence alignment
# // Example: php biorels_api.php get_protein_sequence_alignment -REF_ISO_ID 'P24941-1' -COMP_ISO_ID 'P24941-1'
# // $[/API]
function get_protein_sequence_alignment($REF_ISO_ID,$COMP_ISO_ID)
{
	$query="SELECT prot_seq_al_id,perc_sim,perc_identity,length,e_value,bit_score,perc_sim_com,perc_identity_com
	 FROM prot_seq ps, prot_seq_al psa, prot_seq pc
	WHERE ps.prot_seq_id = psa.prot_seq_ref_id
	AND pc.prot_seq_id = psa.prot_seq_comp_id
	AND  ps.iso_id='".$REF_ISO_ID."'
	AND pc.iso_id='".$COMP_ISO_ID."'";
	$tmp= runQuery($query);
	if (count($tmp)==0) return null;

	$SEQ_REF=array();
	$SEQ_COMP=array();
	$query="SELECT iso_id, position, letter FROM prot_seq_pos psp, prot_seq ps
	 WHERE ps.prot_seq_id = psp.prot_seq_id
	 AND (ps.iso_id='".$REF_ISO_ID."' OR ps.iso_id = '".$COMP_ISO_ID."') ORDER BY iso_id,position ASC";
	$res=runQuery($query);
	foreach ($res as $r)
	{
		if ($r['iso_id']==$REF_ISO_ID)$SEQ_REF[$r['position']]=$r['letter'];
		else $SEQ_COMP[$r['position']]=$r['letter'];
	}




	$data=array();
	foreach ($tmp as $t)
	{
		$data[$t['prot_seq_al_id']]['INFO']=$t;
		$list_match=runQuery("SELECT USP1.POSITION as REF_POS, USP2.POSITION as COMP_POS
		FROM PROT_SEQ_al_seq UDA,  PROT_SEQ_POS USP1, PROT_SEQ_POS USP2
		WHERE uda.PROT_SEQ_ID_ref= USP1.PROT_SEQ_POS_ID
		AND uda.PROT_SEQ_ID_comp= USP2.PROT_SEQ_POS_ID
		AND uda.PROT_SEQ_al_id = " . $t['prot_seq_al_id'].' ORDER BY USP1.POSITION ASC');
		$CURR_REF_POS=1;
		$CURR_ALT_POS=1;

		foreach ($list_match as $m)
		{
			//echo $CURR_REF_POS.' '.$CURR_ALT_POS.' '.$m['ref_pos'].' '.$m['comp_pos']."\n";
			for (;$CURR_REF_POS<$m['ref_pos'];$CURR_REF_POS++)
			{
				$data[$t['prot_seq_al_id']]['MATCH'][]=array($CURR_REF_POS,$SEQ_REF[$CURR_REF_POS],'','','');
			}
			for (; $CURR_ALT_POS<$m['comp_pos'];$CURR_ALT_POS++)
			{
				$data[$t['prot_seq_al_id']]['MATCH'][]=array('','',$CURR_ALT_POS,$SEQ_COMP[$CURR_ALT_POS],'');
			}
			$data[$t['prot_seq_al_id']]['MATCH'][]=array($m['ref_pos'],$SEQ_REF[$m['ref_pos']],$m['comp_pos'],$SEQ_COMP[$m['comp_pos']],($SEQ_REF[$m['ref_pos']]==$SEQ_COMP[$m['comp_pos']] ? 'MATCH' : 'MISMATCH'));
			$CURR_REF_POS++;$CURR_ALT_POS++;
			
		}
		for (;$CURR_REF_POS<max(array_keys($SEQ_REF));$CURR_REF_POS++)
			{
				$data[$t['prot_seq_al_id']]['MATCH'][]=array($CURR_REF_POS,$SEQ_REF[$CURR_REF_POS],'','');
			}
			for (; $CURR_ALT_POS<max(array_keys($SEQ_COMP));$CURR_ALT_POS++)
			{
				$data[$t['prot_seq_al_id']]['MATCH'][]=array('','',$CURR_ALT_POS,$SEQ_COMP[$CURR_ALT_POS]);
			}

		// foreach ($data[$t['prot_seq_al_id']]['MATCH'] as $k)
		// echo implode("\t",$k)."\n";
	}
	
	return $data;
}








///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// PROTEIN TRANSCRIPT  ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get protein transcript translation
# // Function: get_translation
# // Description: Get the mapping between a transcript and a translated protein
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomics:protein;Genomics:transcript
# // Example: php biorels_api.php get_translation -ISOFORM_ID 'P24941-1' -TRANSCRIPT_NAME 'ENST00000379031'
# // $[/API]
function get_translation($ISOFORM_ID,$TRANSCRIPT_NAME)
{
	$DATA['TRANSCRIPT']=search_transcript($TRANSCRIPT_NAME);
	$DATA['ISOFORM']=search_protein_sequence_by_isoform_id($ISOFORM_ID);
	if ($DATA['TRANSCRIPT']==array() || $DATA['ISOFORM']==array()) return array();
	
	foreach ($DATA['TRANSCRIPT'] as &$TR)
	{
		$TR['SEQ']=array();
		$res=runQuery("SELECT * FROM transcript_pos WHERE transcript_id=".$TR['transcript_id'].' ORDER BY seq_pos ASC');
		foreach ($res as $line)
		{
			$TR['SEQ'][$line['transcript_pos_id']]=array($line['nucl'],$line['seq_pos']);
		}
	}

	foreach ($DATA['ISOFORM'] as &$PROT)
	{
		$PROT['SEQ']=array();
		$res=runQuery("SELECT * FROM prot_seq_pos WHERE prot_seq_id=".$PROT['prot_seq_id'].' ORDER BY position ASC');
		foreach ($res as $line)
		{
			$PROT['SEQ'][$line['prot_seq_pos_id']]=array($line['letter'],$line['position']);
		}
	}
	
	foreach ($DATA['TRANSCRIPT'] as &$TR)
	{
		foreach ($DATA['ISOFORM'] as &$PROT)
		{
			$res=runQuery("SELECT * FROM tr_protseq_al 
			WHERE prot_seq_id=".$PROT['prot_seq_id'].' AND transcript_id='.$TR['transcript_id']);
			if (count($res)==0) continue;
			$DATA['TRANSLATION']=array();
			foreach ($res as $al)
			{
				$DATA['TRANSLATION'][$al['tr_protseq_al_id']]=array();
				foreach ($TR['SEQ'] as $k=>$v)
				{
					$DATA['TRANSLATION'][$al['tr_protseq_al_id']][$v[1]]=array($v[0],$v[1],'','','');
				}
				$res2=runQuery("SELECT * FROM tr_protseq_pos_al WHERE tr_protseq_al_id=".$al['tr_protseq_al_id']);
				foreach ($res2 as $l2)
				{
					$DATA['TRANSLATION'][$al['tr_protseq_al_id']][$TR['SEQ'][$l2['transcript_pos_id']][1]]=array(
						$TR['SEQ'][$l2['transcript_pos_id']][0],
						$TR['SEQ'][$l2['transcript_pos_id']][1],
						$PROT['SEQ'][$l2['prot_seq_pos_id']][0],
						$PROT['SEQ'][$l2['prot_seq_pos_id']][1],
						$l2['triplet_pos']
					);
				}
				ksort($DATA['TRANSLATION'][$al['tr_protseq_al_id']]);

			}
		}	
	}
	return $DATA;

}


# // $[API]
# // Title: Get protein transcript translation for a given isoform
# // Function: get_translation_for_isoform
# // Description: Get the mapping between a transcript and a translated protein for a given isoform
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomics:protein;Genomics:transcript
# // Example: php biorels_api.php get_translation_for_isoform -ISOFORM_ID 'P24941-1'
# // $[/API]
function get_translation_for_isoform($ISOFORM_ID)
{
	$res = runQuery("SELECT * FROM TRANSCRIPT T, TR_PROTSEQ_AL TUA, PROT_SEQ US WHERE
	T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
    AND TUA.PROT_SEQ_ID = US.PROT_SEQ_ID AND US.ISO_ID ='" . $ISOFORM_ID."'");
	if (count($res)==0) return array();
	$DATA=array();
	foreach ($res as $line)$DATA[]=get_translation($ISOFORM_ID,$line['transcript_name'].(($line['transcript_version']!='')?'.'.$line['transcript_version']:''));
   	return $DATA;
} 


# // $[API]
# // Title: Get protein transcript translation for a given transcript
# // Function: get_translation_for_transcript
# // Description: Get the mapping between a transcript and a translated protein for a given transcript
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomics:protein;Genomics:transcript
# // Example: php biorels_api.php get_translation_for_transcript -TRANSCRIPT_NAME NM_001798
# // $[/API]
function get_translation_for_transcript($TRANSCRIPT_NAME)
{
	$pos = strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query = "SELECT * FROM PROT_SEQ PS, TR_PROTSEQ_AL TUA, TRANSCRIPT T WHERE
	T.TRANSCRIPT_ID = TUA.TRANSCRIPT_ID
	AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID AND T.TRANSCRIPT_NAME ='" . $TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND T.TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";
	$res = runQuery($query);
	if (count($res)==0) return array();
	$DATA=array();
	foreach ($res as $line)$DATA[]=get_translation($line['iso_id'],$TRANSCRIPT_NAME);
   	return $DATA;
}


# // $[API]
# // Title: Get protein transcript translation for a given transcript position
# // Function: get_translation_transcript_pos
# // Description: Get the mapping between a transcript and a translated protein for a given transcript position
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | NM_001798 | required
# // Parameter: POSITION | Transcript position | int | 400 | required
# // Return: Protein record and sequence, transcript record and sequence, translation order by transcript sequence
# // Ecosystem: Proteomics:protein;Genomics:transcript
# // Example: php biorels_api.php get_translation_transcript_pos -TRANSCRIPT_NAME NM_001798 -POSITION 400
# // $[/API]
function get_translation_transcript_pos($TRANSCRIPT_NAME,$POSITION)
{
	$pos = strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query = "SELECT triplet_pos,iso_id, iso_name, letter,position, psp.prot_seq_pos_id, ps.prot_seq_id,prot_entry_id 
	FROM PROT_SEQ_POS PSP,PROT_SEQ PS, TR_PROTSEQ_AL TUA, TR_PROTSEQ_POS_AL TPSP, TRANSCRIPT T, TRANSCRIPT_POS TP WHERE
	PSP.PROT_SEQ_ID = PS.PROT_SEQ_ID AND PSP.PROT_SEQ_POS_ID = TPSP.PROT_SEQ_POS_ID
	AND TUA.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
	AND TP.TRANSCRIPT_POS_ID = TPSP.TRANSCRIPT_POS_ID
	AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID 
	AND TP.SEQ_POS=".$POSITION." AND T.TRANSCRIPT_NAME ='" . $TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND T.TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";
	$res = runQuery($query);
	return $res;
}




# // $[API]
# // Title: Get protein transcript translation for a given protein position
# // Function: get_translation_isoform_pos
# // Description: Get the mapping between a transcript and a translated protein for a given protein position
# // Parameter: ISOFORM_ID | Isoform identifier | string | P24941-1 | required
# // Parameter: POSITION | Transcript position | int | 74 | required
# // Return: Protein record and sequence, transcript record and sequence, translation
# // Ecosystem: Proteomics:protein;Genomics:transcript
# // Example: php biorels_api.php get_translation_isoform_pos -ISOFORM_ID P24941-1 -POSITION 74
# // $[/API]
function get_translation_isoform_pos($ISOFORM_ID,$POSITION)
{
	
	$query = "SELECT transcript_name, transcript_version,  exon_id, seq_pos,nucl,  triplet_pos, seq_pos_type_id,T.transcript_id, TP.transcript_pos_id, chr_seq_pos_id
	FROM PROT_SEQ_POS PSP,PROT_SEQ PS, TR_PROTSEQ_AL TUA, TR_PROTSEQ_POS_AL TPSP, TRANSCRIPT T, TRANSCRIPT_POS TP WHERE
	PSP.PROT_SEQ_ID = PS.PROT_SEQ_ID AND PSP.PROT_SEQ_POS_ID = TPSP.PROT_SEQ_POS_ID
	AND TUA.TRANSCRIPT_ID = T.TRANSCRIPT_ID AND T.TRANSCRIPT_ID = TP.TRANSCRIPT_ID
	AND TP.TRANSCRIPT_POS_ID = TPSP.TRANSCRIPT_POS_ID
	AND TUA.PROT_SEQ_ID = PS.PROT_SEQ_ID 
	AND POSITION=".$POSITION." AND ISO_ID ='" . $ISOFORM_ID."'";
	$res = runQuery($query);
	return $res;
}



///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////  GENE ONTOLOGY  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: Search gene ontology record by name and/or accession and/or namespace
# // Function: search_gene_ontology
# // Description: Search for a gene ontology record by using its name and/or accession and/or namespace
# // Parameter: AC | Gene ontology accession | string | GO:0010389 
# // Parameter: NAME | Gene ontology name | string | cell cycle | optional
# // Parameter: NAMESPACE | Gene ontology namespace | string | biological_process |  optional
# // Return: Gene ontology record
# // Ecosystem: Proteomics:gene ontology
# // Example: php biorels_api.php search_gene_ontology -AC 'GO:0010389'
# // $[/API]
function search_gene_ontology($AC='',$NAME='',$NAMESPACE='')
{
	$query="SELECT * FROM go_entry WHERE 1=1";
	if ($AC!='')$query.=" AND AC='".$AC."'";
	if ($NAME!='')$query.=" AND name LIKE '%".$NAME."%'";
	if ($NAMESPACE!='')$query.=" AND namespace='".$NAMESPACE."'";
	return runQuery($query);
}


# // $[API]
# // Title: List external identifiers for a gene ontology record
# // Function: get_gene_ontology_dbref
# // Description: Get all external identifiers for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Return: Gene ontology external identifiers
# // Ecosystem: Proteomics:gene ontology
# // Example: php biorels_api.php get_gene_ontology_dbref -AC 'GO:0010389'
# // $[/API]
function get_gene_ontology_dbref($AC)
{
	$query="SELECT * FROM go_dbref g, go_entry ge , source s
	WHERE 
	ge.GO_entry_ID = g.GO_entry_ID AND s.source_id = g.source_id
	AND AC='".$AC."'";
	return runQuery($query);

}

# // $[API]
# // Title: Get all child gene ontology records for a gene ontology record
# // Function: get_child_gene_ontology
# // Description: Get all child gene ontology records for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: MAX_LEVEL | Maximum level of child gene ontology records | int | 1 | optional | Default: 1
# // Parameter: WITH_OBSOLETE | Include obsolete records | boolean | false | optional 
# // Return: List of Gene ontology record that are children of the given gene ontology record
# // Ecosystem: Proteomics:gene ontology
# // Example: php biorels_api.php get_child_gene_ontology -AC 'GO:0010389'
# // Example: php biorels_api.php get_child_gene_ontology -AC 'GO:0010389' -MAX_LEVEL 2
# // $[/API]
function get_child_gene_ontology($AC, $MAX_LEVEL=1,$WITH_OBSOLETE=false)
{

	$RELS=array();
	$LIST_DONE=array();
	$res=runQuery("SELECT * FROM go_entry where AC='".$AC."'");
	foreach ($res as $line)
	{
		$RELS[0][$line['ac']]=$line;
		$LIST_DONE[]=$AC;
	}
	
	
	for ($I=1;$I<=$MAX_LEVEL;$I++)
	{
		$query="SELECT gr.rel_Type, gr.subrel_type,gp.* FROM go_entry gp, go_rel gr, go_entry gc
			WHERE gp.go_entry_id = gr.go_from_id
			and gc.go_entry_id = gr.go_to_id
			AND gc.AC IN ('".implode("','",array_keys($RELS[$I-1]))."')";
			if (!$WITH_OBSOLETE)$query.=" AND gp.is_obsolete='F'";	
			
		$res=runQuery($query);
		foreach ($res as $line)
		{
			if (in_array($line['ac'],$LIST_DONE))continue;
			$RELS[$I][$line['ac']]=$line;
			$LIST_DONE[]=$line['ac'];
		}
		if (!isset($RELS[$I]) || $RELS[$I]==array())break;
	}
	
	return $RELS;
}



# // $[API]
# // Title: Get all parent gene ontology records for a gene ontology record
# // Function: get_parent_gene_ontology
# // Description: Get all parent gene ontology records for a gene ontology record
# // Parameter: AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: MAX_LEVEL | Maximum level of parent gene ontology records | int | 1 | optional | Default: 1
# // Parameter: WITH_OBSOLETE | Include obsolete records | boolean | false | optional 
# // Return: List of Gene ontology record that are parent of the given gene ontology record
# // Ecosystem: Proteomics:gene ontology
# // Example: php biorels_api.php get_parent_gene_ontology -AC 'GO:0010389'
# // Example: php biorels_api.php get_parent_gene_ontology -AC 'GO:0010389' -MAX_LEVEL 2
# // $[/API]
function get_parent_gene_ontology($AC, $MAX_LEVEL=1,$WITH_OBSOLETE=false)
{

	$RELS=array();
	$LIST_DONE=array();
	$res=runQuery("SELECT * FROM go_entry where AC='".$AC."'");
	foreach ($res as $line)
	{
		$RELS[0][$line['ac']]=$line;
		$LIST_DONE[]=$AC;
	}
	
	
	for ($I=1;$I<=$MAX_LEVEL;$I++)
	{
		$query="SELECT gr.rel_Type, gr.subrel_type,gc.* FROM go_entry gp, go_rel gr, go_entry gc
			WHERE gp.go_entry_id = gr.go_from_id
			and gc.go_entry_id = gr.go_to_id
			AND gp.AC IN ('".implode("','",array_keys($RELS[$I-1]))."')";
			if (!$WITH_OBSOLETE)$query.=" AND gc.is_obsolete='F'";	
			
		$res=runQuery($query);
		foreach ($res as $line)
		{
			if (in_array($line['ac'],$LIST_DONE))continue;
			$RELS[$I][$line['ac']]=$line;
			$LIST_DONE[]=$line['ac'];
		}
		if (!isset($RELS[$I]) || $RELS[$I]==array())break;
	}
	
	return $RELS;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// PROTEIN - GENE ONTOLOGY  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get gene ontology by protein identifier
# // Function: get_gene_onto_by_protein
# // Description: Get the gene ontology for a protein by using its identifier
# // Parameter: PROT_IDENTIFIER | Protein identifier | string | CDK2_HUMAN | required
# // Return: Gene ontology record
# // Ecosystem: Proteomics:protein|gene ontology
# // Example: php biorels_api.php get_gene_onto_by_protein -PROT_IDENTIFIER 'CDK2_HUMAN'
# // $[/API]
function get_gene_onto_by_protein($PROT_IDENTIFIER)
{
	$query="SELECT g.*, pgm.evidence, source_name FROM go_entry g, prot_entry PE, prot_go_map PGM,source S
	WHERE pe.prot_entry_Id = pgm.prot_entry_id 
	AND g.GO_entry_ID = pgm.GO_entry_ID
	AND pgm.source_id = s.source_id
	AND  prot_identifier='".$PROT_IDENTIFIER."'";
	return runQuery($query);
}

# // $[API]
# // Title: Search protein records by gene ontology accession
# // Function: search_protein_by_gene_onto_ac
# // Description: Search for a protein by using its gene ontology accession
# // Parameter: GO_AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional
# // Return: Protein record
# // Ecosystem: Proteomics:protein|gene ontology
# // Example: php biorels_api.php search_protein_by_gene_onto_ac -GO_AC 'GO:0010389'
# // Example: php biorels_api.php search_protein_by_gene_onto_ac -GO_AC 'GO:0010389' -TAX_ID 9606
# // $[/API]
function search_protein_by_gene_onto_ac($GO_AC,$TAX_ID='')
{
	$query="SELECT pe.*, pgm.evidence
	 FROM prot_entry PE, prot_go_map PGM, go_entry GE, taxon t
	WHERE pe.prot_entry_Id = pgm.prot_entry_id
	AND t.taxon_id = pe.taxon_id
	AND GE.GO_entry_ID = PGM.GO_entry_ID
	AND AC='".$GO_AC."'";
	if ($TAX_ID!='')$query.=" AND tax_id='".$TAX_ID."'";
	return runQuery($query);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// GENE - GENE ONTOLOGY  //////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Get gene ontology by gene_id
# // Function: get_gene_onto_by_gene_id
# // Description: Get the gene ontology for a gene by using its NCBI gene identifier
# // Parameter: GENE_ID | NCBI Gene ID | int | 1017 | required
# // Return: Gene ontology record
# // Ecosystem: Proteomics:gene ontology;Genomics:gene
# // Example: php biorels_api.php get_gene_onto_by_gene_id -GENE_ID 1017
# // $[/API]
function get_gene_onto_by_gene_id($GENE_ID)
{
	$query="SELECT DISTINCT g.*, pgm.evidence, source_name
	FROM go_entry g, prot_entry PE, prot_go_map PGM,source S, gn_prot_map GPM, gn_entry GE
	WHERE pe.prot_entry_Id = pgm.prot_entry_id 
	AND ge.gn_entry_id = gpm.gn_entry_id
	AND gpm.prot_entry_id = pe.prot_entry_Id
	AND g.GO_entry_ID = pgm.GO_entry_ID
	AND pgm.source_id = s.source_id
	AND gene_id=".$GENE_ID;
	return runQuery($query);
}

# // $[API]
# // Title: Search gene records by gene ontology accession
# // Function: search_gene_by_gene_onto_ac
# // Description: Search for a gene by using its gene ontology accession
# // Parameter: GO_AC | Gene ontology accession | string | GO:0010389 | required
# // Parameter: TAX_ID | Taxonomic Identifier of the organism | string | 9606 | optional
# // Return: Gene record
# // Ecosystem: Proteomics:gene ontology;Genomics:gene
# // Example: php biorels_api.php search_gene_by_gene_onto_ac -GO_AC 'GO:0010389'
# // Example: php biorels_api.php search_gene_by_gene_onto_ac -GO_AC 'GO:0010389' -TAX_ID 9606
# // $[/API]
function search_gene_by_gene_onto_ac($GO_AC,$TAX_ID='')
{
	$query="SELECT DISTINCT ge.*, pgm.evidence, source_name, tax_id
	FROM go_entry g, prot_entry PE, prot_go_map PGM,source S, gn_prot_map GPM, gn_entry GE, taxon t
	WHERE pe.prot_entry_Id = pgm.prot_entry_id 
	AND ge.gn_entry_id = gpm.gn_entry_id
	AND gpm.prot_entry_id = pe.prot_entry_Id
	AND g.GO_entry_ID = pgm.GO_entry_ID
	AND pgm.source_id = s.source_id
	AND t.taxon_id = pe.taxon_id
	AND AC='".$GO_AC."'";
	if ($TAX_ID!='')$query.=" AND tax_id='".$TAX_ID."'";
	return runQuery($query);
}






///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////  MOLECULAR ENTITY ECOSYSTEM  //////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Search small molecule by name
# // Function: search_small_molecule_by_name
# // Description: Search for a small molecule by using its name
# // Parameter: NAME | Small molecule name | string | ATP | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php search_small_molecule_by_name -NAME 'ATP'
# // $[/API]
function search_small_molecule_by_name($NAME)
{
	$query="SELECT source_name, sm_name,inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, scaffold_smiles
	 FROM source s, sm_source ss, sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND ss.sm_entry_id = se.sm_entry_id
    AND s.source_id = ss.source_id
	AND sm_name='".$NAME."'";
	return runQuery($query);
}

# // $[API]
# // Title: Search small molecule by inchi
# // Function: search_small_molecule_by_inchi_key
# // Description: Search for a small molecule by using its InChI, values are separated by a comma
# // Parameter: INCHI_KEYs | Small molecule InChI KEY | array | ZKHQWZAMYRWXGA-KQYNXXCUSA-N | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php search_small_molecule_by_inchi_key -INCHI_KEYs 'ZKHQWZAMYRWXGA-KQYNXXCUSA-N'
# // $[/API]
function search_small_molecule_by_inchi_key($INCHI_KEYs)
{
	if ($INCHI_KEYs==array()) return array();
	$query="SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND inchi_key IN ('".implode("','",$INCHI_KEYs)."')";
	$data= runQuery($query);
	return $data;
}


# // $[API]
# // Title: Search small molecule by full smiles
# // Function: search_small_molecule_by_full_smiles
# // Description: Search for a small molecule by using its smiles string, including counterions if any
# // Parameter: SMILES | Small molecule SMILES | array | Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O | required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php search_small_molecule_by_full_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O'
# // $[/API]
function search_small_molecule_by_full_smiles($FULL_SMILES)
{
	if ($FULL_SMILES==array()) return array();
	$query="SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND full_smiles IN ('".implode("','",$FULL_SMILES)."')";
	$data= runQuery($query);
	return $data;
}




# // $[API]
# // Title: Search small molecule by smiles
# // Function: search_small_molecule_by_smiles
# // Description: Search for a small molecule by using its smiles string - without counterions
# // Parameter: SMILES | Small molecule SMILES | array | Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O | required
# // Parameter: WITHOUT_COUNTERION | True if only molecule without counterion requested | boolean | false | optional
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php search_small_molecule_by_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O'
# // Example: php biorels_api.php search_small_molecule_by_smiles -SMILES 'Nc1[n]c[n]c2[n](c[n]c12)[C@@H]1O[C@H](COP(=O)(OP(=O)(OP(=O)(O)O)O)O)[C@H]([C@H]1O)O' -WITHOUT_COUNTERION true
# // $[/API]
function search_small_molecule_by_smiles($FULL_SMILES,$WITHOUT_COUNTERION=false)
{
	if ($FULL_SMILES==array()) return array();
	$query="SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND smiles IN ('".implode("','",$FULL_SMILES)."')";
	if ($WITHOUT_COUNTERION) $query.= ' AND se.sm_counterion_id IS NULL';
	$data= runQuery($query);
	return $data;
}



# // $[API]
# // Title: Search small molecule by scaffold smiles
# // Function: search_small_molecule_by_Scaffold
# // Description: Search for a small molecule by using its smiles string - without counterions
# // Parameter: SCAFFOLD_SMILES | Small molecule scaffold as SMILES | array | c1ccccc1 |required
# // Return: Small molecule record
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php search_small_molecule_by_Scaffold -SCAFFOLD_SMILES 'c1ccccc1'
# // $[/API]
function search_small_molecule_by_Scaffold($SCAFFOLD_SMILES)
{
	if ($SCAFFOLD_SMILES==array()) return array();
	$query="SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND SCAFFOLD_SMILES IN ('".implode("','",$SCAFFOLD_SMILES)."')";
	$data= runQuery($query);
	return $data;	
}



# // $[API]
# // Title: Get small molecule information
# // Function: get_small_molecule
# // Description: Get the small molecule information by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Parameter: COMPLETE | True if all information is requested | boolean | false | optional
# // Return: Small molecule record with names, patents, descriptions, counterions and scaffolds
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
function get_small_molecule($MD5_HASH,$COMPLETE=false)
{
	$query="SELECT inchi,inchi_key, full_smiles, smiles, md5_hash, se.is_valid, counterion_smiles, scaffold_smiles
	 FROM sm_entry se 
	 LEFT JOIN sm_counterion sc ON sc.sm_counterion_id = se.sm_counterion_id, sm_molecule sm 
	 LEFT JOIN sm_scaffold sca ON sca.sm_scaffold_id = sm.sm_Scaffold_id
	where  se.sm_molecule_id = sm.sm_molecule_id
	AND md5_hash='".$MD5_HASH."'";
	$data= runQuery($query);
	if (!$COMPLETE) return $data;
	foreach ($data as &$line)
	{
		$line['NAMES']=get_small_molecule_names($line['md5_hash']);
		$line['PATENT']=get_small_molecule_patent($line['md5_hash']);
		$line['DESCRIPTION']=get_small_molecule_description($line['md5_hash']);
		
	}
	return $data;

}


# // $[API]
# // Title: Get small molecule names
# // Function: get_small_molecule_names
# // Description: Get the small molecule names by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule names
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule_names -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
function get_small_molecule_names($MD5_HASH)
{
	$query='SELECT sm_name,source_name 
	FROM sm_source sn, source s, sm_entry se
	 WHERE sn.source_id = s.source_id 
	 AND sn.sm_entry_id = se.sm_entry_id
	 AND md5_hash=\''.$MD5_HASH.'\'';
	return runQuery($query);
}


# // $[API]
# // Title: Get small molecule patent
# // Function: get_small_molecule_patent
# // Description: Get the small molecule patent by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule patent
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule_patent -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]

function get_small_molecule_patent($MD5_HASH)
{
	$query='SELECT patent_application 
	FROM sm_patent_map sp, patent_entry p, sm_entry se
	WHERE sp.patent_entry_id = p.patent_entry_id
	AND sp.sm_entry_id = se.sm_entry_id
	AND md5_hash=\''.$MD5_HASH.'\'';
	return runQuery($query);
}


# // $[API]
# // Title: Get small molecule description
# // Function: get_small_molecule_description
# // Description: Get the small molecule description by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule description
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule_description -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
function get_small_molecule_description($MD5_HASH)
{
	$query='SELECT description_text,description_type,source_name 
	FROM sm_description sd, sm_entry se, source s
	WHERE sd.sm_entry_id = se.sm_entry_id
	AND sd.source_id = s.source_id
	AND md5_hash=\''.$MD5_HASH.'\'';
	return runQuery($query);
}


# // $[API]
# // Title: Get small molecule counterion
# // Function: get_small_molecule_counterion
# // Description: Get the small molecule counterion by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule counterion
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule_counterion -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]

function get_small_molecule_counterion($MD5_HASH)
{
	$query='SELECT counterion_smiles
	FROM sm_counterion sc, sm_entry se
	WHERE sc.sm_counterion_id = se.sm_counterion_id
	AND md5_hash=\''.$MD5_HASH.'\'';
	return runQuery($query);
}


# // $[API]
# // Title: Get small molecule scaffold
# // Function: get_small_molecule_scaffold
# // Description: Get the small molecule scaffold by using its MD5 hash
# // Parameter: MD5_HASH | Small molecule MD5 hash | string | 6a561fabdd49ff7e4298d0cea562f2c6 | required
# // Return: Small molecule scaffold
# // Ecosystem: Molecular entity:small molecule
# // Example: php biorels_api.php get_small_molecule_scaffold -MD5_HASH '6a561fabdd49ff7e4298d0cea562f2c6'
# // $[/API]
function get_small_molecule_scaffold($MD5_HASH)
{
	$query='SELECT scaffold_smiles 
	FROM sm_scaffold sc, sm_entry se, sm_molecule sm
	WHERE sc.sm_scaffold_id = sm.sm_scaffold_id
	AND sm.sm_molecule_id = se.sm_molecule_id
	AND md5_hash=\''.$MD5_HASH.'\'';
	return runQuery($query);
}



# // $[API]
# // Title: Get molecular entity information
# // Function: get_molecular_entity
# // Description: Get the molecular entity information with its component by using its hash
# // Parameter: MOLECULAR_ENTITY_HASH | Molecular entity hash | string | 962fb0e3e47bc03f831ebe9b759d027e | required
# // Return: Molecular entity record with components, small molecules, conjugates and nucleic acids
# // Ecosystem: Molecular entity:molecular entity
# // Example: php biorels_api.php get_molecular_entity -MOLECULAR_ENTITY_HASH '962fb0e3e47bc03f831ebe9b759d027e'
# // $[/API]
function get_molecular_entity($MOLECULAR_ENTITY_HASH)
{
	$query="SELECT * FROM molecular_entity WHERE molecular_entity_hash='".$MOLECULAR_ENTITY_HASH."'";
	$data= runQuery($query);
	foreach ($data as &$line)
	{
		$query='SELECT mc.molecular_component_id, molecular_component_hash, molecular_component_structure_hash, molecular_component_structure, components, molar_fraction 
		FROM molecular_component mc, molecular_entity_component_map mecm
		WHERE mc.molecular_component_id = mecm.molecular_component_id
		AND mecm.molecular_entity_id='.$line['molecular_entity_id'];
		$line['COMPONENTS']=runQuery($query);
		foreach ($line['COMPONENTS'] as &$comp)
		{
			$query='SELECT md5_hash, molar_fraction,compound_type
			 FROM molecular_Component_sm_map cs,sm_entry se
			WHERE se.sm_entry_id = cs.sm_entry_id
			AND molecular_component_id='.$comp['molecular_component_id'];
			$res2=runQuery($query);
			foreach ($res2 as &$line2)
			{
				$sm_dt=get_small_molecule($line2['md5_hash'],false);
				$sm_dt['molar_fraction']=$line2['molar_fraction'];
				$sm_dt['compound_type']=$line2['compound_type'];
				$comp['SM'][]=$sm_dt;
			}

			$query='SELECT * FROM conjugate_entry ce, molecular_component_conj_map cm
			WHERE ce.conjugate_entry_id = cm.conjugate_entry_id
			AND cm.molecular_component_id='.$comp['molecular_component_id'];
			$res2=runQuery($query);
			foreach ($res2 as &$line2)
			{
				$comp['CONJUGATE'][]=$line2;
			}

			$query='SELECT helm_hash FROM molecular_component_na_map mcna, nucleic_acid_seq nas
			WHERE mcna.nucleic_acid_seq_id = nas.nucleic_acid_seq_id
			AND mcna.molecular_component_id='.$comp['molecular_component_id'];
			$res2=runQuery($query);
			foreach ($res2 as &$line2)
			{
				$comp['NA'][]=get_nucleic_acid_seq($line2['helm_hash']);
			}

		}
	}
	return $data;

}




# // $[API]
# // Title: Get nucleic acid sequence
# // Function: get_nucleic_acid_seq
# // Description: Get the nucleic acid sequence by using its hash
# // Parameter: HELM_HASH | Nucleic acid HELM hash | string | 962fb0e3e47bc03f831ebe9b759d027e | required
# // Return: Nucleic acid sequence record with modifications
# // Ecosystem: Molecular entity:nucleic acid
# // Example: php biorels_api.php get_nucleic_acid_seq -HELM_HASH '962fb0e3e47bc03f831ebe9b759d027e'
# // $[/API]
function get_nucleic_acid_seq($HELM_HASH)
{
	$query="SELECT * FROM nucleic_acid_seq WHERE helm_hash='".$HELM_HASH."'";
	$data= runQuery($query);
	foreach ($data as &$line)
	{
		if ($line['mod_pattern_id']!='')
		{
		$query='SELECT * FROM mod_pattern mp, mod_pattern_pos mpp
		where mp.mod_pattern_id = mpp.mod_pattern_id
		mp.mod_pattern_id='.$line['mod_pattern_id'];
		$line['MOD_PATTERN']=runQuery($query);
		}
	}
	return $data;
}






///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////  DISEASE/ANATOMY ECOSYSTEM  //////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////  DISEASE  ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get disease information
# // Function: get_disease_information
# // Description: Get the disease information by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Return: Disease record with synonyms and external database references
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php get_disease_information -TAG 'MONDO_0005087'
# // $[/API]
function get_disease_information($TAG)
{
	$query="SELECT * FROM disease_entry WHERE disease_tag='".$TAG."'";
	$data= runQuery($query);
	if ($data==array()) return array();
	foreach ($data as &$line)
	{
		$line['SYNONYMS']=runQuery("SELECT syn_type,syn_value,source_name FROM disease_syn ds,source s WHERE s.source_id = ds.source_id AND disease_entry_id=".$line['disease_entry_id']);
		$line['EXT_DB']=runQuery("SELECT disease_extdb, source_name FROM disease_extdb de, source s WHERE de.source_id = s.source_id AND disease_entry_id=".$line['disease_entry_id']);
		$line['CHILDREN']=get_child_disease($TAG);
		$line['PARENTS']=get_parent_disease($TAG);
	}
	return $data;
}



# // $[API]
# // Title: Search disease by name
# // Function: search_disease_by_name
# // Description: Search for a disease by using its name
# // Parameter: NAME | Disease name | string | Cancer | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php search_disease_by_name -NAME 'Cancer'
# // $[/API]
function search_disease_by_name($NAME)
{
	$query="SELECT * FROM disease_entry WHERE LOWER(disease_name)=LOWER('".$NAME."')";
	$res= runQuery($query);
	if ($res!=array())return $res;
	$query="SELECT * FROM disease_syn ds, disease_entry de WHERE ds.disease_entry_id = de.disease_entry_id AND LOWER(syn_value)=LOWER('".$NAME."')";
	$res= runQuery($query);
	if ($res!=array())return $res;
	$query="SELECT * FROM disease_entry WHERE disease_name LIKE '%".$NAME."%'";
	$res= runQuery($query);
	if ($res!=array())return $res;
	$query = "SELECT * FROM disease_syn ds, disease_entry de WHERE ds.disease_entry_id = de.disease_entry_id AND syn_value LIKE '%".$NAME."%'";
	$res= runQuery($query);
	if ($res!=array())return $res;
	$tab=explode(" ",$NAME);
	$list=array();
	foreach ($tab as $rec)
	{
		$res=runQuery("SELECT disease_entry_id FROM disease_entry WHERE disease_name LIKE '%".$rec."%'");
		foreach ($res as $line)
		{
			if (!isset($list[$line['disease_entry_id']]))$list[$line['disease_entry_id']]=0;
			++$list[$line['disease_entry_id']];
		}
	}
	$max=0;
	foreach ($list as $line)if ($line>$max)$max=$line;
	$tab=array();
	foreach ($list as $key=>$line)if ($line==$max)$tab[]=$key;
	if ($tab==array())return array();
	$query="SELECT * FROM disease_entry WHERE disease_entry_id IN (".implode(",",$tab).")";
	return runQuery($query);


}



# // $[API]
# // Title: Search disease by tag
# // Function: search_disease_by_tag
# // Description: Search for a disease by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php search_disease_by_tag -TAG 'MONDO_0005087'
# // $[/API]
function search_disease_by_tag($TAG)
{
	$query="SELECT * FROM disease_entry WHERE disease_tag LIKE '%".$TAG."%'";
	return runQuery($query);
}


# // $[API]
# // Title: Search disease by synonym
# // Function: search_disease_by_synonym
# // Description: Search for a disease by using its synonym
# // Parameter: SYNONYM | Disease synonym | string | Cancer | required
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php search_disease_by_synonym -SYNONYM 'Cancer'
# // $[/API]
function search_disease_by_synonym($SYNONYM)
{
	$query="SELECT * FROM disease_entry de, disease_syn ds
	WHERE ds.disease_entry_id = de.disease_entry_id
	 AND syn_value LIKE '%".$SYNONYM."%'";
	return runQuery($query);
}


# // $[API]
# // Title: Search disease by identifier
# // Function: search_disease_by_identifier
# // Description: Search for a disease by using its identifier
# // Parameter: ID | Disease identifier | string | MONDO_0005087 | required
# // Parameter: SOURCE | Source of the identifier | string | MONDO | optional
# // Return: Disease record
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php search_disease_by_identifier -ID 'MONDO_0005087' -SOURCE 'MONDO'
# // $[/API]
function search_disease_by_identifier($ID,$SOURCE=null)
{
	$query="SELECT * FROM disease_entry de, disease_extdb ds,source s
	WHERE ds.disease_entry_id = de.disease_entry_id
	AND ds.source_id = s.source_id
	 AND (( ds.disease_extdb = '".$ID."'";
	 if ($SOURCE!=null)$query.=" AND LOWER(s.source_name)=LOWER('".$SOURCE."')";
	$query.=") OR disease_tag = '".$ID."')";
	return runQuery($query);
}



# // $[API]
# // Title: Get all diseases that are children of a given disease
# // Function: get_child_disease
# // Description: Get all diseases that are children of a given disease
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: MAX_DEPTH | Maximum depth of child diseases | int | 1 | optional | Default: 1
# // Return: List of disease records that are children of the given disease
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php get_child_disease -TAG 'MONDO_0005087'
# // $[/API]
function get_child_disease($TAG,$MAX_DEPTH=1)
{
	return runQuery("SELECT dh2.disease_level, de2.disease_tag, de2.disease_name, de2.disease_definition, dh2.disease_level_left, dh2.disease_level_Right
	FROM disease_entry de,
	disease_hierarchy dh1, 
	disease_hierarchy dh2,
	disease_entry de2
	WHERE 
	de.disease_entry_id = dh1.disease_entry_id
	AND dh1.disease_level_left < dh2.disease_level_left
	AND dh1.disease_level_right > dh2.disease_level_right
	AND de2.disease_entry_id = dh2.disease_entry_id
	AND de.disease_tag='".$TAG."' 
	AND dh2.disease_level <= dh1.disease_level+".$MAX_DEPTH."
	ORDER BY dh2.disease_level ASC");
}


# // $[API]
# // Title: Get all diseases that are parent of a given disease
# // Function: get_parent_disease
# // Description: Get all diseases that are parent of a given disease
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: MAX_DEPTH | Maximum depth of parent diseases | int | 1 | optional | Default: 1
# // Return: List of disease records that are parent of the given disease
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php get_parent_disease -TAG 'MONDO_0005087'
# // $[/API]
function get_parent_disease($TAG,$MAX_DEPTH=1)
{
	return runQuery("SELECT dh2.disease_level, de2.disease_tag, de2.disease_name, de2.disease_definition, dh2.disease_level_left, dh2.disease_level_Right
	FROM disease_entry de,
	disease_hierarchy dh1, 
	disease_hierarchy dh2,
	disease_entry de2
	WHERE 
	de.disease_entry_id = dh1.disease_entry_id
	AND dh1.disease_level_left > dh2.disease_level_left
	AND dh1.disease_level_right < dh2.disease_level_right
	AND de2.disease_entry_id = dh2.disease_entry_id
	AND de.disease_tag='".$TAG."' 
	AND dh2.disease_level >= dh1.disease_level-".$MAX_DEPTH."
	ORDER BY dh2.disease_level ASC");
}



# // $[API]
# // Title: get disease information
# // Function: get_disease_info
# // Description: Get the disease information by using its tag
# // Parameter: TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: SOURCE_NAME | Source of the disease information | string | MONDO | optional
# // Return: Textual information about the disease
# // Ecosystem: Disease_anatomy:disease
# // Example: php biorels_api.php get_disease_info -TAG 'MONDO_0005087'
# // $[/API]
function get_disease_info($TAG,$SOURCE_NAME='')
{
	$query="SELECT di.*, source_name
	FROM disease_entry de, disease_info di, source s
	WHERE de.disease_entry_Id = di.disease_entry_id 
	AND di.source_id = s.source_id
	AND de.disease_tag='".$TAG."'";
	if ($SOURCE_NAME!='')$query.=" AND LOWER(s.source_name)=LOWER('".$SOURCE_NAME."')";
	return runQuery($query);
}



///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////  ANATOMY  ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: Get anatomy information
# // Function: get_anatomy_information
# // Description: Get the anatomy information by using its tag
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: COMPLETE | True if all information is requested | boolean | false | optional
# // Return: anatomy record with synonyms and external database references
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php get_anatomy_information -TAG 'UBERON_0000955'
# // $[/API]
function get_anatomy_information($TAG,$COMPLETE=false)
{
	$query="SELECT * FROM anatomy_entry WHERE anatomy_tag='".$TAG."'";
	$data= runQuery($query);
	if ($data==array()) return array();
	if (!$COMPLETE)return $data;
	foreach ($data as &$line)
	{
		$line['SYNONYMS']=runQuery("SELECT syn_type,syn_value,source_name FROM anatomy_syn ds,source s WHERE s.source_id = ds.source_id AND anatomy_entry_id=".$line['anatomy_entry_id']);
		$line['EXT_DB']=runQuery("SELECT anatomy_extdb, source_name FROM anatomy_extdb de, source s WHERE de.source_id = s.source_id AND anatomy_entry_id=".$line['anatomy_entry_id']);
		$line['CHILDREN']=get_child_anatomy($TAG);
		$line['PARENTS']=get_parent_anatomy($TAG,10);
	}
	return $data;
}



# // $[API]
# // Title: Search anatomy by name
# // Function: search_anatomy_by_name
# // Description: Search for a anatomy by using its name
# // Parameter: NAME | anatomy name | string | Brain | required
# // Parameter: IS_EXACT | True if exact match is required | boolean | true | optional | Default: true
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php search_anatomy_by_name -NAME 'Brain'
# // $[/API]
function search_anatomy_by_name($NAME, $IS_EXACT=true)
{
	$query="SELECT * FROM anatomy_entry WHERE ".($IS_EXACT?"LOWER(anatomy_name)=LOWER('".$NAME."')":"LOWER(anatomy_name) LIKE '%".strtolower($NAME)."%'");
	return runQuery($query);
}



# // $[API]
# // Title: Search anatomy by tag
# // Function: search_anatomy_by_tag
# // Description: Search for a anatomy by using its tag
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php search_anatomy_by_tag -TAG 'UBERON_0000955'
# // $[/API]
function search_anatomy_by_tag($TAG)
{
	$query="SELECT * FROM anatomy_entry WHERE anatomy_tag LIKE '%".$TAG."%'";
	return runQuery($query);
}


# // $[API]
# // Title: Search anatomy by synonym
# // Function: search_anatomy_by_synonym
# // Description: Search for a anatomy by using its synonym
# // Parameter: SYNONYM | anatomy synonym | string | Brain | required
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php search_anatomy_by_synonym -SYNONYM 'Brain'
# // $[/API]
function search_anatomy_by_synonym($SYNONYM)
{
	$query="SELECT * FROM anatomy_entry de, anatomy_syn ds
	WHERE ds.anatomy_entry_id = de.anatomy_entry_id
	 AND LOWER(syn_value) LIKE LOWER('%".$SYNONYM."%')";
	return runQuery($query);
}


# // $[API]
# // Title: Search anatomy by identifier
# // Function: search_anatomy_by_identifier
# // Description: Search for a anatomy by using its identifier
# // Parameter: ID | anatomy identifier | string | 0000955 | required
# // Parameter: SOURCE | Source of the identifier | string | UBERON | optional
# // Return: anatomy record
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php search_anatomy_by_identifier -ID 0000955 -SOURCE UBERON
# // $[/API]
function search_anatomy_by_identifier($ID,$SOURCE=null)
{
	$query="SELECT * FROM anatomy_entry de, anatomy_extdb ds,source s
	WHERE ds.anatomy_entry_id = de.anatomy_entry_id
	AND ds.source_id = s.source_id
	 AND ds.anatomy_extdb = '".$ID."'";
	 if ($SOURCE!=null)$query.=" AND LOWER(s.source_name)=LOWER('".$SOURCE."')";
	return runQuery($query);
}



# // $[API]
# // Title: Get all anatomys that are children of a given anatomy
# // Function: get_child_anatomy
# // Description: Get all anatomys that are children of a given anatomy
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: MAX_DEPTH | Maximum depth of child anatomys | int | 1 | optional | Default: 1
# // Return: List of anatomy records that are children of the given anatomy
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php get_child_anatomy -TAG UBERON_0000955
# // $[/API]
function get_child_anatomy($TAG,$MAX_DEPTH=1)
{
	return runQuery("SELECT dh2.anatomy_level, de2.anatomy_tag, de2.anatomy_name, de2.anatomy_definition, dh2.anatomy_level_left, dh2.anatomy_level_Right
	FROM anatomy_entry de,
	anatomy_hierarchy dh1, 
	anatomy_hierarchy dh2,
	anatomy_entry de2
	WHERE 
	de.anatomy_entry_id = dh1.anatomy_entry_id
	AND dh1.anatomy_level_left < dh2.anatomy_level_left
	AND dh1.anatomy_level_right > dh2.anatomy_level_right
	AND de2.anatomy_entry_id = dh2.anatomy_entry_id
	AND de.anatomy_tag='".$TAG."' 
	AND dh2.anatomy_level <= dh1.anatomy_level+".$MAX_DEPTH."
	ORDER BY dh2.anatomy_level ASC");
	
}


# // $[API]
# // Title: Get all anatomy records that are parent of a given anatomy
# // Function: get_parent_anatomy
# // Description: Get all anatomys that are parent of a given anatomy
# // Parameter: TAG | anatomy tag | string | UBERON_0000955 | required
# // Parameter: MAX_DEPTH | Maximum depth of parent anatomys | int | 1 | optional | Default: 1
# // Return: List of anatomy records that are parent of the given anatomy
# // Ecosystem: Disease_anatomy:anatomy
# // Example: php biorels_api.php get_parent_anatomy -TAG UBERON_0000955
# // $[/API]
function get_parent_anatomy($TAG,$MAX_DEPTH=1)
{
	return runQuery("SELECT dh2.anatomy_level, de2.anatomy_tag, de2.anatomy_name, de2.anatomy_definition, dh2.anatomy_level_left, dh2.anatomy_level_Right
	FROM anatomy_entry de,
	anatomy_hierarchy dh1, 
	anatomy_hierarchy dh2,
	anatomy_entry de2
	WHERE 
	de.anatomy_entry_id = dh1.anatomy_entry_id
	AND dh1.anatomy_level_left > dh2.anatomy_level_left
	AND dh1.anatomy_level_right < dh2.anatomy_level_right
	AND de2.anatomy_entry_id = dh2.anatomy_entry_id
	AND de.anatomy_tag='".$TAG."' 
	AND dh2.anatomy_level >= dh1.anatomy_level-".$MAX_DEPTH."
	ORDER BY dh2.anatomy_level ASC");
	
}




///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////  CELL LINE  ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: search cell line by disease
# // Function: search_cell_line_by_disease
# // Description: Search for a cell line by a disease or its child diseases
# // Parameter: DISEASE_TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: INCLUDE_CHILD_DISEASE | True if child diseases should be included | boolean | false | optional | Default: false
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line|disease
# // Example: php biorels_api.php search_cell_line_by_disease -DISEASE_TAG 'MONDO_0005087'
# // Example: php biorels_api.php search_cell_line_by_disease -DISEASE_TAG 'MONDO_0005087' -INCLUDE_CHILD_DISEASE true
# // $[/API]
function search_cell_line_by_disease($DISEASE_TAG,$INCLUDE_CHILD_DISEASE=false)
{
	
	$query="SELECT * FROM cell_entry ce, cell_disease cd, disease_entry de
	WHERE ce.cell_entry_id = cd.cell_entry_id
	AND de.disease_entry_id = cd.disease_entry_id ";
	if (!$INCLUDE_CHILD_DISEASE)
	$query.= " AND de.disease_tag='".$DISEASE_TAG."'";
	else 
	{
		$query.= " AND de.disease_entry_id IN (
			SELECT de2.disease_entry_id 
			FROM disease_hierarchy dh1, disease_Entry de1,
				 disease_hierarchy dh2, disease_entry de2
			WHERE de1.disease_entry_id = dh1.disease_entry_id
			AND de2.disease_entry_id = dh2.disease_entry_id
			AND dh1.disease_level_left < dh2.disease_level_left
			AND dh1.disease_level_right > dh2.disease_level_right
			AND de1.disease_tag='".$DISEASE_TAG."')";
	}
	return runQuery($query);
}


# // $[API]
# // Title: get all cell line information
# // Function: get_cell_info
# // Description: Get all information about a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if complete information is required | boolean | false | optional | Default: false
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line;Genomics:taxon
# // Example: php biorels_api.php get_cell_info -ACC 'CVCL_B6YM'
# // $[/API]
function get_cell_info($ACC,$COMPLETE=false)
{
	$res=runQuery("SELECT * FROM cell_entry where cell_acc='".$ACC."'");
	if ($res==array()) return array();
	$data=array();
	foreach ($res as $line)
	{
		$line['SYNONYMS']=get_cell_line_synonyms($ACC);
		$line['TAXONOMY']=get_cell_line_taxon($ACC);
		$line['DISEASE']=get_cell_line_disease($ACC,$COMPLETE);
		$line['TISSUE']=get_cell_line_tissue($ACC,$COMPLETE);
		$line['PATENT']=get_cell_line_patent($ACC);

		
		$data[]=$line;
	}
	return $data;
}

# // $[API]
# // Title: get cell line synonyms
# // Function: get_cell_line_synonyms
# // Description: Get all synonyms of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: List of synonyms
# // Ecosystem: Disease_anatomy:cell line
# // Example: php biorels_api.php get_cell_line_synonyms -ACC 'CVCL_B6YM'
# // $[/API]
function get_cell_line_synonyms($ACC)
{
	$res=runQuery("SELECT cell_syn_name, source_name FROM cell_syn cs, source s WHERE s.source_id = cs.source_id AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='".$ACC."')");
	return $res;

}



# // $[API]
# // Title: get disease information of a cell line
# // Function: get_cell_line_disease
# // Description: Get all diseases of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if extended disease information is requested | boolean | false | optional | Default: false
# // Return: List of diseases
# // Ecosystem: Disease_anatomy:cell line|disease
# // Example: php biorels_api.php get_cell_line_disease -ACC 'CVCL_B6YM'
# // Example: php biorels_api.php get_cell_line_disease -ACC 'CVCL_B6YM' -COMPLETE true
# // $[/API]
function get_cell_line_disease($ACC,$COMPLETE=false)
{
	$res=runQuery("SELECT disease_tag, disease_name, source_name 
	FROM disease_entry de, cell_disease cd, source s  
	WHERE s.source_id = cd.source_id 
	AND de.disease_entry_id = cd.disease_entry_id 
	AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='".$ACC."')");
	if ($COMPLETE)
	foreach ($res as &$line)
	{
		$line['INFO']=get_disease_information($line['disease_tag']);
	}
	return $res;

}



# // $[API]
# // Title: get tissue information of a cell line
# // Function: get_cell_line_tissue
# // Description: Get all tissues of a cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Parameter: COMPLETE | True if extended tissue information is requested | boolean | false | optional | Default: false
# // Return: List of tissues
# // Ecosystem: Disease_anatomy:cell line|anatomy
# // Example: php biorels_api.php get_cell_line_tissue -ACC 'CVCL_B6YM'
# // Example: php biorels_api.php get_cell_line_tissue -ACC 'CVCL_B6YM' -COMPLETE true
# // $[/API]
function get_cell_line_tissue($ACC,$COMPLETE=false)
{
	$res=runQuery("SELECT anatomy_tag, anatomy_name
	FROM anatomy_entry te, cell_tissue ct
	WHERE te.anatomy_entry_id = ct.anatomy_entry_id 
	AND cell_tissue_id IN (SELECT cell_tissue_id FROM cell_entry WHERE cell_acc='".$ACC."')");
	if ($COMPLETE)
	foreach ($res as &$line)
	{
		$line['INFO']=get_tissue_information($line['anatomy_tag']);
	}

}


# // $[API]
# // Title: get organism information for a given cell line
# // Function: get_cell_line_taxon
# // Description: Get the organism information for a given cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: Organism information
# // Ecosystem: Disease_anatomy:cell line;Genomics:taxon
# // Example: php biorels_api.php get_cell_line_taxon -ACC 'CVCL_B6YM'
# // $[/API]
function get_cell_line_taxon($ACC)
{
	$res=runQuery("SELECT tax_id, scientific_name,source_name FROM taxon t, cell_Taxon_map ctm, source s WHERE s.sourcE_id = ctm.source_id AND t.taxon_id = ctm.taxon_id AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='".$ACC."')");
	return $res;
}


# // $[API]
# // Title: get patent information for a given cell line
# // Function: get_cell_line_patent
# // Description: Get the patent information for a given cell line
# // Parameter: ACC | Cell line accession | string | CVCL_B6YM | required
# // Return: Patent information
# // Ecosystem: Disease_anatomy:cell line;Scientific_community:patent
# // Example: php biorels_api.php get_cell_line_patent -ACC 'CVCL_B6YM'
# // $[/API]
function get_cell_line_patent($ACC)
{
	$res=runQuery("SELECT source_name, patent_application FROM patent_entry p, cell_patent_map cp, source s WHERE s.source_id = cp.source_id AND p.patent_entry_id = cp.patent_entry_id AND cell_entry_id IN (SELECT cell_entry_id FROM cell_entry WHERE cell_acc='".$ACC."')");
	return $res;

}


# // $[API]
# // Title: list cell line types
# // Function: list_cell_line_type
# // Description: List the different types of cell lines
# // Return: List of cell line types
# // Ecosystem: Disease_anatomy:cell line
# // Example: php biorels_api.php list_cell_line_type
# // $[/API]
function list_cell_line_type()
{
	$res=runQuery("SELECT count(*) n_cell_line, cell_type 
		FROM cell_entry
		group by cell_type 
		order by n_cell_line DESC");
	
	return $res;
}


# // $[API]
# // Title: Count the number of cell lines per organism
# // Function: list_cell_line_taxon
# // Description: Count the number of cell lines per organism
# // Return: Count, scientific name, tax id
# // Ecosystem: Disease_anatomy:cell line;Genomics:taxon
# // Example: php biorels_api.php list_cell_line_taxon
# // $[/API]
function list_cell_line_taxon()
{
	$res=runQuery("SELECT count(*) n_cell_line, scientific_name, tax_id FROM taxon t, cell_Taxon_map ctm WHERE t.taxon_id = ctm.taxon_id group by scientific_name ,tax_id order by n_cell_line DESC");
	
	return $res;
}



# // $[API]
# // Title: Count the number of cell lines per tissue
# // Function: list_cell_line_tissue
# // Description: Count the number of cell lines per tissue
# // Return: Count, tissue name, tissue tag
# // Ecosystem: Disease_anatomy:cell line|anatomy
# // Example: php biorels_api.php list_cell_line_tissue
# // $[/API]
function list_cell_line_tissue()
{
	$res=runQuery("SELECT count(*) n_cell_line, anatomy_name, anatomy_tag 
	FROM anatomy_entry te, cell_tissue ct 
	WHERE te.anatomy_entry_id = ct.anatomy_entry_id 
	group by anatomy_name, anatomy_tag
	order by n_cell_line DESC");
	
	return $res;
}

# // $[API]
# // Title: Search cell line by different parameters
# // Function: search_cell_line
# // Description: Search for a cell line by using different parameters. Use $PARAMS=array('NAME'=>array(),'SYN'=>array(),'AC'=>array(),'TAX_ID'=>array(),'DISEASE'=>array(),'CELL_TYPE'=>array());
# // Parameter: PARAMS | List of parameters | multi_array | multi | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line
# // Example: php biorels_api.php search_cell_line -PARAMS "CELL_TYPE=Cancer cell line"
# // $[/API]
function search_cell_line($PARAMS,$COMPLETE=false)
{
	
	$query="SELECT * FROM cell_entry ce";
	$WHERE=array();
	if (isset($PARAMS['NAME'])&&$PARAMS['NAME']!=array())$WHERE[]="(cell_name IN ('".implode("','",$PARAMS['NAME'])."'))\n";
	if (isset($PARAMS['SYN']) &&$PARAMS['SYN']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_syn WHERE cell_syn_name IN ('".implode("','",$PARAMS['SYN'])."')))\n";
	if (isset($PARAMS['AC']) && $PARAMS['AC']!=array())$WHERE[]="(ce.cell_acc IN  ('".implode("','",$PARAMS['AC'])."'))\n";
	if (isset($PARAMS['TAX_ID']) && $PARAMS['TAX_ID']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_Taxon_map ctm, taxon t WHERE t.taxon_id= ctm.taxon_id AND tax_id IN ('".implode("','",$PARAMS['TAX_ID'])."')))\n";
	if (isset($PARAMS['DISEASE'])&&$PARAMS['DISEASE']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_disease WHERE disease_entry_id IN (SELECT disease_entry_id FROM disease_entry WHERE disease_tag IN ('".implode("','",$PARAMS['DISEASE'])."'))))\n";
	if (isset($PARAMS['CELL_TYPE']) && $PARAMS['CELL_TYPE']!=array())$WHERE[]="(ce.cell_type IN ('".implode("','",$PARAMS['CELL_TYPE'])."'))\n";
	if ($WHERE!=array())$query.=" WHERE ".implode(" AND ",$WHERE);
	else return array();
	
	$res=runQuery($query);
	
	if ($res==array())
	{
		$query="SELECT * FROM cell_entry ce";
		$WHERE=array();
	if (isset($PARAMS['NAME'])&&$PARAMS['NAME']!=array())$WHERE[]="(LOWER(cell_name) LIKE LOWER('%".implode("%') OR LOWER(cell_name) LIKE LOWER('%",$PARAMS['NAME'])."%'))\n";
	if (isset($PARAMS['SYN']) &&$PARAMS['SYN']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_syn WHERE LOWER(cell_syn_name) LIKE LOWER('%".implode("%') OR LOWER(cell_syn_name) LIKE LOWER('%",$PARAMS['SYN'])."%')))\n";
	if (isset($PARAMS['AC']) && $PARAMS['AC']!=array())$WHERE[]="(ce.cell_acc IN  ('".implode("','",$PARAMS['AC'])."'))\n";
	if (isset($PARAMS['TAX_ID']) && $PARAMS['TAX_ID']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_Taxon_map ctm, taxon t WHERE t.taxon_id= ctm.taxon_id AND tax_id IN ('".implode("','",$PARAMS['TAX_ID'])."')))\n";
	if (isset($PARAMS['DISEASE'])&&$PARAMS['DISEASE']!=array())$WHERE[]="(ce.cell_entry_id IN (SELECT cell_entry_id FROM cell_disease WHERE disease_entry_id IN (SELECT disease_entry_id FROM disease_entry WHERE disease_tag IN ('".implode("','",$PARAMS['DISEASE'])."'))))\n";
	if (isset($PARAMS['CELL_TYPE']) && $PARAMS['CELL_TYPE']!=array())$WHERE[]="(ce.cell_type IN ('".implode("','",$PARAMS['CELL_TYPE'])."'))\n";
	if ($WHERE!=array())$query.=" WHERE ".implode(" AND ",$WHERE);
	else return array();
	
	$res=runQuery($query);
	
	}
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_cell_info($line['cell_acc'],$COMPLETE);
	}
	return $data;
}





///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// RNA EXPRESSION ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


// $[API]
// Title: list RNA Expression samples
// Function: list_rna_expression_samples
// Description: List all RNA expression samples with their source, organ, and tissue information
// Ecosystem: Disease_anatomy:RNA expression|anatomy
// Example: php biorels_api.php list_rna_expression_samples
// $[/API]
function list_rna_expression_samples()
{
	return runQuery("SELECT rs.rna_sample_id, sample_id, source_name,organ_name,tissue_name,anatomy_tag,anatomy_name,anatomy_definition
	FROM RNA_SAMPLE RS,RNA_SOURCE RO, RNA_TISSUE RT 
	LEFT JOIN ANATOMY_ENTRY AE ON AE.ANATOMY_ENTRY_ID = RT.ANATOMY_ENTRY_ID
	WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID
	AND RT.RNA_TISSUE_ID = RS.RNA_TISSUE_ID");
}


// $[API]
// Title: Search RNA Expression samples
// Function: search_rna_expression_samples
// Description: Search for RNA expression samples by using their source, organ, tissue, sample ID, and anatomy tag
// Parameter: SOURCE_NAME | Source name | array | GTEX | optional
// Parameter: ORGAN | Organ name | array | Brain |  optional
// Parameter: TISSUE | Tissue name | array | Cerebellum | optional
// Parameter: SAMPLE_ID | Sample ID | array | GTEX-1117F |  optional
// Parameter: ANATOMY_TAG | Anatomy tag | array | UBERON_0002037 | optional
// Ecosystem: Disease_anatomy:RNA expression
// Example: php biorels_api.php search_rna_expression_samples -SOURCE_NAME 'GTEX' -ORGAN 'Brain' -TISSUE 'Cerebellum' -SAMPLE_ID 'GTEX-1117F' -ANATOMY_TAG 'UBERON_0002037'
// $[/API]
function search_rna_expression_samples($SOURCE_NAME=array(),$ORGAN=array(),$TISSUE=array(),$SAMPLE_ID=array(),$ANATOMY_TAG=array())
{
	$query="SELECT rs.rna_sample_id, sample_id, source_name,organ_name,tissue_name,anatomy_tag,anatomy_name,anatomy_definition
	FROM RNA_SAMPLE RS,RNA_SOURCE RO, RNA_TISSUE RT 
	LEFT JOIN ANATOMY_ENTRY AE ON AE.ANATOMY_ENTRY_ID = RT.ANATOMY_ENTRY_ID
	WHERE  RO.RNA_SOURCE_ID = RS.RNA_SOURCE_ID
	AND RT.RNA_TISSUE_ID = RS.RNA_TISSUE_ID";
	if (count($SOURCE_NAME)!=0)$query.=" AND source_name IN ('".implode("','",$SOURCE_NAME)."')";
	if (count($ORGAN)!=0)$query.=" AND organ_name IN ('".implode("','",$ORGAN)."')";
	if (count($TISSUE)!=0)$query.=" AND tissue_name IN ('".implode("','",$TISSUE)."')";
	if (count($SAMPLE_ID)!=0)$query.=" AND sample_id IN ('".implode("','",$SAMPLE_ID)."')";
	if (count($ANATOMY_TAG)!=0)$query.=" AND anatomy_tag IN ('".implode("','",$ANATOMY_TAG)."')";
	echo $query."\n";
	return runQuery($query);

}


# // $[API]
# // Title: Get RNA Expression samples for a transcript and a list of samples
# // Function: get_transcript_expression
# // Description: Get the expression of a transcript in a list of samples
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Parameter: SAMPLE_IDS | List of sample IDs | array | GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI | optional
# // Return: TPM, sample ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: php biorels_api.php get_transcript_expression -TRANSCRIPT_NAME 'ENST00000379031' -SAMPLE_IDS GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI
# // $[/API]
function get_transcript_expression($TRANSCRIPT_NAME,$SAMPLE_IDS=array())
{
	$LIST_SAMPLES=array();
	if (is_array($SAMPLE_IDS))
	{
		foreach ($SAMPLE_IDS as $S)$LIST_SAMPLES[]="'".$S."'";
	}
	$pos=strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query="SELECT TPM,SAMPLE_ID
	FROM TRANSCRIPT T, RNA_TRANSCRIPT RT, RNA_SAMPLE RS
	WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
	AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
	AND TRANSCRIPT_NAME='".$TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";
	if ($LIST_SAMPLES!=array())$query.=" AND RS.SAMPLE_ID IN (".implode(',',$LIST_SAMPLES).")";
	echo $query."\n";
	return runQuery($query);

}



# // $[API]
# // Title: Get RNA Expression for all genes for a given sample
# // Function: get_sample_rna_expression
# // Description: Get the expression of all genes in a given sample
# // Parameter: SAMPLE_ID | Sample ID | string | GTEX-ZVZQ-0011-R11a-SM-51MS6 | required
# // Parameter: SOURCE_NAME | Source name | string | GTEX | required
# // Return: TPM, transcript name, transcript version, gene sequence name, gene sequence version, symbol, gene ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene|transcript
# // Example: php biorels_api.php get_sample_rna_expression -SAMPLE_ID 'GTEX-ZVZQ-0011-R11a-SM-51MS6' -SOURCE_NAME 'GTEX'
# // $[/API]
function get_sample_rna_expression($SAMPLE_ID,$SOURCE_NAME)
{
	$query="SELECT TPM,TRANSCRIPT_NAME,TRANSCRIPT_VERSION,GENE_SEQ_NAME,GENE_SEQ_VERSION,SYMBOL,GENE_ID
	FROM GENE_SEQ GS LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID ,
	TRANSCRIPT T, RNA_TRANSCRIPT RT, RNA_SAMPLE RS, RNA_SOURCE RO
	WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
	AND RS.RNA_SOURCE_ID = RO.RNA_SOURCE_ID
	AND  GS.GENE_SEQ_ID= T.GENE_SEQ_ID 
	AND T.TRANSCRIPT_ID = RT.TRANSCRIPT_ID
	AND SAMPLE_ID='".$SAMPLE_ID."'"
	." AND LOWER(SOURCE_NAME)=LOWER('".$SOURCE_NAME."')";

	return runQuery($query);
}


# // $[API]
# // Title: Get statistics of RNA expression for a transcript across different tissues
# // Function: get_transcript_rna_expression_stat
# // Description: Get the statistics of RNA expression for a transcript across different tissues
# // Parameter: TRANSCRIPT_NAME | Transcript name | string | ENST00000379031 | required
# // Return: Organ name, tissue name, number of samples, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: php biorels_api.php get_transcript_rna_expression_stat -TRANSCRIPT_NAME 'ENST00000379031'
# // $[/API]
function get_transcript_rna_expression_stat($TRANSCRIPT_NAME)
{
	$pos=strpos($TRANSCRIPT_NAME,'.');
	if ($pos!==false)
	{
		$TRANSCRIPT_VERSION=substr($TRANSCRIPT_NAME,$pos+1);
		$TRANSCRIPT_NAME=substr($TRANSCRIPT_NAME,0,$pos);
		if (!is_numeric($TRANSCRIPT_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query="SELECT ORGAN_NAME,TISSUE_NAME,NSAMPLE as N_SAMPLE,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
	FROM   TRANSCRIPT T, RNA_TRANSCRIPT_STAT RGS, RNA_TISSUE RT
	WHERE  RGS.TRANSCRIPT_ID = T.TRANSCRIPT_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	AND TRANSCRIPT_NAME='".$TRANSCRIPT_NAME."'";
	if ($pos!==false)$query.=" AND TRANSCRIPT_VERSION='".$TRANSCRIPT_VERSION."'";

	return runQuery($query);
}




# // $[API]
# // Title: Get statistics of Transcript RNA expression 
# // Function: get_tissue_transcript_rna_expression_stat
# // Description: Get the statistics of RNA expression for all transcript across a set of different tissues/source/organ/anatomy_tag
# // Parameter: SOURCE_NAME | Source name | array | Brain | optional
# // Parameter: ORGAN | Organ name | array | Cerebellum,Cortex | optional
# // Parameter: TISSUE | Tissue name | array | UBERON_0002037 | optional
# // Parameter: ANATOMY_TAG | Anatomy tag | array | GTEX | optional
# // Return: Transcript name, transcript version, organ name, tissue name, number of samples, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:transcript
# // Example: php biorels_api.php get_tissue_transcript_rna_expression_stat -ORGAN 'Brain'
# // Example: php biorels_api.php get_tissue_transcript_rna_expression_stat -TISSUE 'Cerebellum,Cortex' 
# // Example: php biorels_api.php get_tissue_transcript_rna_expression_stat -ANATOMY_TAG 'UBERON_0002037'  -SOURCE_NAME 'GTEX'
# // $[/API]
function get_tissue_transcript_rna_expression_stat($SOURCE_NAME=array(),$ORGAN=array(),$TISSUE=array(),$ANATOMY_TAG=array())
{
	$query="SELECT TRANSCRIPT_NAME,TRANSCRIPT_VERSION, ORGAN_NAME,TISSUE_NAME,NSAMPLE as N_SAMPLE,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
	FROM   TRANSCRIPT T, RNA_TRANSCRIPT_STAT RGS, RNA_TISSUE RT
	WHERE  RGS.TRANSCRIPT_ID = T.TRANSCRIPT_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID ";
	if (is_array($SOURCE_NAME))$query.=" AND source_name IN ('".implode("','",$SOURCE_NAME)."')";
	if (is_array($ORGAN))$query.=" AND organ_name IN ('".implode("','",$ORGAN)."')";
	if (is_array($TISSUE))$query.=" AND tissue_name IN ('".implode("','",$TISSUE)."')";
	if (is_array($ANATOMY_TAG))$query.=" AND anatomy_tag IN ('".implode("','",$ANATOMY_TAG)."')";
	return runQuery($query);
}







# // $[API]
# // Title: Get RNA Expression samples for a gene and a list of samples
# // Function: get_gene_expression
# // Description: Get the expression of a gene in a list of samples
# // Parameter: GENE_SEQ_NAME | Ensembl Gene Seq | string | ENSG00000223972 | required
# // Parameter: SAMPLE_IDS | List of sample IDs | array | GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI | optional
# // Return: TPM, sample ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: php biorels_api.php get_gene_expression -GENE_SEQ_NAME 'ENSG00000223972' -SAMPLE_IDS GTEX-ZVZQ-0011-R11a-SM-51MS6,GTEX-ZVT3-0011-R11b-SM-57WBI
# // $[/API]
function get_gene_expression($GENE_SEQ_NAME,$SAMPLE_IDS=array())
{
	$LIST_SAMPLES=array();
	if (is_array($SAMPLE_IDS))
	{
		foreach ($SAMPLE_IDS as $S)$LIST_SAMPLES[]="'".$S."'";
	}
	$pos=strpos($GENE_SEQ_NAME,'.');
	if ($pos!==false)
	{
		$GENE_SEQ_VERSION=substr($GENE_SEQ_NAME,$pos+1);
		$GENE_SEQ_NAME=substr($GENE_SEQ_NAME,0,$pos);
		if (!is_numeric($GENE_SEQ_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query="SELECT TPM,SAMPLE_ID
	FROM GENE_SEQ T, RNA_GENE RT, RNA_SAMPLE RS
	WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
	AND T.GENE_SEQ_ID = RT.GENE_SEQ_ID
	AND GENE_SEQ_NAME='".$GENE_SEQ_NAME."'";
	if ($pos!==false)$query.=" AND GENE_SEQ_VERSION='".$GENE_SEQ_VERSION."'";
	if ($LIST_SAMPLES!=array())$query.=" AND RS.SAMPLE_ID IN (".implode(',',$LIST_SAMPLES).")";
	
	return runQuery($query);

}



# // $[API]
# // Title: Get RNA Expression for all genes for a given sample
# // Function: get_sample_gene_rna_expression
# // Description: Get the expression of all genes in a given sample
# // Parameter: SAMPLE_ID | Sample ID | string | GTEX-ZVZQ-0011-R11a-SM-51MS6 | required
# // Parameter: SOURCE_NAME | Source name | string | GTEX | required
# // Return: TPM,  gene sequence name, gene sequence version, symbol, gene ID
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: php biorels_api.php get_sample_rna_expression -SAMPLE_ID 'GTEX-ZVZQ-0011-R11a-SM-51MS6' -SOURCE_NAME 'GTEX'
# // $[/API]
function get_sample_gene_rna_expression($SAMPLE_ID,$SOURCE_NAME)
{
	
	$query="SELECT TPM,GENE_SEQ_NAME,GENE_SEQ_VERSION,SYMBOL,GENE_ID
	FROM GENE_SEQ GS LEFT JOIN GN_ENTRY GE ON GE.GN_ENTRY_ID = GS.GN_ENTRY_ID ,
	 RNA_GENE RT, RNA_SAMPLE RS, RNA_SOURCE RO
	WHERE  RS.RNA_SAMPLE_ID = RT.RNA_SAMPLE_ID
	AND RS.RNA_SOURCE_ID = RO.RNA_SOURCE_ID
	AND GS.GENE_SEQ_ID= RT.GENE_SEQ_ID
	AND SAMPLE_ID='".$SAMPLE_ID."'"
	." AND LOWER(SOURCE_NAME)=LOWER('".$SOURCE_NAME."')";
	
	return runQuery($query);
}


# // $[API]
# // Title: Get statistics of RNA expression for a gene across different tissues
# // Function: get_gene_rna_expression_stat
# // Description: Get the statistics of RNA expression for a gene across different tissues
# // Parameter: GENE_SEQ_NAME | Gene sequence name | string | ENSG00000223972 | required
# // Return: Organ name, tissue name, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: php biorels_api.php get_gene_rna_expression_stat -GENE_SEQ_NAME 'ENSG00000223972'
# // $[/API]
function get_gene_rna_expression_stat($GENE_SEQ_NAME)
{
	$pos=strpos($GENE_SEQ_NAME,'.');
	if ($pos!==false)
	{
		$GENE_SEQ_VERSION=substr($GENE_SEQ_NAME,$pos+1);
		$GENE_SEQ_NAME=substr($GENE_SEQ_NAME,0,$pos);
		if (!is_numeric($GENE_SEQ_VERSION)) throw new Exception("Version number is not numeric",ERR_TGT_USR);
	}
	$query="SELECT ORGAN_NAME,TISSUE_NAME,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
	FROM   GENE_SEQ T, RNA_GENE_STAT RGS, RNA_TISSUE RT
	WHERE  RGS.GENE_SEQ_ID = T.GENE_SEQ_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID 
	AND GENE_SEQ_NAME='".$GENE_SEQ_NAME."'";
	if ($pos!==false)$query.=" AND GENE_SEQ_VERSION='".$GENE_SEQ_VERSION."'";
	
	return runQuery($query);
}







# // $[API]
# // Title: Get statistics of Gene RNA expression 
# // Function: get_tissue_gene_rna_expression_stat
# // Description: Get the statistics of RNA expression for all gene across a set of different tissues/source/organ/anatomy_tag
# // Parameter: ORGAN | Organ name | array | Brain | optional
# // Parameter: TISSUE | Tissue name | array | Cerebellum,Cortex | optional
# // Parameter: ANATOMY_TAG | Anatomy tag | array | UBERON_0002037 | optional
# // Return: gene seq name, gene version, organ name, tissue name, AUC, lower value, LR, minimum value, Q1, median value, Q3, maximum value
# // Ecosystem: Disease_anatomy:RNA expression;Genomics:gene
# // Example: php biorels_api.php get_tissue_gene_rna_expression_stat -ORGAN 'Brain'
# // Example: php biorels_api.php get_tissue_gene_rna_expression_stat  -TISSUE 'Cerebellum,Cortex' 
# // Example: php biorels_api.php get_tissue_gene_rna_expression_stat  -ANATOMY_TAG 'UBERON_0002037'
# // $[/API]
function get_tissue_gene_rna_expression_stat($ORGAN=array(),$TISSUE=array(),$ANATOMY_TAG=array())
{
	$query="SELECT GENE_SEQ_NAME,GENE_SEQ_VERSION, ORGAN_NAME,TISSUE_NAME,AUC,LOWER_VALUE,LR,MIN_VALUE,Q1,MED_VALUE,Q3,MAX_VALUE
	FROM   GENE_SEQ T, RNA_GENE_STAT RGS, RNA_TISSUE RT
	WHERE  RGS.GENE_SEQ_ID = T.GENE_SEQ_ID 
	AND  RT.RNA_TISSUE_ID = RGS.RNA_TISSUE_ID ";
	if (is_array($ORGAN))$query.=" AND organ_name IN ('".implode("','",$ORGAN)."')";
	if (is_array($TISSUE))$query.=" AND tissue_name IN ('".implode("','",$TISSUE)."')";
	if (is_array($ANATOMY_TAG))$query.=" AND anatomy_tag IN ('".implode("','",$ANATOMY_TAG)."')";
	
	return runQuery($query);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////// Clinical variant ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////




# // $[API]
# // Title: List and count the number of clinical variant by type
# // Function: list_clinical_variant_type
# // Description: List and count the number of clinical variant by type
# // Return: Count, clinical variant type
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: php biorels_api.php list_clinical_variant_type
# // $[/API]
function list_clinical_variant_type()
{
	return runQuery("SELECT count(*) n_clinical_variant, clinical_variant_type 
		FROM clinical_variant_entry cve, clinical_variant_type cvt
		WHERE cve.clinical_variant_type_id = cvt.clinical_variant_type_id
		group by clinical_variant_type 
		order by n_clinical_variant DESC");
}


# // $[API]
# // Title: List and count the number of clinical variant by gene
# // Function: list_clinical_variant_by_gene
# // Description: List and count the number of clinical variant by gene
# // Return: Count, gene ID, symbol, full name
# // Ecosystem: Disease_anatomy:clinical variant;Genomics:gene
# // Example: php biorels_api.php list_clinical_variant_by_gene
# // $[/API]
function list_clinical_variant_by_gene()
{
	return runQuery("SELECT count(*) n_clinical_variant, gene_id, symbol, full_name
		FROM clinical_variant_entry cve, clinical_variant_submission cvs, 
		clinical_variant_gn_map cvgm, gn_entry ge
		WHERE cve.clinvar_entry_id = cvs.clinvar_entry_id
		AND cvs.clinvar_submission_id = cvgm.clinvar_submission_id
		AND cvgm.gn_entry_id = ge.gn_entry_id
		group by gene_id, symbol, full_name");
}


# // $[API]
# // Title: List and count the number of clinical variant by significance
# // Function: list_clinical_variant_significance
# // Description: List and count the number of clinical variant by significance
# // Return: Count, clinical significance
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: php biorels_api.php list_clinical_variant_significance
# // $[/API]
function list_clinical_variant_significance()
{
	return runQuery("SELECT count(distinct clinvar_entry_id) n_clinical_variant, clin_sign as clinical_significance
		FROM clinical_variant_submission cvs, clinical_significance cs
		WHERE cvs.clin_sign_id = cs.clin_sign_id
		group by clin_sign");

}


# // $[API]
# // Title: clinical variant information
# // Function: get_clinical_variant_information
# // Description: Get all information about a clinical variant
# // Parameter: CLINICAL_VARIANT_NAME | Clinical variant name | string | NM_000059.3:c.35G>A | required
# // Return: Clinical variant record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: php biorels_api.php get_clinical_variant_information -CLINICAL_VARIANT_NAME 'NM_000059.3:c.35G>A'
# // $[/API]
function get_clinical_variant_information($CLINICAL_VARIANT_NAME)
{
	$query="SELECT * 
	FROM clinical_variant_entry cve, 
		 clinical_variant_type cvt,
		 clinical_Variant_review_status cvrs
	WHERE cve.clinical_variant_type_id = cvt.clinical_variant_type_id
	AND cve.clinical_variant_review_status = cvrs.clinvar_review_status_id
	AND LOWER(clinical_variant_name) LIKE LOWER('%".$CLINICAL_VARIANT_NAME."%')";

	$res=runQuery($query);
	
	foreach ($res as &$line)
	{
		$res2=runQuery("SELECT scv_id FROM clinical_variant_submission cvs WHERE clinvar_entry_id =".$line['clinvar_entry_id']);
		foreach ($res2 as $l2)
		$line['SUBMISSION'][$l2['scv_id']]=get_clinical_variant_submission($l2['scv_id']);	
	}
	return $res;
}




# // $[API]
# // Title: Get clinical variant submission
# // Function: get_clinical_variant_submission
# // Description: Get all information about a clinical variant submission
# // Parameter: SCV_ID | SCV ID | string | SCV004922085.1 | required
# // Return: Clinical variant submission record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: php biorels_api.php get_clinical_variant_submission -SCV_ID 'SCV004922085.1'
# // $[/API]
function get_clinical_variant_submission($SCV_ID)
{
	$res=runQuery("SELECT *
	FROM clinical_variant_submission cvs, clinical_significance cs, clinical_variant_review_status cvrs
	WHERE cvs.clin_sign_id = cs.clin_sign_id
	AND cvs.clinical_variant_review_status = cvrs.clinvar_review_status_id
	AND scv_id='".$SCV_ID."'");
	foreach ($res as &$line)
	{
		
		$res2=runQuery("SELECT g.gene_id 
		FROM gn_entry g, clinical_variant_gn_map cvgm
		WHERE g.gn_entry_id = cvgm.gn_entry_id
		AND cvgm.clinvar_submission_id = '".$line['clinvar_submission_id']."'");
		if ($res2!=array())
		{
			foreach ($res2 as $line2)
			{
				$line['GENE'][]=get_gene_by_gene_id($line2['gene_id']);
			}
			
		}

		$res2=runQuery("SELECT d.disease_tag
		FROM disease_entry d, clinical_variant_disease_map cvdm
		WHERE d.disease_entry_id = cvdm.disease_entry_id
		AND cvdm.clinvar_submission_id = '".$line['clinvar_submission_id']."'");
		if ($res2!=array())
		{
			foreach ($res2 as $line2)
			{
				$line['DISEASE'][]=search_disease_by_tag($line2['disease_tag']);
			}
			
		}

		$res2=runQuery("SELECT pmid FROM clinical_variant_pmid_map cvp, pmid_entry pe 
		WHERE pe.pmid_entry_id = cvp.pmid_entry_id
		AND clinvar_submission_id='".$line['clinvar_submission_id']."'");
		if ($res2!=array())
		{
			foreach ($res2 as $line2)
			{
				$line['PMID'][]=$line2['pmid'];
			}
			
		}
	}
	return $res;
}



# // $[API]
# // Title: Search clinical variant by different parameters
# // Function: search_clinical_variant
# // Description: Search for a clinical variant by using different parameters. 
# // Parameter: PARAMS | List of parameters, Use $PARAMS=array('NAMES'=>array(),'TYPE'=>array(),'STATUS'=>array(),'GENE_IDS'=>array(),'TRANSCRIPTS'=>array(),'SIGNIFICANCE'=>array()); | multi_array | | required
# // Return: Clinical variant record
# // Ecosystem: Disease_anatomy:clinical variant
# // Example: php biorels_api.php search_clinical_variant -PARAMS "SIGNIFICANCE=Pathogenic"
# // Example: php biorels_api.php search_clinical_variant -PARAMS "GENE_IDS=1017,1018"
# // $[/API]
function search_clinical_variant($PARAMS)
{

	$NAMES=array();
	$TYPE=array();
	$STATUS=array();
	$GENE_IDS=array();
	$TRANSCRIPTS=array();
	$SIGNIFICANCE=array();
	$DISEASE=array();
	if (isset($PARAMS['NAMES'])) $NAMES=$PARAMS['NAMES'];
	if (isset($PARAMS['TYPE'])) $TYPE=$PARAMS['TYPE'];
	if (isset($PARAMS['STATUS'])) $STATUS=$PARAMS['STATUS'];
	if (isset($PARAMS['GENE_IDS'])) $GENE_IDS=$PARAMS['GENE_IDS'];
	if (isset($PARAMS['TRANSCRIPTS'])) $TRANSCRIPTS=$PARAMS['TRANSCRIPTS'];
	if (isset($PARAMS['SIGNIFICANCE'])) $SIGNIFICANCE=$PARAMS['SIGNIFICANCE'];
	if (isset($PARAMS['DISEASE'])) $DISEASE=$PARAMS['DISEASE'];
	if ($NAMES==array() && $DISEASE=array() && $TYPE==array() && $STATUS==array() && $GENE_IDS==array() && $TRANSCRIPTS==array() && $SIGNIFICANCE==array()) throw new Exception("No parameters provided",ERR_TGT_USR);


	$query="SELECT DISTINCT clinical_variant_name 
	FROM clinical_variant_entry cve,clinical_variant_submission cvs  ";
	if (count($TYPE)!=0) $query.=" ,  clinical_variant_type cvt";
	if (count($STATUS)!=0) $query.=" , clinical_Variant_review_status cvrs";
	if (count($SIGNIFICANCE)!=0)$query.=", clinical_significance cs ";
	if (count($GENE_IDS)!=0)$query.=", clinical_variant_gn_map cvgm, gn_entry ge ";
	if (count($DISEASE)!=0)$query.=", clinical_variant_disease_map cvdm, disease_entry de ";
		 
	$query .= " WHERE  cve.clinvar_entry_id = cvs.clinvar_entry_id ";
	if (count($TYPE)!=0) $query .=" AND cve.clinical_variant_type_id = cvt.clinical_variant_type_id   ";
	if (count($STATUS)!=0) $query .= " AND cve.clinical_variant_review_status = cvrs.clinvar_review_status_id ";
	if (count($SIGNIFICANCE)!=0) $query .="	AND cs.clin_sign_id = cvs.clin_sign_id ";
	if (count($DISEASE)!=0) $query .="	AND de.disease_entry_id = cvdm.disease_entry_id AND cvdm.clinvar_submission_id = cvs.clinvar_submission_id and disease_tag IN ('".implode("','",$DISEASE)."') ";
	if (count($NAMES)!=0)$query.=" AND LOWER(clinical_variant_name) LIKE LOWER('%".implode("%') OR LOWER(clinical_variant_name) LIKE LOWER('%",$NAMES)."%'))\n";
	if (count($TYPE)!=0)$query.=" AND clinical_variant_type IN ('".implode("','",$TYPE)."')";
	if (count($STATUS)!=0)$query.=" AND clinical_variant_review_status IN ('".implode("','",$STATUS)."')";
	if (count($GENE_IDS)!=0)$query.=" AND cvs.clinvar_submission_id = cvgm.clinvar_submission_id AND cvgm.gn_entry_id = ge.gn_entry_id
			
			AND gene_id IN ('".implode("','",$GENE_IDS)."')";
	if (count($SIGNIFICANCE)!=0)$query.=" AND LOWER(clin_sign) IN ('".strtolower(implode("','",$SIGNIFICANCE))."')";
	if (count($TRANSCRIPTS)!=0)$query.=" AND LOWER(clinical_variant_name) LIKE LOWER('%".implode("%') OR LOWER(clinical_variant_name) LIKE LOWER('%",$TRANSCRIPTS)."%'))\n";
	$res=runQuery($query);
	
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_clinical_variant_information(str_replace("'","''",$line['clinical_variant_name']));
	}
	return $data;
}












///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////// DRUG/CLINICAL TRIALS //////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////




///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////// CLINICAL TRIALS ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: get information for a clinical trial 
# // Function: get_clinical_trial_information
# // Description: Get all information about a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_information -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_information($TRIAL_ID,$COMPLETE=false)
{
	$query="SELECT * FROM clinical_trial WHERE trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	foreach ($res as &$line)
	{

		if ($COMPLETE)
		{	
			$line['details']=json_decode($line['details'],true);
			
		} 
		else 
		{
			
			unset($line['details']);
		}
		
	}

	return $res;
}



# // $[API]
# // Title: List and count the number of clinical trials by phase
# // Function: list_clinical_phases
# // Description: List and count the number of clinical trials by phase
# // Return: Count, clinical phase
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php list_clinical_phases
# // $[/API]
function list_clinical_phases()
{
	return runQuery("SELECT count(*) n_clinical_trial, clinical_phase
	 FROM clinical_trial group by clinical_phase order by n_clinical_trial DESC");
}


# // $[API]
# // Title: Search clinical trial by different parameters
# // Function: search_clinical_trial
# // Description: Search for a clinical trial by using different parameters.
# // Parameter: PARAMS | List of parameters, Use $PARAMS=array('phase'=>array(),'ATC_code'=>array(),'WITH_ATC_CHILD'=>true,'status'=>array(),'id'=>array(),'gene_symbol'=>array(),'title'=>array(),'alias'=>array(),'intervention_type'=>array(),'intervention_name'=>array(),'condition'=>array(),'company'=>array(),'pmid'=>array(),'after_date'=>array(),'disease_tag'=>array(),'arm'=>array(),'drug_name'=>array()); | multi_array | | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional 
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php search_clinical_trial -PARAMS "phase=1,2;status=Recruiting"
# // $[/API]
function search_clinical_trial($PARAMS,$COMPLETE=false)
{
	$query='SELECT clinical_trial_id FROM ';
	$TABLES['clinical_trial ct']=true;
	$CONNECT=array();
	if (isset($PARAMS['phase'])) $params[]=" clinical_phase IN ('".implode("','",$PARAMS['phase'])."')\n";
	if (isset($PARAMS['status'])) $params[]=" clinical_status IN ('".implode("','",$PARAMS['status'])."')\n";
	if (isset($PARAMS['id'])) $params[]=" trial_id IN ('".implode("','",$PARAMS['id'])."')\n";
	if (isset($PARAMS['title'])) $params[]=" (LOWER(official_title) LIKE LOWER('%".implode("%') OR LOWER(official_title) LIKE LOWER('%",$PARAMS['title'])."%'))\n";
	if (isset($PARAMS['alias']))
	{
		$TABLES['clinical_trial_alias cta']=true;
		$CONNECT[" ct.clinical_trial_id = cta.clinical_trial_id"]=true;
		$params[]= "( LOWER(alias_name) LIKE LOWER('%".implode("%') OR LOWER(alias_name) LIKE LOWER('%",$PARAMS['alias'])."%'))\n";
	}
	if (isset($PARAMS['ATC_code']))
	{
		$list=array();
		if (isset($PARAMS['WITH_ATC_CHILD']))
		{
			foreach ($PARAMS['ATC_code'] as $A)
			{
				$res=get_ATC_child_hierarchy($A);
				foreach ($res as $l)
					$list[]=$l['atc_code'];
			}
		}
		else $list=$PARAMS['ATC_code'];
		$TABLES['clinical_trial_intervention cti']=true;
		$TABLES['clinical_trial_intervention_drug_map ctidm']=true;
		$TABLES['drug_entry de']=true;
		$TABLES['drug_atc_map dam']=true;
		$TABLES['atc_entry ac']=true;
		$CONNECT['ct.clinical_trial_id = cti.clinical_trial_id']=true;
		$CONNECT["cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id"]=true;
		$CONNECT["ctidm.drug_entry_id = de.drug_entry_id"]=true;
		$CONNECT["de.drug_entry_id = dam.drug_entry_id"]=true;
		$CONNECT["dam.atc_entry_id = ac.atc_entry_id"]=true;
		$params[]= " ac.atc_code IN ('".implode("','",$list)."')\n";
	}
	if (isset($PARAMS['intervention_type']))
	{
		$TABLES['clinical_trial_intervention cti']=true;
		$CONNECT[" ct.clinical_trial_id = cti.clinical_trial_id"]=true;
		$params[]= " intervention_type IN ('".implode("','",$PARAMS['intervention_type'])."')\n";
	}

	if (isset($PARAMS['intervention_name']))
	{
		$TABLES['clinical_trial_intervention cti']=true;
		$CONNECT[" ct.clinical_trial_id = cti.clinical_trial_id"]=true;
		$params[]= "( LOWER(intervention_name) LIKE LOWER('%".implode("%') OR LOWER(intervention_name) LIKE LOWER('%",$PARAMS['intervention_name'])."%'))\n";
	}

	if (isset($PARAMS['condition']))
	{
		$TABLES['clinical_trial_condition ctc']=true;
		$CONNECT[" ct.clinical_trial_id = ctc.clinical_trial_id"]=true;
		$params[]= "( LOWER(condition_name) LIKE LOWER('%".implode("%') OR LOWER(condition_name) LIKE LOWER('%",$PARAMS['condition'])."%'))\n";
	}

	if (isset($PARAMS['company']))
	{
		$TABLES['clinical_trial_company_map ctcm']=true;
		$TABLES['company_entry coe']=true;
		$CONNECT["  AND ctcm.company_entry_Id = coe.company_entry_id"]=true;
		$params[]= "( LOWER(company_name) LIKE LOWER('%".implode("%') OR LOWER(company_name) LIKE LOWER('%",$PARAMS['company'])."%'))\n";
	}

	if (isset($PARAMS['pmid']))
	{
		$TABLES['clinical_trial_pmid_map ctpm, pmid_entry pe']=true;
		$CONNECT['ct.clinical_trial_id = ctpm.clinical_trial_id']=true;
		$CONNECT["ctpm.pmid_entry_id = pe.pmid_entry_id"]=true;
		$params[]= " pe.pmid IN ('".implode("','",$PARAMS['pmid'])."')\n";
	
	}

	if (isset($PARAMS['disease_tag']))
	{
		$TABLES['clinical_trial_condition ctc']=true;
		$TABLES['disease_entry de']=true;
		$CONNECT['ct.clinical_trial_id = ctc.clinical_trial_id']=true;
		$CONNECT["ctc.disease_entry_id = de.disease_entry_id"]=true;
		$params[]= " disease_tag IN ('".implode("','",$PARAMS['disease_tag'])."')\n";
	
	}

	if (isset($PARAMS['gene_symbol']))
	{
		$TABLES['clinical_trial_drug ctd']=true;
		$TABLES['drug_disease dd']=true;
		$TABLES['gn_entry g']=true;
		$CONNECT['ct.clinical_trial_id = ctd.clinical_trial_id']=true;
		$CONNECT["ctd.drug_disease_id = dd.drug_disease_id"]=true;
		$CONNECT["dd.gn_entry_id = g.gn_entry_id"]=true;
		$params[]= "( LOWER(symbol) LIKE LOWER('%".implode("%') OR LOWER(symbol) LIKE LOWER('%",$PARAMS['gene_symbol'])."%'))\n";
	}

	if (isset($PARAMS['arm']))
	{
		$TABLES['clinical_trial_arm ctar']=true;
		$params[]= "( LOWER(arm_label) LIKE LOWER('%".implode("%') OR LOWER(arm_label) LIKE LOWER('%",$PARAMS['arm_label'])."%'))\n";
		$CONNECT[" ct.clinical_trial_id = ctar.clinical_trial_id"]=true;
	}

	if (isset($PARAMS['after_date']))
	{
		$params[]= " start_date > '".$PARAMS['after_date']."'\n";
	}

	if (isset($PARAMS['drug_name']))
	{
		$TABLES['clinical_trial_intervention cti']=true;
		$TABLES['clinical_trial_intervention_drug_map ctidm']=true;
		$TABLES['drug_entry de']=true;
		$CONNECT['ct.clinical_trial_id = cti.clinical_trial_id']=true;
		$CONNECT["cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id"]=true;
		$CONNECT["ctidm.drug_entry_id = de.drug_entry_id"]=true;
		$params[]= "( LOWER(drug_primary_name) LIKE LOWER('%".implode("%') OR LOWER(drug_primary_name) LIKE LOWER('%",$PARAMS['drug_name'])."%'))\n";
	}

	foreach ($CONNECT as $C=>&$DUMMY)$params[]=$C;
	$query="SELECT DISTINCT trial_id FROM ".implode(", ",array_keys($TABLES))." WHERE "
		.implode(" AND ",$params);
		echo $query."\n";
	$res=runQuery($query);
	
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_clinical_trial_information($line['trial_id'],$COMPLETE);
	}
	return $data;

}




# // $[API]
# // Title: Get clinical trials by disease
# // Function: get_clinical_trial_by_disease
# // Description: Get all clinical trials for a given disease
# // Parameter: DISEASE_TAG | Disease tag | string | MONDO_0005087 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_CHILDREN | True if clinical trials for children diseases are requested | boolean | false | optional
# // Return: Clinical trial record
# // Ecosystem:  Drug_clinical_trial:clinical trial;Disease_anatomy:disease
# // Example: php biorels_api.php get_clinical_trial_by_disease -DISEASE_TAG 'MONDO_0005087'
# // $[/API]
function get_clinical_trial_by_disease($DISEASE_TAG,$COMPLETE=false,$WITH_CHILDREN=false)
{

	$list=array();
	if ($WITH_CHILDREN)
	{
		$res=get_child_disease($DISEASE_TAG);
		foreach ($res as $l)
		$list[]=$l['disease_tag'];
	}
	else $list[]=$DISEASE_TAG;
	$query="SELECT DISTINCT trial_id FROM clinical_trial ct,clinical_trial_condition ctc,disease_entry de
	WHERE ct.clinical_trial_id = ctc.clinical_trial_id AND ctc.disease_entry_id = de.disease_entry_id
	AND disease_tag IN ('".implode("','",$list)."')";
	
	$res=runQuery($query);
	if (!$COMPLETE) return $res;
	
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_clinical_trial_information($line['trial_id']);
	}
	return $data;
}


# // $[API]
# // Title: Get clinical trials by gene
# // Function: get_clinical_trial_by_gene
# // Description: Get all clinical trials for a given gene
# // Parameter: GENE_SYMBOL | Gene symbol | string | BRCA1 | required
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial;Genomics:gene
# // Example: php biorels_api.php get_clinical_trial_by_gene -GENE_SYMBOL 'BRCA1'
# // $[/API]
function get_clinical_trial_by_gene($GENE_SYMBOL)
{
	$query="SELECT trial_id FROM clinical_trial ct,clinical_trial_drug ctd,drug_disease dd,gn_entry g
	WHERE ct.clinical_trial_id = ctd.clinical_trial_id AND ctd.drug_disease_id = dd.drug_disease_id
	AND dd.gn_entry_id = g.gn_entry_id
	AND LOWER(symbol) LIKE LOWER('%".$GENE_SYMBOL."%')";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_clinical_trial_information($line['trial_id']);
	}
	return $data;

}


# // $[API]
# // Title: Get clinical trials by drug
# // Function: search_clinical_trial_by_drug
# // Description: Search all clinical trials for a given drug
# // Parameter: DRUG_NAME | Drug name | string | Omeprazole | required
# // Return: Clinical trial record
# // Ecosystem: Drug_clinical_trial:clinical trial|drug
# // Example: php biorels_api.php search_clinical_trial_by_drug -DRUG_NAME 'Omeprazole'
# // $[/API]
function search_clinical_trial_by_drug($DRUG_NAME)
{
	$res=search_drug_by_name($DRUG_NAME,false);
	if ($res==array()) return array();
	$data=array();
	
	foreach ($res as $line)
	{
		
		$DRUG_PRIMARY_NAME=$line['drug_primary_name'];
		$query="SELECT trial_id 
		FROM clinical_trial ct,
		clinical_trial_intervention cti,
		clinical_trial_intervention_drug_map ctidm,
		drug_entry de
			WHERE ct.clinical_trial_id = cti.clinical_trial_id
			 AND cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id
			AND ctidm.drug_entry_id = de.drug_entry_id
			AND drug_primary_name ='".$DRUG_PRIMARY_NAME."'";
		$res2=runQuery($query);
		
		foreach ($res2 as $line)
		{
			$data[]=get_clinical_trial_information($line['trial_id']);
		}
	}
	
	return $data;
}



# // $[API]
# // Title: Get the title of a clinical trial
# // Function: get_clinical_trial_title
# // Description: Get the title of a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Clinical trial title
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_title -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_title($TRIAL_ID)
{
	$query="SELECT official_title FROM clinical_trial WHERE trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	if ($res==array()) return "";
	return $res[0]['official_title'];
}


# // $[API]
# // Title: Get the brief summary of a clinical trial
# // Function: get_clinical_trial_brief_summary
# // Description: Get the brief summary of a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Clinical trial brief summary
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_brief_summary -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_brief_summary($TRIAL_ID)
{
	$query="SELECT brief_summary FROM clinical_trial WHERE trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	if ($res==array()) return "";
	return $res[0]['brief_summary'];
}

# // $[API]
# // Title: Get the list of interventions for a clinical trial
# // Function: get_clinical_trial_intervention
# // Description: Get the list of interventions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Intervention name, intervention type, intervention description
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_intervention -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_intervention($TRIAL_ID)
{
	$query="SELECT intervention_name,intervention_type,intervention_description
		 FROM clinical_trial_intervention cti, clinical_trial ct
		 WHERE ct.clinical_trial_id = cti.clinical_trial_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	return $res;
}


# // $[API]
# // Title: Get the list of arms for a clinical trial
# // Function: get_clinical_trial_arms
# // Description: Get the list of arms for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Arm label, arm type, arm description
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_arms -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_arms($TRIAL_ID)
{
	$query="SELECT arm_label,arm_type,arm_description
		 FROM clinical_trial_arm cta, clinical_trial ct
		 WHERE ct.clinical_trial_id = cta.clinical_trial_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	return $res;
}

# // $[API]
# // Title: Get the list of conditions for a clinical trial
# // Function: get_clinical_trial_condition
# // Description: Get the list of conditions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Condition name
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_condition -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_condition($TRIAL_ID)
{
	$query="SELECT condition_name
		 FROM clinical_trial_condition ctc, clinical_trial ct
		 WHERE ct.clinical_trial_id = ctc.clinical_trial_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	return $res;
}


# // $[API]
# // Title: Get the mapping of arms to interventions for a clinical trial
# // Function: get_clinical_trial_arm_intervention
# // Description: Get the mapping of arms to interventions for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: Arm label, intervention name
# // Ecosystem: Drug_clinical_trial:clinical trial
# // Example: php biorels_api.php get_clinical_trial_arm_intervention -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_arm_intervention($TRIAL_ID)
{
	$query="SELECT arm_label,arm_type,arm_description,intervention_name,intervention_type,intervention_description
		 FROM clinical_trial_arm_intervention_map ctam, clinical_trial_arm cta,clinical_trial_intervention cti, clinical_trial ct
		 WHERE ct.clinical_trial_id = cta.clinical_trial_id
		 AND cta.clinical_trial_arm_id = ctam.clinical_trial_arm_id
		 AND ctam.clinical_trial_intervention_id = cti.clinical_trial_intervention_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	return $res;
}


# // $[API]
# // Title: Get the drugs listed in a clinical trial
# // Function: get_clinical_trial_drug
# // Description: Get the drugs listed in a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | true | optional
# // Return: Drug name
# // Ecosystem: Drug_clinical_trial:clinical trial|drug
# // Example: php biorels_api.php get_clinical_trial_drug -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_drug($TRIAL_ID,$COMPLETE=false)
{
	$query="SELECT drug_primary_name
		 FROM clinical_trial_intervention_drug_map ctidm, clinical_trial_intervention cti, drug_entry de, clinical_trial ct
		 WHERE ct.clinical_trial_id = cti.clinical_trial_id
		 AND cti.clinical_trial_intervention_id = ctidm.clinical_trial_intervention_id
		 AND ctidm.drug_entry_id = de.drug_entry_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	if ($COMPLETE)
	{
		foreach ($res as &$line)
		{
			$line['drug_info']=get_drug_information($line['drug_primary_name']);
		}
	}
	return $res;
}



# // $[API]
# // Title: Get the list of publications for a clinical trial
# // Function: get_clinical_trial_publications
# // Description: Get the list of publications for a clinical trial
# // Parameter: TRIAL_ID | Clinical trial ID | string | NCT00005219 | required
# // Return: PMID
# // Ecosystem: Drug_clinical_trial:clinical trial;Scientific_community:publication
# // Example: php biorels_api.php get_clinical_trial_publications -TRIAL_ID 'NCT00005219'
# // $[/API]
function get_clinical_trial_publications($TRIAL_ID)
{
	$query="SELECT pmid 
			FROM clinical_trial_pmid_map ctpm, pmid_entry pe, clinical_trial ct
		 WHERE ct.clinical_trial_id = ctpm.clinical_trial_id
		 AND ctpm.pmid_entry_id = pe.pmid_entry_id
		 AND trial_id='".$TRIAL_ID."'";
	$res=runQuery($query);
	return $res;
}





///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////// DRUGS //////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: Search drug by their identifier
# // Function: search_drug_by_identifier
# // Description: Find a drug by their identifier or name. The identifier can be the drug primary name, the drug external database value, the ChEMBL ID or the DrugBank ID
# // Parameter: ID | Drug identifier | string | CHEMBL1201583 | required
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php search_drug_by_identifier -ID 'DB00006'
# // Example: php biorels_api.php search_drug_by_identifier -ID 'CHEMBL1201583'
# // Example: php biorels_api.php search_drug_by_identifier -ID 'DRUGBANK:DB00006'
# // $[/API]

function search_drug_by_identifier($ID)
{
	$RULES=array();
	$tab=explode(":",$ID);
	$RULES[]= "drug_extdb_value='".$ID."'";
	if (count($tab)==2)
	{
		$RULES[]="LOWER(source_name) = LOWER('".$tab[0]."') AND drug_extdb_value = '".$tab[1]."'";
	}
	$tab=explode("_",$ID);
	if (count($tab)==2)
	{
		$RULES[]="LOWER(source_name) = LOWER('".$tab[0]."') AND drug_extdb_value = '".$tab[1]."'";
	}
	$RULES[]= "drug_primary_name='".$ID."'";
	$RULES[]="chembl_id='".$ID."'";
	$RULES[]="drugbank_id='".$ID."'";
	$query="SELECT distinct drug_primary_name FROM drug_entry de,drug_extdb dx,source s
	 WHERE  de.drug_entry_Id = dx.drug_entry_Id
	 AND dx.source_id = s.source_id
	 AND (".implode(" OR ",$RULES).")";
	$res=runQuery($query);
	$data=array();
	
	foreach ($res as $line)
	{
		$data[]=get_drug_information($line['drug_primary_name']);

	}
return $data;
}


# // $[API]
# // Title: Search drug by their name
# // Function: search_drug_by_name
# // Description: Find a drug by their name. The search is case insensitive
# // Parameter: NAME | Drug name | string | Omeprazole | required
# // Parameter: WITH_DRUG_INFORMATION | True if drug information is requested | boolean | true | optional | Default: true
# // Parameter: COMPLETE | True if complete drug information is requested | boolean | false | optional | Default: false
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php search_drug_by_name -NAME 'Omeprazole'
# // $[/API]
function search_drug_by_name($NAME,$WITH_DRUG_INFORMATION=true,$COMPLETE=false)
{
	$query="SELECT distinct drug_primary_name FROM drug_entry de,drug_name dn
	 WHERE  de.drug_entry_Id = dn.drug_entry_Id
	 AND (LOWER(drug_name) =LOWER('".$NAME."') 
	 OR LOWER(drug_primary_name) = LOWER('".$NAME."'))";
	$res=runQuery($query);
	if ($res==array())
	{
		$query="SELECT distinct drug_primary_name FROM drug_entry de,drug_name dn
	 WHERE  de.drug_entry_Id = dn.drug_entry_Id
	 AND (LOWER(drug_name) LIKE LOWER('%".$NAME."%') 
	 OR LOWER(drug_primary_name) LIKE LOWER('%".$NAME."%'))";
	$res=runQuery($query);
	}
	if (!$WITH_DRUG_INFORMATION&& !$COMPLETE) return $res;
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_drug_information($line['drug_primary_name'],$COMPLETE);
	}
	return $data;

}



# // $[API]
# // Title: get drug information
# // Function: get_drug_information
# // Description: Get all information about a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | true | optional
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_information -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_information($DRUG_PRIMARY_NAME,$COMPLETE=true)
{
	$query="SELECT * FROM drug_entry WHERE LOWER(drug_primary_name)=LOWER('".$DRUG_PRIMARY_NAME."')";
	$res=runQuery($query);
	if ($COMPLETE)
	foreach ($res as &$line)
	{
		$line['names']=get_drug_names($DRUG_PRIMARY_NAME);
		$line['extdb']=get_drug_extdb($DRUG_PRIMARY_NAME);
		$line['type']=get_drug_type($DRUG_PRIMARY_NAME);
		$line['atc_class']=get_drug_atc_class($DRUG_PRIMARY_NAME);
		$line['atc_hierarchy']=get_drug_atc_hierarchy($DRUG_PRIMARY_NAME);
		
		
		$res2=runQuery("SELECT molecular_entity_hash, is_preferred,source_name 
		FROM drug_mol_entity_map dm, molecular_entity me, source s
		WHERE dm.molecular_entity_id = me.molecular_entity_id
		AND dm.source_id = s.source_id
		AND drug_entry_id = ".$line['drug_entry_id']);
		$line['molecular_entity']=array();
		foreach ($res2 as $l)
		{
			$line['molecular_entity'][]=array(
			'is_preferred'=>$l['is_preferred'],
			'source_name'=>$l['source_name'],
			'structure'=>get_molecular_entity($l['molecular_entity_hash']));
		}
	}
	return $res;

}


# // $[API]
# // Title: get drug activity
# // Function: get_drug_activity
# // Description: Get the activity of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Parameter: WITH_ASSAY | True if assay information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_EXTENDED_ASSAY_INFO | True if extended assay information is requested | boolean | false | optional | Default: false
# // Return: Molecular entity hash, is preferred, source name, activity
# // Ecosystem: Drug_clinical_trial:drug;Assay:assay;Molecular entity:activity
# // Example: php biorels_api.php get_drug_activity -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_activity($DRUG_PRIMARY_NAME,$WITH_ASSAY=false,$WITH_EXTENDED_ASSAY_INFO=false)
{
	
		$res=runQuery("SELECT molecular_entity_hash, is_preferred,source_name 
		FROM drug_entry de, drug_mol_entity_map dm, molecular_entity me, source s
		WHERE de.drug_entry_id = dm.drug_entry_id
		AND dm.molecular_entity_id = me.molecular_entity_id
		AND dm.source_id = s.source_id
		AND drug_primary_name = '".$DRUG_PRIMARY_NAME."'");
		
		foreach ($res as &$l)
		{
			$l['activity']=get_molecular_entity_activity($l['molecular_entity_hash'],$WITH_ASSAY,$WITH_EXTENDED_ASSAY_INFO);
		}
	
	return $res;

	
}


# // $[API]
# // Title: get drug names
# // Function: get_drug_names
# // Description: Get all names for a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Drug name, is primary, is tradename, source name
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_names -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_names($DRUG_PRIMARY_NAME)
{
	$query="SELECT drug_name,is_primary,is_tradename,source_name FROM drug_entry de, drug_name dn, source s
	WHERE 
	s.source_id = dn.source_id
	AND de.drug_entry_id = dn.drug_entry_id

	AND drug_primary_name='".$DRUG_PRIMARY_NAME."'";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)$data[$line['drug_name']][]=array('is_primary'=>$line['is_primary'],'is_tradename'=>$line['is_tradename'],'source_name'=>$line['source_name']);
	return $data;

}


# // $[API]
# // Title: get drug external identifiers
# // Function: get_drug_extdb
# // Description: Get all external identifiers for a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Source name, drug external database value
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_extdb -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_extdb($DRUG_PRIMARY_NAME)
{
	$query="SELECT DISTINCT source_name,drug_extdb_value FROM drug_entry de, drug_extdb dx, source s
	WHERE de.drug_entry_id = dx.drug_entry_id
	AND dx.source_id = s.source_id
	AND drug_primary_name='".$DRUG_PRIMARY_NAME."'";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)$data[$line['drug_extdb_value']][]=$line['source_name'];
	return $data;
}


# // $[API]
# // Title: get drug type
# // Function: get_drug_type
# // Description: Get the type of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: Drug type name, drug type group
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_type -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_type($DRUG_PRIMARY_NAME)
{
	$query="SELECT drug_type_name,drug_Type_group FROM drug_entry de, drug_type_map dtm, drug_type dt
	 WHERE  de.drug_entry_id = dtm.drug_entry_id
	 AND dtm.drug_type_id = dt.drug_type_id

	 AND drug_primary_name='".$DRUG_PRIMARY_NAME."'";
	return runQuery($query);
	
}


# // $[API]
# // Title: get drug ATC class
# // Function: get_drug_atc_class
# // Description: Get the ATC class of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_atc_class -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_atc_class($DRUG_PRIMARY_NAME)
{
	$query="SELECT atc_code,atc_title FROM drug_entry de, drug_atc_map da, atc_entry ae
	 WHERE  de.drug_entry_id = da.drug_entry_id
	 AND da.atc_entry_id = ae.atc_entry_id
	 AND drug_primary_name='".$DRUG_PRIMARY_NAME."'";
	 return runQuery($query);
}


# // $[API]
# // Title: get drug ATC hierarchy
# // Function: get_drug_atc_hierarchy
# // Description: Get the ATC hierarchy of a drug
# // Parameter: DRUG_PRIMARY_NAME | Drug primary name | string | Omeprazole | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_atc_hierarchy -DRUG_PRIMARY_NAME 'Omeprazole'
# // $[/API]
function get_drug_atc_hierarchy($DRUG_PRIMARY_NAME)
{
	$query="SELECT distinct atc_entry_id FROM drug_entry de, drug_atc_map da
	 WHERE  de.drug_entry_id = da.drug_entry_id
	 AND drug_primary_name='".$DRUG_PRIMARY_NAME."'";
	 $res= runQuery($query);
	 $data=array();
	 foreach ($res as $line)
	 {
		$data[]=runQuery("SELECT ah2.atc_level,ae.atc_code,ae.atc_title FROM atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae
		WHERE ah1.atc_entry_id = ".$line['atc_entry_id']."
		AND ah1.atc_level_left >= ah2.atc_level_left
		AND ah1.atc_level_right <= ah2.atc_level_right
		AND ah2.atc_entry_id = ae.atc_entry_id ORDER BY ah2.atc_level");
	 }
	 return $data;
}



# // $[API]
# // Title: Find drugs from ATC code
# // Function: get_drug_from_ATC_Code
# // Description: Get all drugs for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Parameter: WITH_CHILD | True if child ATC codes are included | boolean | true | optional | Default: true
# // Return: Drug record
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_drug_from_ATC_Code -ATC_CODE 'A02BC01'
# // $[/API]
function get_drug_from_ATC_Code($ATC_CODE,$WITH_CHILD=true)
{
	$query='SELECT drug_primary_name, ae.atc_code, ae.atc_title
	 FROM drug_entry de, drug_atc_map dam, atc_entry ae';
	if ($WITH_CHILD)
	{
		$query.=', atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2';
	}
	$query.=' WHERE de.drug_entry_id = dam.drug_entry_id
	AND dam.atc_entry_id = ae.atc_entry_id ';
	if ($WITH_CHILD)
	{
		$query.=' AND ae.atc_entry_id = ah1.atc_entry_id
		AND ah1.atc_level_left >= ah2.atc_level_left
		AND ah1.atc_level_right <= ah2.atc_level_right
		AND ah2.atc_entry_id = ae2.atc_entry_id 
		AND ae2.atc_code = \''.$ATC_CODE.'\'';
		
	}
	else 
	{
		$query.=' AND ae.atc_code = \''.$ATC_CODE.'\'';
	}
	$res= runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$entry=get_drug_information($line['drug_primary_name'],false);
		$entry[0]['ATC_code']=$line['atc_code'];
		$entry[0]['ATC_title']=$line['atc_title'];
		$data[]=$entry[0];
	}
	return $data;

}


# // $[API]
# // Title: List target statistics for a given ATC code
# // Function: get_target_stat_for_ATC_Code
# // Description: List the targets for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02 | required
# // Parameter: WITH_CHILD | True if child ATC codes are included | boolean | false | optional | Default: false
# // Return: Target symbol, gene ID, count
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_target_stat_for_ATC_Code -ATC_CODE 'A02'
# // $[/API]

function get_target_stat_for_ATC_Code($ATC_CODE,$WITH_CHILD=false)

{
	$list=array();
	if ($WITH_CHILD)
	{
		$res=get_ATC_child_hierarchy($ATC_CODE);
		foreach ($res as $l)
		$list[]=$l['atc_code'];
	}
	else $list[]=$ATC_CODE;

	$query='SELECT symbol, gene_id, count(*) as count
	FROM atc_entry ae, drug_atc_map dam, drug_entry de, drug_disease dd, gn_entry g';
	
	$query .=' WHERE ae.atc_entry_id = dam.atc_entry_id
	AND dam.drug_entry_id = de.drug_entry_id
	AND de.drug_entry_id = dd.drug_entry_id
	AND dd.gn_entry_id = g.gn_entry_id
	AND ae.atc_code IN  (\''.implode("','",$list).'\')';
	

	$query.=' GROUP BY symbol, gene_id';
	$res= runQuery($query);
	return $res;
}

# // $[API]
# // Title: get information about ATC code
# // Function: get_ATC_info
# // Description: Get information about an ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Return: ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_ATC_info -ATC_CODE 'A02BC01'
# // $[/API]

function get_ATC_info($ATC_CODE)
{
	$query='SELECT atc_code, atc_title FROM atc_entry WHERE atc_code=\''.$ATC_CODE.'\'';
	$res= runQuery($query);
	return $res;
}

# // $[API]
# // Title: get ATC hierarchy
# // Function: get_ATC_hierarchy
# // Description: Get the ATC hierarchy for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02BC01 | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_ATC_hierarchy -ATC_CODE 'A02BC01'
# // $[/API]
function get_ATC_hierarchy($ATC_CODE)
{
	$query='SELECT ah1.atc_level, ae.atc_code, ae.atc_title 
	FROM atc_entry ae , atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2
	WHERE ah1.atc_entry_id = ae.atc_entry_id 
	AND ah1.atc_level_left <= ah2.atc_level_left
	AND ah1.atc_level_right >= ah2.atc_level_right
	AND ah2.atc_entry_id = ae2.atc_entry_id
	AND ae2.atc_code=\''.$ATC_CODE.'\'
	ORDER BY atc_level ASC';
	$res= runQuery($query);
	return $res;
}


# // $[API]
# // Title: get ATC code children
# // Function: get_ATC_child_hierarchy
# // Description: Get the children ATC code for a given ATC code
# // Parameter: ATC_CODE | ATC code | string | A02 | required
# // Return: ATC level, ATC code, ATC title
# // Ecosystem: Drug_clinical_trial:drug
# // Example: php biorels_api.php get_ATC_child_hierarchy -ATC_CODE 'A02'
# // $[/API]
function get_ATC_child_hierarchy($ATC_CODE)
{
	$query='SELECT ah1.atc_level, ae.atc_code, ae.atc_title 
	FROM atc_entry ae , atc_hierarchy ah1, atc_hierarchy ah2, atc_entry ae2
	WHERE ah1.atc_entry_id = ae.atc_entry_id 
	AND ah1.atc_level_left >= ah2.atc_level_left
	AND ah1.atc_level_right <= ah2.atc_level_right
	AND ah2.atc_entry_id = ae2.atc_entry_id
	AND ae2.atc_code=\''.$ATC_CODE.'\'
	ORDER BY atc_level ASC';
	$res= runQuery($query);
	return $res;
}






///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////// ASSAY //////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: search assay by name
# // Function: search_assay_by_name
# // Description: Search assay by name
# // Parameter: NAME | Assay name | string | BRCA1 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php search_assay_by_name -NAME 'BRCA1'
# // $[/API]
function search_assay_by_name($NAME,$COMPLETE=false)
{
	$query="SELECT assay_name,source_name FROM assay_entry ae, source s 
	WHERE s.source_id = ae.source_id AND
	LOWER(assay_name) LIKE LOWER('%".$NAME."%')";
	$res=runQuery($query);
	
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name'],$COMPLETE);
	}
	return $data;
}

# // $[API]
# // Title: search assay by description
# // Function: search_assay_by_description
# // Description: Search assay by description
# // Parameter: DESC | Assay description | string | BRCA1 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php search_assay_by_description -DESC 'BRCA1'
# // $[/API]
function search_assay_by_description($DESC,$COMPLETE=false)
{
	$query="SELECT assay_name,source_name
	 FROM assay_entry ae, source s 
	 WHERE s.source_id = ae.source_id 
	 AND  LOWER(assay_description) LIKE LOWER('%".$DESC."%')";
	$res=runQuery($query);
	if (!$COMPLETE) return $res;
	$data=array();
	$list=array();
	foreach ($res as $line)
	{
		$list[$line['source_name']][]=$line['assay_name'];
	}
	$data=array();
	foreach ($list as $source=>&$LIST_L)
	{
		$tmp=get_assay_information($LIST_L,$source,$COMPLETE);
		foreach ($tmp as $K)$data[]=$K;
	}
	
	return $data;
}



# // $[API]
# // Title: list assay types
# // Function: list_assay_type
# // Description: Provide a list of assay types and the number of assays for each type (Functional, Binding, etc.)
# // Return: Assay type, number of assays
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php list_assay_type
# // $[/API]

function list_assay_type()
{
	$query='SELECT assay_desc, count(*) n_assay
	FROM assay_type at, assay_entry a
	WHERE at.assay_type_id = a.assay_type
	GROUP BY assay_desc
	ORDER BY count(*) DESC';
	$res=runQuery($query);
	return $res;

}

# // $[API]
# // Title: search assay by type
# // Function: search_assay_by_type
# // Description: Search assay by type
# // Parameter: ASSAY_TYPE | Assay type | string | Binding | required
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php search_assay_by_type -ASSAY_TYPE 'Binding'
# // $[/API]
function search_assay_by_type($ASSAY_TYPE)
{
	$query="SELECT assay_name,source_name 
	FROM assay_entry ae,assay_type at, source s
	 WHERE at.assay_type_id = ae.assay_type
	 AND s.source_id = ae.source_id
	 AND LOWER(assay_desc) LIKE LOWER('%".$ASSAY_TYPE."%')";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name']);
	}
	return $data;

}


# // $[API]
# // Title: list all assay categories and the number of assays for each category
# // Function: list_assay_category
# // Description: Provide a list of assay categories and the number of assays for each category
# // Return: Assay category name, number of assays
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php list_assay_category
# // $[/API]
function list_assay_category()
{
	$query='SELECT assay_category, count(*) n_assay
	FROM assay_entry
	GROUP BY assay_category
	ORDER BY count(*) DESC';
	$res=runQuery($query);
	return $res;
}


# // $[API]
# // Title: search assay by category
# // Function: search_assay_by_category
# // Description: Search assay by category
# // Parameter: CATEGORY | Assay category | string | Screening | required
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php search_assay_by_category -CATEGORY 'Screening'
# // $[/API]
function search_assay_by_category($CATEGORY)
{
	$query="SELECT assay_name, sourcE_name FROM assay_entry ae,source s

	 WHERE s.source_id = ae.source_id
	AND  LOWER(assay_category) LIKE LOWER('%".$CATEGORY."%')";
	$res=runQuery($query);
	$data=array();
	foreach ($res as &$line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name']);
	}
	return $data;
}




# // $[API]
# // Title: get assay information
# // Function: get_assay_information
# // Description: Get all information about an assay
# // Parameter: ASSAY_NAME | Assay NAME | string | CHEMBL944488,CHEMBL944489 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record with taxon, assay type, assay tissue, assay cell, assay target, confidence
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php get_assay_information -ASSAY_NAME 'CHEMBL944488' -SOURCE_NAME ChEMBL
# // Example: php biorels_api.php get_assay_information -ASSAY_NAME 'CHEMBL944488,CHEMBL944489' -SOURCE_NAME ChEMBL
# // Example: php biorels_api.php get_assay_information -ASSAY_NAME 'CHEMBL944488,CHEMBL944489' -SOURCE_NAME ChEMBL -COMPLETE true
# // $[/API]
function get_assay_information($ASSAY_NAME,$SOURCE_NAME,$COMPLETE=false)
{
	$LIST=array();
	if (is_string($ASSAY_NAME))$LIST=explode(",",$ASSAY_NAME);
	else $LIST=$ASSAY_NAME;
	
	$query="SELECT s.source_name, ae.* FROM source s,assay_entry ae
	LEFT JOIN taxon t on t.taxon_id = ae.taxon_id
	LEFT JOIN assay_type at ON at.assay_type_id = ae.assay_type
	LEFT JOIN assay_Confidence cs ON cs.confidence_score = ae.confidence_score
	WHERE s.source_id = ae.source_id 
	AND LOWER(source_name)='".strtolower($SOURCE_NAME)."'
	AND assay_name  IN ('".implode("','",$LIST)."')";
	$res=runQuery($query);
	if (!$COMPLETE)return $res;
	
	foreach ($res as &$line)
	{
		$line['cell']=get_assay_cell($line['assay_name'],$line['source_name']);
		$line['tissue']=get_assay_tissue($line['assay_name'],$line['source_name']);
		$line['target']=get_assay_target($line['assay_name'],$line['source_name']);
		$line['variant']=get_assay_variant($line['assay_name'],$line['source_name']);
		
	}
	
	return $res;
}


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - TAXON //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: search assay by organism
# // Function: search_assay_by_taxon
# // Description: Search assay by organism, using the NCBI taxonomy ID
# // Parameter: NCBI_TAX_ID | NCBI taxonomy ID | string | 9606 |  required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Genomics:taxon
# // Example: php biorels_api.php search_assay_by_taxon -NCBI_TAX_ID '9606'
# // $[/API]
function search_assay_by_taxon($NCBI_TAX_ID,$COMPLETE=false)
{
	$query="SELECT assay_name, source s 
	FROM assay_entry ae,source s, taxon t
	 WHERE t.taxon_id = ae.taxon_id
	 AND s.source_id = ae.source_id
	 AND tax_id='".$NCBI_TAX_ID."'";
	$res=runQuery($query);
	$data=array();

	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name'],$COMPLETE);
	}
	return $data;
}


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - TISSUE //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: list assay tissues
# // Function: list_assay_tissue
# // Description: Provide a list of assay tissues and the number of assays for each tissue
# // Return: Assay tissue name, anatomy tag, anatomy name, number of assays
# // Ecosystem: Assay:assay;Disease_anatomy:tissue
# // Example: php biorels_api.php list_assay_tissue
# // $[/API]
function list_assay_tissue()
{
	$query='SELECT assay_tissue_name,anatomy_tag,anatomy_name, count(*) n_assay
	FROM assay_tissue at
	LEFT JOIN anatomy_entry ae ON ae.anatomy_entry_id = at.anatomy_entry_id, assay_entry a
	WHERE at.assay_Tissue_id = a.assay_tissue_id
	GROUP BY assay_tissue_name,anatomy_tag,anatomy_name 
	ORDER BY count(*) DESC';
	$res=runQuery($query);
	return $res;
}

# // $[API]
# // Title: search assay by anatomy tag
# // Function: search_assay_by_anatomy_tag
# // Description: Search assay by anatomy tag
# // Parameter: ANATOMY_TAG | Anatomy tag | string | UBERON_0001004 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:anatomy
# // Example: php biorels_api.php search_assay_by_anatomy_tag -ANATOMY_TAG 'UBERON_0001004'
# // $[/API]
function search_assay_by_anatomy_tag($ANATOMY_TAG,$COMPLETE=false)
{
	$query="SELECT assay_name, source_name FROM assay_entry ae,source s,assay_tissue at,anatomy_entry an
	 WHERE at.assay_tissue_id = ae.assay_tissue_id
	 AND s.source_id = ae.source_id
	 AND at.anatomy_entry_id = an.anatomy_entry_id
	 AND anatomy_tag='".$ANATOMY_TAG."'";
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$COMPLETE);
	}
	return $data;

}






# // $[API]
# // Title: search assay by anatomy name
# // Function: search_assay_by_anatomy_name
# // Description: Search assay by anatomy/tissue name
# // Parameter: ANATOMY_NAME | Anatomy name | string | Valve | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:anatomy
# // Example: php biorels_api.php search_assay_by_anatomy_name -ANATOMY_NAME 'Valve'
# // $[/API]
function search_assay_by_anatomy_name($ANATOMY_NAME,$COMPLETE=false)
{

	$TAGS=search_anatomy_by_name($ANATOMY_NAME);
	
	if ($TAGS==array())
	{
		$TAGS=search_anatomy_by_name($ANATOMY_NAME,false);
		if ($TAGS==array())
		{
			$TAGS=search_anatomy_by_synonym($ANATOMY_NAME);
		}
	}
	$LIST_ID=array();
	foreach ($TAGS as $line)
	{
		$LIST_ID[]=$line['anatomy_entry_id'];
	}
	
	$query="SELECT assay_name, source_name FROM source s, assay_entry ae,assay_tissue at
	LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id
	 WHERE at.assay_tissue_id = ae.assay_tissue_id
	 AND s.source_id = ae.source_id
	 
	 AND (LOWER(assay_Tissue_name) LIKE LOWER('%".$ANATOMY_NAME."%')";
	 if ($LIST_ID!=array())
	 $query.=' OR at.anatomy_entry_id IN ('.implode(",",$LIST_ID).')';
	 $query.=')';
	 
	$res=runQuery($query);
	
	$data=array();
	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name'],$COMPLETE);
	}
	return $data;


}









# // $[API]
# // Title: get assay tissue information
# // Function: get_assay_tissue
# // Description: Get all information about a tissue used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay tissue record
# // Ecosystem: Assay:assay;Disease_anatomy:tissue
# // Example: php biorels_api.php get_assay_tissue -ASSAY_NAME 'CHEMBL967748' -SOURCE_NAME 'CHEMBL'
# // $[/API]
function get_assay_tissue($ASSAY_NAME,$SOURCE_NAME)
{
	$res=runQuery("SELECT at.*,an.anatomy_tag 
	FROM assay_tissue at
	LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id,
	assay_entry ae, source s
	WHERE at.assay_tissue_id = ae.assay_tissue_id
	AND ae.source_id = s.source_id
	AND assay_name='".$ASSAY_NAME."'
	AND LOWER(source_name)='".strtolower($SOURCE_NAME)."'");
	foreach ($res as &$line)
	{
		if ($line['anatomy_tag']!='')
		$line['anatomy']=get_anatomy_information($line['anatomy_tag']);
	}
	return $res;
}





# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - CELL //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: list cell lines with the number of assays
# // Function: list_assay_cell
# // Description: Provide a list of cell lines and the number of assays for each cell line
# // Return: Cell line accession, cell line name, number of assays
# // Ecosystem: Assay:assay;Disease_anatomy:cell line
# // Example: php biorels_api.php list_assay_cell
# // $[/API]
function list_assay_cell()
{
	$query='SELECT ac.cell_name,cell_acc, count(*) n_assay
	FROM assay_cell ac 
	LEFT JOIN cell_Entry ce ON ac.cell_entry_id = ce.cell_entry_id, assay_entry a
	WHERE ac.assay_cell_id = a.assay_Cell_id
	GROUP BY cell_acc,ac.cell_name
	ORDER BY count(*) DESC';
	$res=runQuery($query);
	return $res;
}


# // $[API]
# // Title: search assay by cell line
# // Function: search_assay_by_cell_line
# // Description: Search assay by cell line name, synonym or accession
# // Parameter: CELL_LINE | Cell line name | string | A549 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Disease_anatomy:cell line
# // Example: php biorels_api.php search_assay_by_cell_line -CELL_LINE 'A549'
# // $[/API]
function search_assay_by_cell_line($CELL_LINE,$COMPLETE=false)
{
	$res=search_cell_line(array('NAME'=>array($CELL_LINE)));
	
	if ($res==array()) $res=search_cell_line(array('SYN'=>array($CELL_LINE)));
	if ($res==array()) $res=search_cell_line(array('ACC'=>array($CELL_LINE)));
	
	$query="SELECT assay_cell_id FROM assay_cell ac
	WHERE 
	 cell_name= '".$CELL_LINE."'
	OR cell_description = '".$CELL_LINE."'";
	$res2=runQuery($query);
	if ($res2==array())
	{
		$query="SELECT * FROM assay_cell ac
		WHERE  LOWER(cell_name) LIKE '%".strtolower($CELL_LINE)."%'
		OR LOWER(cell_description) LIKE '%".strtolower($CELL_LINE)."%'";
		$res2=runQuery($query);
	
	}

	if ($res2==array() && $res==array()) return array();
	$data=array();
	$query='SELECT assay_name, source_name 
	FROM assay_entry ae,source s, assay_cell ac
	WHERE s.source_id = ae.source_id
	AND 
	ac.assay_cell_id = ae.assay_cell_id AND (';
	$rules=array();
	if ($res!=array())
	{

		$list=array();
		foreach ($res as &$line)$list[]=$line[0]['cell_entry_id'];
		$rules[]=' ac.cell_entry_id IN ('.implode(",",$list).')';
	}
	if ($res2!=array())
	{
		$list=array();
		foreach ($res2 as &$line)$list[]=$line['assay_cell_id'];
		$rules[]='  ac.assay_cell_id IN ('.implode(",",$list).')';
	}
	$query.=implode(" OR ",$rules).')';
	$res=runQuery($query);
	foreach ($res as $line)
	{
		$data[]=get_assay_information($line['assay_name'],$line['source_name'],$COMPLETE);
	}
	return $data;
	
	
}





# // $[API]
# // Title: get assay cell line information
# // Function: get_assay_cell
# // Description: Get all information about a cell line used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay cell record
# // Ecosystem: Assay:assay;Disease_anatomy:cell line
# // Example: php biorels_api.php get_assay_cell -ASSAY_NAME 'CHEMBL967748' -SOURCE_NAME 'CHEMBL'
# // $[/API]
function get_assay_cell($ASSAY_NAME,$SOURCE_NAME)
{
	$res=runQuery("SELECT ac.*,cell_acc, t.* FROM assay_cell ac
	LEFT JOIN taxon t on t.taxon_id =ac.taxon_Id
	LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ac.cell_entry_Id, 
	assay_entry ae,source s
	WHERE ac.assay_cell_id = ae.assay_cell_id
	AND ae.source_id = s.source_id
	AND assay_name='".$ASSAY_NAME."'
	AND LOWER(source_name)='".strtolower($SOURCE_NAME)."'");
	foreach ($res as &$line)
	{
		$line['cell_line']=get_cell_info($line['cell_acc']);
	}
	return $res;


}





# // $[API]
# // Title: get assay target information
# // Function: get_assay_target
# // Description: Get all information about a target used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL967748 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay target record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php get_assay_target -ASSAY_NAME 'CHEMBL967748' -SOURCE_NAME 'CHEMBL'
# // Example: php biorels_api.php get_assay_target -ASSAY_NAME 'CHEMBL1061685' -SOURCE_NAME 'CHEMBL'
# // $[/API]
function get_assay_target($ASSAY_NAME,$SOURCE_NAME)
{
	$res=runQuery("SELECT at.*,att.*
	FROM assay_target at
	LEFT JOIN taxon t ON t.taxon_id = at.taxon_id,
	assay_target_type att, assay_entry ae,source s
	WHERE att.assay_target_type_id = at.assay_target_type_id
	AND at.assay_target_id = ae.assay_target_id
	AND ae.source_id = s.source_id
	AND assay_name='".$ASSAY_NAME."'
	AND LOWER(source_name)='".strtolower($SOURCE_NAME)."'");
	foreach ($res as &$line)
	{
		$query="SELECT is_homologue,accession, sequence_md5sum, iso_id, gene_id
		FROM assay_target_protein_map atpm, assay_protein ap
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
		LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
		WHERE atpm.assay_protein_id = ap.assay_protein_id
		AND atpm.assay_target_id = ".$line['assay_target_id'];
		$line['protein']=runQuery($query);
		foreach ($line['protein'] as &$l)
		{
			if ($l['gene_id']!='')
			$l['gene']=get_gene_by_gene_id($l['gene_id']);
		if ($l['iso_id']!='')$l['isoform']=get_isoform_info($l['iso_id']);
		}

		$query='SELECT genetic_description,tax_id,gene_seq_name,gene_seq_Version,transcript_name,transcript_version, accession, sequence,gene_id,tax_id,is_homologue
		 FROM assay_Target_genetic_map atgm, assay_genetic ag
		LEFT JOIN taxon t ON t.taxon_id = ag.taxon_id
		LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id
		LEFT JOIN transcript tr ON tr.transcript_id = ag.transcript_id
		WHERE atgm.assay_genetic_id = ag.assay_genetic_id
		AND atgm.assay_target_id = '.$line['assay_target_id'];
		$line['genetic']=runQuery($query);
		foreach ($line['genetic'] as &$l)
		{
			if ($l['tax_id']!='')$l['taxon']=get_taxon_by_tax_id($l['tax_id'],false);
			if ($l['gene_id']!='')$l['gene']=get_gene_by_gene_id($l['gene_id']);
			if ($l['transcript_name']!='')$l['transcript']=search_transcript($l['transcript_name']);

			
		}
	
	}
	return $res;
}


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - VARIANT //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: get assay variant information
# // Function: get_assay_variant
# // Description: Get all information about a variant used in an assay
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL1218392 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Return: Assay variant record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php get_assay_variant -ASSAY_NAME 'CHEMBL1218392' -SOURCE_NAME 'CHEMBL'
# // $[/API]
function get_assay_variant($ASSAY_NAME,$SOURCE_NAME)
{
	$query='SELECT mutation_list,iso_id, ac,av.assay_variant_id FROM assay_Variant av
	LEFT JOIN prot_seq ps ON ps.prot_seq_Id = av.prot_Seq_id, assay_entry ae, source s
	WHERE av.assay_variant_id = ae.assay_variant_id
	AND ae.source_id = s.source_id
	AND assay_name=\''.$ASSAY_NAME.'\'
	AND LOWER(source_name)=\''.strtolower($SOURCE_NAME).'\'';
	$data=runQuery($query);
	
	if ($data==array()) return $data;
	foreach ($data as &$line)
	{
		$line['isoform']=get_isoform_info($line['iso_id']);
		$query='SELECT * 
		FROM assay_variant_pos avp
		LEFT JOIN variant_protein_map vpm ON vpm.variant_protein_id = avp.variant_protein_id
		LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = psp.prot_seq_id
		LEFT JOIN so_entry so ON so.so_entry_Id = vpm.so_entry_id
		WHERE avp.assay_variant_id = '.$line['assay_variant_id'];
		$line['position']=runQuery($query);
	}
	return $data;
}



# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - GENE //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: get all assay by gene
# // Function: get_assay_by_gene
# // Description: Get all assays for a given gene ID
# // Parameter: GENE_ID | Gene ID | string | 1017 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Genomics:gene
# // Example: php biorels_api.php get_assay_by_gene -GENE_ID '1017'
# // $[/API]

function get_assay_by_gene($GENE_ID,$COMPLETE=false)
{
	$query='SELECT DISTINCT assay_name,source_name
	FROM source s,assay_entry ae
	LEFT JOIN assay_target_protein_map atpm ON ae.assay_target_id = atpm.assay_target_id
	LEFT JOIN  assay_protein ap ON atpm.assay_protein_id = ap.assay_protein_id
	LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
	LEFT JOIN gn_prot_map pe ON pe.prot_entry_id = ps.prot_entry_id
	LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
	LEFT JOIN gn_Entry ger ON ger.gn_entry_id = pe.gn_entry_id
	LEFT JOIN assay_Target_genetic_map atgm ON ae.assay_target_id = atgm.assay_target_id
	LEFT JOIN assay_Genetic ag ON ag.assay_genetic_id = atgm.assay_genetic_id
	LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
	LEFT JOIN gn_entry ge2 ON ge2.gn_entry_id = gs.gn_entry_id
	WHERE ae.source_id = s.source_id
	AND (ge.gene_id = '.$GENE_ID.' OR ger.gene_id = '.$GENE_ID.' OR ge2.gene_id = '.$GENE_ID.')';
	$res=runQuery($query);
	if (!$COMPLETE)return $res;
	$data=array();

	$list=array();
	foreach ($res as $line)
	{
		$list[$line['source_name']][]=$line['assay_name'];
	}
	foreach ($list as $SOURCE_NAME=>$ASSAY_NAMES)
	{
		$data[]=get_assay_information($ASSAY_NAMES,$SOURCE_NAME);
	}
	return $data;
}


# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - PROTEIN //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////



# // $[API]
# // Title: get all assay by protein accession
# // Function: get_assay_by_prot_accession
# // Description: Get all assays for a given protein accession
# // Parameter: AC | Protein accession | string | P24941 | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Proteomics:protein
# // Example: php biorels_api.php get_assay_by_prot_accession -AC 'P24941'
# // $[/API]
function get_assay_by_prot_accession($AC,$COMPLETE=false)

{
	$query='SELECT DISTINCT assay_name,source_name
	FROM source s,assay_entry ae, assay_target_protein_map atpm, assay_protein ap
	LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
	LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
	LEFT JOIN prot_Ac pa ON pa.prot_entry_id = pe.prot_entry_id
	WHERE ae.assay_target_id = atpm.assay_target_id
	AND atpm.assay_protein_id = ap.assay_protein_id
	AND ae.source_id = s.source_id
	AND (accession = \''.$AC.'\' OR ac= \''.$AC.'\')';
	

	$res=runQuery($query);
	
	if (!$COMPLETE)return $res;
	$data=array();

	$list=array();
	foreach ($res as $line)
	{
		$list[$line['source_name']][]=$line['assay_name'];
	}
	foreach ($list as $SOURCE_NAME=>$ASSAY_NAMES)
	{
		$data[]=get_assay_information($ASSAY_NAMES,$SOURCE_NAME);
	}
	return $data;
}




# // $[API]
# // Title: search assay by multiple parameter
# // Function: search_assay
# // Description: Search assay by multiple parameters 'DESCRIPTION','TAX_ID','TYPE','CATEGORY','TISSUE','ANATOMY','CELL_ACC','GENE_ID','AC','VARIANT'
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Parameter: COMPLETE | True if complete information is requested | boolean | false | optional | Default: false
# // Multi-array: PARAMS | DESCRIPTION | Assay description | string | BRCA1 | optional
# // Multi-array: PARAMS | TAX_ID | NCBI taxonomy ID | string | 9606 | optional
# // Multi-array: PARAMS | TYPE | Assay type | string | Binding | optional
# // Multi-array: PARAMS | CATEGORY | Assay category | string | Screening | optional
# // Multi-array: PARAMS | TISSUE | Tissue name | string | Valve | optional
# // Multi-array: PARAMS | ANATOMY_TAG | Anatomy tag | string | UBERON_0001004 | optional
# // Multi-array: PARAMS | CELL_ACC | Cell line name | string | A549 | optional
# // Multi-array: PARAMS | GENE_ID | Gene ID | string | 1017 | optional
# // Multi-array: PARAMS | AC | Protein accession | string | P24941 | optional
# // Multi-array: PARAMS | VARIANT | Variant | string | P24941 | optional
# // Return: Assay record
# // Ecosystem: Assay:assay
# // Example: php biorels_api.php search_assay -PARAMS 'DESCRIPTION=BRCA1'
# // Example: php biorels_api.php search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606'
# // Example: php biorels_api.php search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606,TYPE=Binding'
# // Example: php biorels_api.php search_assay -PARAMS 'DESCRIPTION=BRCA1,TAX_ID=9606,TYPE=Binding,CATEGORY=Functional'
# // $[/API]
function search_assay($PARAMS,$COMPLETE=false)
{
	$TABLES=array('assay_entry ae'=>true,'source s'=>true);
	$CONDITIONS=array();
	$JOIN=array('ae.source_id = s.source_id'=>true);
	$PRESEL=array();
	foreach ($PARAMS as $KEY=>&$VALUES)
	{
		switch ($KEY)
		{
			case 'DESCRIPTION':
				$s= '%\') OR LOWER(ae.assay_description) LIKE LOWER(\'%' ;
				$CONDITIONS[]='( LOWER(ae.assay_description) LIKE LOWER(\'%'.implode($s,$VALUES).'%\'))';
				break;
			case 'NAME':
				$s= '%\') OR LOWER(ae.assay_name) LIKE LOWER(\'%' ;
				$CONDITIONS[]='( LOWER(ae.assay_name) LIKE LOWER(\'%'.implode($s,$VALUES).'%\'))';
				break;
			case 'TAX_ID':
				$TABLES['taxon t']=true;
				$JOIN['t.taxon_id = ae.taxon_id']=true;
				$CONDITIONS[]='t.tax_id IN (\''.implode("','",$VALUES).'\')';
				break;
			case 'SOURCE':
				$CONDITIONS[]='LOWER(s.source_name) IN (\''.implode("','",array_map('strtolower',$VALUES)).'\')';
				break;
			case 'TYPE':
				$TABLES['assay_type at']=true;
				$JOIN['at.assay_type_id = ae.assay_type']=true;
				$CONDITIONS[]='LOWER(at.assay_desc) IN (\''.implode("','",array_map('strtolower',$VALUES)).'\')';
				break;
			case 'CATEGORY':
				$CONDITIONS[]='LOWER(ae.assay_category) IN (\''.implode("','",array_map('strtolower',$VALUES)).'\')';
				break;
			case 'ANATOMY_TAG':
				$TABLES['assay_tissue at']=true;
				$TABLES['anatomy_entry an']=true;
				$JOIN['at.assay_tissue_id = ae.assay_tissue_id']=true;
				$JOIN['at.anatomy_entry_id = an.anatomy_entry_id']=true;
				$CONDITIONS[]='an.anatomy_tag IN (\''.implode("','",$VALUES).'\')';
				break;
			case 'TISSUE':
				$TABLES['assay_tissue at2 LEFT JOIN anatomy_entry an2 ON at2.anatomy_entry_id= an2.anatomy_entry_id']=true;
				$JOIN['at2.assay_tissue_id = ae.assay_tissue_id']=true;
				$s= '%\') OR LOWER(at2.assay_tissue_name) LIKE LOWER(\'%' ;
				$s2= '%\') OR LOWER(an2.anatomy_name) LIKE LOWER(\'%' ;
				$CONDITIONS[]='( LOWER(at2.assay_tissue_name) LIKE LOWER(\'%'.implode($s,$VALUES).'%\') OR LOWER(an2.anatomy_name) LIKE LOWER(\'%'.implode($s2,$VALUES).'%\'))';
				break;
			case 'CELL_ACC':
				$list=array();
				foreach ($VALUES as $V)
				{
					$s=search_assay_by_cell_line($V);	
					foreach ($s as $l)$list[$l['assay_name'].'||'.$l['source_name']]=true;
				}
				if ($list==array()) return array();
				if ($PRESEL==array())	$PRESEL=$list;
				else
				{
					foreach ($PRESEL as $K=>&$V)
					{
						if (!isset($list[$K]))$V=false;
					}
				}
				
				
			break;		
			case 'GENE_ID':
				
				$list=array();
				foreach ($VALUES as $V)
				{
					$s=get_assay_by_gene($V);	
					foreach ($s as $l)$list[$l['assay_name'].'||'.$l['source_name']]=true;
				}
				if ($list==array()) return array();
				if ($PRESEL==array())	$PRESEL=$list;
				else
				{
					foreach ($PRESEL as $K=>&$V)
					{
						if (!isset($list[$K]))$V=false;
					}
				}
				break;
			case 'AC':
				$list=array();
				foreach ($VALUES as $V)
				{
					$s=get_assay_by_prot_accession($V);	
					foreach ($s as $l)$list[$l['assay_name'].'||'.$l['source_name']]=true;
				}
				if ($list==array()) return array();
				if ($PRESEL==array())	$PRESEL=$list;
				else
				{
					foreach ($PRESEL as $K=>&$V)
					{
						if (!isset($list[$K]))$V=false;
					}
				}
				break;

					

		}

	}
	
	if ($CONDITIONS!=array())
	{
		$query='SELECT DISTINCT assay_name,source_name FROM '."\n".implode("\n,",array_keys($TABLES)).' WHERE '.implode("\n AND ",$CONDITIONS).' AND '.implode("\n AND ",array_keys($JOIN));

		$res=runQuery($query);
		if ($PRESEL==array())
		{
			$list=array();
			foreach ($res as $line)
			{
				$list[$line['assay_name'].'||'.$line['source_name']]=true;
			}
			$PRESEL=$list;
		}
		else
		{
			$list=array();
			foreach ($res as $line)
			{
				$list[$line['assay_name'].'||'.$line['source_name']]=true;
			}
			foreach ($PRESEL as $K=>&$V)
			{
				if (!isset($list[$K]))$V=false;
			}
		}
	}
	$data=array();
	//print_R($PRESEL);
	if (!$COMPLETE)
	{
		foreach ($PRESEL as $K=>$V) 
		{
			if (!$V)continue;
			$T=explodE("||",$K);
			$data[]=array('ASSAY_NAME'=>$T[0],'SOURCE'=>$T[1]);
		}
		
		return $data;
	}
	
	foreach ($PRESEL as $K=>$V)
	{
		if (!$V)continue;
		
		$T=explode("||",$K);
		$data[]=get_assay_information($T[0],$T[1]);
	
	}
	return $data;
}




# ///////////////////////////////////////////////////////////////////////////////////////////////////
# ////////////////////////////////////////// ASSAY - ACTIVITY //////////////////////////////////////////
# ///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: get assay activity
# // Function: get_assay_activity
# // Description: Get all activity for a given assay name and source name
# // Parameter: ASSAY_NAME | Assay name | string | CHEMBL1218392 | required
# // Parameter: SOURCE_NAME | Source name | string | ChEMBL | required
# // Parameter: WITH_STRUCTURE | True if full molecular entity information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity
# // Example: php biorels_api.php get_assay_activity -ASSAY_NAME 'CHEMBL1218392' -SOURCE_NAME 'CHEMBL'
# // $[/API]
function get_assay_activity($ASSAY_NAME,$SOURCE_NAME,$WITH_STRUCTURE=false)
{
	$query='SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id
	 FROM molecular_entity me,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,assay_entry ae,source s
	WHERE aa.assay_entry_id = ae.assay_entry_id
	AND me.molecular_entity_id = aa.molecular_entity_id
	AND ae.source_id = s.source_id
	AND assay_name=\''.$ASSAY_NAME.'\'
	AND LOWER(source_name)=\''.strtolower($SOURCE_NAME).'\'';
	$res=runQuery($query);
	if (!$WITH_STRUCTURE)return $res;
	foreach ($res as &$line)
	{
		$line['molecular_entity']=get_molecular_entity($line['molecular_entity_hash']);
	}
	return $res;
}




function get_batch_assay_info($SOURCE_NAME,$LIST_ASSAY,$WITH_EXTENDED_ASSAY_INFO=false)
{
	
	
	$query="SELECT s.source_name, ae.*,scientific_name,tax_id,bo.*
	 FROM source s,assay_entry ae
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= ae.bioassay_onto_entry_id
	LEFT JOIN taxon t on t.taxon_id = ae.taxon_id
	LEFT JOIN assay_type at ON at.assay_type_id = ae.assay_type
	LEFT JOIN assay_Confidence cs ON cs.confidence_score = ae.confidence_score
	WHERE s.source_id = ae.source_id 
	AND LOWER(source_name)='".strtolower($SOURCE_NAME)."'
	AND assay_name  IN ('".implode("','",$LIST_ASSAY)."')";
	$tmp=runQuery($query);
	if (!$WITH_EXTENDED_ASSAY_INFO)return $tmp;
	$data=array();
	$REL=array('ASSAY_CELL'=>array(),'ASSAY_TISSUE'=>array(),'ASSAY_TARGET'=>array(),'ASSAY_VARIANT'=>array());
	foreach ($tmp as $K=>$V)
	{
		$data[$K]=$V;
		if ($V['assay_cell_id']!='')$REL['ASSAY_CELL'][$V['assay_cell_id']][]=$K;
		if ($V['assay_tissue_id']!='')$REL['ASSAY_TISSUE'][$V['assay_tissue_id']][]=$K;
		if ($V['assay_target_id']!='')$REL['ASSAY_TARGET'][$V['assay_target_id']][]=$K;
		if ($V['assay_variant_id']!='')	$REL['ASSAY_VARIANT'][$V['assay_variant_id']][]=$K;
	}
	$tmp=null;
	
	if ($REL['ASSAY_CELL']!=array())
	{
		$res=runQuery("SELECT assay_cell_id, ce.cell_name,cell_description,
		cell_source_tissue,chembl_id as assay_cell_chembl_id,
		cell_acc,scientific_name as cell_taxon_scientific_name,tax_id as cell_Tax_id, rank as cell_tax_rank
		FROM assay_cell ac
		LEFT JOIN taxon t on t.taxon_id =ac.taxon_Id
		LEFT JOIN cell_entry ce ON ce.cell_entry_Id = ac.cell_entry_Id
		WHERE assay_cell_id IN (".implode(",",array_keys($REL['ASSAY_CELL'])).")");
		
		foreach ($res as &$line)
		{
			foreach ($REL['ASSAY_CELL'][$line['assay_cell_id']] as $K)
			{
				foreach ($line as $X=>$V2)
				$data[$K][$X]=$V2;
					
			}
		}
		
		
	}
	if ($REL['ASSAY_TISSUE']!=array())
	{
		$res=runQuery("SELECT anatomy_tag,anatomy_name,assay_tissue_name,assay_tissue_id
		FROM assay_tissue at
		LEFT JOIN anatomy_entry an ON an.anatomy_entry_id = at.anatomy_entry_id
		WHERE assay_tissue_id IN (".implode(",",array_keys($REL['ASSAY_TISSUE'])).")");
		
		foreach ($res as &$line)
		{

			//if ($line['anatomy_tag']!='') $line['anatomy']=get_anatomy_information($line['anatomy_tag']);
			foreach ($REL['ASSAY_TISSUE'][$line['assay_tissue_id']] as $K)
			{
				foreach ($line as $X=>$V2)
				$data[$K][$X]=$V2;
			
			}

		}
	}

	if ($REL['ASSAY_TARGET']!=array())
	{
		$tmp=runQuery("SELECT assay_target_id,assay_target_name,assay_target_longname,
		species_group_flag,assay_target_Type_name,assay_target_type_desc,
		assay_Target_type_parent,scientific_name as assay_target_taxon_name,
		tax_id as assay_target_tax_id,rank as assay_target_tax_rank
		FROM assay_target at
		LEFT JOIN taxon t ON t.taxon_id=at.taxon_id
		LEFT JOIN assay_target_type att ON att.assay_target_type_id = at.assay_target_type_id
		WHERE assay_target_id IN (".implode(",",array_keys($REL['ASSAY_TARGET'])).")");
		$targets=array();
		foreach ($tmp as $line)$targets[$line['assay_target_id']]=$line;
		
	
		$query="SELECT is_homologue,accession, sequence_md5sum, iso_id, gene_id,atpm.assay_target_id
		FROM assay_target_protein_map atpm, assay_protein ap
		LEFT JOIN prot_seq ps ON ps.prot_seq_id = ap.prot_seq_id
		LEFT JOIN prot_entry pe ON pe.prot_entry_id = ps.prot_entry_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = ap.gn_entry_id
		WHERE atpm.assay_protein_id = ap.assay_protein_id
		AND atpm.assay_target_id IN (".implode(",",array_keys($REL['ASSAY_TARGET'])).")";
		$tmp=runQuery($query);
		foreach ($tmp as &$l)
		{
			if ($l['gene_id']!='') $l['gene']=get_gene_by_gene_id($l['gene_id']);
			if ($l['iso_id']!='')$l['isoform']=get_isoform_info($l['iso_id']);
			$targets[$l['assay_target_id']]['protein'][]=$l;
		}

		$query="SELECT genetic_description,tax_id,gene_seq_name,gene_seq_Version,
		transcript_name,transcript_version, accession, sequence,gene_id,tax_id,is_homologue, atgm.assay_target_id
		 FROM assay_Target_genetic_map atgm, assay_genetic ag
		LEFT JOIN taxon t ON t.taxon_id = ag.taxon_id
		LEFT JOIN gene_seq gs ON gs.gene_seq_id = ag.gene_seq_id
		LEFT JOIN gn_entry ge ON ge.gn_entry_id = gs.gn_entry_id
		LEFT JOIN transcript tr ON tr.transcript_id = ag.transcript_id
		WHERE atgm.assay_genetic_id = ag.assay_genetic_id
		AND atgm.assay_target_id IN (".implode(",",array_keys($REL['ASSAY_TARGET'])).")";
		$tmp=runQuery($query);
		foreach ($tmp as &$l)
		{
			if ($l['tax_id']!='')$l['taxon']=get_taxon_by_tax_id($l['tax_id'],false);
			if ($l['gene_id']!='')$l['gene']=get_gene_by_gene_id($l['gene_id']);
			if ($l['transcript_name']!='')$l['transcript']=search_transcript($l['transcript_name']);
			$targets[$l['assay_target_id']]['genetic'][]=$l;
			
		}
	
		if ($targets!=array())
		{
			foreach ($targets as $line)
			{
				//print_r($line);
				foreach ($REL['ASSAY_TARGET'][$line['assay_target_id']] as $K)
				{
					foreach ($line as $X=>$V2)
					
					$data[$K][$X]=$V2;
				// if (isset($line['genetic']))$data[$K]['genetic']=$line['genetic'];
				// if (isset($line['protein']))$data[$K]['protein']=$line['protein'];
				//exit;
				}
			}
		}
	
	}

	if ($REL['ASSAY_VARIANT']!=array())
	{
		$query='SELECT mutation_list,iso_id, ac,av.assay_variant_id FROM assay_Variant av
		LEFT JOIN prot_seq ps ON ps.prot_seq_Id = av.prot_Seq_id
		WHERE av.assay_variant_id IN ('.implode(",",array_keys($REL['ASSAY_VARIANT'])).')';
		$tmp=runQuery($query);
		if ($tmp!=array())
		{
			foreach ($tmp as &$line)
			{
				$line['isoform']=get_isoform_info($line['iso_id']);
				$query='SELECT * 
				FROM assay_variant_pos avp
				LEFT JOIN variant_protein_map vpm ON vpm.variant_protein_id = avp.variant_protein_id
				LEFT JOIN prot_seq_pos psp ON psp.prot_seq_pos_id = vpm.prot_seq_pos_id
				LEFT JOIN prot_seq ps ON ps.prot_seq_id = psp.prot_seq_id
				LEFT JOIN so_entry so ON so.so_entry_Id = vpm.so_entry_id
				WHERE avp.assay_variant_id = '.$line['assay_variant_id'];
				$line['position']=runQuery($query);
			}
			foreach ($tmp as $line)
			{
				foreach ($REL['ASSAY_VARIANT'][$line['assay_variant_id']] as $K)
				{
					$data[$K]['variant'][]=$line;
				}
			}
		}
	}
	

	return $data;	
}





# // $[API]
# // Title: get assay activity by molecular entity
# // Function: get_molecular_entity_activity
# // Description: Get all activity for a given molecular entity hash
# // Parameter: MOLECULAR_ENTITY_HASH | Molecular entity hash | string | d8c4c21996d99a71d75cf788d964b6cf | required
# // Parameter: WITH_ASSAY | True if full assay information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity
# // Example: php biorels_api.php get_molecular_entity_activity -MOLECULAR_ENTITY_HASH 'd8c4c21996d99a71d75cf788d964b6cf'
# // $[/API]
function get_molecular_entity_activity($MOLECULAR_ENTITY_HASH,$WITH_ASSAY=false,$WITH_EXTENDED_ASSAY_INFO=false)
{
	$query='SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id, source_name,assay_name
	 FROM molecular_entity me,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,
	 assay_entry ae,source s
	WHERE aa.molecular_entity_id = me.molecular_entity_id
	AND ae.assay_entry_id = aa.assay_entry_id
	AND ae.source_id = s.source_id
	AND me.molecular_entity_hash = \''.$MOLECULAR_ENTITY_HASH.'\'';
	$res=runQuery($query);
	if ($WITH_ASSAY)
	{

		$LIST_ASSAY=array();
		foreach ($res as $K=> &$line)
		{
			$LIST_ASSAY[$line['source_name']][$line['assay_name']][]=$K;
		}
		foreach ($LIST_ASSAY as $SOURCE_NAME=>&$LIST)
		{
			$TMP=get_batch_assay_info($SOURCE_NAME,array_keys($LIST),$WITH_EXTENDED_ASSAY_INFO);
			foreach ($TMP as $K=>$V)
			{
				foreach ($V as $X=>$V2)
				{
					$res[$K][$X]=$V2;
				}
			}
		}
		
	}
		
	
	return $res;
}









# // $[API]
# // Title: get scaffold activity
# // Function: get_scaffold_activity
# // Description: Get all activity for a given scaffold smiles
# // Parameter: SCAFFOLD_SMILES | Scaffold smiles | string | c1ncncc1 | required
# // Parameter: WITH_ASSAY | True if full assay information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_EXTENDED_ASSAY_INFO | True if extended assay information is requested | boolean | false | optional | Default: false
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity|scaffold
# // Example: php biorels_api.php get_scaffold_activity -SCAFFOLD_SMILES 'c1ncncc1'
# // $[/API]
function get_scaffold_activity($SCAFFOLD_SMILES,$WITH_ASSAY=false,$WITH_EXTENDED_ASSAY_INFO=false)
{
	$query='SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id, source_name,assay_name
	 FROM molecular_entity me,sm_entry se, sm_molecule sm, sm_scaffold sc,activity_entry aa
	 LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,
	 assay_entry ae,source s
	WHERE aa.molecular_entity_id = me.molecular_entity_id
	AND me.molecular_structure_hash=se.md5_hash
	AND sm.sm_scaffold_id = sc.sm_scaffold_id
	AND se.sm_molecule_id = sm.sm_molecule_id
	AND sc.scaffold_smiles = \''.$SCAFFOLD_SMILES.'\'';
	$res=runQuery($query);
	if (!$WITH_ASSAY)return $res;
	

	$LIST_ASSAY=array();
	foreach ($res as $K=> &$line)
	{
		$LIST_ASSAY[$line['source_name']][$line['assay_name']][]=$K;
	}
	foreach ($LIST_ASSAY as $SOURCE_NAME=>&$LIST)
	{
		$TMP=get_batch_assay_info($SOURCE_NAME,array_keys($LIST),$WITH_EXTENDED_ASSAY_INFO);
		foreach ($TMP as $K=>$V)
		{
			foreach ($V as $X=>$V2)
			{
				$res[$K][$X]=$V2;
			}
		}
	}
	
	return $res;

}




# // $[API]
# // Title: get activity by molecule name
# // Function: get_activity_by_molecule_name
# // Description: Get all activity for a given molecule name
# // Parameter: MOLECULE_NAME | Molecule name | string | CHEMBL1218392 | required
# // Return: Assay activity record
# // Ecosystem: Assay:assay;Molecular entity:molecular entity|activity
# // Example: php biorels_api.php get_activity_by_molecule_name -MOLECULE_NAME 'CHEMBL1218392'
# // $[/API]
function get_activity_by_molecule_name($MOLECULE_NAME)
{
	$data=search_small_molecule_by_name($MOLECULE_NAME);
	if ($data==array())return array();
	$MAP=array();
	foreach ($data as $K=>&$line)
	{
		$MAP[$line['md5_hash']]=$K;
	}

	$MAP_A=array();
	$query='SELECT me.*,aa.*,bo.bioassay_label, bo.bioassay_tag_id, source_name,assay_name
	FROM molecular_entity me, activity_entry aa
	LEFT JOIN bioassay_onto_entry bo ON bo.bioassay_onto_entry_Id= bao_endpoint,
	assay_entry ae,source s
	WHERE aa.molecular_entity_id = me.molecular_entity_id
	AND aa.assay_entry_id = ae.assay_entry_id
	AND ae.source_id = s.source_id
	AND me.molecular_structure_hash  IN (\''.implode("','",array_keys($MAP)).'\')';
	$res=runQuery($query);
	
	foreach ($res as $l2)
	{
		$K=$MAP[$l2['molecular_structure_hash']];
		
			
		if (!isset($data[$K]['ACTIVITY']))
		{
			$data[$K]['ACTIVITY']=array($l2);
			$MAP_A[$l2['source_name']][$l2['assay_name']][]=array($K,0);
		}
		else
		{
			$MAX=max(array_keys($data[$K]['ACTIVITY']))+1;
			$MAP_A[$l2['source_name']][$l2['assay_name']][]=array($K,$MAX);
			$data[$K]['ACTIVITY'][$MAX]=$l2;
		}
		
	}



	foreach ($MAP_A as $SOURCE_NAME=>&$LIST)
	{
		$TMP=get_batch_assay_info($SOURCE_NAME,array_keys($LIST));
		foreach ($TMP as $K=>$V)
		{
			foreach ($LIST as $ASSAY_NAME=>&$POS)
			{
				foreach ($POS as $POS_INFO)
				{
					foreach ($V as $X=>$V2)
					$data[$POS_INFO[0]]['ACTIVITY'][$POS_INFO[1]][$X]=$V2;
				}

			}
			
		}
	}
	return $data;
		

	

}










///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////// SCIENTIFIC ECOSYSTEM ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// JOURNAL ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: get list of journals
# // Function: get_list_journals
# // Description: Get list of journals
# // Return: Journal record
# // Ecosystem: Scientific_community:journal
# // Example: php biorels_api.php get_list_journals
# // $[/API]
function get_list_journals()
{
	$query='SELECT * FROM pmid_journal';
	return runQuery($query);

}

# // $[API]
# // Title: search journal by name
# // Function: search_journal_by_name
# // Description: Search journal by name
# // Parameter: NAME | Journal name | string | Nature | required
# // Return: Journal record
# // Ecosystem: Scientific_community:journal
# // Example: php biorels_api.php search_journal_by_name -NAME 'Nature'
# // $[/API]
function search_journal_by_name($NAME)
{
	$query='SELECT * FROM pmid_journal WHERE journal_name =\''.$NAME.'\'';
	$res= runQuery($query);
	if ($res!=array())return $res;
	$query='SELECT * FROM pmid_journal WHERE LOWER(journal_name) LIKE \'%'.strtolower($NAME).'%\'';
	$res= runQuery($query);
	if ($res!=array())return $res;

	$query='SELECT * FROM pmid_journal WHERE LOWER(journal_abbr) LIKE \'%'.strtolower($NAME).'\'';
	return runQuery($query);
}


# // $[API]
# // Title: search journal by ISSN
# // Function: search_journal_by_issn
# // Description: Search journal by ISSN
# // Parameter: ISSN | ISSN | string | 0028-0836 | required
# // Return: Journal record
# // Ecosystem: Scientific_community:journal
# // Example: php biorels_api.php search_journal_by_issn -ISSN '0028-0836'
# // $[/API]
function search_journal_by_issn($ISSN)
{
	$query='SELECT * FROM pmid_journal WHERE issn_print =\''.$ISSN.'\' OR issn_online =\''.$ISSN.'\'';
	return runQuery($query);
}



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// INSTITUTION ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

# // $[API]
# // Title: search institution by name
# // Function: search_institution_by_name
# // Description: Search institution by name
# // Parameter: NAME | Institution name | string | INSERM | required
# // Return: Institution record
# // Ecosystem: Scientific_community:institution
# // Example: php biorels_api.php search_institution_by_name -NAME 'INSERM'
# // $[/API]
function search_institution_by_name($NAME)
{
	$query='SELECT * FROM pmid_instit WHERE LOWER(instit_name) LIKE \'%'.strtolower($NAME).'%\'';
	return runQuery($query);
}



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// AUTHOR ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: search author by name
# // Function: search_author_by_name
# // Description: Search author by name
# // Parameter: LAST_NAME | Last name | string | Desaphy | required
# // Parameter: FIRST_NAME | First name | string | Jeremy | required
# // Parameter: INSTITUTION | Institution name | string | Strasbourg | optional
# // Return: Author record
# // Ecosystem: Scientific_community:author
# // Example: php biorels_api.php search_author_by_name -LAST_NAME 'Desaphy' -FIRST_NAME 'Jeremy'
# // $[/API]
function search_author_by_name($LAST_NAME,$FIRST_NAME,$INSTITUTION='')
{
	

	$query='SELECT * FROM pmid_author pa, pmid_instit pi WHERE pa.pmid_instit_id = pi.pmid_instit_id 
	AND LOWER(last_name) = \''.strtolower($LAST_NAME).'\' 
	AND LOWER(first_name) = \''.strtolower($FIRST_NAME).'\'';
	
	//exit;
	$res= runQuery($query);
	if ($INSTITUTION=='')return $res;
	foreach ($res as $K=>$line)
	{
		if (strpos(strtolower($line['instit_name']),strtolower($INSTITUTION))!==false)continue;
		unset($res[$K]);
	}
	return $res;
}


# // $[API]
# // Title: search author by ORCID
# // Function: search_author_by_orcid_id
# // Description: Search author by ORCID
# // Parameter: ORCID | ORCID | string | 0000-0002-1694-233X | required
# // Return: Author record
# // Ecosystem: Scientific_community:author
# // Example: php biorels_api.php search_author_by_orcid_id -ORCID '0000-0002-1694-233X'
# // $[/API]

function search_author_by_orcid_id($ORCID)
{
	$query='SELECT * FROM pmid_author WHERE orcid_id =\''.$ORCID.'\'';
	return runQuery($query);
}



///////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////// PUBLICATION ///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////


# // $[API]
# // Title: search publication
# // Function: search_publication
# // Description: Search publication by multiple parameters 'PMID','DOI','TITLE','ABSTRACT','JOURNAL','ISSN','YEAR_START','YEAR_END','ORCID','INSTITUTION'
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Return: Publication record
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php search_publication -PARAMS 'TITLE=BRCA1'
# // Example: php biorels_api.php search_publication -PARAMS 'TITLE=BRCA1,INSTITUTION=INSERM'
# // $[/API]
function search_publication($PARAMS)
{
	$TABLES=array('pmid_entry pe'=>true);
	$CONDITIONS=array();
	$JOIN=array();
	$INSTITUTION=array();
	foreach ($PARAMS as $KEY=>&$VALUES)
	{
		switch ($KEY)
		{
			case 'PMID':
				$CONDITIONS[]='pe.pmid IN ('.implode(',',$VALUES).')';
				break;
			case 'DOI':
				$CONDITIONS[]='pe.doi IN ('.implode(',',$VALUES).')';
				break;
			case 'TITLE':
				$s= '%\') OR LOWER(pe.title) LIKE LOWER(\'%' ;
				$CONDITIONS[]='( LOWER(pe.title) LIKE LOWER(\'%'.implode($s,$VALUES).'%\'))';
				break;
			case 'ABSTRACT':
				$TABLES['pmid_abstract pa']=true;
				$JOIN['pe.pmid_entry_id = pa.pmid_entry_id']=true;
				$s= '%\') OR LOWER(pe.abstract_text) LIKE LOWER(\'%' ;
				$CONDITIONS[]='( LOWER(pe.abstract_text) LIKE LOWER(\'%'.implode($s,$VALUES).'%\'))';
				break;
			case 'JOURNAL':
				$JOURNALS=array();
				foreach ($VALUES as $V)
				{
					$s=search_journal_by_name($V);
					foreach ($s as $l)$JOURNALS[$l['pmid_journal_id']]=true;
				}
				if ($JOURNALS==array()) return array();
				
				$TABLES['pmid_journal pj']=true;
				$JOIN['pe.pmid_journal_id = pj.pmid_journal_id']=true;
				$CONDITIONS[]='pj.pmid_journal_id IN ('.implode(",",array_keys($JOURNALS)).')';
				break;
			case 'ISSN':
				$TABLES['pmid_journal pj']=true;
				$JOIN['pe.pmid_journal = pj2.pmid_journal_id']=true;
				$CONDITIONS[]='pj2.issn_print IN (\''.implode("','",$VALUES).'\') OR pj2.issn_online IN (\''.implode("','",$VALUES).'\')';
				break;
			case 'YEAR_START':
				echo $VALUES[0];
				$YEAR=strtotime($VALUES[0].'-01-01');
				echo $YEAR;

				$CONDITIONS[]='pe.publication_date > \''.date('Y-m-d',$YEAR).'\'';
				break;
			case 'YEAR_END':
				$CONDITIONS[]='pe.publication_date < \''.$VALUES[0].'\'';
				break;
			case 'ORCID':
				$TABLES['pmid_author pa']=true;
				$JOIN['pe.pmid_entry_id = pa2.pmid_entry_id']=true;
				$CONDITIONS[]='pa2.orcid_id IN (\''.implode("','",$VALUES).'\')';
				break;
			case 'INSTITUTION':
				
				$INSTITUTION=$VALUES;
				break;
		}
	}
	
	if ($CONDITIONS==array())return array();
	foreach ($JOIN as $K=>&$V)$CONDITIONS[]=$K;
	$query='SELECT pmid FROM '.implode(",\n",array_keys($TABLES)).' WHERE '.implode("\n AND ",($CONDITIONS));
	$res= runQuery($query);
	if ($INSTITUTION==array()) return $res;

	$res=runQuery("SELECT DISTINCT pmid, instit_name
	 FROM pmid_Entry pe, pmid_author pa, pmid_author_map pam,pmid_instit pi
	 WHERE pe.pmid_entry_id = pam.pmid_entry_id
	 AND pam.pmid_author_id = pa.pmid_author_id 
	 AND pa.pmid_instit_id = pi.pmid_instit_id 
	 AND pmid IN (".implode(",",array_column($res,'pmid')).")");

	$final_list=array();
	foreach ($res as $K=>$line)
	{
		$FOUND=false;
		foreach ($INSTITUTION as $V)
		{
			if (strpos(strtolower($line['instit_name']),strtolower($V))!==false)
			{
				$final_list[$line['pmid']]=array('pmid'=>$line['pmid']);
				break;
			}
		}
		

	}
	return array_values($final_list);
}




# // $[API]
# // Title: get publication information
# // Function: get_publication_information
# // Description: Get publication information by PMID
# // Parameter: PMID | PMID | array | 24280686,25254650 | required
# // Parameter: COMPLETE | True if full information is requested | boolean | false | optional | Default: false
# // Parameter: WITH_ANNOTATION | True if annotation information is requested | boolean | false | optional | Default: false
# // Return: Publication record
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php get_publication_information -PMID '24280686,25254650'
# // $[/API]
function get_publication_information($PMID,$COMPLETE=false,$WITH_ANNOTATION=false)
{
	$LIST=array();
	if (is_array($PMID)) $LIST=$PMID;
	else $LIST[]=$PMID;

	$res=runQuery("SELECT * FROM pmid_entry pe, pmid_journal pj 
	WHERE pe.pmid_journal_id = pj.pmid_journal_id 
	AND pe.pmid IN (".implode(',',$LIST).")");
	$data=array();
	foreach ($res as $line)
	$data[$line['pmid_entry_id']]=$line;	
	if (!$COMPLETE) return array_values($data);
	if ($data==array())return array();
	$res=runQuery("SELECT * FROM pmid_abstract WHERE pmid_entry_id IN (".implode(',',array_keys($data)).")");
	foreach ($res as $line)
	{
		$data[$line['pmid_entry_id']]['ABSTRACT']=$line;
	}

	$res=runQuery("SELECT last_name,first_name,initials,orcid_id,instit_name,pmid_entry_Id,position
	FROM pmid_author_map pam, pmid_author pa, pmid_instit pi
	WHERE pam.pmid_author_id = pa.pmid_author_id
	AND pa.pmid_instit_id = pi.pmid_instit_id
	AND pam.pmid_entry_id IN (".implode(',',array_keys($data)).") ORDER BY pmid_Entry_id,position");
	foreach ($res as $line)
	{
		$data[$line['pmid_entry_id']]['AUTHOR'][]=$line;
	}

	if ($WITH_ANNOTATION)
	{
		foreach ($data as &$line)
		{
			$line['protein']=get_protein_desc_from_publication($line['pmid']);
			$line['domain']=get_protein_domain_from_publication($line['pmid']);
			$line['assay']=get_assay_from_publication($line['pmid']);
			$line['feature']=get_protein_Feature_from_publication($line['pmid']);
			$line['cell']=get_cell_line_from_publication($line['pmid']);
			$line['clinical_variant']=get_clinical_variant_from_publication($line['pmid']);
			$line['clinical_trial']=get_clinical_trial_from_publication($line['pmid']);
		}
	}


	return array_values($data);

}

//18721477
# // $[API]
# // Title: get protein records citing publication
# // Function: get_protein_from_publication
# // Description: Get protein records using the given publication for description
# // Parameter: PMID | PMID | string | 18721477 | required
# // Return: Protein record
# // Ecosystem: Proteomics:protein;Scientific_community:publication
# // Example: php biorels_api.php get_protein_from_publication -PMID '18721477'
# // $[/API]

function get_protein_desc_from_publication($PMID)
{
	$query='SELECT  eco_id, eco_name, eco_description, prot_identifier,desc_type,description, tax_id, scientific_name
	FROM pmid_entry pe, prot_desc_pmid ppm
	LEFT JOIN eco_entry ee ON ee.eco_entry_id=ppm.eco_entry_Id, prot_entry pp,prot_Desc pd,taxon t
	WHERE pe.pmid_entry_id = ppm.pmid_entry_id
	AND t.taxon_id = pp.taxon_id
	AND pd.prot_desc_id = ppm.prot_desc_id
	AND pd.prot_entry_id = pp.prot_entry_Id
	AND pe.pmid =\''.$PMID.'\'';
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$desc=array('desc_type'=>$line['desc_type'],'description'=>$line['description'],'eco_id'=>$line['eco_id'],'eco_name'=>$line['eco_name'],'eco_description'=>$line['eco_description']);
		unset($line['desc_type'],$line['description'],$line['eco_id'],$line['eco_name'],$line['eco_description']);
		if (!isset($data[$line['prot_identifier']]))$data[$line['prot_identifier']]=array('prot_identifier'=>$line['prot_identifier'],'tax_id'=>$line['tax_id'],'scientific_name'=>$line['scientific_name']);
		else $data[$line['prot_identifier']]['description'][]=$desc;
		
	}
	return $data;
}


# // $[API]
# // Title: get protein domain that referenced a given publication
# // Function: get_protein_domain_from_publication
# // Description: get protein domain that referenced a given publication
# // Parameter: PMID | PMID | string | 3891096 | required
# // Return: Protein domain record
# // Ecosystem: Proteomics:protein|domain;Scientific_community:publication
# // Example: php biorels_api.php get_protein_domain_from_publication -PMID '3891096'
# // $[/API]
function get_protein_domain_from_publication($PMID)
{
	$query='SELECT  ipr_id,protein_count,short_name,entry_type,name, abstract
	FROM pmid_entry pe, ip_pmid_map ipm,ip_entry ip
	where pe.pmid_entry_id = ipm.pmid_entry_id
	AND ipm.ip_entry_id = ip.ip_entry_id
	AND pe.pmid =\''.$PMID.'\'';
	return runQuery($query);
}


# // $[API]
# // Title: get assay information from publication
# // Function: get_assay_from_publication
# // Description: Get assay information from publication
# // Parameter: PMID | PMID | string | 18721477 | required
# // Parameter: COMPLETE | True if full information is requested | boolean | false | optional | Default: false
# // Return: Assay record
# // Ecosystem: Assay:assay;Scientific_community:publication
# // Example: php biorels_api.php get_assay_from_publication -PMID '18721477'
# // $[/API]
function get_assay_from_publication($PMID,$COMPLETE=false)
{
	$query='SELECT assay_name,source_name 
	FROM assay_entry ae, assay_pmid ap, pmid_entry pe, source s
	WHERE ae.assay_entry_id = ap.assay_entry_id
	AND ap.pmid_entry_id = pe.pmid_entry_id
	AND ae.source_id = s.source_id
	AND pe.pmid =\''.$PMID.'\'';
	$res=runQuery($query);
	if (!$COMPLETE)return $res;
	$data=array();
	$list=array();
	$K=0;
	foreach ($res as $line)
	{
		$data[$K]=$line;
		$list[$line['source_name']][$line['assay_name']][]=$K;
		++$K;
	}

	foreach ($list as $SOURCE_NAME=>&$LIST_ASSAY)
	{
		
		$TMP=get_batch_assay_info($SOURCE_NAME,array_keys($LIST_ASSAY),true);
		foreach ($TMP as &$INFO)
		{
			foreach ($LIST_ASSAY[$INFO['assay_name']] as $K)
			{
				foreach ($INFO as $X=>$V)
				$data[$K][$X]=$V;
			}
		}
			
		
	}
	return $data;
}



# // $[API]
# // Title: get protein feature from publication
# // Function: get_protein_Feature_from_publication
# // Description: Get protein feature from publication
# // Parameter: PMID | PMID | string | 23186163 | required
# // Return: Protein feature record
# // Ecosystem: Proteomics:protein|feature;Scientific_community:publication
# // Example: php biorels_api.php get_protein_Feature_from_publication -PMID '23186163'
# // $[/API]
function get_protein_Feature_from_publication($PMID)
{
	
	$query='SELECT eco_id,eco_name,eco_description,feat_name,pft.description as prot_Feat_description,section,tag,feat_value,feat_link,start_pos,end_pos,iso_name,iso_id,is_primary,ps.description prot_seq_desc,prot_identifier, scientific_name,tax_id
	FROM pmid_entry pi, prot_feat_pmid pfm
	LEFT JOIN eco_entry ee ON ee.eco_entry_Id = pfm.eco_entry_Id, prot_feat pm,prot_feat_Type pft,prot_seq ps, prot_entry pe,taxon t
	WHERE pi.pmid_entry_id = pfm.pmid_entry_id
	AND t.taxon_id = pe.taxon_id
	
	AND pfm.prot_feat_id = pm.prot_feat_id
	AND pm.prot_feat_type_id = pft.prot_feat_type_id
	AND pm.prot_seq_id = ps.prot_seq_id
	AND ps.prot_entry_id = pe.prot_entry_id
	AND pi.pmid =\''.$PMID.'\'';
	return runQuery($query);

}


# // $[API]
# // Title: Get clinical trials from publication
# // Function: get_clinical_trial_from_publication
# // Description: Get clinical trials from publication
# // Parameter: PMID | PMID | string | 18721477 |required
# // Return: Clinical trial record 
# // Ecosystem: Drug_clinical_trial:clinical trial;Scientific_community:publication
# // Example: php biorels_api.php get_clinical_trial_from_publication -PMID '18721477'
# // $[/API]

function get_clinical_trial_from_publication($PMID)
{
	$query='SELECT trial_id FROM clinical_Trial ct, clinical_trial_pmid_map ctp, pmid_entry pe
	WHERE ct.clinical_trial_id = ctp.clinical_trial_id
	AND ctp.pmid_entry_id = pe.pmid_entry_id
	AND pe.pmid =\''.$PMID.'\'';
	$res=runQuery($query);
	if ($res==array())return array();
	$data=array();
	foreach ($res as $line)
	{
		$data[$line['trial_id']]=get_clinical_trial_by_id($line['trial_id']);
	}
	return $data;
}

# // $[API]
# // Title: Get cell line from publication
# // Function: get_cell_line_from_publication
# // Description: Get cell line from publication
# // Parameter: PMID | PMID | string | 18721477 | required
# // Return: Cell line record
# // Ecosystem: Disease_anatomy:cell line;Scientific_community:publication
# // Example: php biorels_api.php get_cell_line_from_publication -PMID '18721477'
# // $[/API]
function get_cell_line_from_publication($PMID)
{
	$query='SELECT cell_acc FROM cell_pmid_map cpm, pmid_entry pe,source s, cell_entry ce
	WHERE cpm.pmid_entry_id = pe.pmid_entry_id
	AND cpm.source_id = s.source_id
	AND cpm.cell_entry_id = ce.cell_entry_id
	AND pe.pmid =\''.$PMID.'\'';
	$res= runQuery($query);
	foreach ($res as &$line)
	{
		$line['cell']=get_cell_line_by_acc($line['cell_acc']);
	
	}
	return $res;
}



# // $[API]
# // Title: Get clinical variant from publication
# // Function: get_clinical_variant_from_publication
# // Description: Get clinical variant from publication
# // Parameter: PMID | PMID | string | 12241803 | required
# // Return: Clinical variant record
# // Ecosystem: Disease_anatomy:clinical variant;Scientific_community:publication
# // Example: php biorels_api.php get_clinical_variant_from_publication -PMID '12241803'
# // $[/API]
function get_clinical_variant_from_publication($PMID)
{
	$query='SELECT DISTINCT clinical_variant_name FROM clinical_variant_entry cve, clinical_variant_submission cvs, clinical_variant_pmid_map cvpm, pmid_entry pe
	WHERE cve.clinvar_entry_Id = cvs.clinvar_entry_Id
	AND cvs.clinvar_submission_id = cvpm.clinvar_submission_id
	AND cvpm.pmid_entry_id = pe.pmid_entry_id
	AND pe.pmid =\''.$PMID.'\'';
	$res=runQuery($query);
	$data=array();
	foreach ($res as $line)
	{
		$data[$line['clinical_variant_name']]=get_clinical_variant_information($line['clinical_variant_name']);
	}
	return $data;
	
}




# // $[API]
# // Title: Get the distribution of publications by year mentioning a gene
# // Function: get_gene_publication_distribution
# // Description: Get the distribution of publications by year mentioning a gene
# // Parameter: GENE_ID | Gene ID | string | 1017 | required
# // Return: YEAR/MONTH/Count
# // Ecosystem: Genomics:gene;Scientific_community:publication
# // Example: php biorels_api.php get_gene_publication_distribution -GENE_ID 1017
# // $[/API]
function get_gene_publication_distribution($GENE_ID)
{
	$GN=get_gene_by_gene_id($GENE_ID);
	if ($GN==array())return array();
	
	$query='SELECT EXTRACT(YEAR FROM pe.publication_date) year_pub, EXTRACT(MONTH FROM pe.publication_date) month_pub,count(*) as count
	FROM pmid_gene_map pgm, pmid_entry pe
	WHERE pgm.pmid_entry_id = pe.pmid_entry_id
	AND pgm.gn_entry_id ='.$GN[0]['gn_entry_id'].'
	GROUP BY year_pub,month_pub
	ORDER BY year_pub,month_pub';
	
	return runQuery($query);
}


# // $[API]
# // Title: Get the distribution of publications by year mentioning a set of parameters
# // Function: get_publication_distribution
# // Description: Get the distribution of publications by year mentioning a set of parameters
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Return: YEAR/MONTH/Count
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php get_publication_distribution -PARAMS 'GENE_ID=1017,1018'
# // Example: php biorels_api.php get_publication_distribution -PARAMS 'DISEASE_TAG=MONDO_0005087'
# // Example: php biorels_api.php get_publication_distribution -PARAMS 'DISEASE_TAG=MONDO_0005087;GENE_ID=1017'
# // Example: php biorels_api.php get_publication_distribution -PARAMS 'ANATOMY_TAG=UBERON_0002107'
# // $[/API]
function get_publication_distribution($PARAMS)
{
	$LIST=search_publication_by_tags($PARAMS);
	//print_R($LIST);
	if ($LIST==array())return;
	$query='SELECT EXTRACT(YEAR FROM pe.publication_date) year_pub, EXTRACT(MONTH FROM pe.publication_date) month_pub,count(*) as count
	FROM pmid_entry pe
	WHERE pmid IN ('.implode(',',array_column($LIST,'pmid')).')
	GROUP BY year_pub,month_pub
	ORDER BY year_pub,month_pub';
	
	return runQuery($query);
}

# // $[API]
# // Title: Search publication by annotations
# // Function: search_publication_by_tags
# // Description: Search publication by annotations: GENE_ID,DISEASE_TAG,CLINICAL_TRIAL,CELL_LINE,CLINICAL_VARIANT,ASSAY,DRUG
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Return: Publication record
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php search_publication_by_tags -PARAMS 'GENE_ID=1017,1018'
# // Example: php biorels_api.php search_publication_by_tags -PARAMS 'DISEASE_TAG=MONDO_0005087'
# // Example: php biorels_api.php search_publication_by_tags -PARAMS 'DISEASE_TAG=MONDO_0005087;GENE_ID=1017'
# // Example: php biorels_api.php search_publication_by_tags -PARAMS 'ANATOMY_TAG=UBERON_0002107'
# // $[/API]
function search_publication_by_tags($PARAMS)
{

	$queries=array();
	foreach ($PARAMS as $KEY=>&$LIST)
	{
		switch ($KEY)
		{
			case 'GENE_SYMBOL':
				foreach ($LIST as $L)
				{
					$res=get_gene_by_gene_symbol($L);
					if ($res==array()) return array();
					$GN=array_column($res,'gn_entry_id');
					$queries[]='SELECT pmid_Entry_Id 
								FROM pmid_gene_map 
								WHERE gn_entry_id IN ('.implode(',',$GN).')';
				}
				break;
			case 'GENE_ID':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM pmid_gene_map pgm, gn_entry g 
								WHERE g.gn_entry_Id = pgm.gn_entry_id 
								AND GENE_ID='.$L;
				}
			break;
			case 'DISEASE_TAG':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM pmid_disease_map pdm, disease_entry d 
								WHERE d.disease_entry_Id = pdm.disease_entry_id 
								AND disease_tag=\''.$L.'\'';
				}
				break;
			case 'DISEASE_NAME':
				foreach ($LIST as $NAME)
				{
					$LT=search_disease_by_name($NAME);
					if ($LT==array())$LT=search_disease_by_synonym($NAME);
					if ($LT==array()) return array();
					$DS_ID=array_column($LT,'disease_entry_id');
					$queries[]='SELECT DISTINCT pmid_Entry_Id 
							FROM pmid_disease_map pdm
							WHERE disease_entry_id IN ('.implode(',',$DS_ID).')';	
				}
			break;
			case 'CLINICAL_TRIAL':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM clinical_trial_pmid_map ctp, clinical_Trial ct 
								WHERE ct.clinical_trial_Id = ctp.clinical_trial_id 
								AND trial_id='.$L;
				}
				break;
			case 'CELL_LINE':
				foreach ($LIST as $L)
				{
					$LT=search_cell_line(array('NAME'=>array($L)));
	
					if ($LT==array()) $LT=search_cell_line(array('SYN'=>array($L)));
					if ($LT==array()) $LT=search_cell_line(array('ACC'=>array($L)));
					if ($LT==array()) return array();
					foreach ($LT as $R)
					{
					$queries[]='SELECT pmid_Entry_Id 
								FROM cell_pmid_map cpm 
								WHERE cell_entry_id='.$R[0]['cell_entry_id'];
					}

				}
				break;
			case 'CLINICAL_VARIANT':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM clinical_variant_pmid_map cvpm, clinical_variant_submission cvs 
								WHERE cvs.clinvar_submission_id = cvpm.clinvar_submission_id 
								AND clinical_variant_name=\''.$L.'\'';
				}
				break;
			case 'ANATOMY_TAG':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM pmid_anatomy_map apm, anatomy_entry ae 
								WHERE ae.anatomy_entry_id = apm.anatomy_entry_id 
								AND anatomy_tag=\''.$L.'\'';
				}
				break;
			case 'ANATOMY_NAME':
				foreach ($LIST as $L)
				{
					$LT=search_anatomy_by_name($L);
					if ($LT==array())$LT=search_anatomy_by_synonym($L);
					if ($LT==array()) return array();
					foreach ($LT as $N)
					{
						$queries[]='SELECT pmid_Entry_Id 
									FROM pmid_anatomy_map apm
									WHERE anatomy_entry_id='.$N['anatomy_entry_id'];
					}
					
				}
				break;
			case 'ASSAY':
				foreach ($LIST as $L)
				{
					$queries[]='SELECT pmid_Entry_Id 
								FROM assay_pmid ap, assay_entry ae 
								WHERE ae.assay_entry_id = ap.assay_entry_id 
								AND assay_name=\''.$L.'\'';
				}
				break;
			case 'DRUG':
				foreach ($LIST as $L)
				{
					$LT=search_drug_by_name($L);
					
					foreach ($LT as $N)
					{
					$queries[]='SELECT pmid_Entry_Id 
								FROM pmid_drug_map dpm
								WHERE drug_entry_Id = '.$N[0]['drug_entry_id'];
					}
				}
				break;
		}
	}
	$LIST=array();

	foreach ($queries as $N=>$query)
	{
		$res=runQuery($query);
		if ($LIST==array())
		{
			$LIST=array_flip(array_column($res,'pmid_entry_id'));
			
		}
		else 
		{
			$NEW_LIST=array_flip(array_column($res,'pmid_entry_id'));
			foreach ($LIST as $ID=>$dummy)
			if (!isset($NEW_LIST[$ID]))unset($LIST[$ID]);
			if ($LIST==array())return array();
		}
	}
	if ($LIST==array())return array();
	$LIST=array_keys($LIST);
	$res=runQuery("SELECT pmid from pmid_Entry where pmid_entry_id IN (".implode(',',$LIST).")");
	return $res;
}




function get_fulltext_by_pmid($PMID)
{
	$query='SELECT pmc_id FROM pmc_entry pc, pmid_entry pe 
	WHERE pe.pmid_Entry_Id = pc.pmid_Entry_id 
	AND pmid =\''.$PMID.'\'';
	$res=runQuery($query);
	if ($res==array())return array();
	$PMC=array_column($res,'pmc_id');
	return get_fulltext($PMC[0]);
}

# // $[API]
# // Title: Get full text by PMC
# // Function: get_fulltext_publication
# // Description: Get full text by PMC
# // Parameter: PMC | PMC | string | PMC523836 | required
# // Return: Full text record
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php get_fulltext_publication -PMC 'PMC523836'
# // $[/API]
function get_fulltext_publication($PMC,$WITH_ANNOTATION=false)
{
	$query='SELECT * FROM pmc_entry WHERE pmc_id =\''.$PMC.'\'';
	$data=runQuery($query);
	foreach ($data as &$line)
	{
		$tmp=runQuery("SELECT pmc_fulltext_id,offset_pos,group_id, section_type,section_subtype,full_text 
		FROM pmc_fulltext pf, pmc_section ps 
		where ps.pmc_section_id = pf.pmc_section_id 
		AND pf.pmc_entry_id = '".$line['pmc_entry_id']."' ORDER BY offset_pos asc");
		$MAP=array();
		foreach ($tmp as $r)
		{
			$id=$r['pmc_fulltext_id'];
			unset($r['pmc_fulltext_id']);
			$MAP[$id]=$r['offset_pos'];
			$line['FULLTEXT'][$r['offset_pos']]=$r;
		}
		if (!$WITH_ANNOTATION)continue;
		$tmp=runQuery("SELECT trial_id,loc_info, pmc_fulltext_id FROM 
			pmc_fulltext_clinical_map pftm, clinical_Trial ct 
		WHERE ct.clinical_trial_id = pftm.clinical_trial_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['CLINICAL_TRIAL'][$r2['trial_id']]))
			{
				$line['CLINICAL_TRIAL'][$r2['trial_id']]['TRIAL']=get_clinical_trial_information($r2['trial_id']);
				$line['CLINICAL_TRIAL'][$r2['trial_id']]['LOC_INFO']=array();
			}
			$line['CLINICAL_TRIAL'][$r2['trial_id']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}

		$tmp=runQuery("SELECT drug_primary_name,loc_info, pmc_fulltext_id FROM 
			pmc_fulltext_drug_map pftm, drug_entry ct 
		WHERE ct.drug_entry_Id = pftm.drug_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['DRUG'][$r2['drug_primary_name']]))
			{
				$line['DRUG'][$r2['drug_primary_name']]['DRUG']=get_drug_information($r2['drug_primary_name']);
				$line['DRUG'][$r2['drug_primary_name']]['LOC_INFO']=array();
			}
			$line['DRUG'][$r2['drug_primary_name']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}


		$tmp=runQuery("SELECT disease_tag,loc_info, pmc_fulltext_id FROM 
			pmc_fulltext_disease_map pftm, disease_entry ct 
		WHERE ct.disease_entry_Id = pftm.disease_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['DISEASE'][$r2['disease_tag']]))
			{
				$line['DISEASE'][$r2['disease_tag']]['DISEASE']=get_disease_information($r2['disease_tag']);
				$line['DISEASE'][$r2['disease_tag']]['LOC_INFO']=array();
			}
			$line['DISEASE'][$r2['disease_tag']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}


		$tmp=runQuery("SELECT anatomy_tag,loc_info, pmc_fulltext_id FROM 
		pmc_fulltext_anatomy_map pftm, anatomy_entry ct 
		WHERE ct.anatomy_entry_Id = pftm.anatomy_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['ANATOMY'][$r2['anatomy_tag']]))
			{
				$line['ANATOMY'][$r2['anatomy_tag']]['ANATOMY']=get_anatomy_information($r2['anatomy_tag']);
				$line['ANATOMY'][$r2['anatomy_tag']]['LOC_INFO']=array();
			}
			$line['ANATOMY'][$r2['anatomy_tag']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}


		$tmp=runQuery("SELECT cell_acc,loc_info, pmc_fulltext_id FROM 
		pmc_fulltext_cell_map pftm, cell_entry ct 
		WHERE ct.cell_entry_Id = pftm.cell_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['CELL'][$r2['cell_acc']]))
			{
				$line['CELL'][$r2['cell_acc']]['CELL']=get_cell_info($r2['cell_acc']);
				$line['CELL'][$r2['cell_acc']]['LOC_INFO']=array();
			}
			$line['CELL'][$r2['cell_acc']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}

		$tmp=runQuery("SELECT md5_hash,loc_info, pmc_fulltext_id FROM 
		pmc_fulltext_sm_map pftm, sm_entry ct 
		WHERE ct.sm_entry_Id = pftm.sm_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['SM'][$r2['md5_hash']]))
			{
				$line['SM'][$r2['md5_hash']]['SM']=get_small_molecule($r2['md5_hash']);
				$line['SM'][$r2['md5_hash']]['LOC_INFO']=array();
			}
			$line['SM'][$r2['md5_hash']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}

		$tmp=runQuery("SELECT gene_id,loc_info, pmc_fulltext_id FROM 
		pmc_fulltext_gn_map pftm, gn_entry ct 
		WHERE ct.gn_entry_Id = pftm.gn_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['GENE'][$r2['gene_id']]))
			{
				$line['GENE'][$r2['gene_id']]['GENE']=get_gene_by_gene_id($r2['gene_id']);
				$line['GENE'][$r2['gene_id']]['LOC_INFO']=array();
			}
			$line['GENE'][$r2['gene_id']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}


		$tmp=runQuery("SELECT ac,loc_info, pmc_fulltext_id FROM 
		pmc_fulltext_go_map pftm, go_entry ct 
		WHERE ct.go_entry_Id = pftm.go_entry_id
		AND pmc_fulltext_id IN (".implode(',',array_keys($MAP)).")");
		foreach ($tmp as $r2)
		{
			if (!isset($line['GO'][$r2['ac']]))
			{
				$line['GO'][$r2['ac']]['GO']=search_gene_ontology($r2['ac']);
				$line['GO'][$r2['ac']]['LOC_INFO']=array();
			}
			$line['GO'][$r2['ac']]['LOC_INFO'][]=array($r2['loc_info'],$MAP[$r2['pmc_fulltext_id']]);

		}

		
		

	}
	return $data;
}


# // $[API]
# // Title: Extract all sentences matching set of parameters
# // Function: get_fulltext_analysis
# // Description: Extract all sentences matching set of parameters
# // Parameter: PARAMS | Parameters | multi_array | | required
# // Return: Full text record
# // Ecosystem: Scientific_community:publication
# // Example: php biorels_api.php get_fulltext_analysis -PARAMS 'GENE_SYMBOL=TP53'
# // Example: php biorels_api.php get_fulltext_analysis -PARAMS 'GENE_SYMBOL=TP53;DISEASE_TAG=MONDO_0005087'
# // $[/API]
function get_fulltext_analysis($PARAMS)
{
	$COLS=array('full_text','pmc_id','pmid','title','publication_date','journal_name');
	$TABLES=array('pmc_entry pe LEFT JOIN pmid_entry pm ON pe.pmid_entry_id = pm.pmid_entry_id
	LEFT JOIN pmid_journal pj ON pj.pmid_journal_id = pm.pmid_journal_id',
'pmc_fulltext pf');
	$JOINS=array('pe.pmc_entry_id = pf.pmc_entry_id');
	$data=array('PARAMS'=>array());
	$IDS=array();
	$N_T=0;
	
	foreach ($PARAMS as $KEY=>&$LIST)
	{
		switch ($KEY)
		{
			case 'GENE_SYMBOL':
				$LG=array();$SG=array();
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Gene_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$res=get_gene_by_gene_symbol($L,array('9606','10090'));
					if ($res==array()) return array();
					

					$GN=array_unique(array_column($res,'gn_entry_id'));
					foreach ($GN as $G)$LG[]=$G;
					$GN=array_unique(array_column($res,'symbol'));
					foreach ($GN as $G)$SG[]=$G;
					
					foreach ($res as $l)
					{
						$IDS['rule_'.$N_T][$l['gn_entry_id']]=$l['symbol'];
					}
				}
				if ($LG==array())return array();
					$TABLES[]='pmc_fulltext_gn_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.gn_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.gn_entry_id IN ('.implode(',',$LG).')';
					$data['PARAMS'][$N_T]=array_unique($SG);
				
				break;
			case 'GENE_ID':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Gene_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$res=get_gene_by_gene_id($L);
					if ($res==array()) return array();
					
					foreach ($res as $l)
					{
						$IDS['rule_'.$N_T][$l['gn_entry_id']]=$l['symbol'];
					}
					$GN=array_unique(array_column($res,'gn_entry_id'));
					$TABLES[]='pmc_fulltext_gn_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.gn_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.gn_entry_id IN ('.implode(',',$GN).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($res,'symbol'));
				}
			break;
			case 'DISEASE_TAG':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Disease_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$res=search_disease_by_tag($L);
					if ($res==array()) return array();
					
					foreach ($res as $l)
					{
						$IDS['rule_'.$N_T][$l['disease_entry_id']]=$l['disease_name'];
					}
					$DS=array_unique(array_column($res,'disease_entry_id'));
					$TABLES[]='pmc_fulltext_disease_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.disease_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.disease_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($res,'disease_name'));	
				}
				break;
			case 'DISEASE_NAME':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Disease_rule_'.$N_T;
				foreach ($LIST as $NAME)
				{
					$LT=search_disease_by_name($NAME);
					if ($LT==array())$LT=search_disease_by_synonym($NAME);
					if ($LT==array()) return array();
					
					foreach ($LT as $l)
					{
						$IDS['rule_'.$N_T][$l['disease_entry_id']]=$l['disease_name'];
					}
					$DS=array_unique(array_column($LT,'disease_entry_id'));
					$TABLES[]='pmc_fulltext_disease_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.disease_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.disease_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'disease_name'));	
				}
			break;
			case 'CLINICAL_TRIAL':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Clinical_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$INFO=get_clinical_trial_information($L);
					
					foreach ($INFO as $l)
					{
						$IDS['rule_'.$N_T][$l['clinical_trial_id']]=$l['trial_id'];
					}
					$DS=array_unique(array_column($LT,'clinical_trial_id'));
					$TABLES[]='pmc_fulltext_clinical_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;

					$COLS[]='pgm'.$N_T.'.clinical_trial_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.clinical_trial_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'trial_id'));
				}
				break;
			case 'CELL_LINE':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Cell_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$LT=search_cell_line(array('NAME'=>array($L)));
	
					if ($LT==array()) $LT=search_cell_line(array('SYN'=>array($L)));
					if ($LT==array()) $LT=search_cell_line(array('ACC'=>array($L)));
					if ($LT==array()) return array();

					foreach ($LT as $l)
					{
						$IDS['rule_'.$N_T][$l['cell_entry_id']]=$l['cell_name'];
					}
					$DS=array_unique(array_column($LT,'cell_entry_id'));
					$TABLES[]='pmc_fulltext_cell_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.cell_Entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.cell_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'cell_acc'));

				}
				break;
			
			case 'ANATOMY_TAG':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Anatomy_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$LT=get_anatomy_information($L);
					if ($LT==array()) return array();
					
					foreach ($LT as $l)
					{
						$IDS['rule_'.$N_T][$l['anatomy_entry_id']]=$l['anatomy_name'];
					}
					$DS=array_unique(array_column($LT,'anatomy_entry_id'));
					$TABLES[]='pmc_fulltext_anatomy_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.anatomy_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.anatomy_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'anatomy_name'));
				}
				break;
			case 'ANATOMY_NAME':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Anatomy_rule_'.$N_T;
				foreach ($LIST as $L)
				{
					$LT=search_anatomy_by_name($L);
					if ($LT==array())$LT=search_anatomy_by_synonym($L);
					if ($LT==array()) return array();
					
					foreach ($LT as $l)
					{
						$IDS['rule_'.$N_T][$l['anatomy_entry_id']]=$l['cell_name'];
					}
					$DS=array_unique(array_column($LT,'anatomy_entry_id'));
					$TABLES[]='pmc_fulltext_anatomy_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.anatomy_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.anatomy_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'anatomy_name'));
					
				}
				break;
			
			case 'DRUG':
				++$N_T;
				$IDS['rule_'.$N_T]['NAME']='Drug_rule_'.$N_T;
				
				foreach ($LIST as $L)
				{
					$LT=search_drug_by_name($L);
					
					foreach ($LT as $l)
					{
						$IDS['rule_'.$N_T][$l['drug_entry_id']]=$l['drug_primary_name'];
					}
					$DS=array_unique(array_column($LT,'drug_entry_id'));
					$TABLES[]='pmc_fulltext_drug_map pgm'.$N_T;
					$COLS[]='pgm'.$N_T.'.loc_info as loc_'.$N_T;
					$COLS[]='pgm'.$N_T.'.drug_entry_id as rule_'.$N_T;
					$JOINS[]='pgm'.$N_T.'.pmc_fulltext_id = pf.pmc_fulltext_id';
					$JOINS[]='pgm'.$N_T.'.drug_entry_id IN ('.implode(',',$DS).')';
					$data['PARAMS'][$N_T]=array_unique(array_column($LT,'drug_primary_name'));
					
				}
				break;
		}
	}
	$query='SELECT '.implode(',',$COLS).' FROM '.implode(',',$TABLES).' WHERE '.implode(' AND ',$JOINS);
	
	$tmp=runQuery($query);
	$res=array();
	foreach ($tmp as $DT)
	{
		$rec= array('pubmed_id'=>$DT['pmid'],'pmc_id'=>$DT['pmc_id'],'sentence'=>$DT['full_text']);
		foreach ($IDS as $N=>&$list)
		{
			$rec[$list['NAME']]=$list[$DT[$N]];
		}
		$res[]=$rec;
		

	}
	
	return $res;

}




/*


SELECT gene_id,symbol, pfgm.loc_info as gene_loc, disease_tag, disease_name, pfdm.loc_info as disease_loc, full_text, pe.pmid_entry_id
FROM
test1.gn_entry g, test1.pmc_fulltext_gn_map pfgm, test1.pmc_fulltext_disease_map pfdm,
test1.disease_entry d, test1.pmc_fulltext pf, test1.pmc_entry pe
where g.gn_entry_id = pfgm.gn_entry_id
AND pfgm.pmc_fulltext_id = pfdm.pmc_fulltext_id
AND pfdm.disease_entry_Id = d.disease_entry_id
AND pf.pmc_fulltext_id = pfdm.pmc_fulltext_id 
AND pe.pmc_entry_Id = pf.pmc_entry_id
AND disease_tag='MONDO_0005087'
AND gene_id=3845;
*/
?>