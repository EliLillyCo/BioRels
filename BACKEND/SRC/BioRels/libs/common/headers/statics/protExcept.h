#ifndef PROTEXCEPT_H
#define PROTEXCEPT_H


#include <exception>
#include <vector>
#include <string>
#include <stdexcept>


enum ENFORCE
{
    STRICT=1, SHOW=2, LOOSE=3
};

/**
    * @brief Extension of std::exception to handle protein management exception
    *
    * 0's, are for static
    * 01 - link
    * 02 - dot
    * 03 -Group
    * 04 - Grouplist
    * 05 - Argcv
    * 06 - ObjectPool
    * 07 - strutils
    *
    * 10's are for Graph
    * 10 - Graph
    * 11 - GraphMatch
    *
    * 20's are for math
    * 20 - Matrix
    * 21 - RigidBody
    * 22 - Grid
    *
    * 25's are for IW
    * 25 - IWICF
    * 26 - SubstrSearch

    * 30's are for molecule
    * 30 - MMBond
    * 31 - MMAtom
    * 32 - MMResidue
    * 33 - MMChain
    * 34 - MMRing
    * 35 - MacroMole
    *
    *
    * 40's are for parser
    * 40 - ReaderBase
    * 41 - ReadMOL2
    * 42 - ReadPDB
    * 43 - ReadSDF
    * 44 - Readers
    * 45 - WriterBase
    * 46 - WriterMOL2
    * 47 - WriterPDB
    * 48 - WriterSDF
    *
    * 50's are for sequences
    * 50 - SeqBase
    * 51 - Sequence
    * 52 - SeqChain
    * 53 - UniprotEntry
    *
    * 60's is for proc
    * 60 - AtomPerception
    * 61 - BondPerception
    * 62 - MultiMole
    * 63 - MatchMole
    *
    * 200's is for cmatch
    * 201 - dockmatch
    *
    * 210 is for ligalign
    * 210 - process_input
    *
    *
    * 220 is for MMPGen
    */
class ProtExcept: public std::exception
{
private:
    /**
     * @brief mId : Unique identification code of the error location
     */
    const std::string        mId;


    /**
     * @brief mLocation : Name of the function that throw the error
     */
    const std::string        mLocation;


    /**
     * @brief mGlobalDescription : Generic error text description
     */
    const std::string        mGlobalDescription;



    /**
     * @brief mDescription : Additional information given by the function callers
     */
    std::vector<std::string> mDescription;


    /**
     * @brief mHierarchy : Hierarchy of function callers
     */
    std::vector<std::string> mHierarchy;


    /**
     * @brief threat : Level of threat
     * 0: Just a notice
     * 1: Error
     * 2: Kill program
     */
    const unsigned short mThreat;


public:

    static  ENFORCE gEnforceRule;
    static short verboseLevel;

    /**
     * @brief Constructor of a new object ProtExcept
     * @param id : Unique identification code of the error location
     * @param location : Name of the function that throw the error
     * @param description : Generic error text description
     * @param threat : Level of threat of this exception for the program
     */
    ProtExcept(const std::string& id,
               const std::string& location,
               const std::string& description,
               const char* file,
               int line,
               const unsigned short& threat=0);


    virtual ~ProtExcept() throw() {}



    /**
     * @brief addHierarchy : Add a hierarchy level of function callers
     * @param val : Name of the function callers
     */
    void addHierarchy  (const std::string& val) { mHierarchy.push_back(val);}

    /**
     * @brief addDescription : Add additional information from function caller
     * @param val : Additinal information
     */
    void addDescription(const std::string& val){ mDescription.push_back(val);}

    /**
     * @brief Return the Unique identification code of the error location
     * @return the Unique identification code
     */
    const std::string& getId() const {return mId;}

    /**
     * @brief toString convert all information in this exception into a string
     * @return string describing the exception
     */
    std::string toString() const ;
    const std::string& getLocation()const{return mLocation;}
    const std::string& getDescription()const{return mGlobalDescription;}
};

#define throw_line(arg,code,loc) throw ProtExcept(arg,code,loc,__FILE__,__LINE__);
#define throw_line_t(arg,code,loc,threat) throw ProtExcept(arg,code,loc,__FILE__,__LINE__,threat);


/* 010101   link::getOther              Given parameter is not part of this link
 * 010201   link::getOther              Given parameter is not part of this link
 * 020101   dot::delLink                Given link is not part of this dot
 * 020201   dot::updateLink             Given link not found in this dot
 * 020301   dot::updateDot              Given dot not found in this dot
 * 020401   dot::getDot                 Position is above the number of linked dots
 * 020501   dot::getLink                Position is above the number of links
 * 020601   dot::dot                    Bad allocation
 * 030101   Group::Group                Bad allocation
 * 030201   Group::addDot               Bad allocation
 * 030202   Group::createDot            This group don't own dot, so can't create one
 * 030301   Group::createLink           Dot 1 is not part of this Group
 * 030302   Group::createLink           Dot 2 is not part of this Group
 * 030303   Group::createLink           Bad allocation
 * 030304   Group::createLink           This group don't own link, so can't create one
 * 030401   Group::addDot               Given position is above the number of vertices in the Group
 * 030501   Group::addDot               Cannot add a Dot own by another Group
 * 030601   Group::addLink              Cannot add an Link own by another Group
 * 030701   Group::delDot               Given Dot is not part of this molecule
 * 030702   Group::delDot               Wrong Dot MID
 * 030703   Group::delDot               Dot unmatch MID
 * 030704   Group::delDot               Atom not found in molecule
 * 030801   Group::delLink              Given Link is not part of this Group
 * 030802   Group::delLink              Given Link is not part of this Group
 * 030901   Group::getLink              Given position is above the number of links in the group
 * 031001   Group::getPos               Given dot is not part of this group
 * 040101   GroupList::get              Position  above number of entries
 * 040201   GroupList::remove           Position  above number of entries
 * 050001   ARGCV::ARGCV                not enough arguments
 * 050101   Argcv::processOption        Expected value for option. Got nothing.
 * 050102   Argcv::processOption        Expected value for option  Got parameter
 * 050201   Argcv::getArg               Position above number of arguments
 * 060101   ObjectPool::acquireObject   Given object is already in use
 * 060201   ObjectPool::acquireObject   Given object is already in use
 * 060301   ObjectPool::releaseObject   Given position is above the number of objects
 * 060302   ObjectPool::releaseObject   Given object is not in use
 * 060401   ObjectPool::releaseObject   Given object is not part of this pool
 * 060501   ObjectPool::releaseObject   Given position is above the number of objects
 * 060502   ObjectPool::releaseObject   Given object is not in use
 * 070101   tokenReuseStr               Position above the number of values
 * 100101   Graph::addEdge              Given position 1 is above the number of vertices
 * 100102   Graph::addEdge              Given position 2 is above the number of vertices
 * 110101   GraphMatch::addPair         Bad allocation
 * 110201   GraphMatch::getPair         Given number is above the number of pairs
 * 110301   GraphMatch::getClique       Given number is above the number of clique
 * 110401   GraphMatch::calcCliques     No Pairs defined
 * 110402   GraphMatch::calcCliques     No Edges defined
 * 110501   GraphMatch::getClique       Given number is above the number of clique
 * 200101   Matrix::resize              Bad allocation
 * 200201   Matrix::GetVal              Given row above the number of rows"
 * 200202   Matrix::GetVal              Given column above the number of columns
 * 200301   Matrix::setVal              Given row above the number of rows
 * 200302   Matrix::setVal              Given column above the number of columns
 * 200401   Matrix::val                 Given position above the number of element in the matrix
 * 210101   RigidBody::innerProduct     Weights does not fit size
 * 210201   RigidBody::calcRotation     Rigid coordinate list is empty
 * 210202   RigidBody::calcRotation     Mobil coordinate list is empty
 * 210203   RigidBody::calcRotation     Mobile and Rigid coordinate lists have different size
 * 220201   Grid::createGrid            No heavy atom to consider for the grid
 * 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
 * 220401   Grid::applyRotation         Issue while creating grid. Box position is negative for atom
 * 220402   Grid::applyRotation         Unable to find box for atom
 * 220501   Grid::findBox                Box position above number of boxes
 * 220502   Grid::findBox                Box position is negative for atom
 * 220503   Grid::findBox                Unable to find box
 * 250101   IWtoMacro                   Unsucessfull creation of SMILES
 * 260101   SubstrSearch::SubstrSearch  Unable to construct molecule from SMILES
 * 260102   SubstrSearch::SubstrSearch  Unable to create substructure query
 * 300101   MMBond::MMBond              Both atom are the same
 * 300201   MMBond::getOtherAtom        Given atom is NULL
 * 305001   MMBond_Utils::getBondType   Unrecognized bond type
 * 310101   MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
 * 310102   MMAtom::setAtomicName       Atomic name not found
 * 310201   MMAtom::getBond             position is above the number of bond for this atom
 * 310301   MMAtom::getBond             No bond found between the two atoms
 * 310401   MMAtom::getBondType         No bond found between the two atoms
 * 310501   MMAtom::getAtom             pos is above the number of bonds for this atom
 * 310601   MMAtom::getAtomNotAtom      No alternative aton found
 * 310701   MMAtom::getResidue          No residue defined for this atom
 * 310801   MMAtom::setMOL2Type         No type given
 * 310802   MMAtom::setMOL2Type         Unrecognized MOL2 Type
 * 320101   MMResidue::addAtom          No atom given
 * 320201   MMResidue::delAtom          No atom given
 * 320202   MMResidue::delAtom          Given atom is not part of this Residue
 * 320203   MMResidue::delAtom          Given atom is not part of this Residue
 * 320301   MMResidue::delAtom          Given atom is not part of this Residue
 * 320302   MMResidue::delAtom          Given atom is not part of this Residue
 * 320401   MMResidue::getAtom          Given position is above the number of atom in this Residue
 * 320501   Residue::getAtom            Atom Not found
 * 320502   Residue::getAtom            Atom Not found
 * 325001   MMResidue_Utils::residue1Lto3L Name not found
 * 325002   MMResidue_Utils::residue1Lto3L input should be 1 letter
 * 325101   MMResidue_Utils::getResidueType  Unrecognized residue type
 * 330101   MMChain::addResidue         No Residue given
 * 330102   MMChain::addResidue         Given residue not part of this chain
 * 330201   MMChain::addResidue         Given residue not part of this chain
 * 330301   MMChain::delResidue         No Residue given
 * 330302   MMChain::delResidue         Given Residue is not part of this chain
 * 330303   MMChain::delResidue         Given Residue is not part of this chain
 * 330401   MMChain::delResidue         Given Residue is not part of this chain
 * 330402   MMChain::delResidue         Given Residue is not part of this chain
 * 330501   MMChain::getResidue         Given position is above the number of Residues
 * 330601   MMChain::getResidue         Given position is above the number of Residues
 * 330701   MMChain::getResidueByFID        Given position  has not been found
 * 330801   MMChain::getResidue         No Residue found with the given parameters
 * 330901   MMChain::getResidue         No Residue found with the given parameters
 * 350101   MacroMole::addAtom          This molecule is an alias. Cannot create atom on an alias
 * 350102   MacroMole::addAtom          Bad Allocation
 * 350201   MacroMole::addAtom(RES)     Given residue is not in this molecule
 * 350301   MacroMole::addAtom(RES,COORDS)  Given residue is not in this molecule
 * 350302   MacroMole::addAtom(RES,COO) Associated element to MOL2 is different than given element
 * 350401   MacroMole::getAtomByFID     Given ID not found
 * 350501   MacroMole::getBond          Given position is above the number of bonds
 * 350601   MacroMole::addBond          This molecule is an alias. Cannot create residue on an alias
 * 350602   MacroMole::addBond          Atom not part of this molecule
 * 350603   MacroMole::addBond          Atom not part of this molecule
 * 350604   MacroMole::addBond          Both atoms are the same
 * 350701   MacroMole::delBond          Given Bond is not part of this molecule
 * 350702   MacroMole::delBond          Given Bond is not part of this molecule
 * 350801   MacroMole::delAtom          Atom not found in molecule
 * 350901   MacroMole::GetChain         Position above the number of chain
 * 351001   MacroMole::getChain         Position above the number of chain
 * 351101   MacroMole::getChain         Chain with name not found
 * 351201   MacroMole::removeChainFromList         Given chain not found in molecule
 * 351301   MacroMole::delChain         Given chain is not part of this molecule
 * 351302   MacroMole::delChain         Given chain not found in molecule
 * 351401   MacroMole::addChain         Bad Allocation
 * 351501   MacroMole::addResidue       This molecule is an alias. Cannot create residue on an alias
 * 351502   MacroMole::addResidue       Bad allocation (for chain)
 * 351503   MacroMole::addResidue       Wrong given chain length.
 * 351504   MacroMole::addResidue       Residue name empty
 * 351601   MacroMole::getResidueByFID  No residue found with this ID
 * 351701   MacroMole::delChain         Given chain not found in molecule
 * 351702   MacroMole::getResidue       No residue found with FID
 * 351801   MacroMole::getResidue       Given position is above the number of Residues
 * 351901   MacroMole::getResidue       Given position is above the number of Residues
 * 352001   MacroMole::moveResidueToChain   Residue not part of this molecule
 * 352002   MacroMole::moveResidueToChain    Chain not part of this molecule
 * 352101   MacroMole::deleteNotOwnedResidues   Molecule owns residues
 * 352102   MacroMole::deleteNotOwnedResidues   residue not found in molecule
 * 352201   MacroMole::deleteResidues   Given residue is not part of this molecule
 * 352301   MacroMole::addRingSystem    No atom in the ring
 * 352302   MacroMole::addRingSystem    Bad allocation
 * 352401   MacroMole::getRingFromAtom  No ring found
 * 352501  MacroMole::getRing           Given value above the number of rings
 * 352601   MacroMole::delRing          Given ring is not part of the molecule
 *
 * 400101   ReaderBase::open            No file given
 * 400102   ReaderBase::open            Unable to open file
 * 410101   ReadMOL2::load              @<TRIPOS>MOLECULE block not found
 * 410102   ReadMOL2::load              Number of atoms not given in MOLECULE block
 * 410103   ReadMOL2::load              No ATOM block found while expecting atoms
 * 410104   ReadMOL2::load              No BOND block found while expecting bonds
 * 410105   ReadMOL2::load              No Residue block found while expecting Residues
 * 410106   ReadMOL2::load              Molecule is not owner
 * 410107   ReadMOL2::load              Error while loading file - Memory allocation issue
 * 410201   ReadMOL2::readMOL2Substructure  No file opened
 * 410202   ReadMOL2::readMOL2Substructure  Number of Residue found differs from expected Residues
 * 410301   ReadMOL2::prepline              Wrong number of columns
 * 410401   ReadMOL2::readMOL2Atom          No file opened
 * 410402   ReadMOL2::readMOL2Atom          Number of atoms found differs from expected atoms
 * 410501   ReadMOL2::readMOL2Bond          No file opened
 * 410502   ReadMOL2::readMOL2Bond          Unrecognized bond type
 * 410503   ReadMOL2::readMOL2Bond          Origin atom id is above the number of atoms
 * 410504   ReadMOL2::readMOL2Bond          Target atom id is above the number of atoms
 * 410505   ReadMOL2::readMOL2Bond          Number of bonds found differs from expected bond
 * 420101   ReadPDB::findAtom               Atom serial number not found
 * 420201   ReadPDB::loadAtom               Atom Element not specified
 * 420301   ReadPDB::load                   Unable to read file - Memory allocation issue
 * 430101   ReadSDF::load                   No file opened
 * 430201   ReadSDF::loadHeader             Only read V2000 SD format
 * 420301   ReadSDF::loadBond               Unexpectected end of line
 * 420401   ReadSDF::loadAtom               Unexpectected end of line
 * 420501   ReadSDF::assignProp             Unexpectected end of line
 * 420601   ReadSDF::load                   Unable to read file - Memory allocation issue"
 * 440101   Readers::load                   Unable to find extension
 * 440201   Readers::createReader           Unrecognized extension
 * 450101   WriterBase::open                No Path given
 * 450102   WriterBase::open                Unable to open file
 * 460101   WriterMOL2::outputAtom          Unable to find residue
 * 460201   WriteMOL2::outputBond           Unable to find atom 1
 * 460202   WriteMOL2::outputBond           Unable to find atom 2
 * 460203   WriteMOL2::outputBond           Unrecognized bond type
 * 470101   WritePDB::outputAtom            Unrecognized formal charge
 * 480101   WriterSDF::outputHeader         No file opened
 * 480102   WriterSDF::outputHeader         SDF file limited to 999 atoms
 * 480103   WriterSDF::outputHeader         SDF file limited to 999 bonds
 * 480201   WriterSDF::outputBond           Unrecognized bond type
 * 500101   SeqBase::getPos                 Unrecognized character
 * 500201   SeqBase::getPos                 Unrecognized character
 * 500301   SeqBase::loadFastaSequence      Unrecognized AA
 * 510101   Sequence::loadFastaSequence     Header line should start with > for line
 * 510102   Sequence::loadFastaSequence     Unrecognized AA
 * 510201   Sequence::loadFastaSequence     Unrecognized AA
 * 510301   Sequence::loadPIRSequence       Header line should start with > for line
 * 510302   Sequence::loadPIRSequence       Unrecognized AA
 * 510401   Sequence::getResidue            Standard sequence is not associated to a residue
 * 520101   SequenceChain::update           Unrecognized AA
 * 520201   SequenceChain::posToName        Position is above the number of characters
 * 520301   SequenceChain::posToId          Position is above the number of entries
 * 520401   SequenceChain::getResidue       Position is above the number of entries
 * 520501   SequenceChain::getResidue       Position is above the number of entries
 * 520601   SequenceChain::updateResName    Unrecognized AA
 * 520602   SequenceChain::updateResName    Given position above sequence length
 * 530101   UniprotEntry::UniprotEntry      Given AC must not be empty
 * 530201   UniprotEntry::loadData          Unable to open file
 *
 * 600101   AtomPerception::perceive        Unable to process atom
 * 600201   AtomPerception::setMOL2         Atom out of range of processing list
 * 600301   AtomPerception::followRule      Atom does not have any bonds - Please contact administrator
 * 600401   AtomPerception::assignSingleMOL2    Atom element not recognized
 * 630101   MatchMole::linkPairs            No pairs found
 * 630201   MatchMole::checkBondDistance    Position out of range
 *
 * 2010101  DockMatch::performMatch         Number of atoms in reference too small
 * 2010102  DockMatch::performMatch         Number of atoms in comparison too small
 *
 * 2200101  MMPSearch::loadInput            No RGroup start position defined
 * 2200102  MMPSearch::loadInput            No RGroup end position defined
 * 2200103  MMPSearch::loadInput            Unable to open file
 * 2200104  MMPSearch::loadInput            No Property position defined
*/

#endif // PROTEXCEPT_H
