#include <math.h>
#include "headers/inters/inter_atombase.h"
#include "headers/inters/interdata.h"
using namespace std;
using namespace protspace;


InterAtomBase::InterAtomBase(MacroMole& pMole,
                             const unsigned char pInterType,
                             const bool &pwAngle,
                             const bool& pIsSameMole):
    mMolecule(pMole),
    mAtomPos1(pMole.numAtoms()),
    mMolecule2(nullptr),
    mAtomPos2(pMole.numAtoms()),
    mInterType(pInterType),
    wAngle(pwAngle),
    mIsSameMole(pIsSameMole){

    if (mIsSameMole) mMolecule2=&mMolecule;

}

MMAtom& InterAtomBase::getAtomRef()const
try{
    return mMolecule.getAtom(mAtomPos1);
}catch(ProtExcept &e)
{
    e.addHierarchy("InterAtomBase::getAtomRef");
    throw;
}


MacroMole& InterAtomBase::getMolecule2()const
{
    assert(mMolecule2!= nullptr);
    return *mMolecule2;
}

MMAtom& InterAtomBase::getAtomComp()const
try{
    assert(mMolecule2!= nullptr);
    return mMolecule2->getAtom(mAtomPos2);
}catch(ProtExcept &e)
{
    e.addHierarchy("InterAtomBase::getAtomComp");
    throw;
}



void InterAtomBase::setAtomRef(const MMAtom& pAtom)
{
    if (&pAtom.getMolecule() != &mMolecule)
        throw_line("830101",
                   "InterAtomBase::setAtomRef",
                   "Given Atom is not part of the molecule");
    mAtomPos1=pAtom.getMID();
    updateDistance();
}


void InterAtomBase::setAtomComp(const MMAtom& pAtom)
{

    if (&pAtom.getParent() != mMolecule2)
        throw_line("830201",
                   "InterAtomBase::setAtomComp",
                   "Given Atom is not part of the molecule");
    mAtomPos2=pAtom.getMID();
    updateDistance();
}


void InterAtomBase::setAtomRef(const size_t& pos)
{
    if (pos >= mMolecule.numAtoms())
        throw_line("830301",
                   "InterAtomBase::setAtomRef",
                   "Given Position is above the number of atom for this molecule");
    mAtomPos1=pos;
    updateDistance();
}


void InterAtomBase::setAtomComp(const size_t& pos)
{
    assert(mMolecule2!=nullptr);
    if (pos >= mMolecule2->numAtoms())
        throw_line("830401",
                   "InterAtomBase::setAtomComp",
                   "Given Position is above the number of atom for this molecule");
    mAtomPos2=pos;
    updateDistance();
}
void InterAtomBase::setMoleComp(MacroMole& pMole)
{
    if (mIsSameMole)return ;   

    mMolecule2=&pMole;
    mAtomPos2=mMolecule2->numAtoms();
}


bool InterAtomBase::getIsSameMole() const
{
    return mIsSameMole;
}


void InterAtomBase::updateDistance()
{
    assert(mMolecule2!=nullptr);
    if (mAtomPos2 >=mMolecule2->numAtoms() || mAtomPos1>=mMolecule.numAtoms())
        mAtomDistance=1000;else
    mAtomDistance=mMolecule.getAtom(mAtomPos1).dist(mMolecule2->getAtom(mAtomPos2));
}

bool InterAtomBase::isInteraction(InterData& data)
try{
    if (!checkInteraction())return false;
     assert(mMolecule2!=nullptr);
    InterObj pObj(mMolecule.getAtom(mAtomPos1),
                        mMolecule2->getAtom(mAtomPos2),
                        mInterType,
                        mAtomDistance);
    if (wAngle) pObj.setAngle(getAngle());
    data.addInter(pObj);
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");
    e.addHierarchy("InterAtomBase::isInteraction");
            throw;
}
