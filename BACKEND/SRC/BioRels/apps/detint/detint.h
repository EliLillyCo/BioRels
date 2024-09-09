//
// Created by c188973 on 10/27/16.
//

#ifndef GC3TK_CPP_DETINT_H
#define GC3TK_CPP_DETINT_H


#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"
#include "headers/inters/interprotlig.h"

class DetInt {
private:
    struct Entries
    {
        std::string mName;
        std::vector<short> mListInts;

        Entries(const std::vector<short>& pData,
                const std::string& pName) :
                mName(pName),mListInts(pData) { }
    };
public:
    DetInt();
    ~DetInt();

    /**
     * @brief prepMole
     * @param pFile
     * @throw 030201 Graph::addVertex -              Bad allocation
     * @throw 030303 Group::CreateLink               Bad allocation
     * @throw 030401 MacroMole::getAtom              Given position is above the number of dots
     * @throw 110101 GraphMatch::addPair             Bad allocation
     * @throw 110401 GraphMatch::calcCliques         No Pairs defined
     * @throw 110402 GraphMatch::calcCliques         No Edges defined
     * @throw 200101 Matrix - Bad allocation
     * @throw 220201 Grid::createGrid                No heavy atom to consider for the grid
     * @throw 220301 Grid::calcUnitVector            Geometric center is the same as the baryCenter
     * @throw 220401 Grid::generateCubes             Bad allocation
     * @throw 310101 MMAtom::setAtomicName           Given atomic name must have 1,2 or 3 characters
     * @throw 310102 MMAtom::setAtomicName           Atomic name not found
     * @throw 310801 MMAtom::setMOL2Type             No type given
     * @throw 310802 MMAtom::setMOL2Type             Unrecognized MOL2 Type
     * @throw 350102 MacroMole::addAtom              Bad allocation
     * @throw 350302 MacroMole::addAtom              Associated element to MOL2 is different than given element
     * @throw 350604 MacroMole::addBond              Both atoms are the same
     * @throw 351503 MacroMole::addResidue           Wrong given chain length.
     * @throw 351504 MacroMole::addResidue           Residue name empty
     * @throw 351901 MacroMole::getResidue           Given position is above the number of Residues
     * @throw 352301 MacroMole::addRingSystem        No atom in the ring
     * @throw 352302 MacroMole::addRingSystem        Bad allocation
     * @throw 370101 HETInputAbst::setPosition       File is not opened
     * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
     * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
     * @throw 370403 HETInputFile::loadMole          Bad allocation
     * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
     * @throw 370701 HETInputBin::openBinary         NO GC3TK_HOME parameter defined;
     * @throw 370801 HETInputBin::loadMole           Error while reading file
     * @throw 370901 HETManager::open                Unable to open files
     * @throw 370101 HETInputAbst::setPosition       File is not opened
     * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
     * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
     * @throw 370403 HETInputFile::loadMole          Bad allocation
     * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
     * @throw 370701 HETInputBin::openBinary         NO GC3TK_HOME parameter defined;
     * @throw 370801 HETInputBind::loadMole          Error while reading file
     * @throw 370901 HETManager::open                Unable to open files
     * @throw 410101 ReadMOL2::load                  @<TRIPOS>MOLECULE block not found
     * @throw 410102 ReadMOL2::load                  Number of atoms not given in MOLECULE block
     * @throw 410103 ReadMOL2::load                  No ATOM block found while expecting atoms
     * @throw 410104 ReadMOL2::load                  No BOND block found while expecting bonds
     * @throw 410105 ReadMOL2::load                  No Residue block found while expecting Residues
     * @throw 410106 ReadMOL2::load                  Molecule is not owner
     * @throw 410107 ReadMOL2::load                  Error while loading file - Memory allocation issue
     * @throw 410202 ReadMOL2::readMOL2Substructure  Number of Residue found differs from expected Residues
     * @throw 410301 ReadMOL2::prepline              Wrong number of columns
     * @throw 410301 ReadMOL2::prepline              Wrong number of columns
     * @throw 410301 ReadMOL2::prepline              Wrong number of columns
     * @throw 410402 ReadMOL2::readMOL2Atom          Number of atoms found differs from expected atoms
     * @throw 410502 ReadMOL2::readMOL2Bond          Unrecognized bond type
     * @throw 410503 ReadMOL2::readMOL2Bond          Origin atom id is above the number of atoms
     * @throw 410504 ReadMOL2::readMOL2Bond          Target atom id is above the number of atoms
     * @throw 410505 ReadMOL2::readMOL2Bond          Number of bonds found differs from expected bond
     * @throw 410106 ReadMOL2::load                  Molecule is not owner
     * @throw 410107 ReadMOL2::load                  Error while loading file - Memory allocation issue
     * @throw 420501 ReadSDF::assignProp             Unexpectected end of line
     * @throw 420401 ReadSDF::loadAtom               Unexpectected end of line
     * @throw 420601 ReadSDF::load                   Unable to read file - Memory allocation issue
     * @throw 440101 Readers::load                   Unable to find extension
     * @throw 440201 Readers::load                   Unrecognized extension
     * @throw 610201 BondPerception::processMolecule Molecule cannot be an alias
     * @throw 650101 MatchTemplate::processWater     No oxygen found in water
     * @throw 650301 MatchTemplate::checkBonds       Unable to correct issue
     * @throw 650401 MatchTemplate::processMolecule  Molecule must be owner
     * @throw 660101 MatchLigand::MatchLigand        Bad allocation
     * @throw 660201 MatchLigand::generatePairs      No atom found
     * @throw 660301 MatchAA::generatePairs          No atom found

    */
    void prepMole(const std::string& pFile);
    void calcComplexInteractions();
    void calcProtLigInteractions();
    const std::string &getCurrName() const { return mCurrName; }
    void setCurrName(const std::string &pCurrName) { mCurrName = pCurrName; }
    void exportToMole(const std::string& pFileName,const bool& append=false);
    void exportToCSV(const std::string& pFileName,
                     const std::string& pName,
                     const bool& append=false, const bool &wid=true);
    void assignLigand(const std::string& pFile);
    void assignLigand(protspace::MacroMole& pLig);
    void forceName();
    void addFGP(const std::string &pName);
    void exportToFGP(const std::string &pFileName);
    void setExportArom(const bool& arom){mWExportArom=arom;}
protected:
    protspace::MacroMole mProtein;
    protspace::MacroMole* mCurrLigand;
    protspace::InterProtLig* mInterPL;
    protspace::InterData mCurrResults;
    std::vector<Entries> mListFGPS;
    bool mWExportArom;
    bool mLigandOwned;
    short callID;
    std::string mCurrName;



    void prepPDB();
};


#endif //GC3TK_CPP_DETINT_H
