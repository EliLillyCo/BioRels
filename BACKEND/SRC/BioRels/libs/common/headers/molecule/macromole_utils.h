#ifndef MACROMOLE_UTILS_H
#define MACROMOLE_UTILS_H

#include <vector>
#include "headers/statics/grouplist.h"
#include "headers/math/matrix.h"
namespace protspace
{
class MacroMole;
class MMAtom;
class Coords;
class MMResidue;
class MMChain;
class RigidAlign;


/**
 * @brief getMoleculesData
 * @param mMolecules
 * @param pCenter
 * @param pMassCenter
 * @param pResiduMassCenter
 * @param wHydrogen
 * @param onlySelected
 */
void getMoleculesData(const GroupList<MacroMole>& mMolecules,
                     Coords& pCenter,
                     Coords& pMassCenter,
                      Coords& pResiduMassCenter,
                     const bool& wHydrogen=true,
                     const bool& onlySelected=false);

void convertMSE_MET(MacroMole& mole);
void convertCSE_CYS(MacroMole& mole);
double calcRMSD(std::vector<MMAtom*>&rList, std::vector<MMAtom*>& cList);

size_t getNumHeavyAtoms(const MacroMole& mole);
void getBiggestFragment(const MacroMole& pMole, std::vector<const MMAtom*>&liste);
void removeAllHydrogen(MacroMole& mole);
void mergeInSingleChain(MacroMole& mole);
std::string getFormula(const MacroMole& mole);
double getMolecularWeigth(const MacroMole& pMole);
size_t numAtom(const MacroMole& mole,const unsigned char&pElem);
size_t numHeavyAtoms(const MacroMole& pMole, const bool& pOnlyUsed);
template<class T> size_t getPos(const std::vector<T*>& pList, T& obj)
{
    const auto it = std::find(pList.begin(),pList.end(),&obj);
    if (it == pList.end())return pList.size();
    return std::distance(pList.begin(),it);
}
template<MMChain*> size_t getPos(const std::vector<MMChain*>, MMChain& obj);
template<MMResidue*> size_t getPos(const std::vector<MMResidue*>, MMResidue& obj);

void assignPhysProps(MacroMole& pMole);
void getDistanceMatrix(MacroMole& pMole,
                       protspace::UIntMatrix &matrix,
                       const std::vector<MMAtom*> & dotlist)  throw(ProtExcept);
void getDistanceMatrix(MacroMole& pMole, UIntMatrix &matrix);
void consistentSelection(protspace::MacroMole& mole);
void applyAlignment(const RigidAlign& pAligner,protspace::MacroMole& pMole);
}

#endif // MACROMOLE_UTILS_H

