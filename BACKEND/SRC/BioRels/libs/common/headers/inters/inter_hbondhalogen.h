#ifndef INTER_HALOGENBONDACC_H
#define INTER_HALOGENBONDACC_H
#include <math.h>
#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterHalogenHBond:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() const;
    bool checkAngle();
    ///
    /// \brief Angle found between Halogen donor, Halogen and the Acceptor
    ///
    double mDHA_Angle;

    ///
    /// \brief Angle found between Halogen, Acceptor and the atom in alpha of the acceptor
    ///
    double mHAR_Angle;

public:
    static double mMaxDist;
    ///
    /// \brief Optimal Angle between Halogen, the Hydrogen and the Hydrogen donor
    ///
    static double mXHD_C;
    ///
    /// \brief Allowed angle range around the optimal angle
    ///
    static double mXHD_R;

    InterHalogenHBond(MacroMole& pMole, const bool&pIsSameMole=true);

    double getAngle()const {return mDHA_Angle;}
    double getDegAngle()const {return mDHA_Angle*RadToDeg;}
};
}
#endif // INTER_HALOGENBONDACC_H

