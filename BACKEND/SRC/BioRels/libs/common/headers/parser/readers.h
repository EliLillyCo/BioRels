#ifndef READERS_H
#define READERS_H

#include <string>
#include <vector>
#include <fstream>
#include "headers/statics/grouplist.h"
namespace protspace
{

class MacroMole;
class MultiMole;
class ReaderBase;
class WriterBase;
/**
 * @brief readFile
 * @param mole
 * @param file
 * @return
 * @throw 030401   MacroMole::getAtom      Given position is above the number of dots
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
 * @throw 420501   ReadSDF::assignProp     Unexpectected end of line
 * @throw 420401   ReadSDF::loadAtom       Unexpectected end of line
 * @throw 420601   ReadSDF::load           Unable to read file - Memory allocation issue
 * @throw 440101   Readers::load                   Unable to find extension
 * @throw 440201   Readers::load                   Unrecognized extension

 */
void readFile(MacroMole& mole, const std::string& file);
void readFile(GroupList<MacroMole>& list, const std::string& file);

void readMultiFile(MultiMole& multi, const std::string& pFile, const size_t &maxMole=10000000);

std::string getExtension(const std::string& pFile);

void createReader(ReaderBase *&reader, const std::string& pFile);

std::ifstream::pos_type filesize(const std::string& filename);


/**
 * @brief isInternal
 * @param pFile
 * @return
 * @throw 440401    Readers::isInternal     Unable to find extension
 */
bool isInternal(const std::string& pFile);

/**
 * @brief createWriter
 * @param writer
 * @param pFile
 * @throw 440501   Readers::createWriter     Unrecognized extension
 */
void createWriter(WriterBase*& writer, const std::string& pFile);

/**
 * @brief saveFile
 * @param mole
 * @param file
 * @throw 440501   Readers::createWriter     Unrecognized extension
 * @throw 450101   WriterBase::open     No Path given
 * @throw 450102   WriterBase::open     Unable to open file
 * @throw 460101   WriterMOL2::outputAtom          Unable to find residue
 */
void saveFile(MacroMole& mole, const std::string& file, const bool &onlySelected=false);

/**
 * @brief saveMultiFile
 * @param mole
 * @param file
 * @throw 440501   Readers::createWriter     Unrecognized extension
 * @throw 450101   WriterBase::open     No Path given
 * @throw 450102   WriterBase::open     Unable to open file
 * @throw 460101   WriterMOL2::outputAtom          Unable to find residue
 */
void saveMultiFile(MultiMole& mole, const std::string& file, const bool &onlySelected=false);

}

#endif // READERS_H
