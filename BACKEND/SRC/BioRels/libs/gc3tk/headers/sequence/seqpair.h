#ifndef SEQPAIR_H
#define SEQPAIR_H

#include "headers/sequence/seqbase.h"
namespace protspace
{
class SeqPairAlign
{
protected:
    SeqBase& mRefSeq;
    SeqBase& mCompSeq;
    std::vector<int> mRefPos;
    std::vector<int> mCompPos;
    bool mIsProtein;

public:
    SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq, const bool& pIsProtein=true);
    SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq, std::ifstream& ifs, const bool& pIsProtein=true);
    SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq,
                 const std::vector<int>& pRefPos,
                 const std::vector<int>& pCompPos, const bool& pIsProtein=true);
    SeqPairAlign(const SeqPairAlign& pAlign);
    SeqPairAlign(const bool swap,const SeqPairAlign& pAlign);

    const SeqBase& getRefSeq()const{return mRefSeq;}
    const SeqBase& getCompSeq()const{return mCompSeq;}
    void    addPairToStart(const int& posR, const int& posC);
    void    addPair(const int& posR, const int& posC);

    std::string printAlignment() const;
    double getIdentity()const;

    const std::vector<int>& getRefPosVector()const {return mRefPos;}
    const std::vector<int>& getCompPosVector()const {return mCompPos;}

    void serialize(std::ofstream& ofs);

    std::string printSeqAlign(const bool &wColor=false)const;

    void projectionToRef(std::vector<protspace::MMResidue*>& mappedRes,
                         std::vector<int>& mappedResId)const;


    double getScore(const bool& useRef=true)const;

    /**
     * @brief getSimilarity
     * @return
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     */
    double getSimilarity() const;
    double getSimilarityCommon() const;
    double getIdentityCommon() const;

    /**
     * @brief merge
     * @param pLeft
     * @param pRight
     * @throw 550101   SeqPairAlign::merge             Reference sequence in results must be reference sequence in left Pair
     * @throw 550102   SeqPairAlign::merge             Compared sequence in left side is different than ref sequence in right side
     * @throw 550103   SeqPairAlign::merge             Compared sequence in results must be Compared sequence in right Pair
     */
    void merge(protspace::SeqPairAlign& pLeft,
               protspace::SeqPairAlign& pRight);

    /**
     * @brief Given a position in the reference sequence, give me the position in the compared sequence
     * @param pRefPos
     * @return
     */
    int getCompPosFromRefPos(const int& pRefPos) const;
    int getRefPosFromCompPos(const int &pRefPos) const;
    std::string printLine() const;
};
}
#endif // SEQPAIR_H

