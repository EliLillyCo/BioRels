#ifndef PDBENTRY_UTILS_H
#define PDBENTRY_UTILS_H

#include <inttypes.h>
namespace protspace
{
class MacroMole;

///TODO: list exception and apply to all calling functions
/**
 * @brief prepareMolecule
 * @param mole
 * @param rules
 * @param isInternal
 * @throw 030201 Graph::addVertex -              Bad allocation
 * @throw 030303 Group::CreateLink               Bad allocation
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
 * @throw 350102 Bad allocation
 * @throw 370101 HETInputAbst::setPosition       File is not opened
 * @throw 370201 HETInputAbst::setPosition       No Molecule by this name found
 * @throw 370301 HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
 * @throw 370403 HETInputFile::loadMole          Bad allocation
 * @throw 370601 HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
 * @throw 370701 HETInputBin::openBinary         NO GC3TK_HOME parameter defined;
 * @throw 370801 HETInputBin::loadMole           Error while reading file
 * @throw 370901 HETManager::open                Unable to open files
 * @throw 610201 BondPerception::processMolecule Molecule cannot be an alias
 * @throw 650101 MatchTemplate::processWater     No oxygen found in water
 * @throw 650301 MatchTemplate::checkBonds       Unable to correct issue
 * @throw 650401 MatchTemplate::processMolecule  Molecule must be owner
 * @throw 660101 MatchLigand::MatchLigand        Bad allocation
 * @throw 660201 MatchLigand::generatePairs      No atom found
 * @throw 660301 MatchAA::generatePairs          No atom found
 */
void prepareMolecule(MacroMole& mole, const uint32_t& rules, const bool &isInternal);
}
#endif // PDBENTRY_UTILS_H
