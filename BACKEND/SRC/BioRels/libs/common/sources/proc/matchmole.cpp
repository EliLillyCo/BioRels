
#include <inttypes.h>
#include <math.h>
#include <bitset>
#include <sstream>
#include "headers/proc/matchmole.h"
#include "headers/molecule/macromole.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/math/rigidbody.h"
#include "headers/statics/logger.h"
#undef NDEBUG /// Active assertion in release

const uint32_t protspace::MatchMole::ATOM_NAME            =0x0001;
const uint32_t protspace::MatchMole::ATOM_MOL2            =0x0002;
const uint32_t protspace::MatchMole::ATOM_SYMBOL          =0x0004;
const uint32_t protspace::MatchMole::ATOM_ID              =0x0008;
const uint32_t protspace::MatchMole::ATOM_PROP            =0x0010;
const uint32_t protspace::MatchMole::BOND_ORDER           =0x0020;
const uint32_t protspace::MatchMole::RESIDUE_NAME         =0x0040;
const uint32_t protspace::MatchMole::BOND_LAYER           =0x0100;
const uint32_t protspace::MatchMole::FULL_SCAN            =0x0200;
const uint32_t protspace::MatchMole::EUCLIDIAN_DISTANCE   =0x0400;
const uint32_t protspace::MatchMole::BOND_DISTANCE        =0x0800;
const uint32_t protspace::MatchMole::NO_HYDROGEN          =0x1000;
const uint32_t protspace::MatchMole::RESIDUE_BACKBONE     =0x2000;
const uint32_t protspace::MatchMole::W_POLAR_HYDROGEN     =0x4000;


const uint32_t protspace::MatchMole::SORT_BY_RMSD         =0x2000;
const uint32_t protspace::MatchMole::SORT_BY_SIZE         =0x4000;
const uint32_t protspace::MatchMole::TWO_DIMENSION_MATCH  =0x8000;
const uint32_t protspace::MatchMole::CONSIDER_RING        =0x10000;






protspace::MatchMole::MatchMole(MacroMole& reference,
                     MacroMole& comparison, const bool &onlyUsed):
    reference(reference),
    comparison(comparison),
    rules(0x0000),
    runDone(false),
    mFullScan(false),
    mOnlyUsed(onlyUsed)
{
    eucl_dist_threshold=-1;
    bond_dist_threshold=-1;
    rmsd_threshold=10;

}



void protspace::MatchMole::clearRule()
{
    rules=0x0000;
}


void protspace::MatchMole::addRule(const uint32_t& rule)
{
    rules|=rule;
    runDone=false;
}






void protspace::MatchMole::removeRule(const uint32_t& rule)
{
    rules^=rule;
    runDone=false;
}



void protspace::MatchMole::prepareDistMatrix(MacroMole& molecule,
                                  UIntMatrix& matrix,
                                  std::map<int,int>& map
                                  ) throw(ProtExcept)
try{
    // Depending on whether or not the user as specified to consider
    // only atom in use.

    if(!mOnlyUsed)
    {
        protspace::getDistanceMatrix(molecule,matrix);
        return;
    }
    // Creating a list of used atoms ...
    const size_t nAtm(molecule.numAtoms());
    std::vector<MMAtom*> atmList;atmList.reserve(nAtm);
    for (size_t iAtmR=0; iAtmR< nAtm;++iAtmR)
    {
        MMAtom& atomR= molecule.getAtom(iAtmR);
        if (!atomR.isSelected())continue;

        atmList.push_back(&atomR);
        map.insert(std::make_pair(atomR.getMID(),atmList.size()-1));
    }
    // To generate distance matrix based on only these atoms :
    protspace::getDistanceMatrix(molecule,matrix,atmList);

}catch(ProtExcept &e)
{
    e.addHierarchy("MatchMole::prepareDistanceMatrix");
    throw;
}


bool protspace::MatchMole::isFiltered(const MMAtom& pAtom)const
{
    if (mOnlyUsed && !pAtom.isSelected())return true;

    if (pAtom.getName()=="DuAr"||pAtom.getName()=="DuCy") return true;
    if (((rules & W_POLAR_HYDROGEN) ==W_POLAR_HYDROGEN)
       && pAtom.isHydrogen() && pAtom.numBonds()>0
            && pAtom.getAtom(0).isCarbon())return true;
    if ((rules &NO_HYDROGEN )==NO_HYDROGEN
            && pAtom.isHydrogen()
            && pAtom.numBonds()>0) return true;
    return false;

}


bool protspace::MatchMole::checkBondLayer(const MMAtom& atomR,
                                          const MMAtom& atomC)const
{
    //    cout <<"LAYER"<<endl;
    if (atomR.numBonds()!=atomC.numBonds())return false;
    size_t countR[8],countC[8];
    for(size_t i=0;i<8;++i){countR[i]=0;countC[i]=0;}
    for(size_t iBd=0;iBd <atomR.numBonds();++iBd)
    {
        countR[atomR.getBond(iBd).getType()]++;
    }
    for(size_t iBd=0;iBd <atomC.numBonds();++iBd)
    {
        countC[atomC.getBond(iBd).getType()]++;
    }
    bool ok=true;
    for(size_t i=0;i<8;++i){if (countR[i]!=countC[i])ok=false;}
    return ok;
}

void protspace::MatchMole::listPairs()throw(ProtExcept)
try{

    static const std::string BACKBONE_NAME(" CA C N O ");
    // Looping over all atoms in the reference and the comparison
    // When an atom in the reference and an atom in the comparison
    // have passed all filters, there are considered as a potential pair:
    for (size_t iAtmR=0; iAtmR< reference.numAtoms();++iAtmR)
    {
        MMAtom& atomR= reference.getAtom(iAtmR);

        if (isFiltered(atomR))continue;

        for (size_t iAtmC=0; iAtmC <comparison.numAtoms();++iAtmC)
        {
            MMAtom& atomC= comparison.getAtom(iAtmC);

            if (isFiltered(atomC))continue;


            // FILTERING RULES :
            if ((rules &ATOM_NAME   )==ATOM_NAME    && atomR.getName() != atomC.getName()) {continue;}
            if ((rules &ATOM_MOL2   )==ATOM_MOL2    && atomR.getMOL2() != atomC.getMOL2()) {continue;}
            if ((rules &ATOM_SYMBOL )==ATOM_SYMBOL  && atomR.getAtomicNum() != atomC.getAtomicNum()) {continue;}
            if ((rules &ATOM_ID     )==ATOM_ID      && atomR.getFID() != atomC.getFID() ) {continue;}
            if ((rules &RESIDUE_NAME)==RESIDUE_NAME && atomR.getResidue().getName() != atomC.getResidue().getName()) {continue;}
            if ((rules &BOND_LAYER  )==BOND_LAYER   && !checkBondLayer(atomR,atomC))continue;
            if ((rules &RESIDUE_BACKBONE) ==RESIDUE_BACKBONE
                    && (BACKBONE_NAME.find(" "+atomR.getName()+" ")==std::string::npos
                        ||atomR.getName()!=atomC.getName()))continue;
            if ((rules &CONSIDER_RING)==CONSIDER_RING)
            {
                const bool refInRing=reference.isAtomInRing(atomR);
                const bool compInRing=comparison.isAtomInRing(atomC);
                //if (iAtmR==iAtmC)cout << "RING "<<reference.isAtomInRing(atomR)<<" "<<comparison.isAtomInRing(atomC)<<endl;
                if (refInRing!=compInRing)continue;
                //                if (refInRing)
                //                {
                //                    std::std::vector<MMRing*> listRR, listRC;
                //                    reference.getRingsFromAtom(atomR,listRR);
                //                    comparison.getRingsFromAtom(atomC,listRC);
                //                    if (listRR.size()==1 && listRC.size()==1 &&
                //                    listRR.at(0)->isAromatic()!= listRC.at(0)->isAromatic())continue;

                //                }
            }
//std::cout << graphmatch.numPairs()<<"\t"<<atomR.getIdentifier()<<"\t"<<atomC.getIdentifier()<<"\n";
            // Both have passed the filter, we create a new Pair :
            graphmatch.addPair(atomR,atomC);
        }
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");///Atom must exists
    assert(e.getId()!="310701");///Residue must exists
    e.addHierarchy("MatchMole::listPairs");
    throw;
}


bool protspace::MatchMole::checkBondDistance(const Pair<MMAtom>& pairR,
                                             const Pair<MMAtom>& pairC)const
try{
    // Little trick here since atom MID are based on the whole molecule
    // and not the subset of selected atoms.
    // So we use a mapping for both reference and comparison
    // respectivly called refMapMatrix and compMapMatrix
    const size_t posR1=(mOnlyUsed)?mRefMapMatrix.at(pairR.obj1.getMID()):pairR.obj1.getMID();
    const size_t posR2=(mOnlyUsed)?mCompMapMatrix.at(pairR.obj2.getMID()):pairR.obj2.getMID();
    const size_t posC1=(mOnlyUsed)?mRefMapMatrix.at(pairC.obj1.getMID()):pairC.obj1.getMID();
    const size_t posC2=(mOnlyUsed)?mCompMapMatrix.at(pairC.obj2.getMID()):pairC.obj2.getMID();


    const unsigned int & REF_DIST = mRefMatrix.getVal(posR1,posC1);
    const unsigned int & COMP_DIST= mCompMatrix.getVal(posR2,posC2);
    if (fabs(REF_DIST-COMP_DIST) > bond_dist_threshold) return false;
    if ((rules & BOND_ORDER)==BOND_ORDER && REF_DIST==COMP_DIST && REF_DIST==1)
    {

        if (pairR.obj1.getBond(pairC.obj1).getType()!=
                pairR.obj2.getBond(pairC.obj2).getType()) return false;
    }
    return true;
}catch(ProtExcept &e)
{
    assert(e.getId()!="200202" && e.getId()!="200201");/// matrix positions must exists
    e.addHierarchy("MatchMole::checkBondDistance");
    throw;
}catch (std::out_of_range &e)
{
    throw_line("630201","MatchMole::checkBondDistance","Position out of range "+
               std::string(e.what()));
}


void protspace::MatchMole::linkPairs() throw(ProtExcept)
try{
    const size_t nPair=graphmatch.numPairs();
    if (nPair==0)
        throw_line("630101","MatchMole::linkPairs","No pairs found");
    // STEP 2 : Linking pairs
    // Now that we have a list of all potential atom mapping
    // we are performing an all against all analysis of these pairs
    // in order to check that the distance between the 2 atoms in the reference
    // are at the same distance as between the 2 atoms in the comparison
    //  Pair 1 : R1 C1      Pair 2 : R2 C3
    // So we compare the distance R1-R2 and C1-C3

    // Still, we don't consider case where the 2 atoms in the reference or the
    // 2 atoms in the comparison are the same (1) :
    //  Pair 1 : R1 C1      Pair 2 : R1 C2
    // That doesn't make cense since it would mean that both C2 and C1
    // match a single atom R1

    // User has the possibility to choose between an euclidian distance (2)
    // And a bond distance (3)

    for (size_t iAtmR=0; iAtmR< nPair;++iAtmR)
    {
        Pair<MMAtom>& pairR= graphmatch.getPair(iAtmR);

        for (size_t iAtmC =iAtmR+1; iAtmC <nPair;++iAtmC)
        {
            Pair<MMAtom>& pairC= graphmatch.getPair(iAtmC);

            // (1)
            if(&pairR.obj1 == &pairC.obj1) continue;
            if(&pairR.obj2 == &pairC.obj2) continue;

            // (2)
            if ((rules &  EUCLIDIAN_DISTANCE) == EUCLIDIAN_DISTANCE)
            {

                const double REF_DIST = pairR.obj1.pos().distance(pairC.obj1.pos());
                const double COMP_DIST= pairR.obj2.pos().distance(pairC.obj2.pos());
                if (std::fabs(REF_DIST-COMP_DIST) > eucl_dist_threshold) continue;
            }

            // (3)
            if ((rules &  BOND_DISTANCE) == BOND_DISTANCE &&
                !checkBondDistance(pairR,pairC))continue;

//std::cout <<iAtmR<<" " <<iAtmC<<"\n";
            graphmatch.addLink(pairR,pairC);

        }
    }
}catch(ProtExcept &e)
{
    if (e.getId()=="630101")throw;
    assert(e.getId()!="110201");///Pairs must exists
    assert(e.getId()!="630201");///Out of range exception shouldn't happen
    assert(e.getId()!="030301" && e.getId()!="030302");///Add link must work
    e.addHierarchy("MatchMole::linkPair");
    throw;
}

void protspace::MatchMole::scoreCliques(
        const uint32_t& sortRules,
        std::multimap<double, Clique<MMAtom>*>& tempSort)
{
    protspace::RigidBody aligner;
bool issue=false;
    // Coordinate vector used for rotation :
    std::vector<Coords> rlist, clist;
    for (size_t iCl=0;iCl <graphmatch.numCliques();++iCl)
    {
        try{
        Clique<MMAtom>& clique=graphmatch.getClique(iCl);
        const size_t cliqueSize(clique.listpair.size());
        rlist.clear();clist.clear();

        // there is no cense to have a rmsd on a 2D match :
        if ((rules & TWO_DIMENSION_MATCH) == TWO_DIMENSION_MATCH)
        {
            clique.rmsd=0;
        }
        else
        {
            // Loading all reference and compairison coordinates for this clique
            for (size_t i=0;i< cliqueSize;++i)
            {
                rlist.push_back(clique.listpair.at(i)->obj1.pos());
                clist.push_back(clique.listpair.at(i)->obj2.pos());
            }
            // So we can align the atoms and get a rmsd :
            aligner.clear();
            aligner.loadCoordsToRigid(rlist);
            aligner.loadCoordsToMobile(clist);
            clique.rmsd=aligner.calcRotation();

        }

        if (((sortRules & SORT_BY_RMSD)==SORT_BY_RMSD) && clique.rmsd > rmsd_threshold) continue;

        // Depending on the rule the user gave, different type of sort :
        if (((sortRules & SORT_BY_RMSD)==SORT_BY_RMSD)
                &&(sortRules & SORT_BY_SIZE)==SORT_BY_SIZE)
        {
            const double value=(double)(cliqueSize*cliqueSize)/clique.rmsd;
            tempSort.insert(std::make_pair(value,&clique));
        }
        else if ((sortRules & SORT_BY_RMSD)==SORT_BY_RMSD)
        {
            tempSort.insert(std::make_pair(clique.rmsd,&clique));

        }
        else if ((sortRules & SORT_BY_SIZE)==SORT_BY_SIZE)
        {
            tempSort.insert(std::make_pair((double)cliqueSize,&clique));
        }
        }catch(ProtExcept &e)
        {
            if (e.getId()=="210201"||e.getId()=="210202"|| e.getId()!="210203")
                LOG_ERR("SCORE CLIQUE "+e.getId());
            issue=true;
        }
    }
    if(issue)std::cerr<<"Issue while scoring - Please contact admin"<<std::endl;
}





void protspace::MatchMole::sortResults(const uint32_t& sortRules) throw(ProtExcept)
{
    sortedClique.clear();



    // Order clique by score
    std::multimap<double, Clique<MMAtom>*> tempSort;

    scoreCliques(sortRules,tempSort);
    // Scanning all clique :


    // REVERSE Iteration to get highest score first :
    for (std::multimap<double, Clique<MMAtom>* >::reverse_iterator it=tempSort.rbegin();
         it!= tempSort.rend();++it)
    {
        const double& score = (*it).first;
        Clique<MMAtom> & clique = *(*it).second;
        clique.score=score;
        sortedClique.push_back(&clique);
    }
}







void protspace::MatchMole::runMatch(const uint32_t& sortRules,
                         const bool calcPairs) throw(ProtExcept)
{

    runDone=false;
    graphmatch.setFullScan(mFullScan);
    graphmatch.isVirtual(false);
    if (calcPairs) graphmatch.clear();

    //if ((rules & FULL_SCAN)==FULL_SCAN) graphmatch.setFullScan(true);

    try{

        // STEP 1 - Calculation of distance matrix for both reference and comparison
        // Only when user wants to consider the number of bonds between two atoms

        if (rules &BOND_DISTANCE)
        {
            prepareDistMatrix(reference,mRefMatrix,mRefMapMatrix);
            prepareDistMatrix(comparison,mCompMatrix,mCompMapMatrix);
        }//(rules &BOND_DISTANCE)


        // STEP 2 : Listing pairs :
        if (calcPairs) listPairs();
        //  cout << "Number of pairs : " <<graphmatch.numPairs()<<endl;


        linkPairs();
        //  cout << "Number of edges : " <<graphmatch.numEdges()<<endl;


        // Running search for cliques :
        graphmatch.calcCliques();
        //std::cout << "Number of cliques : " << graphmatch.numCliques()<<std::endl;


        sortResults(sortRules);

    }catch(ProtExcept &e)
    {
        assert(e.getId()!="110401");///Num pairs should have been checked in listPairs

        e.addHierarchy("protspace::MatchMole::runMatch");
        e.addDescription("Reference molecule : "+reference.getName()+"\n"
                         +"Comparison molecule: "+comparison.getName()+"\n");
        throw;
    }

    runDone=true;
}





size_t protspace::MatchMole::numCliques() const {
    assert(runDone==true);
    return sortedClique.size();
}





const protspace::Clique<protspace::MMAtom>& protspace::MatchMole::getClique(const size_t& pos)const
{
    assert(runDone==true && pos < sortedClique.size());
    return *sortedClique.at(pos);
}




protspace::Pair<protspace::MMAtom>& protspace::MatchMole::getPair(const size_t& pos)throw(ProtExcept)
{
    return graphmatch.getPair(pos);
}


void protspace::MatchMole::checkPair(MMAtom& atom1, MMAtom& atom2)
{
    size_t lookedpair=graphmatch.numPairs();
    for(size_t iPair=0;iPair < graphmatch.numPairs();++iPair)
    {
        Pair<MMAtom>& paire=graphmatch.getPair(iPair);
        if(!(&atom1 == &paire.obj1 && &atom2==&paire.obj2))continue;
        lookedpair=iPair;
    }

    if (lookedpair==graphmatch.numPairs())return;
    Pair<MMAtom>& paire=graphmatch.getPair(lookedpair);
    std::cout<<"LOOKED PAIR : "<<lookedpair<<"\n"
       <<paire.obj1.getIdentifier()<<"\t"<<paire.obj2.getIdentifier()<<"\n";
    std::cout <<"LINKED TO :\n";
    for(size_t iVe=0;iVe<paire.vertex.numDot();++iVe)
    {
        Pair<MMAtom>& pairC=graphmatch.getPair(paire.vertex.getVertex(iVe).getMID());
        std::cout <<"|-->"<<pairC.obj1.getIdentifier()<<"\t"<<pairC.obj2.getIdentifier()<<std::endl;
    }
}

bool protspace::MatchMole::checkClique(std::vector<size_t>& list,const bool& verbose)const
{
    bool ok=true;
    for(size_t iP=0;iP<list.size();++iP)
    {
        const size_t& p1=list.at(iP);
        const Pair<MMAtom>& pairP=graphmatch.getPair(p1);
        for(size_t iL=iP+1;iL<list.size();++iL)
        {
            const Pair<MMAtom>& pairL=graphmatch.getPair(list.at(iL));
            if (pairP.vertex.hasEdgeWith(pairL.vertex))continue;
            ok=false;
            if (!verbose)continue;
            std::cout <<list.at(iP)<<"\t"<<pairP.obj1.getIdentifier()<<"\t"<<pairP.obj2.getIdentifier()<<std::endl;
            std::cout <<list.at(iL)<<"\t"<<pairL.obj1.getIdentifier()<<"\t"<<pairL.obj2.getIdentifier()<<std::endl;
            std::cout<<std::endl<<std::endl;
        }
    }
    if (!ok)return false;
    if(!verbose)return true;
    std::cout <<"ALL "<<list.size()<<" GOOD"<<std::endl;
    for(size_t iP=0;iP<list.size();++iP)
    {const Pair<MMAtom>& pairP=graphmatch.getPair(list.at(iP));

        std::cout <<list.at(iP)<<"\t"<<pairP.obj1.getIdentifier()<<"\t"<<pairP.obj2.getIdentifier()<<std::endl;
    }
    return true;

}




void protspace::MatchMole::addPair(MMAtom& atm1, MMAtom&atm2)
{
    graphmatch.addPair(atm1,atm2);
}






