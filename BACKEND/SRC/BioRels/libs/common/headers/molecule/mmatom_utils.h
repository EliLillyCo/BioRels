#ifndef MMATOM_UTILS_H
#define MMATOM_UTILS_H
#include <cstdint>
#include <stddef.h>
#include <vector>
#include "headers/statics/intertypes.h"
namespace protspace
{
class MacroMole;
class MMAtom;
size_t numHeavyAtomBonded(const MMAtom& atom);
size_t numHydrogenAtomBonded(const MMAtom& atom);
size_t numAtomBonded(const MMAtom& atom,const unsigned char&pElem);
void removeAllHydrogen(MMAtom& atom);
bool isAtomInAromRing(const MMAtom& pAtom);
size_t hasAtom(const MMAtom& pAtom,
               const unsigned char& pElem,
               const uint16_t& pBond=BOND::UNDEFINED,
               const size_t& start=0);
void assignCarbonPhysProp(MMAtom& pAtom);
void assignSulfurPhysProp(MMAtom& pAtom);
void assignOxygenPhysProp(MMAtom& pAtom);
void assignNitrogenPhysProp(MMAtom& pAtom);
void expandSelectToRing(MMAtom& atom);
size_t numBondType(const MMAtom& pAtom,const uint16_t& btype);
short checkValence(MMAtom& pAtom);
bool findShortestPath(protspace::MMAtom& pAtomFrom,
                      protspace::MMAtom& pAtomTo,
                      std::vector<protspace::MMAtom*>& pResults);
bool isBackbone(const MMAtom& pAtom);
/**
 * @brief addExplicitHydrogen
 * @param mole
 * @throw 350101 MacroMole::addAtom        This molecule is an alias. Cannot create atom on an alias
 * @throw 350102 MacroMole::addAtom      Bad allocation
 * @throw 350601   MacroMole::addBond          This molecule is an alias. Cannot create residue on an alias
 * @throw 030303 Bad allocation
 */
void addExplicitHydrogen(MacroMole& mole);
bool shareLinkAtom(const protspace::MMAtom& pAtom1,
                   const protspace::MMAtom& pAtom2);
MMAtom& getFirstHeavyAtom(protspace::MMAtom& pAtom);
}
#endif // MMATOM_UTILS_H

