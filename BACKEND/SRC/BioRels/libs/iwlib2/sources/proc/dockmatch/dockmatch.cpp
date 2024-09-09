#include "headers/proc/dockmatch/dockmatch.h"
#include "headers/molecule/mmring.h"
#include "headers/parser/writerSDF.h"
#include "headers/parser/readers.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/math/rigidalign.h"
#include "headers/math/rigidbody.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/statics/strutils.h"
#include "headers/iwcore/macrotoiw.h"
#include "headers/proc/dockmatch/compclique.h"
using namespace protspace;
DockMatch::DockMatch(MacroMole &ref,
                     MacroMole &comp)
    :mRefMole(ref),mCompMole(comp),
      mGraphMatch(mRefMole,mCompMole),
      mAtomMatch(mRefMole,mCompMole),
      mLimitGr(10),
      mLimitAt(10),
      mKeepFused(false),
      mRingAromMatch(false),
      mRingBondMatch(false),
      mConsiderHydrogen(false),
      mRatio(0.4),
      mMinAtm(7)

{
    mDebug=false;
    MacroToIW miwr(mRefMole,false,false);
    MacroToIW miwc(mCompMole,false,false);

    mRefSMI=getLongestSMILES(miwr.getUniqueSMILES());
    mCompSMI=getLongestSMILES(miwc.getUniqueSMILES());



    mGraphMatch.addRule(mGraphMatch.BOND_DISTANCE);
    mGraphMatch.addRule(mGraphMatch.CONSIDER_RING);
    mGraphMatch.setMMBondDistThreshold(0);
    mGraphMatch.addRule(mGraphMatch.TWO_DIMENSION_MATCH);
    if (!mConsiderHydrogen) mGraphMatch.addRule(mGraphMatch.NO_HYDROGEN);
    mAtomMatch.addRule(mAtomMatch.ATOM_SYMBOL);
    mAtomMatch.addRule(mAtomMatch.BOND_DISTANCE);
    mAtomMatch.addRule(mAtomMatch.CONSIDER_RING);
    mAtomMatch.setDistThreshold(0);
    mAtomMatch.setMMBondDistThreshold(0);
    mAtomMatch.addRule(mAtomMatch.TWO_DIMENSION_MATCH);
    if (!mConsiderHydrogen) mAtomMatch.addRule(mAtomMatch.NO_HYDROGEN);
}

DockMatch::~DockMatch()
{
    for(auto it:mListScore)delete (it).second;
}

bool DockMatch::runAll()
{
    try{

        if (!performMatch(mGraphMatch))return false;
        if (mDebug)std::cout << "Graph Match count :"<<mGraphMatch.numCliques()<<std::endl;

        if (!performMatch(mAtomMatch))return false;
        if (mDebug)std::cout << "Atom match count:"<<mAtomMatch.numCliques()<<std::endl;

        compareCliques();
        if (mDebug)std::cout << "OUT COMPARE"<<std::endl;
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("DockMatch::runAll");
        throw;
    }
}



bool DockMatch::performMatch(MatchMole& match)
{
    try{
        const size_t nRAt=getNumHeavyAtoms(mRefMole);
        const size_t nCAt=getNumHeavyAtoms(mCompMole);
        if (nRAt<3)
            throw_line("2010101","DockMatch::performMatch",
                       "Number of atoms in reference too small "+
                       std::to_string(nRAt)+"/3");
        if (nCAt<3)
            throw_line("2010102","DockMatch::performMatch",
                       "Number of atoms in comparison too small "+
                       std::to_string(nRAt)+"/3");

        size_t maxAtm=std::min(nRAt,nCAt);
        if (maxAtm < mMinAtm) return false;
        do
        {
            if(maxAtm < mMinAtm)break;
            match.setMinSize(maxAtm);
            match.runMatch(match.SORT_BY_SIZE);
            maxAtm-=3;

        }while(match.numCliques()==0);
        if(mDebug) std:: cout << "FOUND AT "<<maxAtm<<std::endl;
        return (match.numCliques()>0);
    }catch(ProtExcept &e)
   {
        e.addHierarchy("DockMatch::performMatch");
        throw;
    }
}



void DockMatch::compareCliques()
{
    try{
        if (mLimitGr >= mGraphMatch.numCliques())mLimitGr=mGraphMatch.numCliques();
        if (mLimitAt >= mAtomMatch.numCliques())mLimitAt=mAtomMatch.numCliques();


        /// Compare all of the results in the Graph matching
        /// against all of the results in the atom matching
        /// to find the best overlap.
        for(size_t iGr=0;iGr<mLimitGr;++iGr)
        {
            const Clique<MMAtom>& cliqueGr=mGraphMatch.getClique(iGr);
            if(mDebug)std::cout << "GR:"<<iGr <<" " << cliqueGr.listpair.size()<<" pairs"<<std::endl;
            for(size_t iAt=0;iAt<mLimitAt;++iAt)
            {
                const Clique<MMAtom>& cliqueAt=mAtomMatch.getClique(iAt);
                CompClique *compcli=new CompClique(cliqueGr,cliqueAt,mRefMole,mCompMole);
                compcli->setRingAtomMatch(mRingAromMatch);
                compcli->setRingBondMatch(mRingBondMatch);
                if(!compcli->compare())continue;
                if(mDebug)   std:: cout << "\tAT:"<<iAt <<" " << cliqueAt.listpair.size()<<" pairs => SCORE:"<< compcli->getScore()<<std::endl;
                mListScore.insert(std::make_pair(compcli->getScore(),compcli));
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("DockMatch::compareCliques");
        throw;
    }

}





double DockMatch::bestSolutionSize()const
{
    if (mListScore.empty())return 0;
    const CompClique &groupscore=*(*(mListScore.rbegin())).second;
    return 2*(double)groupscore.size()/((double)(mRefMole.numAtoms()+mCompMole.numAtoms()));
}


void DockMatch::printResults(const size_t& count,
                             const std::string& pOUT,
                             const bool& pOnlySel,
                             const bool& p3DAlign)
try{
    std::vector<std::string> done;
    size_t n=0;

    WriterBase* wd=nullptr;
    createWriter(wd,pOUT);
    wd->onlySelected(pOnlySel);

    // cout << "Number of results:"<<mListScore.size()<<endl;
    for (auto it= mListScore.rbegin(); it != mListScore.rend();++it)
    {
        const size_t& score = (*it).first;
        CompClique &groupscore=*(*it).second;
        const double scoring=2*(double)groupscore.size()/((double)(protspace::getNumHeavyAtoms(mRefMole)+protspace::getNumHeavyAtoms(mCompMole)));
        if (mDebug)
            std::cout <<scoring<<"/" << mRatio<<" "
                      <<score<<" "
                      << groupscore.size()<<"/"<<mMinAtm<<"/"
                      <<protspace::getNumHeavyAtoms(mRefMole)<<"/"
                      <<protspace::getNumHeavyAtoms(mCompMole)<<std::endl;

        if (scoring  < mRatio)continue;
        if (groupscore.size() < mMinAtm)continue;

        groupscore.selectMole();


        // cout<<"######################################"<<endl;
        //  cout << mRefMole.toString(true)<<endl;
        MacroToIW miwr(mRefMole,true,false);
        MacroToIW miwc(mCompMole,true,false);

        const std::string RSMIfinal(getLongestSMILES(miwr.getUniqueSMILES()));
        const std::string CSMIfinal(getLongestSMILES(miwc.getUniqueSMILES()));



        if (std::find(done.begin(),done.end(),RSMIfinal)!= done.end())  continue;

        done.push_back(RSMIfinal);

        if (pOUT != "")
        {
            if (p3DAlign)
            {
                std::vector<EntryMatch> listMatch;
                groupscore.toEntryMatch(listMatch);
                std::vector<const protspace::Coords*> listR,listC;
                std::vector<protspace::Coords*> listC2;
                for(EntryMatch& e:listMatch)
                {
                    listR.push_back(&e.atomR.pos());
                    listC.push_back(&e.atomC.pos());

                }
                for(size_t iA=0;iA<mCompMole.numAtoms();++iA)
                    listC2.push_back(&mCompMole.getAtom(iA).pos());
                protspace::RigidBody rb;
                rb.loadCoordsToRigid(listR);
                rb.loadCoordsToMobile(listC);
                rb.calcRotation();
                rb.getParams().mobilToRef(listC2);

            }
            std::cout <<"SAVING"<<std::endl;
//            sdw.clearMap();
//            sdw.addData("SCORE",std::to_string(scoring));
            wd->save(mCompMole);
        }
        else
        {

            ++n;
            std::cout <<n<<"\t"<<(((double)score)/2)<<"\t"<<scoring<<"\t"<<mRefSMI
                     <<"\t"<<mCompSMI<<"\t"
                    <<mRefMole.getName()<<"\t"<<mCompMole.getName()
                   <<"\t"<<RSMIfinal<<"\t"<<CSMIfinal<<"\n";//ADDED
        }
        if (n==count)break;
    }
    delete wd;

}catch(ProtExcept &e)
{
    e.addHierarchy("DockMatch::printResults");
    throw;
}
