#include <math.h>
#include <sstream>
#include "headers/inters/inter_halogenarom.h"
#include "headers/math/coords_utils.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/molecule/mmresidue.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"
using namespace protspace;
using namespace std;

double InterHalogenPI::mDistThres=4;
double InterHalogenPI::mAngleCenter=0;
double InterHalogenPI::mAngleRange=M_PI/4;
double InterHalogenPI::mAngleRXC_C=M_PI;
double InterHalogenPI::mAngleRXC_R=M_PI/4;
InterHalogenPI::InterHalogenPI(const MMRing& ring):InterAtomArom(ring)
{

}


bool InterHalogenPI::isInteracting()
{
    if (mAtom == nullptr)
        throw_line("860101",
                   "InterHalogenPI::isInteracting",
                   "No Halogen set");
    runMath();
    if (mData.mRc > mDistThres)return false;
    if (mData.mTheta > mAngleCenter + mAngleRange
            || mData.mTheta < mAngleCenter - mAngleRange)
        return false;
    if (mData.mDSide > 0.8)return false;
    //// What is that ?
    ///    if (mRXC > mAngleRXC_C + mAngleRXC_R
    ///        || mRXC < mAngleRXC_C - mAngleRXC_R)
    ///return false;
    return true;
}

bool InterHalogenPI::isInteracting(InterData& data)

try {
    if (!isInteracting())return false;
    InterObj pObj(mRing.getAtomCenter(),
                  *mAtom,
                  INTER::HALOGEN_PI,
                  mData.mRc);
    pObj.setRing1(mRing);
    data.addInter(pObj);
    return true;
}catch(ProtExcept &e)
{
    e.addHierarchy("InterHalogenPI::isInteracting");
    throw;
}


