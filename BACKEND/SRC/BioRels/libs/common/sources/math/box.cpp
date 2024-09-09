#include "headers/math/box.h"
#include "headers/statics/protpool.h"
#include "headers/math/grid.h"
protspace::Box::Box():
  mParent(nullptr),
  mGridpos(protspace::ProtPool::coord.acquireObject(mGridPoolPos)),
  mId(0),
  mIsMargin(false),
  mHasProtein(false),mCloseProtein(false),mIsPotentialCavity(false),mInUse(false)
{

}


protspace::Box::~Box()
{
}


void protspace::Box::clear()
{
    mParent=nullptr;
    mIncludeAtom.clear();
    mCloseAtom.clear();
    mGridpos.clear();
    mId=0;mIsMargin=0;mHasProtein=0;mCloseProtein=0;mIsPotentialCavity=0;
}

protspace::Coords protspace::Box::getOrigPos() const
{
    const protspace::Coords* vect=mParent->getVect();
    pC v;
v.obj.setxyz(mParent->getStartPos()
        +(vect[0]*mGridpos.x()
        +vect[1]*mGridpos.y()
        +vect[2]*mGridpos.z())*mParent->getBoxLength());
return v.obj;
}
