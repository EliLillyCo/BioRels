//
// Created by c188973 on 11/14/16.
//

#ifndef GC3TK_CPP_TOOLPROTALIGN_H
#define GC3TK_CPP_TOOLPROTALIGN_H


#include <string>
#include "headers/proc/protalign.h"

namespace  protspace
{
class Grid;
}
class ToolProtAlign {

protected:

protected:
    bool mEnforce;
    /**
     * \brief : For protein alignment use graph matching
     */
    bool mwGMatch;

    /**
     * \brief : Is the threshold set?
     */
    bool mHasThreshold;


    bool mWhole;

    bool mIsMultipleAnalysis;

    bool mCA;

protected:
    bool mWSwitch;

    std::vector<std::string> mRawResidueList;
    std::map<std::string,std::string> mListChainPairs;

    /**
     * \brief: If not empty, output structure alignment into this file
     */
    std::string mOutFile;


    /**
     * \brief IF not empty, save the analysis into this file
     */
    std::string mAnalysisFile;

    /**
     * \brief starting RMSD threshold for alignment
     */
    double mMinThreshold;

    protspace::MacroMole mReference;

    protspace::MacroMole mComparison;

    protspace::ProtAlign mAligner;

    double RMSDs[3][3];

    double RMSD_count[3][3];

    double nAll;
    double nSite;
    double nAlign;

    /**
     * @brief load
     * @param path
     * @param mole
     * @throw 440401   Readers::isInternal             Unable to find extension
     * @throw 030401   MacroMole::getAtom               Given position is above the number of dots
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
     * @throw 420501   ReadSDF::assignProp              Unexpectected end of line
     * @throw 420401   ReadSDF::loadAtom                Unexpectected end of line
     * @throw 420601   ReadSDF::load                    Unable to read file - Memory allocation issue
     * @throw 440101   Readers::load                   Unable to find extension
     * @throw 440201   Readers::load                   Unrecognized extension
     */
    ///TODO : Add exception from prepareMole
    void load(const std::string& path, protspace::MacroMole& mole);

    /**
     * @brief perceiveResidueList
     * @throw 540101   SeqAlign::SeqAlign              Sequence cannot have gaps
     * @throw 540102   SeqAlign::SeqAlign              Bad allocation
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     * @throw 2710201   ToolProtAlign::perceiveResidueList     Wrong number of columns in Residue List File for line
     */
    void perceiveResidueList();

    /**
     * @brief prepChains
     * @throw 350901    MacroMole::getChain             Position above the number of chain
     * @throw 520101    SequenceChain::update           Unrecognized AA
     * @throw 640103    ProtAlign::addChainPair         Bad allocation
     * @throw 2710101   ToolProtAlign::prepChains       Unsupported feature. Please specify chain pair through -p option
     */
    void prepChains();

    /**
     * @brief analyze
     * @throw 220401  Grid::generateCubes Bad allocation
     * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
     * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
     * @throw 2710401   ToolProtAlign::analyze      Unable to open file
     */
    void analyze();
    void toggleAroundLigand(protspace::MacroMole& mole, protspace::Grid& grid);
    void printHeader(std::ofstream& ofs)const;
    void updateRMSD(const protspace::MMResidue& rres,const protspace::MMResidue& cres,const bool& isInList,std::ofstream& ofs);
    bool switchAtom(const protspace::MMResidue& rres,
                    const protspace::MMResidue& cres,
                    const std::string& pAtom1,
                    const std::string& pAtom2)const;
    std::string reassignName(const std::string& pName,const std::string& pResName)const;
public:
    ToolProtAlign();

    /**
     * @brief setReference
     * @param pFile
     * @param pName
     * @throw 440401   Readers::isInternal             Unable to find extension
     * @throw 030401   MacroMole::getAtom               Given position is above the number of dots
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
     * @throw 420501   ReadSDF::assignProp              Unexpectected end of line
     * @throw 420401   ReadSDF::loadAtom                Unexpectected end of line
     * @throw 420601   ReadSDF::load                    Unable to read file - Memory allocation issue
     * @throw 440101   Readers::load                   Unable to find extension
     * @throw 440201   Readers::load                   Unrecognized extension
     */
    void setReference(const std::string& pFile,const std::string& pName);


    /**
     * @brief setComparison
     * @param pFile
     * @param pName
     * @throw 440401   Readers::isInternal             Unable to find extension
     * @throw 030401   MacroMole::getAtom               Given position is above the number of dots
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
     * @throw 420501   ReadSDF::assignProp              Unexpectected end of line
     * @throw 420401   ReadSDF::loadAtom                Unexpectected end of line
     * @throw 420601   ReadSDF::load                    Unable to read file - Memory allocation issue
     * @throw 440101   Readers::load                   Unable to find extension
     * @throw 440201   Readers::load                   Unrecognized extension
     */
    void setComparison(const std::string& pFile,const std::string& pName);

    void printAlignmentMatrix() const;
    /**
     * @brief processPair
     * @throw 030201    Graph::addVertex - Bad allocation
     * @throw 030303    Bad allocation
     * @throw 110101    GraphMatch::addPair Bad allocation
     * @throw 200101    Matrix - Bad allocation
     * @throw 330601    MMChain::getResidue                     Given position is above the number of MMResidue
     * @throw 350901    MacroMole::getChain                     Position above the number of chain
     * @throw 520101    SequenceChain::update                   Unrecognized AA
     * @throw 520501    SequenceChain::getResidue               Position is above the number of entries
     * @throw 540101    SeqAlign::SeqAlign                      Sequence cannot have gaps
     * @throw 540102    SeqAlign::SeqAlign                      Bad allocation
     * @throw 540201    SeqAlign::loadMatrix                    Unable to load blossum file
     * @throw 640103    ProtAlign::addChainPair                 Bad allocation
     * @throw 2710101   ToolProtAlign::prepChains               Unsupported feature. Please specify chain pair through -p option
     * @throw 2710201   ToolProtAlign::perceiveResidueList      Wrong number of columns in Residue List File for line
     * @throw 2710301   ToolProtAlign::processPair      Wrong set of options
     * @throw 440501   Readers::createWriter     Unrecognized extension
     * @throw 450101   WriterBase::open     No Path given
     * @throw 450102   WriterBase::open     Unable to open file
     * @throw 460101   WriterMOL2::outputAtom          Unable to find residue
     */
    void processPair();

    /**
     * @brief defineChainPairs
     * @param pStrListChains
     * @throw 2710601   ToolProtAlign::defineChainPairs        Unrecognized chain  pair
     */
    void defineChainPairs(const std::string& pStrListChains);

    /**
     * @brief defineResidueList
     * @param pFile
     * @throw 2710501   ToolProtAlign::defineResidueList       Unable to open file
     */
    void defineResidueList(const std::string& pFile);

    void setEnforce(const bool& pEnforce) { mEnforce = pEnforce; }
    void setwGMatch(const bool& pwGMatch) { mwGMatch = pwGMatch; }
    void setWhole(const bool& pWhole) { mWhole = pWhole; }
    void setCA(const bool& pCA){mCA=pCA;}
    void isMultipleAnalysis(const bool& pIsMultipleAnalysis) { mIsMultipleAnalysis = pIsMultipleAnalysis; }
    const std::vector<std::string> &getRawResidueList() const { return mRawResidueList; }
    const std::map<std::string, std::string> &getListChainPairs() const { return mListChainPairs; }
    bool isEnforce() const { return mEnforce; }
    bool iswGMatch() const { return mwGMatch; }
    bool isWhole() const { return mWhole; }
    bool isMultipleAnalysis() const { return mIsMultipleAnalysis;}
    const std::string &getOutFile() const { return mOutFile; }
    const double& getMinThreshold() const { return mMinThreshold; }
    const std::string &getAnalysisFile() const { return mAnalysisFile; }

    void setSwitch(bool pWSwitch) { mWSwitch = pWSwitch; }


    void setOutFile(const std::string &pOutFile) { mOutFile = pOutFile; }
    void setAnalysisFile(const std::string &pAnalysisFile) { mAnalysisFile = pAnalysisFile; }
    void setMinThreshold(const double& pMinThreshold) { mMinThreshold = pMinThreshold; mHasThreshold=true;}

};


#endif //GC3TK_CPP_TOOLPROTALIGN_H
