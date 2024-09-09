#include <sstream>
#include <math.h>
#include "headers/math/grid.h"
#include "headers/statics/protpool.h"
#include "headers/molecule/macromole_utils.h"
#include "headers/parser/writerMOL2.h"
#include "headers/statics/intertypes.h"
#include "headers/math/coords_utils.h"
protspace::Grid::Grid(const double& box_length, const double& margin):
    mListCooPos(8,0),
    mListBox(1000,false),
    mListMolecule(10,false),
    mBoxLength(box_length),
    mMargin(margin),
    mRotMatrix(3,3,0),
    mScale(1),
    mLim{0.5,sqrt(3)*0.5},
    mVect{
        protspace::ProtPool::coord.acquireObject(mListCooPos.at(0)),
        protspace::ProtPool::coord.acquireObject(mListCooPos.at(1)),
        protspace::ProtPool::coord.acquireObject(mListCooPos.at(2)),
        },
    mCenter(protspace::ProtPool::coord.acquireObject(mListCooPos.at(3))),
    mGrid_size(protspace::ProtPool::coord.acquireObject(mListCooPos.at(4))),
    mStart_pos(protspace::ProtPool::coord.acquireObject(mListCooPos.at(5))),
    mExtremePos{protspace::ProtPool::coord.acquireObject(mListCooPos.at(6)),
                protspace::ProtPool::coord.acquireObject(mListCooPos.at(7))},
    mNumPtRange{0,0,0},
    mMaxCubeNumber(0),
    mMoleLock(false),
    mWHydrogen(true)
{
    clear();

}

void protspace::Grid::clear()
{
    mListMolecule.clear();
    for(size_t i=0;i<3;++i){
        mNumPtRange[i]=0;
        for(size_t j=0;j<3;++j)mRotMatrix.setVal(0,0,0);
    }
    mScale=1;

    mVect[0].clear();
    mVect[1].clear();
    mVect[2].clear();
    mCenter.clear();
    mGrid_size.clear();
    mStart_pos.clear();
    mExtremePos[0].clear();
    mExtremePos[1].clear();
    mMaxCubeNumber=0;
    mMoleLock=false;

    for(const auto& id:mListBoxPos)
        protspace::ProtPool::Instance().box.releaseObject(id);
    mListBox.clear();


    mListBoxPos.clear();
}


protspace::Grid::~Grid()
{
    for(const auto& id:mListBoxPos)
        protspace::ProtPool::Instance().box.releaseObject(id);
    for(const auto& id:mListCooPos)
        protspace::ProtPool::Instance().coord.releaseObject(id);
}







void protspace::Grid::considerMolecule(protspace::MacroMole& mole)
try{
    if (mMoleLock) throw_line("220101",
                              "Grid::considerMolecule",
                              "Cannot add more molecules, grid already been created");

    if (!mListMolecule.isIn(mole))mListMolecule.add(mole);
}catch(ProtExcept &e)
{
    e.addHierarchy("Grid::ConsiderMolecule");
    throw;
}








void protspace::Grid::createGrid(const bool& only_used)

try{
    mMoleLock=true;

    if (!checkAtomForGrid(only_used))
        throw_line("220201",
                   "Grid::createGrid",
                   "No heavy atom to consider for the grid");


    calcUnitVector(only_used);
    calcMatrixFromVector(mVect[0],mVect[1],mVect[2],mRotMatrix);
    findDelination(only_used);
    generateCubes();
    applyRotation(only_used);


}catch(ProtExcept &e)
{
    e.addHierarchy("Grid::createGrid");
    throw;
}







bool protspace::Grid::checkAtomForGrid(const bool& only_used)
{
    const size_t nMole(mListMolecule.size());
    bool check=false;
    size_t maxAt=0;
    for(size_t iMole=0;iMole<nMole;++iMole)
    {
        const MacroMole& molecule = mListMolecule.get(iMole);
        const size_t nAtom(molecule.numAtoms());
        if (nAtom > maxAt)maxAt=nAtom;
        if (check)continue;
        for (size_t iAtm=0; iAtm<nAtom;++iAtm)
        {
            const MMAtom& atom=molecule.getAtom(iAtm);
            if (!mWHydrogen && atom.isHydrogen()) continue;
            if (only_used && ! atom.isSelected()) continue;
            check=true;
        }
    }
    mAtomBox.resize(nMole,maxAt,-1);

    return check;
}






void protspace::Grid::generateVectors(const Coords& pt2,
                                      const Coords& pt3)
{
    for(size_t i=0;i<2;++i)mVect[i].clear();
    size_t posPoolX,posPoolY, posPoolZ;
    ObjectPool<Coords>& pool=protspace::ProtPool::Instance().coord;
    Coords& x_axis=pool.acquireObject(posPoolX);x_axis.clear();
    Coords& y_axis=pool.acquireObject(posPoolY);y_axis.clear();
    Coords& z_axis=pool.acquireObject(posPoolZ);z_axis.clear();


    ///
    /// \brief Normal vector between vector (massmCenter,mCenter) and (residuMassmCenter,mCenter)
    ///
    x_axis=(mCenter.getNormal(pt2,pt3));
    ///
    /// \brief Normal vector between vector (x_axis,mCenter) and (massmCenter,mCenter)
    ///
    y_axis=(mCenter.getNormal(pt2,x_axis));

    ///
    /// \brief Normal vector between vector (x_axis,mCenter) and (y_axis,mCenter)
    ///
    z_axis=(mCenter.getNormal(x_axis,y_axis));


    // Setting up unit vector that will be the new orthormal basis
    mVect[0].setxyz(x_axis-mCenter);
    mVect[1].setxyz(y_axis-mCenter);
    mVect[2].setxyz(z_axis-mCenter);

    pool.releaseObject(posPoolX);
    pool.releaseObject(posPoolY);
    pool.releaseObject(posPoolZ);
}






void protspace::Grid::calcUnitVector(const bool& only_used) throw(ProtExcept)
try{
    mCenter.clear();

    size_t posPoolM,posPoolRM;
    ObjectPool<Coords>& pool=protspace::ProtPool::Instance().coord;
    /// \brief mCenter of mass of the molecules
    Coords& massCenter=pool.acquireObject(posPoolM);massCenter.clear();
    /// \brief BaryCenter of the residues
    Coords& residuMassCenter=pool.acquireObject(posPoolRM);residuMassCenter.clear();

    /// START BLOCK GEOMETRIC mCenter
    getMoleculesData(mListMolecule,
                     mCenter,
                     massCenter,
                     residuMassCenter,
                     mWHydrogen,
                     only_used);


    if (massCenter ==mCenter)
    {
        pool.releaseObject(posPoolM);
        pool.releaseObject(posPoolRM);
        throw_line("220301",
                   "Grid::createGrid",
                   "Geometric center is the same as the barycenter");
    }

    if (massCenter.distance(mCenter)<1) massCenter+=1;
    if (residuMassCenter.distance(mCenter)<1)residuMassCenter-=1;

    generateVectors(massCenter,residuMassCenter);

    pool.releaseObject(posPoolM);
    pool.releaseObject(posPoolRM);
}catch(ProtExcept &e)
{
    assert(e.getId()!="060101");///Coord canot be in use
    assert(e.getId()!="060301"&& e.getId()!="060302");///Release should be working
    e.addDescription("Grid::calcUniVector");
    throw;
}





void protspace::Grid::saveGridBox(const std::string& pFile)
{
    if(pFile.empty())return;

    try{
        MacroMole molecule("BOX");
        MMResidue& residue=molecule.addResidue("ALA","A",1);
        const Coords X7=(mCenter-mVect[0]*mExtremePos[0][0]-mVect[1]*mExtremePos[0][1]+mVect[2]*mExtremePos[1][2]);
        const Coords X6=(mCenter-mVect[0]*mExtremePos[0][0]+mVect[1]*mExtremePos[1][1]-mVect[2]*mExtremePos[0][2]);
        const Coords X5=(mCenter-mVect[0]*mExtremePos[0][0]+mVect[1]*mExtremePos[1][1]+mVect[2]*mExtremePos[1][2]);
        const Coords X4=(mCenter+mVect[0]*mExtremePos[1][0]-mVect[1]*mExtremePos[0][1]-mVect[2]*mExtremePos[0][2]);
        const Coords X3=(mCenter+mVect[0]*mExtremePos[1][0]-mVect[1]*mExtremePos[0][1]+mVect[2]*mExtremePos[1][2]);
        const Coords X2=(mCenter+mVect[0]*mExtremePos[1][0]+mVect[1]*mExtremePos[1][1]-mVect[2]*mExtremePos[0][2]);
        const Coords X1=(mCenter+mVect[0]*mExtremePos[1][0]+mVect[1]*mExtremePos[1][1]+mVect[2]*mExtremePos[1][2]);
        const Coords x_axis(mVect[0]+mCenter);
        const Coords y_axis(mVect[1]+mCenter);
        const Coords z_axis(mVect[2]+mCenter);
        MMAtom &aX8=molecule.addAtom(residue,mStart_pos,"Cl","Cl");
        MMAtom &aX7=molecule.addAtom(residue,X7,"C","C.3");
        MMAtom &aX6=molecule.addAtom(residue,X6,"C","C.3");
        MMAtom &aX5=molecule.addAtom(residue,X5,"C","C.3");
        MMAtom &aX4=molecule.addAtom(residue,X4,"C","C.3");
        MMAtom &aX3=molecule.addAtom(residue,X3,"C","C.3");
        MMAtom &aX2=molecule.addAtom(residue,X2,"C","C.3");
        MMAtom &aX1=molecule.addAtom(residue,X1,"C","C.3");
        MMAtom &cent=molecule.addAtom(residue,mCenter,"O","O.3");
        MMAtom &aI=molecule.addAtom(residue,x_axis,"P","P.3");
        MMAtom &aJ=molecule.addAtom(residue,y_axis,"F","F");
        MMAtom &aK=molecule.addAtom(residue,z_axis,"I","I");
        const uint16_t bondspec=BOND::SINGLE;
        molecule.addBond(cent,aI,bondspec,1);
        molecule.addBond(cent,aJ,bondspec,1);
        molecule.addBond(cent,aK,bondspec,1);
        molecule.addBond(aX8,aX7,bondspec,1);
        molecule.addBond(aX8,aX4,bondspec,1);
        molecule.addBond(aX8,aX6,bondspec,1);
        molecule.addBond(aX4,aX3,bondspec,1);
        molecule.addBond(aX4,aX2,bondspec,1);
        molecule.addBond(aX2,aX1,bondspec,1);
        molecule.addBond(aX2,aX6,bondspec,1);
        molecule.addBond(aX6,aX5,bondspec,1);
        molecule.addBond(aX7,aX3,bondspec,1);
        molecule.addBond(aX7,aX3,bondspec,1);
        molecule.addBond(aX3,aX1,bondspec,1);
        molecule.addBond(aX1,aX5,bondspec,1);
        molecule.addBond(aX5,aX7,bondspec,1);
        WriteMOL2 mwrite(pFile);
        mwrite.save(molecule);
    }
    catch(ProtExcept &e)
    {
        std::cerr << "Unable to output box"<<std::endl;
        std::cerr << e.toString()<<std::endl;
    }

}




void protspace::Grid::findDelination(const bool &only_used)
{
    mExtremePos[0].setxyz(10000,10000,10000);
    mExtremePos[1].setxyz(-10000,-10000,-10000);
    Coords& min(mExtremePos[0]);
    Coords& max(mExtremePos[1]);


    for(size_t iMole=0;iMole<mListMolecule.size();++iMole)
    {
        MacroMole& molecule = mListMolecule.get(iMole);
        for (size_t iAtm=0; iAtm<molecule.numAtoms();++iAtm)
        {
            MMAtom& atom=molecule.getAtom(iAtm);
            if (!mWHydrogen && atom.isHydrogen()) continue;
            if (only_used && ! atom.isSelected()) continue;

            Coords rotpos(atom.pos()-mCenter);
            rotate(rotpos,mRotMatrix);
            if(rotpos.x() > max[0]) max[0]=rotpos.x();
            if(rotpos.x() < min[0]) min[0]=rotpos.x();
            if(rotpos.y() > max[1]) max[1]=rotpos.y();
            if(rotpos.y() < min[1]) min[1]=rotpos.y();
            if(rotpos.z() > max[2]) max[2]=rotpos.z();
            if(rotpos.z() < min[2]) min[2]=rotpos.z();
        }
    }

    max[0]+= mMargin;if (min[0] < 0) min[0]=mMargin-min[0] ; else min[0]=min[0]-mMargin;
    max[1]+= mMargin;if (min[1] < 0) min[1]=mMargin-min[1] ; else min[1]=min[1]-mMargin;
    max[2]+= mMargin;if (min[2] < 0) min[2]=mMargin-min[2] ; else min[2]=min[2]-mMargin;

    mGrid_size.setxyz(min[0]+max[0], min[1]+max[1], min[2]+max[2]);
    mStart_pos = mCenter-mVect[0]*min[0]-mVect[1]*min[1]-mVect[2]*min[2];
    mNumPtRange[0]=static_cast<int>(ceil(mGrid_size.x()/mBoxLength))+1;
    mNumPtRange[1]=static_cast<int>(ceil(mGrid_size.y()/mBoxLength))+1;
    mNumPtRange[2]=static_cast<int>(ceil(mGrid_size.z()/mBoxLength))+1;

    mMaxCubeNumber=mNumPtRange[0]*mNumPtRange[1]*mNumPtRange[2];
}



void protspace::Grid::generateCubes() throw(ProtExcept)
{


    mListBox.reserve(mMaxCubeNumber);

    const int maxMargeK = mNumPtRange[2]-mMargin;
    const int maxMargeJ = mNumPtRange[1]-mMargin;
    const int maxMargeI = mNumPtRange[0]-mMargin;
    Box* bx=nullptr;
    size_t posBx=0;
    ObjectPool<Box>& pool=protspace::ProtPool::Instance().box;
    pool.preRequest(mMaxCubeNumber);
    ObjectPool<Coords>& poolCoo=protspace::ProtPool::Instance().coord;
    poolCoo.preRequest(mMaxCubeNumber);
    bool ismMargin=false;
    try
    {
        for (int k=0; k < mNumPtRange[2]; ++k)
            for (int j=0; j < mNumPtRange[1]; ++j)
                for (int i=0; i < mNumPtRange[0]; ++i)
                {
                    ismMargin=false;
                    const int id=i+j*mNumPtRange[0]+k*mNumPtRange[0]*mNumPtRange[1];
                    if ((k <= mMargin || k > maxMargeK)
                            ||(j <= mMargin || j >= maxMargeJ)
                            ||(i <= mMargin || i >= maxMargeI))ismMargin=true;
                    bx=&pool.acquireObject(posBx);
                    bx->clear();
                    bx->mParent=this;
                    bx->mGridpos.setxyz(i,j,k);

                    bx->mId=id;
                    bx->mIsMargin=ismMargin;

                    mListBoxPos.push_back(posBx);
                    mListBox.add(bx);
                }
    }
    catch(std::bad_alloc &e)
    {

        std::ostringstream oss;
        oss  << "Bad allocation append \n"
             << e.what()<<"\n"

             << "### GRID INFORMATION ###\n"
             << "Expected number of boxes : "<< mMaxCubeNumber<<"\n"
             << "Number of boxes allocated :"<< mListBox.size()<<"\n"
             << "Grid size  :" << mGrid_size <<"\n"
             << "Box length :" << mBoxLength<<"\n"
             << "Try increasing box length\n";
        mListBox.clear();
        throw_line("220401",
                   "Grid::generateCubes",
                   oss.str());
    }
}



void protspace::Grid::getRotCoords(const Coords& pos_ini, Coords& rot_pos)const
{
    size_t pos;
    Coords& coord=protspace::ProtPool::coord.acquireObject(pos);
    coord=(pos_ini-mStart_pos)/mBoxLength;
    /// Getting coordinates of the atom in the new axis
    rot_pos.setxyz(
                mVect[0].x()*coord.x()+mVect[0].y()*coord.y()+mVect[0].z()*coord.z(),
            mVect[1].x()*coord.x()+mVect[1].y()*coord.y()+mVect[1].z()*coord.z(),
            mVect[2].x()*coord.x()+mVect[2].y()*coord.y()+mVect[2].z()*coord.z()
            );
    protspace::ProtPool::coord.releaseObject(pos);
}

int protspace::Grid::getBoxPos(const Coords& pos_ini)const
{
    CoordPoolObj rotpos;
    getRotCoords(pos_ini,rotpos.obj);
    if (rotpos.obj.x()<0|| rotpos.obj.x()>=mNumPtRange[0])return -1;
    if (rotpos.obj.y()<0|| rotpos.obj.y()>=mNumPtRange[1])return -1;
    if (rotpos.obj.z()<0|| rotpos.obj.z()>=mNumPtRange[2])return -1;
//    std::cout <<(int)(rotpos.obj.x())<<"/"<<mNumPtRange[0]<<"\t"<<
//                (int)(rotpos.obj.y())<<"/"<<mNumPtRange[1]<<"\t"<<
//                (int)(rotpos.obj.z())<<"/"<<mNumPtRange[2]<<"\n";
    const int value=  (int)(rotpos.obj.x())+
            (int)(rotpos.obj.y())*mNumPtRange[0]+
            (int)(rotpos.obj.z())*mNumPtRange[0]*mNumPtRange[1];
    return value;
}

int protspace::Grid::getBoxFromRotPos(const Coords& rotpos)const
{

    return (int)(rotpos.x())+
            (int)(rotpos.y())*mNumPtRange[0]+
            (int)(rotpos.z())*mNumPtRange[0]*mNumPtRange[1];

}

bool protspace::Grid::findBox(const MMAtom& pAtom, int& posBox)const
{
    int boxpos;
    CoordPoolObj rotpos;
    getRotCoords(pAtom.pos(),rotpos.obj);
    boxpos=getBoxFromRotPos(rotpos.obj);

    if (boxpos >= mMaxCubeNumber ||boxpos<0 )    return false;


    Box& box= mListBox.get(boxpos);
    Box* selBox=isCoordInCube(rotpos.obj,box);
    if (selBox==nullptr)    return false;
    posBox= selBox->getMId();
    return true;
}

protspace::Box& protspace::Grid::findBox(const MMAtom& pAtom)const
{
    int pos;
   if (!findBox(pAtom,pos))
       throw_line("220501","Grid::findBox",
                  "No box found for this atom");

    return mListBox.get(pos);
}


void protspace::Grid::regenBoxAtomAssign(const bool &onlyUsed)
{
    mAtomBox.setVal(-1);
    for(size_t iBx=0;iBx<mListBox.size();++iBx)
    {
        mListBox.get(iBx).mIncludeAtom.clear();
        mListBox.get(iBx).mCloseAtom.clear();
    }
    applyRotation(onlyUsed);
}

void protspace::Grid::applyRotation(const bool& onlyUsed)
{
    const size_t nMole(mListMolecule.size());
    const size_t nBoxSize(mListBox.size());
    for(size_t iMole=0;iMole<nMole;++iMole)
    {
        MacroMole& molecule = mListMolecule.get(iMole);
        const size_t nAtm(molecule.numAtoms());
        for (size_t iAtm=0; iAtm <nAtm;++iAtm)
        {
            MMAtom& atom = molecule.getAtom(iAtm);
            if (onlyUsed&& !atom.isSelected())continue;
            try{
                Box& bx = findBox(atom);
                bx.mIncludeAtom.add(atom);
                mAtomBox.setVal(iMole,iAtm,bx.getMId());

            }catch(ProtExcept)
            {
                /// Box hasn't been found for the atom
                /// It is probably an issue
                /// So we scan all box to ensure we are not missing it.

                bool found=false;
                for(size_t iBox=0;iBox<nBoxSize;++iBox)
                {
                    Box& boxl =mListBox.get(iBox);
                    Box* bx2=isAtomInCube(atom,boxl);
                    if (bx2 ==NULL) continue;

                    bx2->mIncludeAtom.add(atom);
                    mAtomBox.setVal(iMole,iAtm,bx2->getMId());

                    found=true;
                    break;
                }

                if (found)continue;


                throw_line("220601",
                           "Grid::applyRotation",
                           "Unable to find box for atom "+atom.getIdentifier()+atom.getMolecule().getName());
            }
        }
    }
}


void protspace::Grid::perceiveAdjacentBox(const double &ratio)
{
    const size_t nMole(mListMolecule.size());
    int step=0;int id_test;
    Coords rot_pos;
    for(size_t iMole=0;iMole < nMole;++iMole)
    {
        protspace::MacroMole& pMole = mListMolecule.get(iMole);
        const size_t nAtm = pMole.numAtoms();

        for(size_t iAtm=0;iAtm < nAtm;++iAtm)
        {
            MMAtom& atom = pMole.getAtom(iAtm);
            const double& radius=atom.getvdWRadius();
           if (!mWHydrogen && atom.isHydrogen())continue;
            step=(int)ceil(radius/mBoxLength);
            // step=1;
//            std::cout << atom.getIdentifier()<<"\t"<<radius<<"\t"<<step<<std::endl;
            const int& box_id = mAtomBox.getVal(iMole,iAtm);
//            std::cout <<box_id<<"\t"<<mListBox.size()<<"\n";
            if (box_id==-1)continue;
            const Box& bx_ini = mListBox.get(box_id);
            const int i_b=(int)bx_ini.getGridPos().x();
            const int j_b=(int)bx_ini.getGridPos().y();
            const int k_b=(int)bx_ini.getGridPos().z();
            for(int i=i_b-step;i<=i_b+step;++i){
                if (i>= mNumPtRange[0] || i<0)continue;

                for(int j=j_b-step;j<=j_b+step;++j){
                    if (j>= mNumPtRange[1]|| j<0)continue;

                    for(int k=k_b-step;k<=k_b+step;++k){
                        {
                            if (k>= mNumPtRange[2]|| k<0)continue;
                            id_test=i+j*mNumPtRange[0]+k*mNumPtRange[0]*mNumPtRange[1];
                            if (id_test> mMaxCubeNumber || id_test<0)continue;
                            Box& bx_test=mListBox.get(id_test);
//                      std::cout <<"DIST:"<<bx_test.getGridPos().distance(bx_ini.getGridPos())<<"\t"<<bx_test.getOrigPos().distance(bx_ini.getOrigPos())<<std::endl;
                            getRotCoords(atom.pos(),rot_pos);
                            if (isSphereInCube(atom,bx_test,ratio))
                            {
//                                std::cout <<"IN"<<std::endl;
                                bx_test.addCloseAtom(atom);
                            }
                        }}}}
        }
    }
}

protspace::Box* protspace::Grid::isAtomInCube(const MMAtom& atom,
                                              Box& box, const bool &goNext)const
{
    int posM;
    if (!mListMolecule.find(atom.getParent(),posM)) return nullptr;

    Coords rotpos; getRotCoords(atom.pos(),rotpos);
    return isCoordInCube(rotpos,box,goNext);


}
bool protspace::Grid::isSphereInCube(const MMAtom& atom,const Box&box, const double& ratio)const
{
    const double boxAtmDistR=box.getOrigPos().distance(atom.pos());
    /// We request that at least 10% of the radius of the sphere
    /// must be within the box
    //    std::cout << "\t"<<boxAtmDistR<<"\t"<<9*atom.getvdWRadius()/10+mBoxLength/2<<"\t"<<(((boxAtmDistR<9*atom.getvdWRadius()/10+mBoxLength/2))?"T":"F")<<std::endl;
    return (boxAtmDistR<atom.getvdWRadius()*ratio+mBoxLength/2);

}
bool protspace::Grid::isSphereInCube(const MMAtom& atom,Box&box, const double& ratio)const
{
    const double boxAtmDistR=box.getOrigPos().distance(atom.pos());
    /// We request that at least 10% of the radius of the sphere
    /// must be within the box
    //    std::cout << "\t"<<boxAtmDistR<<"\t"<<9*atom.getvdWRadius()/10+mBoxLength/2<<"\t"<<(((boxAtmDistR<9*atom.getvdWRadius()/10+mBoxLength/2))?"T":"F")<<std::endl;
    return (boxAtmDistR<atom.getvdWRadius()+mBoxLength*ratio);

}

protspace::Box* protspace::Grid::isCoordInCube(const Coords& rotpos,
                                               Box& box, const bool &goNext)const
{


    /// Getting distance between atom and cube center IN the (O,x,y,z)
    /// cartesian system
    /// Please note we use the original (O,x,y,z) and not (O',i,j,k)
    /// because the latter is dependant on mBoxStep value
    /// meaning that a distance of 1 in (O',i,j,k) is equal to a
    /// distance of mBoxStep in (O,x,y,z)
    const double boxAtmDistR=box.getGridPos().distance(rotpos);

    if (boxAtmDistR<mLim[0]){return &box;}
    else if (boxAtmDistR<2*mLim[1] && goNext)
    {

        const int diffN=ceil(4./mBoxLength)+1;
        double bestdist=boxAtmDistR;
        Box* bestbox=&box;
        const int atmX=(int)rotpos.x();
        const int atmY=(int)rotpos.y();
        const int atmZ=(int)rotpos.z();
        for (int i=atmX-diffN;i<=atmX+diffN;++i){if(i<0||i>=mNumPtRange[0])continue;
            for (int j=atmY-diffN;j<=atmY+diffN;++j){if(j<0||j>=mNumPtRange[1])continue;
                for (int k=atmZ-diffN;k<=atmZ+diffN;++k){if(k<0||k>=mNumPtRange[2])continue;
                    const int boxpos2=i+
                            j*mNumPtRange[0]+
                            k*mNumPtRange[0]*mNumPtRange[1];
                    if (boxpos2 >= mMaxCubeNumber)continue;
                    Box& box2=mListBox.get(boxpos2);
                    const double dist2=box2.getGridPos().distance(rotpos,bestdist);
                    if (dist2 >= bestdist)continue;
                    bestdist=dist2;
                    bestbox=&box2;
                }}}
        if (bestdist<mLim[1])return bestbox;
        return nullptr;

    }
    return nullptr;


}


void protspace::Grid::saveGrid(const std::string& pFile,
                               const bool& wCubeBorder,
                               const bool& wCenterCube,
                               const bool& pOnlyUsed)const
{
    const size_t nBox=mListBox.size();
    protspace::MacroMole mole;
    int posAt=0;
    std::vector<MMAtom*> list;
    Coords coo;MMAtom* at;
    const uint16_t bondspec=BOND::SINGLE;
    const double boxHalf(mBoxLength/2);
    if (wCubeBorder)
    for(size_t iB=0;iB<nBox;++iB)
    {
        protspace::Box& bx=mListBox.get(iB);
        if (pOnlyUsed && !bx.isInUse())continue;
        std::cout <<iB<<" " <<nBox<<std::endl;
        //        mole.addAtom(mole.getTempResidue(),mListBox.get(iB).getOrigPos(),"C","C.3","C");
        list.clear();
        for(double i=-1;i<=1;i+=2)
            for(double j=-1;j<=1;j+=2)
                for(double k=-1;k<=1;k+=2)
                {
                    //                    std::cout <<i<<" " <<j<<" " << k <<" " <<std::endl;
                    coo= bx.getOrigPos()+mVect[0]*i*boxHalf+mVect[1]*j*boxHalf+mVect[2]*k*boxHalf;
                    posAt=-1;
                    for(size_t iAtm=0;iAtm< mole.numAtoms();++iAtm)
                    {
                        if (mole.getAtom(iAtm).pos()!=coo)continue;
                        posAt=iAtm;break;
                    }
                    if (posAt==-1)at=&mole.addAtom(mole.getTempResidue(),coo,"O","O.3","O");
                    else at=&mole.getAtom(posAt);
                    list.push_back(at);
                }
        //        std::cout <<list.size()<<std::endl;
        //        0 = -1 -1 -1 X8
        //        1 = -1 -1 1  X7
        //        2 = -1 1 -1  X6
        //        3 = -1 1 1   X5
        //        4 = 1 -1 -1  X4
        //        5 = 1 -1 1   X3
        //        6 = 1 1 -1  X2
        //        7 = 1 1 1   X1
        mole.addBond(*list.at(0),*list.at(1),bondspec,1);
        mole.addBond(*list.at(0),*list.at(4),bondspec,1);
        mole.addBond(*list.at(0),*list.at(2),bondspec,1);
        mole.addBond(*list.at(4),*list.at(5),bondspec,1);
        mole.addBond(*list.at(4),*list.at(6),bondspec,1);
        mole.addBond(*list.at(6),*list.at(7),bondspec,1);
        mole.addBond(*list.at(6),*list.at(2),bondspec,1);
        mole.addBond(*list.at(2),*list.at(3),bondspec,1);
        mole.addBond(*list.at(1),*list.at(5),bondspec,1);
        mole.addBond(*list.at(1),*list.at(5),bondspec,1);
        mole.addBond(*list.at(5),*list.at(7),bondspec,1);
        mole.addBond(*list.at(7),*list.at(3),bondspec,1);
        mole.addBond(*list.at(3),*list.at(1),bondspec,1);
    }
    if (wCenterCube)
    for(size_t iB=0;iB<nBox;++iB)
    {
        if (pOnlyUsed && !mListBox.get(iB).isInUse())continue;
        mole.addAtom(mole.getTempResidue(),mListBox.get(iB).getOrigPos(),"C"+std::to_string(iB),"C.3","C");
    }
    std::cout <<"SAVE"<<std::endl;
    protspace::WriteMOL2 wm(pFile);
    wm.save(mole);

}


protspace::Box& protspace::Grid::getPrepBox(const MMAtom& pAtom)const
{
    for(size_t iM=0;iM < mListMolecule.size();++iM)
    {
        if (&mListMolecule.get(iM)!=&pAtom.getParent())continue;
        const int& id=mAtomBox.getVal(iM,pAtom.getMID());
        if (id==-1)
            throw_line("220701","Grid::getPrepBox","No box assigned to this atom");
        return mListBox.get(id);

    }
    throw_line("220702","Grid::getPrepBox","Parent molecule is not part of this grid");
}


const protspace::Box& protspace::Grid::getBox(const size_t& pos)const
{
    if (pos >= mListBox.size())
        throw_line("220801",
                   "Grid::getBox",
                   "Position above the number of boxes "+std::to_string(pos)
                   +"/"+std::to_string(mListBox.size()));
    return mListBox.get(pos);
}

bool protspace::Grid::findBox(const int &i, const int &j, const int &k, int &posBox) const
{
    if(i<0||i>=mNumPtRange[0])return false;
    if(j<0||j>=mNumPtRange[1])return false;
    if(k<0||k>=mNumPtRange[2])return false;
                posBox=i+
                        j*mNumPtRange[0]+
                        k*mNumPtRange[0]*mNumPtRange[1];
                if (posBox >= mMaxCubeNumber)return false;
            return true;
}

protspace::Box& protspace::Grid::getBox(const size_t& pos){
    if (pos >= mListBox.size())
        throw_line("220901",
                   "Grid::getBox",
                   "Position above the number of boxes "+std::to_string(pos)
                   +"/"+std::to_string(mListBox.size()));
    return mListBox.get(pos);
}
