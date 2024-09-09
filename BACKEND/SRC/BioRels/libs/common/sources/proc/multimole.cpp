#include "headers/proc/multimole.h"
#include "headers/parser/readers.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/statics/strutils.h"
#include "headers/statics/intertypes.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;

MultiMole::MultiMole(const bool &isOwner):
    mListMolecules(100,isOwner),
    mIsOwner(isOwner)
{

}


void MultiMole::addStructure(MacroMole& mole)
{
    mListMolecules.add(mole);

}

size_t MultiMole::getPos(MacroMole& mole)const
try{
    for(size_t i=0;i<mListMolecules.size();++i)
    {
        if (&mListMolecules.get(i)==&mole)return i;
    }
    return mListMolecules.size();
}catch(ProtExcept &e)
{
    assert(e.getId()!="040101");/// Position is always within boundarries
    e.addHierarchy("MultiMole::getPos");
    throw;
}


void MultiMole::serialize(std::ofstream& ofs)const
{
    size_t nsize=mListMolecules.size();
  ofs.write((char*)&nsize,sizeof(nsize));
  for(size_t i=0;i < nsize;++i)
      mListMolecules.get(i).serialize(ofs);
}

void MultiMole::unserialize(std::ifstream& ifs)
{
    size_t nEntry;
     ifs.read((char*)&nEntry,sizeof(size_t));
     mListMolecules.reserve(nEntry);
     for(size_t i=0;i<nEntry;++i)
     {
         MacroMole* mole = new MacroMole();
         mole->unserialize(ifs);
         mListMolecules.add(mole);
     }
}
void MultiMole::clear()
{
    mListMolecules.clear();

}

void MultiMole::setOwnership(const bool &isOwner)
{
    if (mIsOwner== isOwner)return;
    if (mIsOwner == true && isOwner==false && mListMolecules.size()>0)  mListMolecules.clear();
    mListMolecules.setOwnership(isOwner);
    mIsOwner=isOwner;
}
