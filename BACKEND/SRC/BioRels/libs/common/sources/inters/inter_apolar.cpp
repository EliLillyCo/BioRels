#include <math.h>
#include "headers/inters/inter_apolar.h"
#include "headers/statics/intertypes.h"
#include "headers/inters/interdata.h"

double protspace::InterAtomApolar::mMaxDist=4.5;
bool protspace::InterApolar::mKeepAll=false;
protspace::InterAtomApolar::InterAtomApolar(MacroMole& pMole, const bool&pIsSameMole):
    InterAtomBase(pMole,INTER::HYDROPHOBIC,false,pIsSameMole)
{

}




bool protspace::InterAtomApolar::checkInteraction()
{
    if (&getAtomRef() == &getAtomComp())return false;
    if (!checkProperty())return false;
    if (mAtomDistance > mMaxDist)return false;
    return true;
}


bool protspace::InterAtomApolar::checkProperty()const
try{
    const PhysProp& atom1 = getAtomRef().prop();
    const PhysProp& atom2 = getAtomComp().prop();

    if (!(atom1.hasProperty(CHEMPROP::HYDROPHOBIC) &&
          atom2.hasProperty(CHEMPROP::HYDROPHOBIC)))
        return false;
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");///Atom should exists
    e.addHierarchy("InterAtomApolar::checkProperty");
    throw;
}



protspace::InterApolar::InterApolar(MacroMole& pMole, const bool &pIsSameMole):
    mIAA(pMole,pIsSameMole),
    mRefRes(pMole.numResidue()),
    mCompRes(pMole.numResidue()),
    mkeepBestPerRefAtom(false),
    mkeepBestPerCompAtom(false){

}
void protspace::InterApolar::setRefResidue(const MMResidue& pRes)
{
    if (&pRes.getParent() != &mIAA.getMolecule())
        throw_line("820101",
                   "InterApolar::setRefResidue",
                   "Given Residue is not part of the molecule");
    mRefRes=pRes.getMID();
}
void protspace::InterApolar::setCompResidue(const MMResidue& pRes)
{

    if (&pRes.getParent() != &mIAA.getMolecule2())
        throw_line("820201",
                   "InterApolar::setCompResidue",
                   "Given Residue is not part of the molecule");
    mCompRes=pRes.getMID();
}


void protspace::InterApolar::setRefResidue(const size_t& pPos)
{
    if (pPos >= mIAA.getMolecule().numResidue())
        throw_line("820301",
                   "InterApolar::setRefResidue",
                   "Given position is above the number of residue");
    mRefRes=pPos;
}




void protspace::InterApolar::setCompResidue(const size_t& pPos)
{
    if (pPos >= mIAA.getMolecule2().numResidue())
        throw_line("820401",
                   "InterApolar::setCompResidue",
                   "Given position is above the number of residues");
                   mCompRes=pPos;
}

bool protspace::InterApolar::isFiltered(MMAtom& atom)const
{
    if (atom.isHydrogen())return true;
    if (!atom.prop().hasProperty(CHEMPROP::HYDROPHOBIC))return true;
    if (atom.prop().hasProperty(CHEMPROP::AROM_RING))return true;
    return false;
}

void protspace::InterApolar::perceivePerAtom(InterData& data,
                                             MMResidue& resR,
                                             MMResidue& resC,
                                             const bool& isSwitched)
try{
    double bestDist;
    size_t bestC;
    for(size_t iR=0;iR<resR.numAtoms();++iR)
    {

        MMAtom& atomR = resR.getAtom(iR);

        if (isFiltered(atomR))continue;
        if (isSwitched) mIAA.setAtomComp(atomR);else mIAA.setAtomRef(atomR);
        bestC=0;
        bestDist =1000;
        for(size_t iC=0;iC<resC.numAtoms();++iC)
        {
            MMAtom& atomC = resC.getAtom(iC);
            if (isFiltered(atomC))continue;
            if (isSwitched) mIAA.setAtomRef(atomC);else            mIAA.setAtomComp(atomC);
            if (!mIAA.checkInteraction()) continue;
            if (mIAA.getDistance() > bestDist)continue;
            bestC=iC;
            bestDist = mIAA.getDistance();

        }
        if (mKeepAll || bestDist > protspace::InterAtomApolar::mMaxDist) return;
        if (bestDist==1000)return;

        mIAA.setAtomComp(resC.getAtom(bestC));
        mIAA.isInteraction(data);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="320401");///Get atom
    assert(e.getId()!="830201"&&e.getId()!="830101");///atom must be in molecule
    e.addHierarchy("InterApolar::perceivePerAtom");
    e.addDescription("Reference residue : "+resR.getIdentifier());
    e.addDescription("Comparison residue : "+resC.getIdentifier());
    throw;
}


void protspace::InterApolar::perceiveInteraction(InterData& data)

try{
    MMResidue& resR= mIAA.getMolecule().getResidue(mRefRes);
    MMResidue& resC= mIAA.getMolecule2().getResidue(mCompRes);
    if      (mkeepBestPerRefAtom) {perceivePerAtom(data,resR,resC);return;}
    else if (mkeepBestPerCompAtom){perceivePerAtom(data,resC,resR,true);return;}
    size_t bestR=0, bestC=0;
    double bestDist =1000;
    for(size_t iR=0;iR<resR.numAtoms();++iR)
    {
        MMAtom& atomR = resR.getAtom(iR);
        if (isFiltered(atomR))continue;
        mIAA.setAtomRef(atomR);
        for(size_t iC=0;iC<resC.numAtoms();++iC)
        {
            MMAtom& atomC = resC.getAtom(iC);
            if (isFiltered(atomC))continue;
            mIAA.setAtomComp(atomC);
            if (!mIAA.checkInteraction()) continue;
            if (mKeepAll)    mIAA.isInteraction(data);
            else
            {
                if (mIAA.getDistance() > bestDist)continue;
                bestR=iR; bestC=iC;
                bestDist = mIAA.getDistance();
            }
        }
    }

    if (mKeepAll || bestDist > protspace::InterAtomApolar::mMaxDist) return;
    if (bestDist==1000)return;

    mIAA.setAtomRef(resR.getAtom(bestR));
    mIAA.setAtomComp(resC.getAtom(bestC));
    //    cout <<"HYDROP "<<mIAA.getAtomRef().getIdentifier()<<" "<<mIAA.getAtomComp().getIdentifier()<<" " <<mIAA.getDistance()<<endl;
    mIAA.isInteraction(data);
}catch(ProtExcept &e)
{
    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="320401");///Atom must exist
    assert(e.getId()!="830101"&&e.getId()!="830101");///Atom must be in molecules
    e.addHierarchy("InterApolar::perceiveInteraction");
    throw;
}





void protspace::InterApolar::setMoleComp(MacroMole& pMole)
{
    mIAA.setMoleComp(pMole);
}


