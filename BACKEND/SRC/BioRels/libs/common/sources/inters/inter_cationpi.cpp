#include <math.h>
#include <sstream>
#include <fstream>
#include "headers/inters/inter_cationpi.h"
#include "headers/math/coords_utils.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/molecule/mmresidue.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"
using namespace protspace;
using namespace std;

double InterCationPI::mDistThres=6;
double InterCationPI::mAngleCenter=0;
double InterCationPI::mAngleRange=M_PI/4;

InterCationPI::InterCationPI(const MMRing& ring):
    InterAtomArom(ring)
{

}

bool InterCationPI::isInteracting()
{
    if (mAtom == nullptr)throw_line("840101",
                                    "InterCationPI::isInteracting",
                                    "No cation set");
    runMath();
    if (mData.mRc > mDistThres)return false;
    if (mData.mTheta > mAngleCenter+mAngleRange
            ||mData.mTheta < mAngleCenter-mAngleRange)return false;
    if (mData.mDSide > 1.2 )return false;
    return true;
}




bool InterCationPI::isInteracting(InterData& data)
{
    try{
        if (!isInteracting())return false;
        InterObj pObj(mRing.getAtomCenter(),
                      *mAtom,
                      INTER::PI_CATION,
                      mData.mRc);
        pObj.setRing1(mRing);
        pObj.setAngle(mData.mTheta);
        data.addInter(pObj);
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("InterCationPI::isInteracting");
        throw;
    }
}

void InterCationPI::exportGeom(std::ofstream &ofs)
{
    InterData data;
    MMResidue &res=mRing.getResidue();

    ofs <<"\t"<<res.getParent().getName()
       <<"\t"<<res.getChainName()
      <<"\t"<<res.getName()
     <<"\t"<<res.getFID()<<"\t";
    for(size_t iR=0;iR< mRing.numAtoms();++iR)
        ofs<<mRing.getAtom(iR).getName()<<",";
    ofs <<"\t"<<mAtom->getName()<<"\t"
       <<mAtom->getResidue().getName()
      <<"\t"<<mAtom->getResidue().getFID()<<"\t"
     <<mAtom->getResidue().getChainName()
    <<"\t"<<mData.mRc<<"\t"<<mData.mTheta*180/M_PI<<"\t"<<mData.mDSide<<"\t"<<isInteracting(data)<<"\n";


}
