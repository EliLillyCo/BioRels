#include <headers/statics/intertypes.h>
#include "headers/statics/logger.h"
#include "headers/proc/matchresidue.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/mmatom.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
//#include "headers/molecule/bondperception.h"




protspace::MatchResidue::MatchResidue(MMResidue& pMoleRes, UIntMatrix &pResMatrix):
    mMoleRes(pMoleRes),mResMatrix(pResMatrix),
    mMoleResConsidered(new bool[pMoleRes.numAtoms()]),
    mHETConsidered(nullptr),
    nMoleResHeavyAtom(numHeavyAtom(pMoleRes)),
    nMResAtom(pMoleRes.numAtoms()),
    mResName(pMoleRes.getName()),
    mOptWHydrogen(false)
{
    preparepos();
    for(size_t i=0;i<nMResAtom;++i)mMoleResConsidered[i]=false;
}


void protspace::MatchResidue::preparepos()
{
    for(size_t i=0;i< mMoleRes.numAtoms();++i)
    {
        mResMatPos.insert(std::make_pair(&mMoleRes.getAtom(i),i));
    }
}


protspace::MatchResidue::~MatchResidue()
{
    if (mHETConsidered != nullptr)delete[] mHETConsidered;
    if (mMoleResConsidered != nullptr)delete[] mMoleResConsidered;
}




bool protspace::MatchResidue::processSingleAtom(const MMAtom& pHETAtom) const
throw(ProtExcept)

try{
    MMAtom& atom = mMoleRes.getAtom(0);
    pushAtomData(atom,pHETAtom);
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="320501" && e.getId()!="320502");/// Atom must exist
    e.addHierarchy(" MatchTemplate::processSingleAtom");
    throw;
}


void protspace::MatchResidue::updateMatrix()

try
{
    const std::vector<MMAtom*>& pAtmList=mMoleRes.getAllAtoms();
    mResMatrix.resize(nMResAtom,nMResAtom);
    protspace::getDistanceMatrix(mMoleRes.getParent(),mResMatrix,pAtmList);
}
catch(ProtExcept &e)
{
    e.addHierarchy("MatchResidue::updateMatrix");
    throw;
}




void protspace::MatchResidue::pushAtomData(MMAtom& to, const MMAtom& from)const
try{
    //std::cout <<to.getIdentifier()<<"::"<<to.getMOL2()<<"\t"<<from.getIdentifier()<<"::"<<from.getMOL2()<<"\n";
    to.setMOL2Type(from.getMOL2());
    to.setName(from.getName());
    to.setFormalCharge(from.getFormalCharge());
    assignHydrogenName(to,from);
    //std::cout <<"\t"<<to.getIdentifier()<<"::"<<to.getMOL2()<<"\t"<<from.getIdentifier()<<"::"<<from.getMOL2()<<"\n";
}catch(ProtExcept &e)
{
    e.addHierarchy("MatchResidue::pushAtomData");
    e.addDescription("From Atom :" +from.toString());
    e.addDescription("To Atom :" +to.toString());
    throw;
}




void protspace::MatchResidue::assignHydrogenName(MMAtom&pAtRes, const MMAtom& pHET)const
{
    const size_t nAB(pAtRes.numBonds());
    const size_t nAH(pHET.numBonds());
    std::vector<std::string> mNames;
    for(size_t i=0;i<nAH;++i)
        if(pHET.getAtom(i).isHydrogen())
            mNames.push_back(pHET.getAtom(i).getName());
    size_t posH=0;



    for(size_t i=0;i<nAB;++i)
    {
        MMAtom& pAtom=pAtRes.getAtom(i);
        if (!pAtom.isHydrogen())continue;
        pAtRes.getBond(pAtom).setBondType(BOND::SINGLE);
        ///TODO #15426
        if (posH>= mNames.size())
        {
            ///TODO - Handle error / Log / verbose ?
            LOG_ERR("Number of Hydrogen mismatch template "+pAtRes.getIdentifier());
            return;
        }

        if (mNames.at(posH)!= "")
        {
            pAtom.setName(mNames.at(posH));
        }
        else pAtom.setName("H");
        ++posH;
    }
}



bool protspace::MatchResidue::processDoubleAtom(const MacroMole& HETMolecule) const
throw(ProtExcept)

try{
    const size_t pRAt(mMoleRes.numAtoms());
    const size_t pHAt(HETMolecule.numAtoms());
    size_t pRHeavy1=pRAt,pRHeavy2=pRAt, pHHeavy1=pHAt,pHHeavy2=pHAt;
    for(size_t i=0;i< pRAt;++i)
    {
        MMAtom& atm= mMoleRes.getAtom(i);
        if (atm.getName().at(0)=='H'){atm.setMOL2Type("H");continue;}
        if (pRHeavy1==pRAt) pRHeavy1=i;    else pRHeavy2=i;
    }
    for(size_t i=0;i< pHAt;++i)
    {
        const MMAtom& atm= HETMolecule.getAtom(i);
        if (atm.getName().at(0)=='H')continue;
        if (pHHeavy1==pHAt) pHHeavy1=i;
        else pHHeavy2=i;

    }

    MMAtom& pRAt1=mMoleRes.getAtom(pRHeavy1);
    MMAtom& pRAt2=mMoleRes.getAtom(pRHeavy2);

    const MMAtom& pHAt1=HETMolecule.getAtom(pHHeavy1);
    const MMAtom& pHAt2=HETMolecule.getAtom(pHHeavy2);
    const bool sameElem(pHAt1.getAtomicNum()==pHAt2.getAtomicNum());
    if (sameElem)
    {
        pushAtomData(pRAt1,pHAt1);
        pushAtomData(pRAt2,pHAt2);
    }
    else
    {
        if (pRAt1.getAtomicNum()==pHAt1.getAtomicNum())
        {
            pushAtomData(pRAt1,pHAt1);
            pushAtomData(pRAt2,pHAt2);
        }
        else
        {
            pushAtomData(pRAt1,pHAt2);
            pushAtomData(pRAt2,pHAt1);
        }
    }
    if (!pHAt1.hasBondWith(pHAt2))return true;
    pRAt1.getBond(pRAt2).setBondType(pHAt1.getBond(pHAt2).getType());
    return true;

}catch(ProtExcept &e)
{
    assert(e.getId()!="320501" && e.getId()!="320502");/// Atom must exist
    e.addHierarchy(" MatchTemplate::processDoubleAtom");
    throw;
}




size_t protspace::MatchResidue::calcCliques(const size_t& size)
{
    try{

       // std::cout <<"NUMBER OF PAIRS/EDGES:"<<mMoleRes.getIdentifier()<<"\t"<<mGraphmatch.numPairs()<<"/"<<mGraphmatch.numEdges()<<std::endl;
       // std::cout << mMoleRes.getIdentifier()<<"\t"<<((size==0)?nMoleResHeavyAtom-1:size)<<std::endl;
        mGraphmatch.setMinSize((size==0)?nMoleResHeavyAtom-1:size);
        mGraphmatch.isVirtual(false);
        mGraphmatch.calcCliques();
         // std::cout <<"NUM CLIQUE FOR "<<" " <<mMoleRes.getIdentifier()<<" " <<mGraphmatch.numCliques()<<std::endl;
        return (mGraphmatch.numCliques());

    }
    catch(ProtExcept &e)
    {

        e.addHierarchy("MatchResidue::calcCliques");
        throw;
    }
}




bool protspace::MatchResidue::assignElement()
try{
    bool mod=false;
    for(size_t iAtm =0; iAtm < mMoleRes.numAtoms();++iAtm)
    {
        MMAtom& atm = mMoleRes.getAtom(iAtm);
        if (atm.getAtomicNum()!=0)continue;
        const std::string &atmName =atm.getName();
        if (atmName == "FE")atm.setAtomicType("FE");
        else if(atmName == "MG")atm.setAtomicType("MG");
        else atm.setAtomicType(atmName.substr(0,1));

        mod=true;

    }
    if (!mod)return false;
    ///TODO - BOND PERCEPTION
    ////// \brief bdp
    ///BondPerception bdp;bdp.process(mMoleRes.getParent());
    updateMatrix();
    return true;
}catch(ProtExcept &e)
{

    e.addHierarchy("MatchResidue::assignElement");
    throw;
}

