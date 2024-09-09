#include <math.h>
#include "headers/proc/protalign.h"
#include "headers/sequence/seqalign.h"

using namespace protspace;
using namespace std;


ProtAlign::ChainPair::ChainPair(MMChain& pRef, MMChain& pComp):
    mRefSeq(pRef),
    mCompSeq(pComp),
    mSeqAlign(mRefSeq,mCompSeq)

{

}

ProtAlign::ChainPair::ChainPair(MMChain& pRef,
                                MMChain& pComp,
                                SeqPairAlign &pAlign):
    mRefSeq(pRef),
    mCompSeq(pComp),
    mSeqAlign(pAlign)
{

}



ProtAlign::ChainPair::~ChainPair()
{

}




ProtAlign::ProtAlign(MacroMole& pRef,MacroMole& pComp)
    :mReference(pRef),
      mComparison(pComp),
      mSeqAlign(10,true)
{
    mResRlist.reserve(300);
    mResClist.reserve(300);
}


ProtAlign::~ProtAlign()
{
}



void ProtAlign::addChainPair(const std::string& pRefChainName,
                             const std::string& pCompChainName)
{
    try{
        MMChain& refChain=mReference.getChain(pRefChainName);
        MMChain& compChain=mComparison.getChain(pCompChainName);
        ChainPair* chPair= new ChainPair(refChain,compChain);
        mSeqAlign.add(chPair);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("ProtAlign::addChainPair");
        e.addDescription("Requested comparison chain name : "+pCompChainName);
        e.addDescription("Requested reference chain name : "+pRefChainName);
        throw;
    }catch(std::bad_alloc &e)
    {
        throw_line("640301",
                   "ProtAlign::addChainPair",
                   "Bad allocation "+std::string(e.what()));
    }
}





void ProtAlign::addChainPair(MMChain& pRefChain,
                             MMChain& pCompChain)
try{
    if (&pRefChain.getMolecule() != &mReference)
        throw_line("640101",
                   "ProtAlign::addChainPair",
                   "Given reference chain is not part of the reference molecule");
    if (&pCompChain.getMolecule() != &mComparison)
        throw_line("640102",
                   "ProtAlign::addChainPair",
                   "Given comparison chain is not part of the comparison molecule");
    ChainPair* chPair= new ChainPair(pRefChain,pCompChain);
    mSeqAlign.add(chPair);
}catch(std::bad_alloc &e)
{
    throw_line("640103",
               "ProtAlign::addChainPair",
               "Bad allocation "+std::string(e.what()));
}





void ProtAlign::addChainPair(MMChain& pRefChain,
                             MMChain& pCompChain,
                             SeqPairAlign& pAlign)
try{
    if (&pRefChain.getMolecule() != &mReference)
        throw_line("640201",
                   "ProtAlign::addChainPair",
                   "Given reference chain is not part of the reference molecule");
    if (&pCompChain.getMolecule() != &mComparison)
        throw_line("640202",
                   "ProtAlign::addChainPair",
                   "Given comparison chain is not part of the comparison molecule");
    ChainPair* chPair= new ChainPair(pRefChain,pCompChain,pAlign);
    mSeqAlign.add(chPair);
}catch(std::bad_alloc &e)
{
    throw_line("640203","ProtAlign::addChainPair","Bad allocation "+std::string(e.what()));
}







void ProtAlign::performSequenceAlignment()
{
    for(size_t i=0;i<mSeqAlign.size();++i)
    {
        ChainPair& cpair=mSeqAlign.get(i);
        try{

            if (!cpair.mSeqAlign.getCompPosVector().empty()
                    &&!cpair.mSeqAlign.getRefPosVector().empty())continue;
            SeqAlign seq(cpair.mRefSeq,cpair.mCompSeq);
            seq.align(cpair.mSeqAlign);
        }catch(ProtExcept &e)
        {

            e.addDescription("Reference chain "+cpair.mRefSeq.getChain().getName());
            e.addDescription("Comparison chain "+cpair.mCompSeq.getChain().getName());
            e.addHierarchy("ProtAlign::performSequenceAlignment");
            throw;
        }

    }
}






void ProtAlign::getResidueLists()
{
    mResRlist.clear();
    mResClist.clear();
    std::vector<const Coords*> refCoo,compCoo;
    size_t pos;
    for(size_t i=0;i<mSeqAlign.size();++i)
    {
        ChainPair& cpair = mSeqAlign.get(i);
        const SeqPairAlign& align=cpair.mSeqAlign;
        const vector<int>& refpos=align.getRefPosVector();
        const vector<int>& compos =align.getCompPosVector();

        for(size_t i=0;i<refpos.size();++i)
        {

            const int& rpos = refpos.at(i);
            const int& cpos = compos.at(i);
            if (rpos == -1 || cpos==-1)continue;
            try{
                MMResidue& rres= cpair.mRefSeq.getResidue(rpos);
                MMResidue& cres= cpair.mCompSeq.getResidue(cpos);

                mResRlist.push_back(&rres);
                mResClist.push_back(&cres);
                if (rres.getAtom("CA",pos))refCoo.push_back(&rres.getAtom(pos).pos());
                else refCoo.push_back(&rres.getAtom(0).pos());
                if (cres.getAtom("CA",pos))compCoo.push_back(&cres.getAtom(pos).pos());
                else compCoo.push_back(&rres.getAtom(0).pos());
            }catch(ProtExcept &e)
            {
                e.addHierarchy("ProtAlign::getResidueLists");
                e.addHierarchy("Ref Position "+std::to_string(rpos));
                e.addHierarchy("Comp Position "+std::to_string(cpos));
                throw;
            }
        }
    }
    mAligner.loadCoordsToRigid(refCoo);
    mAligner.loadCoordsToMobile(compCoo);
}



void ProtAlign::applyRotation(protspace::MacroMole& mole)
{
    std::vector<Coords*> listcoo;
    for(size_t i=0;i<mole.numAtoms();++i)
    {
        listcoo.push_back(&mole.getAtom(i).pos());
    }
    mAligner.getParams().mobilToRef(listcoo);
}




void ProtAlign::applyRotation()
{
    std::vector<Coords*> listcoo;
    for(size_t i=0;i<mComparison.numAtoms();++i)
    {
        listcoo.push_back(&mComparison.getAtom(i).pos());
    }
    mAligner.getParams().mobilToRef(listcoo);
}





void ProtAlign::updateListCoords(Clique<MMResidue>& clique)
try{
    std::vector<const Coords*> refCoo,compCoo;
    mResUsedClist.clear();
    mResUsedRlist.clear();
    size_t pos;
    for(size_t i=0;i<clique.listpair.size();++i)
    {
        Pair<MMResidue>& paire=*clique.listpair.at(i);
        MMResidue& rres=paire.obj1;
        MMResidue& cres=paire.obj2;
        mResUsedRlist.push_back(&rres);
        mResUsedClist.push_back(&cres);
        if (rres.getAtom("CA",pos))
            refCoo.push_back(&rres.getAtom(pos).pos());
        else refCoo.push_back(&rres.getAtom(0).pos());
        if (cres.getAtom("CA",pos))
            compCoo.push_back(&cres.getAtom(pos).pos());
        else compCoo.push_back(&cres.getAtom(0).pos());
    }
    mAligner.loadCoordsToRigid(refCoo);
    mAligner.loadCoordsToMobile(compCoo);
}catch(ProtExcept &e)
{
    e.addHierarchy("ProtAlign::updateListCoords");
    throw;
}






void ProtAlign::updateListCoords()
try{
    std::vector<const Coords*> refCoo,compCoo;
    size_t pos;
    for(size_t i=0;i<mResRlist.size();++i)
    {
        MMResidue& rres=*mResRlist.at(i);
        MMResidue& cres=*mResClist.at(i);
        if (rres.getAtom("CA",pos))
            refCoo.push_back(&rres.getAtom(pos).pos());
        else refCoo.push_back(&rres.getAtom(0).pos());
        if (cres.getAtom("CA",pos))
            compCoo.push_back(&cres.getAtom(pos).pos());
        else compCoo.push_back(&cres.getAtom(0).pos());
    }
    mAligner.loadCoordsToRigid(refCoo);
    mAligner.loadCoordsToMobile(compCoo);
}catch(ProtExcept &e)
{
    e.addHierarchy("ProtAlign::updateListCoords");
    throw;
}






void ProtAlign::scanThreshold(double pThres)
try{
    DMatrix mDistMatrix;
    GraphMatch<MMResidue> gmatch;
    gmatch.setFullScan(false);
    gmatch.isVirtual(false);
    genPairs(gmatch,mDistMatrix);
    const double max(std::max(4.0,pThres+2.0));
    for(; pThres<=max; pThres+=0.2)
    {
        gmatch.clearEdges();
        genLinks(pThres,gmatch,mDistMatrix);
        if(seekMinResThres(gmatch)>0)break;
    }
    if (gmatch.numCliques()==0)
    {
        for(; pThres<=8; pThres+=0.5)
        {
            gmatch.clearEdges();
            genLinks(pThres,gmatch,mDistMatrix);
            if(seekMinResThres(gmatch)>0)break;
        }
    }
    if (gmatch.numCliques()==0) throw_line("640501",
                                           "Protalign::scanThreshold",
                                           "No alignment found");

    //cout <<"NUM CLIQUES:"<< gmatch.numCliques()<<endl;
    size_t best=0;size_t bestcli=0;
    for(size_t i=0;i<gmatch.numCliques();++i)
    {
        Clique<MMResidue>& clique = gmatch.getClique(i);
        if ( clique.listpair.size() < best)continue;
        best=clique.listpair.size();bestcli=i;
    }
    updateListCoords(gmatch.getClique(bestcli));

}catch(ProtExcept &e)
{
    assert(e.getId()!="110501");///   Clique must exists
    assert(e.getId()!="210201" && e.getId()!="210203");/// Coordinates must exists
    assert(e.getId()!="210203");/// Size must be identical
    e.addHierarchy("Protalign::scanThreshold");
    throw;
}


void ProtAlign::createFromCA()
try{
    std::vector<const Coords*> refCoo,compCoo;
    size_t posR,posC;

    for(size_t i=0;i<mSeqAlign.size();++i)
    {
        ChainPair& cpair=mSeqAlign.get(i);
        std::map<protspace::MMResidue*,const Coords*> tmpC;
        protspace::MMChain& cChain=cpair.mCompSeq.getChain();
        protspace::MMChain& rChain=cpair.mRefSeq.getChain();
std::cout <<"COMPARE : "<<rChain.getName()<<" " <<cChain.getName()<<"\n";
        for(size_t iC=0;iC< cChain.numResidue();++iC)
        {
            protspace::MMResidue& resC=cChain.getResidue(iC);
            if (!resC.getAtom("CA",posC))continue;
            tmpC[&resC]=(&resC.getAtom(posC).pos());
        }

        for(size_t iR=0;iR< rChain.numResidue();++iR)
        {
            protspace::MMResidue& resR=rChain.getResidue(iR);
            if (!resR.getAtom("CA",posR))continue;
            const Coords* p=&resR.getAtom(posR).pos();
            for(auto t:tmpC)
            {
                refCoo.push_back(p);mResRlist.push_back(&resR);
                compCoo.push_back(t.second);mResClist.push_back(t.first);
                std::cout <<mResRlist.size()<<"\t"<<resR.getIdentifier()<<"\t"<<t.first->getIdentifier()<<"\n";
            }

        }
    }

    mAligner.loadCoordsToRigid(refCoo);
    mAligner.loadCoordsToMobile(compCoo);
}catch(ProtExcept &e)
{
    e.addHierarchy("ProtAlign::createFromCA");
    throw;
}


double ProtAlign::align(const double &pThres,
                        const bool& wSeqAlign,
                        const bool& fromCA)
{
    if (mSeqAlign.size()==0)
        throw_line("640401",
                   "ProtAlign::align",
                   "No chain assigned "+mReference.getName()+" "+mComparison.getName());
    try{
        if (wSeqAlign)
        {
            performSequenceAlignment();
            getResidueLists();
        }
        else if (fromCA)
        {
            createFromCA();
        }
        else
        {
            if (mResRlist.empty())
                throw_line("640402",
                           "ProtAlign::align",
                           "List of reference residue not given");
            if (mResClist.empty())
                throw_line("640403",
                           "ProtAlign::align",
                           "List of comparison residue not given");
            if (mResRlist.size()!= mResClist.size())
                throw_line("640404",
                           "ProtAlign::align",
                           "Different size list");
            updateListCoords();
        }
        if (pThres>0.0) scanThreshold(pThres);
        const double rmsd(mAligner.calcRotation());
        applyRotation();
        return rmsd;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("ProtAlign::align");

        throw;
    }
}






size_t ProtAlign::seekMinResThres(GraphMatch<MMResidue>& gmatch)const
try{
    const size_t size(mResRlist.size());

    for(size_t perc=100;perc!=30;perc-=5){

        //        cout <<"PERC"<<perc<<"::"<<ends;
        gmatch.isVirtual(false);
        gmatch.setMinSize(size*perc/100);
        gmatch.calcCliques();
        //        cout <<perc<< " " << gmatch.numCliques()<<"  "<<endl;
        if (gmatch.numCliques() ==0)continue;
        return gmatch.numCliques();
    }
    return 0;
}catch(ProtExcept &e)
{
    assert(e.getId()!="110401" && e.getId()!="110402");
    e.addHierarchy("ProtAlign::seekMinResThres");
    throw;
}



void ProtAlign::genLinks(const double& pThres,
                         GraphMatch<MMResidue>& gmatch,
                         DMatrix& distMatrix)
try{
    const size_t size(mResRlist.size());
    for(size_t i=0;i<size;++i)
    {
        for(size_t j=i+1;j<size;++j)
        {
            if (distMatrix.getVal(i,j)>pThres)continue;
            gmatch.addLink(gmatch.getPair(i),
                           gmatch.getPair(j));
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="200201" && e.getId()!="200202");///matrix position must exist
    assert(e.getId()!="110201"); /// pair must exists
    assert(e.getId()!="030301" && e.getId()!="030302");///Pairs must be in gmatch

    e.addHierarchy("ProtAlign::genLinks");
    throw;
}


void ProtAlign::genPairs(GraphMatch<MMResidue>& gmatch,DMatrix& distMatrix)
try{
    const size_t size(mResRlist.size());
    for(size_t i=0;i<size;++i)
    {
        MMResidue& rres= *mResRlist.at(i);
        MMResidue& cres= *mResClist.at(i);
        gmatch.addPair(rres,cres);
    }
    distMatrix.resize(size,size,0);
    size_t pos=0;
    for(size_t i=0;i<size;++i)
    {
        const MMResidue& rRes1=*mResRlist.at(i);
        const MMAtom& rAtom1=rRes1.getAtom("CA",pos)?rRes1.getAtom(pos):rRes1.getAtom(0);
        const MMResidue& rRes2=*mResClist.at(i);
        const MMAtom& rAtom2=rRes2.getAtom("CA",pos)?rRes2.getAtom(pos):rRes2.getAtom(0);
        for(size_t j=i+1;j<size;++j)
        {
            const MMResidue& cRes1=*mResRlist.at(j);
            const MMAtom& cAtom1=cRes1.getAtom("CA",pos)?cRes1.getAtom(pos):cRes1.getAtom(0);
            const MMResidue& cRes2=*mResClist.at(j);
            const MMAtom& cAtom2=cRes2.getAtom("CA",pos)?cRes2.getAtom(pos):cRes2.getAtom(0);
            const double dist1(rAtom1.dist(cAtom1));
            const double dist2(rAtom2.dist(cAtom2));
            const double diff(fabs(dist1-dist2));
            //            cout << rRes1.getIdentifier()<<"--"<<rRes2.getIdentifier()<<"\t"<<cRes1.getIdentifier()<<"--"<<cRes2.getIdentifier()<<"\t"<<diff<<endl;
            distMatrix.setVal(i,j,diff);
            distMatrix.setVal(j,i,diff);
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="320501" && e.getId()!="320502");/// Atom must be found
    assert(e.getId()!="200301" && e.getId()!="200302");/// Matrix position must exist
    e.addHierarchy("ProtAlign::genPairs");

}




bool ProtAlign::isResInRefList(const MMResidue& res)const
{
    return (std::find(mResRlist.begin(),mResRlist.end(),&res)!=mResRlist.end());
}



bool ProtAlign::isResInCompList(const MMResidue& res)const
{
    return (std::find(mResClist.begin(),mResClist.end(),&res)!=mResClist.end());
}




void ProtAlign::clear() {
    mResRlist.clear();mResClist.clear();
    mSeqAlign.clear();mResUsedRlist.clear();mResUsedClist.clear();
    mAligner.clear();
}


