#include "headers/proc/atomperception.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release

void protspace::AtomPerception::resetMolecule(MacroMole& mole)const
try{
    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
    {
        MMAtom& atm = mole.getAtom(iAtm);
        atm.setMOL2Type("Du",false);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401" && e.getId()!="310801" && e.getId()!="310802");
    e.addHierarchy("AtomPerception::ResetMolecule");
    throw;
}

void protspace::AtomPerception::prepProcess(MacroMole& mole)
{
    mListProcessed.clear();
    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
        mListProcessed.push_back(mole.getAtom(iAtm).getMOL2()!="Du");
}

bool protspace::AtomPerception::perceive(MacroMole& mole,const bool& force)
try{

    if(force)resetMolecule(mole);

    mIsMolecule=true;
    prepProcess(mole);

//    std::cout <<"#####START\n";
//    std::cout<<mole.getAtom(7).toString();

        for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
        {
            if (mListProcessed.at(iAtm))continue;
//            std::cout <<"READ:"<<mole.getAtom(iAtm).getIdentifier()<<"\n";
            if (!perceive(mole.getAtom(iAtm)))continue;
//            std::cout <<mole.getAtom(7).toString()<<std::endl;
        }

        for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
        {
            if (mListProcessed.at(iAtm))continue;
            if (!perceiveSingleAtom(mole.getAtom(iAtm)))
            {
                LOG_ERR("Unable to perceive atom : "+mole.getAtom(iAtm).toString());
                return false;
            }

        }
    mIsMolecule=false;
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="310801");///No MOL2 given => Please check property rules to ensure all MOL2 types are defined
    assert(e.getId()!="310802");///No MOL2 given => Please check property rules to ensure all MOL2 types are correct
    assert(e.getId()!="030401");/// Atom must exists
    e.addHierarchy("AtomPerception::perceive(MOLE)");
    throw;
}catch(std::out_of_range &e)
{
    LOG_ERR("Atomperception::perceive::Out of range");
    assert(1!=0);
    return false;
}

bool protspace::AtomPerception::perceiveSingleAtom(MMAtom& atom)
{
    try{
        mAtStat.updateAtom(atom);

        switch (atom.getAtomicNum()) {
        case 6:
            if(perceiveSingleCarbon(atom))return true;
            break;

        case 7:
            if (perceiveSingleNitrogen(atom))return true;     break;
        case 8:
            if (perceiveSingleOxygen(atom))return true;break;
        case 15:
            if (processPhosphate(atom))return true;break;
        default:
            try{
            atom.setMOL2Type(atom.getElement());
        }catch(ProtExcept &e)
            {
                throw_line("600101","AtomPerception::perceive","Unable to process atom "+atom.getParent().getName()+"\n"+atom.toString());
            }
            return true;
            break;
        }
        throw_line("600101","AtomPerception::perceive","Unable to process atom "+atom.getParent().getName()+"\n"+atom.toString());
        return false;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::perceiveSingleAtom");
        e.addDescription(atom.getParent().toString());
        throw;
    }
}

bool
protspace::AtomPerception::perceive(MMAtom& atom)
{
    try{
        if(atom.getName()=="DuAr"||atom.getName()=="DuCy"){setMOL2(atom,"Du");return true;}
        if (assignSingleMOL2(atom))return true;
        if (getSpecificMOL2(atom))return true;

        mAtStat.updateAtom(atom);
        switch (atom.getAtomicNum())
        {
        case 6: return processCarbon(atom);break;
        case 7: return processNitrogen(atom);break;
        case 15: return processPhosphate(atom);break;
        case 16: return processSulfur(atom);break;
        default:break;
        }

        return false;

    }catch(ProtExcept &e)
    {
        e.addHierarchy("AtomPerc::perceive(ATOM)");
        e.addDescription("Atom involved:"+atom.toString());
        throw;
    }
}


bool protspace::AtomPerception::assignSingleMOL2(MMAtom& atom)
{
    switch(atom.getAtomicNum())
    {

    case 6:///C - 209M
    case 8:///O - 81M
    case 7:///N - 56M
    case 15:
    case 16:///S - 1.6M
        return false;
    case 1 : setMOL2(atom,"H");  return true; break;
    //case 15: setMOL2(atom,"P.3");return true; break; /// 219K
    case 17: setMOL2(atom,"Cl"); return true; break; /// 22K
    case 30: setMOL2(atom,"Zn"); return true; break; /// 20K
    case 12: setMOL2(atom,"Mg"); return true; break; /// 17K
    case 9:  setMOL2(atom,"F");  return true; break; /// 17K
    case 20: setMOL2(atom,"Ca"); return true; break; /// 15K
    case 26: setMOL2(atom,"Fe"); return true; break; /// 12K
    case 11: setMOL2(atom,"Na"); return true; break; /// 8K
    case 25: setMOL2(atom,"Mn"); return true; break; /// 5K
    case 53: setMOL2(atom,"I");  return true; break; /// 5K
    case 19: setMOL2(atom,"K");  return true; break; /// 3K
    case 35: setMOL2(atom,"Br"); return true; break; /// 3K
    case 29: setMOL2(atom,"Cu"); return true; break; /// 1.5K
    case 27: setMOL2(atom,"Co.oh"); return true; break; /// 1.2K
    case 5: setMOL2(atom,"B");  return true; break; /// Boron 443
    case 13: setMOL2(atom,"Al"); return true; break;/// Aluminium 272
    case 34: setMOL2(atom,"Se"); return true; break;///Selenium 169
    case 3:  setMOL2(atom,"Li"); return true; break;/// Lithium 54
    case 50: setMOL2(atom,"Sn",false);  return true; break;///Tin 6
    case 4: ///Beryllium 220
    case 14: ///Silicon 24
    case 18: ///Argon 8
    case 21: ///Scandium 1
    case 22: ///Titanium 2
    case 23: ///Vanadium 186
    case 24: ///Chromium 18
    case 28: ///Ni 1.5K
    case 31: ///Gallium 5
    case 33: ///Arsenic 613
    case 36: ///Krypton 44
    case 37: ///Rubidium 80
    case 38: /// Strontium 220
    case 39: ///Ytrium 149
    case 40: ///Zyrconium
    case 42: ///Mobyldenium 0
    case 44: /// Ruthenium 120
    case 45:///Rhodium 0
    case 46: ///Palladium 82
    case 49: ///Indium 3
    case 47: ///Silver 28
    case 48: ///Cd 0
    case 51: ///Antimony 2
    case 52:///Te
    case 54: ///Xenon 307
    case 55:///Cesium 0
    case 56: /// Barium 131
    case 57:///La
    case 58: ///Cesium 176
    case 59:///Pr 0
    case 62:///Sm 0
    case 63:///Eu 0
    case 64: ///Gadolinum 163
    case 65:///Tb 0
    case 66: ///Dy 0
    case 67:///Ho 0
    case 68:///Er 0
    case 70: ///Ytterbium 162
    case 71:///Lu
    case 72:///Hf
    case 73:///Ta 0
    case 74: ///W 0
    case 75:///Re 0
    case 76:/// Os 0
    case 77: ///Iridium 61
    case 78: ///Pt 0
    case 79: ///Gold 0
    case 80: ///Mercury 1.3L
    case 81:///Tl 0
    case 82: ///Lead 104
    case 83: ///Bi 0
    case 90: /// Th
    case 92:///U
    case 94: ///Pu 0
    case 95: ///Am 0
    case 96:///Cm 0
    case 0:///Du
        setMOL2(atom,"Du",false);
        LOG_WARN("No MOL2 Type available for this element. Set to Du "+atom.getIdentifier());
        return true;break;

    default:
        LOG_ERR("Atom Not recognized "+atom.getIdentifier());
        throw_line("600401","AtomPerception::assignSingleMOL2","Atom element not recognized "+atom.getAtomicName()+ " for molecule "+atom.getParent().getName());
    }
    return false;
}
bool protspace::AtomPerception:: getSpecificMOL2(MMAtom& atom)
    try{
        const std::string& resName=atom.getResidue().getName();
        const std::string& atmName=atom.getName();
        if (resName=="HIS" && atmName=="ND1")
        {
            protspace::removeAllHydrogen(atom);
            setMOL2(atom,"N.pl3");
            atom.setFormalCharge(0);
            return true;
        }
        else if (resName=="HIS" && atmName=="NE2")
        {
            setMOL2(atom,"N.2");
            atom.setFormalCharge(0);
            return true;
        }
        else if (resName=="SER" && atmName=="O")
        {
            setMOL2(atom,"O.2");

            return true;
        } else if (resName=="SER" && atmName=="OXT")
        {
            setMOL2(atom,"O.3");

            return true;
        }
        else if (resName=="CMO" && atmName=="C")
        {
            setMOL2(atom,"C.1");atom.setFormalCharge(+1);
            if (atom.numBonds()>0)
            {
            setMOL2(atom.getAtom(0),"O.2");
            atom.getBond(0).setBondType(BOND::DOUBLE);
            }
            return true;
        }
        return false;
    }catch(ProtExcept &e)
    {
    assert(e.getId()!="310701");//Residue must exist
    e.addHierarchy("AtomPerception::getSpecificMOL2");
        throw;
    }


protspace::AtomPerception::AtomPerception():
    mCountsC(mCarbonRules.size(),0),
    mCountsO(mOxygenRules.size(),0),
    mCountsN(mNitrogenRules.size(),0),
    mAllCounts(0),
  mIsMolecule(false),
  mGroupPerception(true)
{


}

void protspace::AtomPerception::setMOL2(MMAtom& pAtom, const std::string& pMOL2,const bool& wAtomicNum)
try{
    //std::cout << pAtom.getIdentifier()<<" " << pMOL2<<std::endl;
    pAtom.setMOL2Type(pMOL2,wAtomicNum);
    if (!mIsMolecule)return;
    mListProcessed.at(pAtom.getMID())=true;
}catch(ProtExcept &e)
{
    e.addHierarchy("AtomPerception::setMOL2");
    e.addDescription("MOL2 : "+pMOL2);
    throw;
}catch(std::out_of_range &e)
{
    throw_line("600201",
               "AtomPerception::setMOL2",
               "Atom out of range of processing list");

}

bool protspace::AtomPerception::followRule(const AtomRule& pRule,const MMAtom& pAtom)const
try{
    if (pAtom.numBonds()==0)
        throw_line("600301",
                   "AtomPerception::followRule",
                   "Atom does not have any bonds - Please contact administrator");
    assert(!pRule.mLinkRules.empty());
    if (pAtom.getAtomicNum()!=pRule.mStartAtom)return false;
    if (pRule.mLinkRules.size()!= pAtom.numBonds())return false;
    /// Both rules and all atom linked must be fullfilled.
    std::vector<bool> validRule(pRule.mLinkRules.size(),false);
    std::vector<bool> validAtom(pAtom.numBonds(),false);

    for(size_t iAtm=0;iAtm<pAtom.numBonds();++iAtm)
    {
        if (validAtom[iAtm])continue;

        for(size_t iRule=0;iRule<pRule.mLinkRules.size();++iRule)
        {
             if (validRule[iRule])continue;
             const LinkRule& rule=pRule.mLinkRules.at(iRule);
             if (rule.mLinkAtom!=pAtom.getAtom(iAtm).getAtomicNum())continue;
             if (rule.mBType!=pAtom.getBond(iAtm).getType())continue;
             validAtom[iAtm]=true;
             validRule[iRule]=true;
             break;
        }
    }
    for(const bool& value:validRule)if (!value)return false;
    for(const bool& value:validAtom)if (!value)return false;
    return true;
}
catch(ProtExcept &e)
{
    assert(e.getId()!="310501" && e.getId()!="310201" && e.getId()!="071001");
    e.addHierarchy("AtomPerception::FollowRule");
    throw;
}catch(std::out_of_range &e)
{
    assert(1==0);
    throw;
}



void protspace::AtomPerception::print()
{
    for(const size_t& pos:mCountsO)
        std::cout <<pos<<" " ;
    std::cout <<std::endl;
    std::cout <<std::endl;
    for(const size_t& pos:mCountsC)
        std::cout <<pos<<" " ;
    std::cout <<std::endl;
    std::cout <<"OVERAL:"<<mAllCounts<<std::endl;
    ///4849754
    ///4849693
    ///4732369
}

//bool AtomPerc::perceiveAtom(MMAtom& atom)
//{
//    try{
//        return !perceive(atom) ? perceiveSingleAtom(atom) : true;
//    }catch(ProtExcept &e)
//    {
//        e.addHierarchy("AtomPerc::perceiveAtom(ATOM)");
//        e.addDescription("Atom involved:"+atom.toString());
//        throw;
//    }
//}




















//bool AtomPerc::perceiveSingleSulfur(MMAtom& atom)
//{
//    try{
//        if (nBd==2 && nSing==2) atom.setMOL2Type("S.3");
//        else if (nBd==1 && nDouble==1)atom.setMOL2Type("S.2");//Case 0UF:S2
//        else if (nBd==2 && nDouble==1 && nSing==1)
//        {
//            atom.setMOL2Type("S.2");atom.setFormalCharge(1);
//        }
//        else if (nBd==3 && nDouble==1 && nSing==2 && nHy==1)
//        {
//            atom.setMOL2Type("S.2");
//        }
//        else if (nBd==3 && nDouble==1 && nSing==2 && nHy==0 &&nOx==2)
//        {
//            atom.setMOL2Type("S.2"); // CAse 0CS:SG
//        }
//        else if (nDouble==0 && nTrip==0 && nSing >1)
//        {
//            atom.setMOL2Type("S.3");//Case 0H2:S2/S9/S5
//        }
//        else if (nDouble ==2 && nBd==2 && nC==2)
//        {
//            atom.setMOL2Type("S.2");//Case V78:SAI
//        }
//        else if (nDouble==1 && nSing==2 && nBd==3)
//        {
//            atom.setMOL2Type("S.2");// CAse 3F1:S12
//        }
//        else if (nDouble==2 && nSing==2 && nBd==4)
//        {
//            atom.setMOL2Type("S.2");//Case CPI:S
//        }
//        else if (nSing ==1 &&nBd==1)
//        {
//            atom.setMOL2Type("S.3");//Case ISG:S6
//        }
//        else if (nOx==2 && nDouble==2 && nSing==1)
//        {
//            atom.setMOL2Type("S.o2");// Case 3GE:SAP
//        }
//        else if (nC==2 && nN==1 && nOx==1 && nHy==1&&nSing==4 && nDouble==1)
//        {
//            //Case BSC
//            atom.setMOL2Type("S.O");
//        }
//        else if (nTrip==1 && nSing==1)
//        {// Case OSV. Not good MOL2 type, but none exists for it
//            atom.setMOL2Type("S.2");
//        }
//        else if (nBd==2 && nDouble==2 && nOx==2)
//        {//SO2
//            atom.setMOL2Type("S.2");
//        }
//        else if (nBd==2 && nDouble==2 && nC==1 && nN==1)
//        {// V21
//            atom.setMOL2Type("S.2");
//        }
//        else if (nOx==0 && nBd==0 && nN==0 && nC==0 && nHy==0 )
//        {
//            atom.setMOL2Type("S.3");
//        }
//        else if (nOx==0 && nN==0 && nC ==2  && nHy==0 && nBd==2 && nAr==2)
//        {
//            atom.setMOL2Type("S.2");
//        }
//        else if (nOx==0 && nN==1 && nC ==1  && nHy==0 && nBd==2 && nAr==2)
//        {
//            atom.setMOL2Type("S.2");
//        }
//        else if (nOx==0 && nN==2 && nC ==0  && nHy==0 && nBd==2 && nAr==2)
//        {
//            atom.setMOL2Type("S.2");
//        }

//        else
//        {
//            cerr << "UNRECOGNIZED ATOM "<< atom.getParent().getName()
//                 <<"::"<<atom.getName()<<"\t"<<atom.getElement()<<atom.getMID()<<"\t"<<
//                   nOx<<" "<<
//                   nN<<" "<<
//                   nC<<" "<<
//                   nHy<<" "<<
//                   nBd<<" "<<
//                   nSing<<" "<<
//                   nDouble<<" "<<
//                   nTrip<<" "<<nAr
//                <<" "<<nDe<<" "<<nAmide<<endl;
//            return false;
//        }
//        return true;
//    }catch(ProtExcept &e)
//    {
//        e.addHierarchy("AtomPerc::perceiveSingleSulfur");
//        throw;
//    }
//}
//bool AtomPerc::perceiveSingleAtom(MMAtom& atom)
//{
//    try{
//        getStats(atom);
//        switch (mAtomicNum) {
//        case 6:
//            if(perceiveSingleCarbon(atom))return true;
//            break;
//        case 7:
//            if (perceiveSingleNitrogen(atom))return true;     break;
//        case 8:
//            if (perceiveSingleOxygen(atom))return true;break;
//        case 16:
//            if (perceiveSingleSulfur(atom))return true;break;
//        default:
//            break;
//        }
//        return false;
//    }catch(ProtExcept &e)
//    {
//        e.addHierarchy("AtomPerc::perceiveSingleAtom");
//        throw;
//    }
//}
