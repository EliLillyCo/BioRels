#include <headers/statics/intertypes.h>
#include <headers/parser/writerMOL2.h>
#include <set>
#include "headers/proc/matchligand.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/mmatom_utils.h"
protspace::MatchLigand::    MatchLigand(MMResidue& pMoleRes,
                                        UIntMatrix &pResMatrix,
                                        const HETEntry& pHETentry):
    MatchResidue(pMoleRes,pResMatrix),
    mHETEntry(pHETentry),
    mNHETHeavyAtom(protspace::numHeavyAtom(pHETentry.getMole().getResidue(0))),
    mNHETAtom(pHETentry.getMole().numAtoms()),
    mNoTerminal(true),
    mwCheck(true),
    mAllowedSmaller(true),
    mMinSize(0)
{try{

        if (!assignElement())updateMatrix();
        mHETConsidered= new bool[mNHETAtom];
        for(size_t i=0;i<mNHETAtom;++i)mHETConsidered[i]=false;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MatchLigand::MatchLigand");
        e.addDescription(pMoleRes.getIdentifier());
        throw;
    }catch(std::bad_alloc &e)
    {
        throw_line("660101","MatchLigand::MatchLigand","Bad allocation\n"+std::string(e.what()));
    }
}

size_t protspace::MatchLigand::getNumSymAtom()
{
    size_t nSymAtom=0;
    std::vector<bool> isTerminal(mNHETAtom,false);
    for(size_t iAtm=0;iAtm<mNHETAtom;++iAtm)
    {
        const MMAtom& atm =mHETEntry.getMole().getAtom(iAtm);
        if (atm.isHydrogen())continue;
        const size_t nBd(atm.numBonds());
        if (nBd==1) {
            isTerminal[iAtm]=true;continue;}
        if (nBd-numHydrogenAtomBonded(atm)!=1)continue;
        isTerminal[iAtm]=true;

    }
    for(size_t iAtm=0;iAtm<mNHETAtom;++iAtm) {
        const MMAtom &atm = mHETEntry.getMole().getAtom(iAtm);
        const size_t nBd(atm.numBonds());
        if (isTerminal[iAtm])continue;
        std::vector<unsigned char> listElem;
        for(size_t iBd=0;iBd < nBd;++iBd)
        {
            if (!isTerminal[atm.getAtom(iBd).getMID()])continue;
            listElem.push_back(atm.getAtom(iBd).getAtomicNum());
        }
        for(auto entry:listElem)
        {
            if (std::count(listElem.begin(), listElem.end(),entry) >1) {
                ++nSymAtom;
            }
            //   std::cout << (unsigned)entry <<" " <<
            // std::count(listElem.begin(), listElem.end(),entry)<< " " << nSymAtom<<std::endl;
        }
    }
    return nSymAtom;
}




bool protspace::MatchLigand::   process()
{
    const size_t nSymAtom(getNumSymAtom());
//    std::cout <<"\tNUM SYM :"<<nSymAtom<<std::ends;

    try{
        if (mHETEntry.isReplaced() )mMoleRes.setName(mHETEntry.getReplaced());

        if (mNHETHeavyAtom < nMoleResHeavyAtom)return false;

        switch (mNHETHeavyAtom)
        {
        case 1:

            for(size_t i=0;i<mNHETAtom;++i)
                if (!mHETEntry.getMole().getAtom(i).isHydrogen())
                    processSingleAtom(mHETEntry.getMole().getAtom(i));
//            std::cout <<"\tSINGLE ATOM"<<std::endl;
            break;
        case 2:
            processDoubleAtom(mHETEntry.getMole());
//            std::cout <<"\tDOUBLE ATOM"<<std::endl;
            break;
        default:

            //std::cout <<mMoleRes.getIdentifier()<<"GEN PAIRS"<< std::endl;
            generatePairs();
//                      std::cout <<mGraphmatch.numPairs()<<" pair found\n"
//                    <<mMoleRes.getIdentifier()<<" GEN LINKS"<<std::endl;
            generateLinks();
            if (mGraphmatch.numEdges()==0)
            {
                LOG_ERR(mMoleRes.getIdentifier()+" NO EDGE FOUND");
                return false;
            }
            mGraphmatch.isVirtual(false);
//                  std::cout <<mGraphmatch.numEdges()<<" LINK FOUND \n"
//                <<mMoleRes.getIdentifier()<<"CLAS CLIQUE"<<std::endl;
            const size_t nCli=calcCliques();
            /// By default we request to ignore terminal atoms
            /// However, if this lead to multiple possible cliques
            /// Then the possibility of matching wrong atoms together
            /// cannot be overuled. Therefore we re-run the matching
            /// by considering all atoms.
//            std::cout <<"\t"<<nCli<<" clique found"<<std::ends;
            if (nCli!=1 && nSymAtom <17 && mAllowedSmaller) {
//                std::cout <<"\tCLIQUE NOT TERMINAL "<<std::ends;
                mGraphmatch.clear();
                mNoTerminal=false;
                generatePairs();
                generateLinks();
                if (calcCliques()==0) {
                    bool found = false;
                    //std::cout <<"\tCLIQUE ISSUE ";
                    for (size_t k = mNHETHeavyAtom; k >= 3; --k) {
                       // std::cout << k << " ";
                        if (calcCliques(k) == 0)continue;
                        found = true;
                        break;
                    }
                   // std::cout << std::ends;
                    if (!found) {
                        LOG_ERR(mMoleRes.getIdentifier()+" NO CLIQUE FOUND\n");
                        return false;
                    }
                }


            }
            else if (nCli==0 && nSymAtom > 17)
            {
                bool found = false;
               // std::cout <<"\tCLIQUE ISSUE ";
                for (size_t k = mNHETHeavyAtom; k >= 3; --k) {
                   // std::cout << k << " ";
                    if (calcCliques(k) == 0)continue;
                    found = true;
                    break;
                }
                //std::cout << std::ends;
                if (!found) {
                    LOG_ERR(mMoleRes.getIdentifier()+" NO CLIQUE FOUND\n");
                    return false;
                }
            }
            if (mGraphmatch.numCliques()==0)
            {
                LOG_ERR(mMoleRes.getIdentifier()+" NO CLIQUE FOUND\n");
                return false;
            }
            processClique();

            if (mwCheck) check();
        }
        return true;

    }catch(ProtExcept &e)
    {
        e.addHierarchy("MatchLigand::process");
        throw;
    }

}


void protspace::MatchLigand::check()const
try{
    const MacroMole& HETMolecule = mHETEntry.getMole();
    std::ostringstream ossDef,ossMiss;bool isDef=false,isMiss=false;
    ossDef<<"Atom not found in the molecule definition ";
    ossMiss<<"Incomplete residue: Missing atom "+HETMolecule.getName();
    const size_t lenDef(ossDef.str().length()),lenMiss(ossMiss.str().length());
    for (size_t iAtom=0;iAtom < nMResAtom;++iAtom)
    {
        if (mMoleResConsidered[iAtom])continue;
        MMAtom& resAtom = mMoleRes.getAtom(iAtom);
        ossDef <<" "<<resAtom.getName();isDef=true;
    }
    if (isDef) {
        ErrorResidue err(ossDef.str(),mMoleRes);
        mMoleRes.getParent().addNewError(err);
    }
    for (size_t iAtom=0;iAtom < mNHETAtom;++iAtom)
    {
        if (mHETConsidered[iAtom])continue;
        const MMAtom& resAtom = HETMolecule.getAtom(iAtom);
        if (resAtom.isHydrogen())continue;
        isMiss=true;
        ossMiss<<" "<<resAtom.getName();
    }
    if (isMiss)
    {
        ErrorResidue err(ossMiss.str(),mMoleRes);
        mMoleRes.getParent().addNewError(err);
    }
    if (ossDef.str().length()>lenDef)LOG_ERR(ossDef.str());
    if (ossMiss.str().length()>lenMiss)LOG_ERR(ossMiss.str());

}catch(ProtExcept &e)
{
    e.addHierarchy("MatchLigand::check");
    throw;
}


void protspace::MatchLigand::generatePairs()
try{
    const MacroMole& HETMolecule = mHETEntry.getMole();
    bool found=false;
    for (size_t iAtom=0;iAtom < nMResAtom;++iAtom)
    {
        MMAtom& resAtom = mMoleRes.getAtom(iAtom);


        if (resAtom.isHydrogen()) {
            mMoleResConsidered[iAtom]=true;
            resAtom.setMOL2Type("H");
            continue;
        }
        if (mNoTerminal && numHeavyAtomBonded(resAtom) ==1)continue;
        mMinSize++;
        for (size_t iHAtom =0;iHAtom < mNHETAtom;++iHAtom)
        {
            const MMAtom& temAtom = HETMolecule.getAtom(iHAtom);
            if ((resAtom.getAtomicNum()!= temAtom.getAtomicNum())
                    && !(!resAtom.isBioRelevant() && temAtom.getMOL2()=="Du"))continue;
            found=true;
            mGraphmatch.addPair(resAtom,temAtom);
        }
        if (!found)
            throw_line("660201",
                       "MatchLigand::generatePairs",
                       "NO ATOM FOUND FOR "+resAtom.getIdentifier());
    }
    if (mMinSize<3 && mNoTerminal) {mNoTerminal=false;mMinSize=0;mGraphmatch.clear();generatePairs();}
}catch(ProtExcept &e)
{
    assert(e.getId()!="310801" && e.getId()!="310802");/// MOL2 H must exists
    e.addHierarchy("MatchLigand::generatePairs");
    throw;
}






void protspace::MatchLigand::generateLinks()
try{
    // Number of potential atom pairs :
    const size_t nPairs = mGraphmatch.numPairs();

    // Comparing pair of pair to check if bond distance is the same
    for(size_t iRPair=0; iRPair < nPairs; ++iRPair)
    {
        AtmPair& rpair =mGraphmatch.getPair(iRPair);

        for(size_t iCPair=iRPair+1; iCPair < nPairs;++iCPair)
        {
            AtmPair& cpair = mGraphmatch.getPair(iCPair);

            if (&rpair.obj1==&cpair.obj1 || &rpair.obj2==&cpair.obj2)continue;
            const unsigned int& obj1D=mResMatrix.getVal(mResMatPos.at(&rpair.obj1),
                                                        mResMatPos.at(&cpair.obj1));
            const unsigned int& obj2D=mHETEntry.getMatrix().getVal(rpair.obj2.getMID(),cpair.obj2.getMID());

            if(obj1D != obj2D )continue;
            mGraphmatch.addLink(rpair,cpair);
        }
    }
}catch(ProtExcept&e)
{
    assert(e.getId()!="110201");/// Pair must exist
    assert(e.getId()!="200202" && e.getId()!="200201");/// Matrix positino must exists
    assert(e.getId()!="030301" && e.getId()!="030302");/// Matrix positino must exists

    e.addHierarchy("MatchLigand::generateLinks");
    throw;
}


size_t protspace::MatchLigand::calcCliques(const size_t& size)
{
    return MatchResidue::calcCliques((size==0)?mMinSize:size);
}


void protspace::MatchLigand::processClique(const size_t& nCli)
{
    try {
//                std::cout <<"NUM CLIQUE : "<<mGraphmatch.numCliques()<<std::endl;
        std::map<const MMAtom *, const MMAtom *> mResToEntryAtomMap;
        std::map<const MMAtom*, const MMAtom*>::iterator itRAM1, itRAM2;
        const AtmClique clique = mGraphmatch.getClique(nCli);

        processAtomClique(clique,mResToEntryAtomMap);

        for (size_t i = 0; i < nMResAtom; ++i) {
            MMAtom &atom = mMoleRes.getAtom(i);
            if (!mMoleResConsidered[mResMatPos.at(&atom)]) {

                //                std::cerr << "Unmatched atom " + atom.getIdentifier() << std::endl;
                //throw_line(99919,"mmcifResidue::matchMolecule","Unmatched atom "+moleResidue.getAtom(i).getIdentifier());
                continue;
            }
            itRAM1 = mResToEntryAtomMap.find(&atom);
            if (itRAM1 == mResToEntryAtomMap.end())continue;

            const MMAtom &mappedAtm = *(*itRAM1).second;
//            std::cout << atom.getIdentifier()<<"\t"<<mappedAtm.getIdentifier()<<"\n";
            for (size_t iBd = 0; iBd < atom.numBonds(); ++iBd) {

                MMAtom &moleAtom = atom.getAtom(iBd);
                if (&moleAtom.getResidue() != &mMoleRes)continue;
                if (moleAtom.getMID() < atom.getMID())continue;
                if (!mMoleResConsidered[mResMatPos.at(&moleAtom)])continue;
                MMBond &moleBond = atom.getBond(iBd);
                itRAM2 = mResToEntryAtomMap.find(&moleAtom);
                if (itRAM2 == mResToEntryAtomMap.end())continue;
                const MMAtom &mappedAtm2 = *(*itRAM2).second;
                const MMBond &tmpBond = mappedAtm.getBond(mappedAtm2);
                moleBond.setBondType(tmpBond.getType());
            }

        }
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="110501");///clique must exists
        assert(e.getId()!="310501"&&e.getId()!="310201"
                && e.getId()!="071001");///Bond must exist
        e.addHierarchy("MatchLigand::processClique");

        throw;
    }
}

void protspace::MatchLigand::assessTerminalAtom(MMAtom& resAtom, const MMAtom& HETatom,
                                                std::map<const MMAtom *, const MMAtom *> &mResToEntryAtomMap)
try{
    //    std::cout <<"ASSESSING "<<resAtom.getIdentifier()<<"\t"<<HETatom.getIdentifier()<<" " <<resAtom.numBonds()<<":"<<HETatom.numBonds()<<std::endl;
    const size_t nHETBd(HETatom.numBonds());
    std::vector<bool> set(nHETBd,false);
    for(size_t iResAtm=0;iResAtm < resAtom.numBonds();++iResAtm)
    {
        MMAtom& resAtom2=resAtom.getAtom(iResAtm);
        if (resAtom2.isHydrogen())continue;
        if (&resAtom2.getResidue()!= &resAtom.getResidue())continue;
        if (numHeavyAtomBonded(resAtom2)!= 1)continue;
        //        std::cout <<"TEST:"<<resAtom2.getIdentifier()<<std::endl;

        for(size_t iHETAtm=0;iHETAtm < nHETBd;++iHETAtm)
        {
            if (set[iHETAtm])continue;
            MMAtom& HETatom2= HETatom.getAtom(iHETAtm);
            //            std::cout <<"AGAINST :"<<HETatom2.getIdentifier()
            //            <<" " <<mHETConsidered[HETatom2.getMID()]<<" "
            //            <<(unsigned)HETatom2.getAtomicNum()<<" " <<(unsigned)resAtom2.getAtomicNum()<<std::endl;
            if (HETatom2.isHydrogen())continue;

            if (mHETConsidered[HETatom2.getMID()])continue;
            if (HETatom2.getAtomicNum()!= resAtom2.getAtomicNum())continue;

            //           std::cout <<"\tMATCH "<<resAtom2.getIdentifier()<<"\t"<<HETatom2.getIdentifier()<<std::endl;
            set[iHETAtm]=true;
            pushAtomData(resAtom2,HETatom2);
            mMoleResConsidered[mResMatPos.at(&resAtom2)] = true;
            mHETConsidered[HETatom2.getMID()] = true;

            mResToEntryAtomMap.insert(std::make_pair
                                      (&resAtom2, &HETatom2));
            break;

        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="310501");///Atom must exists
    e.addHierarchy("MatchLigand::assessTerminalAtom");
    throw;
}



void protspace::MatchLigand::processAtomClique(const AtmClique& clique,
                                               std::map<const MMAtom *, const MMAtom *> &mResToEntryAtomMap)
try{
    for (size_t i = 0; i < clique.listpair.size(); ++i) {
        const AtmPair &pairing = *clique.listpair.at(i);
        const MMAtom &resAtom = pairing.obj1;
        const MMAtom &temAtom = (pairing.obj2);


        pushAtomData(mMoleRes.getParent().getAtom(resAtom.getMID()),temAtom);
        const auto it = mResMatPos.find(&resAtom);
        if (it == mResMatPos.end())
        {
            std::cerr<<"MOLERESATOM NOT FOUND :"<<resAtom.getIdentifier()<<" \t"<<temAtom.getIdentifier()<<std::endl;
        }else
            mMoleResConsidered[(*it).second] = true;
        mHETConsidered[temAtom.getMID()] = true;

        mResToEntryAtomMap.insert(std::make_pair
                                  (&resAtom, &temAtom));

    }

    if (!mNoTerminal)return;

    for (size_t i = 0; i < clique.listpair.size(); ++i) {
        const AtmPair &pairing = *clique.listpair.at(i);
        MMAtom &resAtom = mMoleRes.getParent().getAtom(pairing.obj1.getMID());
        const MMAtom &temAtom = (pairing.obj2);
        assessTerminalAtom(resAtom, temAtom,mResToEntryAtomMap);

    }
}catch(ProtExcept&e)
{
    assert(e.getId()!="030401");
    e.addHierarchy("MatchLigand::processAtomClique");
    throw;
}
