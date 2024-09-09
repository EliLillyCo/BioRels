#ifndef INTER_ATOMAROM_H
#define INTER_ATOMAROM_H
#include <math.h>
#include "headers/molecule/mmring.h"
#include "headers/molecule/mmring_utils.h"

namespace protspace
{
class InterData;
class InterAtomArom
{
protected:
    const MMRing& mRing;
    MMAtom*  mAtom;
    ring_atm_inf mData;
    bool mMathRun;
    InterAtomArom(const MMRing& pRing);
    void clear();
    void runMath();
public:
    void saveToMole()const;
    virtual bool isInteracting(InterData &data)=0;
    const double& getDistCenterRingToAtom()const {return mData.mRc;}
    const double& getAngle()const {return mData.mTheta;}
    void setAtom(const MMAtom& atom);
};
}
#endif // INTER_ATOMAROM_H

