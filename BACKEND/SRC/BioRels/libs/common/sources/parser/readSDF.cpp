#include "headers/parser/readSDF.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/protpool.h"
#include "headers/statics/strutils.h"
#undef NDEBUG /// Active assertion in release

protspace::ReadSDF::ReadSDF(const std::string& path):
    ReaderBase(path),
    mAtomSymbol(protspace::ProtPool::Instance().string.acquireObject(mPosAtomSymbol))
{

}

protspace::ReadSDF::~ReadSDF()
{
    protspace::ProtPool::Instance().string.releaseObject(mPosAtomSymbol);

}

void protspace::ReadSDF::loadHeader()
{

    if (mLigne.find("V2000")==std::string::npos)
    {
        bool found=false;
        for(size_t i=0;i<=2;++i)
        {
            getLine();
            if(mLigne.find("V2000")==std::string::npos)continue;
            found=true;break;
        }
        if (!found)
            throw_line("430201",
                       "ReadSDF::loadHeader",
                       "Only read V2000 SD format");
    }

    nExpectedAtom=atoi(mLigne.substr(0,3).c_str());
    nExpectedBond=atoi(mLigne.substr(3,3).c_str());
    nExpectedAtomList=atoi(mLigne.substr(6,3).c_str());
    if (mLigne.length()>=18)
        nExpectedSText=atoi(mLigne.substr(15,3).c_str());
    ++nStep;
}

void protspace::ReadSDF::loadAtom(MacroMole& molecule)
try{

    mTmpCoords.clear();mMassDiff=0;

    mTmpCoords.setxyz(atof(mLigne.substr( 0,10).c_str()),
                      atof(mLigne.substr(10,10).c_str()),
                      atof(mLigne.substr(20,10).c_str()));
    mAtomSymbol=removeSpaces(mLigne.substr(31,3));

    mMassDiff=atoi(mLigne.substr(34,2).c_str());
    mCharge=atoi(mLigne.substr(36,3).c_str());
//    const int stereo=atoi(ligne.substr(39,3).c_str());
//    const int hcount=atoi(ligne.substr(42,3).c_str());
//    const int sCareBox=atoi(ligne.substr(45,3).c_str());
//    const int valence=atoi(ligne.substr(48,3).c_str());
//    xxxxx.xxxxyyyyy.yyyyzzzzz.zzzz aaaddcccssshhhbbbvvvHHHrrriiimmmnnneee
    MMAtom& atom= molecule.addAtom(molecule.getTempResidue(),
                                   mTmpCoords,
                                   mAtomSymbol,"",
                                   mAtomSymbol);

    atom.setFormalCharge(mCharge);
    nFoundAtom++;
    if( nFoundAtom== nExpectedAtom)
    {
        nStep++;
        if (nExpectedBond==0)nStep++;
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="350101");///NO ALIAS MOLECULE
    assert(e.getId()!="310802" && e.getId()!="350302");///NO MOL2 in SDF
    assert(e.getId()!="350301");///TEMP RESIDUE IS ALWAYS IN THE MOLECULE
    e.addHierarchy("ReadSDF::loadAtom");
    throw;
}catch(std::out_of_range &e)
{
    throw_line("420401","ReadSDF::loadAtom",
               "Unexpectected end of line \n"+std::string(e.what()));
}

void protspace::ReadSDF::loadBond(MacroMole& molecule)
try{

    // First atom number
    const int n1=atoi(mLigne.substr(0,3).c_str())-1;
    const int n2=atoi(mLigne.substr(3,3).c_str())-1;
    const int type=atoi(mLigne.substr(6,3).c_str());
    uint16_t btype=BOND::UNDEFINED;
    switch(type)
    {
    case 1:btype=BOND::SINGLE;break;
    case 2:btype=BOND::DOUBLE;break;
    case 3:btype=BOND::TRIPLE;break;
    case 4:btype=BOND::AROMATIC_BD;break;
    case 5:btype=BOND::DELOCALIZED;break;
    case 6:btype=BOND::DELOCALIZED;break;
    case 7:btype=BOND::DELOCALIZED;break;
    case 8:btype=BOND::UNDEFINED;break;
    }
    //const int stereo=atoi(ligne.substr(9,3).c_str());
    molecule.addBond(molecule.getAtom(n1),
                     molecule.getAtom(n2),
                     btype,
                     molecule.numBonds());
    nFoundBond++;
    if (nFoundBond==nExpectedBond)nStep++;
}catch(ProtExcept &e)
{
    assert(e.getId()!="350601");///ALIAS MOLECULE
    assert(e.getId()!="350602"&&e.getId()!="350603");///ATOM MUST BE IN MOLECULE
    e.addHierarchy("ReadSDF::loadBond");
    throw;
}catch(std::out_of_range &e)
{
    throw_line("420301","ReadSDF::loadBond",
               "Unexpectected end of line \n"+std::string(e.what()));
}






///
/// \brief SDF file is define as text block, each one is associated as a step
/// 0 : COUNT LINE
/// 1 : ATOM BLOCK
/// 2 : BOND BLOCK
/// 3 : ATOM LIST BLOCK
/// 4 : STEXT
/// 5 : PROPERTY BLOCK
///
void protspace::ReadSDF::load(MacroMole& molecule) throw(ProtExcept)
{

    if (!mIfs.is_open())
        open();
    if (!mIfs.is_open())
        throw_line("430101",
                   "ReadSDF::load",
                   "No file opened");

    nStep=0;            bool firstLine=true;
    nExpectedAtom=0;     nExpectedBond=0;   nExpectedAtomList=0;
    nExpectedSText=0;    nFoundAtom=0;      nFoundBond=0;
    mMassDiff=0;         mCharge=0;         mProps.clear();;

  try{
    // Reading file
    while (getLine())
    {
            const size_t lineLength=mLigne.length();
            if (lineLength==0)continue;
            if (mLigne.at(0)=='#')continue;// EMPTY LINE OR COMMENTS
            if (mLigne.at(0)=='M'               // END OF THE MOLECULE
             && mLigne.find("END")!=std::string::npos)nStep++;
            if (firstLine){molecule.setName(mLigne);firstLine=false;}
            if (mLigne == "$$$$")break;
            switch(nStep)
            {
            case 0: loadHeader();break;
            case 1: loadAtom(molecule);break;
            case 2: loadBond(molecule);break;
            }
            if (mLigne.at(0)=='M')assignProp(molecule);
            if (lineLength >=3 &&mLigne.substr(0,3)=="> <") addProp();
    }
    }catch(ProtExcept &e)
    {

        if (e.getId()=="030303"||e.getId()=="350102")///Bad allocation
                throw_line("420601","ReadSDF::load",
                           "Unable to read file - Memory allocation issue");
        e.addHierarchy("ReadSDF::load");
        e.addDescription("Molecule "+molecule.getName());
        e.addDescription("Line involved : "+mLigne);
        throw;
    }

}

void protspace::ReadSDF::assignProp(MacroMole& molecule)
try{

    {
        const std::string mPropType=mLigne.substr(3,3);
        if (mPropType=="CHG")
        {
            const int nCountProp=atoi(mLigne.substr(6,3).c_str());
            for(int i=0;i< nCountProp;++i)
            {
                const int atomId=atoi(mLigne.substr(10+8*i,3).c_str())-1;
                const double chargeN=atoi(mLigne.substr(10+8*i+4,3).c_str());
                molecule.getAtom((const size_t &) atomId).setFormalCharge(chargeN);
            }
        }
    }
}catch(ProtExcept &e)
{
    e.addHierarchy("ReadSDF::assignProp");
    throw;
}catch(std::out_of_range &e)
{
    throw_line("420501","ReadSDF::assignProp",
               "Unexpectected end of line \n"+std::string(e.what()));
}


void protspace::ReadSDF::addProp()
{
    std::vector<std::string> values;
        const std::string header=mLigne.substr(4,mLigne.find_last_of(">",4)-4);
        values.clear();
        while (mLigne != "")
        {
            getLine();
            if (mLigne =="")break;
            values.push_back(mLigne);
        }
        mProps.insert(std::make_pair(header,values));
}
