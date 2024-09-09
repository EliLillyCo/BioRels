#ifndef MATCHRESIDUE_H
#define MATCHRESIDUE_H
#include "headers/graph/graphmatch.h"
#include "headers/molecule/mmresidue.h"
namespace protspace
{
class MatchResidue
{
    protected:
    typedef Pair<const MMAtom> AtmPair;
    typedef Clique<const MMAtom> AtmClique;


    MMResidue& mMoleRes;
    UIntMatrix& mResMatrix;
    std::map<const MMAtom*,int> mResMatPos;
    GraphMatch<const MMAtom> mGraphmatch;

    bool *mMoleResConsidered;
    bool *mHETConsidered;

    const size_t nMoleResHeavyAtom;

    const size_t nMResAtom;
    const std::string& mResName;

    /**
     * @brief assignElement
     * @return
     * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     */
    bool assignElement();
    ///
    /// \brief Consider Hydrogen in the graph Matching
    ///
    bool mOptWHydrogen;

    /**
     * @brief pushAtomData
     * @param to
     * @param from
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    void pushAtomData(MMAtom& to, const MMAtom& from)const;
    void assignHydrogenName(MMAtom&pAtRes, const MMAtom& pHET)const;


    /**
     * @brief processSingleAtom
     * @param pHETAtom
     * @return
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    bool processSingleAtom(const MMAtom& pHETAtom) const
    throw(ProtExcept);

    /**
     * @brief processDoubleAtom
     * @param HETMolecule
     * @return
     * @throw 310801 MMAtom::setMOL2Type No type given
     * @throw 310802 MMAtom::setMOL2Type Unrecognized MOL2 Type
     */
    bool processDoubleAtom(const MacroMole& HETMolecule) const
    throw(ProtExcept);
    void updateMatrix();
    void preparepos();
public:
    MatchResidue(MMResidue& pMoleRes,UIntMatrix &mResMatrix);
    virtual ~MatchResidue();
    virtual void generatePairs()=0;
    virtual void generateLinks()=0;

    /**
     * @brief calcCliques
     * @param size
     * @return
     * @throw 110401 GraphMatch::calcCliques No Pairs defined
     * @throw 110402  GraphMatch::calcCliques No Edges defined
     * @throw 200101 Matrix - Bad allocation
     */
    virtual size_t calcCliques(const size_t& size=0);

    virtual bool process()=0;


};
}
#endif // MATCHRESIDUE_H
