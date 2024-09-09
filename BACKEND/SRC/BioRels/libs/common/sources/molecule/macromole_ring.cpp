#include <sstream>
#include <headers/molecule/mmring_utils.h>

#include "headers/molecule/macromole.h"
#undef NDEBUG /// Active assertion in release



protspace::MMRing& protspace::MacroMole::addRingSystem(const std::vector<MMAtom*>& list,
                                 const bool& isAromatic) throw(ProtExcept)
{
    if (list.empty())
    throw_line("352301",
                     "MacroMole::addRingSystem",
                     "No atom in the ring");
      protspace::MMRing* mmring = (MMRing*)NULL;

    try
    {

          MMAtom& center=addAtom(mTempResidue);
          center.setName(isAromatic?"DuAr":"DuCy");
          center.setMOL2Type(isAromatic?"DuAr":"DuCy");
        mmring= new MMRing(list,center,isAromatic);
        mListRing.push_back(mmring);
    if(!isAromatic)return *mmring;

        turnToAromaticRing(*mmring);
    }
    catch (std::bad_alloc &e)
    {
        std::ostringstream oss;
        oss  << "Bad allocation append \n"
              << e.what()<<"\n"
             << "### RING INFORMATION : ###\n";
        for (size_t i=0;i< list.size();++i)
        {
            oss << list.at(i)->getIdentifier()<<"\n";
        }
        throw_line("352302","MacroMole::addRingSystem",oss.str());
    }
      catch(ProtExcept &e)
      {

          /// TempResidue is always part of the molecule
          assert(e.getId()!="250201");
          /// MOL2Type DuAr and DuCy should always work
          assert(e.getId()!="310801"&&e.getId()!="310802");

      }
      return *mmring;
}






bool protspace::MacroMole::isAtomInRing(const MMAtom& atom)const
{
    for (size_t iRing=0;iRing < mListRing.size();++iRing)
    {
        const MMRing& ring = *mListRing.at(iRing);
        if (ring.isInRing(atom)) return true;
    }
    return false;
}






const protspace::MMRing& protspace::MacroMole::getRingFromAtom(const MMAtom& atom) const throw(ProtExcept)
{
    for (size_t iRing=0;iRing < mListRing.size();++iRing)
    {
        const MMRing& ring = *mListRing.at(iRing);
        if (ring.isInRing(atom)) return ring;
    }
    throw_line("352401",
                     "MacroMole::getRingFromAtom",
                     "No ring found");
}





protspace::MMRing&  protspace::MacroMole::getRing(const size_t& pos) const throw(ProtExcept)
{
    if (pos >= mListRing.size())
        throw_line("352501",
                         "MacroMole::getRing",
                         "Given value above the number of rings");
    return *mListRing.at(pos);
}






void protspace::MacroMole::getRingsFromAtom(const MMAtom& atom,
                         std::vector<MMRing*>& listRings)const
{
for (size_t iRing=0;iRing < mListRing.size();++iRing)
{
    const MMRing& ring = *mListRing.at(iRing);
    if (ring.isInRing(atom)) listRings.push_back(mListRing.at(iRing));
}

}


















void protspace::MacroMole::delRing(MMRing& toDel,const bool& wCenter)
try{
    const std::vector<MMRing*>::iterator
            it=find(mListRing.begin(),mListRing.end(),&toDel);
    if (it==mListRing.end())
        throw_line("352601",
                         "MacroMole::delRing",
                         "Given ring is not part of the molecule");

   if(wCenter) delAtom(toDel.mCenter,true);

    delete *it;

    mListRing.erase(it);


}catch(ProtExcept &e)
{
    assert(e.getId()!= "350801");
    throw;
}









void protspace::MacroMole::clearRing(const bool& wCenter)
try{

    if (mListRing.empty())return;
    do
    {

        delRing(*mListRing.at(0),wCenter);

    }while(mListRing.size()>1);

    if (mListRing.empty())return;

        delRing(*mListRing.at(0),wCenter);

}catch(ProtExcept &e)
{
    e.addHierarchy("MacroMole::clearRing");
    throw;
}


void protspace::MacroMole::updateRingCenter()
{
    for (size_t iRing=0;iRing < mListRing.size();++iRing)
    {
        MMRing& ring = *mListRing.at(iRing);
        protspace::Coords& coo=ring.mCenter.pos();
        for(size_t iA=0;iA<ring.numAtoms();++iA)
            coo+=ring.getAtom(iA).pos();
        coo/=ring.numAtoms();
    }
}
