#ifndef INTER_HBOND_H
#define INTER_HBOND_H
#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterHBond:public InterAtomBase
{
protected:
    bool checkInteraction();
    bool checkProperty() ;
    bool checkAngle();
    bool checkOrderAngle(const bool& order) ;



    bool mLeft;

    bool mRight;

    bool mDoubleTest;
    ///
    /// \brief Angle found between HDonor - Hydrogen - HAcceptor
    ///
    double mDHA_Angle;

    ///
    /// \brief Position of the Hydrogen in the molecule
    ///
    size_t mHyd;
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


    InterHBond(MacroMole& pMole, const bool&pIsSameMole=true);
    MMAtom& getHydrogen()const {return getMolecule().getAtom(mHyd);}
    double getAngle()const {return mDHA_Angle;}
    double getDegAngle()const {return mDHA_Angle*RadToDeg;}


    void setDoubleTest(bool doubleTest);
};
}
#endif // INTER_HBOND_H

