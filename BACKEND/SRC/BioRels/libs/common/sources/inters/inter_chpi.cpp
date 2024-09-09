#include <math.h>
#include <sstream>
#include "headers/inters/inter_chpi.h"
#include "headers/math/coords_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"
using namespace protspace;
using namespace std;

 double InterCHPI::mDistThres=6;
 double InterCHPI::mAngleCenter=0;
 double InterCHPI::mAngleRange=M_PI/4;
 double InterCHPI::mAngleDHC_C=M_PI;
 double InterCHPI::mAngleDHC_R=M_PI/4;
InterCHPI::InterCHPI(const MMRing& ring):InterAtomArom(ring),mDHC(0)
{

}





void InterCHPI::runMath()
{
    try{
    processRingAtomInfo(mRing,mAtom->pos(),mData);
    double mAngle;mDHC=0;
    for(size_t i=0;i<mAtom->numBonds();++i)
    {
        MMAtom& H= mAtom->getAtom(i);
        if (!H.isHydrogen())continue;
        mAngle = H.pos().angle_between(mAtom->pos(),mRing.getCenter());
        if (fabs(mAngleDHC_C-mAngle) < fabs(mAngleDHC_C-mDHC))
        {
            mDHC = mAngle;
        }
    }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterAnionPI::runMath");
        throw;
    }

}
bool InterCHPI::isInteracting()
{
    if (mAtom == nullptr)throw_line("850101",
                                      "InterCHPI::isInteracting",
                                      "No carbon set");
    runMath();

    if (mData.mRc > mDistThres)return false;
    if (mData.mTheta > mAngleCenter+mAngleRange
     ||mData.mTheta < mAngleCenter-mAngleRange)return false;
    if (mData.mDSide > 1.5 )return false;
    if( mDHC > mAngleDHC_C+mAngleDHC_R
            ||mDHC < mAngleDHC_C-mAngleDHC_R)return false;
    return true;
}


bool InterCHPI::isInteracting(InterData& data)
{try{
   if (!isInteracting())return false;

    InterObj pObj(mRing.getAtomCenter(),
                  *mAtom,
                  INTER::XH_PI,
                  mDHC);
    pObj.setRing1(mRing);
    data.addInter(pObj);
    return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterH-Arene::isInteracting");
        throw;
    }
}
