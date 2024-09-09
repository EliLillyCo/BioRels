#include <iostream>
#include "headers/parser/readerbase.h"
#include "headers/statics/strutils.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/statics/protpool.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;

bool ReaderBase::mForceResCheck=false;

void ReaderBase::setForceResCheck(const bool& val){mForceResCheck=val;}

ReaderBase::ReaderBase(const std::string& path)
    :mIsEOF(false),
     mFposition(0),
     mFilePath(path),
     mLigne(protspace::ProtPool::Instance().string.acquireObject(mLignePos))
{

}



void ReaderBase::cleaning()
{
    mIsEOF=false;
    mFposition=0;
    mListChainsRule.clear();

}


ReaderBase::~ReaderBase()
{
    try{
    protspace::ProtPool::Instance().string.releaseObject(mLignePos);
    if (mIfs.is_open()) mIfs.close();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("ReaderBase::~ReaderBase");
        throw;
    }
}

void ReaderBase::close()
{
    mIfs.close();
}




void ReaderBase::open(const std::string&path_f)  throw(ProtExcept)
{
    cleaning();
    if (!path_f.empty()) mFilePath=path_f;

    const size_t pos = mFilePath.find_last_of(":");

    if (pos!=string::npos
#ifdef _WIN32
        && pos != 1
#endif
            ) getChainRules();

    // Closing an already opened file, if so:
    if (mIfs.is_open()) mIfs.close();

    if (mFilePath.empty())
        throw_line("400101",
                         "ReaderBase::open",
                         "No file given");


    // Opening file:
    mIfs.open(mFilePath.c_str(),std::ios::binary);
    if (!mIfs.is_open())
        throw_line("400102",
                         "ReaderBase::open",
                         "Unable to open file "+mFilePath);

}


size_t ReaderBase::getFilePos()
{
    if (mIfs.is_open()) return mIfs.tellg();
    else  return mFposition;

}


bool ReaderBase::getLine()
{
    if (mIfs.eof())
    {
        mIsEOF=true;
        return false;
    }
    safeGetline(mIfs,mLigne);

    return true;

}

bool ReaderBase::getLine(std::string& pLine)
{
    if (mIfs.eof())
    {
        mIsEOF=true;
        return false;
    }
    safeGetline(mIfs,pLine);

    return true;
}


void ReaderBase::getChainRules()
{
    std::vector<string> toks;
    tokenStr(mFilePath,toks,":");
    mFilePath = toks.at(0);
    for(size_t i=1;i<toks.size();++i)
    {
        chainRule cr;
        cr.mChainName=toks.at(i);
        mListChainsRule.push_back(cr);
    }
}



void ReaderBase::setFilterChain(const std::string& list)
{

    for(size_t i=0;i<list.size();++i)
    {
        chainRule cr;
        cr.mChainName=list.substr(i,1);
        mListChainsRule.push_back(cr);
    }
}


bool ReaderBase::checkChainRule(const std::string& pChain)const
{
    if (mListChainsRule.empty())return true;
    for(size_t i=0;i<mListChainsRule.size();++i)
        if (mListChainsRule.at(i).mChainName==pChain) return true;
    return false;
}

void ReaderBase::correctName(std::string &pName) const
{
    pName.erase(std::remove(pName.begin(),pName.end(),'_'),pName.end());


}

void ReaderBase::to_begin()
{
    mIfs.seekg(0,std::ios::beg);
}
