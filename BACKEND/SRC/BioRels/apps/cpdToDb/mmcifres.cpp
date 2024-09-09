#include "mmcifres.h"
#include <cstdlib>
#include <fstream>
#include <iostream>
#include "headers/iwcore/macrotoiw.h"
#include "headers/proc/atomperception.h"
#include "headers/statics/strutils.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/statics/protpool.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/math/coords.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/mmatom.h"
#include "gc3tk.h"
#include "headers/parser/readers.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/logger.h"

std::map<int,std::vector<std::string>> MMCifRes::sListRules;

MMCifRes::MMCifRes( protspace::MacroMole &mole):
    mHET(""),mMole(mole)
{

    if (sListRules.empty())loadRules();

}

void MMCifRes::loadRules()
{
    const protspace::StringPoolObj FULL_PATH(protspace::getEnvVariable("TG_DIR")+"/BACKEND/STATIC_DATA/XRAY/");

    if (FULL_PATH.get() == "") \
        throw_line("2320101",
                   "MMCIFRes::loadRules",
                   "No C3TK_HOME Path set");

    static const std::map<std::string,uint16_t>
            names({
                      {"HET_COFACTOR.tsv",RESTYPE::COFACTOR},
                      {"HET_SOLVANT.tsv",RESTYPE::WATER},
                      {"HET_PROSTHETIC.tsv",RESTYPE::PROSTHETIC},
                      {"HET_ORGANOMET.tsv",RESTYPE::ORGANOMET},
                      {"HET_SUGAR.tsv",RESTYPE::SUGAR},
                      {"HET_UNWANTED.tsv",RESTYPE::UNWANTED},
                      {"HET_MODAA.tsv",RESTYPE::MODIFIED_AA}});
    std::vector<std::string> list;
    for( auto it = names.begin();it!=names.end();++it)
    {
        try{

            loadFile(FULL_PATH.get(),(*it).first,  list);
            sListRules.insert(std::make_pair((*it).second,list));
            std::cout<< (*it).first<<"\t"<< (*it).second<<"\t"<<list.size()<<std::endl;
        }catch(ProtExcept &e)
        {
            e.addHierarchy("MMCIFRes::loadRules");
            e.addHierarchy("File involved : "+(*it).first);
            throw;
        }
    }
}


void MMCifRes::loadFile(const std::string& dir,
                        const std::string& fname,
                        std::vector<std::string>& list) throw(ProtExcept)
{


    std::ifstream ifs(dir+"/"+fname,std::ios::in);
    if (!ifs.is_open())
    {
        LOG_ERR("Unable to open file "+dir+"/"+fname);
        ELOG_ERR("Unable to open file "+dir+"/"+fname);
        throw_line("2320201",
                   "MMCifRes::loadFile",
                   "Unable to open rule file: "+dir+"/"+fname);
    }
    protspace::StringPoolObj line;list.clear();
    while (!ifs.eof())
    {
        safeGetline(ifs,line.get());
        list.push_back(line.get());
    }
    ifs.close();
}








void MMCifRes::addChemCompDbl(const std::string& fHead,
                              const std::string& fValue)
{
    const double val=atof(fValue.c_str());
    mChemCompDbl.insert(std::make_pair(fHead,val));
}







void MMCifRes::addAtom(const double& x_v,
                       const double& y_v,
                       const double& z_v,
                       const bool& isAromatic,
                       const double& fcharge,
                       const std::string& element,
                       const std::string &name)
try{
    protspace::MMAtom& atom =mMole.addAtom(mMole.getResidue(0));
    atom.pos().setxyz(x_v,y_v,z_v);
    atom.setName(name);
    atom.setAtomicType(element);
    atom.setFormalCharge(fcharge);
    if (isAromatic)mAromAtom.push_back(&atom);
}catch(ProtExcept&e)
{

    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="350101");///Molecule cannot be an alias
    assert(e.getId()!="250201");///Residue must be in molecule

e.addHierarchy("MMCifRes::addAtom");
  throw;
}





void MMCifRes::setHET(const std::string& HET)
try{
    mHET=HET;
    mMole.addResidue(HET,"X",1)
         .setResidueType(RESTYPE::UNDEFINED);
}catch(ProtExcept &e)
{
            assert(e.getId()!="351501");///Molecule cannot be an alias
    assert(e.getId()!="351503");///Chain name must be correct
    e.addHierarchy("MMCifRes::SetHET");
    throw;
}




void MMCifRes::addBond(const std::string& atom1,
                       const std::string& atom2,
                       const std::string& order,
                       const bool& arom,
                       const bool& stereo,
                       const int&ordinal)
try{
    protspace::MMResidue& res = mMole.getResidue(0);
    protspace::MMAtom& mmatom1= res.getAtom(atom1);
    protspace:: MMAtom& mmatom2= res.getAtom(atom2);
    uint16_t btype=BOND::SINGLE;
    if (order == "SING") btype=BOND::SINGLE;
    else if (order == "DOUB") btype=BOND::DOUBLE;
    else if (order == "DELO") btype=BOND::DELOCALIZED;
    else if (order == "TRIP") btype=BOND::TRIPLE;
    else if (order == "PI") btype=BOND::AROMATIC_BD;
    else if (order == "POLY") btype=BOND::AROMATIC_BD;
    else btype =BOND::SINGLE;
    mMole.addBond(mmatom1,mmatom2,btype,mMole.numAtoms());
}catch(ProtExcept &e)
{

    assert(e.getId()!="350601");///Molecule cannot be an alias
    assert(e.getId()!="350602" && e.getId()!="350603");///atoms must be in molecule
    e.addHierarchy("MMCifRes::addBond");
    throw;
}




void MMCifRes::addChemComp(const std::string& fHead,
                           const std::string& fValue)
{
    if (fValue=="?")mChemComp.insert(std::make_pair(fHead,""));
    else            mChemComp.insert(std::make_pair(fHead,fValue));

}


void MMCifRes::addSMINCHI(const  std::string& fType,
                          const std::string& fProgram,
                          const std::string& fProgram_version,
                          const std::string& fDescriptor)
{
    sminchi smi;
    smi.mDescriptor=fDescriptor;
    smi.mProgram=fProgram;
    smi.mProgram_version=fProgram_version;
    smi.mType=fType;
    mListSMI.push_back(smi);
}

std::string MMCifRes::perceiveClass()
{
    std::vector<std::string>::const_iterator itPos;
    for(auto it=sListRules.begin();
        it!=sListRules.end();++it)
    {
        itPos    = std::find(it->second.begin() ,it->second.end(),mHET);
        if (itPos ==it->second.end())continue;

        mMole.getResidue(0).setResidueType(it->first);
        return RESTYPE::typeToDBName.at(mMole.getResidue(0).getResType());
    }

    if (mChemComp.find("type") != mChemComp.end())
    {
        protspace::perceiveClass(mMole.getResidue(0),mChemComp.at("type"));
        return RESTYPE::typeToDBName.at(mMole.getResidue(0).getResType());
    }
    else
    {
        ELOG_ERR(mHET+" - No class defined");

        return "LIGAND";
    }
}




bool MMCifRes::perceiveAtom()
try
{
    protspace::AtomPerception perc;
    mMole.setName(mHET);
    perc.perceive(mMole,true);
    /// Residues that have unknown atom by default
    /// so we cannot expexct the tool to assign an elem to an unknown atom
    static const std::string listExceptions=" UNX ASX DUM GLX VV7 ";
    for(size_t iAtom=0;iAtom<mMole.numAtoms();++iAtom)
    {
        const protspace::MMAtom& atm=mMole.getAtom(iAtom);
        const std::string& name=atm.getName();
        const std::string& mol2=atm.getMOL2();
        if (name=="DuAr" || name=="DuCy" || mol2!="Du")continue;
        if (protspace::isInList(listExceptions,mHET))continue;
        /// There are situation where Dummy is allowed
        /// i.e. when you have a metal that does not have a MOL2 type
        /// but as an element defined
        if (atm.getAtomicName()!="Dummy")  continue;
        ELOG_ERR(mHET+" "+atm.getIdentifier()+" - Unable to assign type");
        return false;
    }
    return true;

}catch(ProtExcept &e)
{
    e.addHierarchy("MMCifRes::perceiveAtom");
    throw;
}


const std::string&  MMCifRes::getName() const
{
    const auto it = mChemComp.find("name");
    if (it!= mChemComp.end())return (*it).second;
    throw_line("2320301",
               "MMCifRes::GetName",
               "No name found");
}

std::string MMCifRes::getReplaceBy() const
{
    const auto it = mChemComp.find("pdbx_replaced_by");
    if (it!= mChemComp.end())return (*it).second;
    return "";
}

const uint16_t& MMCifRes::getResidueType() const
{
    return mMole.getResidue(0).getResType();
}

protspace::MacroMole& MMCifRes::getMolecule(){return mMole;}



std::string MMCifRes::getSMILES()const
{

    //    std::cout <<"COUNT SMILES"<<mListSMI.size()<<std::endl;
    for(size_t i=0;i<5;++i)
    {
        for(const auto& sci:mListSMI)
        {
//            std::cout << mHET<<"\t"<< sci.mProgram.get()<<" " <<sci.mType.get()<<std::endl;
            switch(i)
            {
            case 0: if(sci.mProgram.get()=="CACTVS" && sci.mType.get()=="SMILES_CANONICAL")
                {
                    return sci.mDescriptor.get().c_str();
                }break;
            case 1: if(sci.mProgram.get()=="OpenEyeOEToolkits" && sci.mType.get()=="SMILES_CANONICAL")
                {
                    return sci.mDescriptor.get().c_str();
                }break;
            case 2: if(sci.mProgram.get()=="CACTVS" && sci.mType.get()=="SMILES")
                {
                    return sci.mDescriptor.get().c_str();

                }break;
            case 3: if(sci.mProgram.get()=="OpenEyeOEToolkits" && sci.mType.get()=="SMILES")
                {
                    return sci.mDescriptor.get().c_str();

                }break;
            case 4: if(sci.mProgram.get()=="ACDLabs" && sci.mType.get()=="SMILES")
                {
                    return sci.mDescriptor.get().c_str();

                }break;
            }
        }
    }
    throw_line("2320401","MMCifRes::GetSMILES","No SMILES FOUND");
    return "";
}



