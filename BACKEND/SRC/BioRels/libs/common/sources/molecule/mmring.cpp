#include <sstream>
#include <math.h>
#include <iomanip>
#include "headers/molecule/mmring.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/mmresidue.h"
#undef NDEBUG /// Active assertion in release


bool compID(protspace::MMAtom* atom1, protspace::MMAtom* atom2)
{return (atom1->getMID()<atom2->getMID());}


protspace::MMRing::MMRing(const std::vector<MMAtom*>& list_atoms, MMAtom &center,
               const bool& isAromatic):
    mAtomlist(list_atoms.size(),false),
    mIsAromatic(isAromatic),
    mCenter(center),
    mResidue(list_atoms.at(0)->getResidue())
{

//    std::sort(list_atoms.begin(),list_atoms.end(),compID);

    for(size_t i=0;i<list_atoms.size();++i)
    {
        MMAtom* atm=list_atoms.at(i);
        mAtomlist.add(atm);
        mCenter.pos()+=atm->pos();
    }
    mAtomlist.sort();;
    mCenter.pos()/=(double)(mAtomlist.size());

}





void protspace::MMRing::addAtom(MMAtom& atom)
{
    mAtomlist.add(&atom);
    protspace::Coords& pos=mCenter.pos();
    pos.clear();
    for(size_t i=0;i<mAtomlist.size();++i)
        pos+=mAtomlist.get(i).pos();
    pos/=(double)(mAtomlist.size());
}





void protspace::MMRing::addAtom(MMAtom* const atom)
{
    assert(atom!=nullptr);
    mAtomlist.add(atom);
    protspace::Coords& pos=mCenter.pos();
    pos.clear();
    for(size_t i=0;i<mAtomlist.size();++i)
        pos+=mAtomlist.get(i).pos();
    pos/=(double)(mAtomlist.size());
}





std::string protspace::MMRing::toString() const
{
    std::ostringstream oss;
    oss<<"RING ";
    if (mIsAromatic) oss<< "AR {";else oss<<"AL {";
    oss<<mResidue.getIdentifier() << "\t";
    for (size_t i=0;i<mAtomlist.size();++i)
    {
        const MMAtom& atm=mAtomlist.get(i);
        oss<<std::left<<std::setw(4)<< atm.getName()
          <<atm.getMID()<<" ; ";
    }
    oss << " } ";

    return oss.str();

}





bool protspace::MMRing::isInRing(const MMAtom& atom)const
{
    return (&atom == &mCenter || mAtomlist.isIn(atom));
}







void protspace::MMRing::setUse(const bool& used)
{
    for(size_t i=0;i<mAtomlist.size();++i)
        mAtomlist.get(i).select(used);
}




bool protspace::MMRing::isSelected()const
{
    for(size_t i=0;i<mAtomlist.size();++i)
        if (!mAtomlist.get(i).isSelected())return false;
    return true;
}




void protspace::MMRing::fillList(std::vector<MMAtom*>& list)const
{
    for(size_t i=0;i<mAtomlist.size();++i)list.push_back(&mAtomlist.get(i));
}




void protspace::MMRing::fillList(std::vector<size_t>& list)const
{
    for(size_t i=0;i<mAtomlist.size();++i)
        list.push_back(mAtomlist.get(i).getMID());
}
