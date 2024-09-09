#include "headers/molecule/mmatom_utils.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/atomdata.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
namespace protspace
{
size_t numHeavyAtomBonded(const MMAtom& atom)
{
    size_t nHeavy=0;
    for(size_t iBd=0;iBd < atom.numBonds();++iBd)
    {
        if (atom.getAtom(iBd).getAtomicNum()>1)nHeavy++;
    }
    return nHeavy;
}
size_t numHydrogenAtomBonded(const MMAtom& atom)
{
    size_t nHyd=0;
    for(size_t iBd=0;iBd < atom.numBonds();++iBd)
    {
        if (atom.getAtom(iBd).getAtomicNum()==1)nHyd++;
    }
    return nHyd;
}

size_t numAtomBonded(const MMAtom& atom,const unsigned char&pElem)
{
    size_t nAt=0;
    for(size_t iBd=0;iBd < atom.numBonds();++iBd)
    {
        if (atom.getAtom(iBd).getAtomicNum()==pElem)nAt++;
    }
    return nAt;
}

bool isAtomInAromRing(const MMAtom& pAtom)
{
    const MacroMole& mole=pAtom.getParent();
    for (size_t iRing=0;iRing < mole.numRings();++iRing)
    {
        const MMRing& ring = mole.getRing(iRing);
        if (!ring.isInRing(pAtom)) continue;
        if (ring.isAromatic())return true;
    }
    return false;
}


void removeAllHydrogen(MMAtom& atom)
try{
    std::vector<MMAtom*> list;
    for(size_t iBd=0;iBd < atom.numBonds();++iBd)
    {
        MMAtom& at=atom.getAtom(iBd);
        if (at.getAtomicNum()==1)list.push_back(&at);
    }
    for(size_t i=0;i<list.size();++i)atom.getParent().delAtom(*list[i]);
}catch(ProtExcept &e)
{
    assert(e.getId().compare("310501")!=0/// Atom linked must be within range
            && e.getId().compare("350801")!=0); /// Atom to delete must be in the molecule
    e.addHierarchy("RemoveAllHydrogen(ATOM)");
    e.addDescription(atom.toString());
    throw;
}

size_t hasAtom(const MMAtom& pAtom,
               const unsigned char& pElem,
               const uint16_t& pBond,
               const size_t& start)
{
    for(size_t iBd=start;iBd < pAtom.numBonds();++iBd)
    {
        if (pAtom.getAtom(iBd).getAtomicNum()!= pElem)continue;
        if (pBond ==BOND::UNDEFINED)return iBd;
        if (pBond ==pAtom.getBond(iBd).getType())return iBd;
    }
    return pAtom.numBonds();
}


void assignCarbonPhysProp(MMAtom& pAtom)
{
    if (pAtom.getAtomicNum()!=6)return;
    PhysProp& props=pAtom.prop();
    const size_t nHyd=numAtomBonded(pAtom,1);
    const size_t nCarb=numAtomBonded(pAtom,6);
    props.clear();
    ///CARBON IN AN AROMATIC => AROMATIC_RING
    if (isAtomInAromRing(pAtom))
    {
        if (nHyd>0)props.addProperty(CHEMPROP::WEAK_HBOND_DON);
        props.addProperty(CHEMPROP::AROM_RING);
    }
    /// IS ONLY LINKED TO CARBON ATOMS => HYDROPHOBIC
    /// WITH HYDROGEN : WEAK HBOND DONOR
    if (nCarb+nHyd==pAtom.numBonds())
    {
        props.addProperty(CHEMPROP::HYDROPHOBIC);
        if (nHyd>0)props.addProperty(CHEMPROP::WEAK_HBOND_DON);
    }
    if (pAtom.getName()=="CE" && pAtom.getResidue().getName()=="LYS")
        props.addProperty(CHEMPROP::CATIONIC);
    if (pAtom.getFormalCharge()>0)props.addProperty(CHEMPROP::CATIONIC);
    if (pAtom.getFormalCharge()<0)props.addProperty(CHEMPROP::ANIONIC);
    for(size_t iBd=0;iBd < pAtom.numBonds();++iBd)
    {
        const uint16_t& btype=pAtom.getBond(iBd).getType();
        if (btype==BOND::DOUBLE || btype==BOND::TRIPLE)

            props.addProperty(CHEMPROP::WEAK_HBOND_ACC);
    }
}



void assignSulfurPhysProp(MMAtom& pAtom)
{
    if (pAtom.getAtomicNum()!=16)return;
    PhysProp& props=pAtom.prop();
    const size_t nHyd=numAtomBonded(pAtom,1);
    if (numAtomBonded(pAtom,6)+nHyd==pAtom.numBonds())
    {
        props.addProperty(CHEMPROP::HYDROPHOBIC);
        if (nHyd>0)props.addProperty(CHEMPROP::WEAK_HBOND_DON);
    }
    props.addProperty(CHEMPROP::WEAK_HBOND_ACC);
    if (pAtom.getFormalCharge()>0)props.addProperty(CHEMPROP::CATIONIC);
    if (pAtom.getFormalCharge()<0)props.addProperty(CHEMPROP::ANIONIC);
}

void assignNitrogenPhysProp(MMAtom& pAtom)
{

    if (pAtom.getAtomicNum()!=7)return;
    PhysProp& props=pAtom.prop();
    const size_t nHyd=numAtomBonded(pAtom,1);

    if (isAtomInAromRing(pAtom))
    {
        props.addProperty(CHEMPROP::AROM_RING);
        if (nHyd)props.addProperty(CHEMPROP::HBOND_DON);
        else
        {
            props.addProperty(CHEMPROP::HBOND_ACC);
            props.addProperty(CHEMPROP::WEAK_HBOND_ACC);
        }

    }
    else
    {
        if (nHyd)
        {
            props.addProperty(CHEMPROP::HBOND_DON);
            props.addProperty(CHEMPROP::WEAK_HBOND_DON);
        }
        if (pAtom.numBonds()<=3)
        {
            props.addProperty(CHEMPROP::HBOND_ACC);
            props.addProperty(CHEMPROP::WEAK_HBOND_ACC);
        }
    }
    if (pAtom.numBonds()==4||
        pAtom.getFormalCharge()>0)props.addProperty(CHEMPROP::CATIONIC);
    if (pAtom.getFormalCharge()<0)props.addProperty(CHEMPROP::ANIONIC);
}

void assignOxygenPhysProp(MMAtom& pAtom)
{
    if (pAtom.getAtomicNum()!=8)return;
    const size_t nHyd=numAtomBonded(pAtom,1);
    PhysProp& props=pAtom.prop();
    static const std::string listHBacc=" O.2 O.3 O.spc O.t3p O.co2 ";
    static const std::string listHBdon=" O.3 O.spc O.t3p ";
    if (nHyd>=1 && listHBdon.find(pAtom.getMOL2())!=std::string::npos)
    {
        props.addProperty(CHEMPROP::HBOND_DON);
        props.addProperty(CHEMPROP::WEAK_HBOND_DON);
    }
    if (listHBacc.find(pAtom.getMOL2())!=std::string::npos)
    {
        props.addProperty(CHEMPROP::HBOND_ACC);
        props.addProperty(CHEMPROP::WEAK_HBOND_ACC);
    }
    if (pAtom.getFormalCharge()>0)props.addProperty(CHEMPROP::CATIONIC);
    if (pAtom.getFormalCharge()<0)props.addProperty(CHEMPROP::ANIONIC);
}

size_t numBondType(const MMAtom& pAtom,const uint16_t& btype)
{
    size_t count=0;
    for(size_t iAtm=0;iAtm<pAtom.numBonds();++iAtm)
    {
        if (pAtom.getBond(iAtm).getType()==btype)++count;
    }
    return count;
}
bool isBackbone(const MMAtom& pAtom)
{
    const std::string& atmName=pAtom.getName();
    static const std::string listN=" N CA C O ";
    if (listN.find(" "+atmName+" ")!=std::string::npos )return true;
    return false;
}

short checkValence(MMAtom& pAtom)
try{

    double currVal=0;short nUndef=0;
    for(size_t iBd=0;iBd<pAtom.numBonds();++iBd)
    {
        switch(pAtom.getBond(iBd).getType())
        {
        case BOND::SINGLE:
        case BOND::AMIDE:      currVal+=1;break;
        case BOND::AROMATIC_BD:
        case BOND::DELOCALIZED:currVal+=1.5;break;
        case BOND::DOUBLE:     currVal+=2;break;
        case BOND::TRIPLE:     currVal+=3;break;
        case BOND::QUADRUPLE:  currVal+=4;break;
        case BOND::FUSED:      throw_line("","","");
        case BOND::UNDEFINED:  nUndef++;break;
        }
    }

    /// If charge +1, it means it has 1 more valence then expected
    /// so we retrieve it.
    currVal-=pAtom.getFormalCharge();
    const short diff =(short)currVal-(short)Periodic[pAtom.getAtomicNum()].valence;
//    LOG(pAtom.getIdentifier()+ " - Current value: "+std::to_string(currVal)+" Expected:"+std::to_string(Periodic[pAtom.getAtomicNum()].valence)+" - DIFF="+std::to_string(diff));

    if (diff==0)
    {
        if (nUndef==0)return 0;
        for(size_t iBd=0;iBd<pAtom.numBonds();++iBd)
        {
            if (pAtom.getBond(iBd).getType()!=BOND::UNDEFINED)continue;
            if (pAtom.getAtom(iBd).isHydrogen())
            {
                 pAtom.getBond(iBd).setBondType(BOND::SINGLE);

            }
            else
            {
            //          std::cout << "DELETING "<<pAtom.getBond(iBd).toString()<<std::endl;
            LOG_ERR("Deleting undefined bond "+pAtom.getBond(iBd).toString());
            pAtom.getParent().delBond(pAtom.getBond(iBd));
            iBd=0;
            }
        }

        return 0;
    }else if (diff==-1 && nUndef==1)
    {
        for(size_t iBd=0;iBd<pAtom.numBonds();++iBd)
        {
            if (pAtom.getBond(iBd).getType()!=BOND::UNDEFINED)continue;
            pAtom.getBond(iBd).setBondType(BOND::SINGLE);
            return 0;
        }

    }
    //    std::cout <<"################################\n\n";
    //    std::cout <<pAtom.getIdentifier()<<" " <<currVal<<"/" <<(short)Periodic[pAtom.getAtomicNum()].valence<<std::endl;
    return diff;
}catch(ProtExcept &e)
{
    ///Bond deletion should work:
    assert(e.getId()!="350701" &&e.getId()!="020101" && e.getId()!="350702" &&e.getId()!="020201");
    e.addHierarchy("AtomUtils::checkValence");
    throw;
}


void addExplicitHydrogen(MacroMole& mole)
try{
    std::ostringstream oss;
    size_t nAddH=0;
    const Coords coo;
    for(size_t iAtm=0;iAtm < mole.numAtoms();++iAtm)
    {
        MMAtom& atom = mole.getAtom(iAtm);
        const signed char count = checkValence(atom);
        if (count <=0)continue;
        for(signed char i=0;i<count;++i)
        {
            ++nAddH;
            oss.str("");
            oss<<"H"<<nAddH;
            mole.addBond(atom,
                         mole.addAtom(atom.getResidue(),coo,oss.str(),"H","H"),
                         BOND::SINGLE);
        }

    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="030401" && e.getId()!="310701");
    assert(e.getId()!="310802" && e.getId()!="310101"&&e.getId()!="310102"&&e.getId()!="350301"&&e.getId()!="350302");
    assert(e.getId()!="350602"&&e.getId()!="350603" &&e.getId()!="350604");
    e.addHierarchy("AtomUtils::addExplicitHydrogens");
    throw;
}

void expandSelectToRing(protspace::MMAtom& atom)
{
    protspace::MacroMole& mole = atom.getParent();
    atom.select(true);
    std::vector<protspace::MMRing*> list;
    std::vector<protspace::MMAtom*> todo, done,nextodo;
    if (mole.isAtomInRing(atom))
    {
        mole.getRingsFromAtom(atom,list);
        for(protspace::MMRing* ring:list)ring->setUse(true);
        return;
    }
    done.push_back(&atom);

    for(size_t iB=0;iB< atom.numBonds();++iB) todo.push_back(&atom.getAtom(iB));
    do
    {
        nextodo.clear();;
        for(protspace::MMAtom* atomT:todo)
        {
            atomT->select(true);
            if (std::find(done.begin(),done.end(),atomT)!=done.end())continue;
            done.push_back(atomT);
            if (mole.isAtomInRing(*atomT))
            {
                mole.getRingsFromAtom(*atomT,list);
                for(protspace::MMRing* ring:list) ring->setUse(true);
                continue;
            }
            for(size_t iB=0;iB< atomT->numBonds();++iB)
                nextodo.push_back(&atomT->getAtom(iB));
        }
        todo=nextodo;
    }while(!todo.empty());

}


bool findShortestPath(protspace::MMAtom& pAtomFrom,
                      protspace::MMAtom& pAtomTo,
                      std::vector<protspace::MMAtom*>& pResults)
{
    std::vector<std::vector<protspace::MMAtom*>> paths,newpaths;
    int selPath=-1;
    std::vector<protspace::MMAtom*> p1({&pAtomFrom});paths.push_back(p1);
    do
    {
        newpaths.clear();
        for(std::vector<protspace::MMAtom*>& path:paths)
        {
            protspace::MMAtom& atomLast=*path.at(path.size()-1);
            for(size_t iB=0;iB<atomLast.numBonds();++iB)
            {
                protspace::MMAtom& atom2=atomLast.getAtom(iB);
                if (std::find(path.begin(),path.end(),&atom2)!=path.end())continue;
                std::vector<protspace::MMAtom*> newPath(path);
                newPath.push_back(&atom2);
                newpaths.push_back(newPath);
                if (&atom2!=&pAtomTo) continue;
                selPath=newpaths.size()-1;
                break;
            }
            if (selPath!=-1)break;
        }
        paths=newpaths;
        if (selPath!=-1)break;
    }while(!paths.empty());
    if (selPath==-1)return false;
    pResults=paths.at(selPath);
    return true;
}

bool shareLinkAtom(const protspace::MMAtom& pAtom1,
                   const protspace::MMAtom& pAtom2)
{
    for(size_t iAt=0;iAt < pAtom1.numBonds();++iAt)
    {
        if (pAtom2.hasBondWith(pAtom1.getAtom(iAt)))return true;
    }
    return false;
}
MMAtom& getFirstHeavyAtom(protspace::MMAtom& pAtom)
{
    for(size_t iAt=0;iAt < pAtom.numBonds();++iAt)
    {
     if (!pAtom.getAtom(iAt).isHydrogen())   return pAtom.getAtom(iAt);
    }
    throw_line("","","");
}
}
