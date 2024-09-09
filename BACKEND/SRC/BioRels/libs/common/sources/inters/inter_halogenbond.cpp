#include <math.h>
#include "headers/inters/inter_halogenbond.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
using namespace std;
using namespace protspace;

double InterHalogenBond::mMaxDist=3.8;
double InterHalogenBond::mRXA_C=M_PI;
double InterHalogenBond::mRXA_R=M_PI4;





InterHalogenBond::InterHalogenBond(MacroMole& pMole, const bool &pIsSameMole):
    InterAtomBase(pMole,INTER::HALOGEN_BOND,true,pIsSameMole)
{

}






bool InterHalogenBond::checkInteraction()
{
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;
    if (!checkAngle())return false;
    return true;
}





bool InterHalogenBond::checkProperty()const
{
    const PhysProp& prop1=getAtomRef().getProperty();
    const PhysProp& prop2=getAtomComp().getProperty();

    if (!(prop1.hasProperty(CHEMPROP::HALOGEN) &&
          prop2.hasProperty(CHEMPROP::HBOND_ACC))
      && !(prop2.hasProperty(CHEMPROP::HALOGEN) &&
           prop1.hasProperty(CHEMPROP::HBOND_ACC)))return false;

    return true;
}






bool InterHalogenBond::checkAngle()
{
    const bool isHalo(getAtomRef().prop().hasProperty(CHEMPROP::HALOGEN));
    const MMAtom& atomX = isHalo?getAtomRef():getAtomComp();
    const MMAtom& atomA = isHalo?getAtomComp():getAtomRef();
    if (atomX.numBonds()==0)return false;
    const MMAtom& atomR= atomX.getAtom(0);

        mRXA_Angle= atomX.pos().angle_between(atomA.pos(),atomR.pos());
        if (mRXA_Angle >= mRXA_C-mRXA_R && mRXA_Angle <= mRXA_C+mRXA_R)return true;


    return false;

}


