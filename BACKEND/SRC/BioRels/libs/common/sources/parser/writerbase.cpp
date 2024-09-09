#include "headers/parser/writerbase.h"
#include "headers/molecule/macromole.h"
#undef NDEBUG /// Active assertion in release
using namespace std;
using namespace protspace;

void WriterBase::open() throw(ProtExcept)
{

    if (mOfs.is_open()) mOfs.close();
    if (mPath.empty())  throw_line("450101",
                                         "WriterBase::open",
                                         "No Path given");

    mOfs.open(mPath.c_str(),(mAppendFile?std::ios::out|std::ios::app : std::ios::out));
    if (!mOfs.is_open()) throw_line("450102",
                                          "WriterBase::open",
                                          "Unable to open file");
}

void WriterBase::setPath(const std::string& pPath)
{
    if (pPath=="")return;
    mPath=pPath;
    open();
    mChainToConsider.clear();
    mResidueToConsider.clear();
    mAtomToConsider.clear();
    mBondToConsider.clear();
}


WriterBase::WriterBase():
    mOfs(),
    mPath(""),
    mOnlySelected(false),
    mAppendFile(false)
{

}


WriterBase::WriterBase(const std::string& path, const bool &onlySelected):
    mOfs(),
    mPath(path),
    mOnlySelected(onlySelected),
    mAppendFile(false)
{

}




WriterBase::~WriterBase(){if (mOfs.is_open()) mOfs.close();}



bool WriterBase::prepareResidue(const MMResidue& residue)
try{
    bool mIsResidueConsidered=false;

    for (size_t iAtm=0; iAtm < residue.numAtoms();++iAtm)
    {
        const MMAtom& atom =residue.getAtom(iAtm);
        ///TODO Supposed to be for tempres. Check that it's not an issue
        if (atom.getName().length()>=2
          && atom.getName().substr(0,2)=="Du")continue;
        if (mOnlySelected && !atom.isSelected())continue;
        mAtomToConsider.push_back(atom.getMID());
        mIsResidueConsidered=true;
    }
    if (!mIsResidueConsidered ) return false;
    mResidueToConsider.push_back(residue.getMID());
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="320401");
    e.addHierarchy("WriterBase::prepareResidue");
    throw;
}

void WriterBase::selectObjects(const MacroMole& molecule)
try{
    mChainToConsider.clear();
    mResidueToConsider.clear();
    mAtomToConsider.clear();
    mBondToConsider.clear();
    bool mIsChainConsidered;

    const signed char nChain=static_cast<signed char>(molecule.numChains());
    for (signed char iCh=0; iCh < nChain; ++iCh)
    {
        const MMChain& chain=molecule.getChain(iCh);

        if (mOnlySelected && !chain.isSelected())continue;

        mIsChainConsidered=false;

        for (size_t iRes=0;iRes < chain.numResidue(); ++iRes)
        {
            const MMResidue& residue=chain.getResidue(iRes);
            if (mOnlySelected && !residue.isSelected())continue;
           if(prepareResidue(residue)) mIsChainConsidered=true;
        }
        if (mIsChainConsidered) mChainToConsider.push_back(iCh);
    }
    const MMResidue& tmpRes = molecule.getcTempResidue();
    if (prepareResidue(tmpRes)) mResidueToConsider.push_back(tmpRes.getMID());

    for (size_t iBd=0; iBd < molecule.numBonds();++iBd)
    {
        const MMBond& bond = molecule.getBond(iBd);
        if (mOnlySelected && !bond.isSelected()) continue;
        mBondToConsider.push_back(bond.getMID());
    }
}catch(ProtExcept &e)
{
    /// array boundary should always work
    assert(e.getId()!="351001" && e.getId()!="330601" &&e.getId()!="350501");
    e.addHierarchy("WriterBase::selectObjects");
    throw;
}




bool WriterBase::getAtomPos(const size_t& pAtomMID,size_t& pos)const
{
    const auto it=find(mAtomToConsider.begin(),
                       mAtomToConsider.end(),
                       pAtomMID);
    if (it==mAtomToConsider.end())return false;
    pos=std::distance(mAtomToConsider.begin(),it);
    return true;
}





bool WriterBase::getBondPos(const size_t& pBondMID,size_t& pos)const
{
    const auto it=find(mBondToConsider.begin(),
                       mBondToConsider.end(),
                       pBondMID);
    if (it==mBondToConsider.end())return false;
    pos=std::distance(mBondToConsider.begin(),it);
    return true;
}





bool WriterBase::getResiduePos(const size_t& pResidueMID,size_t& pos)const
{
    const auto it=find(mResidueToConsider.begin(),
                       mResidueToConsider.end(),
                       pResidueMID);
    if (it==mResidueToConsider.end())return false;
    pos=std::distance(mResidueToConsider.begin(),it);
    return true;
}




bool WriterBase::getChainPos(const size_t& pChainMID,size_t& pos)const
{
    const auto it=find(mChainToConsider.begin(),
                       mChainToConsider.end(),
                       pChainMID);
    if (it==mChainToConsider.end())return false;
    pos=std::distance(mChainToConsider.begin(),it);
    return true;
}
