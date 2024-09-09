#include <iostream>
#include <cstddef>
#include <fstream>
#include <sstream>
#include<iostream>
#undef NDEBUG
#include <assert.h>
#include "headers/sequence/seqpair.h"
#include "headers/sequence/seqalign.h"


protspace::SeqPairAlign::SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq, const bool &pIsProtein):
    mRefSeq(pRefSeq),
    mCompSeq(pCompSeq),
    mIsProtein(pIsProtein)
{
    mRefPos.reserve(mRefSeq.size());
    mCompPos.reserve(mCompSeq.size());
}


protspace::SeqPairAlign::SeqPairAlign(const SeqPairAlign& pAlign):
    mRefSeq(pAlign.mRefSeq),
    mCompSeq(pAlign.mCompSeq),
    mRefPos(pAlign.mRefPos),
    mCompPos(pAlign.mCompPos),
    mIsProtein(pAlign.mIsProtein)
{

}



protspace::SeqPairAlign::SeqPairAlign(const bool swap,const SeqPairAlign& pAlign):
    mRefSeq((swap)?pAlign.mCompSeq:pAlign.mRefSeq),
    mCompSeq((swap)?pAlign.mRefSeq:pAlign.mCompSeq),
    mRefPos((swap)?pAlign.mCompPos:pAlign.mRefPos),
    mCompPos((swap)?pAlign.mRefPos:pAlign.mCompPos),
    mIsProtein(pAlign.mIsProtein)
{

}


protspace::SeqPairAlign::SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq, std::ifstream& ifs, const bool& pIsProtein):
    mRefSeq(pRefSeq),
    mCompSeq(pCompSeq),
    mIsProtein(pIsProtein)
{
    size_t size;
    ifs.read((char*)&size,sizeof(size));
    int length=0;
    mRefPos.reserve(size);
    mCompPos.reserve(size);
    for(size_t i=0;i<size;++i)
    {

        ifs.read((char*)&length,sizeof(int));
        mRefPos.push_back(length);
    }
    for(size_t i=0;i<size;++i)
    {

        ifs.read((char*)&length,sizeof(int));
        mCompPos.push_back(length);
    }
}




protspace::SeqPairAlign::SeqPairAlign(SeqBase& pRefSeq, SeqBase& pCompSeq,
                                      const std::vector<int> &pRefPos,
                                      const std::vector<int> &pCompPos, const bool &pIsProtein):
    mRefSeq(pRefSeq),
    mCompSeq(pCompSeq),
    mIsProtein(pIsProtein)
{
    size_t pos1Start=1000, pos2Start=1000, pos1End=0,pos2End=0;
    for(size_t k=0;k<pRefPos.size();++k)
    {
        if (pRefPos.at(k)!=-1 && pos1Start==1000) pos1Start=k;
        if (pCompPos.at(k)!=-1 && pos2Start==1000) pos2Start=k;
        if (pRefPos.at(k)!=-1) pos1End=k;
        if (pCompPos.at(k)!=-1) pos2End=k;
    }
    size_t starter=std::min(pos1Start,pos2Start);
    size_t ending=std::max(pos1End,pos2End);
    //            cout <<starter<<" " << ending<<endl;
    for(;starter<=ending;++starter)
    {
        mRefPos.push_back(pRefPos.at(starter));
        mCompPos.push_back(pCompPos.at(starter));
    }
}



double protspace::SeqPairAlign::getScore(const bool& useRef)const
{
    assert(mRefPos.size()==mCompPos.size());
    double score=0,n=0;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        const int& posR=mRefPos.at(i);
        const int& posC=mCompPos.at(i);
        if (posR==-1 || posC==-1)continue;
        n++;
        if (mRefSeq.getLetter(posR)==mCompSeq.getLetter(posC))
            ++score;
    }
    score/=(double)mRefPos.size();
    if (useRef)score*=n/(double)mRefSeq.size();
    else score*=n/(double)mCompSeq.size();
    return score;

}



double protspace::SeqPairAlign::getIdentityCommon()const
{
    assert(mRefPos.size()==mCompPos.size());
    double score=0;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        const int& posR=mRefPos.at(i);
        const int& posC=mCompPos.at(i);
        if (posR==-1 || posC==-1)continue;
        if (mRefSeq.getLetter(posR)==mCompSeq.getLetter(posC))
            ++score;
    }

    return score /(double)(mRefPos.size());

}

double protspace::SeqPairAlign::getIdentity()const
{
    assert(mRefPos.size()==mCompPos.size());
    double score=0;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        const int& posR=mRefPos.at(i);
        const int& posC=mCompPos.at(i);
        if (posR==-1 || posC==-1)continue;
        if (mRefSeq.getLetter(posR)==mCompSeq.getLetter(posC))
            ++score;
    }
    double count=0;
     if (mRefPos.at(0)>= mCompPos.at(0)) count +=mRefPos.at(0);
     else count+=mCompPos.at(0);
     count+=mRefPos.size();

     if (mCompPos.at(mCompPos.size()-1) < (int)mCompSeq.size()-1)
     {
         count+=mCompSeq.size()-mCompPos.at(mCompPos.size()-1)-1;
     }
     if (mRefPos.at(mRefPos.size()-1) <(int) mRefSeq.size()-1)
     {
         count+=mRefSeq.size()-mRefPos.at(mRefPos.size()-1)-1;
     }
    //std::cout <<mCompPos.at(mCompPos.size()-1)<<"/"<<mCompSeq.size()<<"\t"<<mRefPos.at(mRefPos.size()-1)<<"/"<<mRefSeq.size()<<"\t"<<score <<" "<<count<<"\t" <<mRefPos.size()<<std::endl;
    return score /(double)(count);

}



double protspace::SeqPairAlign::getSimilarityCommon()const
{
    assert(mRefPos.size()==mCompPos.size());

    const protspace::SMatrix& matrix=protspace::SeqAlign::getMatrix(mIsProtein);
    double score=0;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        const int& posR=mRefPos.at(i);
        const int& posC=mCompPos.at(i);
        if (posR==-1 || posC==-1)continue;
        if (mRefSeq.getLetter(posR)==mCompSeq.getLetter(posC))
            ++score;
        else if (matrix.getVal(mRefSeq.at(posR),mCompSeq.at(posC))>0)
            ++score;

    }

    return score /(double)(mRefPos.size());

}


double protspace::SeqPairAlign::getSimilarity()const
{
    assert(mRefPos.size()==mCompPos.size());

    const protspace::SMatrix& matrix=protspace::SeqAlign::getMatrix(mIsProtein);
    double score=0;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        const int& posR=mRefPos.at(i);
        const int& posC=mCompPos.at(i);
        if (posR==-1 || posC==-1)continue;
        if (mRefSeq.getLetter(posR)==mCompSeq.getLetter(posC))
            ++score;
        else if (matrix.getVal(mRefSeq.at(posR),mCompSeq.at(posC))>0)
            ++score;

    }
    double count=0;
     if (mRefPos.at(0)>= mCompPos.at(0)) count +=mRefPos.at(0);
     else count+=mCompPos.at(0);
     count+=mRefPos.size();
     if (mCompPos.at(mCompPos.size()-1) < (int)mCompSeq.size()-1)
     {
         count+=mCompSeq.size()-mCompPos.at(mCompPos.size()-1)-1;
     }
     if (mRefPos.at(mRefPos.size()-1) <(int) mRefSeq.size()-1)
     {
         count+=mRefSeq.size()-mRefPos.at(mRefPos.size()-1)-1;
     }
//    std::cout <<score <<" " <<mRefPos.size()<<std::endl;
    return score /(double)(count);

}





void    protspace::SeqPairAlign::addPairToStart(const int& posR, const int& posC)
{
    mRefPos.insert(mRefPos.begin(),posR);
    mCompPos.insert(mCompPos.begin(),posC);
}




void    protspace::SeqPairAlign::addPair(const int& posR, const int& posC)
{
    mRefPos.push_back(posR);
    mCompPos.push_back(posC);
}

std::string protspace::SeqPairAlign::printLine()const
{

    try{
        const protspace::SMatrix& matrix=protspace::SeqAlign::getMatrix(mIsProtein);

        std::ostringstream results1,results2;
        results1 << ">"<<mRefSeq.getName()<< "\n";
        results2 << ">"<<mCompSeq.getName()<<"\n";

        if (mRefPos.at(0)>= mCompPos.at(0))
        {
            for (int i=0;i<mCompPos.at(0);++i)
            {
                results1<< "-";
                results2<<mCompSeq.getLetter(i);
            }
            for (int i=0;i<mRefPos.at(0);++i)
            {
                results1<< mRefSeq.getLetter(i);
                results2<<"-";
            }
            for (size_t iSeq=0; iSeq < mRefPos.size(); iSeq++)
            {
                if (mRefPos.at(iSeq) == -1) results1<< "-";
                else results1 << mRefSeq.getLetter(mRefPos.at(iSeq));

                assert(iSeq < mCompPos.size());

                if (mCompPos.at(iSeq) == -1) results2<< "-";
                else results2  << mCompSeq.getLetter(mCompPos.at(iSeq));
            }

        }
        else
        {
            for (int i=0;i<mRefPos.at(0);++i)
            {
                results1<< mRefSeq.getLetter(i);
                results2<<"-";
            }
            for (int i=0;i<mCompPos.at(0);++i)
            {
                results1<< "-";
                results2<< mCompSeq.getLetter(i);
            }
            for (size_t iSeq=0; iSeq < mCompPos.size(); iSeq++)
            {

                if (mRefPos.at(iSeq) == -1) results1<< "-";
                else results1 <<mRefSeq.getLetter(mRefPos.at(iSeq));

                assert(iSeq < mCompPos.size());

                if (mCompPos.at(iSeq) == -1) results2<< "-";
                else results2  << mCompSeq.getLetter(mCompPos.at(iSeq));

            }

        }
//        std::cout <<"STOP "<< mCompPos.at(mCompPos.size()-1)<<"::"<<mCompSeq.size()<<std::endl;
//        std::cout <<"STOP "<< mRefPos.at(mRefPos.size()-1)<<"::"<<mRefSeq.size()<<std::endl;
        if (mCompPos.at(mCompPos.size()-1) < (int)mCompSeq.size())
        {
            for (int i=mCompPos.at(mCompPos.size()-1)+1;i<(int)mCompSeq.size();++i)
            {

                results1<<"-";
                results2<<mCompSeq.getLetter(i);
            }
        }
        if (mRefPos.at(mRefPos.size()-1) <(int) mRefSeq.size())
        {
            for (int i=mRefPos.at(mRefPos.size()-1)+1;i<(int)mRefSeq.size();++i)
            {

                results1<< mRefSeq.getLetter(i);
                results2<<"-";
            }
        }
        return results1.str()+"\n"+results2.str();
    }catch(ProtExcept &e)
    {
        return e.toString();
    }
}

std::string protspace::SeqPairAlign::printSeqAlign(const bool& wColor)const
{

    try{
        if (mRefPos.empty() ||mCompPos.empty())return "";
        const protspace::SMatrix& matrix=protspace::SeqAlign::getMatrix();

        int maxV=0,lastRSeq=-2, lastCSeq=-2;size_t start_r, start_c;
        for(size_t i=0;i<mRefSeq.len();++i)
            if (mRefSeq.posToId(i)>maxV)maxV=mRefSeq.posToId(i);
        for(size_t i=0;i<mCompSeq.len();++i)
            if (mCompSeq.posToId(i)>maxV)maxV=mCompSeq.posToId(i);
        const size_t maxIDLen(std::to_string(maxV).length());


        for(size_t j=0;j<mRefPos.size();++j)
            if (mRefPos.at(j)!=-1){lastRSeq=mRefSeq.posToId(mRefPos.at(j));
                start_r=mRefPos.at(j);break;}
        for(size_t j=0;j<mCompPos.size();++j)
            if (mCompPos.at(j)!=-1){lastCSeq=mCompSeq.posToId(mCompPos.at(j));
                start_c=mCompPos.at(j);break;}

        size_t countStep=0;

        /// Creation of headers for reference and comparison
        /// So they both have the same length
        std::string headR=mRefSeq.getName();
        std::string headC=mCompSeq.getName();
        if (headR.length()>headC.length())
            for(size_t i=headC.length();i<headR.length();++i)headC+=" ";
        else if (headR.length()<headC.length())
            for(size_t i=headR.length();i<headC.length();++i)headR+=" ";
        const size_t lenHead(headR.length());

        std::ostringstream resultR,resultC,comparison,all;

        ///Loading the headers
        lastRSeq=mRefSeq.posToId(0);
        lastCSeq=mCompSeq.posToId(0);


        /// Filling the reference part of the sequence that is not covered by the alignment
        for(size_t i=0;i<start_r;++i)
        {
            lastRSeq=mRefSeq.posToId(i);
            if (countStep==0 || i==0)
            {

                size_t lenR(std::to_string(lastRSeq).length());
                size_t lenCR(std::to_string(lastCSeq).length());
                resultR<<headR<<" "<<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                resultC<<headC<<" "<<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";

            }
            resultR<< mRefSeq.getLetter(i);
            resultC<<mCompSeq.getGapChar();
            comparison<<" ";
            ++countStep;

            if (countStep==50)
            {
                countStep=0;
                all<<resultR.str()<<" "<<lastRSeq<<"\n"
                  <<resultC.str()<<" "<<lastCSeq<<"\n"
                   <<comparison.str()<<"\n\n";
                resultR.str("");
                resultC.str("");
                comparison.str("");
            }
            if (countStep%10==0 && countStep>0)
            {
                resultR<<" ";
                comparison<<" " ;
                resultC<<" ";
            }
        }
//        resultR<<"/";
//        comparison<<"/" ;
//        resultC<<"/";
        /// Filling the comparison part of the sequence that is not covered by the alignment
        for(size_t i=0;i<start_c;++i)
        {
            lastCSeq=mCompSeq.posToId(i);
            if (countStep==0 || i==0)
            {

                size_t lenR(std::to_string(lastRSeq).length());
                size_t lenCR(std::to_string(lastCSeq).length());
                resultR<<headR<<" "<<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                resultC<<headC<<" "<<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";

            }
            resultR<< mRefSeq.getGapChar();
            resultC<<mCompSeq.getLetter(i);
            comparison<<" ";
            ++countStep;
            if (countStep==50)
            {
                countStep=0;
                all<<resultR.str()<<" "<<lastRSeq<<"\n"
                  <<resultC.str()<<" "<<lastCSeq<<"\n"
                   <<comparison.str()<<"\n\n";
                resultR.str("");
                resultC.str("");
                comparison.str("");
            }
            if (countStep%10==0 && countStep>0)
            {
                resultR<<" ";
                comparison<<" " ;
                resultC<<" ";
            }

        }

        if (countStep==0 )
        {

            size_t lenR(std::to_string(lastRSeq).length());
            size_t lenCR(std::to_string(lastCSeq).length());
            resultR<<headR<<" "<<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
            resultC<<headC<<" "<<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
            for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";

        }
//        resultR<<"/";
//        comparison<<"/" ;
//        resultC<<"/";
        int last_r_pos=0,last_c_pos=0;int prev_r_pos=lastRSeq,prev_c_pos=lastCSeq;
        /// Now we print the alignment
        for (size_t iSeq=0; iSeq < mRefPos.size(); iSeq++)
        {
            const int& currRefSeqPos=mRefPos.at(iSeq);
            const int& currCompSeqPos=mCompPos.at(iSeq);

            const bool isRGap(currRefSeqPos==-1);
            const bool isCGap(currCompSeqPos==-1);

            if (!isRGap && currRefSeqPos> prev_r_pos+1)
            {
//                resultR<<"_";resultC<<"_";comparison<<"_";
                for(size_t i=prev_r_pos+1;i<mRefPos.at(iSeq);++i)
                {
                    lastRSeq=mRefSeq.posToId(i);
                    resultR<<mRefSeq.getLetter(i);
                    resultC<<mCompSeq.getGapChar();
                    comparison<<" ";
                    countStep++;
                    if (countStep==50)
                    {
                        countStep=0;
                        all<<resultR.str()<<" "<<lastRSeq<<"\n"
                           <<resultC.str()<<" "<<lastCSeq<<"\n"
                           <<comparison.str()<<"\n\n";
                        resultR.str("");
                        resultC.str("");
                        comparison.str("");
                    }
                    if (countStep%10==0&& countStep>0)
                    {
                        resultR<<" ";
                        comparison<<" " ;
                        resultC<<" ";
                    }
                    if (countStep==0)
                    {
                        size_t lenR(std::to_string(lastRSeq).length());
                        size_t lenCR(std::to_string(lastCSeq).length());
                        resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                        resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                        for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
                    }
                }
//                resultR<<"_";resultC<<"_";comparison<<"_";
            }
            else if (!isCGap && currCompSeqPos> prev_c_pos+1)
            {
//                resultR<<"/";resultC<<"/";comparison<<"/";
                for(size_t i=prev_c_pos+1;i<mCompPos.at(iSeq);++i)
                {
                    lastCSeq=mCompSeq.posToId(i);
                    resultR<<mRefSeq.getGapChar();
                    resultC<<mCompSeq.getLetter(i);
                    comparison<<" ";
                    countStep++;
                    if (countStep==50)
                    {
                        countStep=0;
                        all<<resultR.str()<<" "<<lastRSeq<<"\n"
                          <<resultC.str()<<" "<<lastCSeq<<"\n"
                         <<comparison.str()<<"\n\n";
                        resultR.str("");
                        resultC.str("");
                        comparison.str("");
                    }
                    if (countStep%10==0&& countStep>0)
                    {
                        resultR<<" ";
                        comparison<<" " ;
                        resultC<<" ";
                    }
                    if (countStep==0)
                    {
                        size_t lenR(std::to_string(lastRSeq).length());
                        size_t lenCR(std::to_string(lastCSeq).length());
                        resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                        resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                        for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
                    }
                }
//                resultR<<"/";resultC<<"/";comparison<<"/";
            }
            if (isRGap)resultR<<mRefSeq.getGapChar();
            else
            {
                prev_r_pos=currRefSeqPos;
                resultR<< mRefSeq.getLetter(currRefSeqPos);
                last_r_pos=currRefSeqPos;
                lastRSeq=mRefSeq.posToId(currRefSeqPos);
            }
            if (isCGap)resultC<<mCompSeq.getGapChar();
            else
            {
                prev_c_pos=currCompSeqPos;
                resultC<< mCompSeq.getLetter(currCompSeqPos);
                last_c_pos=currCompSeqPos;
                lastCSeq=mCompSeq.posToId(currCompSeqPos);
            }
            if (!isRGap && !isCGap)
            {
                if (mCompSeq.getLetter(currCompSeqPos)
                        == mRefSeq.getLetter(currRefSeqPos))comparison<<"*";
                else if (matrix.getVal(mCompSeq.at(currCompSeqPos),
                                       mRefSeq.at(currRefSeqPos))>0)
                    comparison<<((wColor)?"\033[1;41m":"")<<":"<<((wColor)?"\033[0m":"");
                else if (wColor)comparison<<"\033[1;41m \033[0m";
                else comparison<<" " ;;
            }else comparison<<" ";
            ++countStep;
            if (countStep==50)
            {
                countStep=0;
                all<<resultR.str()<<" "<<lastRSeq<<"\n"
                  <<resultC.str()<<" "<<lastCSeq<<"\n"
                 <<comparison.str()<<"\n\n";
                resultR.str("");
                resultC.str("");
                comparison.str("");
            }
            if (countStep%10==0&& countStep>0)
            {
                resultR<<" ";
                comparison<<" " ;
                resultC<<" ";
            }
            if (countStep==0)
            {
                size_t lenR(std::to_string(lastRSeq).length());
                size_t lenCR(std::to_string(lastCSeq).length());
                resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
            }


//            if (mRefPos.at(iSeq) == -1) resultR<<mRefSeq.getGapChar();
//            else
//            {
//                lastRSeq=mRefSeq.posToId(mRefPos.at(iSeq));
//                if (mRefPos.at(iSeq) > prev_r_pos+1)
//                {
//                    std::cout <<"DIFF:"<<mRefPos.at(iSeq)<<" " <<prev_r_pos<<std::endl;
//                    resultR<<"_";resultC<<"_";comparison<<"_";
//                    for(size_t i=prev_r_pos+1;i<mRefPos.at(iSeq);++i)
//                    {
//                        resultR<<mRefSeq.getLetter(i);
//                        resultC<<mCompSeq.getGapChar();
//                        comparison<<" ";
//                        countStep++;
//                        if (countStep==50)
//                        {
//                            countStep=0;
//                            all<<resultR.str()<<" "<<lastRSeq<<"\n"
//                               <<resultC.str()<<" "<<lastCSeq<<"\n"
//                               <<comparison.str()<<"\n\n";
//                            resultR.str("");
//                            resultC.str("");
//                            comparison.str("");
//                        }
//                        if (countStep%10==0&& countStep>0)
//                        {
//                            resultR<<" ";
//                            comparison<<" " ;
//                            resultC<<" ";
//                        }
//                        if (countStep==0)
//                        {
//                            size_t lenR(std::to_string(lastRSeq).length());
//                            size_t lenCR(std::to_string(lastCSeq).length());
//                            resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
//                            resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
//                            for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
//                        }
//                    }
//                    resultR<<"_";resultC<<"_";comparison<<"_";
//                }
//                prev_r_pos=mRefPos.at(iSeq);

//                resultR<< mRefSeq.getLetter(mRefPos.at(iSeq));
//                last_r_pos=mRefPos.at(iSeq);
//            }

//            assert(iSeq < mCompPos.size());

//            if (mCompPos.at(iSeq) == -1) resultC<< mCompSeq.getGapChar();
//            else
//            {
//                lastCSeq=mCompSeq.posToId(mCompPos.at(iSeq));
//                if (mCompPos.at(iSeq) > prev_c_pos+1)
//                {
//                    std::cout <<"DIFF:"<<mCompPos.at(iSeq)<<" " <<prev_c_pos<<std::endl;
//                    resultR<<"/";resultC<<"/";comparison<<"/";
//                    for(size_t i=prev_c_pos+1;i<mCompPos.at(iSeq);++i)
//                    {
//                        resultR<<mRefSeq.getGapChar();
//                        resultC<<mCompSeq.getLetter(i);
//                        comparison<<" ";
//                        countStep++;
//                        if (countStep==50)
//                        {
//                            countStep=0;
//                            all<<resultR.str()<<" "<<lastRSeq<<"\n"
//                               <<resultC.str()<<" "<<lastCSeq<<"\n"
//                               <<comparison.str()<<"\n\n";
//                            resultR.str("");
//                            resultC.str("");
//                            comparison.str("");
//                        }
//                        if (countStep%10==0&& countStep>0)
//                        {
//                            resultR<<" ";
//                            comparison<<" " ;
//                            resultC<<" ";
//                        }
//                        if (countStep==0)
//                        {
//                            size_t lenR(std::to_string(lastRSeq).length());
//                            size_t lenCR(std::to_string(lastCSeq).length());
//                            resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
//                            resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
//                            for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
//                        }
//                    }
//                    resultR<<"/";resultC<<"/";comparison<<"/";
//                }
//                prev_c_pos=mCompPos.at(iSeq);

//                resultC  << mCompSeq.getLetter(mCompPos.at(iSeq));
//                last_c_pos=mCompPos.at(iSeq);
//            }
//            if (mRefPos.at(iSeq)!=-1 && mCompPos.at(iSeq)!=-1)
//            {
//                if (mCompSeq.getLetter(mCompPos.at(iSeq))
//                  == mRefSeq.getLetter(mRefPos.at(iSeq)))comparison<<"*";
//                else if (matrix.getVal(mCompSeq.at(mCompPos.at(iSeq)),
//                                       mRefSeq.at(mRefPos.at(iSeq)))>0)
//                 comparison<<((wColor)?"\033[1;41m":"")<<":"<<((wColor)?"\033[0m":"");
//                else if (wColor)comparison<<"\033[1;41m \033[0m";
//                else comparison<<" " ;;
//            }else comparison<<" ";
//            ++countStep;
//            if (countStep==50)
//            {
//                countStep=0;
//                all<<resultR.str()<<" "<<lastRSeq<<"\n"
//                   <<resultC.str()<<" "<<lastCSeq<<"\n"
//                   <<comparison.str()<<"\n\n";
//                resultR.str("");
//                resultC.str("");
//                comparison.str("");
//            }
//            if (countStep%10==0&& countStep>0)
//            {
//                resultR<<" ";
//                comparison<<" " ;
//                resultC<<" ";
//            }
//            if (countStep==0)
//            {
//                size_t lenR(std::to_string(lastRSeq).length());
//                size_t lenCR(std::to_string(lastCSeq).length());
//                resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
//                resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
//                for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"]";
//            }
        }


        /// Now we print the end of the reference sequence not covered by the alignment

            for (int i=last_c_pos+1;i<(int)mCompSeq.size();++i)
            {
                resultR<<mRefSeq.getGapChar();
                resultC<<mCompSeq.getLetter(i);
                lastCSeq=mCompSeq.posToId(i);
                comparison<<" ";
                ++countStep;
                if (countStep==50)
                {
                    countStep=0;
                    all<<resultR.str()<<" "<<lastRSeq<<"\n"
                      <<resultC.str()<<" " <<lastCSeq<<"\n"
                       <<comparison.str()<<"\n\n";
                    resultR.str("");
                    resultC.str("");
                    comparison.str("");
                }
                if (countStep%10==0 && countStep>0)
                {
                    resultR<<" ";
                    comparison<<" " ;
                    resultC<<" ";
                }
                if (countStep==0)
                {
                    size_t lenR(std::to_string(lastRSeq).length());
                    size_t lenCR(std::to_string(lastCSeq).length());
                    resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                    resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                    for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"|";
                }
            }


            for (int i=last_r_pos+1;i<(int)mRefSeq.size();++i)
            {

                resultR << mRefSeq.getLetter(i);
                resultC<<mCompSeq.getGapChar();
                lastRSeq=mRefSeq.posToId(i);
                comparison<<" ";
                ++countStep;
                if (countStep==50)
                {
                    countStep=0;
                    all<<resultR.str()<<" "<<lastRSeq<<"\n"
                      <<resultC.str()<<" " <<lastCSeq<<"\n"
                       <<comparison.str()<<"\n\n";
                    resultR.str("");
                    resultC.str("");
                    comparison.str("");
                }
                if (countStep%10==0)
                {
                    resultR<<" ";
                    comparison<<" " ;
                    resultC<<" ";
                }

                if (countStep==0)
                {
                    size_t lenR(std::to_string(lastRSeq).length());
                    size_t lenCR(std::to_string(lastCSeq).length());
                    resultR<<headR<<" " <<lastRSeq; for(size_t j=lenR;j<=maxIDLen;++j)resultR<<" ";
                    resultC<<headC<<" " <<lastCSeq; for(size_t j=lenCR;j<=maxIDLen;++j)resultC<<" ";
                    for(size_t j=0;j<=maxIDLen+lenHead+1;++j)comparison<<" ";//comparison<<"/";
                }

        }
        all<<resultR.str()<<" "<<lastRSeq<<"\n"
          <<resultC.str()<<" " <<lastCSeq<<"\n"
           <<comparison.str()<<"\n\n";
        return all.str();

    }catch(ProtExcept &e)
    {
        return e.toString();
    }
}



std::string protspace::SeqPairAlign::printAlignment()const
{

    try{
        const protspace::SMatrix& matrix=protspace::SeqAlign::getMatrix();

        std::ostringstream results;
        results << mRefPos.at(0)<< " " <<  mCompPos.at(0)<<std::endl;
        results <<"|------\tREF\t-----|\t|------\tCOMP\t-----|\n"
               <<"POS_SEQ\tID\tRES\t\tRES\tPOS_SEQ\tID\n";
        // cout << mRefPos.size()<< " " <<mCompPos.size()<< " " << mSimilarity.size()<<endl;
        if (mRefPos.at(0)>= mCompPos.at(0))
        {
            for (int i=0;i<mRefPos.at(0);++i)
            {
                results  <<i<<"\t"
                        << mRefSeq.posToId(i)<<"\t"
                        << mRefSeq.getLetter(i)<<"\t";
                results<< "/\t/\t/\t"
                       << "\n";
            }
            for (size_t iSeq=0; iSeq < mRefPos.size(); iSeq++)
            {
                if (mRefPos.at(iSeq) == -1) results<< "/\t/\t/\t";
                else results << mRefPos.at(iSeq)<<"\t"
                             << mRefSeq.posToId(mRefPos.at(iSeq))<<"\t"
                             << mRefSeq.getLetter(mRefPos.at(iSeq))<<"\t";

                assert(iSeq < mCompPos.size());

                if (mCompPos.at(iSeq) == -1) results<< "/\t/\t/";
                else results  << mCompSeq.getLetter(mCompPos.at(iSeq))<<"\t"
                              << mCompSeq.posToId(mCompPos.at(iSeq))<<"\t"
                              << mCompPos.at(iSeq);
                if (mCompPos.at(iSeq) != -1 && mRefPos.at(iSeq) != -1)
                {
                    if (mCompPos.at(iSeq) == mRefPos.at(iSeq))results<<"\t|";
                    else if (matrix.getVal(mCompSeq.at(mCompPos.at(iSeq)),
                                           mRefSeq.at(mRefPos.at(iSeq)))>0)results<<"\t*";
                    else results<<"\t_";
                }
                results<<"\n";
            }

        }
        else
        {
            for (int i=0;i<mCompPos.at(0);++i)
            {
                results<< "/\t/\t/\t";
                results  << mCompSeq.getLetter(i)<<"\t"
                         << mCompSeq.posToId(i)<<"\t"
                         << "/\n";
            }
            for (size_t iSeq=0; iSeq < mCompPos.size(); iSeq++)
            {

                if (mRefPos.at(iSeq) == -1) results<< "/\t/\t/\t";
                else results << mRefPos.at(iSeq)<<"\t"
                             << mRefSeq.posToId(mRefPos.at(iSeq))<<"\t"
                             << mRefSeq.getLetter(mRefPos.at(iSeq))<<"\t";

                assert(iSeq < mCompPos.size());

                if (mCompPos.at(iSeq) == -1) results<< "/\t/\t/";
                else results  << mCompSeq.getLetter(mCompPos.at(iSeq))<<"\t"
                              << mCompSeq.posToId(mCompPos.at(iSeq))<<"\t"
                              << mCompPos.at(iSeq);
                if (mCompPos.at(iSeq) != -1 && mRefPos.at(iSeq) != -1)
                {
                    if (mCompPos.at(iSeq) == mRefPos.at(iSeq))results<<"\t|";
                    else if (matrix.getVal(mCompSeq.at(mCompPos.at(iSeq)),
                                           mRefSeq.at(mRefPos.at(iSeq)))>0)results<<"\t*";
                    else results <<"\t_";
                }
                results<<"\n";
            }

        }
//        std::cout <<"STOP "<< mCompPos.at(mCompPos.size()-1)<<"::"<<mCompSeq.size()<<std::endl;
//        std::cout <<"STOP "<< mRefPos.at(mRefPos.size()-1)<<"::"<<mRefSeq.size()<<std::endl;
        if (mCompPos.at(mCompPos.size()-1) < (int)mCompSeq.size())
        {
            for (int i=mCompPos.at(mCompPos.size()-1)+1;i<(int)mCompSeq.size();++i)
            {

                results  << "/\t/\t/\t"<<mCompSeq.getLetter(i)<<"\t"
                         << mCompSeq.posToId(i)<<"\t"
                         << i<<"\n";
            }
        }
        if (mRefPos.at(mRefPos.size()-1) <(int) mRefSeq.size())
        {
            for (int i=mRefPos.at(mRefPos.size()-1)+1;i<(int)mRefSeq.size();++i)
            {

                results  << i<<"\t"<<mRefSeq.posToId(i)<<"\t"
                         << mRefSeq.getLetter(i)<<"\t"
                         << "/\t/\t/\n";
            }
        }
        return results.str();
    }catch(ProtExcept &e)
    {
        return e.toString();
    }
}





void protspace::SeqPairAlign::serialize(std::ofstream& ofs)
{

    size_t size=mRefPos.size();
    ofs.write((char*)&size,sizeof(size));
    int d;
    for(size_t i=0;i<mRefPos.size();++i)
    {
        d=mRefPos.at(i);
        ofs.write((char*)&d,sizeof(int));
    }
    for(size_t i=0;i<mRefPos.size();++i)
    {
        d=mCompPos.at(i);
        ofs.write((char*)&d,sizeof(int));
    }
}






void protspace::SeqPairAlign::projectionToRef(std::vector<protspace::MMResidue*>& mappedRes,
                                              std::vector<int>& mappedResId)const
{

    mappedRes.clear();
    mappedResId.clear();
    const std::string StrSeq(mRefSeq.toString());
    size_t posStr=0;
    std::cout <<StrSeq<<std::endl;
    for(size_t iS=0;iS< mRefPos.size();++iS)
    {
        std::cout <<iS<<"::"<<mRefPos.at(iS);
        if (mRefPos.at(iS)==-1)
        {std::cout<<std::endl;
            mappedResId.push_back(-1);
            mappedRes.push_back(nullptr);
            continue;
        }
        std::cout << "::"<<mRefSeq.getLetter(mRefPos.at(iS))<<"\t"<<posStr<<"::"<<StrSeq.at(posStr);
        ++posStr;
        while(StrSeq.at(posStr)!=mRefSeq.getLetter(mRefPos.at(iS)))
        {
            mappedResId.push_back(-1);
            mappedRes.push_back(nullptr);

            ++posStr;
        }
        //            cout << "::"<<seq.getLetter(refpos.at(iS))<<"::"<<posStr<<"::"<<StrSeq.at(posStr)<<"\t";
        //            cout<< compos.at(iS);
        if (mCompPos.at(iS)==-1){
            mappedResId.push_back(-1);
            mappedRes.push_back(nullptr);//cout<<endl;
            continue;
        }
        mappedResId.push_back(mCompPos.at(iS));
        //            cout <<"::"<<seqChain.getLetter(compos.at(iS))<<endl;
        mappedRes.push_back(&mCompSeq.getResidue(mCompPos.at(iS)));

    }
}



void protspace::SeqPairAlign::merge(
        protspace::SeqPairAlign &pLeft,
        protspace::SeqPairAlign &pRight)
{
    if (&pLeft.mRefSeq!=&mRefSeq )
        throw_line("550101","SeqPairAlign::merge",
                   "Reference sequence in results must be reference sequence in left Pair");
    if (&pRight.mCompSeq!=&mCompSeq)
        throw_line("550102","SeqPairAlign::merge",
                   "Compared sequence in results must be Compared sequence in right Pair");
    if (&pLeft.mCompSeq!=&pRight.mRefSeq)
        throw_line("550103","SeqPairAlign::merge",
                   "Compared sequence in left side is different than ref sequence in right side");
    const std::vector<int>& LR=pLeft.mRefPos;
    const std::vector<int>& LC=pLeft.mCompPos;
    const std::vector<int>& RR=pRight.mRefPos;
    const std::vector<int>& RC=pRight.mCompPos;

    size_t LC_P=0, RR_P=0;
    const size_t LC_size(LC.size());
    const size_t RR_size(RR.size());
    if (LC.at(LC_P)<RR.at(RR_P))
    {
        for(;LC_P<LC_size;++LC_P)
        {
            mRefPos.push_back(LR.at(LC_P));
            mCompPos.push_back(-1);
            if (LC.at(LC_P)==RR.at(RR_P))break;
        }
    }else if (LC.at(LC_P)> RR.at(RR_P))
    {

        for(;RR_P<RR_size;++RR_P)
        {
            mRefPos.push_back(-1);
            mCompPos.push_back(RC.at(RR_P));
            if (LC.at(LC_P)==RR.at(RR_P))break;
        }
    }

    do
    {
        LC_P++;
        RR_P++;
        if (LC_P>=LC_size || RR_P>=RR_size)break;
        int LC_v=LC.at(LC_P);
        int RR_v=RR.at(RR_P);
        if (LC_v == RR_v)
        {
            mRefPos.push_back(LR.at(LC_P));
            mCompPos.push_back(RC.at(RR_P));
        }
        else if (LC_v < RR_v)
        {
            do
            {
                mRefPos.push_back(LR.at(LC_P));
                mCompPos.push_back(-1);
                LC_P++;
                if (LC_P >= LC_size)break;
                LC_v=LC.at(LC_P);
            }while (LC_v != RR_v);

        }
        else if (LC_v > RR_v)
        {
            do
            {
                mRefPos.push_back(-1);
                mCompPos.push_back(RC.at(RR_P));
                RR_P++;
                if (RR_P >=RR_size)break;
                RR_v=RR.at(RR_P);
            }while (LC_v != RR_v);
        }

    }while(LC_P<LC_size && RR_P<RR_size);

}

int protspace::SeqPairAlign::getCompPosFromRefPos(const int &pRefPos)const
{
    for(size_t i=0;i<mRefPos.size();++i)
        if (mRefPos.at(i)==pRefPos)return mCompPos.at(i);
    return -1;
}
int protspace::SeqPairAlign::getRefPosFromCompPos(const int &pRefPos)const
{
    for(size_t i=0;i<mCompPos.size();++i)
        if (mCompPos.at(i)==pRefPos)return mRefPos.at(i);
    return -1;
}
