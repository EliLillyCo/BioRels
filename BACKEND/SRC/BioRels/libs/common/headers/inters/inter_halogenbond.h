#ifndef INTER_HALOGENBOND_H
#define INTER_HALOGENBOND_H

#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterHalogenBond:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() const;
    bool checkAngle();
    ///
    /// \brief Angle found between Halogen donor (R), Halogen and the Acceptor
    ///
    double mRXA_Angle;

public:
    static double mMaxDist;
    ///
    /// \brief Optimal Angle between Halogen, the Hydrogen and the Hydrogen donor
    ///
    static double mRXA_C;
    ///
    /// \brief Allowed angle range around the optimal angle
    ///
    static double mRXA_R;

    InterHalogenBond(MacroMole& pMole, const bool&pIsSameMole=true);

    double getAngle()const {return mRXA_Angle;}
    double getDegAngle()const {return mRXA_Angle*RadToDeg;}
};
}


#endif // INTER_HALOGENBOND_H

