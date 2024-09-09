#ifndef INTER_WEAKHBOND_H
#define INTER_WEAKHBOND_H

#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterWHBond:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() const;
    bool checkAngle();
    ///
    /// \brief Angle found between HDonor - Hydrogen - HAcceptor
    ///
    double mDHA_Angle;

    ///
    /// \brief Position of the Hydrogen in the list of linked atoms of the HDonor
    ///
    size_t mHyd;

    bool isFilteredAcc(const MMAtom& acc)const;
public:
    static double mMaxDist;
    ///
    /// \brief Optimal Angle between HDonor - Hydrogen - HAcceptor
    ///
    static double mDHA_C;
    ///
    /// \brief Allowed angle range around the optimal angle
    ///
    static double mDHA_R;


    InterWHBond(MacroMole& pMole,const bool& pIsSameMole=true);
    MMAtom& getHydrogen()const {return getMolecule().getAtom(mHyd);}
    double getAngle()const {return mDHA_Angle;}
    double getDegAngle()const {return mDHA_Angle*RadToDeg;}
};
}


#endif // INTER_WEAKHBOND_H

