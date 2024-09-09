#include "headers/statics/logger.h"
#include "headers/proc/bondperception.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/strutils.h"
#include "headers/math/grid_utils.h"
#include "headers/molecule/mmbond_utils.h"
#include "headers/molecule/mmatom_utils.h"
bool protspace::BondPerception::sMatrixLoaded=false;
protspace::Matrix<double> protspace::BondPerception::sThresDist= protspace::Matrix<double>(NNATM,NNATM,0);
protspace::Matrix<double> protspace::BondPerception::sThresShort=protspace::Matrix<double>(NNATM,NNATM,0);




protspace::BondPerception::BondPerception(const double& pShortThresFactor):
    mGrid(2,2),
    mKeepExistingBond(true),
    mNoDeletion(false),
    mAcceptClashes(false),
    mMaxDev(0.4)
{
    if (!sMatrixLoaded)prepareMatrix(pShortThresFactor);
}





void protspace::BondPerception::prepareMatrix(const double& pShortThresFactor)
try{
    for(size_t i=0;i<NNATM;++i)
    {
        const double& vdw1=Periodic[i].covRadii;

        for(size_t j=i;j<NNATM;++j)
        {
            const double& vdw2=Periodic[j].covRadii;
            const double val=(vdw1+vdw2);
            sThresDist.setVal(i,j,val);
            sThresDist.setVal(j,i,val);
            const double short_val=(vdw1+vdw2)*pShortThresFactor;
            sThresShort.setVal(i,j,short_val);
            sThresShort.setVal(j,i,short_val);
        }
    }
    sThresShort.setVal(6,6,1.18);// to consider C#C
    sMatrixLoaded=true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="200301");
    assert(e.getId()!="200302");
    e.addHierarchy("BondPercetion::prepareMatrix");
    throw;
}




void protspace::BondPerception::setDistThreshold(const aelem& element1,
                                                 const aelem& element2,
                                                 const double& thres)
{
    if (element1 >= NNATM)
        throw_line("610101",
                   "BondPerception::setDistThreshold",
                   "First atomic number is above the number of element allowed");
    if (element2 >= NNATM)
        throw_line("610102",
                   "BondPerception::setDistThreshold",
                   "Second atomic number is above the number of element allowed");
    sThresDist.setVal(element1,element2,thres);
}

void protspace::BondPerception::preProcessMolecule(MacroMole& pMole)
try{
    if (!pMole.isOwner())
        throw_line("610201",
                   "BondPerception::processMolecule",
                   "Molecule cannot be an alias");
    /// Step 1 - Create grid
    prepGrid(pMole);
    /// Step 2 - Heavy atom search
    createHeavyAtomBond(pMole);
    /// Step 4 - Hydrogen - Heavy
    createHydrogenBond(pMole);
}catch(ProtExcept &e)
{
    e.addHierarchy("BondPerception::processMolecule");
    throw;
}

void protspace::BondPerception::postProcessMolecule(MacroMole& pMole)
try{
    if (!pMole.isOwner())
        throw_line("610201",
                   "BondPerception::processMolecule",
                   "Molecule cannot be an alias");

    //// Step 3 - Remove extra bonds
    removeExtraBonds(pMole);
    removeCrazyBonds(pMole);
}catch(ProtExcept &e)
{
    e.addHierarchy("BondPerception::processMolecule");
    throw;
}


void protspace::BondPerception::processMolecule(MacroMole& pMole)
try{
    if (!pMole.isOwner())
        throw_line("610201",
                   "BondPerception::processMolecule",
                   "Molecule cannot be an alias");
    /// Step 1 - Create grid
    prepGrid(pMole);
    /// Step 2 - Heavy atom search
    createHeavyAtomBond(pMole);
    //// Step 3 - Remove extra bonds
    removeExtraBonds(pMole);
    /// Step 4 - Hydrogen - Heavy
    createHydrogenBond(pMole);
}catch(ProtExcept &e)
{
    e.addHierarchy("BondPerception::processMolecule");
    throw;
}


void protspace::BondPerception::prepGrid(MacroMole& pMole)
try{
    mGrid.clear();
    mGrid.considerMolecule(pMole);
    mGrid.createGrid();

}catch(ProtExcept &e)
{
    assert(e.getId()!="220101");///Grid shouldn't be locked
    assert(e.getId()!="220601");///Should be able to find boxes for all atoms
    e.addHierarchy("BondPerception::prepGrid");
    throw;
}


bool protspace::BondPerception::isHeavyFiltered(const MMAtom& pAtom)const
{
    if (pAtom.isHydrogen())   return true;
    const bool isRMet(pAtom.isMetallic());
    if (pAtom.getResidue().numAtoms()==1 &&
            (isRMet || pAtom.getResidue().getResType()==RESTYPE::ION))return true;
    if (pAtom.getAtomicNum()==DUMMY_ATM)
    {
        ///TODO handle error
        /// ErrorAtom errAtm(pAtom,ERROR_ATOM::ATOM_DUMMY,"Dummy atom");
        //p.addNewError(errAtm);
        return true;
    }
    const uint16_t& rtype=pAtom.getResidue().getResType() ;
    if (rtype==RESTYPE::WATER)return true;
    return false;
}


bool protspace::BondPerception::checkCase(MMAtom& rAtm, MMAtom& cAtm)const
try{
    const bool isRMet(rAtm.isMetallic());
    const bool isCMet(cAtm.isMetallic());
    if (rAtm.hasBondWith(cAtm))return false;
    if (isRMet && isCMet&& &rAtm.getResidue()==&cAtm.getResidue())return false;
    /// TODO exclusion rule N3B/CO in B12

    if ((isRMet || isCMet) && &rAtm.getResidue()!=&cAtm.getResidue())return false;

    const uint16_t& ctype=cAtm.getResidue().getResType() ;
    if (ctype==RESTYPE::WATER)return false;

    const double dist=rAtm.dist(cAtm);
    const double& shortDist=sThresShort.getVal(rAtm.getAtomicNum(),cAtm.getAtomicNum())+getShorterException(rAtm,cAtm);
    if (dist <  shortDist)
    {

        if (!mAcceptClashes){
        LOG_ERR(rAtm.getIdentifier()+" "+cAtm.getIdentifier()+" ATOM CLASH||"+std::to_string(shortDist)+"||"+std::to_string(dist));
        //        ErrorBond errBond(rAtm,cAtm,ERROR_BOND::ATOM_CLASH,
        //                          rAtm.getIdentifier()+" / "+cAtm.getIdentifier()+":Atom clash. Should be > "+std::to_string(shortDist)
        //                          +" Angstroems. Is "+std::to_string(dist)+" Angstroems");
        //        mole.addNewError(errBond);
        return false;
        }
        else {
            std::cerr<<"WARNING - "<<rAtm.getIdentifier()<<"\t"<<cAtm.getIdentifier()<<"\tDISTANCE TOO SHORT\t"<<dist<<"\t"<<shortDist<<"\n";
        }
    }
    const double& thresDist(sThresDist.getVal(rAtm.getAtomicNum(),cAtm.getAtomicNum()));

    if (dist-mMaxDev > thresDist+getException(rAtm,cAtm))return false;
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="200202" && e.getId()!="200201");
    e.addHierarchy("BondPerception::checkCase");
    throw;

}

double protspace::BondPerception::
getException(const MMAtom& rAtm, const MMAtom& cAtm)const
{
    if (&rAtm.getResidue()!= &cAtm.getResidue())return 0;

    if (rAtm.getElement()=="N" && cAtm.getElement()=="Fe") return 1;
    if (rAtm.getElement()=="Fe" && cAtm.getElement()=="N") return 1;


    return 0;
}

double protspace::BondPerception::getShorterException(const protspace::MMAtom &rAtm, const protspace::MMAtom &cAtm) const
{
    if (&rAtm.getResidue()!= &cAtm.getResidue())return 0;

    if (rAtm.getElement()=="N" && cAtm.getElement()=="C") return -0.1;
    if (rAtm.getElement()=="C" && cAtm.getElement()=="N") return -0.1;


    return 0;
}

void protspace::BondPerception::createHeavyAtomBond(MacroMole& pMole)
try{
    std::vector<MMAtom*> list;
    const size_t nAtm(pMole.numAtoms());
    for(size_t iAtm=0;iAtm<nAtm;++iAtm)
    {
        MMAtom& rAtm=pMole.getAtom(iAtm);
        if (isHeavyFiltered(rAtm))continue;

        list.clear();
        protspace::getAtomClose(list,rAtm,4,mGrid,true);

        for(size_t jAtm=0;jAtm<list.size();++jAtm)
        {
            MMAtom& cAtm=*list.at(jAtm);
            if (cAtm.getMID() <= rAtm.getMID())continue;

            if (isHeavyFiltered(cAtm))continue;
            if (checkCase(rAtm,cAtm))pMole.addBond(rAtm,cAtm,BOND::UNDEFINED);

        }
    }
}catch(ProtExcept &e)
{

    assert(e.getId()!="350604");///Atoms cannot be the same
    assert(e.getId()!="350602"&& e.getId()!="350603");///Atoms must be part of the molecule
    assert(e.getId()!="220701" && e.getId()!="220702");///atom must be in grid
    assert(e.getId()!="030401");////Atom must exists
    e.addHierarchy("BondPerception::createHeavyAtomBond");
    throw;
}


bool protspace::BondPerception::checkHydrogenBond(MMAtom& Hyd,
                                                  MMAtom& heavy)const
{
    const aelem num(heavy.getAtomicNum());
    if(!((num>=5 && num<=8) || (num>=16 && num<=17) || (num>=34 && num<=35) ||num==53))
    {
        LOG_ERR("HYD - "+Hyd.getIdentifier()+" BAD HEAVY "+heavy.getIdentifier() );
        return false;
    }
    const double dist= heavy.dist(Hyd);
    const double &thres =sThresShort.getVal(1,heavy.getAtomicNum());
    if (dist < thres-0.3 )
    {
        LOG_ERR("Possible clash "+Hyd.getIdentifier()+" - "+heavy.getIdentifier()+" "+
                std::to_string(dist)+" < "+std::to_string(thres));
        ///TODO Handle error
        //        ErrorBond errBond(Hyd,heavy,ERROR_BOND::ATOM_CLASH,
        //                          "Atom clash. Should be < "+std::to_string(thres)
        //                          +" Angstroems. Is "+std::to_string(dist)+" Angstroems");
        //        mole.addNewError(errBond);
        return false;
    }

    return true;
}

void protspace::BondPerception::createHydrogenBond(MacroMole& mole)
try{
    double shortest_dist=0;
    size_t bestAtm=0;
    std::vector<MMAtom*> HToDel;
    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
    {
        MMAtom& rAtm=mole.getAtom(iAtm);
        if (!rAtm.isHydrogen())continue;
        const MMResidue& mRes= rAtm.getResidue();
        shortest_dist=1000;
        bestAtm=0;
        for(size_t iRAt=0;iRAt< mRes.numAtoms();++iRAt)
        {
            const MMAtom& cAtm=mRes.getAtom(iRAt);
            if (cAtm.isHydrogen())continue;
            const double dist=cAtm.dist(rAtm);
            //LOG(rAtm.getIdentifier()+" - "+cAtm.getIdentifier()+" - "+std::to_string(dist)+" - ")
            if (dist > shortest_dist)continue;
            shortest_dist=dist;
            bestAtm=iRAt;
        }
        if (shortest_dist==1000){
            //LOG("HYD - "+rAtm.getIdentifier()+" - "+std::to_string(shortest_dist)+" "+std::to_string(mRes.numHeavyAtom()));
            HToDel.push_back(&rAtm);continue;}
        MMAtom& Hyd=mRes.getAtom(bestAtm);
        if (Hyd.hasBondWith(rAtm))continue;
        if (!checkHydrogenBond(rAtm,mRes.getAtom(bestAtm)))HToDel.push_back(&rAtm);
        else mole.addBond(rAtm,mRes.getAtom(bestAtm),BOND::SINGLE);

    }
    LOG("NUMBER OF HYDROGEN TO DELETE :"+std::to_string(HToDel.size()));
    if (!mNoDeletion)cleanAtoms(mole,HToDel);

    //std::cout <<"EXIT"<<std::endl;
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");///Atom must exist in molecule
    assert(e.getId()!="320401");///Atom must exist in residue
    assert(e.getId()!="350604");///Atoms cannot be the same
    assert(e.getId()!="350602"&& e.getId()!="350603");///Atoms must be part of the molecule
    assert(e.getId()!="220701" && e.getId()!="220702");///atom must be in grid
    assert(e.getId()!="030401");////Atom must exists
    assert(e.getId()!="350601");///Molecule cannot be alias
    e.addHierarchy("BondPerception::createHydrogenBond");
    throw;
}

void protspace::BondPerception::cleanAtoms(MacroMole& mole,const std::vector<MMAtom*>& HtoDel)const
try{

    for(auto at:HtoDel)
    {
        LOG("DELETING HYDROGEN "+at->getIdentifier());
        mole.delAtom(*at,true);
    }


    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm) {
        MMAtom &rAtm = mole.getAtom(iAtm);
        if (!rAtm.isHydrogen())continue;
        if (rAtm.numBonds() == 1)continue;
        assert(rAtm.numBonds()!=0);
        double shortd=1000;const MMAtom*  pos=nullptr;
        for(size_t k=0;k<rAtm.numBonds();++k)
        {
            const MMAtom& at=rAtm.getAtom(k);
            const double dist(at.dist(rAtm));
            if (dist >= shortd)continue;
            shortd =dist; pos=&at;
        }
        for(size_t k=0;k<rAtm.numBonds();++k)
        {
            if (&rAtm.getAtom(k)== pos)continue;
            mole.delBond(rAtm.getBond(k));
            k=0;
        }

        if (rAtm.numBonds() == 1)continue;

        std::cout <<rAtm.toString()<<std::endl;
        for(size_t i=0;i<rAtm.numBonds();++i)
        {
            std::cout <<rAtm.getAtom(i).toString()<<std::endl;
        }

        assert(rAtm.numBonds()==1);
    }///END FOR
}catch(ProtExcept &e)
{
    assert(e.getId()!="350801");///Atom must be part of the molecule
    assert(e.getId()!="030401");///Atom must exist in molecule
    assert(e.getId()!="310501");///Atom in atom must exists
    assert(e.getId()!="310201");///Bond must exist in atom
    assert(e.getId()!="071001");///Bond must exist in atom
    assert(e.getId()!="350701");/// Given Bond must be part of this molecule
    assert(e.getId()!="350702");/// Given Bond must be part of this molecule
    assert(e.getId()!="020101");/// Given link must be part of this dot
    assert(e.getId()!="020201");/// Given link must be found in this dot
    e.addHierarchy("BondPerception::cleanAtoms");
    throw;
}




void protspace::BondPerception::removeExtraBonds(MacroMole& mole)
try{
    ///TODO test function
    std::multimap<double,MMBond*> dists;
    std::multimap<double,MMBond*>::reverse_iterator itD;
    static const std::string rNameRule=" CD CO K RB CA NA ";
    for(size_t iAtm=0;iAtm<mole.numAtoms();++iAtm)
    {
        MMAtom& rAtm=mole.getAtom(iAtm);
        const bool isH(rAtm.isHydrogen());
        const bool isM(rAtm.isMetallic());
        const bool isRName(isInList(rNameRule,rAtm.getResName()));
        signed char diffValence=checkValence(rAtm);


        if (isH)continue;
        if ((isM||isRName) && rAtm.getResidue().numAtoms()==1 )//Bug #15952
        {
            protspace::removeAllBondsFromAtom(rAtm);
            continue;
        }
        if (isM|| diffValence >=0)continue;
         LOG("Diff Valence "+rAtm.getIdentifier()+" " +std::to_string(diffValence));
        if (rAtm.getResidue().getResType()==RESTYPE::WATER)
        {
            ///CASE 1QPW: FE(8791/6444):: A::HEM ( 650/ 574)<->O(9405/6897):: A::HOH ( 754/ 683)
            /// 1 Hydrogen of the water was deleted to make room for Oxygen-Iron bond
            /// Afterward, the MatchTemplate assigned the  Iron as Hydrogen because of naming convention
            /// Here, we ensure that all water molecules are only bonded to themselves
            for(size_t iBd=0;iBd<rAtm.numBonds();++iBd) {
                MMAtom& cAtm = rAtm.getAtom(iBd);
                if (cAtm.isHydrogen()) continue;
                MMBond &bond = rAtm.getBond(iBd);
                mole.delBond(bond);--iBd;
            }
        }
        dists.clear();
        for(size_t iBd=0;iBd<rAtm.numBonds();++iBd)
        {
            MMBond& bond=rAtm.getBond(iBd);
            dists.insert(std::make_pair(bond.dist()-
                                        sThresDist.getVal(bond.getAtom1().getAtomicNum(),bond.getAtom2().getAtomicNum()),&bond));
        }
        itD=dists.rbegin();
        for(itD=dists.rbegin();itD!=dists.rend();++itD)
        {
            if ((*itD).first <0.1)break;
            LOG("Deletion of "+(*itD).second->toString()+" to maintain valence\n"+rAtm.toString());
            mole.delBond(*(*itD).second);
            diffValence++;
            if (diffValence==0)break;
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="350701" &&e.getId()!="350702");
    assert(e.getId()!="020101" && e.getId()!="020201");
    assert(e.getId()!="310501");
    assert(e.getId()!="200201" && e.getId()!="200202");
    e.addHierarchy("BondPerception::removeExtraBonds");
    throw;
}

void protspace::BondPerception::removeCrazyBonds(protspace::MacroMole& pMole)
{
    for(size_t iB=0;iB<pMole.numBonds();++iB)
    {
        protspace::MMBond& bd=pMole.getBond(iB);
        if (bd.dist() < 10)continue;
        LOG("Deletion of "+bd.toString()+" - bond way too long");
        pMole.delBond(bd);
        --iB;
    }
}
