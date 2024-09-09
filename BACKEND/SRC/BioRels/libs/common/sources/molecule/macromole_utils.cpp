#include <math.h>
#include <sstream>
#include "headers/molecule/macromole_utils.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/statics/logger.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/statics/atomdata.h"
#include "headers/molecule/mmatom_utils.h"
#include "headers/math/matrix.h"
#include "headers/math/rigidalign.h"
#undef NDEBUG /// Active assertion in release
namespace protspace
{
void getMoleculesData(const GroupList<MacroMole>& mMolecules,
                      Coords& pCenter,
                      Coords& pMassCenter,
                      Coords& pResidueMassCenter,
                      const bool& wHydrogen,
                      const bool& onlySelected)
try{
    ///
    /// \brief Number of atoms taken into account to generate geometric mCenter
    ///
    double nActAtom=0;

    ///
    /// \brief Number of active atom in a residue
    ///
    double nActResAtom=0;


    ///
    /// \brief Number of active residue
    ///
    double nActRes=0;


    double pMassSum=0;

    for(size_t iMole=0;iMole<mMolecules.size();++iMole)
    {
        const MacroMole& molecule = mMolecules.get(iMole);
        const int nRes((int)molecule.numResidue());
        for (int iRes=-1; iRes< nRes;++iRes)
        {
            const MMResidue& residue = molecule.getResidue(iRes);
            nActResAtom=0;
            for (size_t iAtm=0; iAtm<residue.numAtoms();++iAtm)
            {
                const MMAtom& atom=residue.getAtom(iAtm);
                if (atom.isHydrogen() && !wHydrogen) continue;
                if (onlySelected && ! atom.isSelected()) continue;
                nActResAtom++;
                nActAtom++;
                pCenter+= atom.pos();
                pMassSum+= atom.getWeigth();
                pMassCenter+=atom.pos()*atom.getWeigth();
            }
            if (nActResAtom==0) continue;
            pResidueMassCenter+= protspace::getCenter(residue,onlySelected);
            nActRes++;
        }
    }

    assert(nActAtom > 0);
    assert(nActRes > 0);
    assert(pMassSum>0);
    pCenter/=nActAtom;
    pMassCenter/=pMassSum;
    pResidueMassCenter/=nActRes;
}catch(ProtExcept &e)
{e.addHierarchy("MacroMoleUtils::getMoleculesData");
    assert(e.getId()!="040101");//// Molecule must exists in grouplist
    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="320501" && e.getId()!="320502");
    throw;

}


void convertMSE_MET(MacroMole& mole)
try{
    const int nRes((const int) mole.numResidue());
    size_t pos=0;
    for(int iR=0;iR<nRes;++iR)
    {
        MMResidue& res=mole.getResidue(iR);
        if (res.getName()!="MSE")continue;
        res.setName("MET");
        if (!res.hasAtom("SE",pos))
        {
            LOG_ERR("Unable to find SE when converting MSE to MET");
            ErrorResidue err("Atom not found when converting MSE to MET : SE",res);
            mole.addNewError(err);
            continue;
        }
        MMAtom& atom=res.getAtom(pos);
        atom.setName("SD");
        atom.setAtomicType(16);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="320401");///Atom must exists
    e.addHierarchy("converMSE_MET");
    throw;
}


void convertCSE_CYS(MacroMole& mole)
try{
    const int nRes((const int) mole.numResidue());
    size_t pos=0;
    for(int iR=0;iR<nRes;++iR)
    {
        MMResidue& res=mole.getResidue(iR);
        if (res.getName()!="CSE" && res.getName()!="SEC")continue;
        res.setName("CYS");
        if (!res.hasAtom("SE",pos))
        {
            LOG_ERR("Atom not found when converting CSE to CYS: SG"+res.getIdentifier());
            ErrorResidue err("Atom not found when converting CSE to CYS: SG",res);
            mole.addNewError(err);
            continue;
        }
        MMAtom& atom=res.getAtom(pos);
        atom.setName("SG");
        atom.setAtomicType(16);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="351901");///Residue must exists
    assert(e.getId()!="320401");///Atom must exists
    e.addHierarchy("converCSE_CYS");
    throw;
}


double calcRMSD(std::vector<MMAtom*>&rList, std::vector<MMAtom*>& cList)
{

    double rmsd=0;
    for(size_t i=0;i<rList.size();++i)
    {
        const Coords& cooR=rList.at(i)->pos();
        const Coords& cooC=cList.at(i)->pos();
        rmsd += (cooR.x()-cooC.x())*(cooR.x()-cooC.x())
                +(cooR.y()-cooC.y())*(cooR.y()-cooC.y())
                +(cooR.z()-cooC.z())*(cooR.z()-cooC.z());
    }
    rmsd /=rList.size();
    if (rmsd ==0) return 0;
    return sqrt(rmsd);
}


size_t getNumHeavyAtoms(const MacroMole& mole)
{
    size_t nAtm=0;
    for(size_t i=0;i<mole.numAtoms();++i)
    {
        const MMAtom& atom = mole.getAtom(i);
        /// Note >1 and not != 1 because the value 0 is for dummy atom
        if (atom.getAtomicNum() >1)nAtm++;
    }
    return nAtm;
}


size_t numAtom(const MacroMole& mole,const unsigned char&pElem)
{
    size_t nAt=0;
    for(size_t iBd=0;iBd < mole.numAtoms();++iBd)
    {
        if (mole.getAtom(iBd).getAtomicNum()==pElem)nAt++;
    }
    return nAt;
}


void getBiggestFragment(const MacroMole& pMole, std::vector<const MMAtom*>&liste)
{
    const size_t nAtm=pMole.numAtoms();
    bool passed[nAtm];


    for(size_t iAtm=0;iAtm< nAtm;++iAtm)    passed[iAtm]= !pMole.getAtom(iAtm).isSelected();



    do
    {
        // Finding next to do:
        size_t pos=0;
        for(;pos <= nAtm;++pos)
        {
            if (nAtm==pos)break;
            if (!passed[pos])break;
        }
        if (nAtm==pos)break;

        std::vector<const MMAtom*> nextlist,todo,willdo;
        const MMAtom& refAtm=pMole.getAtom(pos);
        nextlist.push_back(&refAtm);
        passed[pos]=true;
        // The first object to be process is itself :
        todo.push_back(&refAtm);



        // We scan the list of object to be process
        // until we have nothing
        while (!todo.empty())
        {
            // All object that will be process in the next round
            // will go here:
            willdo.clear();

            // Scanning list of object to be processed :
            for (size_t iTodo=0;iTodo< todo.size(); ++iTodo)
            {
                const MMAtom& dotTodo=*todo.at(iTodo);

                // Scanning list of dot of the looked dot
                // To get the next round :
                for(size_t iPotDot=0;
                    iPotDot < dotTodo.numDot();
                    ++iPotDot)
                {
                    MMAtom& atm=dotTodo.getAtom(iPotDot);
                    if (passed[atm.getMID()])continue;
                    willdo.push_back(&atm);
                    nextlist.push_back(&atm);
                    passed[atm.getMID()]=true;
                }
            }// end iTodo
            todo.clear();
            todo=willdo;
        }//end while
        if (nextlist.size() > liste.size())liste=nextlist;

    }while(1);

}




void removeAllHydrogen(MacroMole& mole)
{
    std::vector<MMAtom*> atoms;
    for(size_t i=0;i<mole.numAtoms();++i)
    {
        if (mole.getAtom(i).isHydrogen()) atoms.push_back(&mole.getAtom(i));
    }
    for(size_t i=0;i<atoms.size();++i) mole.delAtom(*atoms.at(i));
}





void mergeInSingleChain(MacroMole& mole)
{
    std::vector<MMChain*> list;
    const signed char nChain((const signed char) mole.numChains());
    for(signed char iChain=1;iChain <nChain;++iChain)
    {
        MMChain& ch=mole.getChain(iChain);list.push_back(&ch);
    }
    for(size_t iL=0;iL< list.size();++iL)
    {
        MMChain& ch=*list.at(iL);

        for(size_t iRes=0;iRes < ch.numResidue();++iRes)
        {

            mole.moveResidueToChain(ch.getResidue(iRes),mole.getChain(0));
        }
    }
}


double getMolecularWeigth(const MacroMole& pMole)
{
    double fWeigth=0;
    for(size_t i=0; i< pMole.numAtoms();++i)
    {
        const MMAtom& atom = pMole.getAtom(i);
        fWeigth+=atom.getWeigth();
    }
    return fWeigth;
}



std::string getFormula(const MacroMole& pMole)
{
    size_t NCount[NNATM];
    for(size_t i=0;i<NNATM;++i)NCount[i]=0;
    for(size_t i=0;i<pMole.numAtoms();++i)
    {
        const MMAtom& atom = pMole.getAtom(i);
        const int atmNum=atom.getAtomicNum();
        NCount[atmNum]++;
    }
    std::ostringstream formula;
    for(size_t i=0;i<NNATM;++i)
    {
        if (NCount[i]==0)continue;
        formula << Periodic[i].name<<NCount[i]<<" ";
    }
    return formula.str().substr(0,formula.str().length()-1);
}

void assignPhysProps(MacroMole& pMole)
{
    for(size_t iAt=0;iAt<pMole.numAtoms();++iAt)
    {
        MMAtom& pAtom=pMole.getAtom(iAt);
        try{
            PhysProp& props=pAtom.prop();
            const std::string& mMOL2type=pAtom.getMOL2();
            props.clear();
            if (pAtom.isHydrogen())continue;

            if (pAtom.isHalogen())props.addProperty(CHEMPROP::HALOGEN|CHEMPROP::HYDROPHOBIC);
            if (pAtom.isCarbon()) assignCarbonPhysProp(pAtom);
            else if (pAtom.getAtomicNum()==16)assignSulfurPhysProp(pAtom);
            else if (pAtom.isNitrogen())assignNitrogenPhysProp(pAtom);
            else if (pAtom.isOxygen()) assignOxygenPhysProp(pAtom);
            if (mMOL2type=="Ca"|| mMOL2type=="Cu"||
                    mMOL2type=="Fe"|| mMOL2type=="Mg"||
                    mMOL2type=="Mn"|| mMOL2type=="Zn"||pAtom.isMetallic())
            {
                props.addProperty(CHEMPROP::METAL);
            }


            if (pAtom.getResidue().getResType() == RESTYPE::STANDARD_AA
                    && (pAtom.getName()=="CA" || pAtom.getName()=="C"))
                props.clear();
        }catch(ProtExcept &e )
        {
            e.addHierarchy("MacroMoleUtils::assignPhysProp");
            e.addDescription(pAtom.getIdentifier());
            throw;
        }
    }


}///END assignPhysProp



void getDistanceMatrix(MacroMole& pMole,
                       protspace::UIntMatrix &matrix,
                       const std::vector<MMAtom*> & dotlist) throw(ProtExcept)
try
{

    // Setting up the matrix :
    matrix.resize(dotlist.size(),dotlist.size());


    unsigned int level=0;

    std::vector<const MMAtom*> todo,willdo;

    // Taking each dot as a reference:
    for (size_t iAtm=0;iAtm<dotlist.size();++iAtm)
    {

        const size_t & mRefpos=iAtm;

        // The first object to be process is itself :
        todo.push_back(dotlist.at(iAtm));

        level=0;

        // We scan the list of object to be process
        // until we have nothing
        while (!todo.empty())
        {
            // All object that will be process in the next round
            // will go here:
            willdo.clear();

            // Scanning list of object to be processed :
            for (size_t iTodo=0;iTodo< todo.size(); ++iTodo)
            {
                const MMAtom& dotTodo=*todo.at(iTodo);

                // Are they in the list of object to consider:
                const auto itP=std::find(dotlist.begin(),
                                         dotlist.end(),
                                         &dotTodo);
                if (itP == dotlist.end())continue;

                // Getting position:
                const size_t & compos=std::distance(dotlist.begin(),itP);

                // value != 0 when pair already process.
                // It avoid to come back on the same path:
                // Second condition is for the starter with itself
                // In order to avoid coming back to itself
                if (matrix.getVal(mRefpos,compos) != 0
                        ||(matrix.getVal(mRefpos,compos) ==0
                           && mRefpos==compos
                           && level!=0)
                        ){ continue;}

                matrix.setVal(mRefpos,compos,level);

                // Scanning list of dot of the looked dot
                // To get the next round :
                for(size_t iPotDot=0;
                    iPotDot < dotTodo.numDot();
                    ++iPotDot)
                {
                    // Since dotTodo.getDot() gives an id
                    // which is the position in the list of object of the owned
                    // molecule. when the molecule is the owner, its fine
                    // but otherwise, we have to call the parent object to
                    // get the correct dot.
                    if (pMole.isOwner())
                        willdo.push_back(&pMole.getAtom(dotTodo.getDot(iPotDot)));
                    else
                        willdo.push_back(&dotTodo.getParent().getDot(dotTodo.getDot(iPotDot)));
                }
            }// end iTodo
            level++;

            todo.clear();
            todo=willdo;
        }//end while



    }
}
catch(ProtExcept &e)
{
    e.addHierarchy("Group::getDistanceMatrix");
    throw;
}

void getDistanceMatrix(MacroMole& pMole, UIntMatrix &matrix)
try{
    const size_t nAtm=pMole.numAtoms();
    if (nAtm <=1) return;
    if (!pMole.isOwner()){getDistanceMatrix(pMole,matrix,pMole.getAtoms());return ;}
    // Setting up the matrix :
    matrix.resize(nAtm,nAtm);


    unsigned int level=0;
    std::vector<MMAtom*> todo,willdo;

    // Scanning each atom of the molecule :
    for (size_t iAtm=0;iAtm<nAtm;++iAtm)
    {
        MMAtom& atom= pMole.getAtom(iAtm);
        // Getting position:
        const size_t & mRefpos=iAtm;
        todo.push_back(&atom);
        level=0;

        while(!todo.empty())
        {
            willdo.clear();

            for (size_t iTodo=0;iTodo< todo.size(); ++iTodo)
            {
                const MMAtom& atmtodo=*todo.at(iTodo);
                // Getting position:
                const size_t & compos=atmtodo.getMID();
                if (matrix.getVal(atom.getMID(),atmtodo.getMID()) != 0
                        ||(matrix.getVal(mRefpos,compos) ==0 && mRefpos==compos && level!=0)
                        ){ continue;}

                matrix.setVal(mRefpos,compos,level);
                for (size_t iBd=0;iBd < atmtodo.numDot();++iBd)
                {
                    willdo.push_back(&pMole.getAtom(atmtodo.getDot(iBd)));
                }
            }

            todo.clear();todo=willdo;
            level++;
        }
        matrix.setVal(atom.getMID(),atom.getMID(),0);
    }
}catch(ProtExcept &e)
{
    assert(e.getId()!="030401");
    e.addHierarchy("getDistanceMatrix");
    throw;
}


void consistentSelection(protspace::MacroMole& mole)
{
    protspace::UIntMatrix matrix;
    std::vector<protspace::MMAtom*> list;
    for(size_t iA=0;iA< mole.numAtoms();++iA)
    {
        protspace::MMAtom& atom1=mole.getAtom(iA);
        if (atom1.isSelected())list.push_back(&atom1);
    }
    protspace::getDistanceMatrix(mole, matrix,list);
    bool correct=true;
    std::vector<std::vector<protspace::MMAtom*>> groups;
    for(size_t iA=0;iA< list.size();++iA)
    {
        protspace::MMAtom& atom1=*list.at(iA);
        if (!atom1.isSelected())continue;
        for(size_t iB=iA+1;iB< list.size();++iB)
        {
            protspace::MMAtom& atom2=*list.at(iB);
            if (!atom2.isSelected())continue;
            if (matrix.getVal(iA,iB)==0)correct=false;
            else
            {
                size_t pos1=groups.size(),pos2=groups.size();
                for(size_t iG=0;iG<groups.size();++iG)
                {
                    if (std::find(groups.at(iG).begin(),groups.at(iG).end(),&atom1)!=groups.at(iG).end())
                        pos1=iG;
                    if (std::find(groups.at(iG).begin(),groups.at(iG).end(),&atom2)!=groups.at(iG).end())
                        pos2=iG;
                }
                if (pos1==pos2 && pos1==groups.size()){std::vector<protspace::MMAtom*> gr({&atom1,&atom2});
                    groups.push_back(gr);
                }
                else if (pos1==groups.size())groups.at(pos2).push_back(&atom1);
                else if (pos2==groups.size())groups.at(pos1).push_back(&atom2);
            }
        }
    }
    if (correct)return;
    std::cout <<"GROUPS "<<groups.size()<<std::endl;

    protspace::getDistanceMatrix(mole, matrix);
    for(size_t iG=0;iG< groups.size();++iG)
        for(size_t jG=iG+1;jG<groups.size();++jG)
        {
            std::vector<protspace::MMAtom*> &gr1=groups.at(iG);
            std::vector<protspace::MMAtom*> &gr2=groups.at(jG);
            unsigned int maxD=1000;protspace::MMAtom* bat1=nullptr,*bat2=nullptr;
            for(protspace::MMAtom* at1:gr1)
                for(protspace::MMAtom* at2:gr2)
                {
                    const unsigned int& pos=matrix.getVal(at1->getMID(),at2->getMID());
                    if (pos>maxD)continue;
                    maxD=pos;bat1=at1;bat2=at2;
                }
            std::cout <<maxD<<" " <<bat1->getIdentifier()<<"\t"<<bat2->getIdentifier()<<"\n";
            std::vector<protspace::MMAtom*> goodPath;
            if (!protspace::findShortestPath(*bat1,*bat2,goodPath))
                throw_line("XX","","Unable to find shortest path");
            std::vector<protspace::MMRing*> list;
            for(protspace::MMAtom* atm:goodPath)
            {
                atm->select(true);
                if (!mole.isAtomInRing(*atm))continue;
                mole.getRingsFromAtom(*atm,list);
                for(protspace::MMRing* ring:list)ring->setUse(true);
            }
        }

}

void applyAlignment(const RigidAlign& pAligner,protspace::MacroMole& pMole)
{
    std::vector<protspace::Coords*> list;
    for(size_t iA=0;iA<pMole.numAtoms();++iA)list.push_back(&pMole.getAtom(iA).pos());
    pAligner.mobilToRef(list);

}

size_t numHeavyAtoms(const protspace::MacroMole& pMole, const bool& pOnlyUsed)
{
    size_t n=0;
    for(size_t i=0;i<pMole.numAtoms();++i)
    {
       const protspace::MMAtom& atm(pMole.getAtom(i));
       if (pOnlyUsed && !atm.isSelected())continue;
       if (atm.getAtomicNum()>1)++n;
    }
    return n;
}

}
