#ifndef MATCHAA_H
#define MATCHAA_H
#include <cstdint>
#include "headers/proc/matchligand.h"
namespace protspace
{
class MatchAA:public MatchLigand
{
protected:
    bool allNameMatched();
    bool matchAtomByName(std::map<MMAtom*,const MMAtom*>& mapAtm)const;
    bool matchBond(const std::map<MMAtom*,const MMAtom*>& mapAtm,
                   std::map<MMBond*,MMBond*>& mapBd)const;
    bool shareInterBond(const MMAtom& atm)const;

    /**
     * @brief correctSTDAA
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     * @throw 030303 Bad allocation
     */
    void correctSTDAA() const;
    void check();
public:
    /**
     * @brief MatchAA
     * @param pMoleRes
     * @param pResMatrix
     * @param pHETentry
     * @throw 310101    MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
     * @throw 310102    MMAtom::setAtomicName       Atomic name not found
     * @throw 660101    MatchLigand::MatchLigand    Bad allocation
     */
    MatchAA(MMResidue& pMoleRes,
                UIntMatrix &pResMatrix,
                const HETEntry& pHETentry);

    /**
     * @brief process
     * @return
     * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type

     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 660301   MatchAA::generatePairs          No atom found
     * @throw 030303 Bad allocation
     * @throw 110401 GraphMatch::calcCliques No Pairs defined
     * @throw 110402  GraphMatch::calcCliques No Edges defined
     * @throw 200101 Matrix - Bad allocation
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     * @throw 030303 Bad allocation
     */
    bool process();


    /**
     * @brief generatePairs
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 660301   MatchAA::generatePairs          No atom found
     */
    void generatePairs();


    /**
     * @brief tryByName
     * @return
     * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    bool tryByName();

};
}
#endif // MATCHAA_H
