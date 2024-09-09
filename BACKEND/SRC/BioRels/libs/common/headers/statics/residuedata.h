#ifndef RESIDUEDATA_H
#define RESIDUEDATA_H
#include <string>

#define NBAA 21

static const struct
{
        std::string code;       /*!< Amino Acid Code */
        std::string name;/* Full name of the amino acid*/
    }
AAcid[NBAA]={
    {"L", "LEU"},
    {"A", "ALA"},
    {"V", "VAL"},
    {"G", "GLY"},
    {"E", "GLU"},
    {"K", "LYS"},
    {"S", "SER"},
    {"I", "ILE"},
    {"D", "ASP"},
    {"T", "THR"},
    {"R", "ARG"},
    {"P", "PRO"},
    {"F", "PHE"},
    {"N", "ASN"},
    {"Q", "GLN"},
    {"Y", "TYR"},
    {"H", "HIS"},
    {"M", "MET"},
    {"C", "CYS"},
    {"W", "TRP"},
    {"O", "PYL"}

};

#endif // RESIDUEDATA_H
/* Number of xray_Residue found in DB as of Nov 23th 2016
3960701	LEU
3129736	ALA
2912639	VAL
2879843	GLY
2779538 GLU
2462002	LYS
2461940	SER
2386358	ILE
2336683	ASP
2204398	THR
2111915	ARG
1903452	PRO
1731500	PHE
1705751	ASN
1581992	GLN
1480042	TYR
1027768	HIS
956375	MET
643053	CYS
588678	TRP
2	HIE
2	HID
*/





