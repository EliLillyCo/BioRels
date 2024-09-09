#ifndef PDBREAD_H
#define PDBREAD_H
#include <map>
#include "headers/parser/readerbase.h"

namespace protspace
{
class MMResidue;
class MMAtom;
class MacroMole;

class ReadPDB:public ReaderBase
{
protected:

    ///
    /// \brief Residue that is currently is use
    ///
    MMResidue* mCurrResidue;

    ///
    /// \brief mIsNMR : boolean set to TRUE when the file is a PDB NIR structure
    ///
    bool mIsNMR;

    bool mIsAbove100K;

    ///
    /// \brief Number of alternative position found
    ///
    int nInsert;


    ///
    /// \brief Number of bond found
    ///
    size_t mNumBond;


    ///
    /// \brief Number of error found in the file
    ///
    size_t mNError;

    ///
    /// \brief Current insertion code
    ///
    std::string mCurrInsert;

    ///
    /// \brief Residue name mapping for internal structure
    ///
    std::map<std::string,std::string> mMapNames;

    ///
    /// \brief list of Atom Id from PDB file that mapped the position of atoms in posATI
    ///
    std::vector<int> mPosID;

    ///
    /// \brief list of atoms already created that are mapped with their id in mPosID
    ///
    std::vector<MMAtom*> posATI;

    std::vector<protspace::MMResidue*> listInsertedRes;
    ///
    /// \brief List of alternative positions
    ///
    std::vector<std::string> mListAltLines;

    std::string mStrCurrRes;
    std::string mStrNewCurrRes;
    std::string mPrevAltPos;
    int mCurrNMRModel;



    /**
     * @brief findAtom
     * @param pos
     * @param isFound
     * @return
     * @throw 420101   ReadPDB::findAtom           Atom serial number not found
     */
    MMAtom& findAtom(const int& pos, bool &isFound) const;

    /**
     * @brief createResidue
     * @param molecule
     * @param resName
     * @return
     * @throw 351401    MacroMole::addChain     Bad Allocation
     * @throw 351502    MacroMole::addResidue   Bad allocation
     * @throw 351503   MacroMole::addResidue       Wrong given chain length.
     * @throw 351504   MacroMole::addResidue       Residue name empty

     */
    bool createResidue(MacroMole& molecule,const std::string& resName);

    /**
     * @brief loadBond
     * @param molecule
     * @throw 350604   MacroMole::addBond          Both atoms are the same
     * @throw 420101   ReadPDB::findAtom           Atom serial number not found
     * @throw 030303 Bad allocation
     */
    void loadBond(MacroMole& molecule);
    /**
     * @brief loadAtom
     * @param molecule
     * @throw 351401  MacroMole::addChain     Bad Allocation
     * @throw 351502  MacroMole::addResidue   Bad allocation
     * @throw 351503  MacroMole::addResidue   Wrong given chain length.
     * @throw 351504  MacroMole::addResidue   Residue name empty
     * @throw 420201  ReadPDB::loadAtom       Atom Element not specified
     * @throw 350102  MacroMole::addAtom      Bad allocation
     * @throw 310101  MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102  MMAtom::setAtomicName   Atomic name not found

     */
    void loadAtom(MacroMole &molecule);
    void assignFormalCharge(MMAtom& atom)const;
    void handleAtomLine(protspace::MacroMole& molecule)throw(ProtExcept);
    void processAlternativePositions(const std::vector<std::string>& listLines, MacroMole& mole);
    bool findPos(const std::vector<int> &list, const int& pos, unsigned int& arrPos)const;
    void checkInsertedResidues(MacroMole &mole);
public:
    ReadPDB(const std::string& path="");
    ~ReadPDB();

    /**
     * @brief load
     * @param molecule
     * @throw 310101   MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     * @throw 350604   MacroMole::addBond      Both atoms are the same
     * @throw 351503   MacroMole::addResidue   Wrong given chain length.
     * @throw 351504   MacroMole::addResidue   Residue name empty
     * @throw 420101   ReadPDB::findAtom       Atom serial number not found
     * @throw 420201   ReadPDB::loadAtom       Atom Element not specified
     * @throw 420301   ReadPDB::load           Unable to read file - Memory allocation issue
     */
    void load(MacroMole &molecule) throw(ProtExcept);
   void clean();
};

}
#endif // PDBREAD_H

