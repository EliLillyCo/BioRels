#ifndef INTER_ATOMBASE_H
#define INTER_ATOMBASE_H
#include <math.h>
#include "headers/molecule/macromole.h"

#define M_PI4 M_PI/4
#define M_PI6 M_PI/6
#define M_PI3 M_PI/3
#define M_PI23 2*M_PI/3
#define RadToDeg 180/M_PI
namespace protspace
{

class InterData;
class InterAtomBase
{
  private:
    MacroMole& mMolecule;
    size_t mAtomPos1;
    MacroMole* mMolecule2;
    size_t mAtomPos2;
protected:
    const unsigned char mInterType;
    ///
    /// \brief Distance between the two heavy atoms
    ///
    double mAtomDistance;
    bool wAngle;
    bool mIsSameMole;
    void updateDistance();
    virtual bool checkInteraction()=0;
public:
    InterAtomBase(MacroMole& pMole,
                  const unsigned char pInterType,
                  const bool& pwAngle=false,
                  const bool& pIsSameMole=true);

    /**
     * @brief setAtomRef
     * @param pAtom
     * @throw 830101   InterAtomBase::setAtomRef       Given Atom is not part of the molecule
     */
    void setAtomRef(const MMAtom& pAtom);

    /**
     * @brief setAtomComp
     * @param pAtom
     * @throw 830201    InterAtomBase::setAtomComp  Given Atom is not part of the molecule
     */
    void setAtomComp(const MMAtom& pAtom);

    /**
     * @brief setAtomRef
     * @param pos
     * @throw 830301   InterAtomBase::setAtomRef       Given Position is above the number of atom for this molecule
     */
    void setAtomRef(const size_t& pos);

    /**
     * @brief setAtomComp
     * @param pos
     * @throw 830401   InterAtomBase::setAtomComp      Given Position is above the number of atom for this molecule
     */
    void setAtomComp(const size_t& pos);
    void setMoleComp(MacroMole& pMole);

    /**
     * @brief getAtomRef
     * @return
     * @throw 030401 MacroMole::getAtom Given position is above the number of dots
     */
    MMAtom& getAtomRef()const ;

    /**
     * @brief getAtomComp
     * @return
     * @throw 030401 MacroMole::getAtom Given position is above the number of dots
     */
    MMAtom& getAtomComp()const;
    const double& getDistance()const {return mAtomDistance;}
    bool isInteraction(InterData &pObj);
    virtual double getAngle()const=0;
    MacroMole& getMolecule()const {return mMolecule;}
    MacroMole& getMolecule2()const;
    bool getIsSameMole() const;

};
}

#endif // INTER_ATOMBASE_H

