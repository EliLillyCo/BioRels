#ifndef IWICF_H
#define IWICF_H

#include "molecule.h"
#include "headers/molecule/macromole.h"

class IWtoMacro
{
protected:
    Molecule mIWMole;
    protspace::MacroMole* mMacro;
    size_t mNRing;
    ///
    /// \brief createAtoms
    /// \param pRes
    /// \throw 350102 MacroMole::addAtom      Bad allocation
    /// \throw 310101 MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    /// \throw 310102 MMAtom::setAtomicName   Atomic name not found
    ///
    void createAtoms(protspace::MMResidue &pRes);
    ///
    /// \brief createBonds
    /// \throw 030303 Bad allocation
    void createBonds();
     std::map<protspace::MMAtom*,const Atom*>  atom_mapping_to;
    std::map<const Atom*, protspace::MMAtom*> atom_mapping;
    std::map<int,protspace::MMAtom*>mapping;
    void perceiveArom();
public:
    /**
     * @brief IWtoMacro
     * @param SMILES
     * @param name
     * @throw 250101   IWtoMacro                   Unsucessfull creation of SMILES
     */
    IWtoMacro(const std::string& SMILES, const std::string &name);
    void setMacroMole(protspace::MacroMole& mole);

    ///
    /// \brief toMacroMole
    /// \param wAromatic
    /// \param pRes
    /// \return
    /// \throw 350102 MacroMole::addAtom Bad allocation
    /// \throw 352301    MacroMole::addRingSystem  No atom in the ring
    /// \throw 352302    MacroMole::addRingSystem    Bad allocation
    /// \throw 030303 Bad allocation
    /// \throw 310101 MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    /// \throw 310102 MMAtom::setAtomicName   Atomic name not found
    ///
    ///
    bool toMacroMole(const bool& wAromatic, protspace::MMResidue &pRes);

    ///
    /// \brief generateRings
    /// \throw 350102 MacroMole::addAtom Bad allocation
    /// \throw 352301    MacroMole::addRingSystem  No atom in the ring
    /// \throw 352302    MacroMole::addRingSystem    Bad allocation
    ///
    void generateRings();

    ///
    /// \brief Get the canonical smiles
    /// \return Return the canonical smiles
    ///
    std::string getUniqueSMILES();
};
namespace protspace
{

/**
 * @brief SMILEStoMacroMole
 * @param SMILES
 * @param name
 * @param mole
 * @param perceiveRing
 * @throw 250101    IWtoMacro   Unsucessfull creation of SMILES
 * @throw 350102 MacroMole::addAtom Bad allocation
 * @throw 352301    MacroMole::addRingSystem  No atom in the ring
 * @throw 352302    MacroMole::addRingSystem    Bad allocation
 */
void SMILEStoMacroMole(const std::string& SMILES,
                       const std::string& name,
                       MacroMole& mole, const bool &perceiveRing=false);
}

#endif // IWICF_H
