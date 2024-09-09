#ifndef MOLEREAD_H
#define MOLEREAD_H


#include <fstream>
#include <vector>
#include <map>
#include "headers/parser/readerbase.h"
#include "headers/statics/grouplist.h"
namespace protspace
{

class MacroMole;
class MMResidue;

class ReadMOL2:public ReaderBase
{
protected:
    /**
     * @brief Number of atoms that the given molecule already contain (generally 0)
     */
    size_t mNAtomMole;


    /**
     * @brief Number of residues that the given molecule already have (generally 0)
     */
    size_t mNResidueMole;

    bool mForceSubstID;

    std::map<MMResidue*,int> mRootAtom;

    std::vector<std::string> mTokens;


    std::map<int,protspace::MMResidue*> mSubstID;




    ///
    ///\brief Read Substructure block and create MMResiduees
    ///\param nExpectedRes : Number of expected MMResiduees in the molecule
    ///\param molecule : molecule to add substructure to
    ///\todo handle case where a comment is in the file making tokens size > 10
    ///
    ///\bug when MMResidue length below 3
    ///\throw 410201   ReadMOL2::readMOL2Substructure  No file opened
    /// \throw 351401    MacroMole::addChain     Bad Allocation
    /// \throw 351502    MacroMole::addResidue   Bad allocation
    /// \throw 351503   MacroMole::addResidue       Wrong given chain length.
    /// \throw 351504   MacroMole::addResidue       Residue name empty
    /// \throw 410202   ReadMOL2::readMOL2Substructure  Number of Residue found differs from expected Residues
    /// \throw 410301   ReadMOL2::prepline  Wrong number of columns

    ///
    void readMOL2Substructure(const size_t& nExpectedRes,
                              MacroMole &molecule)
    throw(ProtExcept);


    /**
     * @brief Read MMAtom block and link atom to their MMResiduees
     * @param nExpectedMMAtom : Number of expected atom in the molecule
     * @param molecule : molecule to add atoms to
     * @throw 351901   MacroMole::getResidue   Given position is above the number of Residues
     * @throw 410301   ReadMOL2::prepline  Wrong number of columns
     * @throw 350102 MacroMole::addAtom      Bad allocation
     * @throw 310802 MMAtom::setMol2Type     Unrecognized MOL2 Type
     * @throw 310101 MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102 MMAtom::setAtomicName   Atomic name not found
     * @throw 350302 Associated element to MOL2 is different than given element
     * @throw 410401   ReadMOL2::readMOL2Atom      No file opened
     * @throw 410402   ReadMOL2::readMOL2Atom      Number of atoms found differs from expected atoms
     *
     *
     */
    void readMOL2Atom(const size_t& nExpectedMMAtom,
                      MacroMole &molecule)
    throw(ProtExcept);



    /**
     * @brief Read MMBond block and link bond to their atoms
     * @param nExpectedMMBond : Number of expected bond in the molecule
     * @param molecule : molecule to add atoms to
     * @throw 350604   MacroMole::addBond          Both atoms are the same
     * @throw 030303 Bad allocation
     * @throw 410301   ReadMOL2::prepline          Wrong number of columns
     * @throw 410501   ReadMOL2::readMOL2Bond      No file opened
     * @throw 410502   ReadMOL2::readMOL2Bond      Unrecognized bond type
     * @throw 410503   ReadMOL2::readMOL2Bond      Origin atom id is above the number of atoms
     * @throw 410504   ReadMOL2::readMOL2Bond      Target atom id is above the number of atoms
     * @throw 410505   ReadMOL2::readMOL2Bond      Number of bonds found differs from expected bond
     */
    void readMOL2Bond(const size_t& nExpectedMMBond,
                      MacroMole &molecule)
    throw(ProtExcept);


    void readMOL2Header(MacroMole& molecule,
                        size_t& nExpectedRes,
                        size_t& nExpectedAtom,
                        size_t& nExpectedBond) throw(ProtExcept);


    size_t correctName(std::string& pName)const;
    /**
     * @brief prepLine
     * @param expectedSize
     * @return
     * @throw 410301   ReadMOL2::prepline  Wrong number of columns
     */
    size_t prepLine(const size_t &expectedSize);
public:
    ~ReadMOL2();



    /**
     * @brief ReadMOL2
     * @param path
     */
    ReadMOL2(const std::string &path="");


    /**
     * @brief loadAsMOL2 : Convert the MOL2 file into a molecule
     * @param molecule : Molecule to load file into
     * @todo MOLECULE block handle molecular type, charge type, mol_comment
     * @throw 310101   MMAtom::setAtomicName            Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName            Atomic name not found
     * @throw 310802   MMAtom::setMol2Type              Unrecognized MOL2 Type
     * @throw 350302   MacroMole::addAtom               Associated element to MOL2 is different than given element
     * @throw 350604   MacroMole::addBond               Both atoms are the same
     * @throw 351503   MacroMole::addResidue            Wrong given chain length.
     * @throw 351504   MacroMole::addResidue            Residue name empty
     * @throw 351901   MacroMole::getResidue            Given position is above the number of Residues
     * @throw 410101   ReadMOL2::load                   @<TRIPOS>MOLECULE block not found
     * @throw 410102   ReadMOL2::load                   Number of atoms not given in MOLECULE block
     * @throw 410103   ReadMOL2::load                   No ATOM block found while expecting atoms
     * @throw 410104   ReadMOL2::load                   No BOND block found while expecting bonds
     * @throw 410105   ReadMOL2::load                   No Residue block found while expecting Residues
     * @throw 410106   ReadMOL2::load                   Molecule is not owner
     * @throw 410107   ReadMOL2::load                   Error while loading file - Memory allocation issue
     * @throw 410202   ReadMOL2::readMOL2Substructure   Number of Residue found differs from expected Residues
     * @throw 410301   ReadMOL2::prepline               Wrong number of columns
     * @throw 410301   ReadMOL2::prepline               Wrong number of columns
     * @throw 410301   ReadMOL2::prepline               Wrong number of columns
     * @throw 410402   ReadMOL2::readMOL2Atom           Number of atoms found differs from expected atoms
     * @throw 410502   ReadMOL2::readMOL2Bond           Unrecognized bond type
     * @throw 410503   ReadMOL2::readMOL2Bond           Origin atom id is above the number of atoms
     * @throw 410504   ReadMOL2::readMOL2Bond           Target atom id is above the number of atoms
     * @throw 410505   ReadMOL2::readMOL2Bond           Number of bonds found differs from expected bond
     * @throw 410106   ReadMOL2::load                   Molecule is not owner
     * @throw 410107   ReadMOL2::load                   Error while loading file - Memory allocation issue
     * @throw 310101   MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     * @throw 350604   MacroMole::addBond      Both atoms are the same
     * @throw 351503   MacroMole::addResidue   Wrong given chain length.
     * @throw 351504   MacroMole::addResidue   Residue name empty
     * @throw 420101   ReadPDB::findAtom       Atom serial number not found
     * @throw 420201   ReadPDB::loadAtom       Atom Element not specified
     * @throw 420301   ReadPDB::load           Unable to read file - Memory allocation issue

     */
    void load(MacroMole& molecule) throw(ProtExcept);

    void forceUseSubstID(){mForceSubstID=true;}
};


}

#endif // MOLEREAD_H

