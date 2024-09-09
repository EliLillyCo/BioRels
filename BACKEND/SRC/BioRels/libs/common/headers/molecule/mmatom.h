#ifndef MMATOM_H
#define MMATOM_H
#include <cstdint>

#include "headers/statics/dot.h"
#include "headers/statics/group.h"
#include "headers/math/coords.h"
#include "headers/molecule/physprop.h"
#include "headers/molecule/mmresidue.h"

namespace protspace
{

class MacroMole;
class MMBond;


class MMAtom:public dot<MacroMole,MMAtom>
{
    friend class MacroMole;
    friend class MMResidue;
    friend class Group<MMAtom,MMBond,MacroMole>;
private:
    size_t mCoordPoolPos;
protected:

    ///
    /// \brief  3D coordinates describing the position of this atom
    ///
    Coords&        mPosition;

    ///
    /// \brief  MMAtom name, e.g. CA,CB,O,NZ ...
    ///
    std::string              mName;

    ///
    /// \brief TRIPOS MOL2 atom type
    ///
    std::string            mMOL2type;


    ///
    /// \brief Temperature Factor
    ///
    /// If we were able to hold an atom rigidly fixed in one place, we could
    ///  observe its distribution of electrons in an ideal situation.
    /// The image would be dense towards the center with the density
    /// falling off further from the nucleus. When you look at experimental
    /// electron density distributions, however, the electrons usually
    /// have a wider distribution than this ideal.
    /// This may be due to vibration of the atoms, or differences between
    /// the many different molecules in the crystal lattice.
    /// The observed electron density will include an average of all
    /// these small motions, yielding a slightly smeared image of the molecule.
    ///
    /// These motions, and the resultant smearing of the electron density,
    /// are incorporated into the atomic model by a B-value or temperature factor.
    /// The amount of smearing is proportional to the magnitude of the B-value.
    /// Values under 10 create a model of the atom that is very sharp,
    /// indicating that the atom is not moving much and is in the same position
    /// in all of the molecules in the crystal. Values greater than 50 or so
    /// indicate that the atom is moving so much that it can barely been seen.
    /// This is often the case for atoms at the surface of proteins,
    /// where long sidechains are free to wag in the surrounding water.
    /// (Source : PDB-101)
    double mBFactor;


    ///
    /// \brief Formal charge of the atom
    ///
    signed char            mFCharge;

    ///
    /// \brief Atomic number of the atom
    ///
    unsigned char           mAtomicNumber;


    ///
    /// \brief Residue associated with this atom
    ///
    int                   mResidueId;



    ///
    /// \brief Selected atom
    ///
    bool                    mSelected;

    ///
    /// \brief Physico chemical properties of the atom
    ///
    PhysProp mProperties;







protected:
    MMAtom(const unsigned int& id,
           MacroMole * const molecule);

    MMAtom(const MMAtom& atom);


    /**
     * @brief remove the current bond from the list of bond of this atom
     * @param bond : bond to remove
     * @throw 020101 Given link is not part of this dot
     * This function does not delete the bond instance. It just remove the bond
     * from the bond list and the other atom involved in the bond from the
     * MMAtom::atomlist
     */
    void delBond(MMBond &bond) throw(ProtExcept);

    /**
     * @brief remove the current bond from the list of bond of this atom
     * @param bond : bond to remove
     * @throw 080401 no bond given
     * @throw 020101 Given link is not part of this dot
     * This function does not delete the bond instance. It just remove the bond
     * from the bond list and the other atom involved in the bond from the
     * MMAtom::atomlist
     */
    void delBond(const MMBond * const bond) throw(ProtExcept);

    void setResidue(MMResidue* res);

public:
virtual ~MMAtom();
    ///
    /// \brief  Gives the 3D coordinates of this atom
    /// \return Coords instance describing the 3D coordinates
    ///
    inline const protspace::Coords& pos() const {return mPosition;}

    ///
    /// \brief  Gives the 3D coordinates of this atom
    /// \return Coordinates of the atom
    ///
    inline  Coords& pos() {return mPosition;}

    ///
    /// \brief Set the formal charge of the atom
    /// \param charge value to set the formal charge to
    ///
    void setFormalCharge(const signed char& charge){mFCharge=charge;}

    ///
    /// \brief get the Formal Charge of this atom
    /// \return value of the formal charge
    ///
    inline  const signed char& getFormalCharge() const {return mFCharge;}

    ///
    /// \brief Defines the value of the BFactor
    /// \param bf Value of the BFactor
    ///
    void setBFactor(const double& bf){mBFactor=bf;}



    ///
    /// \brief Returns a const reference of the BFactor for this atom
    /// \return BFactor of this atom
    ///
    inline  const double& getBFactor()const {return mBFactor;}


    ///
    /// \brief Return the name of this atom
    /// \return name of this atom (CA, CB,O, NZ ...)
    /// Returns the atom name property of an atom. This property is typically
    /// used when reading or writing molecular file formats. The default value
    /// is an empty string. However, it should never stay this way.
    ///
    ///
    ///
    inline  const std::string& getName() const {return mName;}



    ///
    /// \brief tell whether this atom is used or not
    /// \return TRUE for atom in use, FALSE otherwise
    ///
    inline  const bool& isSelected()const {return mSelected;}

    ///
    /// \brief Return the MOL2 Type of the atom
    /// \return MOL2 Type of the atom
    ///
    inline  const std::string& getMOL2() const {return mMOL2type;}

    ///
    /// \brief Return the list of physicochemical properties of the atom
    /// \return List of properties as a constant object
    ///
    inline  const  PhysProp& getProperty() const {return mProperties;}

    ///
    /// \brief Return the list of physicochemical properties of the atom
    /// \return List of properties
    ///
    inline PhysProp& prop(){return mProperties;}
    inline const PhysProp& prop()const {return mProperties;}


    ///
    /// \brief Return the van der Waals radius (in Angstroems)
    /// \return van der Waals radius
    ///
    /// Need the atomic type set up
    ////
    const double& getvdWRadius()const ;

    ///
    /// \brief give the Weight (g/mol) of this atom
    /// \return the weight of the atom
    /// Need the atomic type set up
    ////
    const double& getWeigth() const ;

    ///
    /// \brief return the full atomic name of the atom (Carbon, Nitrogen, Oxygen)
    /// \return full atomic name
    ///
    /// Need the atomic type set up
    ////
    const std::string& getAtomicName() const ;


    ///
    /// \brief Return the element symbol for this atom (i.e. C,N,O,H,He ...)
    /// \return element symbol
    ///
    std::string getElement() const;

    ///
    /// \brief Return the atomic number of the atom
    /// \return the atomic number
    ///
    /// Return the atomic number property of the atom. A value defined by the
    /// constant DUMMY_ATM is returned for wildcard atoms and dummy atoms.
    /// An atomic number is specified through setMMAtomicType() function.
    ////
    const unsigned char& getAtomicNum() const {return mAtomicNumber;}

    ///
    /// \brief Tell whether this atom is an Hydrogen
    /// \return TRUE when atom is an hydrogen
    ///
    /// Check if the atomic number of the atom equals 1
    ///
    ////

    inline bool isHydrogen()const { return (mAtomicNumber==1);}

    ///
    /// \brief Tell whether this atom is a Carbon
    /// \return TRUE when atom is a carbon
    ///
    /// Check if the atomic number of the atom equals 6
    ///
    ////
    inline bool isCarbon() const { return (mAtomicNumber==6);}

    ///
    /// \brief Tell whether this atom is a Oxygen
    /// \return TRUE when atom is a Oxygen
    ///
    /// Check if the atomic number of the atom equals 8
    ///
    ////
    inline bool isOxygen() const { return (mAtomicNumber==8);}

    ///
    /// \brief Tell whether this atom is a Nitrogen
    /// \return TRUE when atom is a Nitrogen
    ///
    /// Check if the atomic number of the atom equals 7
    ///
    inline bool isNitrogen() const {return (mAtomicNumber==7);}


    ///
    /// \brief Tell whether this atom is a Metallic atom
    /// \return TRUE when atom is a metallic atom
    ///
    /// Check if the atomic number of the atom is one of the following :
    /// Magnesium (12), Mangenese (25), Iron (26), Nickel (28), Copper (29), Zinc (30)
    ///
    ///
    ////
    bool isMetallic() const;

    ///
    /// \brief Tell whether this atom is an Halogen
    /// \return  TRUE when the atom is an halogen atom
    ///
    /// Check if the atomic number of the atom is one of the following :
    /// Fluorine, Chlorine, Bromine, Iodine
    ///
    ////
    bool  isHalogen()const;


    bool isIon()const;


    bool isBioRelevant() const;


    /**
     * @brief set the atomic type of the atom by giving the atomic name
     * @param atomicName Aomic name (C, O, N ...) of the atom
     * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     */
    void setAtomicType(const std::string& atomicName) throw(ProtExcept);

    inline double dist(const MMAtom& atm)const{return pos().distance(atm.pos());}
    inline double angle(const MMAtom& atm1, const MMAtom& atm2)const {
        return mPosition.angle_between(atm1.pos(),atm2.pos());
    }

    ///
    /// \brief Set the use of the atom
    /// \param isSelected : New status for the atom
    /// \param applyRes : Update the MMResidue use
    /// \param applyToBond : Apply isUsed to the atom bonds
    /// \throw 350501 when pos is above the number of bonds
    ///
    /// Molecular object has the possibility to ignore some atom, bond, MMResidue
    /// or even chain for the sake of computational time and/or output. To do so,
    /// user has the possibility to "tag" an atom, a bond, a MMResidue, a chain and
    /// update other object if necessary. For example, turning off an atom will
    /// by default turn off all of it's bond. But turning off a bond will only
    /// turn off the bond. Turning off a MMResidue turns off all atoms and bond
    /// and can turn off the chain only if its chain has no long MMResidue in use.
    ///
    void select(const bool& isSelected,
                const bool& applyRes=true,
                const bool& applyToBond=true);

    void serialize(std::ofstream& out) const;
    void unserialize(std::ifstream& ifs);
    std::string toString(const bool& onlyUsed=false, const bool &withBond=true)const;
    MacroMole& getMolecule()const {return getParent();}


    ///
    /// \brief Return the reference of the pos th bond in the list of bond of this atom
    /// \param pos : position in the bond list
    /// \return Reference of the pos th bond in the list of bond
    /// \throw 310201  position is above the number of bond for this atom
    /// \throw 071001 when pos is above the number of bonds
    /// \test testAddBond
    ///
    /// Return the reference of a bond object that connecting the MMAtom to another
    /// atom. The reference is defined by the pos in the method argument. The
    /// latter correspond to the position of the bond in the list of bond of this
    /// atom. Please note that position in C++ start at 0 in an array.
    /// When the position given in the method argument is above the number of
    /// bond defined for this atom, then a ProtExcept exception is thrown.
    ///
    ///
    MMBond& getBond(const size_t&  pos) const throw(ProtExcept);

    ///
    /// \brief Return the bond existing between this atom and the given one
    /// \param atom : atom to consider having a bond with this atom
    /// \return  the bond existing between this atom and the given one
    /// \throw 310301 No bond found between the two atoms
    /// \throw 071001 when pos is above the number of bonds
    /// \test testAddBond
    ///
    /// Return the reference of a Bond object that connecting the MMAtom to the atom
    /// specified in the method argument. When the atom in the method argument
    /// is not connected to this atom, then a ProtExcept exception is thrown.
    ///
    MMBond& getBond(const MMAtom& atom)const throw(ProtExcept);

    ///
    /// \brief Get the type of bond made between this atom and the given atom
    /// \param atom Atom to check the bond type with
    /// \return Bond type as defined in Bond::Type enumeration
    /// \throw 310401 when pos is above the number of bonds
    /// \test testAddBond
    ///
    const uint16_t &getBondType(const MMAtom& atom) const throw(ProtExcept);

    ///
    /// \brief Give the number of bonds for this atom
    /// \return number of bonds for this atom
    /// \test testAddBond
    /// Return the total number of neighbor atoms bonded to an atom, or
    /// equivalently the total number of bonds connected to an atom. This value
    /// only consider explicit atoms, whether or not there are in use.
    ///
    ///
    size_t numBonds() const {return mListLinks.size();}


    ///
    /// \brief hasBondWith Tells whether the given atom shares a bond with this atom
    /// \param atom Atom to check
    /// \return TRUE when this atom and the given atom shares a bond, FALSE otherwise
    /// \test testAddBond
    /// Scan the list of atoms linked by a bond and seach for a match with the given atom
    ///
    bool hasBondWith(const MMAtom& atom)const;

    std::string getIdentifier() const;


    ///
    /// \brief Return the posth linked atom
    /// \param pos : position in the bond list
    /// \return the reference of the correpsonding atom
    /// \throw 310501 pos is above the number of bonds for this atom
    /// \test testAddBond
    ///
    /// Each atom stores both their bonds and the atoms there are linked to.
    /// One way to scan it is to specify the position in the array of linked
    /// atom to retrieve the corresponding linked atom. In the case where the
    /// given position is above the number of bonds/linked atoms, an exception
    /// is thrown
    ///
    MMAtom& getAtom(const size_t &pos) const throw(ProtExcept);

    /**
     * \brief Return an atom bonded to this atom that is not the given atom
     * \param atom : Atom to avoid returning
     * \return Atom that is different to the given atom in param and linked to this atom
     * \throw 310601 MMAtom::getAtomNotAtom No alternative atom found
     */
    MMAtom& getAtomNotAtom(const MMAtom& atom)const throw(ProtExcept);

    /**
     * @brief getResidue associated with this atom
     * @return Associated residue
     * @throw 310701 No residue defined for this atom
     */
    MMResidue& getResidue()const throw(ProtExcept);

    inline void setName(const std::string& name){mName=name;}

    /**
     * @brief setMOL2Type
     * @param mol2type
     * @param wAtomicNum
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    void setMOL2Type(const std::string& mol2type, const bool &wAtomicNum=true) throw(ProtExcept);

    void setAtomicType(const unsigned char &atomicNum) throw(ProtExcept);

    /**
     * @brief getResName
     * @return
     * @throw 310701 No residue defined for this atom
     */
    inline const std::string& getResName()const{return getResidue().getName();}
};

}


#endif // MMATOM_H

