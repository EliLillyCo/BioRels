#include <math.h>
#include <headers/inters/inter_carbonylpi.h>
#include <headers/inters/inter_anionpi.h>
#include "headers/inters/intercomplex.h"
#include "headers/math/grid_utils.h"
#include "headers/molecule/macromole_utils.h"
using namespace protspace;
using namespace std;

InterComplex::InterComplex(MacroMole& pMole):
    mMolecule(pMole),
    mHAcceptor({"O.2","O.3","O.co2","S.3","N.ar","N.2","O.spc","O.t3p"}),
    mHDonor({"O.3","N.pl3","N.4","N.ar","O.spc","O.t3p"}),
    mGrid(4,4),
    mIapol(mMolecule),
    mIAtApol(mMolecule,true),
    mIhalo(mMolecule),
    mHBondX(mMolecule),
    mIHBond(mMolecule),
    mIMet(mMolecule),
    mIIonic(mMolecule),
    mIWeak(mMolecule),
    wExportArom(false)

{

    prepareGrid();
}

void InterComplex::setWExportArom(bool value)
{
    wExportArom = value;
}
void InterComplex::prepareGrid()
try{
    mGrid.considerMolecule(mMolecule);
    mGrid.createGrid();
    protspace::assignPhysProps(mMolecule);
}catch(ProtExcept &e)
{
    assert(e.getId()!="220101");
    e.addHierarchy("InterProtLig::prepareGrid");
    throw;
}


void InterComplex::updateGrid()
{
    mGrid.regenBoxAtomAssign(false);

}


void InterComplex::processHBD(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
try{
    const bool wFilter(!mSelectedRes.empty());

    mIHBond.setAtomComp(atom);
    const bool isHBD(atom.prop().hasProperty(CHEMPROP::HBOND_DON) );
    mIWeak.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if (wFilter && !checkRes(atomC.getResidue()))continue;

        if (isHBD&& atomC.prop().hasProperty(CHEMPROP::HBOND_ACC))
        {
            mIHBond.setAtomRef(atomC);
            mIHBond.isInteraction(mData);
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
    e.addHierarchy("InterComplex::processHBD");
    throw;
}



void InterComplex::processHalo(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
try{
    const bool wFilter(!mSelectedRes.empty());

    mIhalo.setAtomComp(atom);
    mHBondX.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (&atomC.getParent()==&atom.getParent())continue;
        if (atomC.isHydrogen())continue;
        if (wFilter && !checkRes(atomC.getResidue()))continue;

        if (atomC.prop().hasProperty(CHEMPROP::HBOND_ACC))
        {

            mIhalo.setAtomRef(atomC);
            mIhalo.isInteraction(mData);
        }
        if (atomC.prop().hasProperty(CHEMPROP::HBOND_DON))
        {
            mHBondX.setAtomRef(atomC);
            mHBondX.isInteraction(mData);
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterComplex::processHalo");
    throw;
}





void InterComplex::processCation(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom)
try{
    const bool wFilter(!mSelectedRes.empty());

    mIIonic.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if ( !atomC.prop().hasProperty(CHEMPROP::ANIONIC))continue;
        if (wFilter && !checkRes(atomC.getResidue()))continue;
        mIIonic.setAtomRef(atomC);
        mIIonic.isInteraction(mData);

    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterComplex::processCation");
    throw;
}





void InterComplex::processAnionic(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom)
try{
    const bool wFilter(!mSelectedRes.empty());

    mIIonic.setAtomComp(atom);
    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;
        if ( !atomC.prop().hasProperty(CHEMPROP::CATIONIC))continue;
        if (wFilter && !checkRes(atomC.getResidue()))continue;
        mIIonic.setAtomRef(atomC);
        mIIonic.isInteraction(mData);

    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterComplex::processAnion");
    throw;
}







void InterComplex::processHBA(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom)

try{
    const bool wFilter(!mSelectedRes.empty());

    mIHBond.setAtomComp(atom);
    mIWeak.setAtomComp(atom);
    mIMet.setAtomComp(atom);

    for(size_t i=0;i< list.size();++i)
    {
        MMAtom& atomC = *list.at(i);
        if (atomC.isHydrogen())continue;

        if (!atomC.prop().hasProperty(CHEMPROP::METAL))continue;
        if (wFilter && !checkRes(atomC.getResidue()))continue;

            mIMet.setAtomRef(atomC);
            mIMet.isInteraction(mData);

    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="830201" && e.getId()!="830101");///Atom must be in molecule
    e.addHierarchy("InterComplex::processHBA");
    e.addDescription(atom.toString());
    throw;
}

bool InterComplex::checkRes(const protspace::MMResidue& res)const
{
    return (std::find(mSelectedRes.begin(),
                     mSelectedRes.end(),
                     &res)!=mSelectedRes.end());
}

void InterComplex::scanAtom(InterData& mData)
try{
    const bool wFilter(!mSelectedRes.empty());
    std::vector<MMAtom*> list;
    for(size_t iRes=0;iRes<mMolecule.numResidue();++iRes)
    {
        protspace::MMResidue& res=mMolecule.getResidue(iRes);
        if (wFilter && !checkRes(res))continue;
        for(size_t iAtm=0;iAtm < res.numAtoms();++iAtm)
        {
            MMAtom& atomR = res.getAtom(iAtm);
            if (atomR.isHydrogen())continue;
            list.clear();
            getAtomClose(list,atomR,6,mGrid,false);

            if (atomR.prop().hasProperty(CHEMPROP::HALOGEN)) processHalo(mData, list, atomR);

            if (atomR.prop().hasProperty(CHEMPROP::HBOND_DON)
                    || atomR.prop().hasProperty(CHEMPROP::WEAK_HBOND_DON))
                processHBD(mData, list, atomR);

            if (atomR.prop().hasProperty(CHEMPROP::HBOND_ACC)
                    || atomR.prop().hasProperty(CHEMPROP::WEAK_HBOND_ACC))
                processHBA(mData, list, atomR);

            if (atomR.prop().hasProperty(CHEMPROP::ANIONIC)) processAnionic(mData, list, atomR);
            //            if (atomR.prop().hasProperty(CHEMPROP::CATIONIC)) processCation(mData, list, atomR);

            //            if (atomR.prop().hasProperty(CHEMPROP::HYDROPHOBIC) && !atomR.prop().hasProperty(CHEMPROP::AROM_RING))
            //                processHydrophobic(mData, list, atomR);
        }
    }
}catch(ProtExcept &e)
{
    e.addHierarchy(" InterComplex::scanAtom");
    throw;
}


void InterComplex::scanHydroRingRing(InterData& mData, const MMRing& ringR,const MMRing& ringC)
{try{

        if (&ringR==&ringC)return;
        if (&ringR.getResidue()==&ringC.getResidue())return;

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

        mIAtApol.setAtomRef(ringR.getAtom(posR));
        mIAtApol.setAtomComp(ringC.getAtom(posC));
        const bool isInt = mIAtApol.isInteraction(mData);
        assert(isInt);

    }catch(ProtExcept &e)
    {
        e.addHierarchy(" InterProtLig::scanHydroRingRing(");
        throw;
    }}

void InterComplex::scanRingRing(InterData& mData)
{try{
        const bool wFilter(!mSelectedRes.empty());

        for(size_t iRing=0;iRing < mMolecule.numRings();++iRing) {
            const MMRing &ringR = mMolecule.getRing(iRing);
            if (!ringR.isAromatic())continue;
            if (wFilter && !checkRes(ringR.getResidue()))continue;

            for(size_t iComp=iRing+1;iComp < mMolecule.numRings();++iComp)
            {
                const MMRing& ringC=mMolecule.getRing(iComp);
                if (&ringR.getResidue()==&ringC.getResidue())continue;
                if (wFilter && !checkRes(ringC.getResidue()))continue;

                if (ringR.getCenter().distance(ringC.getCenter())>10)continue;
                if (ringC.isAromatic()) {
                    InterArom aromPI(ringR, ringC);
                    aromPI.runMath();
                    if (wExportArom)
                    {
                        aromPI.saveRefToMole();
                        aromPI.saveCompToMole();
                    }
                    //                    aromPI.isEdgeToFace(mData);
                    //                    aromPI.isParallelDisplaced(mData);
                    if (aromPI.isEdgeToFace(mData))continue;
                    if (aromPI.isParallelDisplaced(mData))continue;
                }
                scanHydroRingRing(mData,ringR,ringC);
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy(" InterProtLig::scanRingRing(");
        throw;
    }}




void InterComplex::processRingAtom(InterData& mData,const MMRing& pRing, MMAtom& pAtom)const
{try{

        const PhysProp& props=pAtom.prop();

        if (props.hasProperty(CHEMPROP::CATIONIC))
        {
            InterCationPI catPI(pRing);
            catPI.setAtom(pAtom);
            catPI.isInteracting(mData);
        }
        if (props.hasProperty(CHEMPROP::ANIONIC))
        {
            InterAnionPI anionPI(pRing);
            anionPI.setAtom(pAtom);
            anionPI.isInteracting(mData);
        }
        if (props.hasProperty(CHEMPROP::HBOND_DON)
                ||props.hasProperty(CHEMPROP::WEAK_HBOND_DON))
        {
            InterCHPI XHPI(pRing);
            XHPI.setAtom(pAtom);
            XHPI.isInteracting(mData);
        }
        if (props.hasProperty(CHEMPROP::HALOGEN))
        {InterHalogenPI XPI(pRing);
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
        e.addHierarchy(" InterComplex::processRingAtom");
        e.addDescription(pAtom.toString());
        e.addDescription(pRing.toString());
        throw;
    }}

void InterComplex::selectResAroundRes(MMResidue &pRes, const double &thres)
{
    mSelectedRes.clear();
    getResidueClose(mSelectedRes,pRes,thres,mGrid,false,true,false);

}


void InterComplex::scanRingAtom(InterData& mData)
{try{
        const bool wFilter(!mSelectedRes.empty());

        MMAtom*atmLig=nullptr;
        std::map<protspace::MMResidue*,
                std::map<double,
                std::pair<protspace::MMAtom*,protspace::MMAtom*>>> atoms;
        std::vector<MMAtom*> listCloseAtom;
        for(size_t iRing =0;iRing < mMolecule.numRings();++iRing)
        {
            const MMRing& pRing =mMolecule.getRing(iRing);
            if (!pRing.isAromatic())continue;
            if (wFilter && !checkRes(pRing.getResidue()))continue;

            listCloseAtom.clear();atoms.clear();
            getAtomClose(listCloseAtom,pRing,6,mGrid,false);

            for(size_t iAtmL=0;iAtmL<listCloseAtom.size();++iAtmL)
            {
                MMAtom& pAtom = *listCloseAtom.at(iAtmL);
                //                std::cout << "\t"<<pAtom.numBonds()<<"\t"<<pAtom.getIdentifier()<<"\t"<<pAtom.pos().distance(pRing.getCenter())<<std::endl;
                const PhysProp& props=pAtom.prop();
                if (pAtom.isHydrogen()||
                        props.hasProperty(CHEMPROP::AROM_RING))continue;
                if (wFilter && !checkRes(pAtom.getResidue()))continue;

                processRingAtom(mData,pRing,pAtom);
                const double dist(getClosestDist(pAtom,pRing,atmLig));
                atoms[&pAtom.getResidue()][dist]=std::make_pair(atmLig,&pAtom);

            }
            for(auto it = atoms.begin();it!=atoms.end();++it)
            {
                const auto &list=(*it).second;
                const auto itV=list.begin();
                mIAtApol.setAtomRef(*(*itV).second.first);
                mIAtApol.setAtomComp(*(*itV).second.second);
                mIAtApol.isInteraction(mData);
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy(" InterProtLig::scanRingAtomProt(");
        throw;
    }}

void InterComplex::calcInteractions(InterData& mData)
{
    scanAtom(mData);
    scanRingRing(mData);
    scanRingAtom(mData);
    processHydrophobic(mData);
    filter(mData);

}


void InterComplex::processHydrophobic(InterData& mData)
{
    try{
        const bool wFilter(!mSelectedRes.empty());

        double best_dist=1000;
        size_t posR=0,posC=0;
        std::vector<MMResidue*> list;
        for(size_t iR=0;iR< mMolecule.numResidue();++iR)
        {
            MMResidue&pRes=mMolecule.getResidue(iR);
            if (wFilter && !checkRes(pRes))continue;

            list.clear();
            getResidueClose(list,pRes,6,mGrid,true,true,false);

            for(size_t iC=0;iC< list.size();++iC)
            {
                MMResidue&cRes=*list.at(iC);
                if (&cRes==&pRes)continue;
                if (cRes.getMID() < pRes.getMID())continue;
                if (wFilter && !checkRes(cRes))continue;

                best_dist=1000;
                posR=pRes.numAtoms();
                posC=cRes.numAtoms();
                for(size_t iAtmR=0;iAtmR< pRes.numAtoms();++iAtmR)
                {
                    MMAtom& pAtomR = pRes.getAtom(iAtmR);
                    if (pAtomR.isHydrogen())continue;
                    if (!pAtomR.prop().hasProperty(CHEMPROP::HYDROPHOBIC))continue;
                    if (pAtomR.prop().hasProperty(CHEMPROP::AROM_RING))continue;
                    for(size_t iAtmC=0;iAtmC< cRes.numAtoms();++iAtmC)
                    {
                        MMAtom& pAtomC = cRes.getAtom(iAtmC);
                        if (pAtomC.isHydrogen())continue;
                        if (&pAtomC==&pAtomR)continue;
                        if (!pAtomC.prop().hasProperty(CHEMPROP::HYDROPHOBIC))continue;
                        if (pAtomC.prop().hasProperty(CHEMPROP::AROM_RING))continue;
                        const double dist (pAtomR.dist(pAtomC));
                        if (dist > best_dist )continue;
                        posR=iAtmR;posC=iAtmC;best_dist=dist;
                    }
                }///Correcting bug where add interaction was within atomR loop
                if (posR==pRes.numAtoms() || posC==cRes.numAtoms())continue;
                mIAtApol.setAtomRef(pRes.getAtom(posR));
                mIAtApol.setAtomComp(cRes.getAtom(posC));
                mIAtApol.isInteraction(mData);

            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterProtLig::processHydrophobic");
        throw;
    }
}
void InterComplex::filter(InterData& mData)
{
    std::map<protspace::MMResidue*, std::map<protspace::MMResidue*,std::map<unsigned char,std::vector<size_t>>>> list;
    for(size_t i=0;i<mData.count();++i)
    {
        InterObj& obj=mData.getInter(i);
        list[&obj.getResidue1()][&obj.getResidue2()][obj.getType()].push_back(i);
        list[&obj.getResidue2()][&obj.getResidue1()][obj.getType()].push_back(i);

    }
    std::vector<size_t> toDel;
    for(auto it=list.begin();it!=list.end();++it)
    {
        //protspace::MMResidue& resStart=*(*it).first;
        auto & listInter=(*it).second;
        for(auto it2=listInter.begin();it2!=listInter.end();++it2)
        {
            if ((*it2).second.size()==1)continue;
            protspace::MMResidue& resTo=*(*it2).first;
            std::map<unsigned char,std::vector<size_t>>& inters=(*it2).second;
            const auto io=inters.find(INTER::IONIC);
            if (resTo.getName()=="LYS" && io!=inters.end()&& (*io).second.size()==2)
            {
                InterObj& obj1=mData.getInter((*io).second.at(0));
                InterObj& obj2=mData.getInter((*io).second.at(1));
                if (obj1.getDistance() < obj2.getDistance())toDel.push_back((*io).second.at(1));
                else toDel.push_back((*io).second.at(0));
            }
            for(auto itI=inters.begin();itI!=inters.end();++itI)
            {
                const std::vector<size_t>& listing=(*itI).second;
                for(size_t i=0;i<listing.size();++i)
                {
                    InterObj& obj1=mData.getInter(listing.at(i));
                    if (obj1.getRing1()!=nullptr || obj1.getRing2()!=nullptr)continue;
                    for(size_t j=i+1;j<listing.size();++j)
                    {
                        InterObj& obj2=mData.getInter(listing.at(j));
                        if (obj2.getRing1()!=nullptr || obj2.getRing2()!=nullptr)continue;
                        if ((&obj1.getAtom1() == &obj2.getAtom1()&& &obj1.getAtom2()==&obj2.getAtom2())
                                ||(&obj1.getAtom1() == &obj2.getAtom2()&& &obj1.getAtom2()==&obj2.getAtom1()))
                        {
                            toDel.push_back(listing.at(j));
                        }
                    }
                }

            }

        }

    }

    std::sort(toDel.begin(),toDel.end());
    toDel.erase(std::unique(toDel.begin(),toDel.end()),toDel.end());

    for(auto it = toDel.rbegin();it!=toDel.rend();++it)
    {
        mData.erase(*it);
    }

}
