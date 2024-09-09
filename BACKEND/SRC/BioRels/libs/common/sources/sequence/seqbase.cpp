#include <sstream>
#undef NDEBUG /// Active assertion in release
#include <assert.h>
#include "headers/sequence/seqbase.h"
const std::string protspace::SeqBase::mAASeq="LAGVESIDKRTPNQFYHMCWXBZ*UO";
const unsigned char protspace::SeqBase::mAALen=(unsigned char)mAASeq.length();
const std::string protspace::SeqBase::mNuclSeq="ATGCSWRYKMBVHDN*";
const unsigned char protspace::SeqBase::mNuclLen=(unsigned char)mNuclSeq.length();

protspace::SeqBase::~SeqBase()
{

}

protspace::SeqBase::SeqBase(const std::string& name,
                                   const size_t& len,
                                   const char& gapChar):
    mName(name),
    mGapChar(gapChar)
{
    mSeq.reserve(len);
}


std::string protspace::SeqBase::toFastaString() const
{
    std::ostringstream oss;
    oss <<">"<<mName<<"\n";
    for(size_t i=0;i< mSeq.size();++i)
    {
        oss<< getLetter(i);
    }
    return oss.str();
}

std::string protspace::SeqBase::toString() const
{

    std::ostringstream oss;

    for(size_t i=0;i< mSeq.size();++i)
    {
        oss<< getLetter(i);
    }
    return oss.str();
}

size_t protspace::SeqBase::ungapped_length() const {
    size_t nNoGap=0;
    for(const unsigned char& val:mSeq)
    {
        if (val != mAALen)  nNoGap++;
    }
    return nNoGap;
}

std::string protspace::SeqBase::ungapped_sequence() const {
    std::ostringstream oss;

    for(const unsigned char& val:mSeq)
    {
        if (val != mAALen) oss<<getLetter(val);
    }
    return oss.str();
}
bool  protspace::SeqBase::is_gap(const size_t& pos ) const {
    if ( pos < 1 || pos > mSeq.size() ) return true;
    return ( getLetter(pos)== mGapChar );
}
bool protspace::SeqBase::hasAA()const
{
    for(size_t i=0;i<mSeq.size();++i)
    {   const char& l=getLetter(i);
        if (l!=mGapChar && l!='X')return true;
    }
    return false;
}

bool protspace::SeqBase::hasGap()const
{
    for (size_t i=0;i<mSeq.size();++i) {
        const char& l=getLetter(i);
        if (l== mGapChar)return true;
    }
    return false;
}

char protspace::SeqBase::operator[](const size_t& pos ) const {
    assert(pos <=mSeq.size());
    return getLetter(pos);
}



void protspace::SeqBase::insert(const size_t& pos, const char new_char ) {
    assert(pos < mSeq.size());
    mSeq.insert(mSeq.begin()+pos,getPos(new_char));
}



void protspace::SeqBase::remove( const size_t& pos ) {
assert(pos < mSeq.size());
    mSeq.erase(mSeq.begin()+pos);
}



void protspace::SeqBase::insertGap(const size_t& pos ) {
    if(pos >= mSeq.size())
            throw_line("500401","SeqBase::insertGap",
                       "Position above length "+std::to_string(pos)+"/"+
                       std::to_string(mSeq.size()));
    mSeq.insert(mSeq.begin()+pos,(unsigned char)mAASeq.length());
}



void protspace::SeqBase::push_back(const char& val) {
    mSeq.push_back(getPos(val));
}

void protspace::SeqBase::replace(const size_t &pos, const char &letter)
{
    const size_t posLetter = mAASeq.find(letter);
    assert(pos < mSeq.size());
    mSeq[pos]=(unsigned char)posLetter;
}


void protspace::SeqBase::push_back_gap() {
    mSeq.push_back((unsigned char)mAASeq.length());
}

unsigned char protspace::SeqBase::getPos(const std::string& entry)const
{
    const size_t pos(mAASeq.find(entry));
    if (pos == std::string::npos)
        throw_line("500101",
                   "SeqBase::getPos",
                   "Unrecognized character "+entry);
    return (unsigned char)pos;
}


unsigned char protspace::SeqBase::getPos(const char& entry)
{
    const size_t pos(mAASeq.find(entry));
    if (pos == std::string::npos)
        throw_line("500201",
                   "SeqBase::getPos",
                   "Unrecognized character "+entry);
    return (unsigned char)pos;
}


void protspace::SeqBase::loadSequence(const std::string& line)

{
    size_t pos;

    for(size_t i=0;i<line.size();++i)
    {
        if (line.at(i)==mGapChar)continue;
        pos=mAASeq.find(line.at(i));
        if (pos == std::string::npos)
            throw_line("500301","loadFastaSequence",
                       "Unrecognized AA |"+line.substr(i,1)+"|");
        mSeq.push_back((unsigned char)pos);

    }

}
