#ifndef CHAIN_H
#define CHAIN_H
#undef NDEBUG
#include <assert.h>
#include <cstdint>
#include "headers/math/coords.h"
#include "headers/statics/protExcept.h"
namespace protspace
{
class MacroMole;
class MMResidue;
/**
 * @brief The MMChain class
 */
class MMChain
{
    friend class MacroMole;

    /**
     * @brief parent molecule owning the chain
     */
    MacroMole* mMolecule;

    /**
     * @brief name of the chain. Generally one letter: A,B,C,D, but wwPDB allows up to 4
     */
    std::string mName;

    /**
     * @brief List of MMResidues managed by the chain
     */
    std::vector<MMResidue*> mResiduelist;

    /**
     * @brief is this chain used
     */
    bool mSelected;

    uint16_t mChainType;

    /**
     * @brief MMChain constructor
     * @param molecule : reference of the parent molecule
     * @param name : Chain name (1 letter code)
     *
     * Create a new Chain object
     *
     * Please note that this is a private function that can only be called by
     * Molecule::addChain
     */
    MMChain(MacroMole& molecule,const std::string& name);
    /**
     * @brief MMChain constructor
     * @param molecule : pointer to the parent molecule
     * @param name : Chain name (1 letter code)
     *
     * Create a new Chain object
     *
     * Please note that this is a private function that can only be called by
     * Molecule::addChain
     */
    MMChain(MacroMole* const molecule,const std::string& name);


    /**
     * @brief Add the given Residue to this chain
     * @param Residue: Residue to add.
     * @throw 330101 when given Residue is NULL
     * @throw 330102 Given residue not part of this chain
     */
    void addResidue(MMResidue* const Residue) throw(ProtExcept);

    /**
     * @brief Add the given Residue to this chain
     * @param Residue: Residue to add
     * @throw 330201 Given residue not part of this chain
     */
    void addResidue(MMResidue&       Residue) ;
    /**
     * @brief MMChain::delResidue: Remove the given Residue from the list ofIResidues
     * @param MMResidue : Residue to remove
     * @throw 330201 : No Residue given
     * @throw 330202 : Given Residue is not part of this chain
     * @throw 330203 : Given Residue is not part of this chain
     * Note that this does not delete the Residue, but remove it from the list of
     * Residuees for this chain.
     * @warning : After this function, thi call of Residue->getChain() will
     * cause an error
     */
    void delResidue(MMResidue* const Residue) throw(ProtExcept);


    /**
     * @brief MMChain::delResidue: Remove the given Residue from the list of Residues
     * @param residue : Residue to remove
     * @throw 330401 : No Residue given
     * @throw 330402 : Given Residue is not part of this chain
     * Note that this does not delete the MMResidue, but remove it from the list of
     * Residues for this chain.
     * @warning : After this function, thi call of MMResidue->getChain() will
     * cause an error
     */
    void delResidue(MMResidue&       residue) throw(ProtExcept);
public:

    /**
     * @brief Gives the name of the chain
     * @return the chain name
     */
    inline const std::string& getName() const {return mName;}
    /**
     * @brief Return the molecule name associated to this chain
     * @return the molecule name
     */
    const std::string& getMoleName() const ;

    /**
     * @brief MMChain::getResidue : return the pos th Residue of this chain
     * @param pos: position in the Residue array of this chain
     * @return The corresponding Residue
     * @throw 330501 : Given position is above the number of MMResidue
     */
    MMResidue& getResidue(const size_t& pos) throw(ProtExcept);



    /**
     * @brief MMChain::getResidue : return the pos th Residue of this chain
     * @param pos: position in the Residue array of this chain
     * @return The corresponding Residue
     * @throw 330601 : Given position is above the number of MMResidue
     */
    const MMResidue& getResidue(const size_t& pos)const throw(ProtExcept);


    /**
     * @brief Get the residue corresponding to the given name and number
     * @param name Name of the residue (can be 1 or 3 Letter code)
     * @param num Id of the residue as given by the file
     * @param name_1_letter TRUE when the given name is a one letter code
     * @throw 330801 No Residue found with the given parameters
     * @throw 325001 input should be 1 letter
     * @throw 325002 No name found
     * @return Corresponding residue
     */
    MMResidue& getResidue(const std::string& name,
                          const int &num=-1,
                          const bool& name_1_letter=false) throw(ProtExcept) ;


    /**
     * @brief Get the residue corresponding to the given name and number
     * @param name Name of the residue (can be 1 or 3 Letter code)
     * @param num Id of the residue as given by the file
     * @param name_1_letter TRUE when the given name is a one letter code
     * @throw 330901 MMChain::getResidue    No Residue found with the given parameters
     * @throw 325001 ResidueUtils::residue1Lto3L   input should be 1 letter
     * @throw 325002 ResidueUtils::residue1Lto3L    No name found
     * @return Corresponding residue
     */
    const MMResidue& getResidue(const std::string& name,
                          const int &num=-1,
                          const bool& name_1_letter=false) const throw(ProtExcept) ;
    /**
     * @brief Gives the number ofResidues included in this chain
     * @return count of Residues
     */
    inline size_t numResidue() const {return mResiduelist.size();}

    /**
     * @brief Gives the number of Residues included in this chain that are in use
     * @return count of Residues
     */
    size_t numUsedResidue() const;

    /**
     * @brief Check all MMResiduees to see if one is used or not.
     */
    void checkSelection();

    /**
     * @brief Tell whether the chain is used or not
     * @return true when the chain is used. false otherwise
     */
    inline const bool& isSelected() const {return mSelected;}


    ///
    /// \brief Give the type of chain (See namespace CHAINTYPE)
    /// \return Type of chain
    ///
    inline const uint16_t& getType()const{return mChainType;}


    ///
    /// \brief Set the type of chain (See namespace CHAINTYPE)
    /// \param pType Type of chain
    ///
    inline void setType(const uint16_t& pType) {mChainType=pType;}


    /**
     * @brief Set the use of the chain
     * @param isUsed : New status for the chain
     * @param applyToRes : Apply isUsed to the Residues of this chain
     *
     *
     * Molecular object has the possibility to ignore some atom, bond, Residue
     * or even chain for the sake of computational time and/or output. To do so,
     * user has the possibility to "tag" an atom, a bond, a Residue, a chain and
     * update other object if necessary. For example, turning off an atom will
     * by default turn off all of it's bond. But turning off a bond will only
     * turn off the bond. Turning off a Residue turns off all atoms and bond
     * and can turn off the chain only if its chain has no long Residue in use.
     */
    void setSelection(const bool& isSelected,
                const bool& applyToRes=true);


    ///
    /// \brief Search a residue in this chain by its ID defined in the file
    /// \param pos Position of the residue to search
    /// \return the corresponding residue
    ///  \throw  330701  Given position  has not been found
    ///
    MMResidue& getResidueByFID(const int &pos) const throw(ProtExcept);


   inline   MacroMole& getMolecule()const {assert(mMolecule!=NULL);return *mMolecule;}


     size_t getResiduePos(const MMResidue& res)const;

     /**
      * @brief getResiduePos
      * @param pName
      * @param num
      * @param name_1_letter
      * @return
      * @throw 325001 input should be 1 letter
      * @throw 325002 No name found
      */
     size_t getResiduePos(const std::string& pName, const int &num=-1,
                          const bool& name_1_letter=false) const;

     std::string toString()const;

     void select(const bool& isUsed,
                 const bool& applyToRes=true);
     size_t numSelectedResidue() const;
     void serialize(std::ofstream& ofs)const;
     void unserialize(std::ifstream& ifs);


};
}
#endif // CHAIN_H
