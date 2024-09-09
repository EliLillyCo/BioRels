#include "headers/statics/intertypes.h"
#include "headers/proc/matchaa.h"
#include "headers/molecule/mmresidue_utils.h"
protspace::MatchAA::    MatchAA(MMResidue& pMoleRes,
                                UIntMatrix &pResMatrix,
                                const HETEntry& pHETentry):
    MatchLigand(pMoleRes,pResMatrix,pHETentry)
{

}



bool protspace::MatchAA::   process()
{
    try{
        if (mHETEntry.isReplaced() )mMoleRes.setName(mHETEntry.getReplaced());
        if (tryByName()) return true;
        mNoTerminal=false;

        generatePairs();
        if (mGraphmatch.numPairs()==0)return false;
        generateLinks();
        if (mGraphmatch.numEdges()==0)return false;
        if (!calcCliques())
        {
            correctSTDAA();
        }else
        {
            processClique();
            check();
        }
        return true;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MatchAA::process");
        throw;
    }

}



bool protspace::MatchAA::shareInterBond(const MMAtom& atm)const
{
    for(size_t iBd=0;iBd <atm.numBonds();++iBd)
    {
        if (&atm.getAtom(iBd).getResidue()!=&atm.getResidue())return true;
    }return false;
}



void protspace::MatchAA::check()
{
    std::vector<MMAtom*> atmToDel;
    for (size_t iAtom=0;iAtom < nMResAtom;++iAtom)
    {
        MMAtom& resAtom = mMoleRes.getAtom(iAtom);
        if (mMoleResConsidered[iAtom])continue;
        ///TODO Avoid deletion of atom if the residue is indeed a GLY (need seq align)
        if (mResName=="GLY" && resAtom.getName()=="CB")
        {
            LOG_ERR("Removing "+resAtom.getIdentifier()+" - GLY has no CB");
            atmToDel.push_back(&resAtom);
            for(size_t iA=0;iA< resAtom.numBonds();++iA)
            {
                if (resAtom.getAtom(iA).isHydrogen())
                    atmToDel.push_back(&resAtom.getAtom(iA));
            }
            continue;
        }
        ErrorAtom err(mMoleRes,resAtom.getName(),ERROR_ATOM::ATOM_NOT_FOUND,"Atom not found in the molecule definition");
        mMoleRes.getParent().addNewError(err);
    }
    const MacroMole& HETMolecule = mHETEntry.getMole();
    for (size_t iAtom=0;iAtom < mNHETAtom;++iAtom)
    {
        if (mHETConsidered[iAtom])continue;
        const MMAtom& resAtom = HETMolecule.getAtom(iAtom);
        if (resAtom.isHydrogen())continue;
        if (resAtom.getName()=="OXT")continue;
        ErrorAtom err(mMoleRes,resAtom.getName(),ERROR_ATOM::MISSING,"Missing atom "+resAtom.getName()+" IN "+HETMolecule.getName());
        mMoleRes.getParent().addNewError(err);
    }
    if (atmToDel.empty())return;
    for(auto atm:atmToDel) {
        ErrorAtom err(mMoleRes,atm->getName(),ERROR_ATOM::NOT_EXISTING,"Cbeta does not exist in Glycine");
        mMoleRes.getParent().addNewError(err);
        mMoleRes.getParent().delAtom(*atm);
    }

}



void protspace::MatchAA::generatePairs()

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

        for (size_t iHAtom =0;iHAtom < mNHETAtom;++iHAtom)
        {
            const MMAtom& temAtom = HETMolecule.getAtom(iHAtom);
            if (temAtom.getName()=="C" && !shareInterBond(resAtom))continue;
            if ((resAtom.getAtomicNum()!= temAtom.getAtomicNum())
                    && !(!resAtom.isBioRelevant() && temAtom.getMOL2()=="Du"))continue;
            found=true;
            //std::cout <<resAtom.getIdentifier()<<" " <<temAtom.getIdentifier()<<std::endl;
            mGraphmatch.addPair(resAtom,temAtom);
        }
        if (!found)
            throw_line("630301",
                       "MatchLigand::generatePairs",
                       "NO ATOM FOUND FOR "+resAtom.getIdentifier());
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="320501" && e.getId()!="320502");/// Atom must exist
    assert(e.getId()!="310801" && e.getId()!="310802");/// MOL2 H must exist
    e.addHierarchy("MatchAA::generatePairs");
    throw;
}



bool protspace::MatchAA::matchAtomByName(std::map<MMAtom*,const MMAtom*>& mapAtm)const
try {

    const MacroMole &HETMolecule = mHETEntry.getMole();
    const MMResidue& HETRes=HETMolecule.getResidue(0);
    size_t pos;
    for (size_t iAtom = 0; iAtom < nMResAtom; ++iAtom) {
        MMAtom &resAtom = mMoleRes.getAtom(iAtom);


        if (resAtom.isHydrogen()) {
            mMoleResConsidered[iAtom] = true;
            resAtom.setMOL2Type("H");
            continue;
        }
        if (!HETRes.hasAtom(resAtom.getName(),pos))continue;
        const MMAtom& temAtom=HETRes.getAtom(pos);
        const size_t& iHAtom=temAtom.getMID();
        if (mHETConsidered[iHAtom])        return false;
        mMoleResConsidered[iAtom] = true;
        mHETConsidered[iHAtom] = true;
        mapAtm.insert(std::make_pair(&resAtom, &temAtom));


    }
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="351901");/// Residue of HETMole must exists
    assert(e.getId()!="320501" && e.getId()!="320502");///Atom must exist
    assert(e.getId()!="310801" && e.getId()!="310802");///MOL2 H must exist
    e.addDescription(mMoleRes.getIdentifier());
    e.addHierarchy("MatchAA::matchAtomByName");
    throw;
}


bool protspace::MatchAA::matchBond(const std::map<MMAtom*,const MMAtom*>& mapAtm,
                                   std::map<MMBond*,MMBond*>& mapBd)const
{
    const MacroMole& HETMolecule = mHETEntry.getMole();
    std::vector<bool> BDChecked(HETMolecule.numBonds(),false);

    for (size_t iAtom=0;iAtom < nMResAtom;++iAtom)
    {
        MMAtom& resAtom = mMoleRes.getAtom(iAtom);
        try{
            if (resAtom.isHydrogen())continue;
            const auto it2=  mapAtm.find(&resAtom);
            if (it2 == mapAtm.end()) return false;
            const MMAtom& HETAtom = *(*it2).second;
            for(size_t iBd=0;iBd < resAtom.numBonds();++iBd)
            {
                MMAtom& resAtom2 = resAtom.getAtom(iBd);
                if (resAtom2.isHydrogen())continue;

                if (&resAtom2.getResidue()!=&resAtom.getResidue())continue;
                const auto it=  mapAtm.find(&resAtom2);
                if (it == mapAtm.end()) return false;
                const MMAtom& HETAtom2 = *(*it).second;
                try {
                    if (HETAtom.hasBondWith(HETAtom2))
                    {
                    MMBond &bd = HETAtom.getBond(HETAtom2);

                    BDChecked[bd.getMID()] = true;
                    //            std::cout <<bd.toString()<<" " <<resAtom.getBond(iBd).toString()<<std::endl;
                    mapBd.insert(std::make_pair(&resAtom.getBond(iBd), &bd));
                    }
                    else
                    {
                        LOG_ERR("Bond shouldn't exist between "+resAtom.getIdentifier()+" AND "+resAtom2.getIdentifier()+" DISTANCE:"+std::to_string(resAtom.dist(resAtom2)));
                        resAtom.getParent().delBond(resAtom.getBond(resAtom2));
                        iBd=0;
                    }
                }catch(ProtExcept &e)
                {

                    assert(e.getId()!="310301" && e.getId()!="071001");///Bond must exist
                    e.addDescription(HETAtom.getIdentifier()+"\t"+HETAtom2.getIdentifier());
                    continue;
                }
            }
        }catch(ProtExcept &e)
        {
            assert(e.getId()!="310501");///Atom must exist

            e.addDescription(resAtom.toString());
            e.addHierarchy("MatchAA::matchBond");
            throw;
        }
    }
    for(size_t iBd=0;iBd < HETMolecule.numBonds();++iBd)
    {
        if (BDChecked[iBd])continue;
        const MMBond& bd = HETMolecule.getBond(iBd);
        if (bd.getAtom1().isHydrogen())continue;
        if (bd.getAtom2().isHydrogen())continue;
        if (bd.getAtom1().getName()=="OXT")continue;
        if (bd.getAtom2().getName()=="OXT")continue;
        //        std::cout <<bd.toString()<<std::endl;
        return false;
    }
    return true;
}




bool protspace::MatchAA::tryByName()
{
    try{
        //    std::cout <<nMoleResHeavyAtom<<" " <<mNHETHeavyAtom<<std::endl;
        /// -1 is for OXT that is removed;
        if (nMoleResHeavyAtom < mNHETHeavyAtom-1)return false;
        std::map<MMAtom*,const MMAtom*> mapAtm;
        std::map<MMBond*,MMBond*> mapBd;
        assignElement();
        //   std::cout << mMoleRes.toString()<<std::endl;
        if (!matchAtomByName(mapAtm)) return false;
        if (!allNameMatched()){return false;}
        if (!matchBond(mapAtm,mapBd)){return false;}

        for(auto it = mapAtm.begin();it != mapAtm.end();++it)
        {
            MMAtom& resAtm = *(*it).first;
            const MMAtom& hetAtm = *(*it).second;
            pushAtomData(resAtm,hetAtm);
        }
        for(auto it = mapBd.begin();it != mapBd.end();++it)
        {
            MMBond& resBd= *(*it).first;
            const MMBond& hetBd = *(*it).second;
            resBd.setBondType(hetBd.getType());
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MatchAA::tryByName");
        throw;
    }
    //std::cout <<"NAME MATCHED"<<std::endl;
    return true;
}




bool protspace::MatchAA::allNameMatched()
{
    const MacroMole& HETMolecule = mHETEntry.getMole();
    for (size_t iHAtom =0;iHAtom < mNHETAtom;++iHAtom)
    {
        const MMAtom& temAtom = HETMolecule.getAtom(iHAtom);
        if (temAtom.isHydrogen())continue;
        if (temAtom.getName()=="OXT")continue;
        if (!mHETConsidered[iHAtom]) return false;
    }
    return true;
}



void protspace::MatchAA::correctSTDAA() const
try{
    MacroMole& mole=mMoleRes.getParent();
    const MacroMole& HETMole=mHETEntry.getMole();

    /// Deleting all bonds
    protspace::delIntraBond(mMoleRes);
    size_t posAt=0;
    const size_t nHETAt(HETMole.numAtoms());

    //// Assign atom types:
    for(size_t iAtom=0;iAtom < nHETAt;++iAtom)
    {
        const MMAtom& atom=HETMole.getAtom(iAtom);
        if (!mMoleRes.hasAtom(atom.getName(),posAt))continue;
        MMAtom& atomM=mMoleRes.getAtom(posAt);
        atomM.setMOL2Type(atom.getMOL2());
        atomM.setFormalCharge(atom.getFormalCharge());
        //            moleResConsidered[mResMatPos.at(&atomM)]=true;
        //            HETtempConsidered[atom.getMID()]=true;
    }

    //// Recreate bonds :
    for(size_t iAtom=0;iAtom < nHETAt;++iAtom)
    {
        const MMAtom& atom=HETMole.getAtom(iAtom);
        if (!mMoleRes.hasAtom(atom.getName(),posAt))continue;
        MMAtom& atomM=mMoleRes.getAtom(posAt);

        for(size_t iAtomC=iAtom+1;iAtomC < nHETAt;++iAtomC)
        {
            const MMAtom& atom2=HETMole.getAtom(iAtomC);
            if (!atom2.hasBondWith(atom))continue;
            if (!mMoleRes.hasAtom(atom2.getName(),posAt))continue;
            MMAtom& atomM2=mMoleRes.getAtom(posAt);

            mole.addBond(atomM,
                         atomM2,
                         atom.getBond(atom2).getType(),
                         mole.numBonds());
        }
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");///atom must exists
    assert(e.getId()!="320401");///atom in residue must exist
    assert(e.getId()!="350601");///Molecule cannot be an alias
     assert(e.getId()!="350602" && e.getId()!="350603");/// Atom must be part of molecule
     assert(e.getId()!="350604");///atom cannot be the same
    e.addHierarchy("MatchAA::correctSTDAA");
    throw;
}
