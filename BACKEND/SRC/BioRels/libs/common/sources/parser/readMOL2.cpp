#include <string>

#include "headers/parser/readMOL2.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmbond.h"
#include "headers/statics/strutils.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/protpool.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;


ReadMOL2::ReadMOL2(const std::string& path):
    ReaderBase(path),
    mNAtomMole(0),
    mNResidueMole(0),
    mForceSubstID(false),
    mTokens(15,"")
{


}

ReadMOL2::~ReadMOL2()
{

}



void ReadMOL2::load(MacroMole &molecule) throw(ProtExcept)
{
    if (!molecule.isOwner())
        throw_line("410106",
                   "ReadMOL2::load",
                   "Molecule is not owner");

    if (!mIfs.is_open()) open();

    size_t nExpectedAtom=0;///Expected number of atoms defined in @<TRIPOS>MOLECULE
    size_t nExpectedBond=0;///Expected number of bonds defined in @<TRIPOS>MOLECULE
    size_t nExpectedRes=0;///Expected number of substructure defined in @<TRIPOS>MOLECULE
    std::streampos posAtom=0;///Position in the file of @<TRIPOS>ATOM
    std::streampos posBond=0;///Position in the file of @<TRIPOS>BOND
    std::streampos posRes=0;///Position in the file of @<TRIPOS>SUBSTRUCTURE

    mNAtomMole=molecule.numAtoms();
    mNResidueMole=molecule.numResidue();



    bool tripmol_found=false;

    // First scan the whole file to get  pointer of headers
    while(getLine())
    {if (mLigne[0]!='@')continue;

        if (mLigne.find("@<TRIPOS>MOLECULE")!=std::string::npos)
        {
            // Handle multi-mol2
            if (tripmol_found) break;
            readMOL2Header(molecule,
                           nExpectedRes,
                           nExpectedAtom,
                           nExpectedBond);

            tripmol_found=true;

        }
        else if (mLigne.find("@<TRIPOS>SUBSTRUCTURE")!= std::string::npos)
        {
            posRes=getFilePos();
        }
        else if (mLigne.find("@<TRIPOS>ATOM")!= std::string::npos)
        {
            posAtom=getFilePos();
        }
        else if (mLigne.find("@<TRIPOS>BOND")!=std:: string::npos)
        {
            posBond=getFilePos();
        }
    }

    if (!tripmol_found){
        if (mIfs.eof())return;
        throw_line("410101",
                   "ReadMOL2::load",
                   "@<TRIPOS>MOLECULE block not found");
    }
    if (nExpectedAtom==0)
        throw_line("410102",
                   "ReadMOL2::load",
                   "Number of atoms not given in MOLECULE block");
    if (posAtom==0 && nExpectedAtom >0)
        throw_line("410103",
                   "ReadMOL2::load",
                   "No ATOM block found while expecting atoms");
    if (posBond==0  && nExpectedBond >0)
        throw_line("410104",
                   "ReadMOL2::load",
                   "No BOND block found while expecting bonds");
    if (posRes==0  && nExpectedRes >0)
        throw_line("410105",
                   "ReadMOL2::load",
                   "No Residue block found while expecting Residues");


    if (molecule.getName().length()==0)
    {
        const size_t posDot=mFilePath.find_last_of(".");
        const size_t posSlash=mFilePath.find_last_of("/");

        molecule.setName(mFilePath.substr(posDot, posSlash-posDot-1));
    }
    try{
        if (posRes >0)
        {
            mFposition=posRes;
            mIfs.clear();
            mIfs.seekg(posRes,mIfs.beg);
            readMOL2Substructure(nExpectedRes,molecule);

        }

        if(posAtom >0)
        {
            mFposition=posAtom;
            mIfs.clear();
            mIfs.seekg(mFposition,mIfs.beg);
            readMOL2Atom(nExpectedAtom,molecule);
        }

        if (posBond >0)
        {
            mFposition=posBond;
            mIfs.clear();
            mIfs.seekg(mFposition,mIfs.beg);
            readMOL2Bond(nExpectedBond,molecule);
        }
    }catch(ProtExcept &e)
    {
        /// No  file opened
        assert(e.getId()!="410201" && e.getId()!="410401" && e.getId()!="410501");

        /// Bad allocation
        if (e.getId()=="351401" ||e.getId()=="351502" || e.getId()=="350102" || e.getId()=="030303")
        {
            throw_line("410107",
                       "ReadMOL2::load",
                       "Error while loading file - Memory allocation issue\n");
        }

        e.addHierarchy("MoleRead::loadAsMOL2");
        e.addDescription("File to load : "+mFilePath);
        throw;
    }
}

size_t ReadMOL2::prepLine(const size_t& expectedSize)
{
    for(std::string& v:mTokens)v="";
    // Splitting line according to spaces :
    size_t tokSize(tokenReuseStr(mLigne,mTokens," ",false));

    if (tokSize<expectedSize)
    {
        for(std::string& v:mTokens)v="";
        const size_t tokSize2=tokenReuseStr(mLigne,mTokens,"\t",true);
        if (tokSize2<tokSize)
        {
            for(std::string& v:mTokens)v="";
            tokSize=(tokenReuseStr(mLigne,mTokens," ",false));
        }else tokSize=tokSize2;
    }
    if (tokSize < expectedSize)
        throw_line("410301","ReadMOL2::prepline",
                   "Wrong number of columns. Expected minimum of "+
                   std::to_string(expectedSize)+". Got "+std::to_string(tokSize));
    return tokSize;
}



void ReadMOL2::readMOL2Header(MacroMole& molecule,
                              size_t& nExpectedRes,
                              size_t& nExpectedAtom,
                              size_t& nExpectedBond) throw(ProtExcept)
{


    // FIRST LINE IS THE NAME
    getLine(); molecule.setName(mLigne);
    // SECOND LINE DEFINES EXPECTED ATOM/BOND/SUBSTRUCTURE...

    getLine();
    const size_t tokSize(prepLine(1));


    switch (tokSize)
    {
    case 5:
    case 4:
    case 3:
        nExpectedRes=atoi(mTokens.at(2).c_str());
    case 2:
        nExpectedBond=atoi(mTokens.at(1).c_str());
    case 1:
        nExpectedAtom=atoi(mTokens.at(0).c_str());
        break;
    }


}





size_t ReadMOL2::correctName(std::string& pName)const
{
    //Case of internal structure where residue name can be
    // SO4_2 => In that case _2 is transformed in 0 with atoi
    // So we need to check for any _ and remove it.
    const size_t lenName(pName.length());
    if( pName.find("_") != std::string::npos)ReaderBase::correctName(pName);
    size_t pos;
    protspace::ProtPool& pool=protspace::ProtPool::Instance();
    std::string& subName= pool.string.acquireObject(pos);
    // Case of metallic residues that has only two letters :

    subName=(pName.substr(0,2));
    size_t length=3, tmp_len;
    if (lenName==3 && (
                subName=="MG" ||
                subName=="NA" ||
                subName=="ZN" ||
                subName=="CA" ||
                subName=="CL")) length=2;
    else if (lenName==2 && (
                 pName.substr(0,1)=="K"  ))length=1;
    else if (lenName<=3)
    {
        tmp_len=mTokens.at(9).length();
        if (tmp_len!=0)length=tmp_len;
    }
    pool.string.releaseObject(pos);
    return length;
}




void ReadMOL2::readMOL2Substructure(const size_t& nExpectedRes,
                                    MacroMole& molecule) throw(ProtExcept)
{
    if (!mIfs.is_open())
        throw_line("410201",
                   "ReadMOL2::readMOL2Substructure",
                   "No file opened");

    const bool hasExclusionRule(!mListChainsRule.empty());
    size_t nFoundRes=0,tokSize=0;

    int root_atom=0, subst_id=0;
    ///TODO Handle dict_type and inter_bond

    mRootAtom.clear();

    while ( getLine())
    {try {
            if (mLigne.substr(0,9) == "@<TRIPOS>" || mLigne=="")break;
            if (mLigne.substr(0,1)=="#")continue;
//std::cout << mLigne<<"\n";
            tokSize=prepLine(3);

             root_atom   =-1; subst_id    =-1;

            ///0 : subst_id     1 : subst_name      2: root_atom
            /// 3: subst_type   4: dict_type        5: chain
            /// 6: sub_type     7: inter_bond       8:status
            /// 9: comments
            /// Converting integer values:
            if (tokSize >=3)
            {
                subst_id=atoi(mTokens.at(0).c_str());
                root_atom=atoi(mTokens.at(2).c_str());
            }
//            if (tokSize>=5) dict_type=atoi(mTokens.at(4).c_str());
//            if (tokSize>=8) inter_bond=atoi(mTokens.at(7).c_str());

            if (hasExclusionRule && !checkChainRule(mTokens.at(5)))
            {       mListExclusionResidue.push_back(subst_id);
                continue;
            }
            const size_t length(correctName(mTokens.at(1)));
            MMResidue& residue=
                    molecule.addResidue(mTokens.at(1).substr(0,length),
                                        (mTokens.at(5)==""||mTokens.at(5)=="****")?"XX":mTokens.at(5),
                                        atoi(mTokens.at(1).substr(length).c_str()),
                                        mForceResCheck);
            if (mForceSubstID)mSubstID[subst_id]=&residue;
//            std::cout << residue.getIdentifier()<<"\t"<<residue.getFID()<<"\t"<<residue.getMID()<<"\n";
            if (root_atom != -1)
                mRootAtom.insert(std::make_pair(&residue,root_atom));
            nFoundRes++;
        }catch (ProtExcept &e)
        {
            /// Molecule cannot be an alias
            assert(e.getId()!="351501");
            e.addHierarchy("ReadMOL2::readMOL2Substructure");
            e.addDescription("Line involved : "+mLigne);
            throw;
        }
    }


    if (nFoundRes != nExpectedRes)
        throw_line("410202",
                   "ReadMOL2::readMOL2Substructuree",
                   "Number of Residue found ("+std::to_string(nFoundRes)+") differs from expected Residues ("+std::to_string(nExpectedRes)+")");

}





/**
 * DESCRIPTION
 * atom_id atom_name x y z atom_type [subst_id [subst_name [charge [status_bit]]]]
 * - atom_id (integer) = the ID number of the atom at the time the file was
 *     created. This is provided for reference only and is not used when the
 *     .mol2 file is read into SYBYL.
 * - atom_name (string) = the name of the atom.
 * - x (real) = the x coordinate of the atom.
 * - y (real) = the y coordinate of the atom.
 * - z (real) = the z coordinate of the atom.
 * - atom_type (string) = the SYBYL atom type for the atom.
 * - subst_id (integer) = the ID number of the substructure containing the atom.
 * - subst_name (string) = the name of the substructure containing the atom.
 * - charge (real) = the charge associated with the atom.
 * - status_bit (string) = the internal SYBYL status bits associated with the
 *   atom. These should never be set by the user. Valid status bits are
 *   DSPIOD, TYPECOL, CAP, BACKBONE, DICT, ESSENTIAL, WATER and
 *   DIRECT.
 *
 */
void ReadMOL2::readMOL2Atom(const size_t& nExpectedAtom,
                            MacroMole &molecule)
throw(ProtExcept)
{

    if (!mIfs.is_open())
        throw_line("410401",
                   "ReadMOL2::readMOL2Atom",
                   "No file opened");

    MMResidue& tempMMResidue = molecule.getTempResidue();

    size_t nFoundAtom=0, tokSize=0;
    int    atom_id=0,subst_id=0;
    Coords coo;
    double charge=0;
    // Reading file
    while (getLine())
    {try{
            // Ensure that it's not another block
            if (mLigne.substr(0,9) == "@<TRIPOS>" )break;

            // Splitting the line according to space
            tokSize=prepLine(6);
            //   cout <<"READ"<<tokens.size()<<endl;
            // Initialize values
            atom_id=0;
            coo.clear();
            subst_id=0;
            charge=0;
            ///0 : atom_id      1: atom_name        2:x     3:y     4:z
            ///5: atom_type     6: subst_id         7:subst_name    8:charge
            /// 9:status_bit
            if (tokSize>=5)
            {
                atom_id     =atoi(mTokens.at(0).c_str());
                coo.setxyz(atof(mTokens.at(2).c_str()),
                           atof(mTokens.at(3).c_str()),
                           atof(mTokens.at(4).c_str()));
            }
            if (tokSize>=7)  subst_id    =atoi(mTokens.at(6).c_str());
            if (tokSize>=9)  charge      =atof(mTokens.at(8).c_str());

//            std::cout << mLigne<<" " <<"\n"<<subst_id<<" " << subst_id-1+mNResidueMole<<"\t"<< molecule.numResidue()<<std::endl;


            if (!mListExclusionResidue.empty() &&
                    std::find(mListExclusionResidue.begin(),
                              mListExclusionResidue.end(),
                              subst_id) != mListExclusionResidue.end())continue;

            MMResidue * res=(MMResidue*)NULL;
            if (mTokens.at(7).substr(0,1)== "<" &&
                mTokens.at(7).substr(mTokens.at(7).length()-1)==">") res=&tempMMResidue;
            else if (!mForceSubstID && subst_id!=0) res= &molecule.getResidue(subst_id-1+mNResidueMole);
            else if (mForceSubstID) res=mSubstID[subst_id];
            else res = &tempMMResidue;

            //Creating atom
            MMAtom& atom= molecule.addAtom(*res,coo,mTokens.at(1),mTokens.at(5));

            atom.setFID(atom_id);
            atom.setFormalCharge(charge);
            nFoundAtom++;

        }catch(ProtExcept &e)
        {
            /// Cannot be an alias molecule & residue MUST be in the molecule
            assert(e.getId()!="350101" && e.getId()!="350301");
            if (e.getId()=="351901")
                e.addDescription("Residue Number:"+std::to_string(subst_id+1+mNResidueMole)+
                               "/"+std::to_string(molecule.numResidue()));
            e.addHierarchy("ReadMOL2::readMOL2Atom");
            e.addDescription("Line involved : "+mLigne);
            throw;
        }

    }

    if (nFoundAtom != nExpectedAtom)
        throw_line("410402",
                   "ReadMOL2::readMOL2Atom",
                   "Number of atoms found differs from expected atoms");




}

/**
 * DESCRIPTION :
 *  bond_id origin_atom_id target_atom_id bond_type [status_bits]
 *
 * - bond_id (integer) = the ID number of the bond at the time the file was
 * created. This is provided for reference only and is not used when the
 * .mol2 file is read into SYBYL.
 * - origin_atom_id (integer) = the ID number of the atom at one end of the bond.
 * - target_atom_id (integer) = the ID number of the atom at the other end of the bond.
 * - bond_type (string) = the SYBYL bond type (see below).
 * - status_bits (string) = the internal SYBYL status bits associated with the
 *   bond. These should never be set by the user. Valid status bit values are
 *  TYPECOL, GROUP, CAP, BACKBONE, DICT and INTERRES.
 *
 * List of bond types :
 *  1 = single
 *  2 = double
 *  3 = triple
 * am = amide
 * ar = aromatic
 * du = dummy
 * un = unknown (cannot be determined from the parameter tables)
 * nc = not connected

 */
void ReadMOL2::readMOL2Bond(const size_t& nExpectedBond,
                            MacroMole &molecule)
throw(ProtExcept)
{
    if (!mIfs.is_open())
        throw_line("410501",
                   "ReadMOL2::readMOL2Bond",
                   "No file opened");

    size_t nFoundBond=0, tokSize;
    int bond_id =0;
    unsigned int origin_atom_id=0;
    unsigned int target_atom_id=0;
    size_t btype_Pos,status_pos;
    protspace::ProtPool& pool=protspace::ProtPool::Instance();
    std::string &bond_type=pool.string.acquireObject(btype_Pos);bond_type="";
    std::string &status_bit=pool.string.acquireObject(status_pos);status_bit="";
    uint16_t bondVal=BOND::UNDEFINED;
    // Reading file
    while (getLine())
    {


        try{

            // Ensure that it's not another block
            if (mLigne.substr(0,9) == "@<TRIPOS>" || mLigne.empty())break;

            // Splitting the line according to space
            tokSize=prepLine(4);

            // Initialize values
            bond_id       =0;
            origin_atom_id=0;
            target_atom_id=0;
            bond_type     ="";
            status_bit  ="";
            bondVal=BOND::UNDEFINED;

            switch(tokSize)
            {
            case 5:    status_bit     =mTokens.at(4);
            case 4:    bond_type      =mTokens.at(3);
                target_atom_id =atoi(mTokens.at(2).c_str())+mNAtomMole;
                origin_atom_id =atoi(mTokens.at(1).c_str())+mNAtomMole;
                bond_id        =atoi(mTokens.at(0).c_str());
            }

            if (bond_type == "1") bondVal=BOND::SINGLE;
            else if (bond_type == "2") bondVal=BOND::DOUBLE;
            else if (bond_type == "3") bondVal=BOND::TRIPLE;
            else if (bond_type == "ar") bondVal=BOND::AROMATIC_BD;
            else if (bond_type == "de") bondVal=BOND::AROMATIC_BD;
            else if (bond_type == "am") bondVal=BOND::AMIDE;
            else if (bond_type == "du") bondVal=BOND::DUMMY;
            else if (bond_type == "un" || bond_type=="nc") bondVal=BOND::UNDEFINED;
            else throw_line("410502",
                           "ReadMOL2::readMOL2Bond",
                            "Unrecognized bond type");

            if (origin_atom_id > molecule.numAtoms())
                throw_line("410503",
                           "ReadMOL2::readMOL2Bond",
                           "Origin atom id is above the number of atoms");
            if (target_atom_id > molecule.numAtoms())
                throw_line("410504",
                           "ReadMOL2::readMOL2Bond",
                           "Target atom id is above the number of atoms");

            molecule.addBond(molecule.getAtom(origin_atom_id-1),
                             molecule.getAtom(target_atom_id-1),
                             bondVal,
                             bond_id);
            nFoundBond++;

        }catch(ProtExcept &e)
        {
            ///getAtom should always work, since we check for boundaries.
            /// No alias molecule
            assert(e.getId()!="030401" && e.getId()!="350601");
            /// Atom must be part of this molecule
            assert(e.getId()!="350602" && e.getId()!="350603");


            e.addDescription("--- Line Involved : "+mLigne);
            if (e.getId() == "350603" || e.getId() != "030303" || e.getId() != "410301")
                e.addHierarchy("ReadMOL2::readMOL2Bond");

            throw;
        }
    }

    if (nFoundBond != nExpectedBond)
        throw_line("410505",
                   "ReadMOL2::readMOL2Bond",
                   "Number of bonds found differs from expected bond");

    pool.string.releaseObject(btype_Pos);
    pool.string.releaseObject(status_pos);

}





