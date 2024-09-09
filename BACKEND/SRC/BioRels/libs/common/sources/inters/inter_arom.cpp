#include <math.h>
#include <sstream>
#include <math.h>

#include "headers/math/coords_utils.h"
#include "headers/inters/inter_arom.h"
#include "headers/molecule/mmresidue.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/macromole.h"
#include "headers/inters/interdata.h"

using namespace std;
using namespace protspace;

double InterArom::mDrc_PD=6;
double InterArom::mDryz_Min_PD=1;///Should be 1.5 but 2r59 proves it's too long
double InterArom::mDryz_Max_PD=6;
double InterArom::mTheta_C_PD=0;
double InterArom::mTheta_R_PD=M_PI/6;
double InterArom::mAP_C_PD=0;
double InterArom::mAP_R_PD=M_PI/6;

double InterArom::mDrc_EF=8;
double InterArom::mDH_EF=6;
double InterArom::mDryz_EF=2;
double InterArom::mTheta_C_EF=0;
double InterArom::mTheta_R_EF=M_PI/6;
double InterArom::mAP_C_EF=M_PI/2;
double InterArom::mAP_R_EF=M_PI/3;

double InterArom::mDrc_HE=8;
double InterArom::mDryz_HE=8;
double InterArom::mTheta_C_HE=M_PI/3;
double InterArom::mTheta_R_HE=M_PI/6;
double InterArom::mAP_C_HE=M_PI;
double InterArom::mAP_R_HE=M_PI/4;

InterArom::InterArom(const MMRing& pRingRef, const MMRing& pRingComp):
    mRingRef(pRingRef),
    mRingComp(pRingComp)
{


}
void InterArom::runMath()
{
    processRingAtomInfo(mRingRef,mRingComp.getCenter(),mDataRef);
    processRingAtomInfo(mRingComp,mRingRef.getCenter(),mDataComp);
    mAngleRot=    mRingRef.getCenter().angle_between(mDataRef.mVect_i.obj+mRingRef.getCenter(),
                                                     mDataComp.mVect_i.obj+mRingRef.getCenter());
}




bool InterArom::sameRingSystem()const
try{
    if (&mRingRef.getResidue()!=&mRingComp.getResidue())  return false;
    if (&mRingRef.getResidue()==&mRingComp.getResidue())  return true;
    if (mRingRef.getResidue().getResType()==RESTYPE::STANDARD_AA)return true;

    for(size_t iR=0;iR< mRingRef.numAtoms();++iR)
    {
        const MMAtom&atomR = mRingRef.getAtom(iR);
        for(size_t iC=0;iC< mRingComp.numAtoms();++iC)
        {
            const MMAtom&atomC = mRingComp.getAtom(iC);
            if (&atomR==&atomC)return true;
        }
    }
    return false;
}catch(ProtExcept &e)
{

    e.addHierarchy("InterArom::sameRingSystem");
    throw;
}






bool InterArom::isParallelDisplaced(InterData& data)const
{
    bool ok=true;

    if( sameRingSystem())return false;
    /// Aromatic center shouldn't be too far away
    if (mDataRef.mRc > mDrc_PD) return false;
    /// Offset between the two rings
    /// Lower than Min => Repulsive face to face
    if (mDataRef.mDSide < mDryz_Min_PD)ok=false;
    /// Above max => side by side
    if (mDataRef.mDSide > mDryz_Max_PD)ok=false;

    const double angleDeg(mAngleRot*180/M_PI);
    if (mAngleRot >      mAP_C_PD-mAP_R_PD &&      mAngleRot <      mAP_C_PD+mAP_R_PD)
    {
//     std::cout <<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"R13-"<<mDataRef.mDSide*-30.08 +220.04<<"\t"<<angleDeg<<"\t"<<mDataRef.mDSide*-16.929+60.682<<std::endl;
        if (     angleDeg < mDataRef.mDSide*-16.929+60.682) ok=false;//#1
        else if (angleDeg > mDataRef.mDSide*-30.08 +220.04) ok=false;//#3
    }
    else if (mAngleRot > M_PI+mAP_C_PD-mAP_R_PD &&      mAngleRot < M_PI+mAP_C_PD+mAP_R_PD)
    {
//        std::cout <<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"\tR45-"<<"\t"<<mDataRef.mDSide<<"\t"<<mDataRef.mDSide*-57.143+388.57<<"\t"<<angleDeg
  //               <<"\t"<<mDataRef.mDSide*-85.981+795.89<<std::endl;
        if (     angleDeg < mDataRef.mDSide*-57.143+388.57) ok=false; //#4
        else if (angleDeg > mDataRef.mDSide*-85.981+795.89) ok=false; //#5
    }
    else
    {
//        std::cout <<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"\t"<<
//                         (mAP_C_PD-mAP_R_PD)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(mAP_C_PD+mAP_R_PD)*180/M_PI<<"\t"
//                  <<(M_PI+mAP_C_PD-mAP_R_PD)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(M_PI+mAP_C_PD+mAP_R_PD)*180/M_PI<<"\n";
        ok=false;
    }



    if (ok)
    {
        InterObj pObj(mRingRef.getAtomCenter(),
                      mRingComp.getAtomCenter(),
                      INTER::AROMATIC_FF,
                      mDataRef.mRc);
        pObj.setRing1(mRingRef);
        pObj.setRing2(mRingComp);
        data.addInter(pObj);
        return true;
    }
    ok=true;
    /// Offset between the two rings
    /// Lower than Min => Repulsive face to face
    if (mDataComp.mDSide < mDryz_Min_PD)ok=false;
    /// Above max => side by side
    if (mDataComp.mDSide > mDryz_Max_PD)ok=false;

    if (mAngleRot >      mAP_C_PD-mAP_R_PD &&      mAngleRot <      mAP_C_PD+mAP_R_PD)
    {
        if (     angleDeg < mDataComp.mDSide*-16.929+60.682) ok=false;//#1
        else if (angleDeg > mDataComp.mDSide*-30.08 +220.04) ok=false;//#3
    }
    else if (mAngleRot > M_PI+mAP_C_PD-mAP_R_PD &&      mAngleRot < M_PI+mAP_C_PD+mAP_R_PD)
    {
        if (     angleDeg < mDataComp.mDSide*-57.143+388.57) ok=false; //#4
        else if (angleDeg > mDataComp.mDSide*-85.981+795.89) ok=false; //#5
    }
    else
    {
//        std::cout <<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"\t"<<
//                         (mAP_C_PD-mAP_R_PD)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(mAP_C_PD+mAP_R_PD)*180/M_PI<<"\t"
//                  <<(M_PI+mAP_C_PD-mAP_R_PD)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(M_PI+mAP_C_PD+mAP_R_PD)*180/M_PI<<"\n";
        ok=false;
    }

    if (!ok) return false;
    InterObj pObj(mRingRef.getAtomCenter(),
                  mRingComp.getAtomCenter(),
                  INTER::AROMATIC_FF,
                  mDataRef.mRc);
    pObj.setRing1(mRingRef);
    pObj.setRing2(mRingComp);
    data.addInter(pObj);
    return true;

}





bool InterArom::isEdgeToFace(InterData& data)const
{
    bool ok=true;

    if( sameRingSystem()){//cout <<"SAME RING"<<endl;
        return false;}
    /// Aromatic center shouldn't be too far away
    if (mDataRef.mRc > mDrc_EF){//cout <<"TOO FAR"<<endl;
        return false;}

    if (!(mAngleRot >      mAP_C_EF-mAP_R_EF &&      mAngleRot <      mAP_C_EF+mAP_R_EF)&&
        !(mAngleRot > M_PI+mAP_C_EF-mAP_R_EF &&      mAngleRot < M_PI+mAP_C_EF+mAP_R_EF))
    {
//        std::cout <<"EF\t"<<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"\t"<<
//                         (mAP_C_EF-mAP_R_EF)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(mAP_C_EF+mAP_R_EF)*180/M_PI<<"\t"
//                  <<(M_PI+mAP_C_EF-mAP_R_EF)*180/M_PI<<" < "<<mAngleRot*180/M_PI<<" < "<<(M_PI+mAP_C_EF+mAP_R_EF)*180/M_PI<<"\n";
        return false;
    }


    /// Offset between the two rings
    if (mDataRef.mDSide > mDryz_EF){ok=false;
//                  std::cout <<"OFFSET TOO BIG"<<std::endl;
    }

    const double angleDeg(mAngleRot*180/M_PI);
   if       (mDataRef.mDSide < 7 &&
             angleDeg > -16.929* mDataRef.mDSide+60.821 && angleDeg<-13.566*mDataRef.mDSide+123.47)ok=true;
    else if (mDataRef.mDSide > 3 && angleDeg>90 && angleDeg < 150 &&
             angleDeg > -41.228* mDataRef.mDSide+278.27 && angleDeg<-42.143*mDataRef.mDSide+165.85)ok=true;
   else if (mDataRef.mDSide > 3 && angleDeg<90 && angleDeg>30 &&
            angleDeg > -41.228* mDataRef.mDSide+278.27 && angleDeg<-48.189*mDataRef.mDSide+387.9)ok=true;
   else ok=false;






    if (ok)
    {
        InterObj pObj(mRingRef.getAtomCenter(),
                      mRingComp.getAtomCenter(),
                      INTER::AROMATIC_EF,
                      mDataRef.mRc);
        pObj.setRing1(mRingRef);
        pObj.setRing2(mRingComp);
        data.addInter(pObj);
        return true;
    }
    ok=true;
    /// Offset between the two rings
    /// Above max => side by side
    if (mDataComp.mDSide > mDryz_EF)
    {
//               std::cout<<"EF\t"<<mRingRef.toString()<<"\t"<<mRingComp.toString()<<"\t"<<"EF2 OFFSET TOO BIG"<<std::endl;
        ok=false;
    }
    if (mAngleRot*180/M_PI < 55.148-mDataComp.mDSide*38.427) ok=false;
    if       (mDataComp.mDSide < 7 &&
              angleDeg > -16.929* mDataComp.mDSide+60.821 && angleDeg<-13.566*mDataComp.mDSide+123.47)ok=true;
     else if (mDataComp.mDSide > 3 && angleDeg>90 && angleDeg < 150 &&
              angleDeg > -41.228* mDataComp.mDSide+278.27 && angleDeg<-42.143*mDataComp.mDSide+165.85)ok=true;
    else if (mDataComp.mDSide > 3 && angleDeg<90 && angleDeg>30 &&
             angleDeg > -41.228* mDataComp.mDSide+278.27 && angleDeg<-48.189*mDataComp.mDSide+387.9)ok=true;
    else ok=false;
    if (!ok) return false;
    InterObj pObj(mRingRef.getAtomCenter(),
                  mRingComp.getAtomCenter(),
                  INTER::AROMATIC_EF,
                  mDataRef.mRc);
    pObj.setRing1(mRingRef);
    pObj.setRing2(mRingComp);
    data.addInter(pObj);
    return true;
}


bool InterArom::isHerringBone()const
{
    return false;
    //      if (mDataRef.mRc > )
}
bool InterArom::isInteracting()const
{
    return false;
}
void InterArom::saveRefToMole()const
{
    const Coords& rcent(mRingRef.getCenter());
    MacroMole test;
    Coords rotpos(mRingComp.getCenter()-rcent);
    rotate(rotpos,mDataRef.mRotMatrix);
    MMAtom& at1=test.addAtom(test.getTempResidue(),rcent,"P","P.3","P");
    MMAtom& at2=test.addAtom(test.getTempResidue(),mDataRef.mVect_i.obj+rcent,"C","C.3","C");
    MMAtom& at3=test.addAtom(test.getTempResidue(),mDataRef.mVect_j.obj+rcent,"N","N.3","N");
    MMAtom& at4=test.addAtom(test.getTempResidue(),mDataRef.mVect_k.obj+rcent,"O","O.3","O");

    Coords at5c(rcent+mDataRef.mVect_i.obj*rotpos.x()+mDataRef.mVect_j.obj*rotpos.y()+mDataRef.mVect_k.obj*rotpos.z());
    MMAtom& at5=test.addAtom(test.getTempResidue(),at5c,"F","F","F");

    Coords at6c(rcent+mDataRef.mVect_k.obj*rotpos.z());
    MMAtom& at6=test.addAtom(test.getTempResidue(),at6c,"F","F","F");

    Coords at7c(rcent+mDataRef.mVect_j.obj*rotpos.y());
    MMAtom& at7=test.addAtom(test.getTempResidue(),at7c,"F","F","F");

    Coords at8c(rcent+mDataRef.mVect_k.obj*rotpos.z()+mDataRef.mVect_j.obj*rotpos.y());
    MMAtom& at8=test.addAtom(test.getTempResidue(),at8c,"F","F","F");
    Coords at9c(rcent+mDataComp.mVect_i.obj);
    test.addAtom(test.getTempResidue(),at9c,"Cl","Cl","Cl");
    test.addBond(at1,at2,BOND::SINGLE);
    test.addBond(at1,at3,BOND::SINGLE);
    test.addBond(at1,at4,BOND::SINGLE);
    test.addBond(at6,at8,BOND::SINGLE);
    test.addBond(at8,at7,BOND::SINGLE);
    test.addBond(at5,at8,BOND::SINGLE);
    ostringstream oss;
    MMResidue &res=mRingRef.getResidue();
    oss<<"R"<<res.getName()<<"-"
      <<res.getFID()<<"-"
     <<res.getChainName()<<"_"<<mRingRef.getAtom(0).getName();
    MMResidue &resC=mRingComp.getResidue();
    oss<<resC.getName()<<"-"
      <<resC.getFID()<<"-"
     <<resC.getChainName()<<"_"<<mRingComp.getAtom(0).getName()<<".mol2";
    protspace::WriteMOL2 writer(oss.str());
    writer.save(test);
}

void InterArom::saveCompToMole()const
{
    const Coords& rcent(mRingComp.getCenter());
    MacroMole test;
    Coords rotpos(mRingRef.getCenter()-rcent);
    rotate(rotpos,mDataComp.mRotMatrix);
    MMAtom& at1=test.addAtom(test.getTempResidue(),rcent,"P","P.3","P");
    MMAtom& at2=test.addAtom(test.getTempResidue(),mDataComp.mVect_i.obj+rcent,"C","C.3","C");
    MMAtom& at3=test.addAtom(test.getTempResidue(),mDataComp.mVect_j.obj+rcent,"N","N.3","N");
    MMAtom& at4=test.addAtom(test.getTempResidue(),mDataComp.mVect_k.obj+rcent,"O","O.3","O");

    Coords at5c(rcent+mDataComp.mVect_i.obj*rotpos.x()+mDataComp.mVect_j.obj*rotpos.y()+mDataComp.mVect_k.obj*rotpos.z());
    MMAtom& at5=test.addAtom(test.getTempResidue(),at5c,"F","F","F");

    Coords at6c(rcent+mDataComp.mVect_k.obj*rotpos.z());
    MMAtom& at6=test.addAtom(test.getTempResidue(),at6c,"F","F","F");

    Coords at7c(rcent+mDataComp.mVect_j.obj*rotpos.y());
    MMAtom& at7=test.addAtom(test.getTempResidue(),at7c,"F","F","F");

    Coords at8c(rcent+mDataComp.mVect_k.obj*rotpos.z()+mDataComp.mVect_j.obj*rotpos.y());
    MMAtom& at8=test.addAtom(test.getTempResidue(),at8c,"F","F","F");
    Coords at9c(rcent+mDataRef.mVect_i.obj);
    MMAtom& at9=test.addAtom(test.getTempResidue(),at9c,"Cl","Cl","Cl");
    test.addBond(at1,at2,BOND::SINGLE);
    test.addBond(at1,at3,BOND::SINGLE);
    test.addBond(at1,at4,BOND::SINGLE);
    test.addBond(at6,at8,BOND::SINGLE);
    test.addBond(at8,at7,BOND::SINGLE);
    test.addBond(at5,at8,BOND::SINGLE);
    ostringstream oss;
    MMResidue &res=mRingComp.getResidue();
    oss<<"C"<<res.getName()<<"-"
      <<res.getFID()<<"-"
     <<res.getChainName()<<"_"<<mRingRef.getAtom(0).getName();
    MMResidue &resC=mRingRef.getResidue();
    oss<<resC.getName()<<"-"
      <<resC.getFID()<<"-"
     <<resC.getChainName()<<"_"<<mRingComp.getAtom(0).getName()<<".mol2";
    protspace::WriteMOL2 writer(oss.str());
    writer.save(test);
}
