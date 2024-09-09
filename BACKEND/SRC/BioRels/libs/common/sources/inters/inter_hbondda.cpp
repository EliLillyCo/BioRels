#include "headers/inters/inter_hbondda.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
protspace::Inter_HBond_DA::Inter_HBond_DA(MacroMole &pMole, const bool &pIsSameMole):
    protspace::InterHBond(pMole,pIsSameMole)
{

}


bool protspace::Inter_HBond_DA::checkInteraction()
{
    try{

        if (!getAtomRef() .prop().hasProperty(CHEMPROP::HBOND_DON))return false;
        if (!getAtomComp().prop().hasProperty(CHEMPROP::HBOND_ACC))return false;
        if (mAtomDistance > mMaxDist) return false;
        if (!checkAngle())return false;
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterHBond::checkInteraction");
        throw;
    }

}


bool protspace::Inter_HBond_DA::checkAngle()
try{

    double currAngle, diff = 100, lowerdiff = 1000;
    protspace::MMAtom& atomD=getAtomRef();
    protspace::MMAtom& atomA=getAtomComp();
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

