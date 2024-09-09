#ifndef MMRING_H
#define MMRING_H

#include <vector>
#include <string>
#include "headers/molecule/mmatom.h"
#include"headers/statics/grouplist.h"
namespace protspace
{

class MMAtom;

class MMRing
{
    friend class MacroMole;
private:

    /**
     * @brief List of atoms defining this ring
     */
    GroupList<MMAtom> mAtomlist;

    /**
     * @brief TRUE when the ring is aromatic
     */
    bool mIsAromatic;


    MMAtom& mCenter;

    MMResidue& mResidue;

public:

    /**
     * @brief Standard constructor
     * @param list_atoms List of the atoms that defines this ring
     * @param isAromatic Is this ring aromatic
     */
    MMRing(const std::vector<MMAtom*>& list_atoms,
           MMAtom& center,
           const bool& isAromatic=false);


    /**
     * @brief Add an atom to this ring.
     * @param atom Atom to add
     *
     * adding an atom to the ring automatically force the ring center to be updated
     *
     */
    void addAtom(MMAtom& atom);


    /**
     * @brief Add an atom to this ring.
     * @param atom Atom to add
     *
     * adding an atom to the ring automatically force the ring center to be updated
     *
     */
    void addAtom(MMAtom* const atom);

    /**
     * @brief Return an atom that is part of this ring
     * @param pos Position in the list of atom of this ring
     * @return  Atom corresponding to pos position in the atom list
     *
     * Value must be between 0 and the number of atom in this ring (not included)
     *
     */
    MMAtom& getAtom(const size_t& pos) const {return mAtomlist.get(pos);}

    /**
     * @brief Tell whether the atom given in parameter is in this ring
     * @param atom Atom to check
     * @return True when the given atom is in the ring
     *
     * Scan all atoms in this ring and look if one of them as the same memory
     * address as the atom given in parameter.
     *
     */
    bool isInRing(const MMAtom& atom)const ;


    /**
     * @brief Gives the number of atoms in this ring
     * @return Number of atoms
     */
    size_t numAtoms() const {return mAtomlist.size();}

    /**
     * @brief Describe this ring by listing all atoms
     * @return Description of the ring
     */
    std::string toString() const;

    /**
     * @brief Tell whether or not this ring is an aromatic ring
     * @return True when the ring is aromatic
     *
     * Aromaticity is based on Ian aromatic perception
     *
     */
    const bool& isAromatic() const {return mIsAromatic;}

    /**
     * @brief gives the geometric center of the ring
     * @return Coordinates of the geometric center
     *
     * The geometric center is the coordinate average
     * of all heavy atoms involved in this ring.
     *
     */
    const Coords& getCenter() const{return mCenter.pos();}


    /**
     * @brief Return the pseudo atom representing the center of the ring
     * @return Pseudo atom reference
     */
    MMAtom& getAtomCenter() const{return mCenter;}


    /**
     * @brief Set the aromaticity of the ring
     * @param isArom : True when the ring is aromatic false otherwise
     */
    void setIsAromatic(const bool& isArom){mIsAromatic=isArom;}





    /**
     * @brief Return the residue in which this ring is
     * @return Involved residue
     */
    MMResidue& getResidue() const {return mResidue;}

    void setUse(const bool& used);
    bool isSelected()const;

    void fillList(std::vector<MMAtom*>& list)const;
    void fillList(std::vector<size_t>& list)const;
};

}

#endif // MMRING_H

