#undef NDEBUG
#include <chrono>
#include <assert.h>
#include "headers/molecule/hetmanager.h"
#include "gc3tk.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/strutils.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/delmanager.h"
#include "headers/statics/delsingleton.h"
#include "headers/molecule/macromole_utils.h"


const std::string protspace::HETManager::mHETInternals=" SX1 SX2 SX3 LIG REF INH BIC SX0 INX MOL UNK SX9 ";
protspace::HETManager::HETManagerPtr& protspace::HETManager::get_instance()
{
    static HETManagerPtr objx(new protspace::HETManager);
    return objx;
}

protspace::HETManager::HETManager():mListEntries(10,true),mInput(nullptr)
{
    mWBinary=true;mAllLoaded=false;
    new protspace::TDelOrderSing<HETManager>(this,protspace::DelSingleton(2));


}

protspace::HETManager::~HETManager()
{

    if (mInput!= nullptr)delete mInput;
}


size_t protspace::HETManager::numEntry()const
{
    assert(mInput!=nullptr);
    return mInput->size();
}


size_t protspace::HETManager::numLoadedEntry()const
{
    return mListEntries.size();
}

void protspace::HETManager::open()
try{
   // std::cout <<"IS BINARY:"<<mWBinary<<std::endl;
    if (mWBinary)
    {
        mInput=new HETInputBin();
        mInput->loadPositions();
        if (mInput->isReady())    return ;

        delete mInput;
    }
//    std::cout <<"LOADING HETFILE"<<std::endl;
    mInput = new HETInputFile();
    mInput->loadPositions();
    if (mInput->isReady()) return ;
    throw_line("370901","HETManager::open","Unable to open files");

}catch(ProtExcept &e)
{
    if (e.getId()!="370901")e.addHierarchy("HETManager::open");
    throw;
}


protspace::HETEntry &protspace::HETManager::getEntry(std::string HET,
                                                     const bool &wMatrix,
                                                     const bool& check) throw(ProtExcept)

try{

    protspace::removeAllSpace(HET);
    if (mInput==nullptr)open();
    size_t pos;
    if (check)
    {
    bool ok=isLoaded(HET,pos);
    if (ok)return mListEntries.get(pos);
    }
     return loadMolecule(HET,wMatrix, check);

}catch(ProtExcept &e)
{
    e.addDescription("HET Involved : "+HET);
    e.addHierarchy("HETManager::getEntry");
    throw;
}



bool protspace::HETManager::isLoaded(const std::string& HET, size_t& pos)const
{
    const size_t size(mListEntries.size());
    for(pos=0;pos<size;++pos)
    {
        if (mListEntries.get(pos).getName()==HET)return true;
    }
    return false;
}


protspace::HETEntry&
protspace::HETManager::loadMolecule(const std::string &HET,const bool& wMatrix,
                                    const bool& wCheck) throw(ProtExcept)
try{
    size_t pos;
    if (wCheck && isLoaded(HET,pos))return mListEntries.get(pos);
    HETEntry* entry=mInput->loadMole(HET,wMatrix);
    mListEntries.add(entry);
    return *entry;
}catch(ProtExcept &e)
{
    e.addHierarchy("HETManager::loadMolecule");
    throw;
}


void protspace::HETManager::clear()
{
    mListEntries.clear();
    delete mInput; mInput=nullptr;mAllLoaded=false;
}




void protspace::HETManager::loadAll()
{

    if (mAllLoaded)return;
    mListEntries.clear();
    if (mInput==nullptr)open();
    mInput->prepForAll();;
    const auto& mPositions=mInput->getPositions();
    for(auto it = mPositions.begin();it!=mPositions.end();++it)
    {
        HETEntry* entry=mInput->loadMole((*it).first);
        mListEntries.add(entry);
    }
    mAllLoaded=true;


}

void protspace::HETManager::exportFile(const std::string& pBinFile,
                                       const std::string& pPosFile)const
{
    size_t nAt=0;
    for(size_t i=0;i<mListEntries.size();++i)
    {
        HETEntry& pEntry=mListEntries.get(i);
        nAt+=pEntry.getMole().numAtoms();
    }
    std::ofstream ofsB(pBinFile,std::ios::out);
    if (!ofsB.is_open())throw_line("371001","HETManager::exportFile","Unable to open binary file "+pBinFile);
    std::ofstream ofsP(pPosFile,std::ios::out);
    if (!ofsP.is_open())throw_line("371002","HETManager::exportFile","Unable to open position file "+pPosFile);
    ofsP.write((char*)&nAt,sizeof(size_t));
    size_t fpos=0;uint16_t classR;
    nAt=mListEntries.size();
    ofsP.write((char*)&nAt,sizeof(size_t));
    for(size_t i=0;i<mListEntries.size();++i)
    {
        HETEntry& pEntry=mListEntries.get(i);

         saveSerializedString(ofsP,pEntry.getMole().getName());
        fpos=ofsB.tellp();                            ofsP.write((char*)&fpos,sizeof(size_t));
          fpos=protspace::numAtom(pEntry.getMole(),6);ofsP.write((char*)&fpos,sizeof(size_t));
          fpos=protspace::numAtom(pEntry.getMole(),7);ofsP.write((char*)&fpos,sizeof(size_t));
          fpos=protspace::numAtom(pEntry.getMole(),8);ofsP.write((char*)&fpos,sizeof(size_t));
          classR=pEntry.getClass();ofsP.write((char*)&classR,sizeof(uint16_t));


        pEntry.serialize(ofsB);
    }
    ofsB.close();;
    ofsP.close();
}


void protspace::HETManager::getPossibleMatch(const size_t& nC,
                                             const size_t& nO,
                                             const size_t& nN,
                                             std::vector<protspace::StringPoolObj>& list)
{
    if (mInput==nullptr)open();
    if (!mInput->isBinary())loadAll();
    mInput->getPossibleMatch(nC,nO,nN,list);

}


void protspace::HETManager::getExactMatch(const size_t& nC,
                                          const size_t& nO,
                                          const size_t& nN,
                                          std::vector<StringPoolObj> &list)
{
    if (mInput==nullptr)open();
    if (!mInput->isBinary())loadAll();
    mInput->getExactMatch(nC,nO,nN,list);

}


void protspace::HETManager::assignResidueType(
        MacroMole& molecule,
        const bool &isInternal,
        const bool& setUpdatedVisible) throw(ProtExcept)
try{

    if (setUpdatedVisible)molecule.select(false);

    /// If updated, please also update internalNames in matchTemplate.cpp
    static const std::string internalNames=" SX1 SX2 SX3 LIG REF INH BIC SX0 INX MOL UNK SX9 ";
    static const std::string MetalName=" ZN2 CA2 ";
    for(size_t iRes=0;iRes < molecule.numResidue();++iRes)
    {
        MMResidue& residue = molecule.getResidue(iRes);
        const std::string& rName(residue.getName());
        if (isInternal)
        {
            if (internalNames.find(" "+rName+" ") != std::string::npos)
            {
                    residue.setResidueType(RESTYPE::LIGAND);
            continue;
            }

            if ( MetalName.find(" "+rName+" ")!=std::string::npos
                 && residue.numAtoms()==1 )
            {
                std::cout <<residue.getIdentifier()<<std::endl;
                assert(residue.getName().length()>=2);
                residue.setName(rName.substr(0,2));
            }
            if ((rName=="WAT" ||rName=="TIP") && residue.numHeavyAtom()==1
                    && (residue.numAtoms()==1||residue.numAtoms()==3))
            {
                residue.setName("HOH");
             }

        }

        try{
            if ((rName=="DG3"||rName=="DG5")&& (residue.numHeavyAtom() == 19||residue.numHeavyAtom() == 22)){
                std::cout <<residue.getIdentifier()<<"\tMODIFIED\n";
                residue.setName("DG");}
            if ((rName=="M5U")&& (residue.numHeavyAtom() == 21||residue.numHeavyAtom() == 37)){

                residue.setName("5MU");}
            if ((rName=="DC3"||rName=="DC5") && (residue.numHeavyAtom() == 19||residue.numHeavyAtom() == 16)){residue.setName("DC");
            std::cout <<residue.getIdentifier()<<"\tMODIFIED\n";}
            const HETEntry& HETtemplate = getEntry(residue.getName());
            if(!HETtemplate.isReplaced())
            {
                residue.setResidueType(HETtemplate.getClass()); continue;
            }
            const HETEntry& HETtemplate2 = getEntry(HETtemplate.getReplaced());
            /// TODO Add to molecule a residue error
            residue.setName(HETtemplate2.getName());
            residue.setResidueType(HETtemplate2.getClass());
            if (setUpdatedVisible)molecule.select(true);
        }catch (ProtExcept &e)
        {
            e.addDescription("Residue Involved: "+residue.getIdentifier());
            throw;
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="351901");///residue must exists
    e.addHierarchy("HETManager::assignResidueType");
    throw;
}

bool protspace::HETManager::isInList(const std::string& HET) {
    if (mInput==nullptr)open();
    return mInput->isInList(HET);

    }


void protspace::HETManager::toHETList(const std::string& pFile)
{
    std::ofstream ofs(pFile);
    if (!ofs.is_open())
        throw_line("371101","HETManager::toHETList","Unable to open file ");
    for(size_t i=0;i<mListEntries.size();++i)
    {
        const HETEntry& pEntry=mListEntries.get(i);
        ofs<<pEntry.getName()<<"|1\t";
        if (pEntry.isReplaced() )ofs<<pEntry.getReplaced()<<"\t";else ofs<<"/\t";
        ofs<<(unsigned) pEntry.getClass()<<"\t";
        const MacroMole& mole = pEntry.getMole();
        for(size_t iA=0;iA< mole.numAtoms();++iA)
        {
            const MMAtom& atom = mole.getAtom(iA);
            if (atom.getName()=="DuAr"|| atom.getName()=="DuCy")continue;
            ofs<<"["<<atom.getName()<<"|"<<atom.getMOL2();
            if (atom.getFormalCharge()!=0)ofs<<"|"<<(signed)atom.getFormalCharge();
            ofs<<"]";
        }
        for(size_t iB=0;iB<mole.numBonds();++iB)
        {
            const MMBond& bond=mole.getBond(iB);
            ofs<<"{"<<bond.getAtom1().getMID()<<"|"
                    <<bond.getAtom2().getMID()<<"|"
                    <<BOND::typeToMOL2.at(bond.getType())<<"}";
        }
        ofs<<"\n";

    }
    ofs.close();
}

std::vector<protspace::StringPoolObj> protspace::HETManager::getPositions()
{
    if (mInput==nullptr)open();
    assert(mInput!=nullptr);
    std::vector<protspace::StringPoolObj> results;
    const auto& mPositions=mInput->getPositions();
    for(auto it = mPositions.begin();it!=mPositions.end();++it)
    {
        results.push_back((*it).first);
    }
    return results;

}
