#include <sstream>
#include "headers/statics/errors.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmresidue.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/macromole.h"
#include "headers/parser/string_convert.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;

ErrorResidue::ErrorResidue(const std::string &pErrorDescription, MMResidue &pResidue) :
        mChain(pResidue.getChainName()),
        mResName(pResidue.getName()),
        mFId(pResidue.getFID()),
        mMId(pResidue.getMID()),
        mErrorDescription(pErrorDescription),
        mResidue(&pResidue) { }


ErrorResidue::ErrorResidue(const std::string &mChain, const std::string &mResName, const int mFId, const int mMId,
             const std::string &mErrorDescription) : mChain(mChain), mResName(mResName), mFId(mFId), mMId(mMId),
                                                     mErrorDescription(mErrorDescription) { }

ErrorBond::ErrorBond(MMBond& bond,
                     const uint16_t& errorFlag,
          const std::string& errorDescription):
    mChain1(bond.getAtom1().getResidue().getChainName()),
     mResidue1(bond.getAtom1().getResidue().getName()),
     mAtomName1(bond.getAtom1().getName()),
     mResidueId1(bond.getAtom1().getResidue().getFID()),
     mFId1(bond.getAtom1().getFID()),
     mMId1(bond.getAtom1().getMID()),
     mChain2(bond.getAtom2().getResidue().getChainName()),
     mResidue2(bond.getAtom2().getResidue().getName()),
     mAtomName2(bond.getAtom2().getName()),
     mResidueId2(bond.getAtom2().getResidue().getFID()),
     mFId2(bond.getAtom2().getFID()),
     mMId2(bond.getAtom2().getMID()),
     mType(bond.getType()),
     mDist(bond.dist()),
     mBond(&bond)
{

}

ErrorBond::ErrorBond(const MMAtom &atom1, const MMAtom &atom2, const uint16_t &errorFlag, const string &errorDescription)
    :
      mChain1(atom1.getResidue().getChainName()),
       mResidue1(atom1.getResidue().getName()),
       mAtomName1(atom1.getName()),
       mResidueId1(atom1.getResidue().getFID()),
       mFId1(atom1.getFID()),
       mMId1(atom1.getMID()),
       mChain2(atom2.getResidue().getChainName()),
       mResidue2(atom2.getResidue().getName()),
       mAtomName2(atom2.getName()),
       mResidueId2(atom2.getResidue().getFID()),
       mFId2(atom2.getFID()),
       mMId2(atom2.getMID()),
       mType(BOND::UNDEFINED),
       mDist(-1),
       mBond(nullptr),
      mErrorDescription(errorDescription)
{

}


std::string ErrorBond::toString()const
{
    ostringstream oss;
    if (mBond != nullptr)
        return ""+*mBond+" :: "+mErrorDescription;
    else {
        oss  << mChain1 << "|" << mResidue1 << "|" << mResidueId1 << "|" << mAtomName1
        << "\t"
        <<   mChain2 << "|" << mResidue2 << "|" << mResidueId2 << "|" << mAtomName2
        << "\t" << mErrorDescription;
        return oss.str();
    }


}






ErrorAtom::ErrorAtom(MMAtom& atom,
          const uint16_t& errorFlag,
          const std::string& errorDescription):
    mChain(atom.getResidue().getChainName()),
    mResidue(atom.getResidue().getName()),
    mAtomName(atom.getName()),
    mResidueId(atom.getResidue().getFID()),
    mFId(atom.getFID()),
    mMId(atom.getMID()),
    mAtom(&atom),
    mErrorDescription(errorDescription),
    mErrorFlag(errorFlag)
{

}
ErrorAtom::ErrorAtom(const MMResidue& res,
          const std::string atmName,
          const uint16_t& errorFlag,
          const std::string& errorDescription):

    mChain(res.getChainName()),
    mResidue(res.getName()),
    mAtomName(atmName),
    mResidueId(res.getFID()),
    mFId(-1),
    mMId(-1),
    mAtom(nullptr),
    mErrorDescription(errorDescription),
mErrorFlag(errorFlag)

{

}
std::string ErrorAtom::toString()const
{
    if (mAtom != nullptr)
    {
        ostringstream oss;
        oss<<"ERROR\tATOM\t"<<mAtom->getResidue().getChainName()<<"/"
           <<mAtom->getResidue().getName()<<"/"<<mAtom->getName()<<"/"<<mAtom->getResidue().getFID()
                <<"/"<<mAtom->getFID()<<"/"<<mAtom->getMID()<<"\t"<<mErrorDescription<<"\n";

    }
    else
    {
        ostringstream oss;
        oss<<"ERROR\tATOM \t"
        <<mChain<<"/"<<mResidue<<"/"<<mAtomName<<"/"<<mResidueId<<"/"<<mFId<<"/"<<mMId<<"\t"
        <<mErrorDescription<<"\n";
        return oss.str();
    }

}
