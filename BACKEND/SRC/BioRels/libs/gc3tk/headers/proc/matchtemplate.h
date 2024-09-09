#ifndef MATCHTEMPLATE_H
#define MATCHTEMPLATE_H

#include "headers/molecule/hetmanager.h"

namespace protspace
{
class MMResidue;
class MacroMole;
class MatchTemplate
{

    /**
     * \brief HETManager is a singleton that handles all template molecules
     */
    HETManager& mHETManager;

    /**
     * \brief Atom Distance matrix for each residue
     */
    UIntMatrix mResMatrix;

    /**
     *\brief when set to true, this class will only consider residues in use.
     */
    bool mOnlyUsed;
    /**
     * \brief Verbose mode
     */
    bool mVerbose;

    bool mIsInternal;


    bool mKeepAtoms;


    std::vector<std::string> mPotentialIntNames;
    std::vector<MMBond*> mCheckBonds;
    /**
      * \brief For internal ligand, scan all templates to find the right one
      * \param pMoleRes : Residue to search
      * \param exact : Perform an exact match
      * \param internal :Is it an internal ligand
      * @throw 030201 Graph::addVertex - Bad allocation
      * @throw 030303 Bad allocation
      * @throw 030303 Bad allocation
      * @throw 110101 GraphMatch::addPair Bad allocation
      * @throw 110401 GraphMatch::calcCliques No Pairs defined
      * @throw 110402 GraphMatch::calcCliques No Edges defined
      * @throw 200101 Matrix - Bad allocation
      * @throw 310101 MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
      * @throw 310102 MMAtom::setAtomicName       Atomic name not found
      * @throw 310801 MMAtom::setMOL2Type No type given
      * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
      * @throw 350102 Bad allocation
      * @throw 370101 HETInputAbst::setPosition       File is not opened
      * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370403 HETInputFile::loadMole          Bad allocation
      * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701 HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
      * @throw 370801 HETInputBind::loadMole    Error while reading file
      * @throw 370901 HETManager::open                Unable to open files
      * @throw 660101 MatchLigand::MatchLigand    Bad allocation
      * @throw 660201 MatchLigand::generatePairs      No atom found
     */
    bool scanAll(MMResidue& pMoleRes,
                 const bool &exact=true,
                 const bool internal=true);

    /**
     * \brief PRocess water residues
     *
     * Water molecules don't need graph matching.
     * @throw 650101   MatchTemplate::processWater     No oxygen found in water
     */
    void  processWater(MMResidue& pMoleRes)const;


    /**
      * @brief processResidue
      * @param pMoleRes
      * @throw 030201 Graph::addVertex -              Bad allocation
      * @throw 030303 Bad allocation
      * @throw 110101 GraphMatch::addPair             Bad allocation
      * @throw 110401 GraphMatch::calcCliques         No Pairs defined
      * @throw 110402 GraphMatch::calcCliques         No Edges defined
      * @throw 200101 Matrix - Bad allocation
      * @throw 310101 MMAtom::setAtomicName           Given atomic name must have 1,2 or 3 characters
      * @throw 310102 MMAtom::setAtomicName           Atomic name not found
      * @throw 310801 MMAtom::setMOL2Type             No type given
      * @throw 310802 MMAtom::setMOL2Type             Unrecognized MOL2 Type
      * @throw 650101 MatchTemplate::processWater     No oxygen found in water
      * @throw 660101 MatchLigand::MatchLigand        Bad allocation
      * @throw 660201 MatchLigand::generatePairs      No atom found
      * @throw 660301 MatchAA::generatePairs          No atom found
      * @throw 350102 Bad allocation
      * @throw 370101 HETInputAbst::setPosition       File is not opened
      * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370403 HETInputFile::loadMole          Bad allocation
      * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701 HETInputBin::openBinary         NO GC3TK_HOME parameter defined;
      * @throw 370801 HETInputBind::loadMole          Error while reading file
      * @throw 370901 HETManager::open                Unable to open files

     */
    void processResidue(MMResidue& pMoleRes) throw(ProtExcept);

    void scanForAmide(MacroMole& pMolecule)throw(ProtExcept);

    /**
    * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
    * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
    * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
    * @throw 370901   HETManager::open                Unable to open files
    * @throw 370801    HETInputBind::loadMole    Error while reading file
    * @throw 370101   HETInputAbst::setPosition       File is not opened
    * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
    * @throw 370403   HETInputFile::loadMole          Bad allocation
    * @throw 350102 Bad allocation
    * @throw 030303 Bad allocation
    * */
    bool testInternalLigand(MMResidue& pMoleRes, const std::string &pName);
    bool processIodine(MMResidue& pMoleRes);
public:

    MatchTemplate();


    void setOnlyUse(const bool& onlyUse){mOnlyUsed=onlyUse;}
    /**
      * \brief For internal ligand, scan all templates to find the right one
      * \param pMoleRes : Residue to search
      * \param exact : Perform an exact match
      * \param internal :Is it an internal ligand
      * @throw 030201 Graph::addVertex - Bad allocation
      * @throw 030303 Bad allocation
      * @throw 030303 Bad allocation
      * @throw 110101 GraphMatch::addPair Bad allocation
      * @throw 110401 GraphMatch::calcCliques No Pairs defined
      * @throw 110402 GraphMatch::calcCliques No Edges defined
      * @throw 200101 Matrix - Bad allocation
      * @throw 310101 MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
      * @throw 310102 MMAtom::setAtomicName       Atomic name not found
      * @throw 310801 MMAtom::setMOL2Type No type given
      * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
      * @throw 350102 Bad allocation
      * @throw 370101 HETInputAbst::setPosition       File is not opened
      * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370403 HETInputFile::loadMole          Bad allocation
      * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701 HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
      * @throw 370801 HETInputBind::loadMole    Error while reading file
      * @throw 370901 HETManager::open                Unable to open files
      * @throw 660101 MatchLigand::MatchLigand    Bad allocation
      * @throw 660201 MatchLigand::generatePairs      No atom found
     */
    void processInternalLigand(MMResidue& pMoleRes );

    void addInternalName(const std::string& pName){mPotentialIntNames.push_back(pName);}

    void correctAmideAA(MMResidue& pRes) const;

    void setIsInternal(const bool& isInternal){mIsInternal=isInternal;}
    /**
     * @brief processMolecule
     * @param pMolecule
      * @throw 030201 Graph::addVertex -              Bad allocation
      * @throw 030303 Bad allocation
      * @throw 110101 GraphMatch::addPair             Bad allocation
      * @throw 110401 GraphMatch::calcCliques         No Pairs defined
      * @throw 110402 GraphMatch::calcCliques         No Edges defined
      * @throw 200101 Matrix - Bad allocation
      * @throw 310101 MMAtom::setAtomicName           Given atomic name must have 1,2 or 3 characters
      * @throw 310102 MMAtom::setAtomicName           Atomic name not found
      * @throw 310801 MMAtom::setMOL2Type             No type given
      * @throw 310802 MMAtom::setMOL2Type             Unrecognized MOL2 Type
      * @throw 650101 MatchTemplate::processWater     No oxygen found in water
      * @throw 660101 MatchLigand::MatchLigand        Bad allocation
      * @throw 660201 MatchLigand::generatePairs      No atom found
      * @throw 660301 MatchAA::generatePairs          No atom found
      * @throw 350102 Bad allocation
      * @throw 370101 HETInputAbst::setPosition       File is not opened
      * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370403 HETInputFile::loadMole          Bad allocation
      * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701 HETInputBin::openBinary         NO GC3TK_HOME parameter defined;
      * @throw 370801 HETInputBin::loadMole          Error while reading file
      * @throw 370901 HETManager::open                Unable to open files
      * @throw 650301   MatchTemplate::checkBonds       Unable to correct issue
      * @throw 650401   MatchTemplate::processMolecule  Molecule must be owner
     */
    bool processMolecule(MacroMole& pMolecule) throw(ProtExcept);

    /**
     * @brief checkBonds
     * @param mole
     * @throw 650301   MatchTemplate::checkBonds       Unable to correct issue
     */
    void checkBonds(MacroMole &mole);

    bool delLongHBond(protspace::MMAtom& pAtom,short diff);
    void forceKeepAtom(const bool& b){mKeepAtoms=b;}
};
}
#endif // MATCHTEMPLATE_H
