#include <math.h>
#include "headers/inters/inter_ionic.h"
#include "headers/statics/intertypes.h"
using namespace std;
using namespace protspace;

 double InterIonic::mMaxDist=4.5;
InterIonic::InterIonic(MacroMole& pMole, const bool&pIsSameMole):InterAtomBase(pMole,INTER::IONIC,false,pIsSameMole)
{

}




bool InterIonic::checkInteraction()
{
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;
    return true;
}


bool InterIonic::checkProperty()const
{
    const PhysProp& atom1 = getAtomRef().prop();
    const PhysProp& atom2 = getAtomComp().prop();
    if (!(atom1.hasProperty(CHEMPROP::ANIONIC) &&
          atom2.hasProperty(CHEMPROP::CATIONIC))
      &&!(atom2.hasProperty(CHEMPROP::ANIONIC) &&
          atom1.hasProperty(CHEMPROP::CATIONIC)))return false;
    return true;
}
