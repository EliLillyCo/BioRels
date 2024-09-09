#include <iostream>
#include <sstream>
#include <math.h>
#include "headers/math/rigidbody.h"
#include "headers/math/coords.h"
#undef NDEBUG /// Active assertion in release
using namespace protspace;
using namespace std;



RigidBody::RigidBody(const size_t& size):RigidCoords(size,false),
    MobilCoords(size,false),DotPro(9,false),rmsd(0),centered(false)
{
    size_t pos;
    mAlign.sPoolDbl.preRequest(10);
    for(size_t i=0;i<9;++i)
    {
        double& value=mAlign.sPoolDbl.acquireObject(pos);
        value=0;
        DotPro.add(value);
        mReleaseObj.push_back(pos);
    }

}

RigidBody::~RigidBody()
{

    for(const auto& pos:mReleaseObj) mAlign.sPoolDbl.releaseObject(pos);

    for(const auto& pos:rigidPos) mAlign.sPoolCoo.releaseObject(pos);
    for(const auto& pos:mobilPos) mAlign.sPoolCoo.releaseObject(pos);
}

void RigidBody::clear()
{
    RigidCoords.clear();          /*!< Rigid coordinates */
    MobilCoords.clear();          /*!< Iobile coordinates */
    Weigths.clear();              /*!< List of weigths */
    rmsd=10000;                 /*!< RMSD of the alignment */
    for (int i=0;i<3;i++)
        for (int j=0;j<3;j++)
        {
            DotPro.get(i*3+j)=0;
        }

    E0=0;
    mAlign.clear();;
    centered=false;
}


void RigidBody::centerData()
{
    size_t posCenter;
    protspace::Coords& Center=mAlign.sPoolCoo.acquireObject(posCenter);
    Center.clear();

    const size_t sizeRigid(RigidCoords.size());
    for(size_t i=0;i<sizeRigid;++i) Center+=RigidCoords.get(i);
    Center/=sizeRigid;
    mAlign.TransRigid= Center;
    for(size_t i=0;i<sizeRigid;++i) RigidCoords.get(i)-=Center;


    Center.clear();
    const size_t sizeMobil(MobilCoords.size());
    for(size_t i=0;i<sizeMobil;++i) Center+=MobilCoords.get(i);
    Center/=sizeMobil;
    mAlign.TransMob = Center;
    for(size_t i=0;i<sizeMobil;++i) MobilCoords.get(i)-=Center;

    mAlign.sPoolCoo.releaseObject(posCenter);

}


double RigidBody::InnerProduct()
{
    if (!centered) centerData();
    size_t             i;
    size_t g1Pos,g2Pos;
    double& G1= mAlign.sPoolDbl.acquireObject(g1Pos);G1=0.0;
    double& G2= mAlign.sPoolDbl.acquireObject(g2Pos);G2=0.0;


    for (i=0;i<9;++i) DotPro.get(i)=0;


    const  size_t len = RigidCoords.size();
    if (Weigths.size() == len)
    {
        size_t Rew_Pos;
        Coords& Rew=mAlign.sPoolCoo.acquireObject(Rew_Pos);
        for (i = 0; i < len; ++i)
        {// Not checked
            const Coords& Re = (RigidCoords.get(i));

            const double& weigth= Weigths.at(i);

            Rew= Re*weigth;

            G1+=Rew.x()*Re.x()+Rew.y()*Re.y()+Rew.z()*Re.z();

            const Coords& Co = (MobilCoords.get(i));

            G2+=weigth*(Co.x()*Co.x()+Co.y()*Co.y()+Co.z()*Co.z());

            DotPro[0] +=  Rew.x()*Co.x();
            DotPro[1] +=  Rew.x()*Co.y();
            DotPro[2] +=  Rew.x()*Co.z();

            DotPro[3] +=  Rew.y()*Co.x();
            DotPro[4] +=  Rew.y()*Co.y();
            DotPro[5] +=  Rew.y()*Co.z();

            DotPro[6] +=  Rew.z()*Co.x();
            DotPro[7] +=  Rew.z()*Co.y();
            DotPro[8] +=  Rew.z()*Co.z();
        }
        mAlign.sPoolCoo.releaseObject(Rew_Pos);
    }
    else
    {
        if (Weigths.size() > 0 && len > 0 && Weigths.size()!=len)
        {
        throw_line("210101","RigidBody::innerProduct",
                   "Weights does not fit size");}

        for (i = 0; i < len; ++i)
        {
            const Coords& Re = (RigidCoords.get(i));

            G1+=Re.x()*Re.x()+Re.y()*Re.y()+Re.z()*Re.z();
            const Coords& Co = (MobilCoords.get(i));
            G2+=Co.x()*Co.x()+Co.y()*Co.y()+Co.z()*Co.z();

            DotPro[0] +=  Re.x()*Co.x();
            DotPro[1] +=  Re.x()*Co.y();
            DotPro[2] +=  Re.x()*Co.z();

            DotPro[3] +=  Re.y()*Co.x();
            DotPro[4] +=  Re.y()*Co.y();
            DotPro[5] +=  Re.y()*Co.z();

            DotPro[6] +=  Re.z()*Co.x();
            DotPro[7] +=  Re.z()*Co.y();
            DotPro[8] +=  Re.z()*Co.z();
        }
    }
    E0= (G1 + G2) * 0.5;
     mAlign.sPoolDbl.releaseObject(g1Pos);
     mAlign.sPoolDbl.releaseObject(g2Pos);
    return E0;
}



double RigidBody::calcRotation(const  double minScore)
{
    if (RigidCoords.size()==0)
        throw_line("210201","RigidBody::calcRotation",
                   "Rigid coordinate list is empty");
        if (MobilCoords.size()==0)
            throw_line("210202","RigidBody::calcRotation",
                       "Mobil coordinate list is empty");
    if (RigidCoords.size() != MobilCoords.size())
        throw_line("210203","RigidBody::calcRotation",
                   "Mobile and Rigid coordinate lists have different size");
    InnerProduct();

    const size_t len = RigidCoords.size();
    double Sxx, Sxy, Sxz, Syx, Syy, Syz, Szx, Szy, Szz;
    double Szz2, Syy2, Sxx2, Sxy2, Syz2, Sxz2, Syx2, Szy2, Szx2,
            SyzSzymSyySzz2, Sxx2Syy2Szz2Syz2Szy2, Sxy2Sxz2Syx2Szx2,
            SxzpSzx, SyzpSzy, SxypSyx, SyzmSzy,
            SxzmSzx, SxymSyx, SxxpSyy, SxxmSyy;
    double C[4];
    size_t i;
    double mxEigenV;
    double oldg = 0.0;
    double b, a, delta, rms, qsqr;
    double q1, q2, q3, q4, normq;
    double a11, a12, a13, a14, a21, a22, a23, a24;
    double a31, a32, a33, a34, a41, a42, a43, a44;
    double a2, x2, y2, z2;
    double xy, az, zx, ay, yz, ax;
    double a3344_4334, a3244_4234, a3243_4233, a3143_4133,a3144_4134, a3142_4132;
    double evecprec = 1e-6;
    double evalprec = 1e-14;

    Sxx = DotPro[0]; Sxy = DotPro[1]; Sxz = DotPro[2];
    Syx = DotPro[3]; Syy = DotPro[4]; Syz = DotPro[5];
    Szx = DotPro[6]; Szy = DotPro[7]; Szz = DotPro[8];

    Sxx2 = Sxx * Sxx;
    Syy2 = Syy * Syy;
    Szz2 = Szz * Szz;

    Sxy2 = Sxy * Sxy;
    Syz2 = Syz * Syz;
    Sxz2 = Sxz * Sxz;

    Syx2 = Syx * Syx;
    Szy2 = Szy * Szy;
    Szx2 = Szx * Szx;

    SyzSzymSyySzz2 = 2.0*(Syz*Szy - Syy*Szz);
    Sxx2Syy2Szz2Syz2Szy2 = Syy2 + Szz2 - Sxx2 + Syz2 + Szy2;

    C[2] = -2.0 * (Sxx2 + Syy2 + Szz2 + Sxy2 + Syx2 + Sxz2 + Szx2 + Syz2 + Szy2);
    C[1] = 8.0 * (Sxx*Syz*Szy + Syy*Szx*Sxz + Szz*Sxy*Syx - Sxx*Syy*Szz - Syz*Szx*Sxy - Szy*Syx*Sxz);

    SxzpSzx = Sxz + Szx;
    SyzpSzy = Syz + Szy;
    SxypSyx = Sxy + Syx;
    SyzmSzy = Syz - Szy;
    SxzmSzx = Sxz - Szx;
    SxymSyx = Sxy - Syx;
    SxxpSyy = Sxx + Syy;
    SxxmSyy = Sxx - Syy;
    Sxy2Sxz2Syx2Szx2 = Sxy2 + Sxz2 - Syx2 - Szx2;

    C[0] = Sxy2Sxz2Syx2Szx2 * Sxy2Sxz2Syx2Szx2
            + (Sxx2Syy2Szz2Syz2Szy2 + SyzSzymSyySzz2) * (Sxx2Syy2Szz2Syz2Szy2 - SyzSzymSyySzz2)
            + (-(SxzpSzx)*(SyzmSzy)+(SxymSyx)*(SxxmSyy-Szz)) * (-(SxzmSzx)*(SyzpSzy)+(SxymSyx)*(SxxmSyy+Szz))
            + (-(SxzpSzx)*(SyzpSzy)-(SxypSyx)*(SxxpSyy-Szz)) * (-(SxzmSzx)*(SyzmSzy)-(SxypSyx)*(SxxpSyy+Szz))
            + (+(SxypSyx)*(SyzpSzy)+(SxzpSzx)*(SxxmSyy+Szz)) * (-(SxymSyx)*(SyzmSzy)+(SxzpSzx)*(SxxpSyy+Szz))
            + (+(SxypSyx)*(SyzmSzy)+(SxzmSzx)*(SxxmSyy-Szz)) * (-(SxymSyx)*(SyzpSzy)+(SxzmSzx)*(SxxpSyy-Szz));

    mxEigenV = E0;
    for (i = 0; i < 50; ++i)
    {
        oldg = mxEigenV;
        x2 = mxEigenV*mxEigenV;
        b = (x2 + C[2])*mxEigenV;
        a = b + C[1];
        delta = ((a*mxEigenV + C[0])/(2.0*x2*mxEigenV + b + a));
        mxEigenV -= delta;
        if (fabs(mxEigenV - oldg) < fabs((evalprec)*mxEigenV))
            break;
    }

    if (i == 50)
    {  // fprintf(stderr,"\nIore than %d iterations needed!\n", i);
        mAlign.rmsd=100000;
        return 100000;}


    rms = 2.0 * (E0 - mxEigenV)/len;
    if (fabs(rms) < 0.00001) rms =0.00001;
    if (rms < 0){mAlign.rmsd=100000; return 100000;}
    rms = sqrt(rms);
    rmsd = rms;

    if (minScore > 0)
        if (rms < minScore)
        {
            mAlign.rmsd=rmsd;
            return rmsd; // Don't bother with rotation.
        }

    a11 = SxxpSyy + Szz-mxEigenV; a12 = SyzmSzy; a13 = - SxzmSzx; a14 = SxymSyx;
    a21 = SyzmSzy; a22 = SxxmSyy - Szz-mxEigenV; a23 = SxypSyx; a24= SxzpSzx;
    a31 = a13; a32 = a23; a33 = Syy-Sxx-Szz - mxEigenV; a34 = SyzpSzy;
    a41 = a14; a42 = a24; a43 = a34; a44 = Szz - SxxpSyy - mxEigenV;
    a3344_4334 = a33 * a44 - a43 * a34; a3244_4234 = a32 * a44-a42*a34;
    a3243_4233 = a32 * a43 - a42 * a33; a3143_4133 = a31 * a43-a41*a33;
    a3144_4134 = a31 * a44 - a41 * a34; a3142_4132 = a31 * a42-a41*a32;
    q1 =  a22*a3344_4334-a23*a3244_4234+a24*a3243_4233;
    q2 = -a21*a3344_4334+a23*a3144_4134-a24*a3143_4133;
    q3 =  a21*a3244_4234-a22*a3144_4134+a24*a3142_4132;
    q4 = -a21*a3243_4233+a22*a3143_4133-a23*a3142_4132;

    qsqr = q1 * q1 + q2 * q2 + q3 * q3 + q4 * q4;

    /* The following code tries to calculate another column in the adjoint matrix when the norm of the
       current column is too small.
       Usually this commented block will never be activated.  To be absolutely safe this should be
       uncommented, but it is most likely unnecessary.
    */
    if (qsqr < evecprec)
    {
        q1 =  a12*a3344_4334 - a13*a3244_4234 + a14*a3243_4233;
        q2 = -a11*a3344_4334 + a13*a3144_4134 - a14*a3143_4133;
        q3 =  a11*a3244_4234 - a12*a3144_4134 + a14*a3142_4132;
        q4 = -a11*a3243_4233 + a12*a3143_4133 - a13*a3142_4132;
        qsqr = q1*q1 + q2 *q2 + q3*q3+q4*q4;

        if (qsqr < evecprec)
        {
            double a1324_1423 = a13 * a24 - a14 * a23, a1224_1422 = a12 * a24 - a14 * a22;
            double a1223_1322 = a12 * a23 - a13 * a22, a1124_1421 = a11 * a24 - a14 * a21;
            double a1123_1321 = a11 * a23 - a13 * a21, a1122_1221 = a11 * a22 - a12 * a21;

            q1 =  a42 * a1324_1423 - a43 * a1224_1422 + a44 * a1223_1322;
            q2 = -a41 * a1324_1423 + a43 * a1124_1421 - a44 * a1123_1321;
            q3 =  a41 * a1224_1422 - a42 * a1124_1421 + a44 * a1122_1221;
            q4 = -a41 * a1223_1322 + a42 * a1123_1321 - a43 * a1122_1221;
            qsqr = q1*q1 + q2 *q2 + q3*q3+q4*q4;

            if (qsqr < evecprec)
            {
                q1 =  a32 * a1324_1423 - a33 * a1224_1422 + a34 * a1223_1322;
                q2 = -a31 * a1324_1423 + a33 * a1124_1421 - a34 * a1123_1321;
                q3 =  a31 * a1224_1422 - a32 * a1124_1421 + a34 * a1122_1221;
                q4 = -a31 * a1223_1322 + a32 * a1123_1321 - a33 * a1122_1221;
                qsqr = q1*q1 + q2 *q2 + q3*q3 + q4*q4;

                if (qsqr < evecprec)
                {
                    /* if qsqr is still too small, return the identity matrix. */
                    mAlign.RotMat[0] = mAlign.RotMat[4] = mAlign.RotMat[8] = 1.0;
                    mAlign.RotMat[1] = mAlign.RotMat[2] = mAlign.RotMat[3] = mAlign.RotMat[5] = mAlign.RotMat[6] = mAlign.RotMat[7] = 0.0;
                    mAlign.rmsd=0;
                    return 0;
                }
            }
        }
    }

    normq = sqrt(qsqr);
    q1 /= normq;
    q2 /= normq;
    q3 /= normq;
    q4 /= normq;

    a2 = q1 * q1;
    x2 = q2 * q2;
    y2 = q3 * q3;
    z2 = q4 * q4;

    xy = q2 * q3;
    az = q1 * q4;
    zx = q4 * q2;
    ay = q1 * q3;
    yz = q3 * q4;
    ax = q1 * q2;

    mAlign.RotMat[0] = a2 + x2 - y2 - z2;
    mAlign.RotMat[1] = 2 * (xy + az);
    mAlign.RotMat[2] = 2 * (zx - ay);
    mAlign.RotMat[3] = 2 * (xy - az);
    mAlign.RotMat[4] = a2 - x2 + y2 - z2;
    mAlign.RotMat[5] = 2 * (yz + ax);
    mAlign.RotMat[6] = 2 * (zx + ay);
    mAlign.RotMat[7] = 2 * (yz - ax);
    mAlign.RotMat[8] = a2 - x2 - y2 + z2;
    mAlign.rmsd=rmsd;
    return rmsd;
}





std::string RigidBody::mat3Print() const
{
    ostringstream oss;


    for (int i=0; i<3; i++){
        oss << "[" << mAlign.RotMat.get(i*3+0) << " " <<
                mAlign.RotMat.get(i*3+1) << " " <<
                mAlign.RotMat.get(i*3+2) << "]";
    }
    return oss.str();
}


void RigidBody::loadCoordsToRigid   (const std::vector<const Coords*> &liste)
try{
    for(const size_t& pos:rigidPos)mAlign.sPoolCoo.releaseObject(pos);
    RigidCoords.clear();rigidPos.clear();
    for(const Coords* coo:liste)
    {
        size_t pos;
    Coords& coo2=mAlign.sPoolCoo.acquireObject(pos);
    coo2=*coo;
    RigidCoords.add(coo2);
    rigidPos.push_back(pos);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="060301"
        &&e.getId()!="060302"
        &&e.getId()!="060101");
    throw;
}

void RigidBody::loadCoordsToRigid(const CoordList& liste)
try{
    for(const size_t& pos:rigidPos)mAlign.sPoolCoo.releaseObject(pos);
    RigidCoords.clear();rigidPos.clear();
    for(const Coords& coo:liste)
    {
        size_t pos;
    Coords& coo2=mAlign.sPoolCoo.acquireObject(pos);
    coo2=coo;
    RigidCoords.add(coo2);
    rigidPos.push_back(pos);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="060301"
        &&e.getId()!="060302"
        &&e.getId()!="060101");
    throw;
}

void RigidBody::loadCoordsToMobile(const CoordList& liste)
try{
    for(const size_t& pos:mobilPos)mAlign.sPoolCoo.releaseObject(pos);
    MobilCoords.clear();mobilPos.clear();
    for(const Coords& coo:liste)
    {
        size_t pos;
    Coords& coo2=mAlign.sPoolCoo.acquireObject(pos);
    coo2=coo;
    MobilCoords.add(coo2);
    mobilPos.push_back(pos);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="060301"
        &&e.getId()!="060302"
        &&e.getId()!="060101");
    throw;
}



void RigidBody::loadCoordsToMobile(const std::vector<const Coords*>& liste)
try{
    for(const size_t& pos:mobilPos)mAlign.sPoolCoo.releaseObject(pos);
    MobilCoords.clear();mobilPos.clear();
    for(const Coords* coo:liste)
    {
        size_t pos;
    Coords& coo2=mAlign.sPoolCoo.acquireObject(pos);
    coo2=*coo;
    MobilCoords.add(coo2);
    mobilPos.push_back(pos);
    }

}catch(ProtExcept &e)
{
    assert(e.getId()!="060301"
        &&e.getId()!="060302"
        &&e.getId()!="060101");
    throw;
}


