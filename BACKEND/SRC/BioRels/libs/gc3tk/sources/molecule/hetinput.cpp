#undef NDEBUG
#include <assert.h>
#include <chrono>
#include "headers/molecule/hetinput.h"
#include "gc3tk.h"
#include "headers/statics/protExcept.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/hetentry.h"
#include "headers/statics/strutils.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/protpool.h"



void protspace::HETInputAbst::setPosition(const std::string& pHET)
{
    if (!mIfs.is_open())
        throw_line("370101",
                   "HETInputAbst::setPosition",
                   "File is not opened");
    const auto itPos = mPositions.find(pHET);
    if (itPos==mPositions.end())
        throw_line("370102",
                   "HETInputAbst::setPosition",
                   "No Molecule by this name found");
    mIfs.seekg((*itPos).second);
}


void protspace::HETInputAbst::getPossibleMatch(const size_t& nC,
                      const size_t& nO,
                      const size_t& nN,
                      std::vector<protspace::StringPoolObj>& list)
{
    for(auto it = mCounts.begin();it!=mCounts.end();++it)
    {
        const Counts& co=(*it).second;
        if (nC <= co.nC && nO <=co.nO && nN <= co.nN)list.push_back((*it).first);
    }
}


void protspace::HETInputAbst::getExactMatch(const size_t& nC,
                      const size_t& nO,
                      const size_t& nN,
                      std::vector<StringPoolObj> &list)const
{
    for(auto it = mCounts.begin();it!=mCounts.end();++it)
    {

        const Counts& co=(*it).second;
        if (nC == co.nC && nO ==co.nO && nN == co.nN)list.push_back((*it).first);
    }
}



void protspace::HETInputFile::loadPositions()
{
    const std::string path_posit=getHETListFlat();
    std::cout <<path_posit<<std::endl;
    if (path_posit.empty())
        throw_line("370301","HETInputFile::loadPositions","NO GC3TK_HOME parameter defined");



    mIfs.open(path_posit);
    if (!mIfs.is_open()){mIsReady=false;return;}

    std::string line;
    std::streampos pos;pos=mIfs.tellg();

    while (!mIfs.eof())
    {
        safeGetline(mIfs,line);
        size_t posL = line.find_first_of("|");
        if (line.substr(0,posL)=="")continue;
        mPositions.insert(std::make_pair(line.substr(0,posL),pos));
        pos = mIfs.tellg();
    }
    mIfs.clear();
    mIsReady=true;
}


protspace::HETEntry*
protspace::HETInputFile::loadMole(const std::string &pHET, const bool &wMatrix)
throw(ProtExcept)
try{

    setPosition(pHET);
    std::string line;

    safeGetline(mIfs,line);
//std::cout <<line<<std::endl;
    std::vector<std::string> tokens;
    tokenStr(line,tokens,"\t");



    HETEntry* resEntry=new HETEntry(pHET,tokens.at(1));
    resEntry->getMole().setName(pHET);
    resEntry->getMole().addResidue(pHET,"A",1,false);

    const int rClass=atoi(tokens.at(2).c_str());
    //    cout << rClass<< " " << HET<<endl;
    switch(rClass)
    {

    case 2 :    resEntry->setResClass(RESTYPE::STANDARD_AA );break;
    case 4 :    resEntry->setResClass(RESTYPE::MODIFIED_AA );break;
    case 8 :    resEntry->setResClass(RESTYPE::NUCLEIC_ACID);break;
    case 16 :   resEntry->setResClass(RESTYPE::WATER       );break;
    case 32:    resEntry->setResClass(RESTYPE::LIGAND);      break;
    case 64:    resEntry->setResClass(RESTYPE::SUGAR);       break;
    case 128:   resEntry->setResClass(RESTYPE::ORGANOMET);   break;
    case 512:   resEntry->setResClass(RESTYPE::METAL);       break;
    case 1024:  resEntry->setResClass(RESTYPE::COFACTOR);    break;///
    case 2048:  resEntry->setResClass(RESTYPE::ION        ); break;///
    case 4096:  resEntry->setResClass(RESTYPE::PROSTHETIC);  break;///
    case 16384: resEntry->setResClass(RESTYPE::UNWANTED);    break;
    default: throw_line("370401","HETInputFile::loadMole","Unrecognized residue type");
    }
    if (rClass !=0)
        if (!perceiveMole(resEntry->getMole(),tokens.at(3)))
            throw_line("370402","HETInputFile::loadMole","Unable to perceive molecule");
   if (wMatrix) resEntry->calcDistMatrix();

    return resEntry;
}catch(ProtExcept &e)
{
    /// If these exceptions are thrown, it means that the
    /// file is corrupted, so the program should immidiatly stop working
    assert(e.getId()!="370401" && e.getId()!="370402");
}catch(std::bad_alloc &e)
{
    throw_line("370403","HETInputFile::loadMole","Bad allocation "+std::string(e.what()));
    return nullptr;
}



bool protspace::HETInputFile::perceiveMole(MacroMole& molecule,const std::string& molf)
{
    try{
    const size_t len = molf.length();
    bool wrong=false;
    std::vector<std::string> token2;
    MMResidue& residue = molecule.getResidue(0);
    for (size_t iPos=0;iPos < len;++iPos)
    {
        const char& character = molf.at(iPos);
        if (character == '[')
        {
            const size_t pos = molf.find_first_of("]",iPos+1);
            if (pos == std::string::npos)
                throw_line("370501",
                           "HETInputFile::perceiveMolecule",
                           "Wrongly form atom block");
            const std::string atomBlock = molf.substr(iPos+1,pos-iPos-1);
            token2.clear();
            tokenStr(atomBlock,token2,"|"   );

            if (token2.size() ==1){
                throw_line("370502",
                           "HETInputFile::perceiveMolecule",
                           "Atom block incomplete : "+atomBlock);
                wrong=true;break;}
            try{
                MMAtom& atom = molecule.addAtom(residue);
                atom.setName(token2.at(0));
                atom.setMOL2Type(token2.at(1));
                if (token2.size()==3)
                    atom.setFormalCharge(atof(token2.at(2).c_str()));
//                std::cout << residue.getName()<<" " <<molecule.numAtoms()<<std::endl;
            }catch(ProtExcept &e)
            {
                e.addDescription(atomBlock);
                throw;
            }
            iPos=pos;
        }
        if (character == '{')
        {
            const size_t pos = molf.find_first_of("}",iPos+1);
            if (pos == std::string::npos)
                throw_line("370503",
                           "HETManager::loadMolecule",
                           "Wrongly form bond block");

            const std::string atomBlock = molf.substr(iPos+1,pos-iPos-1);
            try{
            token2.clear();
            tokenStr(atomBlock,token2,"|");
            uint16_t typebond=BOND::UNDEFINED;
            if(token2.at(2)=="1") typebond=BOND::SINGLE;
            if(token2.at(2)=="2") typebond=BOND::DOUBLE;
            if(token2.at(2)=="3") typebond=BOND::TRIPLE;
            if(token2.at(2)=="ar") typebond=BOND::AROMATIC_BD;
            if(token2.at(2)=="am") typebond=BOND::AMIDE;
            if(token2.at(2)=="de") typebond=BOND::AROMATIC_BD;
            molecule.addBond(molecule.getAtom(atoi(token2.at(0).c_str())),
                             molecule.getAtom(atoi(token2.at(1).c_str())),
                             typebond,molecule.numBonds());


            iPos=pos;
            }catch(ProtExcept &e)
            {
                e.addDescription("Reading bond "+atomBlock);
                throw;

            }
        }

    }
    Counts count;
    count.nC=protspace::numAtom(molecule,6);
    count.nN=protspace::numAtom(molecule,7);
    count.nO=protspace::numAtom(molecule,8);
    mCounts.insert(std::make_pair(molecule.getName(),count));
    return !wrong;
}catch(ProtExcept &e)
    {

         assert(e.getId()!="351901");/// Residue must exist
         assert(e.getId()!="350101");/// Molecule cannot be an alias
         assert(e.getId()!="250201");/// Residue must exist
         assert(e.getId()!="310801");/// MOL2 type must be given
         assert(e.getId()!="310802");/// MOL2 type must exist
         assert(e.getId()!="030401");/// Atom must exists
         assert(e.getId()!="350601");/// Molecule cannot be an alias
         assert(e.getId()!="350602" &&e.getId()!="350603");///Atom must exists
         assert(e.getId()!="350604");/// Atom must be different
        e.addHierarchy("HETManager::perceiveMole");
        throw;
    }
}

void protspace::HETInputBin::loadPositions()
{


    const std::string path=getTG_DIR_HOME();
    //std::cout <<path<<std::endl;
    if (path.empty())
        throw_line("370601","HETInputBin::loadPosition","NO GC3TK_HOME parameter defined");
    const std::string path_posit=getHETList()+".posit";
    //std::cout <<path_posit<<std::endl;
    const std::string alt_path=getAltTG_DIR_PATH();
    mIfs.open(path_posit);

    if (!mIfs.is_open()){
        if (!alt_path.empty())
        {
            mIfs.open(alt_path+"/HETLIST.posit");
            if (!mIfs.is_open()){
                mIsReady=false;return;
            }
        }
    }

    size_t nNeedAtom=0;


    mIfs.read((char*)&nNeedAtom,sizeof(size_t));
    size_t nEntry=0, posFile;
    mIfs.read((char*)&nEntry,sizeof(size_t));
    std::string resname="";
    Counts count;
    for(size_t i=0;i<nEntry;++i)
    {
        readSerializedString(mIfs,resname);
        mIfs.read((char*)&posFile,sizeof(size_t));
        mIfs.read((char*)&count.nC,sizeof(size_t));
        mIfs.read((char*)&count.nN,sizeof(size_t));
        mIfs.read((char*)&count.nO,sizeof(size_t));
        mIfs.read((char*)&count.mClass,sizeof(uint16_t));

        mPositions[resname]=posFile;
        mCounts[resname]=count;
    }

    mIfs.close();
    mIsReady=openBinary();
}


bool protspace::HETInputBin::openBinary()
{
    const std::string path=getTG_DIR_HOME();
    if (path.empty())
        throw_line("370701","HETInputBin::openBinary","NO GC3TK_HOME parameter defined");
    const std::string path_posit=getHETList();
    //std::cout <<"PATH:"<<path_posit<<"\n";
    const std::string alt_path=getAltTG_DIR_PATH();
     mIfs.open(path_posit,std::ios::in|std::ios::binary);
     if (!mIfs.is_open())
     {
         if (!alt_path.empty())
         {
             mIfs.open(alt_path+"/HETLIST.objx",std::ios::in|std::ios::binary);
             if (!mIfs.is_open())return false;
         }else return false;
     }
    return true;
}

protspace::HETEntry*
protspace::HETInputBin::loadMole(const std::string& pHET,const bool& wMatrix) throw(ProtExcept)
try{

setPosition(pHET);
    if (mIfs.tellg()==-1)
    {
        throw_line("370801","loadMole","Error while reading file");
    }
    HETEntry* entry = new HETEntry();
    entry->unserialize(mIfs,wMatrix);

 return entry;
}catch(ProtExcept &e)
{
    if (e.getId()!="370801")e.addHierarchy("HETInputBind::loadMolecule");
    e.addDescription("HET Requested: "+pHET);
    throw;
}

void protspace::HETInputBin::prepForAll()
{
    protspace::ProtPool::coord.preRequest(nNeedAtom);
}
