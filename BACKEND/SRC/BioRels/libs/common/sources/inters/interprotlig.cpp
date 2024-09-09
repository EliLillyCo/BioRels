#include "headers/inters/interprotlig.h"
#include "headers/math/grid_utils.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/inters/inter_carbonylpi.h"
using namespace protspace;
using namespace std;

InterProtLig::InterProtLig(MacroMole& pMole):
    mProtein(pMole),
    mHAcceptor({"O.2","O.3","O.co2","S.3","N.ar","N.2","O.spc","O.t3p"}),
    mHDonor({"O.3","N.pl3","N.4","N.ar","O.spc","O.t3p"}),
    mGrid(4,4),
    mIhalo(mProtein,false),
    mIHBondAD(mProtein,false),
    mIHBondDA(mProtein,false),
    mIIonic(mProtein,false),
    mIWeak(mProtein,false),
    mIXBond(mProtein,false),
    mIMet(mProtein,false),
    mDebugExport(true)
{try{
        protspace::assignPhysProps(mProtein);
        prepareGrid();

    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterProtLig::InterProtLig");
        throw;

    }
}
void InterProtLig::prepareGrid()

try{
    mGrid.considerMolecule(mProtein);
    mGrid.createGrid();
}catch(ProtExcept &e)
{
    assert(e.getId()!="220101");
    e.addHierarchy("InterProtLig::prepareGrid");
    throw;
}




void InterProtLig::processHBD(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
try{

    mIHBondAD.setAtomComp(atom);
    const bool isHBD(atom.prop().hasProperty(CHEMPROP::HBOND_DON) );
    mIWeak.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if (isHBD&& atomC.prop().hasProperty(CHEMPROP::HBOND_ACC))
        {
            mIHBondAD.setAtomRef(atomC);
            mIHBondAD.isInteraction(mData);
        }
        else if (atomC.prop().hasProperty(CHEMPROP::WEAK_HBOND_ACC))
        {
            mIWeak.setAtomRef(atomC);
            mIWeak.isInteraction(mData);
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterProtLig::processHBD");
    throw;
}




void InterProtLig::processClash(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
{
    try{

        mIhalo.setAtomComp(atom);

        mIXBond.setAtomComp(atom);
        for(size_t i=0;i< list.size();++i)
        {
            MMAtom& atomC = *list.at(i);
            if (&atomC.getParent()==&atom.getParent())continue;
            if (atomC.isHydrogen())continue;
            if (atomC.dist(atom)>= (atomC.getvdWRadius()+atom.getvdWRadius())/2)continue;
            InterObj pObj(atom,
                              atomC,
                                INTER::CLASH_APOLAR,
                                atomC.dist(atom));
            mData.addInter(pObj);
        }
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
        e.addHierarchy("InterProtLig::processHalo");
        throw;
    }
}
void InterProtLig::processHalo(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
{
    try{

        mIhalo.setAtomComp(atom);

        mIXBond.setAtomComp(atom);
        for(size_t i=0;i< list.size();++i)
        {
            MMAtom& atomC = *list.at(i);
            if (&atomC.getParent()==&atom.getParent())continue;
            if (atomC.isHydrogen())continue;

            if (atomC.prop().hasProperty(CHEMPROP::HBOND_ACC))
            {

                mIhalo.setAtomRef(atomC);
                mIhalo.isInteraction(mData);
            }
            if (atomC.prop().hasProperty(CHEMPROP::HBOND_DON))
            {
                mIXBond.setAtomRef(atomC);
                mIXBond.isInteraction(mData);
            }
        }
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
        e.addHierarchy("InterProtLig::processHalo");
        throw;
    }
}
void InterProtLig::processCation(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom)
try{

    mIIonic.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if ( !atomC.prop().hasProperty(CHEMPROP::ANIONIC))continue;

        mIIonic.setAtomRef(atomC);
        mIIonic.isInteraction(mData);

    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterProtLig::processCation");
    throw;
}

void InterProtLig::processAnionic(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
try{
    mIIonic.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if ( !atomC.prop().hasProperty(CHEMPROP::CATIONIC))continue;

        mIIonic.setAtomRef(atomC);
        mIIonic.isInteraction(mData);

    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterProtLig::processAnion");
    throw;
}


void InterProtLig::processHBA(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom)

try{
    const bool isHBA(atom.prop().hasProperty(CHEMPROP::HBOND_ACC) );
    mIHBondDA.setAtomComp(atom);
    mIWeak.setAtomComp(atom);
    mIMet.setAtomComp(atom);

    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        //        assignProperties(atomC);

        if (isHBA &&atomC.prop().hasProperty(CHEMPROP::HBOND_DON))
        {
            mIHBondDA.setAtomRef(atomC);
            mIHBondDA.isInteraction(mData);
        }
        else if (atomC.prop().hasProperty(CHEMPROP::WEAK_HBOND_DON))
        {
            mIWeak.setAtomRef(atomC);
            mIWeak.isInteraction(mData);
        }
        if (atomC.prop().hasProperty(CHEMPROP::METAL))
        {
            mIMet.setAtomRef(atomC);
            mIMet.isInteraction(mData);
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterProtLig::processHBA");
    e.addDescription(atom.toString());
    throw;
}




void InterProtLig::processHydrophobic(InterData& mData,
                                      const std::vector<MMAtom*>& list,
                                      MMAtom& atom)
try{
    InterAtomApolar iapol(atom.getParent(),false);

    iapol.setMoleComp(mProtein);
    iapol.setAtomRef(atom);
    double bestdist=1000;size_t pos;
    std::vector<MMResidue*> listR;
    for(size_t i=0;i< list.size();++i)
    {
        listR.push_back(&list.at(i)->getResidue());
    }
    sort(listR.begin(),listR.end());
    listR.erase(std::unique(listR.begin(),listR.end()),listR.end());
    for(size_t iR=0;iR<listR.size();++iR)
    {
        MMResidue& res1 = *listR.at(iR);bestdist=1000;
        for(size_t i=0;i< list.size();++i)
        {
            MMAtom& atomC = *list.at(i);
            if (&atomC.getResidue() != &res1)continue;
            if (atomC.isHydrogen())continue;
            if (atomC.prop().hasProperty(CHEMPROP::HYDROPHOBIC))
            {

                iapol.setAtomComp(atomC);
                if (!iapol.checkInteraction())continue;
                if(iapol.getDistance() > bestdist)continue;
                bestdist = iapol.getDistance();
                pos=i;
            }
        }
        if (bestdist > InterAtomApolar::mMaxDist)continue;
        iapol.setAtomComp(*list.at(pos));
        iapol.isInteraction(mData);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterProtLig::processHydrophobic");
    throw;
}


void InterProtLig::prepInters(MacroMole& pLigand)
{
    mIWeak.setMoleComp(pLigand);
    mIIonic.setMoleComp(pLigand);
    mIHBondDA.setMoleComp(pLigand);
    mIHBondAD.setMoleComp(pLigand);
    mIhalo.setMoleComp(pLigand);
    mIXBond.setMoleComp(pLigand);
    mIMet.setMoleComp(pLigand);
}

void InterProtLig::scanAtom(InterData& mData, MacroMole& pLigand)
try{
    protspace::assignPhysProps(pLigand);
    std::vector<MMAtom*> list;
    for(size_t iAtmR=0;iAtmR < pLigand.numAtoms();++iAtmR) {
        MMAtom &atomR = pLigand.getAtom(iAtmR);

        if (atomR.isHydrogen())continue;
        list.clear();
        getAtomClose(list, atomR, 6, mGrid, false);
        if (atomR.prop().hasProperty(CHEMPROP::HALOGEN)) processHalo(mData, list, atomR);
        if(!atomR.isHydrogen())processClash(mData,list,atomR);

        if (atomR.prop().hasProperty(CHEMPROP::HBOND_DON)
                || atomR.prop().hasProperty(CHEMPROP::WEAK_HBOND_DON))
            processHBD(mData, list, atomR);

        if (atomR.prop().hasProperty(CHEMPROP::HBOND_ACC)
                || atomR.prop().hasProperty(CHEMPROP::WEAK_HBOND_ACC))
            processHBA(mData, list, atomR);

        if (atomR.prop().hasProperty(CHEMPROP::ANIONIC))
        {
            processAnionic(mData, list, atomR);
        }
        if (atomR.prop().hasProperty(CHEMPROP::CATIONIC))
        {
            processCation(mData, list, atomR);
        }
        if (atomR.prop().hasProperty(CHEMPROP::HYDROPHOBIC) && !atomR.prop().hasProperty(CHEMPROP::AROM_RING))
            processHydrophobic(mData, list, atomR);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="220701"&& e.getId()!="220702");
    e.addHierarchy("InterProtLig::scanAtom");
    throw;
}



void InterProtLig::scanHydroRingRing(InterData& mData, const MMRing& ringR,const MMRing& ringC)const
try{


    double bestdist=InterAtomApolar::mMaxDist;
    size_t posR(ringR.numAtoms()),posC(ringC.numAtoms());
    for(size_t iAtmR=0;iAtmR< ringR.numAtoms();++iAtmR)
    {
        MMAtom& atomR = ringR.getAtom(iAtmR);
        if (!atomR.prop().hasProperty(CHEMPROP::HYDROPHOBIC))continue;
        for(size_t iAtmC=0;iAtmC< ringC.numAtoms();++iAtmC)
        {
            MMAtom& atomC = ringC.getAtom(iAtmC);

            if (!atomC.prop().hasProperty(CHEMPROP::HYDROPHOBIC))continue;
            const double dist(atomR.dist(atomC));
            if (dist >bestdist)continue;
            bestdist=  dist;
            posR=iAtmR;posC=iAtmC;
        }

    }
    if (bestdist >= InterAtomApolar::mMaxDist)return;
    InterAtomApolar iaa(ringR.getResidue().getParent(),false);
    iaa.setMoleComp(mProtein);
    iaa.setAtomRef(ringR.getAtom(posR));
    iaa.setAtomComp(ringC.getAtom(posC));
    const bool isInt = iaa.isInteraction(mData);
    assert(isInt);

}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy(" InterProtLig::scanHydroRingRing");
    throw;
}

void InterProtLig::scanRingRing(InterData& mData, MacroMole& pLigand)
try{

    for(size_t iRing=0;iRing < pLigand.numRings();++iRing) {
        const MMRing &ringR = pLigand.getRing(iRing);
        if (!ringR.isAromatic())continue;
        for(size_t iComp=0;iComp < mProtein.numRings();++iComp)
        {
            const MMRing& ringC=mProtein.getRing(iComp);
            if (ringR.getCenter().distance(ringC.getCenter())>10)continue;
            if (ringC.isAromatic()) {
                InterArom aromPI(ringR, ringC);
                aromPI.runMath();

                if (aromPI.isEdgeToFace(mData))continue;
                if (aromPI.isParallelDisplaced(mData))continue;
            }
            scanHydroRingRing(mData,ringR,ringC);
        }
    }
}catch(ProtExcept &e)
{
    e.addHierarchy(" InterProtLig::scanRingRing");
    throw;
}


void InterProtLig::processRingAtom(InterData& mData,const MMRing& pRing, MMAtom& pAtom)const
try{

    const PhysProp& props=pAtom.prop();
    if (props.hasProperty(CHEMPROP::CATIONIC))
    {
        InterCationPI catPI(pRing);
        catPI.setAtom(pAtom);
        catPI.isInteracting(mData);
    }
    if (props.hasProperty(CHEMPROP::HBOND_DON)
            ||props.hasProperty(CHEMPROP::WEAK_HBOND_DON))
    {
        InterCHPI XHPI(pRing);
        XHPI.setAtom(pAtom);
        XHPI.isInteracting(mData);
    }
    if (props.hasProperty(CHEMPROP::HALOGEN))
    {
        InterHalogenPI XPI(pRing);
        XPI.setAtom(pAtom);
        XPI.isInteracting(mData);
    }
    if (pAtom.isOxygen())
    {
        InterCarbonylPI CPI(pRing);
        if (CPI.setOxygen(pAtom)){
            CPI.runMath();
            CPI.isInteracting(mData);

        }
    }
}catch(ProtExcept &e)
{
    e.addHierarchy(" InterProtLig::processRingAtom");
    e.addDescription(pAtom.toString());
    throw;
}

void InterProtLig::scanRingAtomProt(InterData& mData, MacroMole& pLigand)const
{try{
        std::vector<MMResidue*> listProtRes;
        for(size_t iRes=0;iRes < pLigand.numResidue();++iRes)
            getResidueClose(listProtRes,pLigand.getResidue(iRes), 6, mGrid, false,false,true);
        if (pLigand.getTempResidue().numAtoms()>0)
            getResidueClose(listProtRes,pLigand.getTempResidue(), 6, mGrid, false,false,true);
        double best_dist=1000;MMAtom* atm=nullptr,*atmLig=nullptr,*bestAtmLig=nullptr;
        for(size_t iRing =0;iRing < mProtein.numRings();++iRing)
        {
            const MMRing& pRing =mProtein.getRing(iRing);

            const auto it = std::find(listProtRes.begin(),
                                      listProtRes.end(),&pRing.getResidue());
            if (it ==listProtRes.end())continue;
            best_dist=1000;
            atm=nullptr;
            atmLig=nullptr;
            bestAtmLig=nullptr;
            for(size_t iAtmL=0;iAtmL<pLigand.numAtoms();++iAtmL)
            {
                MMAtom& pAtom = pLigand.getAtom(iAtmL);

                const PhysProp& props=pAtom.prop();
                if (pAtom.isHydrogen()||props.hasProperty(CHEMPROP::AROM_RING))continue;
                if (!isAtomCloserThan(pAtom,pRing,6))continue;
                processRingAtom(mData,pRing,pAtom);
                if (!props.hasProperty(CHEMPROP::HYDROPHOBIC))continue;
                const double dist(getClosestDist(pAtom,pRing,atmLig));
                if (dist >best_dist)continue;
                best_dist=dist;bestAtmLig=atmLig;
                atm=&pAtom;
            }
            if (atm==nullptr || bestAtmLig==nullptr)continue;
            InterAtomApolar iaa(pLigand,false);
            iaa.setMoleComp(mProtein);
            iaa.setAtomRef(*atm);
            iaa.setAtomComp(*bestAtmLig);
            iaa.isInteraction(mData);
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy(" InterProtLig::scanRingAtomProt(");
        throw;
    }}




void InterProtLig::scanRingAtomLig(InterData& mData, MacroMole& pLigand)const
{
    try{
        std::vector<MMAtom*> listLigAtom;
        std::map<protspace::MMResidue*,
                std::map<double,
                std::pair<protspace::MMAtom*,protspace::MMAtom*>>> atoms;
        MMAtom*atmLig=nullptr;
        for(size_t iRing =0;iRing < pLigand.numRings();++iRing)
        {
            const MMRing& pRing =pLigand.getRing(iRing);
            atoms.clear();
            listLigAtom.clear();
            getAtomClose(listLigAtom,pRing,6,mGrid,false);

            for(size_t iAtm=0;iAtm < listLigAtom.size();++iAtm)
            {
                MMAtom& pAtom = *listLigAtom.at(iAtm);
                const PhysProp& props=pAtom.prop();
                if (pAtom.isHydrogen()||
                        props.hasProperty(CHEMPROP::AROM_RING)||
                        !props.hasProperty(CHEMPROP::HYDROPHOBIC))continue;
                if (!isAtomCloserThan(pAtom,pRing,6))continue;
                processRingAtom(mData,pRing,pAtom);
                const double dist(getClosestDist(pAtom,pRing,atmLig));
                atoms[&pAtom.getResidue()][dist]=
                        std::pair<protspace::MMAtom*,protspace::MMAtom*>(atmLig,&pAtom);
            }
            for(auto it = atoms.begin();it!=atoms.end();++it)
            {
                const auto &list=(*it).second;
                const auto itV=list.begin();
                InterAtomApolar iaa(pLigand,false);
                iaa.setMoleComp(mProtein);
                iaa.setAtomRef(*(*itV).second.first);
                iaa.setAtomComp(*(*itV).second.second);
                iaa.isInteraction(mData);

            }
        }

    }catch(ProtExcept &e)
    {
        e.addHierarchy(" InterProtLig::scanRingAtomLig(");
        throw;
    }
}



void InterProtLig::calcInteractions(InterData& mData, MacroMole& pLigand)

try{
    prepInters(pLigand);
    scanAtom(mData, pLigand);
    scanRingRing(mData,pLigand);
    scanRingAtomProt(mData,pLigand);
    scanRingAtomLig(mData,pLigand);

}catch(ProtExcept &e)
{
    e.addHierarchy("InterProtLig::calcInteraction");
    throw;
}



