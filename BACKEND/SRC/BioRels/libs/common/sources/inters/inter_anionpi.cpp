#include <math.h>
#include <sstream>
#include "headers/inters/inter_anionpi.h"
#include "headers/math/coords_utils.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/molecule/mmresidue.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"


double protspace::InterAnionPI::mDistThres=6;
double protspace::InterAnionPI::mAngleCenter=0;
double protspace::InterAnionPI::mAngleRange=M_PI/4;

protspace::InterAnionPI::InterAnionPI(const MMRing& ring):
    InterAtomArom(ring)
{

}


bool protspace::InterAnionPI::isInteracting()
{
    if (mAtom == nullptr)
        throw_line("800101",
                   "InterAnionPI::isInteracting",
                   "No anion set");
    runMath();
    if (mData.mRc > mDistThres)return false;
    if (mData.mTheta > mAngleCenter+mAngleRange
            ||mData.mTheta < mAngleCenter-mAngleRange)return false;
    if (mData.mDSide > 1.2 )return false;
    return true;
}



bool protspace::InterAnionPI::isInteracting(InterData& data)
{
    if (!isInteracting())return false;

    InterObj pObj(mRing.getAtomCenter(),
                  *mAtom,
                  INTER::PI_ANION,
                  mData.mRc);
    pObj.setRing1(mRing);
    pObj.setAngle(mData.mTheta);
    data.addInter(pObj);
    return true;
}
