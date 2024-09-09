#ifndef MACROTOIW_H
#define MACROTOIW_H


#include "molecule.h"
#include "headers/molecule/macromole.h"
class MacroToIW
{
protected:
    ///
    /// \brief Molecular object used to represent the mMacro
    ///
    Molecule mIWMole;


    ///
    /// \brief Original molecule
    ///
    protspace::MacroMole& mMacro;


    ///
    /// \brief Only consider selected atoms/bonds in the conversion MacroMole to Molecule
    ///
    bool mOnlyUsed;

    ///
    /// \brief Convert all atoms to carbon and all bonds to single
    ///
    bool mToGraph;

    bool mWArom;

    ///
    /// \brief list of aromatic atoms
    ///
    int *arAtoms;

    ///
    /// \brief list of aromatic bonds
    ///
    int *arBonds;


    ///
    /// \brief Mapping between atom in macromole and position in molecule
    ///
    std::map<protspace::MMAtom*,size_t> positions;

    ///
    /// \brief Mapping between atom in molecule and atom in macromole
    ///
    std::map<const Atom*,protspace::MMAtom*> mAtom_mapping;
    void convertAtoms(const std::map<protspace::MMAtom *, int> &isotopes);
    void convertBonds();


public:
    ~MacroToIW();
    ///
    /// \brief Standard constructore
    /// \param mole Molecule to be considered
    /// \param onlyUsed Only convert atoms/bonds that are selected
    /// \param toGraph Convert all heavy atoms to carbon and all bonds to single bonds
    ///
    MacroToIW(protspace::MacroMole& mole,
              const bool &onlyUsed=false,
              const bool &toGraph=false,
              const std::map<protspace::MMAtom *, int> &isotopes=std::map<protspace::MMAtom *, int> (),
              const bool &wAromaticity=true);

    ///
    /// \brief Get the canonical smiles
    /// \return Return the canonical smiles
    ///
    std::string getUniqueSMILES();

    ///
    /// \brief Return the SMILES of the molecule.
    /// \return SMILES string
    ///
    std::string getSMILES();

    ///
    /// \brief Perceive all the rings of the molecule
    ///
    /// \throw 350102 MacroMole::addAtom Bad allocation
    /// \throw 352301    MacroMole::addRingSystem  No atom in the ring
    /// \throw 352302    MacroMole::addRingSystem    Bad allocation
    void generateRings(const bool &cleanRing=true);

    Molecule& getIWMole(){return mIWMole;}
    const Molecule& getIWMole()const{return mIWMole;}
    int standardize();
    protspace::MMAtom& getMMAtomFromAtomPos(const size_t &pos);
};


#endif // MACROTOIW_H
