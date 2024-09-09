#ifndef DOCKMATCH_H
#define DOCKMATCH_H

#include "headers/molecule/macromole.h"
#include "headers/proc/matchmole.h"
#include "entrymatch.h"
namespace protspace
{
class CompClique;
class DockMatch
{

    std::string mRefSMI;
    std::string mCompSMI;
    MacroMole &mRefMole;
    MacroMole &mCompMole;
    MatchMole mGraphMatch;
    MatchMole mAtomMatch;
    size_t mLimitGr;
    size_t mLimitAt;
   bool mDebug;
    bool mKeepFused;
    bool mRingAromMatch;
    bool mRingBondMatch;
    bool mConsiderHydrogen;

    struct Scoring
    {
        size_t GrPos, AtPos;
        std::vector<EntryMatch> addMatch;
        std::map<int,int> iaja;
        std::vector<MMAtom*> selAtmR, selAtmC;
        size_t score;
    };


double mRatio;
double mMinAtm;
std::multimap<size_t,CompClique*> mListScore;

    void getBiggestFragment(const MacroMole& pMole, std::vector<MMAtom*>&liste,
                            const bool& onlyUsed=false);
    bool refineScore(Scoring& groupscore, std::ostringstream &oss);
    void assessRing(MacroMole& mole, std::vector<MMAtom *> &delAtm,std::ostringstream& oss);
    void cleanGroup(const bool& isRef, const std::vector<MMAtom*>& toDel,
                               Scoring& groupscore,std:: ostringstream &oss);
    void filterUnsimilarAtoms(Scoring& groupscore,std:: ostringstream &oss);
    void getAtomRings(const MMAtom& atom, std::vector<MMRing *> &list)const;
    bool checkFilter(const EntryMatch& match)const;
public:
    DockMatch(MacroMole&ref,MacroMole& comp);
    ~DockMatch();
    bool runAll();
    /**
     * @brief performMatch
     * @param match
     * @throw 630101  MatchMole::linkPairs  No pairs found
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 030303 Bad allocation
     * @throw 110402  GraphMatch::calcCliques No Edges defined
     * @throw 200101 Matrix - Bad allocation
     * @throw 2010101  DockMatch::performMatch         Number of atoms in reference too small
     * @throw 2010102  DockMatch::performMatch         Number of atoms in comparison too small
     */
    bool performMatch(MatchMole &match);

    void compareCliques();

    const std::string& getRefSMILES()const {return mRefSMI;}
    const std::string& getCompSMILES()const {return mCompSMI;}
    const std::string& getRefName() const { return mRefMole.getName();}
    const std::string& getCompName() const { return mCompMole.getName();}

    double bestSolutionSize()const;
    /**
     * @brief printResults
     * @param count
     * @param pOUT
     * @throw 450101   WriterBase::open     No Path given
     * @throw 450102   WriterBase::open     Unable to open file
     */
    void printResults(const size_t& count=10, const std:: string &pOUT="", const bool &pOnlySel=true, const bool &p3DAlign=false);
    void setKeepFusedRing(const bool& keepFused){ mKeepFused=keepFused;}
    void setMinRatio(const double& paramRatio){mRatio=paramRatio;}
    void setNAtom(const double &paramNAtom){mMinAtm=paramNAtom;}
    void setRingAromMatch(const bool& enforce){mRingAromMatch=enforce;}
    void setRingBondMatch(const bool& enforce){mRingBondMatch=enforce;}
    void setWHydrogen(const bool& wHydrogen){mConsiderHydrogen=wHydrogen;}
    const CompClique& getResult(const size_t& pos)const{
        auto it=std::next(mListScore.rbegin(), pos);
        return *(*it).second;}
    size_t numResults()const{return mListScore.size();}
};
}
#endif // DOCKMATCH_H
