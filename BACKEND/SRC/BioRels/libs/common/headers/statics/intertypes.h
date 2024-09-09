#ifndef INTERTYPES_H
#define INTERTYPES_H
#include <cstdint>
#include <inttypes.h>
#include <map>
#include <string>
#define NB_INTERTYPE 14
#define NB_BOND 10
#define NB_CHEMPROP 10;


namespace CHEMPROP
{
const uint16_t HYDROPHOBIC   =0x0001;
const uint16_t HBOND_ACC     =0x0002;
const uint16_t HBOND_DON     =0x0004;
const uint16_t AROM_RING     =0x0800;
const uint16_t CATIONIC      =0x0008;
const uint16_t ANIONIC       =0x0020;
const uint16_t METAL         =0x0040;
const uint16_t WEAK_HBOND_ACC=0x0200;
const uint16_t WEAK_HBOND_DON=0x0400;
const uint16_t HALOGEN   =0x1000;
const std::map<std::string,uint16_t> nameToType={
    {"Hydrophobic",HYDROPHOBIC},
    {"H-Bond Acceptor",HBOND_ACC},
    {"H-Bond Donor",HBOND_DON},
    {"Aromatic Ring",AROM_RING},
    {"Cationic",CATIONIC},
    {"Anionic",ANIONIC},
    {"Metal",METAL},
    {"Weak H-Bond Acceptor",WEAK_HBOND_ACC},
    {"Weak H-Bond Donor",WEAK_HBOND_DON},
    {"Halogen",HALOGEN}
};
const std::map<uint16_t,std::string> typeToName={
    {HYDROPHOBIC,"Hydrophobic"},
    {HBOND_ACC,"H-Bond Acceptor"},
    {HBOND_DON,"H-Bond Donor"},
    {AROM_RING,"Aromatic Ring"},
    {CATIONIC,"Cationic"},
    {ANIONIC,"Anionic"},
    {METAL,"Metal"},
    {WEAK_HBOND_ACC,"Weak H-Bond Acceptor"},
    {WEAK_HBOND_DON,"Weak H-Bond Donor"},
    {HALOGEN,"Halogen"}
};
}
struct IntNames
{
    std::string resName;
    std::string atmName;
    std::string atmElem;
    std::string atmMOL2;
    IntNames(const std::string& rn,
             const std::string& an,
             const std::string& ae,
             const std::string& am):resName(rn),atmName(an),atmElem(ae),atmMOL2(am){}
};

namespace INTER
{
const unsigned char HYDROPHOBIC     =0;
const unsigned char HBOND           =1;
const unsigned char IONIC           =2;
const unsigned char AROMATIC_EF     =3;
const unsigned char AROMATIC_FF     =4;
const unsigned char METAL_ACCEPTOR  =5;
const unsigned char WEAK_HBOND      =6;
const unsigned char PI_CATION       =7;
const unsigned char HALOGEN_BOND    =8;
const unsigned char XH_PI           =9;
const unsigned char HBOND_HALOGEN   =10;
const unsigned char HALOGEN_PI      =11;
const unsigned char CARBONYL_PI     =12;
const unsigned char PI_ANION        =13;
const unsigned char UNF_HBOND       =14;
const unsigned char UNF_METAL       =15;
const unsigned char UNF_IONIC       =16;
const unsigned char CLASH_APOLAR    =17;
const unsigned char CLASH_POLAR     =18;
const std::map<std::string,unsigned char> interTypes={
    {"Hydrophobic",HYDROPHOBIC},
    {"H-Bond",HBOND},
    {"Ionic",IONIC},
    {"Aromatic EF",AROMATIC_EF},
    {"Aromatic PD",AROMATIC_FF},
    {"Metal",METAL_ACCEPTOR},
    {"Weak H-Bond",WEAK_HBOND},
    {"Cation PI",PI_CATION},
    {"Halogen Bond",HALOGEN_BOND},
    {"H-Arene",XH_PI},
    {"Halogen HBond",HBOND_HALOGEN},
    {"Halogen Arom",HALOGEN_PI},
    {"Carbonyl PI",CARBONYL_PI},
    {"ANION PI",PI_ANION},
    {"Clash",CLASH_APOLAR},
};
    const std::map<unsigned char,std::string> typeToName={
            {HYDROPHOBIC,"Hydrophobic"},
            {HBOND,"H-Bond"},
            {IONIC,"Ionic"},
            {AROMATIC_EF,"Aromatic EF"},
            {AROMATIC_FF,"Aromatic PD"},
            {METAL_ACCEPTOR,"Metal"},
            {WEAK_HBOND,"Weak H-Bond"},
            {PI_CATION,"Cation PI"},
            {HALOGEN_BOND,"Halogen Bond"},
            {XH_PI,"H-Arene"},
            {HALOGEN_PI,"Halogen Arom"},
            {HBOND_HALOGEN,"Halogen HBond"},
            {CARBONYL_PI,"Carbonyl PI"},
            {PI_ANION,"ANION PI"},
            {CLASH_APOLAR,"Clash"}};

    const static std::map<unsigned char, IntNames> ItoN={
        {AROMATIC_EF,IntNames("EF","CZ","C","C.ar")},
        {AROMATIC_FF,IntNames("FF","CE1","C","C.ar")},
        {HALOGEN_BOND,IntNames("XB","Cl","Cl","Cl")},
        {HALOGEN_PI,IntNames("XP","I","I","I")},
        {HBOND,IntNames("HB","N","N","N.3")},
        {HBOND_HALOGEN,IntNames("BX","F","F","F")},
        {HYDROPHOBIC,IntNames("HY","CA","C","C.ar")},
        {IONIC,IntNames("IO","NZ","N","N.4")},
        {PI_CATION,IntNames("PC","NH1","N","N.4")},
        {WEAK_HBOND,IntNames("WH","S","S","S.3")},
        {XH_PI,IntNames("HP","HP","H","H")},
        {METAL_ACCEPTOR,IntNames("MG","MG","Mg","Mg")},
        {CARBONYL_PI,IntNames("OPI","O2","O","O.2")},
        {PI_ANION,IntNames("API","OX","O","O.co2")},
        {CLASH_APOLAR,IntNames("CLA","F","F","F")}};
}

namespace BOND
{
const uint16_t UNDEFINED  =0x0001;
const uint16_t SINGLE     =0x0002;
const uint16_t DOUBLE     =0x0004;
const uint16_t TRIPLE     =0x0008;
const uint16_t DELOCALIZED=0x1000;
const uint16_t AROMATIC_BD=0x0010;
const uint16_t AMIDE      =0x0040;
const uint16_t QUADRUPLE=0x0100;
const uint16_t DUMMY=0x0200;
const uint16_t FUSED=0x0400;
const std::map<std::string,uint16_t> nameToType={
    {"UNDEFINED",UNDEFINED},
    {"SINGLE",SINGLE},
    {"DOUBLE",DOUBLE},
    {"TRIPLE",TRIPLE},
    {"DELOCALIZED",DELOCALIZED},
    {"AROMATIC_BD",AROMATIC_BD},
    {"AMIDE",AMIDE},
    {"QUADRUPLE",QUADRUPLE},
    {"DUMMY",DUMMY},
    {"FUSED",FUSED}
};
const std::map<uint16_t,std::string> typeToName={
    {UNDEFINED,"UNDEFINED"},
    {SINGLE,"SINGLE"},
    {DOUBLE,"DOUBLE"},
    {TRIPLE,"TRIPLE"},
    {DELOCALIZED,"DELOCALIZED"},
    {AROMATIC_BD,"AROMATIC Bond"},
    {AMIDE,"AMIDE"},
    {QUADRUPLE,"QUADRUPLE"},
    {DUMMY,"DUMMY"},
    {FUSED,"FUSED"}
};
const std::map<uint16_t,std::string> typeToMOL2={
    {UNDEFINED,"un"},
    {SINGLE,"1"},
    {DOUBLE,"2"},
    {TRIPLE,"3"},
    {DELOCALIZED,"de"},
    {AROMATIC_BD,"ar"},
    {AMIDE,"am"},
    {QUADRUPLE,"4"},
    {DUMMY,"du"},
    {FUSED,""}
};
const std::map<std::string,uint16_t> MOL2toType={
    {"1",SINGLE},
    {"2",DOUBLE},
    {"3",TRIPLE},
    {"de",DELOCALIZED},
    {"ar",AROMATIC_BD},
    {"am",AMIDE},
    {"4",QUADRUPLE},
    {"du",DUMMY},
    {"",FUSED},
    {"un",UNDEFINED}
};
const std::map<std::string,uint16_t> SDtoType={
    {"1",SINGLE},
    {"2",DOUBLE},
    {"3",TRIPLE},
    {"4",AROMATIC_BD},
    {"8",DUMMY}};
const std::map<uint16_t,std::string> typeToSD={
    {UNDEFINED,"1"},
    {SINGLE,"1"},
    {DOUBLE,"2"},
    {TRIPLE,"3"},
    {DELOCALIZED,"4"},
    {AROMATIC_BD,"4"},
    {AMIDE,"1"},
    {QUADRUPLE,"4"},
    {DUMMY,"8"},
    {FUSED,"8"}
};
}

namespace MOLETYPE
{
const uint16_t UNDEFINED=0x0001;
const uint16_t PROTEIN  =0x0002;
const uint16_t LIGAND   =0x0004;
const uint16_t WATER    =0x0008;
const uint16_t PEPTIDE  =0x0010;
const uint16_t COFACTOR =0x0020;
const uint16_t CAVITY   =0x0040;
const uint16_t INTERS   =0x0080;
const uint16_t FRAG_RING=0x0200;
const uint16_t FRAG_LINKER=0x0400;
const uint16_t FRAG_SUBSTI=0x0800;
const std::map<std::string,uint16_t> nameToType={
    {"UNDEFINED",UNDEFINED},
    {"PROTEIN",PROTEIN},
    {"LIGAND",LIGAND},
    {"WATER",WATER},
    {"PEPTIDE",PEPTIDE},
    {"COFACTOR",COFACTOR},
    {"CAVITY",CAVITY},
    {"INTERS",INTERS},
    {"FRAG_RING",FRAG_RING},
    {"FRAG_LINKER",FRAG_LINKER},
    {"FRAG_SUBSTI",FRAG_SUBSTI},
};
const std::map<uint16_t,std::string> typeToName={
    {UNDEFINED,"UNDEFINED"},
    {PROTEIN,"PROTEIN"},
    {LIGAND,"LIGAND"},
    {WATER,"WATER"},
    {PEPTIDE,"PEPTIDE"},
    {COFACTOR,"COFACTOR"},
    {CAVITY,"CAVITY"},
    {INTERS,"INTERS"},
    {FRAG_RING,"FRAG_RING"},
    {FRAG_LINKER,"FRAG_LINKER"},
    {FRAG_SUBSTI,"FRAG_SUBSTI"}
};
}
#define NB_RESTYPE 13
namespace RESTYPE
{
const uint16_t UNDEFINED    =0x0001;//1
const uint16_t STANDARD_AA  =0x0002;//2
const uint16_t MODIFIED_AA  =0x0004;//4
const uint16_t NUCLEIC_ACID =0x0008;//8
const uint16_t WATER        =0x0010;//16
const uint16_t LIGAND       =0x0020;//32
const uint16_t SUGAR        =0x0040;//64
const uint16_t ORGANOMET    =0x0080;//128
const uint16_t METAL        =0x0200;//256
const uint16_t COFACTOR     =0x0400;//512
const uint16_t ION          =0x0800;//1024
const uint16_t PROSTHETIC   =0x1000;//2048
const uint16_t UNWANTED     =0x4000;//4094


const std::map<std::string,uint16_t> nameToType={
    {"UNDEFINED",UNDEFINED},
    {"AA",STANDARD_AA},
    {"MOD_AA",MODIFIED_AA},
    {"NUCLEIC",NUCLEIC_ACID},
    {"WATER",WATER},
    {"LIGAND",LIGAND},
    {"SUGAR",SUGAR},
    {"ORGANOMET",ORGANOMET},
    {"METAL",METAL},
    {"COFACTOR",COFACTOR},
    {"ION",ION},
    {"PROSTHETIC",PROSTHETIC},
    {"UNWANTED",UNWANTED},
};
const std::map<uint16_t,std::string> typeToName={
    {UNDEFINED,"UNDEFINED"},
    {STANDARD_AA,"AA"},
    {MODIFIED_AA,"MOD_AA"},
    {NUCLEIC_ACID,"NUCLEIC"},
    {WATER,"WATER"},
    {LIGAND,"LIGAND"},
    {SUGAR,"SUGAR"},
    {ORGANOMET,"ORGANOMET"},
    {METAL,"METAL"},
    {COFACTOR,"COFACTOR"},
    {ION,"ION"},
    {PROSTHETIC,"PROSTHETIC"},
    {UNWANTED,"UNWANTED"},
};
const std::map<uint16_t,std::string> typeToDBName={
    {UNDEFINED,"UNDEFINED"},
    {STANDARD_AA,"AA"},
    {MODIFIED_AA,"MOD_AA"},
    {WATER,"WATER"},
    {NUCLEIC_ACID,"NUCLEIC"},
    {LIGAND,"LIGAND"},
    {SUGAR,"SUGAR"},
    {ORGANOMET,"ORGANOMET"},
    {METAL,"METAL"},
    {ION,"ION"},
    {COFACTOR,"COFACTOR"},
    {PROSTHETIC,"PROSTHETIC"},
    {UNWANTED,"UNWANTED"},
};
const std::map<std::string,uint16_t> DBNameToType={
    {"UNDEFINED",UNDEFINED},
    {"AA",STANDARD_AA},
    {"MOD_AA",MODIFIED_AA},
    {"WATER",WATER},
    {"NUCLEIC",NUCLEIC_ACID},
    {"LIGAND",LIGAND},
    {"SUGAR",SUGAR},
    {"ORGANOMET",ORGANOMET},
    {"METAL",METAL},
    {"ION",ION},
    {"COFACTOR",COFACTOR},
    {"PROSTHETIC",PROSTHETIC},
    {"UNWANTED",UNWANTED},
};
}

namespace CHAINTYPE {
const uint16_t UNDEFINED =0x0001;
const uint16_t PROTEIN   =0x0002;
const uint16_t LIGAND    =0x0004;
const uint16_t PEPTIDE   =0x0008;
const uint16_t NUCLEIC    =0x0010;
const uint16_t WATER     =0x0020;
const std::map<uint16_t,std::string> typeToName={
    {UNDEFINED,"UNDEFINED"},
    {PROTEIN,"PROTEIN"},
    {LIGAND,"LIGAND"},
    {PEPTIDE,"PEPTIDE"},
    {NUCLEIC,"NUCLEIC"},
    {WATER,"WATER"},

};
}

namespace ERROR_BOND
{
const uint16_t BOND_LENGTH    =0x0001;
const uint16_t UNEXPECTED_BOND=0x0002;
const uint16_t BOND_EXISTS    =0x0004;
const uint16_t ATOM_CLASH     =0x0008;
}

namespace ERROR_ATOM
{
const uint16_t BAD_VALENCE      =0x0001;
const uint16_t ATOM_NOT_FOUND   =0x0002;
const uint16_t BAD_CHARGE       =0x0004;
const uint16_t ATOM_DUMMY       =0x0008;
const uint16_t NOT_EXISTING     =0x0010;
const uint16_t MISSING          =0x0020;
const uint16_t HYDROGEN_MISMATCH=0x0040;
}




namespace PREPRULE
{
const uint32_t SELENOMET=0x0001;
const uint32_t SELENOCYS=0x0002;
const uint32_t CONNECT=0x0004;
const uint32_t ASSIGN_RESTYPE=0x0008;
const uint32_t ASSIGN_ATMTYPE=0x0010;
const uint32_t ALL=SELENOCYS|SELENOMET|CONNECT|ASSIGN_RESTYPE|ASSIGN_ATMTYPE;

}

namespace METRIC
{
const uint16_t TANIMOTO=0x0001;
const uint16_t RTVERSKY=0x0002;
}
typedef uint16_t metric;

#endif // INTERTYPES_H


