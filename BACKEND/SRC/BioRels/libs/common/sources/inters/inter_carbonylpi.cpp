//
// Created by c188973 on 10/5/16.
//
#include <math.h>
#include <headers/statics/intertypes.h>
#include <headers/molecule/macromole.h>
#include <headers/math/coords_utils.h>
#include <headers/parser/writerMOL2.h>
#include <sstream>
#include <headers/inters/interobj.h>
#include "headers/inters/inter_carbonylpi.h"
#include "headers/inters/interdata.h"
#include "headers/statics/logger.h"

double protspace::InterCarbonylPI::mTD_ArC_Ox=5;
double protspace::InterCarbonylPI::mTD_ArC_C=6;
double protspace::InterCarbonylPI::mTD_Shift=1.6;
double protspace::InterCarbonylPI::mCD_carbonyl_ring=0;
double protspace::InterCarbonylPI::mRD_carbonyl_ring=M_PI/2;
std::string protspace::InterCarbonylPI::mDebugData="";
bool protspace::InterCarbonylPI::mDebugSave=true;

protspace::InterCarbonylPI::InterCarbonylPI(const MMRing& pRing):
    mRing(pRing),
    mCarbon(nullptr),
    mOxygen(nullptr)
{

}



bool protspace::InterCarbonylPI::setOxygen(MMAtom& atom)

try {
    clear();
    /// Check the oxygen.
    if (atom.numBonds() != 1)return false;
    const uint16_t &type = atom.getBond(0).getType();
    if (type != BOND::DOUBLE &&
        type != BOND::DELOCALIZED &&
        type != BOND::AROMATIC_BD)return false;
    MMAtom &atmC = atom.getAtom(0);
    if (atmC.getAtomicNum() != 6)return false;
    unsigned char nOx = 0;
    for (size_t iBd = 0; iBd < atmC.numBonds(); ++iBd) {
        const uint16_t &type2 = atmC.getBond(iBd).getType();
        if (atmC.getAtom(iBd).isOxygen() &&
                (type2 == BOND::DOUBLE ||
                 type2 == BOND::DELOCALIZED ||
                 type2 == BOND::AROMATIC_BD))
            nOx++;
    }
    if (nOx != 2)return false;
    mOxygen = &atom;
    mCarbon = &atmC;
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="310501");///getAtom should work.
    assert(e.getId()!="310201" &&e.getId()!="071001");///Bond should work
    e.addHierarchy("InterCarbonylPI::setOxygen");
    throw;
}




void protspace::InterCarbonylPI::clear()
{
    mOxygen=nullptr;
    mCarbon=nullptr;
}






void protspace::InterCarbonylPI::runMath()
{
    try {
        processRingAtomInfo(mRing, mOxygen->pos(), mDataOx);
        processRingAtomInfo(mRing, mCarbon->pos(), mDataCa);
        mVectCarbonyl = (mCarbon->pos().getNormal(
                         mCarbon->getAtomNotAtom(*mOxygen).pos(),
                         mOxygen->pos()));

        mCD_Value = (computeSignedDihedralAngle(
                         mVectCarbonyl,
                         mCarbon->pos(),
                         mRing.getCenter(),
                         mDataOx.mVect_i.obj + mRing.getCenter()));

        if (!mDebugSave)return;

        std::ostringstream oss;
        oss << mOxygen->getParent().getName() << "\t"
            << mOxygen->getResidue().getChainName() << "\t"
            << mOxygen->getResidue().getName() << "\t"
            << mOxygen->getResidue().getFID() << "\t"
            << mOxygen->getName() << "\t"
            << mRing.getResidue().getName() << "\t"
            << mRing.getResidue().getChainName() << "\t"
            << mRing.getResidue().getName() << "\t"
            << mRing.getResidue().getFID() << "\t";
        for (size_t i = 0; i < mRing.numAtoms(); ++i)
            oss << mRing.getAtom(i).getName() << "_";
        oss << "\t";
        oss << mDataOx.mDSide
            << "\t" << mDataOx.mRc
            << "\t" << mDataOx.mTheta * 180 / M_PI
            << "\t" << mDataCa.mDSide
            << "\t" << mDataCa.mRc
            << "\t" << mDataCa.mTheta * 180 / M_PI << "\t" << mCD_Value << "\n";
        mDebugData += oss.str();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterCarbonylPI::runMath");
        throw;
    }
}
bool protspace::InterCarbonylPI::isInteracting(InterData &data)const
{
    try{
        if (mDataOx.mRc >= mTD_ArC_Ox)return false;
        if (mDataCa.mRc >= mTD_ArC_C) return false;
        if (mDataOx.mDSide >= mTD_Shift)return false;
        if (mCD_Value < mCD_carbonyl_ring-mRD_carbonyl_ring)return false;
        if (mCD_Value > mCD_carbonyl_ring+mRD_carbonyl_ring)return false;

        InterObj pObj(mRing.getAtomCenter(),
                      *mOxygen,
                      INTER::CARBONYL_PI,
                      mDataOx.mRc);
        pObj.setRing1(mRing);
        data.addInter(pObj);

        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterCarbonylPI::isInteracting");
        throw;
    }

}
void protspace::InterCarbonylPI::saveToMole()const
{
    const Coords& rcent(mRing.getCenter());
    MacroMole test;
    Coords rotpos(mOxygen->pos()-rcent);
    rotate(rotpos,mDataOx.mRotMatrix);
    MMAtom& at1=test.addAtom(test.getTempResidue(),rcent,"P","P.3","P");
    MMAtom& at2=test.addAtom(test.getTempResidue(),mDataOx.mVect_i.obj+rcent,"C","C.3","C");
    MMAtom& at3=test.addAtom(test.getTempResidue(),mDataOx.mVect_j.obj+rcent,"N","N.3","N");
    MMAtom& at4=test.addAtom(test.getTempResidue(),mDataOx.mVect_k.obj+rcent,"O","O.3","O");

    Coords at5c(rcent+mDataOx.mVect_i.obj*rotpos.x()+mDataOx.mVect_j.obj*rotpos.y()+mDataOx.mVect_k.obj*rotpos.z());
    MMAtom& at5=test.addAtom(test.getTempResidue(),at5c,"F","F","F");

    Coords at6c(rcent+mDataOx.mVect_k.obj*rotpos.z());
    MMAtom& at6=test.addAtom(test.getTempResidue(),at6c,"F","F","F");

    Coords at7c(rcent+mDataOx.mVect_j.obj*rotpos.y());
    MMAtom& at7=test.addAtom(test.getTempResidue(),at7c,"F","F","F");

    Coords at8c(rcent+mDataOx.mVect_k.obj*rotpos.z()+mDataOx.mVect_j.obj*rotpos.y());
    MMAtom& at8=test.addAtom(test.getTempResidue(),at8c,"F","F","F");

    test.addBond(at1,at2,BOND::SINGLE);
    test.addBond(at1,at3,BOND::SINGLE);
    test.addBond(at1,at4,BOND::SINGLE);
    test.addBond(at6,at8,BOND::SINGLE);
    test.addBond(at8,at7,BOND::SINGLE);
    test.addBond(at5,at8,BOND::SINGLE);

    const Coords norm(mCarbon->pos().getNormal(mCarbon->getAtomNotAtom(*mOxygen).pos(),mOxygen->pos()));
    MMAtom& at9=test.addAtom(test.getTempResidue(),norm,"F","F","F");
    MMAtom& at10=test.addAtom(test.getTempResidue(),mCarbon->pos(),"F","F","F");
    test.addBond(at9,at10,BOND::SINGLE);

    std::ostringstream oss;
    MMResidue &res=mRing.getResidue();
    oss<<res.getName()<<"-"<<res.getFID()<<"-"<<mRing.getAtom(0).getFID()<<"-"<<res.getChainName()
      <<"-"<<mOxygen->getResidue().getName()<<"-"<<mOxygen->getResidue().getFID()<<"-"<<mOxygen->getName()<<".mol2";
    std::cout <<oss.str()<<std::endl;
    WriteMOL2 writer(oss.str());
    writer.save(test);
}

void protspace::InterCarbonylPI::exportDebugData()const
{
    std::ofstream ofs("/home/c188973/CARBONYL_PI.csv",std::ios::out|std::ios::app);
    ofs<<mDebugData;
}
