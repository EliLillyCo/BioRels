#ifndef INTER_IONIC_H
#define INTER_IONIC_H

#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterIonic:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() const;
    double getAngle()const {return 0;}
public:
    static double mMaxDist;
    InterIonic(MacroMole& pMole, const bool&pIsSameMole=true);
};
}
#endif // INTER_IONIC_H

