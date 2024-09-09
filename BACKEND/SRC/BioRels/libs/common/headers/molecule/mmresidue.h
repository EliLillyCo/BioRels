#ifndef RESIDUE_H
#define RESIDUE_H

#include <cstdint>
#include "headers/statics/protExcept.h"
#include "headers/math/coords.h"
#include "headers/statics/group.h"
#include "headers/statics/dot.h"

//#include "headers/molecule/mmbond.h"
namespace protspace
{

class MMChain;
class MMBond;
class MMAtom;
class MacroMole;
///
 /// \brief The MMResidue class
 ///
class MMResidue:public ids
{


public:
    friend class MacroMole;
    friend class Group<MMAtom,MMBond,MacroMole>;
    friend class MMChain;
private:
    ///
     /// \brief 3 Letter HET code
     ///
    std::string mName;

    ///
     /// \brief String describing the MMResidue, its number and chain name
     ///
    std::string mIdentifier;


    ///
     /// \brief list of atoms managed by this MMResidue
     ///
    std::vector<MMAtom*> mAtomlist;


    ///
     /// \brief parent chain
     ///
    signed char mChain;

    ///
    /// \brief Type of MMResidue
    ///
    uint16_t mResType;

    ///
    /// \brief Parent MacroMole
    ///
    MacroMole* mParent;


    ///
     /// \brief is this MMResidue used
     ///
    bool mSelected;

    ///
     /// \brief MMResidue constructor
     /// \param chain : Reference of it's chain
     /// \param name : 3 Letters code for this MMResidue
     /// \param fid : Id of the MMResidue as given in the file
     /// \note Private constructor. Can only be called from molecule
     ///
    MMResidue(MacroMole* const parent,
              const signed char &chain,
              const std::string& name,
              const int& fid);

    ///
     /// \brief Set a chain to this Residue
     /// \param chain Address of the new chain to set to
     /// \warning : Does not remove the Residue from the former chain
     ///
    inline void setChain(const signed char& chain) {mChain=chain;}

    ///
     /// \brief Add an atom to the Residue atom list
     /// \param atom atom to add to the Residue list
     /// \throw 320101 No atom given
     ///
    void addAtom(MMAtom* const atom) throw(ProtExcept);

    ///
     /// \brief Add an atom to the Residue atom list
     /// \param atom atom to add to the Residue list
     ///
    void addAtom(MMAtom& atom)      ;

    ///
     /// \brief Delete the given atom from the MMResidue atom list
     /// \param atom atom to remove from the MMResidue atom list
     /// \warning deleting the atom from the MMResidue list does not mean
     /// deleting the atom
     /// \throw 320201  No atom given
     /// \throw 320202  Given atom is not part of this Residue
     /// \throw 320203  Given atom is not part of this Residue
     ///
    void delAtom(MMAtom* const atom) throw(ProtExcept);



    ///
     /// \brief Delete the given atom from the Residue atom list
     /// \param atom atom to remove from the Residue atom list
     /// \warning deleting the atom from the Residue list does not mean
     /// deleting the atom
     /// \throw  320301  Given atom is not part of this Residue
     /// \throw  320302  Given atom is not part of this Residue
     ///
    void delAtom(MMAtom& atom)       throw(ProtExcept);


    void genIdentifier();

    MMResidue(MacroMole* const parent);


    /**
     * @brief clearAtoms
     * @warning this function does not set to null the residue pointer of its atom
     */
    void clearAtoms();

protected:
   virtual ~MMResidue();
public:

    ///
     /// \brief Call Molecule::getName()
     /// \return the molecule name related to this MMResidue
     ///
    const std::string& getMoleName() const ;

    ///
     /// \brief gives the name of the MMResidue
     /// \return the MMResidue name (3 letters code)
     ///
    inline  const std::string& getName() const {return mName;}

    ///
     /// \brief gives the chain name (1 letter)
     /// \return the chain name
     ///
    const std::string& getChainName() const;

    ///
     /// \brief gives the parent chain
     /// \return the reference of the parent chain
     ///
     MMChain& getChain();


    const MMChain& getChain() const;

    ///
     /// \brief gives the atom at the pos position in the MMResidue atom list
     /// \param pos position of the atom in the MMResidue atom list
     /// \return a reference of the atom
     /// \throw 320401 Given position is above the number of atom in this Residue
     ///
    MMAtom& getAtom(const size_t& pos)const        throw(ProtExcept);

    ///
     /// \brief gives the number of atoms in this MMResidue
     /// \return number of atoms in this MMResidue
     /// The returned number include hydrogens
     ///
    inline size_t numAtoms() const {return mAtomlist.size();}



    size_t numHeavyAtom() const;

    /**
     *
     *  \brief Gives the MMResidue type (AA, LIGAND, SUGAR ...)
     *  \return residue type
     */
    const uint16_t& getResType() const {return mResType;}

    ///
     /// \brief Small description of the MMResidue
     /// \return   String describing the MMResidue, its number and chain name
     ///
    const std::string &getIdentifier() const;

    ///
     /// \brief Update used by checking every atom use.
     ///
    void checkUse();

    ///
     /// \brief tell whether this MMResidue is used or not
     /// \return true when the MMResidue is used. false otherwise
     ///
    inline const bool& isSelected() const {return mSelected;}

    ///
     /// \brief Set the use of the MMResidue
     /// \param isUsed : New status for the MMResidue
     /// \param applyToChain : Update the chain use
     /// \param applyToAtom : Apply isUsed to the atoms of this MMResidue
     /// Molecular object has the possibility to ignore some atom, bond, MMResidue
     /// or even chain for the sake of computational time and/or output. To do so,
     /// user has the possibility to "tag" an atom, a bond, a MMResidue, a chain and
     /// update other object if necessary. For example, turning off an atom will
     /// by default turn off all of it's bond. But turning off a bond will only
     /// turn off the bond. Turning off a MMResidue turns off all atoms and bond
     /// and can turn off the chain only if its chain has no long MMResidue in use.
     ///
    void select(const bool& isSelected,
                const bool& applyToChain=true,
                const bool& applyToAtom=true);

    ///
     /// \brief Return the weight of the amino acid
     /// \return Weight (g/mol) of the amino acid
     /// \todo When mmcif parsed, modify it to get the value of ALL residues
     ///
    double getWeight() const;


    ///
     /// \brief Assign a new name to this residue
     /// \param name New name to assign to this residue
     /// \warning This does not change the type of the residue
     ///
    void setName(const std::string& name);

    ///
     /// \brief String describing the residue
     /// \return String describing the residue
     ///
    std::string toString(const bool &wBond=false)const;

    ///
     /// \brief Get the atom of this residue having the given atom name
     /// \param atomName Name of the atom (CA, CB, OD1...)
     /// \return The corresponding atom
     /// \throw 320501      MMResidue::getAtom    Atom Not found
     /// \throw 320502      MMResidue::getAtom    Atom Not found
     /// This function scan each atom of the residue and look if their name
     /// match the given atom name. If so, it returns this atom. If no
     /// atom has been found with this name, then the function throw an exception
     ///
    MMAtom& getAtom(const std::string& atomName, bool testLower=false)const  throw(ProtExcept);

    bool getAtom(const std::string& pAtomName,size_t& pos)const;

    ///
     /// \brief Assign a residue type for this residue
     /// \param value Type to assign
     /// Residue type are a very important part of a residue. Iany tools,
     /// including non-bonded interaction, cavities, or just PDB Processing
     /// involves different procedures depending on the type of the residue
     ///
    inline void setResidueType(const uint16_t& value) {mResType=value;}






    bool hasAtom(const std::string& pName, size_t &atom)const;


    void serialize(std::ofstream &out) const;
    void unserialize(std::ifstream &in);

    inline const std::vector<MMAtom*>& getAllAtoms()const {return mAtomlist;}

    inline MacroMole& getParent()const{return *mParent;}
    inline const signed char& getChainPos()const {return mChain;}
};
}
#endif // MMResidue_H
