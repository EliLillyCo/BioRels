#include <math.h>
#include "headers/molecule/mmring.h"
#include "headers/molecule/macromole.h"
#include "headers/math/coords_utils.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmring_utils.h"
#include "headers/statics/protpool.h"
#undef NDEBUG /// Active assertion in release
namespace protspace
{


bool isAllAtomInRing(const MMRing& ring, const std::vector<const MMAtom*>& liste )
{
    for(size_t iAtm = 0;iAtm < ring.numAtoms();++iAtm)
    {
        if (std::find(liste.begin(),liste.end(),
                 &ring.getAtom(iAtm))==liste.end())return false;

    }
    return true;

}


bool hasFusedAtom(const MMRing& ring, const MacroMole&mole)
{

        for (size_t iR=0;iR<mole.numRings();++iR)
        {
            const MMRing& ringC=mole.getRing(iR);
            if (&ringC==&ring)continue;
            if (shareAtoms(ring,ringC))return true;
        }


    return false;
}
size_t getRingPosFromCenter(const MMAtom& pAtom)
{
    const MacroMole& pMole=pAtom.getParent();
    const size_t nR(pMole.numRings());
    for(size_t iR=0;iR< pMole.numRings();++iR)
    {
        const MMRing& ring = pMole.getRing(iR);
        if (&ring.getAtomCenter()==&pAtom)return iR;
    }
    return nR;
}
MMRing& getRingFromCenter(MMAtom& pAtom)
{
    const size_t pos=getRingPosFromCenter(pAtom);
    if (pos != pAtom.getParent().numRings())
        return pAtom.getParent().getRing(pos);
    throw_line("XXXXX","RngUtils::getRingFromCenter","Unable to find ring for given atom");
}

void getAtomRings(const MMAtom& atom, std::vector<MMRing*>& list)
{
    const MacroMole& mole=atom.getMolecule();
    for(size_t iRR=0;iRR< mole.numRings();++iRR)
    {
        MMRing& ring=mole.getRing(iRR);
        if (! ring.isInRing(atom))continue;
        list.push_back(&ring);
    }
}




bool hasOverlappingAtoms(const MMRing& ring1, const MMRing& ring2,
                         std::vector<MMAtom*>& list)
{
    bool found=false;
    for(size_t iAtm1=0;iAtm1<ring1.numAtoms();++iAtm1)
    {
        MMAtom& atom1=ring1.getAtom(iAtm1);
        for(size_t iAtm2=0;iAtm2<ring2.numAtoms();++iAtm2)
        {
            const MMAtom& atom2=ring2.getAtom(iAtm2);

            if (atom1.getMID()!=atom2.getMID())continue;

            list.push_back(&atom1);
            found=true;
        }
    }
    return found;
}


void turnToAromaticBonds(MacroMole& mole)
{
    for(size_t iRR=0;iRR< mole.numRings();++iRR)
    {
        MMRing& ring=mole.getRing(iRR);
        if (!ring.isAromatic())continue;
        for(size_t iAtm1=0;iAtm1<ring.numAtoms();++iAtm1)
        {
            MMAtom& atom1=ring.getAtom(iAtm1);
            for(size_t iAtm2=iAtm1+1;iAtm2<ring.numAtoms();++iAtm2)
            {
                MMAtom& atom2=ring.getAtom(iAtm2);
                if (atom1.hasBondWith(atom2))
                    atom1.getBond(atom2).setBondType(BOND::AROMATIC_BD);
            }
        }
    }
}


bool isAtomInAromaticRing(const MMAtom& atom)
{
    const MacroMole& mole = atom.getParent();
    for(size_t iRR=0;iRR< mole.numRings();++iRR)
    {
        const MMRing& ring=mole.getRing(iRR);
        if (!ring.isAromatic())continue;
        if (ring.isInRing(atom))return true;
    }return false;
}

bool shareAtoms(const MMRing& pRing1, const MMRing& pRing2)
{
    for(size_t iR2=0;iR2<pRing1.numAtoms();++iR2)
    {
        const MMAtom&  atm1=pRing1.getAtom(iR2);
        if (pRing2.isInRing(atm1))return true;
    }
    return false;
}

void fuseRingSystems(MacroMole& mole)
try{

    bool mod=false;
    do
    {
        mod=false;
        for(size_t iRing=0;iRing < mole.numRings();++iRing)
        {
            MMRing& ring =mole.getRing(iRing);
            for(size_t iRing2=iRing+1;iRing2<mole.numRings();++iRing2)
            {
                MMRing& ring2=mole.getRing(iRing2);
                if(!shareAtoms(ring,ring2)) continue;
                mod=true;
                for(size_t iR3=0;iR3<ring2.numAtoms();++iR3)
                {
                    if(!ring.isInRing(ring2.getAtom(iR3)))
                    ring.addAtom(&ring2.getAtom(iR3));
                }
                mole.delRing(ring2);
                break;

            }
            if (mod)break;
        }

    }while(mod);
}catch(ProtExcept &e)
{
    assert(e.getId()!="352501");///Ring must exists
    assert(e.getId()!="352601");///ring must be in the molecule
    e.addHierarchy("fuseRingSystems");
    throw;
}

MMAtom& getClosestRingAtom(const Coords &coo, const MMRing& ring)
{
    double bestdist=1000;size_t best=ring.numAtoms();
    for(size_t iAtm=0;iAtm < ring.numAtoms();++iAtm)
    {
        const MMAtom& atm = ring.getAtom(iAtm);
        const double dist(atm.pos().distance(coo));
        if (dist > bestdist)continue;
        bestdist=dist;
        best=iAtm;
    }
    assert(best != ring.numAtoms());
    return ring.getAtom(best);

}

void getVectorsForRingAtom(const Coords& coo,
                           const MMRing& ring,
                           Coords& vect_i,
                           Coords& vect_j,
                           Coords& vect_k)
{
    const Coords& rcent(ring.getCenter());
    MMAtom& closeAtm=getClosestRingAtom(coo,ring);

    MMAtom* cb=nullptr;
    for(size_t iBd=0;iBd < closeAtm.numBonds();++iBd)
    {
        if (closeAtm.getAtom(iBd).isHydrogen())continue;
        ///Ensure it is not colinear, such as CZ and OH of TYR
        if (!ring.isInRing(closeAtm.getAtom(iBd)))continue;
        cb=&closeAtm.getAtom(iBd);
    }
    assert(cb!= nullptr);

    const protspace::CoordPoolObj x_axis(rcent.getNormal(closeAtm.pos(),cb->pos()));
    const protspace::CoordPoolObj y_axis(rcent.getNormal(closeAtm.pos(),x_axis.obj));
    const protspace::CoordPoolObj z_axis(rcent.getNormal(x_axis.obj,y_axis.obj));

    vect_i.setxyz(x_axis.obj-rcent);
    vect_j.setxyz(y_axis.obj-rcent);
    vect_k.setxyz(z_axis.obj-rcent);

}


void processRingAtomInfo(const MMRing& pRing,const Coords& pCoo,ring_atm_inf& data)
{
    /// Distance cation - center of ring
    const Coords& rcent = pRing.getCenter();
    data.mRc=rcent.distance(pCoo);

    /// New vector centered on the ring center
    /// j and k on the plane of the ring
    /// i perpendicular to the ring plane

    getVectorsForRingAtom(pCoo,pRing,data.mVect_i.obj,data.mVect_j.obj,data.mVect_k.obj);
    if ((data.mVect_i.obj+rcent).distance(pCoo)> rcent.distance(pCoo))
    {
        data.mVect_i.obj.setxyz(-data.mVect_i.obj.x(),-data.mVect_i.obj.y(),-data.mVect_i.obj.z());
    }
    /// Getting the rotation matrix out of it:
    calcMatrixFromVector(data.mVect_i.obj,data.mVect_j.obj,data.mVect_k.obj,data.mRotMatrix);


    /// Calculating relative coordinates of the cation from the geometric center of ther ring
    protspace:: CoordPoolObj rotpos(pCoo-rcent);
    rotate(rotpos.obj,data.mRotMatrix);

    /// Cxyz should be the exact same coordinate as cation, otherwise there is an issue
    const protspace:: CoordPoolObj Cxyz(rcent+data.mVect_i.obj*rotpos.obj.x()
                           +data.mVect_j.obj*rotpos.obj.y()
                           +data.mVect_k.obj*rotpos.obj.z());
    assert(Cxyz.obj.distance(pCoo)<0.01);

    data.mCz=(rcent+data.mVect_k.obj*rotpos.obj.z());
    data.mCy=(rcent+data.mVect_j.obj*rotpos.obj.y());
    /// Projected position of the cation onto the plane of the ring
    /// That helps to assess whether the cation is above the plane of the ring
    /// or around it.
    data.mCyz=(rcent+data.mVect_k.obj*rotpos.obj.z()+data.mVect_j.obj*rotpos.obj.y());
    /// Distance from the ring center to the projected position.

    data.mDSide=rcent.distance(data.mCyz.obj);
    data.mTheta=rcent.angle_between(pCoo,data.mVect_i.obj+rcent);
    data.mHeight=data.mCyz.obj.distance(pCoo);





}

void turnToAromaticRing(MMRing& ring)
{
    if (!ring.isAromatic())return;
    //std::cout <<"\n";
    size_t nNAtm=0;
    for(size_t iAtm=0;iAtm<ring.numAtoms();++iAtm)
    {
        MMAtom& atom=ring.getAtom(iAtm);
        for(size_t j=0;j<atom.numBonds();++j)
        {
           // std::cout <<ring.isInRing(atom.getAtom(j))<<"\t"<<atom.getBond(j).toString()<<std::endl;
            if (ring.isInRing(atom.getAtom(j)))continue;
            if (atom.getBond(j).getType()!=BOND::SINGLE
              &&atom.getBond(j).getType()!=BOND::AROMATIC_BD)return;
        }
        if (atom.getAtomicNum()==16||atom.isOxygen())return;
        if (atom.isNitrogen())nNAtm++;
    }
    if (nNAtm>=2) return;

    for(size_t iAtm=0;iAtm<ring.numAtoms();++iAtm)
    {
        MMAtom& atom=ring.getAtom(iAtm);
        if (atom.getElement()=="C") atom.setMOL2Type("C.ar");
        else if (atom.getMOL2()=="N.2") atom.setMOL2Type("N.ar");
        for(size_t iAtm2=iAtm+1;iAtm2<ring.numAtoms();++iAtm2)
        {
            const MMAtom& atom1=ring.getAtom(iAtm2);
            if(!atom.hasBondWith(atom1))continue;
            atom.getBond(atom1).setBondType(BOND::AROMATIC_BD);
        }
    }
}

void getMaskInRing(bool isInRing[], const MacroMole& mole)
{
    for(size_t iRing=0;iRing< mole.numRings();++iRing)
    {
        const MMRing& ring1=mole.getRing(iRing);
        for(size_t iAtm1=0;iAtm1<ring1.numAtoms();++iAtm1)
        {
            const MMAtom& atom1=ring1.getAtom(iAtm1);
            isInRing[atom1.getMID()]=true;
        }
    }
}


bool isAtomCloserThan(const MMAtom& pAtom, const MMRing& pRing, const double& pDist)
{
    for(size_t iAtm=0;iAtm < pRing.numAtoms();++iAtm)
    {
        const double dist = pRing.getAtom(iAtm).dist(pAtom);
        if (dist < pDist)return true;
    }
    return false;
}

double getClosestDist(const MMAtom& pAtom, const MMRing& pRing,MMAtom*& best)
{
    double pDist=1000;
    for(size_t iAtm=0;iAtm < pRing.numAtoms();++iAtm)
    {
        const double dist = pRing.getAtom(iAtm).dist(pAtom);
        if (dist > pDist)continue;
        pDist=dist;
        best = &pRing.getAtom(iAtm);
    }
    return pDist;
}






void cleanProteinRings(MacroMole& pMole)
{
    std::vector<MMRing*> todel;
    for(size_t iRing=0;iRing < pMole.numRings();++iRing)
    {
        MMRing& pRing = pMole.getRing(iRing);
        MMResidue* res=nullptr;
        for(size_t iAt=0;iAt < pRing.numAtoms();++iAt) {
            if (res == nullptr) {
                res = &pRing.getAtom(iAt).getResidue();
                continue;
            }
            else if (res == &pRing.getAtom(iAt).getResidue())continue;

            todel.push_back(&pRing);
            break;
        }
    }
    for(auto ring:todel) pMole.delRing(*ring);
}








double getDistCenter(const MMRing& ring1,const MMRing& ring2)
{
    return ring1.getCenter().distance(ring2.getCenter());
}







double getClosestDistance(const MMRing& ring1,const MMRing& ring2)
{
    double bestDist=10000;
    const size_t nAtm1(ring1.numAtoms());
    const size_t nAtm2(ring2.numAtoms());

    for(size_t iAt1=0;iAt1<nAtm1;++iAt1)
    {
        const Coords& refR=ring1.getAtom(iAt1).pos();
        for(size_t iAt2=0;iAt2<nAtm2;++iAt2)
        {
            const Coords& refC=ring2.getAtom(iAt2).pos();
            const double dist=refC.distance_squared(refR);
            if (dist < bestDist)bestDist=dist;
        }
    }
    return sqrt(bestDist);
}







const MMAtom& getClosestAtom(const MMRing& ringFrom, const MMRing &ringAgainst)
{
    double bestDist=10000;
    const size_t nAtm1(ringFrom.numAtoms());
    const size_t nAtm2(ringAgainst.numAtoms());
    size_t bestAtm=nAtm2;
    for(size_t iAt2=0;iAt2<nAtm2;++iAt2)
    {

        const Coords& refR=ringAgainst.getAtom(iAt2).pos();
        for(size_t iAt1=0;iAt1<nAtm1;++iAt1)
        {
            const Coords& refC=ringFrom.getAtom(iAt1).pos();
            const double dist=refC.distance_squared(refR);
            if (dist > bestDist)continue;
            bestDist=dist;
            bestAtm=iAt2;
        }
    }
    assert(bestAtm != nAtm2);
    return ringAgainst.getAtom(bestAtm);
}


}
