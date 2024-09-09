#include <math.h>
#include <memory>
#include "headers/math/grid_utils.h"
#include "headers/math/grid.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/molecule/macromole.h"
#include "headers/math/coords_utils.h"
#include "headers/parser/writerMOL2.h"
namespace protspace
{

double getOverlap(const Grid& pGrid,
                  const MacroMole& pMole1,
                  const MacroMole& pMole2,
                  const metric &pMetric,
                  const std::string& pFile,
                  const bool& pOnlyUsed)
{
    double nM1=0, nM2=0, nOver=0;
    const size_t nBox(pGrid.getNumBoxes());
    size_t nAtm=0;
    bool hasM1=false,hasM2=false;
    const bool wFile=!pFile.empty();

    protspace::MacroMole* mole=nullptr;
    if (wFile)mole=new protspace::MacroMole;
    std::unique_ptr<MacroMole> free_results(mole);
    for(size_t iB=0;iB<nBox;++iB)
    {
        const Box& bx=pGrid.getBox(iB);
        nAtm=bx.numAtom();
        hasM1=false;
        hasM2=false;
        for(size_t iA=0;iA<nAtm;++iA)
        {
            const MMAtom& pAtom=bx.getAtom(iA);
            if (pAtom.isHydrogen())continue;
            if (!pAtom.isSelected() && pOnlyUsed)continue;
            if (&pAtom.getParent()==&pMole1)hasM1=true;
            if (&pAtom.getParent()==&pMole2)hasM2=true;
        }
        nAtm=bx.numCloseAtom();
        for(size_t iA=0;iA<nAtm;++iA)
        {
            const MMAtom& pAtom=bx.getCloseAtom(iA);
            if (pAtom.isHydrogen())continue;
            if (!pAtom.isSelected() && pOnlyUsed)continue;
            if (&pAtom.getParent()==&pMole1)hasM1=true;
            if (&pAtom.getParent()==&pMole2)hasM2=true;
        }
        if (wFile)
        {
            if (hasM1 && !hasM2)
                mole->addAtom(mole->getTempResidue(),bx.getOrigPos(),"C"+std::to_string(iB),"C.3","C");
            else if (!hasM1 && hasM2)
                mole->addAtom(mole->getTempResidue(),bx.getOrigPos(),"N"+std::to_string(iB),"N.3","N");
            else if (hasM1 && hasM2)
                mole->addAtom(mole->getTempResidue(),bx.getOrigPos(),"F"+std::to_string(iB),"F","F");
        }
        if (hasM1)nM1++;
        if (hasM2)nM2++;
        if (hasM1&&hasM2)nOver++;
    }
    if (wFile)
    {
        protspace::WriteMOL2 wm(pFile);
        wm.save(*mole);

    }
    switch(pMetric)
    {
    case METRIC::TANIMOTO: return (nM1+nM2-nOver==0)?0.0:(nOver/(nM1+nM2-nOver));break;
    case METRIC::RTVERSKY: return (0.95*nM1+nM2-nOver==0)?0.0:(nOver/(0.95*nM1+nM2-nOver));break;
    }

    return 0.0;
}



void getAtomClose(std::vector<MMAtom *> &list,
                  const MMAtom& atom,
                  const double& thres,
                  const Grid& grid,
                  const bool& isGridConsidered)

try{
    const Box* tmpB=nullptr;
    if (isGridConsidered)tmpB=&grid.getPrepBox(atom);
    else
    {
        int pos;
        if (!grid.findBox(atom,pos))return;
        tmpB=&grid.getBox(pos);
    }
    std::vector<const Box*> listBox;
    getCubeAround(*tmpB,thres,grid,listBox);
    sort(listBox.begin(),listBox.end());
    listBox.erase(std::unique(listBox.begin(),listBox.end()),listBox.end());
    for(const Box* bxx:listBox)
    {
        const Box& box2= *bxx;
        for(size_t p=0;p<box2.numAtom();++p)
        {
            MMAtom& atom2= box2.getAtom(p);

            if (atom2.pos().distance(atom.pos())>thres)continue;
            if (std::find(list.begin(),list.end(),&atom2)!=list.end())continue;
            list.push_back(&atom2);
        }
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="220801");///Box must exists
    e.addHierarchy("getAtomClose");
    e.addDescription("Atom involved : "+atom.getIdentifier());
    throw;
}




void getResidueClose(std::vector<MMResidue *> &list,
                     const MMResidue& res,
                     const double& thres,
                     const Grid& grid,
                     const bool& higherMID,
                     bool isInGrid,
                     bool wDiffMole)
{
    std::vector<const Box*> listBox,listBoxTest;
    for(size_t i=0;i<res.numAtoms();++i)
    {
        MMAtom& atom=res.getAtom(i);
        if (atom.isHydrogen())continue;
        const Box* tmpB=nullptr;
        if (isInGrid)
        {tmpB=&grid.getPrepBox(atom);
        }
        else
        {
            int pos;
            if (!grid.findBox(atom,pos))continue;
            tmpB=&grid.getBox(pos);
        }
        listBox.push_back(tmpB);
    }

    sort(listBox.begin(),listBox.end());
    listBox.erase(std::unique(listBox.begin(),listBox.end()),listBox.end());
    std::vector<int> doneRes;

    for(size_t i=0;i< listBox.size();++i)
    {
        const Box& box=*listBox.at(i);
        getCubeAround(box,thres,grid,listBoxTest);
    }
    std::sort(listBoxTest.begin(),listBoxTest.end());
    listBoxTest.erase(std::unique(listBoxTest.begin(),listBoxTest.end()),listBoxTest.end());

    //    cout <<"RES: "<< res.getIdentifier()<<endl;
    for(const Box* bxx:listBoxTest)
    {
        const Box& box2= *bxx;
        for(size_t p=0;p<box2.numAtom();++p)
        {
            MMResidue& res2= box2.getAtom(p).getResidue();
            if (wDiffMole && &res2.getParent() == &res.getParent())continue;
            if (higherMID && res2.getMID() < res.getMID())continue;
            if (std::find(doneRes.begin(),doneRes.end(),res2.getMID())!=doneRes.end())continue;
            doneRes.push_back(res2.getMID());
            if (getShortestDistance(res,res2,thres)>thres)continue;

            list.push_back(&res2);
        }
    }

    std::sort(list.begin(),list.end());
    list.erase(std::unique(list.begin(),list.end()),list.end());



}

void getCubeAround(const Box& box,
                   const double& pThres,
                   const Grid& grid,
                   std::vector<const Box*>& pListBox)
{
    const int cubeN=(int)ceil(pThres/grid.getBoxLength());
    const int boxX=(int)box.getGridPos().x();
    const int boxY=(int)box.getGridPos().y();
    const int boxZ=(int)box.getGridPos().z();

    const int& mNumPtRangeI=grid.getRangeI();
    const int& mNumPtRangeJ=grid.getRangeJ();
    const int& mNumPtRangeK=grid.getRangeK();
    const int& mMaxCubeNum = grid.getNumBoxes();


    int n=0;
    for(int i=boxX-cubeN;i<=boxX+cubeN;++i)
        for(int j=boxY-cubeN;j<=boxY+cubeN;++j)
            for(int k=boxZ-cubeN;k<=boxZ+cubeN;++k)
            {
                if (i<0 || i >=mNumPtRangeI)continue;
                if (j<0 || j >=mNumPtRangeJ)continue;
                if (k<0 || k >=mNumPtRangeK)continue;
                n=0;
                if (abs(i-boxX)>=cubeN)n++;
                if (abs(j-boxY)>=cubeN)n++;
                if (abs(k-boxZ)>=cubeN)n++;
                //  cout << cubeN<< " " << n<<endl;
                if (n>=cubeN)continue;
                const int boxpos=i+
                        j*mNumPtRangeI+
                        k*mNumPtRangeI*mNumPtRangeJ;
                if (boxpos>=mMaxCubeNum)continue;
                pListBox.push_back(&grid.getBox(boxpos));
            }
}

void getAtomClose(std::vector<MMAtom *> &list,
                  const MMResidue& res,
                  const double& thres,
                  const Grid& grid,
                  const bool& isGridConsidered)
{
    try{

        const Box* tmpB=nullptr;
        std::vector<const Box*> listBoxes;
        for(size_t iAtm=0;iAtm < res.numAtoms();++iAtm)
        {
            MMAtom& atom= res.getAtom(iAtm);
            if (atom.isHydrogen())continue;
            tmpB=nullptr;
            if (isGridConsidered)tmpB=&grid.getPrepBox(atom);
            else
            {
                int pos;
                if (!grid.findBox(atom,pos))continue;
                tmpB=&grid.getBox(pos);
            }
            getCubeAround(*tmpB,thres,grid,listBoxes);
        }
        std::sort(listBoxes.begin(),listBoxes.end());
        listBoxes.erase(std::unique(listBoxes.begin(),listBoxes.end()),listBoxes.end());
        for(size_t iAtm=0;iAtm < res.numAtoms();++iAtm)
        {
            MMAtom& atom= res.getAtom(iAtm);
            if (atom.isHydrogen())continue;
            for(size_t iBx=0;iBx < listBoxes.size();++iBx)
            {
                const Box& box2= *listBoxes.at(iBx);
                for(size_t p=0;p<box2.numAtom();++p)
                {
                    MMAtom& atom2= box2.getAtom(p);

                    if (atom2.dist(atom)>thres)continue;
                    if (std::find(list.begin(),list.end(),&atom2)!=list.end())continue;
                    list.push_back(&atom2);
                }
            }
        }

    }catch(ProtExcept &e)
    {
        e.addHierarchy("getAtomClose");
        e.addDescription("Residue involved : "+res.getIdentifier());
        throw;
    }
}



void getAtomClose(std::vector<MMAtom *> &list,
                  const MMRing& pRing,
                  const double& thres,
                  const Grid& grid,
                  const bool& isGridConsidered)
{
    for(size_t iAtm=0;iAtm<pRing.numAtoms();++iAtm)
    {
        getAtomClose(list,pRing.getAtom(iAtm),thres,grid,isGridConsidered);
    }
    std::sort(list.begin(),list.end());
    list.erase(std::unique(list.begin(),list.end()),list.end());
}






double getSAS(protspace::MacroMole& mole,
              const double& n_obj,
              const bool& onlyUsed,
              const double& gr_step,
              const double& gr_margin,
              const int& step,
              const bool& wWaterS,
              const std::string& pPath
              )
{
    try{

        protspace::MacroMole spheres;
        std::vector<protspace::Coords> liste;
        distributes(liste,n_obj);
        protspace::Coords posit;
        protspace::Grid grid(gr_step,gr_margin);
        grid.considerMolecule(mole);
        grid.createGrid(true);
        grid.perceiveAdjacentBox(1);
        //const int step=2;
        double mol_surface=0;
        double atm_radius=0;
        for(size_t na=0;na< mole.numAtoms();++na)
        {
            //        std::cout <<na<<"/"<<mole.numAtoms()<<std::endl;
            protspace::MMAtom& atom=mole.getAtom(na);
            if (onlyUsed && !atom.isSelected())continue;
            const uint16_t& resT=atom.getResidue().getResType();
            if (resT!=RESTYPE::STANDARD_AA && resT!=RESTYPE::MODIFIED_AA)continue;
            atm_radius=atom.getvdWRadius()+((wWaterS)?1.4:0);
            const double sphere_area(atm_radius*atm_radius*4*M_PI);
            const double point_area(sphere_area/n_obj);
            //        std::cout <<"\n\n\n######\n\n\n"<<mole.getAtom(na).getIdentifier()<<std::endl;///(28.4137,0.006,-0.0887812)
            for (protspace::Coords& coo:liste)
            {
                //            std::cout <<"START"<<std::endl;
                posit=coo*atm_radius+
                        atom.pos();
                bool ok=true;
                //                std::cout <<posit<<std::endl;
                const int posBox(grid.getBoxPos(posit));
                //                std::cout <<posBox<<"\t"<<grid.getNumBoxes()<<std::endl;

                protspace::Box& bx_ini(grid.getBox(posBox));

                const int i_b=(int)bx_ini.getGridPos().x();
                const int j_b=(int)bx_ini.getGridPos().y();
                const int k_b=(int)bx_ini.getGridPos().z();

                for(int i=i_b-step;i<=i_b+step;++i){
                    if (i>= grid.getRangeI()|| i<0)continue;

                    for(int j=j_b-step;j<=j_b+step;++j){
                        if (j>= grid.getRangeJ()|| j<0)continue;

                        for(int k=k_b-step;k<=k_b+step;++k){
                            {
                                if (k>= grid.getRangeK()|| k<0)continue;
                                int id_test=i+j*grid.getRangeI()+k*grid.getRangeI()*grid.getRangeJ();
                                if (id_test> grid.getNumBoxes()|| id_test<0)continue;
                                protspace::Box& bx_test=grid.getBox(id_test);
                                //                                std::cout <<"IN"<<std::endl;
                                for(size_t iAB=0;iAB<bx_test.numAtom();++iAB)
                                {
                                    protspace::MMAtom& atmAB=bx_test.getAtom(iAB);
                                    if (onlyUsed && !atmAB.isSelected())continue;
                                    const uint16_t& resT2=atmAB.getResidue().getResType();
                                    if (resT2!=RESTYPE::STANDARD_AA && resT2!=RESTYPE::MODIFIED_AA)continue;
                                    if (&atmAB== &atom)continue;
                                    if (atmAB.pos().distance(posit)<atmAB.getvdWRadius()+((wWaterS)?1.4:0)){ok=false;break;}
                                }
                                if (!ok)break;
                            }if (!ok)break;}if (!ok)break;}if (!ok)break;}

                if (!ok)continue;
                mol_surface+=point_area;
                if (!pPath.empty())
                    spheres.addAtom(spheres.getTempResidue(),
                                    posit,
                                    mole.getAtom(na).getName(),mole.getAtom(na).getMOL2());

            }
            //        break;
        }

        if (!pPath.empty())
        {
            protspace::WriteMOL2 mw(pPath);
            mw.save(spheres);
        }

        return mol_surface;
    }catch(ProtExcept &e)
    {
        std::cerr<<e.toString()<<std::endl;
    }

}
}///END NAMESPACE
