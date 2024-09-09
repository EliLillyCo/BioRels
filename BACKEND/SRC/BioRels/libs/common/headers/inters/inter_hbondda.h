#ifndef INTER_HBONDDA_CPP
#define INTER_HBONDDA_CPP

#include "headers/inters/inter_hbond.h"
namespace protspace
{

    class Inter_HBond_DA:public InterHBond
    {
        public:
        Inter_HBond_DA(MacroMole& pMole, const bool&pIsSameMole=true);

    protected:
        bool checkAngle();
        bool checkInteraction();
    };
}

#endif // INTER_HBONDDA_CPP

