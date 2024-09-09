#include <fstream>
#include "headers/sequence/seqchain.h"

#include "headers/molecule/mmresidue.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue_utils.h"
#undef NDEBUG /// Active assertion in release
protspace::SequenceChain::SequenceChain(MMChain& pChain):
    SeqBase(pChain.getMoleName()+"_"+pChain.getName(),pChain.numResidue()),
    mChain(pChain)
{
    update();
    mList.reserve(pChain.numResidue());
}

protspace::SequenceChain::~SequenceChain()
{

}



///TODO : Improve update system by incorporating continuity information
void protspace::SequenceChain::update()
{
    mList.clear();
    mSeq.clear();
    size_t pos;

    for (size_t iRes=0;iRes < mChain.numResidue();++iRes)
    {
        const std::string& Rname=mChain.getResidue(iRes).getName();
        //const uint16_t&    Rtype=mChain.getResidue(iRes).getResType();
        if (Rname=="HOH") continue;
        mList.push_back(iRes);

        pos=mAASeq.find(residue3Lto1L(Rname));
        if (pos == std::string::npos)
            throw_line("520101","SequenceChain::update",
                       "Unrecognized AA "+Rname+"\nResidue Involved :"+mChain.getResidue(iRes).getIdentifier());
        mSeq.push_back(static_cast<unsigned char>(pos));
    }
}






unsigned char protspace::SequenceChain::posToName(const size_t& pos)const throw(ProtExcept)
{
    if (pos >= mSeq.size())
        throw_line("520201","SequenceChain::posToName",
                   "Position is above the number of characters");
    return mSeq.at(pos) ;
}





const int& protspace::SequenceChain::posToId(const size_t& pos)const throw(ProtExcept)
{if (pos >= mList.size())
        throw_line("520301","SequenceChain::posToId",
                   "Position is above the number of entries");
    return mChain.getResidue(mList.at(pos)).getFID();
}





size_t protspace::SequenceChain::size() const
{
    return mList.size();

}


const unsigned char &protspace::SequenceChain::at(const size_t& pos) const
{
    assert(pos < mSeq.size());
    return mSeq.at(pos);

}


const char& protspace::SequenceChain::getLetter(const size_t&pos)const
{
    assert(pos<mSeq.size());
    const unsigned char& v=mSeq.at(pos);
    if (v==SEQB_LEN) return mGapChar;
    assert(v<mAASeq.size());
    return mAASeq.at(v);
}


const protspace::MMResidue& protspace::SequenceChain::getResidue(const size_t& pos)const
{
    if (pos >= mList.size())
            throw_line("520401","SequenceChain::getResidue",
                       "Position is above the number of entries");
    try{
    return mChain.getResidue(mList.at(pos));
    }catch(ProtExcept &e)
    {
        e.addHierarchy("SequenceChain::getResidue");
        e.addDescription("Position requested: "+std::to_string(pos));
        e.addDescription("Residue Position requested: "+std::to_string(mList.at(pos)));
        throw;
    }
}
protspace:: MMResidue& protspace::SequenceChain::getResidue(const size_t& pos)
{
    if (pos >= mList.size())
            throw_line("520501","SequenceChain::getResidue",
                       "Position is above the number of entries");
    try{
    return mChain.getResidue(mList.at(pos));
    }catch(ProtExcept &e)
    {
        e.addHierarchy("SequenceChain::getResidue");
        e.addDescription("Position requested: "+std::to_string(pos));
        e.addDescription("Residue Position requested: "+std::to_string(mList.at(pos)));
        throw;
    }
}

void protspace::SequenceChain::updateResName(const std::string& resName, const size_t& seqPos)
{
    const size_t pos(mAASeq.find(residue3Lto1L(resName)));
    if (pos == std::string::npos)
        throw_line("520601","SequenceChain::updateResName",
                   "Unrecognized AA");
    if (seqPos >= mSeq.size())
        throw_line("520701","SequenceChain::updateResName",
                   "Given position above sequence length");
    mSeq.at(seqPos)=(static_cast<unsigned char>(pos));
}




bool protspace::SequenceChain::getSeqPos(const MMResidue& res,size_t& value)const
{
    size_t pos=mChain.getResiduePos(res);
    const auto it(std::find(mList.begin(),mList.end(),pos));
    if (it==mList.end())return false;
    value=std::distance(mList.begin(),it);
    return true;
}




void protspace::SequenceChain::serialize(std::ofstream& ofs)const
{
     const std::string name=mChain.getMoleName()+"_"+mChain.getName();
    size_t length=name.size();
    ofs.write((char*)&length,sizeof(size_t));
    ofs.write(name.c_str(),mName.size());
    length=mList.size();
    ofs.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<mList.size();++i)
    ofs.write((char*)&mList.at(i),sizeof(short));
}




