#include <math.h>
#include <sstream>
#include "headers/inters/inter_atomarom.h"
#include "headers/math/coords_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/parser/writerMOL2.h"
#include "headers/statics/intertypes.h"
protspace::InterAtomArom::InterAtomArom(const protspace::MMRing &pRing):
    mRing(pRing),
    mAtom(nullptr),
    mMathRun(false)
{

}

void protspace::InterAtomArom::clear()
{
    mData.mDSide=0;
    mData.mRc=0;
    mData.mTheta=0;
    mData.mHeight=0;
    mData.mCz.obj.clear();
    mData.mCy.obj.clear();
    mData.mCyz.obj.clear();
    mData.mVect_i.obj.clear();
    mData.mVect_j.obj.clear();
    mData.mVect_k.obj.clear();
    mMathRun=false;
    mAtom=nullptr;
}




void protspace::InterAtomArom::setAtom(const protspace::MMAtom &atom)
{
    clear();
    mAtom=const_cast<MMAtom*>(&atom);
}

void protspace::InterAtomArom::runMath()
try{
    if (!mMathRun) processRingAtomInfo(mRing,mAtom->pos(),mData);
    mMathRun=true;
}catch(ProtExcept &e)
{
    e.addHierarchy("InterAtomArom::runMatch");
    throw;
}



void protspace::InterAtomArom::saveToMole()const
{
    const Coords& rcent(mRing.getCenter());
    MacroMole test;
    Coords rotpos(mAtom->pos()-rcent);
    rotate(rotpos,mData.mRotMatrix);
    MMAtom& at1=test.addAtom(test.getTempResidue(),rcent,"P","P.3","P");
    MMAtom& at2=test.addAtom(test.getTempResidue(),mData.mVect_i.obj+rcent,"C","C.3","C");
    MMAtom& at3=test.addAtom(test.getTempResidue(),mData.mVect_j.obj+rcent,"N","N.3","N");
    MMAtom& at4=test.addAtom(test.getTempResidue(),mData.mVect_k.obj+rcent,"O","O.3","O");

    Coords at5c(rcent+mData.mVect_i.obj*rotpos.x()+mData.mVect_j.obj*rotpos.y()+mData.mVect_k.obj*rotpos.z());
    MMAtom& at5=test.addAtom(test.getTempResidue(),at5c,"F","F","F");

    Coords at6c(rcent+mData.mVect_k.obj*rotpos.z());
    MMAtom& at6=test.addAtom(test.getTempResidue(),at6c,"F","F","F");

    Coords at7c(rcent+mData.mVect_j.obj*rotpos.y());
    MMAtom& at7=test.addAtom(test.getTempResidue(),at7c,"F","F","F");

    Coords at8c(rcent+mData.mVect_k.obj*rotpos.z()+mData.mVect_j.obj*rotpos.y());
    MMAtom& at8=test.addAtom(test.getTempResidue(),at8c,"F","F","F");

    test.addBond(at1,at2,BOND::SINGLE);
    test.addBond(at1,at3,BOND::SINGLE);
    test.addBond(at1,at4,BOND::SINGLE);
    test.addBond(at6,at8,BOND::SINGLE);
    test.addBond(at8,at7,BOND::SINGLE);
    test.addBond(at5,at8,BOND::SINGLE);
    std::ostringstream oss;
    MMResidue &res=mRing.getResidue();
    oss<<res.getName()<<"-"<<res.getFID()<<"-"<<res.getChainName()<<"-"<<mAtom->getName()<<".mol2";
    WriteMOL2 writer(oss.str());
    writer.save(test);
}
