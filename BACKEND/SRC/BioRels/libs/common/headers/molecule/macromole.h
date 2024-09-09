#ifndef MACROMOLE_H
#define MACROMOLE_H
#include <cstdint>
#include "headers/statics/group.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/mmchain.h"
#include "headers/molecule/mmring.h"
#include "headers/statics/errors.h"

namespace protspace
{


class MacroMole:public Group<MMAtom,MMBond,MacroMole>, public ids
{
protected:
    ///
    /// \brief Name of the molecule
    ///
    std::string mName;

    ///
    /// \brief Dummy atom used for copy
    ///
    const MMAtom mCreateAtom;

    ///
    /// \brief mChain
    ///
    MMChain mTempChain;



    ///
    /// \brief Temporary residue used when atoms are not assigned to a residue
    ///
    MMResidue mTempResidue;



    std::vector<MMResidue*> mListResidues;


    std::vector<MMChain*> mListChain;


    std::vector<ErrorAtom> mListErrorAtom;
    std::vector<ErrorBond> mListErrorBond;
    std::vector<ErrorResidue> mListErrorResidue;

    std::vector<MMRing*> mListRing;

    bool mIsResidueNumberOk;

    /**
     * @brief removeChainFromList
     * @param pChain
     * @return
     * @throw 351201   MacroMole::removeChainFromList         Given chain not found in molecule
     */
    size_t removeChainFromList(MMChain& pChain) throw(ProtExcept);

    uint16_t mMoleType;

    void updateResidueMID();

    void renumBonds(const size_t &starter);

public:
    const std::vector<MMAtom*>& getAtoms()const{return mListDot;}
    ///
    /// \brief Standard constructor
    /// \param name Name of the molecule
    /// \param owner Ownership of its atom,bond,residues
    ///
    MacroMole(const std::string &name="",
              const bool& owner=true);


   virtual ~MacroMole();

    ///
    /// \brief Returns the name of the molecule
    /// \return name of the molecule
    ///
    const std::string& getName() const { return mName;}


    ///
    /// \brief Set the name of the molecule
    /// \param New name of the molecule to set to
    ///
    void setName(const std::string& name) { mName=name;}


    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////////  ATOMS ////////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////


    ////
    /// \brief Add a new empty atom in the molecule
    /// \return Newly created atom
    /// \test TestAddAtom
    /// \throw 350101 This molecule is an alias. Cannot create atom on an alias
    /// \throw 350102 Bad allocation
    ///
    MMAtom& addAtom(const int &pResId=-1)throw(ProtExcept);


    ///
    /// \brief Add an atom that is assigned to the given residue
    /// \param residue Residue to assign the atom to
    /// \return newly created atom
    /// \throw 350101 This molecule is an alias. Cannot create atom on an alias
    /// \throw 350102 Bad allocation
    /// \throw 250201 Given residue is not part of this molecule
    ///
    MMAtom& addAtom(MMResidue& residue)throw(ProtExcept);


    ///
    /// \brief Add an atom with additional data
    /// \param residue Residue to assign the atom to
    /// \param coord Coordinates of the atom
    /// \param atomName Atom name (Optional)
    /// \param MOL2Name MOL2 Type (Optional)
    /// \param mElement Atomic Element (Optional)
    /// \return Newly created atom
    /// \throw 350101 MacroMole::addAtom        This molecule is an alias. Cannot create atom on an alias
    /// \throw 350102 MacroMole::addAtom      Bad allocation
    /// \throw 310802 MMAtom::setMol2Type     Unrecognized MOL2 Type
    /// \throw 310101 MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    /// \throw 310102 MMAtom::setAtomicName   Atomic name not found
    /// \throw 350301 Given residue is not in this molecule
    /// \throw 350302 Associated element to MOL2 is different than given element
    MMAtom& addAtom(MMResidue &residue,const Coords& coord,
                    const std::string& atomName="",
                    const std::string& MOL2Name="",
                    const std::string& mElement="");


    ///
    /// \brief Get the number of atoms in the molecule
    /// \return number of atoms
    /// \test TestAddAtom
    ///
    /// This function will give the total number of atoms without distinction
    /// of use or element types
    ///
    size_t numAtoms() const {return mListDot.size();}

    ///
    /// \brief get the pos ith atom in the atom list
    /// \param  pos : position in the atom list
    /// \return the atom at the ith position
    /// \throw 030401 Given position is above the number of dots
    /// \test TestAddAtom
    ///
    MMAtom& getAtom(const size_t& pos) throw(ProtExcept);

    ///
    /// \brief get the pos ith atom in the atom list
    /// \param  pos : position in the atom list
    /// \return the atom at the ith position
    /// \throw 030401 MacroMole::getAtom Given position is above the number of dots
    /// \test TestAddAtom
    ///
    const MMAtom& getAtom(const size_t& pos) const throw(ProtExcept);


    ///
    /// \brief get atom by looking at its ID in the original file
    /// \param FID Given ID to be considered
    /// \return Atom with this associated FID
    /// \throw 350401 - Given ID not found
    ///
    MMAtom& getAtomByFID(const int &FID) const throw(ProtExcept);

    /**
     * @brief Delete the given atom from this molecule
     * @param atom Atom to delete
     * @note The given atom must be part of this molecule
    * @throw 350801 MacroMole::delAtom  Atom not found in molecule
     * When delAtom is call to delete a given atom, it will also delete
     * its bonds, its residue if the residue is empty, its chain if the
     * chain has no residue, and any ring it can be in
     *
     *
     */
    void delAtom(MMAtom& atom, const bool &noring=false) throw(ProtExcept);



    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////////  BONDS ////////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////

    ///
    /// \brief Return the bond found at position pos
    /// \param pos position of the bond in the bond list
    /// \return the bond at the pos position in the bond list
    /// \throw 350501 when pos is above the number of bonds
    ///
    ///  Bonds are stored in the molecule as an array of bonds.
    ///  Therefore giving a position will return the corresponding bond
    ///  in the bond array. When the position is above the number of bonds -1
    ///  (array start at 0), the function will throw an exception
    ///
    MMBond& getBond(const size_t& pos) const throw(ProtExcept);

    ///
    /// \brief Add a new bond for this molecule
    /// \param atom1 : First atom involved in the bond
    /// \param atom2 : Second atom involved in the bond
    /// \param bondType : Type of bond (Single, double)
    /// \param fid id of the bond as given by the input file
    /// \return the newly created bond
    /// \throw 350601   MacroMole::addBond          This molecule is an alias. Cannot create residue on an alias
    /// \throw 350602   MacroMole::addBond          Atom not part of this molecule
    /// \throw 350603   MacroMole::addBond          Atom not part of this molecule
    /// \throw 350604   MacroMole::addBond          Both atoms are the same
    /// \throw 030303   Group::CreateLink           Bad allocation
    ///
    MMBond& addBond(MMAtom& atom1,
                    MMAtom& atom2,
                    const uint16_t &bondType,
                    const int& fid=0) throw(ProtExcept);





    inline size_t numBonds() const {return mListLinks.size();}

    /**
     * @brief Delete the given bond
     * @param bond : Bond to delete
     *
     * This function delete the given bond from the molecule. The given bond
     * must be handled by the molecule. Therefore given a bond from a molecule
     * to be deleted in another molecule will result in an exception.
     * When a bond is deleted, this function calls for both involved atoms
     * the MMAtom::delBond() function to remove the corresponding bond and
     * linked atom to the bond list and the linkedatom list of theses atoms.
     * Then the function removes the given bond from the molecule. Since
     * each bond has a mIid member that correspond to the position of the
     * position within the bond list of the molecule, the deletion of a bond
     * causes to invalidate the mIid of other bonds. The option updateIaxId
     * will update all bonds to the correct value of mIid. The only reason
     * to set updateIId to false would be when the user wants to delete
     * many bonds and doesn't necesseraly need to update mIid each time.
     * For this purpose, the function MacroMole::renumBond will renumerotate
     * bonds mMid
     * @throw 350701 Given Bond is not part of this molecule
     * @throw 350702 Given Bond is not part of this molecule
     * @throw 020101 Given link is not part of this dot
     * @throw 020201 Given link not found in this dot
     *
     */
    void delBond(MMBond& bond) throw(ProtExcept);

    /**
     * @brief Tells whether or not the given bond is part of this molecule
     * @param bond Bond to check
     * @return TRUE when the bond is found in this molecule, false otherwise
     * @warning If the molecule is an alias, it will also return true as well
     * if the bond is part of this alias molecule
     */
    bool hasBond(const MMBond& bond)const;

    void clearBond();

    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////  SERIALIZATION ////////////////////
    ///////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////

    ///
    /// \brief Serialize this molecule into the given file stream
    /// \param out File stream to save the molecule into
    ///
    void serialize(std::ofstream& out) const;

    ///
    /// \brief Populate this molecule with the data in the given file stream
    /// \param ifs File stream to load data from
    ///
    void unserialize(std::ifstream& ifs);

    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    //////////////////////////// CHAINS GENERICS //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////

    /**
         * @brief Gives the number of chains in the molecule
         * @return number of chains
         *
         * Returns the total number of chains managed by this molecule.
         * This doesn't take into account whether or not the chain is in use.
         * It does not consider the temporary chain either
         */
    inline size_t numChains() const {return mListChain.size();}

    /**
         * @brief Return the pos th chain  of the chain list
         * @param pos : position of the chain to return
         * @return a reference of the pos th chain
         * @throw 350901  Position above the number of chain
         * Chains are stored in the molecule as an array of Chains.
         * Therefore giving a position will return the corresponding Chain
         * in the Chain array. When the position is above the number of Chains -1
         * (array start at 0), the function will throw an exception
         *
         * getChain function will not give you the temporary chain. To get the latter
         * you need to call getTempChain function
         */
    MMChain& getChain(const signed char& pos)  throw(ProtExcept);

    /**
     * @brief getcChain
     * @param pos
     * @return
     * @throw 351001   Position above the number of chain
     */
    const MMChain& getChain(const signed char& pos) const throw(ProtExcept);

    /**
         * @brief Return the chain with the given name
         * @param name : Name of the chain (1 Letter code : A,B,C,D)
         * @return the corresponding chain
         * @throw 351101  MacroMole::getChain Chain with name not found
         */
    MMChain& getChain(const std::string& name)const throw(ProtExcept);

    /**
         * @brief Delete a chain from the molecule
         * @param pChain Chain to delete
         * @throw 351301   MacroMole::delChain         Given chain is not part of this molecule
         * @throw 351302  MacroMole::delChain         Given chain not found in molecule
         @throw 351201   MacroMole::removeChainFromList         Given chain not found in molecule
         * The given chain must be part of the molecule.
         * All residues, atoms, bonds, rings related to this chain will be
         * deleted as well.
         *
         */
    void delChain(MMChain& pChain) throw(ProtExcept);


    /**
     * @brief addChain
     * @param pName
     * @return
     * @throw 351401    MacroMole::addChain     Bad Allocation
     */
    MMChain& addChain(const std::string& pName)throw(ProtExcept);

    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////// RESIDUES GENERICS ///////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////

    /**
         * @brief Add a new Residue to this molecule
         * @param resName Name of the new Residue
         * @param chain Related chain of the new Residue
         * @param resID ID as given by the file
         * @return the newly created Residue
         * @throw 351501   MacroMole::addResidue       This molecule is an alias. Cannot create residue on an alias
         * @throw 351401   MacroMole::addChain         Bad Allocation
         * @throw 351502   MacroMole::addResidue       Bad allocation
         * @throw 351503   MacroMole::addResidue       Wrong given chain length.
         * @throw 351504   MacroMole::addResidue       Residue name empty
         *
         * It first check that there is no Residue with name "resName" and id "resID"
         * in the chain "chain". If one is found, no Residue will be created and the
         * existing one will be returned.
         *
         */
    MMResidue& addResidue(const std::string& resName,
                          const std::string& chain,
                          const int& resID ,
                          const bool &forceCheck=true) throw(ProtExcept);

    /**
         * @brief Return the pos th Residue of the Residue list
         * @param pos : position of the Residue to return
         * @return a reference of the pos th Residue
         * @throw 351801 Given position is above the number of Residues
         * Residue are stored in the molecule as an array of Residue.
         * Therefore giving a position will return the corresponding Residue
         * in the Residue array. When the position is above the number of Residue -1
         * (array start at 0), the function will throw an exception
         */
    MMResidue& getResidue(const int &pos) throw(ProtExcept);

    /**
         * @brief Gives the number of Residues in the molecule
         * @return number of Residues
         * Returns the total number of residues managed by this molecule.
         * This doesn't take into account whether or not the residues is in use.
         * It does not consider the temporary residue either
         */
    size_t numResidue() const {return mListResidues.size();}

    /**
         * @brief deleteResidues
         * @param residueList
         * @throw 352102   MacroMole::deleteNotOwnedResidues   residue not found in molecule
         * @throw 352201    MacroMole::deleteResidues   Given residue is not part of this molecule
         */
    void deleteResidues(const std::vector<MMResidue*>& residueList) throw(ProtExcept);


    /**
     * @brief moveResidueToChain
     * @param res
     * @param chain
     * @throw 352001   MacroMole::moveResidueToChain   Residue not part of this molecule
     * @throw 352002   MacroMole::moveResidueToChain    Chain not part of this molecule
     */
    void moveResidueToChain(MMResidue& res, MMChain &chain);

    /**
     * @brief getResidueByFID
     * @param pos
     * @return
     * @throw 351601   MacroMole::getResidueByFID  No residue found with this ID
     */
    MMResidue& getResidueByFID(const int& pos) throw(ProtExcept);

    /**
     * @brief getResidue
     * @param chainName
     * @param resFID
     * @return
     * @throw 351701   MacroMole::delChain Given chain not found in molecule
     * @throw 351702   MacroMole::getResidue   No residue found with FID
     */
    MMResidue& getResidue(const std::string& chainName, const int& resFID) throw(ProtExcept);
    /**
         * @brief renumResidue
         */
    void renumResidue();

    /**
     * @brief getResidue
     * @param pos
     * @return
     * @throw 351901   MacroMole::getResidue   Given position is above the number of Residues
     */
    const MMResidue& getResidue(const int& pos)const  throw(ProtExcept);

    void select(const bool& use);
    MMChain* getChainFromName(const std::string& chainName)const;
    signed char getChainPosFromName(const std::string& chainName)const;
    int getChainPos(const std::string& chainName)const;


    MMResidue& getTempResidue(){return mTempResidue;}
    const MMResidue& getcTempResidue()const{return mTempResidue;}
    std::string toString(const bool& onlyUsed=false)const;


    void addNewError(const ErrorAtom& err){mListErrorAtom.push_back(err);}
    void addNewError(const ErrorBond& err){mListErrorBond.push_back(err);}
    void addNewError(const ErrorResidue& err);
    const ErrorAtom& getAtomError(const size_t& pos)const {return mListErrorAtom.at(pos);}
    const ErrorBond& getBondError(const size_t& pos)const {return mListErrorBond.at(pos);}
    const ErrorResidue& getResiduerror(const size_t& pos)const {return mListErrorResidue.at(pos);}
    size_t numAtomError()const{return mListErrorAtom.size();}
    size_t numBondError()const{return mListErrorBond.size();}
    size_t numResidueError()const{return mListErrorResidue.size();}
    size_t numErrors()const {return mListErrorAtom.size()+mListErrorBond.size();mListErrorResidue.size();}



    /**
     * @brief delRing
     * @param toDel
     * @param wCenter
     * @throw 352601   MacroMole::delRing    Given ring is not part of the molecule;
     */
    void delRing(MMRing& toDel,const bool& wCenter=true);

    /**
     * @brief clearRing
     * @param wCenter
     * * @throw 352601   MacroMole::delRing    Given ring is not part of the molecule;
     */
    void clearRing(const bool& wCenter=true);


    /**
     * @brief addRingSystem
     * @param list
     * @param isAromatic
     * @return
     * @throw 350101 MacroMole::addAtom This molecule is an alias. Cannot create atom on an alias
     * @throw 350102 MacroMole::addAtom Bad allocation
     * @throw 352301    MacroMole::addRingSystem  No atom in the ring
     * @throw 352302    MacroMole::addRingSystem    Bad allocation
     */
    MMRing& addRingSystem(const std::vector<MMAtom*>& list,
                          const bool& isAromatic) throw(ProtExcept);
    bool isAtomInRing(const MMAtom& atom)const;

    /**
     * @brief getRingFromAtom
     * @param atom
     * @return
     * @throw 352401   MacroMole::getRingFromAtom    No ring found
     */
    const MMRing& getRingFromAtom(const MMAtom& atom) const throw(ProtExcept);

    /**
     * @brief getRing
     * @param pos
     * @throw 352501  MacroMole::getRing    Given value above the number of rings
     */
    MMRing&  getRing(const size_t& pos) const throw(ProtExcept);
    void getRingsFromAtom(const MMAtom& atom,
                          std::vector<MMRing*>& listRings)const;
    size_t numRings() const {return mListRing.size();}

    const uint16_t& getMoleType() const;
    inline  void setMoleType(const uint16_t& pType){mMoleType=pType;}

    void clear();


    /**
     * @brief MacroMole::deleteNotOwnedResidues
     * @param residueList
     * @throw 352101   MacroMole::deleteNotOwnedResidues   Molecule owns residues
     * @throw 352102   MacroMole::deleteNotOwnedResidues   residue not found in molecule
     */
    void deleteNotOwnedResidues(const std::vector<MMResidue*>& residueList) throw(ProtExcept);

    /**
     * @brief Scan each ring and re-calculate geometric center position
     */
    void updateRingCenter();
};

}

#endif // MACROMOLE_H

