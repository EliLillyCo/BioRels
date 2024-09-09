#ifndef INTER_AROM_H
#define INTER_AROM_H
#include <math.h>
#include "headers/molecule/mmring.h"
#include "headers/molecule/mmring_utils.h"

namespace protspace
{
class InterData;
class InterArom
{
protected:
    static double mDrc_PD;
   static  double mDryz_Min_PD;
   static  double mDryz_Max_PD;
     static double mTheta_C_PD;
     static double mTheta_R_PD;
     static double mAP_C_PD;
     static double mAP_R_PD;

     static double mDrc_EF;
     static double mDH_EF;
     static double mDryz_EF;
     static double mTheta_C_EF;
     static double mTheta_R_EF;
     static double mAP_C_EF;
     static double mAP_R_EF;

     static double mDrc_HE;
     static double mDryz_HE;
     static double mTheta_C_HE;
     static double mTheta_R_HE;
     static double mAP_C_HE;
     static double mAP_R_HE;

  const MMRing& mRingRef;
  const MMRing& mRingComp;
 ring_atm_inf mDataRef;
 ring_atm_inf mDataComp;
 double mAngleRot;
bool sameRingSystem()const;
public:
  InterArom(const MMRing& pRingRef, const MMRing& pRingComp);
    void runMath();
    bool isInteracting()const;
    void saveRefToMole()const;
    void saveCompToMole()const;
bool isParallelDisplaced(InterData& data)const;
bool isEdgeToFace(InterData &data)const;
bool isHerringBone()const;
};
}

#endif // INTER_AROM_H

