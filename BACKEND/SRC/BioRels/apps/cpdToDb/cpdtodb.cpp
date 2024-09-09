#include "cpdtodb.h"
#include "headers/statics/protpool.h"

#include "headers/statics/strutils.h"
#include "headers/parser/readPDB.h"
#include "headers/molecule/hetmanager.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerSDF.h"
#include "cpdtoxml.h"

CpdToDB::CpdToDB(const protspace::Argcv& args):mArgs(args),mListCompounds(20,true)
{
    mOverall=true;
    mVerbose=mArgs.hasOption("-v");
}

void CpdToDB::run()
try{
    processExternals();

    bool withoutPreviousVersion(mArgs.hasOption("-noV"));
    protspace::StringPoolObj path("");
if (mArgs.getOptArg("-oXML",path.get())) outputXMLFile(path.get());
    if (mArgs.getOptArg("-oB",path.get()))outputBinaryFile(path.get(),withoutPreviousVersion);
    if (mArgs.getOptArg("-oSDF",path.get()))outputSDF(path.get());
    if (mArgs.getOptArg("-oH",path.get()))protspace::HETManager::Instance().toHETList(path.get());
    LOG("END CPDDB::RUN")
            LOG_FLUSH
}catch(ProtExcept &e)
{
    e.addHierarchy("CpdToDB::run");
    throw;
}



void CpdToDB::outputXMLFile(const std::string& pFile)
{
std::cout <<"OUTPUT XML FILE "<< pFile<<std::endl;
    CpdToXML xmlF;
    LOG("NEW TO PUSH:"+std::to_string(mListNew.size()));
    for(size_t i=0;i<mListNew.size();++i)
    {
        Compound& cpd=mListCompounds.get(mListNew[i]);
        xmlF.processEntry(cpd);
    }
    xmlF.exportFile(pFile);
     for(size_t i=0;i<mXML.size();++i)
         mXML.get(i).exportFile(std::to_string(i)+pFile);
}

void CpdToDB::processExternals()
try{
    std::cout <<"PROCESS EXTERNAL"<<std::endl;
    protspace::StringPoolObj valueO("");
    std::string& value=valueO.get();

    if (mArgs.getOptArg("-c",value)) mExtProcess.setCifFile(value);
    if (mArgs.getOptArg("-exD",value))mExtProcess.getExistingList(value);
    std::cout <<"NEW ENTRIES:"<<mExtProcess.numEntries()<<std::endl;
    for(size_t i=0;i< mExtProcess.numEntries();++i)
    {
     //   if (i==20)break;
        try{


            mListNew.push_back(mListCompounds.add(mExtProcess.proceed(i)));
            ELOG(mExtProcess.getName(i)+"|EXTERNAL|SUCCESS");
            LOG(mExtProcess.getName(i)+"|EXTERNAL|SUCCESS");
            LOG_FLUSH
        }catch(ProtExcept &e)
        {
            /**
             * @brief proceed

             * @throw 310101    MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
             * @throw 310102    MMAtom::setAtomicName       Atomic name not found
             * @throw 320501    Residue::getAtom            Atom Not found
             * @throw 320502    Residue::getAtom            Atom Not found
             * @throw 350604    MacroMole::addBond          Both atoms are the same
             * @throw 2330201   MMCifReader::loadNext       Unable to find HET
             * @throw 2340102   ExtCpdHandler::proceed      Given residue name is empty
             * @throw 2340103   ExtCpdHandler::proceed      Unable to assign atom type
             * @throw 2320101   MMCIFRes::loadRules         No C3TK_HOME Path set
             * @throw 2320201   MMCifRes::loadFile          Unable to open rule file
             * @throw 2320401  MMCifRes::GetSMILES             No SMILES FOUND
             */
            ELOG_ERR(mExtProcess.getName(i)+"|EXTERNAL|FAILED|"+e.getDescription());
            LOG_ERR(mExtProcess.getName(i)+" FAILED - "+e.getDescription());
            static const std::string throwExp=" 030303 350102 352302 2340101 2320101 2320201 ";
            if (protspace::isInList(throwExp,e.getId()))
            {
                e.addDescription("Structure involved "+mExtProcess.getName(i));
                throw;
            }
            mOverall=false;
        }
    }
   std::cout <<"END PROCESS EXTERNAL "<<std::endl;
}catch(ProtExcept &e)
{
    assert(e.getId()!="060101");///StrngPool already is use... no way
    e.addHierarchy("CpdToDB::processExternals");
    throw;
}





bool CpdToDB::checkName(const std::string& pName)const
{

    if (mExtProcess.isReady(pName))return true;

    return false;
}



void CpdToDB::pushCpdtoHETManager()
{
    protspace::HETManager& HETM=protspace::HETManager::Instance();
   // std::cout <<"COUNT HETM BEFORE "<<HETM.numEntry()<<std::endl;
    for(size_t i=0;i<mListCompounds.size();++i)
    {
        Compound& cpd=mListCompounds.get(i);
        protspace::HETEntry* entry=new protspace::HETEntry(cpd.getName(),cpd.getReplaced_By());
        protspace::MacroMole& CMole = cpd.getMole();
        protspace::MacroMole& HMole=entry->getMole();
        entry->setResClass(RESTYPE::DBNameToType.at(cpd.getClass()));
        HMole.setName(cpd.getName());
        std::map<protspace::MMResidue*, protspace::MMResidue*> mapR;
        std::map<protspace::MMAtom*, protspace::MMAtom*> mapA;
        for(size_t iR=0;iR< CMole.numResidue();++iR)
        {
            protspace::MMResidue& CRes=CMole.getResidue(iR);
            protspace::MMResidue& RRes=HMole.addResidue(CRes.getName(),
                             CRes.getChainName(),
                             CRes.getMID());
            mapR.insert(std::make_pair(&CRes,&RRes));
        }
        for(size_t iA=0;iA<CMole.numAtoms();++iA)
        {
            protspace::MMAtom& CAt=CMole.getAtom(iA);
            if (CAt.getName()=="DuCy"||CAt.getName()=="DuAr")continue;
            protspace::MMAtom& HAt=HMole.addAtom(*mapR.at(&CAt.getResidue()),
                          CAt.pos(),
                          CAt.getName(),
                          CAt.getMOL2());
            HAt.setFormalCharge(CAt.getFormalCharge());
            mapA.insert(std::make_pair(&CAt,&HAt));
        }
        for(size_t iB=0;iB<CMole.numBonds();++iB)
        {
            protspace::MMBond& CBd=CMole.getBond(iB);
            HMole.addBond(*mapA.at(&CBd.getAtom1()),*mapA.at(&CBd.getAtom2()),CBd.getType());
        }
        HETM.addEntry(entry);

    }
   // std::cout <<"COUNT HETM AFTER "<<HETM.numEntry()<<std::endl;
}




void CpdToDB::outputBinaryFile(const std::string& pFile, const bool& withoutPreviousVersion)
{
std::cout <<"OUTPUT BINARY FILE:"<<pFile<<std::endl;
    protspace::HETManager& HETM=protspace::HETManager::Instance();
try{
   if (!withoutPreviousVersion) HETM.loadAll();
}catch (ProtExcept &e)
{
	std::cerr<<e.toString();
}
    pushCpdtoHETManager();
    HETM.exportFile(pFile+".objx",pFile+".posit");
}







void CpdToDB::outputSDF(const std::string& pFile)
{
    protspace::WriterSDF sdW(pFile);
    for(size_t i=0;i<mListCompounds.size();++i)
    {
        Compound& cpd=mListCompounds.get(i);
        sdW.save(cpd.getMole());
    }
}






