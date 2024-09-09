#ifndef INTER_METAL_H
#define INTER_METAL_H
#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterMetal:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() const;
    bool checkAngle();
    double getAngle()const {return 0;}

public:
    static double mMaxDist;



    InterMetal(MacroMole& pMole, const bool&pIsSameMole=true);

};
}
#endif // INTER_METAL_H

