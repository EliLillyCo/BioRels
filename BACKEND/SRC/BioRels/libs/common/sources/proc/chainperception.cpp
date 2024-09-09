
#include <sstream>
#include "headers/proc/chainperception.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/math/grid_utils.h"
#include "headers/statics/logger.h"
#include"headers/molecule/mmresidue_utils.h"

unsigned short  protspace::ChainPerception::mAAPeptSep=8;
double protspace::ChainPerception::mDWatThres=6.5;

protspace::ChainPerception::ChainPerception():
    mGrid(3.25,3.25),
    mGroups(nullptr),
    mNGroup(0)
{

}
void protspace::ChainPerception::clear()
{
   if (mGroups != nullptr) delete[] mGroups;
   mGrid.clear();
}



void protspace::ChainPerception::process(protspace::MacroMole &pMole)
{
    const size_t nChain(pMole.numChains());
    const size_t nRes(pMole.numResidue());
    mNGroup=0;
    clear();
    mGroups = new signed short[nRes];
    for(size_t i=0;i<nRes;++i)mGroups[i]=-1;

    for(size_t iChain=0;iChain < nChain;++iChain)
    {
        MMChain& pChain= pMole.getChain(iChain);

        signed short starting=mNGroup;
        getGroups(pChain);
        assignChainType(pChain,starting);
        controlFID(pChain);
    }

}

void protspace::ChainPerception::getGroups(const MMChain& pChain)
{
    const size_t nRes(pChain.numResidue());
    for(size_t iRes=0;iRes < nRes;++iRes)
        scanAtoms(pChain.getResidue(iRes));

}

void protspace::ChainPerception::scanAtoms(const MMResidue& pRes)
{
    const size_t nAtm(pRes.numAtoms());
    for(size_t iAtm=0;iAtm < nAtm;++iAtm)
    {
        const MMAtom& pAtom = pRes.getAtom(iAtm);
        if (pAtom.isHydrogen())continue;
        for(size_t iBd=0;iBd<pAtom.numBonds();++iBd)
        {
            const MMBond& bd=pAtom.getBond(iBd);
            const MMAtom& atm2 = bd.getOther(pAtom);
            if (atm2.getResidue().getMID() == pRes.getMID())continue;
            if (&pRes.getChain()!= &atm2.getResidue().getChain())continue;
//            std::cout <<"#######################\n";
//            std::cout << bd.toString()<<"\n";
//            std::cout <<pAtom.toString()<<"\n"<<atm2.toString()<<"\n";
            updateGroup(pRes,atm2.getResidue());
        }
    }
}


void protspace::ChainPerception::updateGroup(const MMResidue& pRes1,
                                             const MMResidue& pRes2)
{
    const size_t nGr(pRes1.getChain().numResidue());
    signed short& group1 = mGroups[pRes1.getMID()];
    signed short& group2 = mGroups[pRes2.getMID()];
  //  std::cout <<pRes1.getIdentifier()<<"\t"<<pRes2.getIdentifier()<<"\t"<<group1<<"\t"<<group2;
  //      std::cout << group1<<"\t"<<group2<<std::endl;
    if (group1 ==-1 && group2 == -1)
    {
        group1=mNGroup;
        group2=mNGroup;mNGroup++;
    }
    else if (group1==group2 && group1 != -1){//std::cout <<"\n";
        return;}
    else if (group1==-1 && group2 != -1)    group1=group2;
    else if (group1!=-1 && group2 == -1)    group2=group1;
    else if (group1!=-1 && group2 != -1)
    {
        const signed short minV(std::min(group1,group2));
        const signed short maxV(std::max(group1,group2));
        for(size_t i=0;i<nGr;++i)
        {
            if (mGroups[i]==maxV)mGroups[i]=minV;
            else if (mGroups[i]>maxV)mGroups[i]--;
        }
        mNGroup--;
    }
  // std::cout << "\tAFTER:"<<group1<<"\t"<<group2<<std::endl;


}

protspace::ChainPerception::~ChainPerception()
{
    if (mGroups != nullptr)delete[]mGroups;
}


void protspace::ChainPerception::assignChainType(MMChain& pChain, signed short starting)
{
    double nAA=0, nMOD_AA=0, nSUGAR=0, nOTH=0, nNU=0,nAll=0,nHOH=0;
    const size_t nRes(pChain.numResidue());
    const signed short allGroup(mNGroup-starting);

//    for(size_t iRes=0;iRes < nRes;++iRes)
//    {
//        std::cout <<pChain.getResidue(iRes).getIdentifier()<<"\t"<<RESTYPE::typeToName.at(pChain.getResidue(iRes).getResType())<<"\t"<<mGroups[pChain.getResidue(iRes).getMID()]<<"\t"<<starting<<"\n";
//    }
    //std::cout <<"PROCESSING\t"<<starting<<"\t"<<mNGroup<<"\n";
    bool hasRes=false;
    for(;starting<=mNGroup;++starting)
    {
        //LOG("GROUP:" +std::to_string(starting));

        for(size_t iRes=0;iRes < nRes;++iRes)
        {
            if (mGroups[pChain.getResidue(iRes).getMID()]!=starting)continue;
            hasRes=true;
            MMResidue& pRes = pChain.getResidue(iRes);

            LOG(pRes.getIdentifier());
            const uint16_t& resType=pRes.getResType();
            if      (resType==RESTYPE::STANDARD_AA) nAA++;
            else if (resType==RESTYPE::MODIFIED_AA) nMOD_AA++;
            else if (resType==RESTYPE::SUGAR)       nSUGAR++;
            else if (resType==RESTYPE::NUCLEIC_ACID)nNU++;
            else if (resType==RESTYPE::WATER)nHOH++;
            else nOTH++;
            nAll++;
        }


    }

    if (allGroup==1)
    {
        if ((nAA+nMOD_AA)/nAll > 0.5)
        {
           if (nAll <= mAAPeptSep) pChain.setType(CHAINTYPE::PEPTIDE);
           else pChain.setType(CHAINTYPE::PROTEIN);
        }
        else if (nNU/nAll > 0.5) pChain.setType(CHAINTYPE::NUCLEIC);
        else pChain.setType(CHAINTYPE::LIGAND);

    }
    else if(hasRes)
    {

        if ((nAA+nMOD_AA)/nAll > 0.5) pChain.setType(CHAINTYPE::PROTEIN);
        else if (nNU/nAll > 0.5) pChain.setType(CHAINTYPE::NUCLEIC);
        else if (nHOH/nAll >0.9999)pChain.setType(CHAINTYPE::WATER);
            else if (allGroup==0) pChain.setType(CHAINTYPE::LIGAND);
        else
            LOG_ERR("UNKNOWN CHAIN TYPE : "+pChain.getName()+
                    " nAA: "+std::to_string(nAA)+
                    " nMOD:"+std::to_string(nMOD_AA)+
                    " nSUGAR:"+std::to_string(nSUGAR)+
                    " nNUCLEIC:"+std::to_string(nNU)+
                    " nOTHER:"+std::to_string(nOTH));
    }
    LOG("CHAIN STAT: "+pChain.getName()+
            " nAA: "+std::to_string(nAA)+
            " nMOD:"+std::to_string(nMOD_AA)+
            " nSUGAR:"+std::to_string(nSUGAR)+
            " nNUCLEIC:"+std::to_string(nNU)+
            " nOTHER:"+std::to_string(nOTH)+" => "+CHAINTYPE::typeToName.at(pChain.getType()));

}


void protspace::ChainPerception::reassignOther(MacroMole& pMole)
try{
    mGrid.considerMolecule(pMole);
    mGrid.createGrid();
    const size_t nRes(pMole.numResidue());
    const size_t nChain(pMole.numChains());
    assert(mGroups!= nullptr);
    std::vector<MMAtom*> list;
    std::map<signed char,int> maps;
    for(size_t iChain=0;iChain < nChain;++iChain)
        maps.insert(std::make_pair(iChain,0));
    std::string log_t;
    std::map<protspace::MMResidue*, protspace::MMChain*> toApply;
    for(size_t iRes=0;iRes < nRes;++iRes)
    {
        MMResidue& pRes=pMole.getResidue(iRes);
        log_t=pRes.getIdentifier()+"\t"+std::to_string(mGroups[pRes.getMID()]);
        try {
            if (mGroups[pRes.getMID()] != -1)continue;

            for (auto it = maps.begin(); it != maps.end(); ++it)(*it).second = 0;
            list.clear();
            protspace::getAtomClose(list, pRes, mDWatThres, mGrid, true);
            for (size_t iResCl = 0; iResCl < list.size(); ++iResCl) {
                const MMAtom &pAtm = *list.at(iResCl);
                if (pAtm.isHydrogen())continue;
                const MMResidue &pResCl = pAtm.getResidue();
                if (mGroups[pResCl.getMID()] == -1)continue;
                assert(protspace::getShortestDistance(pRes,pResCl) < mDWatThres*1.5);
                if (pResCl.getResType() != RESTYPE::STANDARD_AA &&
                    pResCl.getResType() != RESTYPE::MODIFIED_AA &&
                    pResCl.getResType() != RESTYPE::NUCLEIC_ACID)
                    continue;
                maps.at(pResCl.getChainPos())++;
            }

            int max = -1;
            signed char best = -3;
            for (auto it = maps.begin(); it != maps.end(); ++it) {

                log_t+="\t" +pMole.getChain((*it).first).getName()+"\t"+std::to_string((*it).second);
                if ((*it).second < max) continue;
                max = (*it).second;
                best = (*it).first;
            }
            LOG(log_t);

            if (best == -3)continue;
            if (best == pRes.getChainPos())
            {
                /// In the case of a metal or prosthetic group (such as Heme)
                /// we can to keep them as part of the protein
                if (pRes.getResType()==RESTYPE::METAL ||
                        pRes.getResType()==RESTYPE::PROSTHETIC)
                    mGroups[pRes.getMID()]=best;

                continue;
            }
            toApply[&pRes]=&pMole.getChain(best);


        }catch(ProtExcept &e)
        {
            e.addDescription("Residue involved : "+pRes.getIdentifier());
            std::ostringstream oss; oss<<&pRes.getParent()<<" " <<&pMole;
            e.addDescription(oss.str());
            e.addHierarchy("ChainPerception::reassignWater");
            throw;
        }
    }

    for(auto it:toApply)
    {
        protspace::MMResidue& pRes= *(it.first);
        protspace::MMChain& pChain =*(it.second);
        LOG("MOVING "+pRes.getIdentifier()+" TO CHAIN : "+pChain.getName());

    pMole.moveResidueToChain(pRes, pChain);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="220101");///Grid cannot be already created
    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="352001"&&e.getId()!="352002");///Residue& chain must exists

    e.addHierarchy("ChainPerception::reassignWater");
    throw;
}

void protspace::ChainPerception::controlFID(MMChain& pChain) {
    std::vector<int> list;
    std::vector<MMResidue*> listPb;
    const size_t nRes(pChain.numResidue());
    if (pChain.numResidue()==0)return;
    size_t issue=0;int maxFID=-1;
    for(size_t iRes=0;iRes<nRes;++iRes)
    {
        MMResidue &pRes= pChain.getResidue(iRes);
        if (pRes.getFID()>maxFID)maxFID=pRes.getFID();
        if (std::find(list.begin(),list.end(),pRes.getFID())==list.end())
            list.push_back(pRes.getFID());
        else { ++issue;listPb.push_back(&pRes);}
    }
    if (issue==0)return;
    /// Moving water:
    for(MMResidue* res:listPb)
    {
        if (res->getResType()!=RESTYPE::STANDARD_AA && res->getResType()!=RESTYPE::MODIFIED_AA)
        {

            LOG("Changing residue ID : "+res->getIdentifier()+" to ID : "+std::to_string(maxFID));
            maxFID++;
            mFIDMap[pChain.getName()][res->getFID()]=res;
            res->setFID(maxFID);
            --issue;
        }

    }
    if (issue==0) return;
    int prevFID=pChain.getResidue(0).getFID();

    for(size_t iRes=1;iRes< nRes;++iRes) {
        MMResidue &pRes= pChain.getResidue(iRes);

        if (prevFID!= pRes.getFID()){prevFID=pRes.getFID();continue;}
        prevFID=pRes.getFID();
        if (iRes+1 ==nRes)break;
        MMResidue &pRes2= pChain.getResidue(iRes+1);

        pRes.setFID(pRes.getFID()+1);
        if (pRes2.getFID() > pRes.getFID()+1)
        {
            continue;
        }
        for(size_t jRes=iRes+1;jRes < nRes;++jRes)
        {
            MMResidue &pRes3= pChain.getResidue(jRes);
            if (pRes3.getFID() > pChain.getResidue(jRes-1).getFID()+1)break;
            if (pRes3.getFID()== pChain.getResidue(jRes-1).getFID())
            {
                LOG("Changing residue ID : "+pRes3.getIdentifier()+" to ID : "+std::to_string(pRes3.getFID()+1));
                mFIDMap[pChain.getName()][pRes3.getFID()]=&pRes3;
                pRes3.setFID(pRes3.getFID()+1);
            }
        }
    }
}

void protspace::ChainPerception::getSingleton(protspace::MacroMole &pMole,std::vector<protspace::MMResidue *> &list) const
{
    const size_t nRes(pMole.numResidue());
    for(size_t iRes=0;iRes < nRes;++iRes)
    {
        if (mGroups[pMole.getResidue(iRes).getMID()]==-1) list.push_back(&pMole.getResidue(iRes));
    }
}


