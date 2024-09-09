#undef NDEBUG /// Active assertion in release
#include <assert.h>
#include <fstream>
#include <iostream>
#include <sstream>
#include "headers/sequence/seqnucl.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/molecule/mmresidue_utils.h"
protspace::SeqNucl::SeqNucl():
    SeqBase("")
{

}

protspace::SeqNucl::SeqNucl(const std::string &pName):
    SeqBase(pName)
{

}

protspace::MMResidue &protspace::SeqNucl::getResidue(const size_t &pos)
{
    assert(1==0 && "This function should never be called");
}
protspace::SeqNucl::SeqNucl(const SeqNucl& seq):
    SeqBase(seq.getName(),seq.len(),seq.getGapChar())

{
    mSeq=seq.mSeq;
    mPos=seq.mPos;
}



protspace::SeqNucl::SeqNucl(const SeqNucl& seq, std::vector<int> &map):
    SeqBase(seq.getName(),seq.len(),seq.getGapChar())

{
    for(size_t i=0;i<seq.len();++i)
    {
        if (seq.getLetter(i)==mGapChar)continue;
        mSeq.push_back(seq.mSeq.at(i));
        mPos.push_back(seq.mPos.at(i));
        map.push_back(i);
    }
}

unsigned char protspace::SeqNucl::posToName(const size_t& position)const throw(ProtExcept)
{
    return mSeq.at(position);
}



const long &protspace::SeqNucl::posToId(const size_t& position)const throw(ProtExcept)
{
    assert(position < mPos.size());
    return mPos.at(position);
}

size_t protspace::SeqNucl::idToPos(const long& id)const
{
    size_t pos=0;
    for(const long& p:mPos)
    {
        ++pos;
        if (p==id)return pos-1;
    }
    return mPos.size();
}

void protspace::SeqNucl::setID(const int &pos, const long &id)
{
    mPos.at(pos)=id;
}


size_t protspace::SeqNucl::size() const
{
    return mSeq.size();
}



const unsigned char &protspace::SeqNucl::at(const size_t& pos) const
{
    return mSeq.at(pos);
}




void protspace::SeqNucl::loadFastaSeqNucl(const std::vector<std::string> &sequence)
{
    int num=0;
    mSeq.clear();
    mPos.clear();
    const std::string& head = sequence.at(0);
    if (head.substr(0,1) != ">")
        throw_line("510101",
                   "SeqNucl::loadFastaSeqNucl",
                   "Header line should start with > for line:\n"+head);
    mName=head.substr(1);

    size_t pos;
    for (size_t iLine=1; iLine < sequence.size();++iLine)
    {
        const std::string &line = sequence.at(iLine);
        if (line.substr(0,1)== ">")return;
        for(const char& ch:line)
        {
            pos=mNuclSeq.find(ch);
            if (pos == std::string::npos)
                throw_line("510102","SeqNucl::loadFastaSeqNucl",
                           "Unrecognized AA |"+std::to_string(ch)+"|");
            mSeq.push_back(pos);
            mPos.push_back(num);++num;
        }

    }

}

void protspace::SeqNucl::loadSeqNucl(const std::string& line,
                                       const bool& keepGap,
                                       const bool& wGapShift)

{
    mSeq.clear();
    mPos.clear();

    size_t pos,len=0;
    long num=0;
    for(const char& ch:line)
    {

        if (ch==mGapChar)
        {
            if (keepGap)
            {
                mSeq.push_back(mAALen);
                mPos.push_back(num);++num;
            }else if (wGapShift)++num;
            continue;
        }
        pos=mNuclSeq.find(ch);
        if (pos == std::string::npos)
            throw_line("510201","loadSeqNucl",
                       "Unrecognized AA |"+line.substr(len,1)+"|"+std::to_string(len));
        mSeq.push_back(pos);
        mPos.push_back(num);++num;
        ++len;
    }

}



void protspace::SeqNucl::loadPIRSeqNucl(const std::vector<std::string> &sequence)
{
    mSeq.clear();


    const std::string& head = sequence.at(0);
    if (head.substr(0,1) != ">")
        throw_line("510301",
                   "SeqNucl::loadPIRSeqNucl",
                   "Header line should start with > for line:\n"+head);
    mName=head.substr(1);

    size_t pos;


    long num=0;
    for (size_t iLine=2; iLine < sequence.size();++iLine)
    {
        const std::string &line = sequence.at(iLine);
        if (line.substr(0,1)== ">")return;
        for(const char& ch:line)
        {
            if (ch=='*')return;
            if (ch==' ')continue;

            pos=mNuclSeq.find(ch);
            if (pos == std::string::npos)
                throw_line("510302","SeqNucl::loadPIRSeqNucl",
                           "Unrecognized AA |"+std::to_string(ch)+"|");
            mSeq.push_back(static_cast<unsigned char>(pos));
            mPos.push_back(num);++num;
        }

    }

}


void protspace::SeqNucl::loadFastaFile(const std::string& file)
{
    std::vector<std::string> list;
    std::ifstream ifs(file);
    std::string line;
    while(!ifs.eof())
    {
        std::getline(ifs,line);if (line.empty())continue;
        list.push_back(line);

    }
    ifs.close();
    loadFastaSeqNucl(list);
}



const char &protspace::SeqNucl::getLetter(const size_t&pos)const
{
    if (pos >= mSeq.size())std::cout <<pos<<"\t"<<mSeq.size()<<"\t"<<mName<<std::endl;
    assert(pos < mSeq.size());
    if (mSeq.at(pos)==SEQB_LEN)return mGapChar;
    return mNuclSeq.at(mSeq.at(pos));
}

std::string protspace::SeqNucl::getSubSeq(const size_t &pos, const size_t &pLen) const
{
    std::string s("");
    const size_t len(mSeq.size());
    for(size_t i=pos;i<pos+pLen;++i)
    {
        if (i<len && mSeq[pos]!=SEQB_LEN)s+=mNuclSeq.at(mSeq.at(i));
         else s+=mGapChar;
    }
    return s;
}

void protspace::SeqNucl::getSubSeq(const size_t& pos, const size_t& pLen, std::string& pSeq)const
{
    pSeq="";
    const size_t len(mSeq.size());
    for(size_t i=pos;i<pos+pLen;++i)
    {
        if (i<len && mSeq[pos]!=SEQB_LEN)pSeq+=mNuclSeq.at(mSeq.at(i));
         else pSeq+=mGapChar;
    }
}


/*protspace::MMResidue& protspace::SeqNucl::getResidue(const size_t& pos)
{
    throw_line("510401",
               "SeqNucl::getResidue",
               "Standard sequence is not associated to a residue");
}*/



void protspace::SeqNucl::serialize(std::ofstream& ofs)const
{

    size_t length=mName.size();
    ofs.write((char*)&length,sizeof(size_t));
    ofs.write(mName.c_str(),mName.size());
    ofs.write((char*)&mGapChar,sizeof(char));


    length=mSeq.size();
    ofs.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<mSeq.size();++i)
        ofs.write((char*)&mSeq.at(i),sizeof(unsigned char));
    length=mPos.size();
    ofs.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<mPos.size();++i)
        ofs.write((char*)&mPos.at(i),sizeof(long));
}

void protspace::SeqNucl::unserialize(std::ifstream& ifs)
{

    readSerializedString(ifs,mName);
    ifs.read((char*)&mGapChar,sizeof(char));
    size_t length;
    ifs.read((char*)&length,sizeof(size_t));
    unsigned char v;
    for(size_t i=0;i<length;++i)
    {

        ifs.read((char*)&v,sizeof(unsigned char));
        mSeq.push_back(v);
    }
    ifs.read((char*)&length,sizeof(size_t));
    int vi;
    for(size_t i=0;i<length;++i)
    {
        ifs.read((char*)&vi,sizeof(long));
        mPos.push_back(vi);}
}

void protspace::SeqNucl::clear()
{
    mSeq.clear();
    mPos.clear();
}

void protspace::SeqNucl::push_values(const std::string &val, const long& pos) {
    try {
        size_t posR=mNuclSeq.find(val);
        if (posR == std::string::npos)
            throw_line("510501","SeqNucl::push_value",
                       "Unrecognized AA "+val);
        mSeq.push_back(static_cast<unsigned char>(posR));
        mPos.push_back(pos);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("SeqNucl::push_values");
        e.addDescription("Value "+val+" / Position : "+std::to_string(pos));
    }
}

std::string protspace::SeqNucl::toHumanString() const
{
    assert(mPos.size()==mSeq.size());
    std::ostringstream oss;
        for(size_t i=0;i<mSeq.size();++i)
        {
            if (i%50==49 || i==0)
            {
                if (i!=0)
                {
                    oss<<getLetter(i)<<"   ";
                    const int& id=posToId(i);
                    if (id<1000)oss<<" ";
                    if (id<100)oss<<" ";
                    if (id<10)oss<<" ";
                    oss<<id<<"\n";
                    i++;
                }
                if (i==mSeq.size())continue;
                const int& id2=posToId(i);
                if (id2<1000)oss<<" ";
                if (id2<100)oss<<" ";
                if (id2<10)oss<<" ";
                oss<<id2<<"   ";
                //oss<<"\033[1;31m"<<
                     oss<<getLetter(i);
                 //<<"\033[0m";///https://stackoverflow.com/questions/2616906/how-do-i-output-coloured-text-to-a-linux-terminal
                continue;
            }
            else if (i%10==0)oss<<" " ;
            oss<<getLetter(i);
        }
        const size_t diff=50-(mSeq.size()%50);
        for(size_t j=0;j<diff;++j)oss<<" ";
        for(size_t j=0;j<diff/10;++j)oss<<" ";

        const long& id2=posToId(mSeq.size()-1);
        oss<<"   ";
        if (id2<1000)oss<<" ";
        if (id2<100)oss<<" ";
        if (id2<10)oss<<" ";
        oss<<id2<<"\n";
        return oss.str();

}


void protspace::SeqNucl::push_values(const char &val, const long& pos) {
    try {
        mSeq.push_back(getPos(val));
        mPos.push_back(pos);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("SeqNucl::push_values");
        e.addDescription("Value "+std::to_string(val)+" / Position : "+std::to_string(pos));
    }
}


void protspace::SeqNucl::insertGap(const size_t& pos ) {
    if(pos >= mSeq.size())
            throw_line("500401","SeqBase::insertGap",
                       "Position above length "+std::to_string(pos)+"/"+
                       std::to_string(mSeq.size()));
    mSeq.insert(mSeq.begin()+pos,mNuclLen);
}

void protspace::SeqNucl::replace(const size_t &pos, const char &letter)
{
    const size_t posLetter = mNuclSeq.find(letter);
    assert(pos < mSeq.size());
    mSeq[pos]=(unsigned char)posLetter;
}


void protspace::SeqNucl::push_back_gap() {
    mSeq.push_back(mNuclLen);
}

unsigned char protspace::SeqNucl::getPos(const std::string& entry)const
{
    const size_t pos(mNuclSeq.find(entry));
    if (pos == std::string::npos)
        throw_line("500101",
                   "SeqBase::getPos",
                   "Unrecognized character "+entry);
    return (unsigned char)pos;
}


unsigned char protspace::SeqNucl::getPos(const char& entry)
{
    const size_t pos(mNuclSeq.find(entry));
    if (pos == std::string::npos)
        throw_line("500201",
                   "SeqBase::getPos",
                   "Unrecognized character "+entry);
    return (unsigned char)pos;
}


void protspace::SeqNucl::loadSequence(const std::string& line)

{
    size_t pos;
    int num=0;
    for(size_t i=0;i<line.size();++i)
    {
        if (line.at(i)==mGapChar)continue;
        pos=mNuclSeq.find(line.at(i));
        if (pos == std::string::npos)
            throw_line("500301","loadFastaSequence",
                       "Unrecognized AA |"+line.substr(i,1)+"|");
        mSeq.push_back((unsigned char)pos);
    mPos.push_back(num);++num;
    }

}
