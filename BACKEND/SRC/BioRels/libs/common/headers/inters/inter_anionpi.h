//
// Created by c188973 on 10/7/16.
//

#ifndef GC3TK_CPP_INTER_ANIONPI_H_H
#define GC3TK_CPP_INTER_ANIONPI_H_H
#include <math.h>
#include "headers/inters/inter_atomarom.h"

namespace protspace
{
    class InterData;
    class InterAnionPI:public  InterAtomArom
    {
    protected:
        static double mDistThres;
        static double mAngleCenter;
        static double mAngleRange;
    public:
        InterAnionPI(const MMRing& pRing);

        /**
         * @brief isInteracting
         * @param data
         * @return
         * @throw 800101   InterAnionPI::isInteracting     No anion set
         */
        bool isInteracting(InterData &data);

        /**
         * @brief isInteracting
         * @return
         * @throw 800101   InterAnionPI::isInteracting     No anion set
         */
        bool isInteracting();
    };
}

#endif //GC3TK_CPP_INTER_ANIONPI_H_H
