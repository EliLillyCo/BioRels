#ifndef INTER_CHPI_H
#define INTER_CHPI_H

#include "headers/inters/inter_atomarom.h"
namespace protspace
{
class InterData;
class InterCHPI:public InterAtomArom
{
protected:
    static double mDistThres;
    static double mAngleCenter;
    static double mAngleRange;
    static double mAngleDHC_C;
    static double mAngleDHC_R;

    double mDHC;
    void runMath();
public:
    InterCHPI(const MMRing& pRing);
    /**
     * @brief isInteracting
     * @return
     * @throw 850101   InterCHPI::isInteracting        No carbon set
     */
    bool isInteracting();
    /**
     * @brief isInteracting
     * @param data
     * @return
     * @throw 850101   InterCHPI::isInteracting        No carbon set
     */
    bool isInteracting(InterData &data);

};
}

#endif // INTER_CHPI_H

