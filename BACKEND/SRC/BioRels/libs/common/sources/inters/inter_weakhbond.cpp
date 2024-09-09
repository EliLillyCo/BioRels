#include <math.h>
#include "headers/inters/inter_weakhbond.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/math/coords_utils.h"
using namespace std;
using namespace protspace;

double InterWHBond::mMaxDist=4;
double InterWHBond::mDHA_C=M_PI;
double InterWHBond::mDHA_R=M_PI6;




InterWHBond::InterWHBond(MacroMole& pMole,const bool& pIsSameMole):InterAtomBase(pMole,INTER::WEAK_HBOND,true,pIsSameMole)
{

}






bool InterWHBond::checkInteraction()
{
    try{
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;
    if (!checkAngle())return false;

    return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterWHBond::checkInteraction");
        throw;
    }

}


bool InterWHBond::isFilteredAcc(const MMAtom& acc)const
{
    for(size_t i=0;i < acc.numBonds();++i)
    {
        const MMAtom& atom=acc.getAtom(i);

        if (atom.isOxygen() && acc.getBond(i).getType()==BOND::DOUBLE)return true;
    }
    return false;
}


bool InterWHBond::checkProperty()const
{
    try{
    const MMAtom& atom1 = getAtomRef();
    const MMAtom& atom2 = getAtomComp();
    const PhysProp& prop1 = atom1.getProperty();
    const PhysProp& prop2 = atom2.getProperty();
    const bool atom1isDon(  prop1.hasProperty(CHEMPROP::HBOND_DON)
                         || prop1.hasProperty(CHEMPROP::WEAK_HBOND_DON));
    const bool atom2isDon(  prop2.hasProperty(CHEMPROP::HBOND_DON)
                         || prop2.hasProperty(CHEMPROP::WEAK_HBOND_DON));
    const bool atom1isAcc(  prop1.hasProperty(CHEMPROP::HBOND_ACC)
                         || prop1.hasProperty(CHEMPROP::WEAK_HBOND_ACC));
    const bool atom2isAcc(  prop2.hasProperty(CHEMPROP::HBOND_ACC)
                         || prop2.hasProperty(CHEMPROP::WEAK_HBOND_ACC));
    if (atom1isDon)
    {
        if (!atom2isAcc)return false;
        if (isFilteredAcc(atom2))return false;
        if (numHydrogenAtomBonded(atom1)==0)return false;
        return true;
    }else if (atom2isDon)
    {   if (!atom1isAcc)return false;
        if (isFilteredAcc(atom1))return false;
        if (numHydrogenAtomBonded(atom2)==0)return false;
        return true;
    }
    return false;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterWHbond::checkProperty");
        throw;
    }


}






bool InterWHBond::checkAngle()
{
    const bool isAtm1(getAtomRef().prop().hasProperty(CHEMPROP::HBOND_DON)
                   ||getAtomRef().prop().hasProperty(CHEMPROP::WEAK_HBOND_DON));
    const MMAtom& atomD = (isAtm1)?getAtomRef():getAtomComp();
    const MMAtom& atomA = (isAtm1)?getAtomComp():getAtomRef();
    try{
    double currAngle,diff=100, lowerdiff=1000;

    /// Scanning all Hydrogen of the Hydrogen Donor
    /// To check the angle DHA with the Hydrogen Acceptor
    /// We keep the Hydrogen that has the closest angle from mDHA_C
    MMAtom* hyd=nullptr;

    for(size_t iAtm=0;iAtm < atomD.numBonds();++iAtm)
    {
        MMAtom& atomH = atomD.getAtom(iAtm);
        if (!atomH.isHydrogen())continue;
        currAngle=atomH.pos().angle_between(atomD.pos(),atomA.pos());
        diff=fabs(currAngle-mDHA_C);
        if (diff > lowerdiff) continue;
        lowerdiff=diff;
        mHyd=atomH.getMID();
        mDHA_Angle=currAngle;
        hyd = &atomH;
    }
    /// Then we check if this best angle is within the range
    if (!(mDHA_Angle > mDHA_C-mDHA_R && mDHA_Angle <= mDHA_C+mDHA_R))return false;

    size_t Heavy=atomA.numBonds();
    for(size_t iAtm=0;iAtm < atomA.numBonds();++iAtm) {
        if (atomA.getAtom(iAtm).isHydrogen())continue;
        Heavy=iAtm;break;
    }
        if (Heavy==atomA.numBonds())return true;
    const MMAtom& ALinked=atomA.getAtom(Heavy);

    const double mDAL_angle=atomA.pos().angle_between(atomD.pos(),ALinked.pos());
    const double mDHAL_dihe=protspace::computeDihedralAngle(atomD.pos(),hyd->pos(),atomA.pos(),ALinked.pos());
//    std::cout << order<< "/"<<mLeft<<"/"<<mRight<<"\t"<<atomA.getIdentifier()<<" "<<atomA.getProperty().hasProperty(CHEMPROP::HBOND_ACC)
//    <<atomA.getProperty().hasProperty(CHEMPROP::HBOND_DON)<<"\t"
//    <<atomD.getIdentifier()<<" "<<atomD.getProperty().hasProperty(CHEMPROP::HBOND_ACC)
//      <<atomD.getProperty().hasProperty(CHEMPROP::HBOND_DON)<<"\t"<<ALinked.getIdentifier()<<" \t"<<mDAL_angle*180/M_PI<<"\t"<<mDHAL_dihe*180/M_PI<<std::endl;
    if (mDAL_angle < M_PI/2) return false;


    return mDHAL_dihe >= M_PI / 4;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterWHbond::checkAngle");
        e.addDescription(atomD.toString());
        e.addDescription(atomA.toString());
        throw;
    }



}


