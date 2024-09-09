#ifndef MMBOND_H
#define MMBOND_H
#include <cstdint>
#include "headers/statics/link.h"
#include "headers/molecule/mmatom.h"


namespace protspace
{

class MacroMole;


class MMBond:public link<MacroMole,MMAtom >
{
    friend class Group<MMAtom,MMBond,MacroMole>;
    friend class MacroMole;
protected:
    uint16_t mType;

    bool mSelected;

    ///
    /// \brief Minimal constructor
    /// \param parent : Parent molecule
    /// \param atom1 : First atom involved in the bond
    /// \param atom2 : Second atom involved in the bond
    /// \param mid : Id as given by the input file
    /// \param bondType : Type of the bond
    /// \throw 300201 : Both atoms are the same
    ///
    MMBond(const MacroMole& parent,
           MMAtom& atom1,
           MMAtom& atom2,
           const int &mid,
           const uint16_t& bondType)throw(ProtExcept);

    virtual ~MMBond();
public:
    ///
     /// \brief getOtherAtom
     /// \param atom : One of the two atoms involved in this bond
     /// \return the other atom involved in this bond
     /// \throw 300201  when no atom is given
     /// \throw 010201 - Given atom is not part of this bond
     ///
     /// The given atom must be one the the two atom involved in this bond
     ///
    MMAtom& getOtherAtom(MMAtom* const atom) const  throw(ProtExcept);

    ///
     /// \brief Return the reference of the first atom involved in the bond
     /// \return Reference of the first atom involved in the bond
     ///
    inline MMAtom& getAtom1() const {return mDot1;}


    ///
     /// \brief Return the reference of the second atom involved in the bond
     /// \return Reference of the second atom involved in the bond
     ///
    inline MMAtom& getAtom2() const {return mDot2;}


    ///
     /// \brief Set the bond order for this bond
     /// \param btype Bond order to set to. See Bond::TYPE for possibilities
     ///
    void setBondType(const uint16_t& bondtype) {mType=bondtype;}


    ///
     /// \brief Get the type of the bond (See Bond::TYPE) for more information
     /// \return Type of the bond
     ///
    inline const uint16_t& getType() const {return mType;}




    ///
     /// \brief Give the length of the bond, i.e. the distance between the 2 atoms
     /// \return a distance
     ///
    double dist() const;


    ///
     /// \brief Set the use for this bond
     /// \param isUsed New status for the bond
     /// \param applyToAtom  apply the new status to the atoms involved in the bond
     /// Molecular object has the possibility to ignore some atom, bond, MMResidue
     /// or even chain for the sake of computational time and/or output. To do so,
     /// user has the possibility to "tag" an atom, a bond, a MMResidue, a chain and
     /// update other object if necessary. For example, turning off an atom will
     /// by default turn off all of it's bond. But turning off a bond will only
     /// turn off the bond. Turning off a MMResidue turns off all atoms and bond
     /// and can turn off the chain only if its chain has no long MMResidue in use.
     ///
    void setUse(const bool& isUsed, const bool& applyToAtom=false);


    ///
     /// \brief Is this bond in use ?
     /// \return TRUE when the bond is in use, false otherwise.
     ///
     /// See setUse function for more information by use
     ///
    const bool& isSelected() const {return mSelected;}



    void serialize(std::ofstream& out) const;

    operator std::string();
    std::string toString()const;
};

}

#endif // MMBOND_H

