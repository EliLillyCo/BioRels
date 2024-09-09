#ifndef INTER_CATIONPI_H
#define INTER_CATIONPI_H
#include <math.h>
#include "headers/inters/inter_atomarom.h"

namespace protspace
{
class InterData;
class InterCationPI:public InterAtomArom
{
protected:
    static double mDistThres;
    static double mAngleCenter;
    static double mAngleRange;
public:
    InterCationPI(const MMRing& pRing);
    /**
     * @brief isInteracting
     * @return
     * @throw 840101   InterCationPI::isInteracting    No cation set
     */
    bool isInteracting();

    /**
     * @brief isInteracting
     * @param data
     * @return
     * @throw 840101   InterCationPI::isInteracting    No cation set
     */
    bool isInteracting(InterData &data);
    void exportGeom(std::ofstream &ofs);
};
}

#endif // INTER_CATIONPI_H

