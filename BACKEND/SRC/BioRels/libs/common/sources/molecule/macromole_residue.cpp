#include <sstream>
#include <climits>
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release
using namespace std;
using namespace protspace;



MMResidue& MacroMole::addResidue(const std::string& resName,
                                 const std::string& chainName,
                                 const int& resID,
                                 const bool& forceCheck)throw(ProtExcept)
try{
    if (!mOwner)
        throw_line("351501",
                   "MacroMole::addResidue",
                   "This molecule is an alias. Cannot create residue on an alias");
    if (chainName=="" || chainName.length()>2)
        throw_line("351503",
                   "MacroMole::addResidue",
                   "Wrong given chain length. Given value:"+chainName);
    if (resName=="")
        throw_line("351504",
                   "MacroMole::addResidue",
                   "Residue name empty");
    signed char chain= getChainPosFromName(chainName);

    MMResidue* residAtm=nullptr;
    // Searching the chain:

    if(!mIsResidueNumberOk) renumResidue();

    // MMChain not found : Create it
    if (chain ==-1)
    {
        addChain(chainName);
        chain=mListChain.size()-1;
    }
    assert(chain !=-1);

    MMChain& currChain=*mListChain.at(chain);
    // Searching the Residue :
    if (forceCheck)
    {
        const size_t posRes(currChain.getResiduePos(resName, resID));
        if (posRes!= currChain.numResidue())return currChain.getResidue(posRes);
    }

    try{
        residAtm = new MMResidue(this,chain,resName,resID);
    }catch (std::bad_alloc &e)
    {
        ostringstream oss;
        oss  << "Bad allocation append \n"
             << e.what()<<"\n"
             << "For Residue creation "<< resName << " " << resID<<"\n";
        throw_line("351502","MacroMole::addResidue",oss.str());
    }

    currChain.addResidue(residAtm);
    residAtm->setMID(mListResidues.size());
    mListResidues.push_back(residAtm);
    return *residAtm;
}catch(ProtExcept &e)
{
    ///1Lto3Letter conversion shouldn't be happening here.
    assert(e.getId()!="325001"&& e.getId()!="325002" );

    /// Adding residue in chain must not be an issue
    assert(e.getId()!="330102"&&e.getId()!="330101");

    if (e.getId()=="351402")
        e.addHierarchy("MacroMole::AddResidue");
    e.addDescription("Residue name : "+resName);
    e.addDescription("Chain name: "+chainName);
    e.addDescription("Residue ID : "+std::to_string(resID));
throw;
}




MMResidue& MacroMole::getResidueByFID(const int& pos)throw(ProtExcept )
{
    for(size_t i=0;i<mListResidues.size();++i)
    {
        if (mListResidues.at(i)->getFID()==pos)return
                *mListResidues.at(i);
    }
    throw_line("351601",
               "MacroMole::getResidueByFID",
               "No residue found with this ID");
}






// Look up by chain name and file residue ID, e.g., the non-consecutive numbers
// This is unlikely to work with insertion codes
MMResidue& MacroMole::getResidue(const std::string& chainName, const int& resFID) throw (ProtExcept)
{
    MMChain* chain = getChainFromName(chainName);
    if (chain == nullptr) {
        throw_line("351701",
                   "MacroMole::delChain",
                   "Given chain not found in molecule");
    }
    for(size_t iR=0;iR< chain->numResidue();++iR)
    {
        MMResidue& res=chain->getResidue(iR);
        if (res.getFID()==resFID)return res;
    }
    throw_line("351702",
               "MacroMole::getResidue",
               "No residue found with FID");
}





MMResidue& MacroMole::getResidue(const int& pos)  throw(ProtExcept)
{
    if (pos==-1) return mTempResidue;
    if((size_t)pos >= mListResidues.size())
        throw_line("351801",
                   "MacroMole::getResidue",
                   "Given position is above the number of Residues "
                   +std::to_string(pos)+"/"+std::to_string(mListResidues.size()));
    return *mListResidues.at((unsigned int) pos);
}






const MMResidue& MacroMole::getResidue(const int& pos)const  throw(ProtExcept)
{

    if (pos==-1) return mTempResidue;
    if((size_t)pos >= mListResidues.size())
        throw_line("351901",
                   "MacroMole::getResidue",
                   "Given position is above the number of Residues");
    return *mListResidues.at((unsigned int) pos);
}



void MacroMole::renumResidue()
{
    if (mIsResidueNumberOk) return;
    int pos=-1;
    for(MMResidue* res:mListResidues)
    {
        pos++;
        if (res->getMID() == pos)continue;
        res->setMID(pos);
    }
    mIsResidueNumberOk=true;
}



void MacroMole::moveResidueToChain(MMResidue& res,MMChain& chain)
try{
    if (&res.getParent()!=this)
        throw_line("352001",
                   "MacroMole::moveResidueToChain",
                   "Residue not part of this molecule");
    if (&chain.getMolecule()!=this)
        throw_line("352002",
                   "MacroMole::moveResidueToChain",
                   "Chain not part of this molecule");
    assert(res.mChain <(signed char) mListChain.size());
    MMChain& former=    *mListChain.at(res.mChain);
    former.delResidue(res);
    res.mChain=(signed char)protspace::getPos(mListChain,chain);

    chain.addResidue(res);
    int maxFID=0;
    for(size_t iRes=0;iRes <chain.numResidue();++iRes)
    {
        MMResidue& pR= chain.getResidue(iRes);
        if (pR.getFID() > maxFID)maxFID=pR.getFID();
    }
    res.setFID(maxFID+1);
    res.genIdentifier();

    if (former.numResidue()==0) {
        LOG_V("DELETE CHAIN WITH NO RESIDUES "+former.getName());
        delChain(former);
    }

}catch(ProtExcept &e)
{
    ///Removing and adding residue shouldn't be an issue:
    assert(e.getId()!="330401"&&e.getId()!="330402" && e.getId()!= "330201");
    //Chain deletion shouldn't be an issue:
    assert(e.getId()!="351301"&&e.getId()!="351302"&& e.getId()!="351201");
    e.addDescription("Residue : "+res.getIdentifier());
    throw;
}


void MacroMole::deleteNotOwnedResidues(const std::vector<MMResidue*>& residueList) throw(ProtExcept)
{
if (mOwner)
    throw_line("352101",
               "MacroMole::deleteNotOwnedResidue",
               "Molecule owns residues");

        // Case where residues are not owned by this molecule
        // We just remove theses residues from this residue list
        // of this alias molecule :
        const size_t nMoleRes(mListResidues.size());
        for(MMResidue*res:residueList)
        {
            const size_t pos=protspace::getPos(mListResidues,*res);
            if (pos==nMoleRes)
                throw_line("352102",
                           "MacroMole::deleteNotOwnedResidues",
                           "residue not found in molecule");
            mListResidues.erase(mListResidues.begin()+pos);
        }

}



/// Update deleteChain
void MacroMole::deleteResidues(const std::vector<MMResidue*>& residueList) throw(ProtExcept)
{
    try{
    if (!mOwner) {deleteNotOwnedResidues(residueList);return;}

    size_t iRes=0;
    size_t iAtom=0;
    size_t iChain=0;

        // First checking that all residues are part of this molecule
        const size_t nResidues= residueList.size();
        // Molecule owns the residues :
        // Check the residues are indeed part of the molecule
        // Delete all atoms included in the residue
        // Delete residue from chain
        // Delete residue
        // Remove residue from list of residues of the molecule

        vector<MMResidue*>::iterator itRpos;
        for (;iRes < nResidues;++iRes)
        {

            MMResidue& residue= *residueList.at(iRes);

            // Check the residues are indeed part of the molecule
            itRpos=find(mListResidues.begin(),mListResidues.end(),&residue);
            // Not found : Exception
            if (itRpos== mListResidues.end())
                throw_line("352201",
                           "MacroMole::deleteResidues",
                           "Given residue is not part of this molecule "
                           +residue.getIdentifier());


            /// Remove all errors associated with this residue:
            for(size_t iErr=0;iErr <  mListErrorAtom.size();++iErr)
            {
                const ErrorAtom& err=mListErrorAtom.at(iErr);
                if (err.hasAtomDefined())
                {
                    if (&err.getAtom().getResidue()!= &residue)continue;
                    LOG_ERR("ISSUE w ERROR DELETION ON RESIDUE 4");
                }
                else if (err.getMResidue()==residue.getName()
                         &&err.getMChain()==residue.getChainName()
                         && err.getMResidueId()==residue.getFID())
                {
                    LOG_ERR("ISSUE w ERROR DELETION ON RESIDUE 3");
                }
            }

            // Delete all atoms included in the residue
            std::vector<MMAtom*> atomList = residue.mAtomlist;
            iAtom=0;
            for (;iAtom < atomList.size();++iAtom)
            {
                delAtom(*atomList.at(iAtom));
            }
            int MID = residue.getMID();
            // Delete residue from chain
            mListChain.at((unsigned int) residue.mChain)->delResidue(residue);

            /// Remove all errors associated with this residue:
            for(size_t iErr=0;iErr <  mListErrorResidue.size();++iErr)
            {
                const ErrorResidue& err=mListErrorResidue.at(iErr);
                if (err.hasResidueDefined())
                {
                    if (&err.getResidue()!= &residue)continue;
                    LOG_ERR("ISSUE w ERROR DELETION ON RESIDUE");
                }
                else if (err.getChain()==residue.getChainName()&&
                         err.getResName()==residue.getName()&&
                         err.getMFId() ==residue.getFID())
                {
                    LOG_ERR("ISSUE w ERROR DELETION ON RESIDUE 2");
                }
            }

            // Delete residue
            delete &residue;
            // Remove them from list
            mListResidues.erase(itRpos);
            const int size((int)mListResidues.size());
            for(int iRes=0;iRes < size;++iRes)
            {
                MMResidue& pRes = *mListResidues.at(iRes);
                if (pRes.getMID() < MID)continue;
                for(size_t iAtm=0;iAtm < pRes.numAtoms();++iAtm)
                {
                    pRes.getAtom(iAtm).mResidueId=iRes;
                }
                pRes.mMId=iRes;
            }
        }

        // Checking that chains still have at least one residue
        for (iChain=mListChain.size()-1;;--iChain)
        {
            // Or delete them if empty:
            if (mListChain.at(iChain)->numResidue()==0)
            {
                MMChain& pChain=*mListChain.at(iChain);
                removeChainFromList(pChain);
                delete &pChain;
            }
            if (iChain==0)break;
        }



    }catch(ProtExcept &e)
    {
        /// if we know on this function that the molecule does not own residues
        /// then in the next function it should remain the same
        assert(e.getId()!="352101");
        /// If a residue is confirmed to be in a molecule than the aton must
        /// be in the molecule as well
        assert(e.getId()!="350801");
        /// Deleting the residue from the chain should work properly

        assert(e.getId()!="330201"&& e.getId()!="330202"&&e.getId()!="330203");
        /// Deleting chains should not be an issue
        assert(e.getId()!="351301"&& e.getId()!="351302"&&e.getId()!="351201");
        e.addHierarchy("MacroMole::deleteResidues");
        throw;



    }
}


void MacroMole::updateResidueMID()
{
    const int size((int)mListResidues.size());
    for(int iRes=0;iRes < size;++iRes)
    {
        MMResidue& pRes = *mListResidues.at(iRes);
        if (pRes.getMID()==iRes)continue;
        for(size_t iAtm=0;iAtm < pRes.numAtoms();++iAtm)
        {
            pRes.getAtom(iAtm).mResidueId=iRes;
        }
        pRes.mMId=iRes;
    }
}
void MacroMole::addNewError(const ErrorResidue& err)
{
    mListErrorResidue.push_back(err);
}
