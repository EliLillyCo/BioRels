#include <math.h>
#include "headers/inters/inter_metal.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmatom_utils.h"
using namespace std;
using namespace protspace;

double InterMetal::mMaxDist=2.8;




InterMetal::InterMetal(MacroMole& pMole, const bool &pIsSameMole):InterAtomBase(pMole,INTER::METAL_ACCEPTOR,true,pIsSameMole)
{

}






bool InterMetal::checkInteraction()
{
//    cout <<"INTER HBOND"<< checkProperty()<<" " << mAtomDistance<<" " << checkAngle()<<endl;
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;

    return true;
}





bool InterMetal::checkProperty()const
{
    const MMAtom& atom1 = getAtomRef();
    const MMAtom& atom2 = getAtomComp();
    const PhysProp& prop1 = atom1.getProperty();
    const PhysProp& prop2 = atom2.getProperty();
//    cout << atom1.getIdentifier()<<" " << atom2.getIdentifier()<<
//            " " <<prop1.hasProperty(CHEMPROP::HBOND_DON)
//            <<prop1.hasProperty(CHEMPROP::HBOND_ACC)
//               <<prop1.hasProperty(CHEMPROP::HBOND_DON)
//                  <<prop1.hasProperty(CHEMPROP::HBOND_ACC)<<endl;
    if (prop1.hasProperty(CHEMPROP::METAL))
    {
        if (!prop2.hasProperty(CHEMPROP::HBOND_ACC))return false;

        return true;
    }
    else if (prop2.hasProperty(CHEMPROP::METAL))
    {
        if (!prop1.hasProperty(CHEMPROP::HBOND_ACC))return false;

        return true;
    }else return false;


}





