#include <math.h>
#include "headers/inters/inter_hbondhalogen.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
using namespace std;
using namespace protspace;

double InterHalogenHBond::mMaxDist=3.4;
double InterHalogenHBond::mXHD_C=3*M_PI/4;
double InterHalogenHBond::mXHD_R=M_PI3;





InterHalogenHBond::InterHalogenHBond(MacroMole& pMole, const bool &pIsSameMole):
    InterAtomBase(pMole,INTER::HBOND_HALOGEN,true,pIsSameMole)
{

}






bool InterHalogenHBond::checkInteraction()
{
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;
    if (!checkAngle())return false;
    return true;
}





bool InterHalogenHBond::checkProperty()const
{
    const PhysProp& prop1=getAtomRef().getProperty();
    const PhysProp& prop2=getAtomComp().getProperty();

    if (!(prop1.hasProperty(CHEMPROP::HALOGEN) &&
          prop2.hasProperty(CHEMPROP::HBOND_DON))
      && !(prop2.hasProperty(CHEMPROP::HALOGEN) &&
           prop1.hasProperty(CHEMPROP::HBOND_DON)))return false;

    return true;
}






bool InterHalogenHBond::checkAngle()
{
    const bool isHalo(getAtomRef().prop().hasProperty(CHEMPROP::HALOGEN));
    const MMAtom& atomX = isHalo?getAtomRef():getAtomComp();
    const MMAtom& atomD = isHalo?getAtomComp():getAtomRef();


    for(size_t iAtm=0;iAtm< atomD.numBonds();++iAtm)
    {
        MMAtom& atomH= atomD.getAtom(iAtm);
        if (atomH.isHydrogen())continue;
        mHAR_Angle= atomH.pos().angle_between(atomX.pos(),atomD.pos());
        if (mHAR_Angle >= mXHD_C-mXHD_R && mHAR_Angle <= mXHD_C+mXHD_R)return true;
    }

    return false;

}


