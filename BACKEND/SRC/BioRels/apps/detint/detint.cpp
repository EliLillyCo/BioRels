//
// Created by c188973 on 10/27/16.
//

#include <headers/statics/intertypes.h>
#include "headers/iwcore/macrotoiw.h"
//#include "headers/molecule/protproj_utils.h"
#include "headers/inters/intercomplex.h"
#include "headers/parser/writerMOL2.h"
#include "headers/proc/atomperception.h"
#include "headers/proc/bondperception.h"
#include "headers/inters/interprotlig.h"
#include "detint.h"
#include "headers/molecule/pdbentry_utils.h"
#include "headers/parser/readers.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/hetmanager.h"
#include "headers/proc/matchtemplate.h"

void DetInt::prepPDB()
{
    try{

        convertMSE_MET(mProtein);
        convertCSE_CYS(mProtein);
        protspace::HETManager& hetmanager=protspace::HETManager::Instance();
        hetmanager.assignResidueType(mProtein,true,false);

        protspace::BondPerception perc;
        perc.processMolecule(mProtein);

        protspace::MatchTemplate matcht;
        try{
        matcht.processMolecule(mProtein);
        }catch(ProtExcept &e)
        {
            LOG_ERR(e.toString());
        }
        bool overallDu=false;
        for(size_t iR=0;iR< mProtein.numResidue();++iR)
        {
            protspace::MMResidue& pRes=mProtein.getResidue(iR);
            bool hasDu=false;
            for(size_t iA=0;iA< pRes.numAtoms();++iA)
            {
                protspace::MMAtom& pAtom=pRes.getAtom(iA);
                if (pAtom.getMOL2()=="Du")hasDu=true;
                break;
            }
            if (!hasDu)continue;
            overallDu=true;
            for(size_t iA=0;iA< pRes.numAtoms();++iA)

            {
                protspace::MMAtom& pAtom=pRes.getAtom(iA);
                pAtom.setMOL2Type("Du",false);
            }

        }
        for(size_t iBd=0;iBd < mProtein.numBonds();++iBd)
        {
            protspace::MMBond& bond=mProtein.getBond(iBd);
            if (bond.getType()!=BOND::UNDEFINED)continue;
            LOG_ERR("Unrecognized bond type - Set to single "+bond.toString());
            ELOG_ERR("Unrecognized bond type - Set to single "+bond.toString());
            bond.setBondType(BOND::SINGLE);
        }
        if (overallDu)
        {
            protspace::AtomPerception perc;
            perc.perceive(mProtein,false);

        }

    }catch(ProtExcept &e)
    {
        e.addHierarchy("prepareMolecule");
        throw;
    }
}
void DetInt::prepMole(const std::string &pFile) {
    try{
        const std::string ext(protspace::getExtension(pFile));
        protspace::readFile(mProtein,pFile);
        if (ext!="mol2")prepPDB();
        else protspace::prepareMolecule(mProtein,PREPRULE::ASSIGN_RESTYPE,false);
        MacroToIW miw2(mProtein);miw2.generateRings();
        protspace::AtomPerception aperc;
        aperc.perceive(mProtein,false);
        MacroToIW miw(mProtein);
        miw.generateRings();
        mCurrName=pFile.substr(0,pFile.find_last_of("."));;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("DetInt::prepMole");
        throw;
    }
}


///TODO Add exceptions on whole file

void DetInt::calcComplexInteractions() {
    protspace::InterComplex  inters(mProtein);
    inters.setWExportArom(mWExportArom);
    inters.calcInteractions(mCurrResults);
    mCurrResults.unique();
}



DetInt::DetInt():mCurrLigand(nullptr),mInterPL(nullptr),mCurrName(""),callID(0),mWExportArom(false) {

}




DetInt::~DetInt()
{
    if (mCurrLigand !=nullptr && mLigandOwned)delete mCurrLigand;
    if (mInterPL != nullptr) delete mInterPL;
}




void DetInt::exportToMole(const std::string &pFileName,const bool& append) {
    mCurrResults.toMolecule();
    protspace::WriteMOL2 mw(pFileName+".mol2");
    mw.appendFile(append);
    mw.save(mCurrResults.getFullMole());
}



void DetInt::exportToCSV(const std::string& pFileName,
                         const std::string& pName,
                         const bool& append,
                         const bool& wid)
{
    ++callID;

    if (wid)mCurrResults.toText(pName+"_"+std::to_string(callID));
    else mCurrResults.toText(pName);
    mCurrResults.saveText(pFileName+".txt",append, (protspace::filesize(pFileName+".txt")<0));
}


void DetInt::exportToFGP(const std::string& pFileName)
{
    const size_t len(mProtein.numResidue()*NB_INTERTYPE);
     std::vector<short> FGP(len,0);
     for(const Entries& entry:mListFGPS)
     {
         for(size_t i=0;i<len;++i)
         {
             FGP[i]+= (entry.mListInts.at(i)>0)?1:0;
         }
     }
     std::ofstream ofs(pFileName);
     if (!ofs.is_open())
         throw_line("","DetInt::exportToFGP","Unable to open "+pFileName);
     ofs<<"NAME";
     for(size_t i=0;i< mProtein.numResidue();++i)
     {
         const protspace::MMResidue& res=mProtein.getResidue(i);
         for(size_t j=0;j<NB_INTERTYPE;++j)
         {
             if (FGP[i*NB_INTERTYPE+j]==0)continue;
             ofs<<"\t"<<res.getIdentifier()<<":"<<INTER::typeToName.at(j);
         }
     }
     ofs<<"\n";
     size_t pos=0;
       for(const Entries& entry:mListFGPS)
     {
         ofs<<entry.mName;
         pos=0;
         for(const short& s:(entry.mListInts))
         {
             if (!FGP[pos]){++pos;continue;}
             pos++;
             ofs<<"\t"<<s;
         }
             ofs<<"\n";
     }
     ofs.close();
}





void DetInt::assignLigand(const std::string& pFile)
{
    mLigandOwned=true;
    try{
        protspace::BondPerception bperc;
        protspace::AtomPerception aperc;
        if (mCurrLigand != nullptr && mLigandOwned)delete mCurrLigand;
        mCurrLigand = new protspace::MacroMole;
        if (mCurrLigand->numResidue()==0)
            mCurrLigand->getTempResidue().setResidueType(RESTYPE::LIGAND);
        else mCurrLigand->getResidue(0).setResidueType(RESTYPE::LIGAND);
        mCurrName=pFile.substr(0,pFile.find_last_of("."));

        protspace::readFile(*mCurrLigand,pFile);
        if (protspace::getExtension(pFile)!="mol2")
        {
        bperc.processMolecule(*mCurrLigand);
        MacroToIW miw2(*mCurrLigand);miw2.generateRings();
            aperc.perceive(*mCurrLigand,true);
            ////This is not a mistake. We need to do it twice.
            MacroToIW miw(*mCurrLigand);miw.generateRings();
        }else{
            MacroToIW miw(*mCurrLigand);miw.generateRings();
        }

    }catch(ProtExcept &e)
    {e.addHierarchy("DetInt::assignLigand");
        throw;
    }
}





void DetInt::calcProtLigInteractions() {

    if (mInterPL==nullptr)mInterPL=new protspace::InterProtLig(mProtein);
    mCurrResults.clear();
    mInterPL->calcInteractions(mCurrResults,*mCurrLigand);
}





void DetInt::forceName() {
    if (mCurrLigand == nullptr)
        throw_line("XXXXX","DetInt::forceName","No ligand");
    mCurrLigand->getTempResidue().setName(mCurrLigand->getName());
}

void DetInt::addFGP(const std::string &pName)
{
    std::vector<short> FGP(mProtein.numResidue()*NB_INTERTYPE,0);
    for(size_t i=0;i< mCurrResults.count();++i)
    {
        const protspace::InterObj& data=mCurrResults.getInter(i);
        if (&data.getResidue1().getParent()==&mProtein)
        {
            FGP.at(data.getResidue1().getMID()*NB_INTERTYPE+data.getType())++;
        }
        else if (&data.getResidue2().getParent()==&mProtein)
        {
            FGP.at(data.getResidue2().getMID()*NB_INTERTYPE+data.getType())++;
        }else assert(1==0);
    }
    mListFGPS.push_back(Entries(FGP,pName));
}




void DetInt::assignLigand(protspace::MacroMole &pLig) {
    if (mCurrLigand != nullptr && mLigandOwned)delete mCurrLigand;
    mLigandOwned=false;
    mCurrLigand=&pLig;
    pLig.getTempResidue().setResidueType(RESTYPE::LIGAND);
}





