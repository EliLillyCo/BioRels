#undef NDEBUG
#include <assert.h>
#include "headers/proc/matchtemplate.h"
#include "headers/statics/intertypes.h"
#include "headers/proc/matchaa.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/proc/matchresidue.h"
#include "headers/statics/atomdata.h"
#include "headers/proc/matchligand.h"
#include "headers/statics/strutils.h"
#include "headers/statics/logger.h"
protspace::MatchTemplate::    MatchTemplate():
    mHETManager(protspace::HETManager::Instance()),
    mResMatrix(310,310,0),
    mOnlyUsed(false),
    mVerbose(true),
    mIsInternal(false),
    mKeepAtoms(false)
{

}


bool protspace::MatchTemplate::processMolecule(MacroMole& pMolecule) throw(ProtExcept)
try{
    if (!pMolecule.isOwner())
        throw_line("650401","MatchTemplate::processMolecule",
                   "Molecule must be owner");
    const int nMoleRes = (int)pMolecule.numResidue();
    bool allgood=true;
    for(int iRes=0;iRes < nMoleRes;++iRes)
    {
        MMResidue& pMoleRes = pMolecule.getResidue(iRes);

        try{


            processResidue(pMoleRes);

        }catch(ProtExcept &e)

        {
            e.addHierarchy("MatchTemplate::processMolecule");
            e.addDescription("Residue involved : "+pMolecule.getName()+">>"+pMoleRes.getIdentifier());
            e.addDescription("Residue : \n"+pMoleRes.toString(true));
            ///TODO : Think about showing issue vs log vs error in molecule
            LOG_ERR("RESIDUE FAILED "+pMoleRes.getIdentifier());
            std::cerr <<e.toString();
            allgood=false;
        }
    }

    scanForAmide(pMolecule);

    checkBonds(pMolecule);

    return allgood;
}catch(ProtExcept &e)
{
    e.addHierarchy("MatchTemplate::processMolecule");
    throw;
}




void protspace::MatchTemplate::scanForAmide(MacroMole& pMolecule)throw(ProtExcept)
{
    const int nMoleRes = (int)pMolecule.numResidue();
    for(int iRes=0;iRes < nMoleRes;++iRes)
    {
        MMResidue& pMoleRes = pMolecule.getResidue(iRes);
        try{
            if (isAA(pMoleRes))    correctAmideAA(pMoleRes);

        }catch(ProtExcept &e)
        {
            e.addHierarchy("MatchTemplate::scanForAmide");
            ///TODO : Think about showing issue vs log vs error in molecule
        }
    }
}

void protspace::MatchTemplate::processResidue(MMResidue& pMoleRes) throw(ProtExcept){

    /// If updated, please also update internalNames in HETManager.cpp

    protspace::MatchResidue* matcher=nullptr;
    const std::string&   pMoleName    = pMoleRes.getName();
    try{
        if (mOnlyUsed && !pMoleRes.isSelected())return;
        if ((isInList(mHETManager.mHETInternals,pMoleName) && mIsInternal)
                         ||pMoleName.length() > 3)
                {

                    LOG(pMoleRes.getParent().getName()+" - "+pMoleRes.getIdentifier()+" - INTERNAL");
                    processInternalLigand(pMoleRes);
                    return;
                }
        const HETEntry&       HETtemplate = mHETManager.getEntry(pMoleRes.getName());

        if (pMoleName=="I" && processIodine(pMoleRes))return;
        if (pMoleName=="HOH") {   processWater(pMoleRes); return;}

        if (isAA(pMoleRes))
        {

            matcher=new MatchAA(pMoleRes,mResMatrix,HETtemplate);
        }
        else
        {

            LOG(pMoleRes.getParent().getName()+" - "+pMoleRes.getIdentifier()+" - LIGAND");
            matcher = new  MatchLigand(pMoleRes,mResMatrix,HETtemplate);
        }

        if (!matcher->process())
        {
            LOG_ERR(pMoleRes.getParent().getName()+" - "+pMoleRes.getIdentifier()+" - Unable to assign template");
            ///TODO Should we add an error to molecule
        }

    }catch(ProtExcept &e)
    {

        e.addHierarchy("MatchTemplate::processResidue");
        e.addDescription("Residue involved : "+pMoleRes.getParent().getName()+">>"+pMoleRes.getIdentifier());



        std::cerr <<e.toString();
        if (matcher !=nullptr) {delete matcher;
            matcher=nullptr;}
    }
    if (matcher !=nullptr) delete matcher;
}





void protspace::MatchTemplate::correctAmideAA(MMResidue& pRes) const
try{

    size_t posC, posO;
    if (!pRes.hasAtom("C",posC))return;
    if (!pRes.hasAtom("O",posO))return;

    MMAtom& atomC=pRes.getAtom(posC);atomC.setMOL2Type("C.2");
    MMAtom& atomO=pRes.getAtom(posO);atomO.setMOL2Type("O.2");

    if (!atomC.hasBondWith(atomO))
    {
        LOG_ERR("Expected bond between "+atomO.getIdentifier()
                +" AND "+atomC.getIdentifier());
        atomO.getParent().addBond(atomC,atomO,BOND::DOUBLE);
    }else

        atomC.getBond(atomO).setBondType(BOND::DOUBLE);
    for(size_t ibd=0;ibd < atomC.numBonds();++ibd)
    {
        MMAtom& atomN=atomC.getAtom(ibd);
        if (atomN.getName()!="N")continue;
        if (!isAA(atomN.getResidue()))continue;
        atomN.setMOL2Type("N.am");
        atomC.getBond(atomN).setBondType(BOND::AMIDE);

    }

}catch(ProtExcept &e)
{
    ///TODO See if it need to be a distinct error or if it should be forgot
    ///TODO add info when it's atomC.getBond(atomO) that fails => WRONG BONDING
    e.addHierarchy("MatchTemplate::correctAmideAA");
    e.addDescription("Residue involved : "+pRes.getIdentifier());
    //        e.addDescription(pRes.toString());
    if (e.getId()=="080601")
    {
        e.addDescription(pRes.getAtom("C").toString());
        e.addDescription(pRes.getAtom("O").toString());
    }
    std::cerr <<"ERROR"<<e.toString()<<std::endl;
    return;
}





bool protspace::MatchTemplate::testInternalLigand(MMResidue& pMoleRes,const std::string& pName)

try{
    const size_t nSize= numHeavyAtom(pMoleRes);
    const HETEntry&       HETtemplate =mHETManager.getEntry(pName);
    if (numHeavyAtom(HETtemplate.getMole().getResidue(0)) < nSize)return false;
    MatchLigand matcher(pMoleRes,mResMatrix,HETtemplate);
    matcher.setNoTerminal(false);
    /// Bug #15951
    matcher.allowSubstSearch(false);
    matcher.enforceCheck(false);

    if (!matcher.process()) return false;

    LOG("FOUND MATCH AGAINST "+pMoleRes.getName()+" -> "+pName);
    pMoleRes.setName(pName);
    matcher.check();

    return true;
}catch(ProtExcept &e)
{
    if (e.getId()=="660201")return false;
    e.addHierarchy("MatchTemplate::testInternalLigand");
    e.addDescription("Residue involved : "+pMoleRes.getIdentifier());
    throw;
}

bool protspace::MatchTemplate::processIodine(MMResidue &pMoleRes)
{
    if (pMoleRes.getName()!="I")return false;
    if (pMoleRes.numAtoms()!=1)return false;
    protspace::MMAtom& atom = pMoleRes.getAtom(0);
    if (atom.getAtomicNum()!=53)return false;
    pMoleRes.setName("IOD");
    atom.setMOL2Type("I");
    pMoleRes.setResidueType(RESTYPE::ION);
    return true;
}


void protspace::MatchTemplate::processInternalLigand(MMResidue& pMoleRes )
{
    try{
        std::chrono::time_point<std::chrono::system_clock> now = std::chrono::system_clock::now();
        LOG(pMoleRes.getIdentifier()+"\tSTART INTERNAL");
        try {

        if (testInternalLigand(pMoleRes,pMoleRes.getName()))
        {
            LOG("TEST INTERNAL LIGAND SUCCESS");
            return;
        }
        } catch (ProtExcept &e) {
            if (e.getId()!="370102")throw;/// If the internal code name doesn't have an existing residue, it's OK, because it's not this code that we want
        }
        if (!mPotentialIntNames.empty())
        for(const std::string& pHETName:mPotentialIntNames)
        {
            if (pHETName=="" ||!testInternalLigand(pMoleRes,pHETName))continue;
            LOG("FOUND BY PARAM NAME "+pHETName);
            return ;
        }
        std::chrono::time_point<std::chrono::system_clock> end = std::chrono::system_clock::now();
        LOG("TIME FOR INTERNAL:"+pMoleRes.getIdentifier()+" : "+std::to_string(std::chrono::duration_cast<std::chrono::milliseconds>(end - now).count()));
        LOG("NOT FOUND = SCAN ALL INTERNAL EXACT");
        if (scanAll(pMoleRes,true,true))return;
        std::chrono::time_point<std::chrono::system_clock> end2 = std::chrono::system_clock::now();
        LOG("TIME FOR INTERNAL:"+pMoleRes.getIdentifier()+" : "+std::to_string(std::chrono::duration_cast<std::chrono::milliseconds>(end2 - end).count()));
        LOG("NOT FOUND = SCAN ALL EXTERNAL EXACT");
        if (scanAll(pMoleRes,true,false))return;
        LOG("NOT FOUND = SCAN ALL INTERNAL INEXACT");
        if (scanAll(pMoleRes,false,true))return;
        LOG("NOT FOUND = SCAN ALL EXTERNAL INEXACT");
        if (scanAll(pMoleRes,false,false))return;

        throw_line("650201",
                   "MatchTemplate::processInternalLigand",
                   "Unable to find template");
    }catch(ProtExcept &e)
    {
        e.addHierarchy("MatchTemplate::processInternalLigand");
        e.addDescription(pMoleRes.getIdentifier());
        throw;
    }

}













bool protspace::MatchTemplate::scanAll(MMResidue& pMoleRes, const bool& exact,
                                       const bool internal)

try{

    std::vector<protspace::StringPoolObj> names;
    size_t nN=0,nC=0,nO=0,nOth=0;
    size_t inexactBestMatch=0;std::string inexactBestHET="";bool inexactFound=false;
    protspace::getCounts(pMoleRes,nN,nC,nO,nOth);
    if (exact) mHETManager.getExactMatch(nC,nO,nN,names);
    else       mHETManager.getPossibleMatch(nC,nO,nN,names);
    LOG_WARN("Number of possible matches : "+std::to_string(names.size()));

    for(const protspace::StringPoolObj& nameN:names)
    {
        const std::string& pTHET=nameN.get();
        if (internal && pTHET.length()<=3)continue;
        if (!internal && pTHET.length()>3)continue;
               // std::cout << pTHET<<" " << internal<<"\n";

        try{
            for(size_t iAt=0;iAt < pMoleRes.numAtoms();++iAt)
            {
                MMAtom& pAtom = pMoleRes.getAtom(iAt);
                if (pAtom.isHydrogen())continue;
                const unsigned char atNum=pAtom.getAtomicNum();
                pAtom.setMOL2Type("Du");
                pAtom.setAtomicType(atNum);
            }
            if (!testInternalLigand(pMoleRes,pTHET))
            {
                //std::cout <<pTHET<<" FAILED\n";
                continue;
            }

        }catch(ProtExcept &e){continue;}

        bool all=true;size_t nMatch=0;
        for(size_t iAt=0;iAt < pMoleRes.numAtoms();++iAt)
        {

            if (pMoleRes.getAtom(iAt).getMOL2()!="Du") {nMatch++;continue;}
            LOG_ERR(pMoleRes.getAtom(iAt).getIdentifier());
            all=false;break;
        }
        LOG(" TEST : "+pTHET+" " +std::to_string(nMatch));
        if (!all & exact)continue;
        // BUG #15959
        if (!exact && nMatch > inexactBestMatch)
        {
            inexactBestMatch=nMatch;
            inexactBestHET=pTHET;
            inexactFound=true;

            LOG(pMoleRes.getIdentifier()+"\tSUCCESS : "
                +pTHET+" "+std::to_string(nMatch)+"/"+std::to_string(pMoleRes.numAtoms()));
            if (nMatch == pMoleRes.numAtoms())break;
        }
        if (!exact)continue;
        LOG(pMoleRes.getIdentifier()+"\tSUCCESS : "
            +pTHET+" "+std::to_string(nMatch)+"/"+std::to_string(pMoleRes.numAtoms()));
        pMoleRes.setName(pTHET);
        if (exact) return true;
    }

    if (!exact)
    {
        if (!inexactFound)return false;
        MatchLigand matcher(pMoleRes,mResMatrix,mHETManager.getEntry(inexactBestHET));
        matcher.setNoTerminal(false);

        assert(matcher.process());
        LOG("FINAL ASSIGNMENT : "+inexactBestHET);
        pMoleRes.setName(inexactBestHET);
        return true;
    }
    return false;

}
catch(ProtExcept &e)
{
    assert(e.getId()!="320401");///atom must exists
    assert(e.getId()!="310801" && e.getId()!="310802");///Wrong MOL2 => Corruption ?

    e.addHierarchy("MatchTemplate::scanAll");
    throw;
}






void protspace::MatchTemplate::processWater(MMResidue &pMoleRes) const
try{
    const size_t nAt(pMoleRes.numAtoms());
    size_t pos(nAt);

    for(size_t iOx=0;iOx<nAt;++iOx)
    {
        if (pMoleRes.getAtom(iOx).isOxygen()){pos=iOx;break;}
    }
    if (pos == nAt)
        throw_line("650101",
                   "MatchTemplate::processWater",
                   "No oxygen found in water");


    MMAtom& atomO=pMoleRes.getAtom(pos);
    atomO.setMOL2Type("O.3");
    atomO.setName("O");

    switch(atomO.numBonds())
    {
    case 2:
        atomO.getAtom(1).setMOL2Type("H");
        atomO.getAtom(1).setName("H2");
        atomO.getBond(1).setBondType(BOND::SINGLE);
        /// DON'T BREAK HERE, if there is two Hydrogen,
        /// then we need to process both
    case 1:
        atomO.getAtom(0).setMOL2Type("H");
        atomO.getAtom(0).setName("H1");
        atomO.getBond(0).setBondType(BOND::SINGLE);
    default:
        break;
    }
}catch(ProtExcept &e)
{
    e.addDescription(pMoleRes.toString());
    assert(e.getId()!="310801" && e.getId()!="310802");/// MOL2 type should be correct
    assert(e.getId()!="320501" && e.getId()!="320502");/// Atom must exists
    throw;
}





bool protspace::MatchTemplate::delLongHBond(protspace::MMAtom& pAtom,short diff)
try{
    std::map<double,MMAtom*> list;
    for(size_t i=0;i<pAtom.numBonds();++i)
    {
        MMAtom& atmL=pAtom.getAtom(i);
        if (!atmL.isHydrogen())continue;
        list.insert(std::make_pair(pAtom.getBond(i).dist(),&atmL));
    }
    if (mKeepAtoms)return true;
    auto it=list.rbegin();
    while (diff>0)
    {
        if (it==list.rend())return false;
        LOG_ERR("Deleting Hydrogen "+(*it).second->getIdentifier()+" to fit valence for atom "+pAtom.getIdentifier());

        pAtom.getParent().delAtom(*(*it).second);
        ++it;
        --diff;
    }
    return true;

}catch(ProtExcept &e)
{
    assert(e.getId()!="350801");////Atom is in the molecule
    assert(e.getId()!="310501");////Atom in atom must exist
    assert(e.getId()!="071001"&& e.getId()!="310201");/// bond must exist
    throw;
}










void protspace::MatchTemplate::checkBonds(protspace::MacroMole& mole)
{
    for(size_t iAtm=0;iAtm < mole.numAtoms();++iAtm)
    {
        protspace::MMAtom& atom=mole.getAtom(iAtm);
        const short diff(protspace::checkValence(mole.getAtom(iAtm)));
        if (atom.getName()=="CG"&& atom.getResName()=="ASP")
        {
            atom.setFormalCharge(-1);continue;
        }
        else if (atom.isNitrogen())
        {
            if ((atom.getName()=="NE"||atom.getName()=="NH1"||atom.getName()=="NH2")
                    &&atom.getResidue().getName()=="ARG")
                atom.setFormalCharge(0);
            continue;
        }
        if (diff ==0)continue;
        if (diff+atom.getFormalCharge()==0)continue;
        if (atom.isOxygen())
        {
            if (diff==-1 && atom.getMOL2()!="O.co2")
            {
                LOG("Setting charge -1 to "+atom.getIdentifier());
                atom.setFormalCharge(-1);
                continue;
            }
        }
        else if (atom.isCarbon())
        {
            if (atom.getName()=="CZ" && atom.getResidue().getName()=="ARG")
            {atom.setFormalCharge(1);continue;}

            if (diff >0)
            {
                LOG_ERR("Difference in valence: "+std::to_string(diff)+" FOR "+atom.getIdentifier());
                LOG_ERR(atom.toString());
                if (delLongHBond(atom,diff))continue;
            }
        }else if (atom.isNitrogen())
        {
            if ((atom.getName()=="NE"||atom.getName()=="NH1"||atom.getName()=="NH2")
                    &&atom.getResidue().getName()=="ARG")
                atom.setFormalCharge(0);
            continue;
        }
        else if (atom.getAtomicNum()==16)
        {
            LOG_ERR("Difference in valence: "+std::to_string(diff)+" FOR "+atom.getIdentifier());
            LOG_ERR(atom.toString());
            if (delLongHBond(atom,diff))continue;
        }
        if ((atom.getName()=="OE1"||atom.getName()=="OE2")&&atom.getResName()=="GLU")continue;
        if ((atom.getName()=="OD1"||atom.getName()=="OD2")&&atom.getResName()=="ASP")continue;
        LOG_ERR("Unable to correct issue on valence : "+atom.getIdentifier()+" Diff valence:"+std::to_string(diff));
        LOG_ERR(atom.toString());
        //throw_line("650301","MatchTemplate::checkBonds","Unable to correct issue "+std::to_string(diff)+" " +atom.toString());


    }

}

