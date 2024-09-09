#include <math.h>
#include "headers/inters/inter_hbond.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/math/coords_utils.h"
using namespace std;
using namespace protspace;

double InterHBond::mMaxDist=3.6;
double InterHBond::mDHA_C=M_PI;
double InterHBond::mDHA_R=M_PI3;




InterHBond::InterHBond(MacroMole& pMole, const bool &pIsSameMole):InterAtomBase(pMole,INTER::HBOND,true,pIsSameMole)
{

}






bool InterHBond::checkInteraction()
{
    try{
        if (!checkProperty())return false;
        if (mAtomDistance > mMaxDist) return false;
        if (!checkAngle())return false;
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterHBond::checkInteraction");
        throw;
    }

}





bool InterHBond::checkProperty()
try{
    const MMAtom& atom1 = getAtomRef();
    const MMAtom& atom2 = getAtomComp();
    const PhysProp& prop1 = atom1.getProperty();
    const PhysProp& prop2 = atom2.getProperty();

    for(size_t iAt=0;iAt < atom1.numBonds();++iAt)
    {
        if (atom2.hasBondWith(atom1.getAtom(iAt)))return false;
    }
    mLeft=false; mRight=false;
    if (prop1.hasProperty(CHEMPROP::HBOND_DON)
            && prop2.hasProperty(CHEMPROP::HBOND_ACC)
            && numHydrogenAtomBonded(atom1)>0) mLeft=true;
    if (prop2.hasProperty(CHEMPROP::HBOND_DON)
            && prop1.hasProperty(CHEMPROP::HBOND_ACC)
            && numHydrogenAtomBonded(atom2)>0) mRight=true;

    return (mRight||mLeft);



}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");/// getAtomRef & Comp must exists
    assert(e.getId()!="310501");///getAtom should work
    return false;
}




bool InterHBond::checkOrderAngle(const bool &order)
try{

    const MMAtom &atomD = (order) ? getAtomRef() : getAtomComp();
    const MMAtom &atomA = (order) ? getAtomComp() : getAtomRef();

    double currAngle, diff = 100, lowerdiff = 1000;

    /// Scanning all Hydrogen of the Hydrogen Donor
    /// To check the angle DHA with the Hydrogen Acceptor
    /// We keep the Hydrogen that has the closest angle from mDHA_C
    for (size_t iAtm = 0; iAtm < atomD.numBonds(); ++iAtm) {
        MMAtom &atomH = atomD.getAtom(iAtm);
        if (!atomH.isHydrogen())continue;
        currAngle = atomH.pos().angle_between(atomD.pos(), atomA.pos());
        diff = fabs(currAngle - mDHA_C);
        if (diff > lowerdiff) continue;
        lowerdiff = diff;
        mHyd = atomH.getMID();
        mDHA_Angle = currAngle;
        /// Then we check if this best angle is within the range
        if (!(mDHA_Angle > mDHA_C - mDHA_R && mDHA_Angle <= mDHA_C + mDHA_R))continue;
        if (numHeavyAtomBonded(atomD) == 0)return true;
        size_t Heavy = 0;
        for (size_t iAtm = 0; iAtm < atomA.numBonds(); ++iAtm) {
            if (atomA.getAtom(iAtm).isHydrogen())continue;
            Heavy = iAtm;
            break;
        }
        if (Heavy==atomA.numBonds())return true;
        //const MMAtom &ALinked = atomA.getAtom(Heavy);

        //const double mDAL_angle = atomA.pos().angle_between(atomD.pos(), ALinked.pos());
        //const double mDHAL_dihe = protspace::computeDihedralAngle(atomD.pos(), atomH.pos(), atomA.pos(), ALinked.pos());
      //  if (mDAL_angle < 2 * M_PI / 3) continue;
//        if (mDHAL_dihe < M_PI / 3)continue;
        return true;
    }
    return false;
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");/// getAtomRef & Comp must exists
    assert(e.getId()!="310501");///getAtom should work
    e.addHierarchy(" InterHBond::checkOrderAngle");
    throw;
}


bool InterHBond::checkAngle()
{

    if (mLeft &&checkOrderAngle(true))return true;
    return mRight && checkOrderAngle(false);


}

