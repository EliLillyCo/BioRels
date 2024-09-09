#ifndef SEQALIGN_H
#define SEQALIGN_H

#include "headers/sequence/seqbase.h"
#include "headers/math/matrix.h"
namespace  protspace
{
class SeqPairAlign;
enum SeqStepMove {
    undefined=0,
    diagonal = 1,
    left=2,
    above=3,
    end=4
};

struct SeqMove
{
    double mScore;
    SeqStepMove mMove;
    int mX;
    int mY;
    int mXNext;
    int mYNext;
    SeqMove():mScore(0),mMove(end),mX(-1),mY(-1){}

};


class SeqAlign
{
protected:
    SeqBase& mRefSeq;
    SeqBase& mCompSeq;
    static SMatrix sSimMat;
    static bool sMatLoaded;
    static std::string sAltPath;
    /**
     * @brief loadMatrix
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     */
    static void loadMatrix(const bool &pIsProtein);
    double mExtendGapPenalty;
    double mGapOpenPenalty;
    double mThreshold;
    SeqMove*mScoreMat;
    SeqMove* mBestMove;
public:
    static void setAltPath(const std::string &pDir);
    /**
     * @brief SeqAlign
     * @param refSeq
     * @param compSeq
     * @throw 540101   SeqAlign::SeqAlign              Sequence cannot have gaps
     * @throw 540102   SeqAlign::SeqAlign              Bad allocation
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     * @throw 540103   SeqAlign::SeqAlign              Reference sequence is empty
     * @throw 540104   SeqAlign::SeqAlign              Compared sequence is empty
     */
    SeqAlign(SeqBase& refSeq, SeqBase& compSeq, const bool& pIsProtein=true);
    ~SeqAlign();
    /**
     * @brief align
     * @param scoreSchema
     * @throw  500401   SeqBase::insertGap              Position above length
     */
    void align(SeqPairAlign& scoreSchema);

    void traceback(SeqPairAlign &scoreSchema) ;

    /**
     * @brief loadMatrix
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     */
    static const SMatrix& getMatrix(const bool &pIsProtein=true);
};
}
#endif // SEQALIGN_H
