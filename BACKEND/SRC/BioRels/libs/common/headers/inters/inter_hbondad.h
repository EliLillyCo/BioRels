#ifndef INTER_HBONDAD_CPP
#define INTER_HBONDAD_CPP

#include "headers/inters/inter_hbond.h"
namespace protspace
{

    class Inter_HBond_AD:public InterHBond
    {
    public:
        Inter_HBond_AD(MacroMole& pMole, const bool&pIsSameMole=true);

    protected:
        bool checkAngle();
        bool checkInteraction();
    };
}

#endif // INTER_HBONDAD_CPP

