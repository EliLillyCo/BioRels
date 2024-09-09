#ifndef INTER_HALOGENAROM_H
#define INTER_HALOGENAROM_H


#include "headers/inters/inter_atomarom.h"


namespace protspace
{
class InterData;
class InterHalogenPI:public InterAtomArom
{
protected:
    static double mDistThres;
    static double mAngleCenter;
    static double mAngleRange;
    static double mAngleRXC_C;
    static double mAngleRXC_R;
public:
    InterHalogenPI(const MMRing& pRing);

    /**
     * @brief isInteracting
     * @param data
     * @return
     * @throw 860101   InterHalogenPI::isInteracting   No Halogen set
     */
    bool isInteracting(InterData &data);
    /**
     * @brief isInteracting
     * @return
     * @throw 860101   InterHalogenPI::isInteracting   No Halogen set
     */
    bool isInteracting();

};
}
#endif // INTER_HALOGENAROM_H

