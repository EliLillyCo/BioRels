#include "extcpdhandler.h"
#include "mmcifreader.h"
#include "compound.h"
#include "headers/iwcore/macrotoiw.h"
#include "mmcifres.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/statics/strutils.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/logger.h"
ExtCpdHandler::ExtCpdHandler():mPublicCifFile("")
{

}


void ExtCpdHandler::setCifFile(const std::string& pFile)
{
    mPublicCifFile=pFile;
    mReader.setCifFile(mPublicCifFile);
    mReader.getListHET(mListInputLine);
}
Compound& ExtCpdHandler::proceed(const size_t& pId)
try{
    return proceed(mListInputLine.at(pId));
}catch(ProtExcept &e)
{
    e.addHierarchy("ExtCpdHandler::proceed");
    throw;
}
Compound& ExtCpdHandler::proceed(const std::string& pHET)
{

    ///TODO :Remove
if (pHET=="DUM" || pHET=="H")
    throw_line("XXXXXX",
               "ExtCpdHandler::proceed",
               "TO TEST - BE REMOVED");
    if (!mReader.isReady())
        throw_line("2340101",
                   "ExtCpdHandler::proceed",
                   "No public cif file given");
    if (pHET.empty())
        throw_line("2340102",
                   "ExtCpdHandler::proceed",
                   "Given residue name is empty");



    Compound* cpd = new Compound();

    try{
        cpd->setName(pHET);
        protspace::MacroMole& mole=cpd->getMole();
        MMCifRes res(mole);

        mReader.loadNext(res,pHET);
        MacroToIW miw(mole);
        miw.generateRings();
        if (!res.perceiveAtom())
            throw_line("2340103","ExtCpdHandler::proceed",
                       "Unable to assign atom type");

        res.perceiveClass();
        for(size_t iR=0;iR<mole.numRings();++iR)
            protspace::turnToAromaticRing(mole.getRing(iR));

        cpd->setReplaced_By(res.getReplaceBy());
        cpd->setClass(res.perceiveClass());
        cpd->addProp("Molecular_weight",
                     std::to_string(protspace::getWeight(res.getMolecule().getResidue(0))));
        cpd->addProp("Formula",
                     protspace::getFormula(res.getMolecule()));
        cpd->setSMILES(res.getSMILES());
        cpd->setIsLilly(false);
        cpd->setTautomer_Id(1);
        cpd->setSubClass("");
        cpd->setMIsCorrect(true);
        mListReady.push_back(pHET);

        return *cpd;
    }catch(ProtExcept &e)
    {
    assert(e.getId()!="352301");///No atom in ring -> issue

        delete cpd;
        if (e.getId()!="000100")e.addHierarchy("ProcessExternal");
        throw;
    }
}



void ExtCpdHandler::getExistingList(const std::string& pFile)
{
    if (!mReader.isReady())throw_line("2340301","ExtCpdHandler::getExistingList",
                                      "Reader must be ready");

   // mReader.getListHET(mListInputLine);

    std::ifstream ifs(pFile);
    std::string line;
    while(!ifs.eof())
    {
        std::getline(ifs,line);

        const auto it=std::find(mListInputLine.begin(),mListInputLine.end(),line);
        if (it==mListInputLine.end())continue;
        mListInputLine.erase(it);
        mListReady.push_back(line);

    }

        LOG("Number of external entries to process after filter from DB : "+std::to_string(mListInputLine.size()));

        ifs.close();

}



void ExtCpdHandler::loadStream(const std::string& stream)
{

    protspace::tokenStr(stream,mListInputLine,"\n");
}

void ExtCpdHandler::loadFile(const std::string& pFile)
{
    std::ifstream ifs(pFile);
    if (!ifs.is_open())throw_line("2340201","ExtCpdHandler::loadFile",
                                  "Unable to open file "+pFile);
    protspace::StringPoolObj line("");


    while(!ifs.eof())
    {
        safeGetline(ifs,line.get());
        if (line.get().empty())continue;
        mListInputLine.push_back(line.get());
    }
    ifs.close();
}


