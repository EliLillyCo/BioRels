#ifndef COMPCLIQUE_H
#define COMPCLIQUE_H

#include "headers/graph/graphclique.h"
#include "headers/molecule/mmatom.h"
#include "headers/proc/dockmatch/entrymatch.h"
namespace protspace
{
class CompClique
{
protected:
    const Clique<MMAtom>& mCliqueR;
    const Clique<MMAtom>& mCliqueC;
    MacroMole& mRefMole;
    MacroMole& mCompMole;
    bool *mRAtmFound;
    bool mRingAromMatch;
    bool mRingBondMatch;
    size_t mOveralScore;
    struct CliquePair
    {
        size_t rpos;
        size_t cpos;
        size_t ratpos;
        size_t catpos;
        ///
        /// \brief 1: IDENTICAL 2:SIMILAR 0:UNKNOWN
        ///
        signed char type;
    };

    std::vector<CliquePair> mListInters;
    void computeScore();
    void getSimilar();
    void findIdentical();
    bool isFullMatch(const MMAtom& atom)const;
    bool refineScore();
    bool checkAromRing();
    void assessRing(MacroMole& mole);
    void cleanNonBiggestFrag(MacroMole& mole,
                              std::vector<const MMAtom*> selAtm);
    void removeFromResults(const MMAtom& atom, const bool& isRef);

    bool checkFilter(const MMAtom& atomR,
                                 const MMAtom& atomC)const;
    void filterUnsimilarAtoms();

public:
    ~CompClique();
    CompClique(const Clique<MMAtom>& cliqueR,
                     const  Clique<MMAtom>& cliqueC,
                   MacroMole &mole,
               MacroMole& moleC);
    bool compare();
    const size_t& getScore()const {return mOveralScore;}
    void selectMole();
    void setRingAtomMatch(const bool& match){mRingAromMatch=match;}
    void setRingBondMatch(const bool& match){mRingBondMatch=match;}
    size_t size()const {return mListInters.size();}
    void toEntryMatch(std::vector<EntryMatch>& list)const;

};

}
#endif // COMPCLIQUE_H
