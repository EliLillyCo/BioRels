#include <climits>
#include <sstream>
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
#undef NDEBUG /// Active assertion in release
using namespace std;
using namespace protspace;


size_t MacroMole::removeChainFromList(MMChain& pChain) throw(ProtExcept)
{
    const size_t pos(protspace::getPos(mListChain,pChain));
    if (pos==mListChain.size())
        throw_line("351201",
                   "MacroMole::removeChainFromList",
                   "Given chain not found in molecule");
    mListChain.erase(mListChain.begin()+pos);
    return pos;
}

void MacroMole::delChain(MMChain& pChain) throw(ProtExcept)
{
    if (!mOwner) {removeChainFromList(pChain);return;}

    if (&pChain.getMolecule() != this)
    throw_line("351301",
                     "MacroMole::delChain",
                     "Given chain is not part of this molecule ");

    const signed char pos((signed char)protspace::getPos(mListChain,pChain));
    if (pos==(signed char)mListChain.size())
        throw_line("351302",
                         "MacroMole::delChain",
                         "Given chain not found in molecule");

    vector<MMResidue*> resToDel=pChain.mResiduelist;
    try{

    if (!resToDel.empty())deleteResidues(resToDel);
    else
    {
        mListChain.erase(mListChain.begin()+pos);

    }
    }
    catch(ProtExcept &e)
    {
        e.addHierarchy("MacroMole::delChain");
        e.addDescription("Chain involved : "+pChain.getName());
        e.addDescription("Molecule name : "+mName);
        throw;
    }

    const size_t size((size_t)mListResidues.size());
    for(size_t iRes=0;iRes < size;++iRes)
    {
        MMResidue& res = *mListResidues.at(iRes);
        if (res.mChain > pos) res.mChain--;
    }



}

MMChain* MacroMole::getChainFromName(const std::string& chainName)const
{
    for (size_t iCh =0 ;iCh  < mListChain.size() ; ++iCh )
    {
        assert(mListChain.at(iCh) != (MMChain*)NULL);
        if (mListChain.at(iCh)->getName()== chainName)return mListChain.at(iCh);

    }
    return nullptr;

}

signed char MacroMole::getChainPosFromName(const std::string& chainName)const
{
    for (size_t iCh =0 ;iCh  < mListChain.size() ; ++iCh )
    {
        assert(mListChain.at(iCh) != (MMChain*)NULL);
        if (mListChain.at(iCh)->getName()== chainName)return (signed char) iCh;

    }
    return -1;

}

int MacroMole::getChainPos(const std::string& chainName)const
{
    for (size_t iCh =0 ;iCh  < mListChain.size() ; ++iCh )
    {
        assert(mListChain.at(iCh) != (MMChain*)NULL);
        if (mListChain.at(iCh)->getName()== chainName)return (int) iCh;

    }
    return -1;

}




MMChain& MacroMole::addChain(const string &pName)throw(ProtExcept)
try{
    signed char chain= getChainPosFromName(pName);
    if (chain !=-1) return getChain(chain);

        try{
            MMChain *newchain = new MMChain(*this, pName);
            mListChain.push_back(newchain);
            assert(mListChain.size()-1 <SCHAR_MAX );
            chain=mListChain.size()-1;
        }catch (std::bad_alloc &e)
        {
            ostringstream oss;
            oss  << "Bad allocation append \n"
            << e.what()<<"\n"
            << "For chain creation "<< pName<<"\n";
            throw_line("351401","MacroMole::addChain",oss.str());
        }
    return *mListChain.at(chain);
}catch(ProtExcept &e)
{
    /// If we find a chain from its name, the getChain must work
    assert(e.getId()!="350901");
    throw;
}
