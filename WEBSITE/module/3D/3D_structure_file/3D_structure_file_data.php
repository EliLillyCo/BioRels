<?php
ini_set('memory_limit', '2000M');
if (!defined("BIORELS")) {
    header("Location:/");
}

$PDB_ID = $USER_INPUT['PAGE']['VALUE'];

$N_PARAM = count($USER_INPUT['PARAMS']);
$FILTERS = array();
for ($I = 0; $I < $N_PARAM; ++$I) {
    $V = &$USER_INPUT['PARAMS'][$I];

    if ($V == 'RESID') {
        if ($I + 1 == $N_PARAM) {
            throw new Exception("Resid  missing", ERR_TGT_USR);
        }

        $V = &$USER_INPUT['PARAMS'][$I + 1];
        $tab = explode("-", $V);
        if (count($tab) != 2) {
            throw new Exception("Wrong format for Resid ", ERR_TGT_USR);
        }

        $FILTERS['RES'] = $tab;
        ++$I;
    }
    if ($V == 'CHAIN') {
        if ($I + 1 == $N_PARAM) {
            throw new Exception("Resid  missing", ERR_TGT_USR);
        }

        $V = &$USER_INPUT['PARAMS'][$I + 1];
        $tab = explode("-", $V);

        $FILTERS['CHAIN'] = $tab;
        ++$I;
    }
}

if (isset($FILTERS['RES'])) {
    $MODULE_DATA = getPDBRes($PDB_ID, $FILTERS['RES'][0], $FILTERS['RES'][1]);
} else if (isset($FILTERS['CHAIN'])) {
    $MODULE_DATA = getPDBStructure($PDB_ID, $FILTERS);
} else {
    $MODULE_DATA = getPDBStructure($PDB_ID, array());
}

$MODULE_DATA['ENTRY'] = getPDBInfo($PDB_ID, false, false, false)['ENTRY'];

switch ($USER_INPUT['VTYPE']) {
    case 'MOL2':$MODULE_DATA['MOL2'] = xray2MOL2($PDB_ID, $MODULE_DATA);
        break;
    case 'PDB':$MODULE_DATA['PDB'] = xray2PDB($PDB_ID, $MODULE_DATA);
        break;
}

function xray2PDB($PDB_ID, $E)
{
    $STR = 'HEADER';
    for ($I = 6; $I < 50; ++$I) {
        $STR .= ' ';
    }

    $STR .= date('d-M-y', strtotime($E['ENTRY']['DEPOSITION_DATE']));
    for ($I = strlen($STR); $I < 62; ++$I) {
        $STR .= ' ';
    }

    $STR .= $E['ENTRY']['PDB_ID'] . "\n";
    $TITLE = str_split($E['ENTRY']['TITLE'], 70);
    foreach ($TITLE as $K => $T) {
        $STR .= 'TITLE   ';
        if ($K == 0) {
            $STR .= '  ';
        } else if ($K < 9) {
            $STR .= ' ' . ($K + 1);
        } else {
            $STR .= ($K + 1);
        }

        $STR .= $T . "\n";
    }

    // $RotMat=array(array(-0.999902, 0.0010769, -0.0139687 ),array( -0.0139445, 0.0199191, 0.999704 ),array( 0.00135483, 0.999801, -0.0199021 ),array(11.019051, 76.183477, 83.519917));

    // $CENTER=array('X'=>0,'Y'=>0,'Z'=>0,'N'=>0);
    // foreach ($E['CHAIN'] as $CHAIN_NAME=>&$RES_LIST)
    // foreach ($RES_LIST as $RESID=>&$RES_INFO)
    // {

    //     foreach ($RES_INFO['ATOM'] as &$ATOM)
    //     {
    //         $ATOM['X']=$ATOM['X']*$RotMat[0][0]+$ATOM['Y']*$RotMat[0][1]+$ATOM['Z']*$RotMat[0][2];
    //         $ATOM['Y']=$ATOM['X']*$RotMat[1][0]+$ATOM['Y']*$RotMat[1][1]+$ATOM['Z']*$RotMat[0][2];
    //         $ATOM['Z']=$ATOM['X']*$RotMat[2][0]+$ATOM['Y']*$RotMat[2][1]+$ATOM['Z']*$RotMat[0][2];
    //         $CENTER['X']+=$ATOM['X'];
    //         $CENTER['Y']+=$ATOM['Y'];
    //         $CENTER['Z']+=$ATOM['Z'];
    //         $CENTER['N']++;
    //     }
    // }
    // $CENTER['X']/=$CENTER['N'];
    // $CENTER['Y']/=$CENTER['N'];
    // $CENTER['Z']/=$CENTER['N'];

    // foreach ($E['CHAIN'] as $CHAIN_NAME=>&$RES_LIST)
    // foreach ($RES_LIST as $RESID=>&$RES_INFO)
    // {

    //     foreach ($RES_INFO['ATOM'] as &$ATOM)
    //     {
    //         $ATOM['X']+=$RotMat[3][0]-$CENTER['X'];
    //         $ATOM['Y']+=$RotMat[3][1]-$CENTER['Y'];
    //         $ATOM['Z']+=$RotMat[3][2]-$CENTER['Z'];
    //         print_R($ATOM);
    //     }
    // }
    // exit;
    foreach ($E['CHAIN'] as $CHAIN_NAME => &$RES_LIST) {
        foreach ($RES_LIST as $RESID => &$RES_INFO) {

            foreach ($RES_INFO['ATOM'] as &$ATOM) {

                $STR_LINE = 'ATOM  ';
                $S = (string) $ATOM['ATOM_NUM'];
                $L = strlen($STR_LINE) + strlen($S);for ($I = $L; $I < 11; ++$I) {
                    $STR_LINE .= ' ';
                }

                $STR_LINE .= $S;
                $L = strlen($STR_LINE);for ($I = $L; $I < 13; ++$I) {
                    $STR_LINE .= ' ';
                }

                $STR_LINE .= $ATOM['NAME'];
                $L = strlen($STR_LINE);for ($I = $L; $I < 17; ++$I) {
                    $STR_LINE .= ' ';
                }

                $STR_LINE .= $RES_INFO['NAME'];
                $L = strlen($STR_LINE);for ($I = $L; $I < 21; ++$I) {
                    $STR_LINE .= ' ';
                }

                $STR_LINE .= $CHAIN_NAME;
                $S = (string) $RESID;
                for ($I = strlen($S); $I < 4; ++$I) {
                    $STR_LINE .= ' ';
                }

                $STR_LINE .= $S . '    ';
                $STR_LINE .= sprintf("%8.3f", $ATOM['X']) . sprintf("%8.3f", $ATOM['Y']) . sprintf("%8.3f", $ATOM['Z']) . '  1.00' . sprintf("%6.2f", $ATOM['B_FACTOR']) . '          ' . sprintf("%2s", $ATOM['SYMBOL']);

                $STR .= $STR_LINE . "\n";
            }
        }
    }

    if (isset($E['BOND'])) {
        $CO = array();

        foreach ($E['BOND'] as $NDB => &$BD) {
            $CO[min($BD[0], $BD[1])][] = max($BD[0], $BD[1]);
        }
        foreach ($CO as $ID1 => $LISTID) {
            $STR .= 'CONECT' . sprintf("%5d", $ID1);
            foreach ($LISTID as $ID) {
                $STR .= sprintf("%5d", $ID);
            }

            $STR .= "\n";

        }
    }

    return $STR;

}

function xray2MOL2($PDB_ID, $E)
{
    $STR = '';
    $STR = '@<TRIPOS>MOLECULE' . "\n";
    $STR .= $PDB_ID . "\n";
    $STR .= $E['STAT']['ATOM'] . ' ' . $E['STAT']['BOND'] . ' ' . $E['STAT']['RES'] . "\nPROTEIN\nUSER_CHARGES\n\n\n@<TRIPOS>ATOM\n";
    $STR_R = '';
    $N_RES = 0;
    foreach ($E['CHAIN'] as $CHAIN_NAME => &$RES_LIST) {
        foreach ($RES_LIST as $RESID => &$RES_INFO) {
            //1 O           1.7394   -2.1169   -1.0894 O.3     1  LIG1       -0.3859
            $ROOT = -1; ++$N_RES;
            foreach ($RES_INFO['ATOM'] as &$ATOM) {
                if ($ATOM['NAME'] == 'CA') {
                    $ROOT = $ATOM['ATOM_NUM'];
                } else if ($ROOT == -1) {
                    $ROOT = $ATOM['ATOM_NUM'];
                }

                $STR .= sprintf("%7d", $ATOM['ATOM_NUM']) . ' ' . sprintf("%-8s", $ATOM['NAME']) .
                ' ' . sprintf("%9.4f", $ATOM['X']) .
                ' ' . sprintf("%9.4f", $ATOM['Y']) .
                ' ' . sprintf("%9.4f", $ATOM['Z']) .
                ' ' . sprintf("%-5s", $ATOM['MOL2TYPE']) . ' ' . sprintf("%3d", $N_RES) . ' ' . sprintf("%-10s", $RES_INFO['NAME'] . $RESID) . " " . $ATOM['CHARGE'] . "\n";
            }

            /*
            mOfs << std::right << std::setw(6) << (iRes+1)<<" ";
            std::ostringstream ossx; ossx<<residue.getName()<<residue.getFID();
            mOfs<< std::left <<std::setw(7)<<ossx.str()<<" ";
            if (residue.getResType()==(RESTYPE::STANDARD_AA)
            ||residue.getResType()==(RESTYPE::MODIFIED_AA))
            {
            bool found=false;
            for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
            {
            const MMAtom& atom=residue.getAtom(iAtm);
            if (atom.getName()=="CA" &&
            getAtomPos(atom.getMID(),atmpos))
            {
            mOfs  << std::setw(5) << atmpos+1<<" ";
            found =true;break;
            }
            }
            if (!found && getAtomPos(residue.getAtom(0).getMID(),atmpos))
            mOfs  << std::setw(5) <<atmpos+1<<" ";
            }
            else
            {
            getAtomPos(residue.getAtom(0).getMID(),atmpos);
            mOfs   << std::setw(5) << (atmpos+1) <<" ";
            }
            mOfs<< "RESIDUE 1 "<< ((residue.getChainName()=="")?"X":residue.getChainName())
            << " "<< std::setw(3)<<residue.getName();
            list.clear();
            getLinkedResidue(residue,list);

             */
            $STR_R .= sprintf("%6d", $N_RES) . ' ' . sprintf("%-6s", $RES_INFO['NAME'] . $RESID) . ' ' . sprintf("%4d", $ROOT) . ' RESIDUE 1 ' . $CHAIN_NAME . ' ' . sprintf("%3s", $RES_INFO['NAME']) . "\n";
        }
    }

    if (isset($E['BOND'])) {
        $STR .= "@<TRIPOS>BOND\n";
        foreach ($E['BOND'] as $NDB => &$BD) {

            $STR .= sprintf("%6d", ($NDB + 1)) . ' ' . sprintf("%6d", $BD[0]) . ' ' . sprintf("%6d", $BD[1]) . ' ' . sprintf("%6s", $BD[2]) . "\n";
        }
    }
    $STR .= "@<TRIPOS>SUBSTRUCTURE\n" . $STR_R . "\n";
    return $STR;
}
?>