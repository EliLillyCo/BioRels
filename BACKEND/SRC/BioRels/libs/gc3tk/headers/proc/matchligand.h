#ifndef MATCHLIGAND_H
#define MATCHLIGAND_H

#include "headers/proc/matchresidue.h"
#include "headers/molecule/hetmanager.h"
namespace protspace
{
class MatchLigand:public MatchResidue
{
protected:
    const HETEntry& mHETEntry;
    const size_t mNHETHeavyAtom;
    const size_t mNHETAtom;
    bool mNoTerminal;
    bool mwCheck;
    /**
     * \brief If not match has been found, this allow to check for substructure
     *
     * Help to correct Bug #15951
     */
    bool mAllowedSmaller;
    size_t mMinSize;

public:
    /**
     * @brief MatchLigand
     * @param pMoleRes
     * @param pResMatrix
     * @param pHETentry
     * @throw 310101    MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
     * @throw 310102    MMAtom::setAtomicName       Atomic name not found
     * @throw 660101    MatchLigand::MatchLigand    Bad allocation
     */
    MatchLigand(MMResidue& pMoleRes,
                UIntMatrix &pResMatrix,
                const HETEntry& pHETentry);


    /**
     * @brief process
     * @return
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 030303 Bad allocation
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 110401 GraphMatch::calcCliques No Pairs defined
     * @throw 110402  GraphMatch::calcCliques No Edges defined
     * @throw 200101 Matrix - Bad allocation
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     * @throw 660201   MatchLigand::generatePairs      No atom found
     */
    bool process();

    /**
     * @brief generatePairs
     * @throw 660201   MatchLigand::generatePairs      No atom found
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     */
    void generatePairs();

    /**
     * @brief generateLinks
     * @throw 030303 Bad allocation
     */
    void generateLinks();

    /**
     * @brief calcCliques
     * @param size
     * @return
     * @throw 110401 GraphMatch::calcCliques No Pairs defined
     * @throw 110402  GraphMatch::calcCliques No Edges defined
     * @throw 200101 Matrix - Bad allocation
     */
    size_t calcCliques(const size_t& size=0);

    /**
     * @brief assessTerminalAtom
     * @param resAtom
     * @param HETatom
     * @param mResToEntryAtomMap
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    void assessTerminalAtom(MMAtom& resAtom, const MMAtom& HETatom,
                                                    std::map<const MMAtom *, const MMAtom *> &mResToEntryAtomMap);

    /**
     * @brief processClique
     * @param nCli
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    void processClique(const size_t& nCli=0);

    /**
     * @brief processAtomClique
     * @param clique
     * @param mResToEntryAtomMap
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    void processAtomClique(const AtmClique& clique,
        std::map<const MMAtom *, const MMAtom *> &mResToEntryAtomMap);
    void check()const;
    void enforceCheck(const bool& enforce){mwCheck=enforce;}
    void allowSubstSearch(const bool& enforce){mAllowedSmaller=enforce;}
    void setNoTerminal(const bool& noTerm){mNoTerminal=noTerm;}

    size_t getNumSymAtom();
};
}
#endif // MATCHLIGAND_H
