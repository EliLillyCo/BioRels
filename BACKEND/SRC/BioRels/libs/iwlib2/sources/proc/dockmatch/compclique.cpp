#include "headers/proc/dockmatch/compclique.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/molecule/mmatom_utils.h"
using namespace protspace;



CompClique::CompClique(const Clique<MMAtom>& cliqueR,
                       const Clique<MMAtom>& cliqueC,
                       MacroMole& mole,
                       MacroMole &moleC):
    mCliqueR(cliqueR),
    mCliqueC(cliqueC),
    mRefMole(mole),
    mCompMole(moleC),
    mRAtmFound(new bool[mole.numAtoms()]),
    mRingAromMatch(false),
    mRingBondMatch(false),
    mOveralScore(0)
{
    for(size_t i=0;i<mole.numAtoms();++i)mRAtmFound[i]=false;
}

CompClique::~CompClique()
{
    delete[] mRAtmFound;
}
bool CompClique::compare()
{
    try{
        /// Find all of the exact match between the graph matching and the atom matching
        findIdentical();//std::cout << "\tFOUND:"<<mListInters.size()<<std::endl;
        /// Find all of the similar match (N to C, N to O )....
        getSimilar();   //std::cout << "\tSIM:"<<mListInters.size()<<std::endl;

        if (!refineScore())return false; //std::cout << "\tREF:"<<mListInters.size()<<std::endl;
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::compare");
        throw;
    }
}
void CompClique::findIdentical()
{
    try{
        const std::vector<Pair<MMAtom>*> &rlist=mCliqueR.listpair;
        const std::vector<Pair<MMAtom>*> &clist=mCliqueC.listpair;
        const int nRatm=(int)mRefMole.numAtoms();

        /// We have pairs of atom mapping in the graph matching and the atom
        /// matching. Finding identical pairs is checking whether the same atoms
        /// are matched in the two matching

        for(size_t ia=0;ia<rlist.size();++ia)
        {
            const Pair<MMAtom>& rpair = *rlist[ia];
            for(size_t ja=0;ja<clist.size();++ja)
            {
                const Pair<MMAtom>& cpair =* clist[ja];

                if (rpair != cpair)continue;
                CliquePair  cpr;
                cpr.rpos=ia;
                cpr.cpos=ja;
                cpr.type=1;
                cpr.ratpos=rpair.obj1.getMID();
                cpr.catpos=rpair.obj2.getMID();
                mListInters.push_back(cpr);
                assert(rpair.obj1.getMID()<nRatm);
                mRAtmFound[rpair.obj1.getMID()]=true;
                break;
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::findIdentical");
        throw;
    }
}


bool CompClique::isFullMatch(const MMAtom& atom)const
{
        if (atom.isHydrogen())return false;

        /// Checking that all of its bonded atom are matched.
        for(size_t iBd=0;iBd<atom.numBonds();++iBd)
        {
            MMAtom& linkAtm=atom.getAtom(iBd);
            if (linkAtm.isHydrogen())continue;
            if (mRAtmFound[linkAtm.getMID()])return false;
        }
        return true;
}



void CompClique::getSimilar()
{
    try{
        const size_t nRatm=mRefMole.numAtoms();
        const   std::vector<Pair<MMAtom>*> &clist=mCliqueR.listpair;


        /// We supposed here that all identical pairs have been found
        /// We know need to find the similar ones, i.e. pairs that
        /// matches the same atoms in the graph layer (same position of the atom
        /// in the graph connectivity) but that are different at an atom layer
        /// (different element for instance)

        for(size_t km=0;km<nRatm;++km)
        {

            if (mRAtmFound[km])continue;

            /// Looking at reference atom that hasn't been matched:
            MMAtom& atom=mRefMole.getAtom(km);

            if (isFullMatch(atom))continue;

            ///Finding all mapped atoms of the bonded atoms:
            for(size_t ja=0;ja<clist.size();++ja)
            {
                const Pair<MMAtom>& crmatch = *clist.at(ja);
                if (&crmatch.obj1!=&atom)continue;
                CliquePair  cpr;
                cpr.rpos=0;
                cpr.cpos=ja;
                cpr.type=2;
                cpr.ratpos=atom.getMID();
                cpr.catpos=crmatch.obj2.getMID();
                mListInters.push_back(cpr);
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::getSimilar");
        throw;
    }

}




void CompClique::computeScore()
{
    mOveralScore=0;

    for(size_t i=0;i<mListInters.size();++i)
    {
        switch(mListInters.at(i).type)
        {
        case 1:mOveralScore+=2;break;
        case 2:mOveralScore+=1;break;
        }
    }
}

void CompClique::selectMole()
{
    try{
        mRefMole.select(false);
        mCompMole.select(false);
        for(size_t i=0;i<mListInters.size();++i)
        {
            mRefMole.getAtom(mListInters.at(i).ratpos).select(true);
            mCompMole.getAtom(mListInters.at(i).catpos).select(true);
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::selectMole");
        throw;
    }
}

void CompClique::toEntryMatch(std::vector<EntryMatch>& list)const
{
    for(size_t i=0;i<mListInters.size();++i)
    {
        EntryMatch em(mRefMole.getAtom(mListInters.at(i).ratpos),
                      mCompMole.getAtom(mListInters.at(i).catpos));
        list.push_back(em);
    }
}



bool CompClique::refineScore()
{
    try{
    /// Toggle selection only to matched atom
        selectMole();

        //bool ok=true;
        assessRing(mRefMole);
        assessRing(mCompMole);
        filterUnsimilarAtoms();
        if( mRingAromMatch || mRingBondMatch) {
            if (!checkAromRing())return false;
        }
        computeScore();
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::refineScore");
        throw;
    }
    return true;
}

void CompClique::cleanNonBiggestFrag(MacroMole& mole,
                                     std::vector<const MMAtom*> selAtm)
{
    try{
        for(size_t iAtm=0;iAtm< mole.numAtoms();++iAtm)
        {
            if (!mole.getAtom(iAtm).isSelected())continue;
            if (find(selAtm.begin(),selAtm.end(),&mole.getAtom(iAtm))!=selAtm.end())continue;
            removeFromResults(mole.getAtom(iAtm),(&mole==&mRefMole)?true:false);
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::cleanNonBiggestFrag");
        throw;
    }
}




bool CompClique::checkAromRing()
{
    std::map<MMAtom*,MMAtom*> maps;
    for(size_t iR=0;iR<mRefMole.numRings();++iR)
    {
        const MMRing& refR=mRefMole.getRing(iR);
        /// Checking that all atom in the ring are selected :
        bool isValidR=true;
        for(size_t iAtm=0;iAtm< refR.numAtoms();++iAtm)
            if (!refR.getAtom(iAtm).isSelected())isValidR=false;
        if (!isValidR)continue;



        for(size_t iC=0;iC<mCompMole.numRings();++iC)
        {
            const MMRing& compR=mCompMole.getRing(iC);
            isValidR=true;
            /// Checking that all atom in the ring are selected :
            for(size_t iAtm=0;iAtm< compR.numAtoms();++iAtm)
                if (!compR.getAtom(iAtm).isSelected())isValidR=false;
            if (!isValidR)continue;

            /// No need to compare if they don't have the same number of atoms
            if (compR.numAtoms() != refR.numAtoms())continue;
//            std::cout <<"\t"<<iC<<std::endl;
            size_t n=0;
            for(size_t i=0;i<mListInters.size();++i)
            {
                MMAtom& atomR=mRefMole.getAtom(mListInters.at(i).ratpos);
                MMAtom& atomC=mCompMole.getAtom(mListInters.at(i).catpos);
                if (!refR.isInRing(atomR) || !compR.isInRing(atomC))continue;
                maps.insert(std::make_pair(&atomR,&atomC));
                ++n;
            }
//            std::cout <<"N:"<<n<<" " <<refR.numAtoms()<<std::endl;
            if (n!= refR.numAtoms())continue;
//            std::cout <<"AROM L: "<<refR.isAromatic()<<" "<<compR.isAromatic()<<std::endl;
            if (refR.isAromatic()!=compR.isAromatic())
            {
                if (!mRingAromMatch)continue;
                for(size_t iAtm=0;iAtm< refR.numAtoms();++iAtm)
                {
                    refR.getAtom(iAtm).select(false);
                    removeFromResults(refR.getAtom(iAtm), true);
                }
                for(size_t iAtm=0;iAtm< compR.numAtoms();++iAtm)
                    compR.getAtom(iAtm).select(false);

            }
            else if (!refR.isAromatic() && mRingBondMatch)
            {

                bool ok=true;
                for(auto itR=maps.begin();itR!=maps.end();++itR)
                {
                    MMAtom& atomR1=*(*itR).first;
                    MMAtom& atomR2=*(*itR).second;;
                    auto itC2=itR;++itC2;
                    for(;itC2!=maps.end();++itC2)
                    {
                        MMAtom& atomC1=*(*itC2).first;
                        MMAtom& atomC2=*(*itC2).second;
                        if (!atomR1.hasBondWith(atomC1))continue;
                        const uint16_t & btype =atomR1.getBond(atomC1).getType();
                        if (!atomR2.hasBondWith(atomC2)){ok=false;break;}
                        if (atomR2.getBond(atomC2).getType()!=btype){ok=false;break;}

                    }
                    if (!ok)break;
                }
                if (ok)continue;

                for(size_t iAtm=0;iAtm< refR.numAtoms();++iAtm)
                {
                    refR.getAtom(iAtm).select(false);
                    removeFromResults(refR.getAtom(iAtm), true);
                }
                for(size_t iAtm=0;iAtm< compR.numAtoms();++iAtm)
                    compR.getAtom(iAtm).select(false);
            }
        }
    }
    return true;
}



void CompClique::assessRing(MacroMole& mole)
{
    try{
        /// As the matching atom/graph is performed
        /// we can be in situation were the core is not matched
        /// but the side are matched.
        /// getBiggestFragment will only keep the fragment that contains
        /// the maximum number of atoms.
        std::vector<const MMAtom*> selAtm;
        ///TODO Understand why this line below doesn't compile
        getBiggestFragment(mole,selAtm);


        std::vector<MMRing*> toRemove;
        for(size_t i=0;i<mole.numRings();++i)
        {
            MMRing& ring=mole.getRing(i);
            if (!isAllAtomInRing(ring,selAtm)
                    && !hasFusedAtom(ring,mole))
                toRemove.push_back(&ring);
        }

        std::vector<MMRing*> atomRing;
        for(size_t iAtmR=0;iAtmR<selAtm.size();++iAtmR)
        {
            const MMAtom& atomR=*selAtm.at(iAtmR);
            atomRing.clear();
            getAtomRings(atomR,atomRing);
            if (atomRing.size()==0)continue;
            size_t nFail=0;
            for(size_t i=0;i<atomRing.size();++i)
            {
                if (find(toRemove.begin(),toRemove.end(),atomRing.at(i))
                        != toRemove.end())nFail++;
            }
            if (nFail!= atomRing.size()) continue;
            removeFromResults(atomR, &mRefMole == &mole);
        }

        cleanNonBiggestFrag(mole,selAtm);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::assessRing");
        throw;
    }

}

void CompClique::removeFromResults(const MMAtom& atom, const bool& isRef)
{
    try{
        for(size_t i=0;i<mListInters.size();++i)
        {
            CliquePair& paire=mListInters.at(i);
            if ((isRef&& paire.ratpos==(size_t)atom.getMID())||
                    (!isRef&& paire.catpos==(size_t)atom.getMID()))
            {
                mListInters.erase(mListInters.begin()+i);
                return;
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::removeFromResults");
        throw;
    }
}


bool CompClique::checkFilter(const MMAtom& atomR,
                             const MMAtom& atomC)const
{
    try{
        std::vector<MMRing*> listR,listC;
        getAtomRings(atomR,listR);
        getAtomRings(atomC,listC);
        //  cout << match.atomR.getIdentifier()<<" " << match.atomC.getIdentifier()<<" " << listR.size()<<" " << listC.size()<<" " << match.atomR.getMolecule().numRings()<<" " << match.atomC.getMolecule().numRings()<<endl;
        if (listR.size()==1 && listC.size()==1)
        {
            if (listR.at(0)->isAromatic() != listC.at(0)->isAromatic())
                return false;
        }
        if (listR.size()==0 && listC.size()==0)
        {
            if (atomR.isOxygen() && atomC.isOxygen())
            {
                if (protspace::numBondType(atomR,BOND::DOUBLE)!=
                    protspace::numBondType(atomC,BOND::DOUBLE)){
                    //std::cout << "REMOVED"<<std::endl;
                    return false;
                }
            }
        }
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::checkFilter");
        throw;
    }
}


void CompClique::filterUnsimilarAtoms()
{
    try{
        std::vector<size_t> list;
        for(size_t i=0;i<mListInters.size();++i)
        {
            CliquePair& paire=mListInters.at(i);
            MMAtom& atomR = mRefMole.getAtom(paire.ratpos);
            MMAtom& atomC = mCompMole.getAtom(paire.catpos);
            if (atomR.getAtomicNum()!=atomC.getAtomicNum())continue;
            if (!checkFilter(atomR,atomC)) list.push_back(i);
        }
        for(auto i=list.rbegin();i!=list.rbegin();++i)
            mListInters.erase(mListInters.begin()+*i);
    }catch(ProtExcept &e)
    {
        e.addHierarchy("CompClique::filterUnsimilarAtoms");
        throw;
    }
}
