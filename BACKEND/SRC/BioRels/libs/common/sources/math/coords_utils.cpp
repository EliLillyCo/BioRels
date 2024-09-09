#include <cmath>
#include "headers/math/coords_utils.h"
#include "headers/statics/protpool.h"
#undef NDEBUG /// Active assertion in release
namespace protspace
{
double computeSignedDihedralAngle(const     protspace::Coords &pt1, const protspace::Coords &pt2,
                            const protspace::Coords &pt3, const protspace::Coords &pt4)  {
  const protspace::Coords begEndVec(pt3 - pt2);
  const protspace::Coords begNbrVec(pt1 - pt2);
  const protspace::Coords crs1(begNbrVec.get_cross_product(begEndVec));

  const protspace::Coords endNbrVec(pt4 - pt3);
  const protspace::Coords crs2(endNbrVec.get_cross_product(begEndVec));

  double ang = crs1.angleTo(crs2);

  // now calculate the sign:
  const protspace::Coords crs3 = crs1.get_cross_product(crs2);
  const double dot = crs3.dot_product(begEndVec);
  if(dot<0.0) ang*=-1;

  return ang;
}
double computeDihedralAngle(const protspace::Coords &pt1, const protspace::Coords &pt2,
                            const protspace::Coords &pt3, const protspace::Coords &pt4)  {
  const protspace::Coords begEndVec(pt3 - pt2);
  const protspace::Coords begNbrVec(pt1 - pt2);
  const protspace::Coords crs1(begNbrVec.get_cross_product(begEndVec));

  const protspace::Coords endNbrVec(pt4 - pt3);
  const protspace::Coords crs2(endNbrVec.get_cross_product(begEndVec));

  const double ang = crs1.angleTo(crs2);
  return ang;

}


double triple_product(protspace::Coords coo1,
                      protspace::Coords coo2,
                       protspace::Coords coo3)
{
    coo1.normalise();
    coo2.normalise();
    coo3.normalise();
return coo1.x()*(coo2.y()*coo3.z()-coo2.z()*coo3.y())
     + coo1.y()*(coo2.z()*coo3.x()-coo2.x()*coo3.z())
     + coo1.z()*(coo2.x()*coo3.y()-coo2.y()*coo3.x());
}


void rotate(Coords& rotpos, const Matrix<double>&matrix)
{
    rotpos.setxyz(
                matrix.getVal(0,0)*rotpos.x()
               +matrix.getVal(0,1)*rotpos.y()
               +matrix.getVal(0,2)*rotpos.z(),
                matrix.getVal(1,0)*rotpos.x()
               +matrix.getVal(1,1)*rotpos.y()
               +matrix.getVal(1,2)*rotpos.z(),
                matrix.getVal(2,0)*rotpos.x()
               +matrix.getVal(2,1)*rotpos.y()
               +matrix.getVal(2,2)*rotpos.z()
            );
}


void calcMatrixFromVector(const Coords& vect_i,
                          const Coords& vect_j,
                          const Coords& vect_k,
                          Matrix<double>& matrix)
{
    const double

            &A= vect_i.x(),
            &D= vect_i.y(),
            &G= vect_i.z(),
            &B= vect_j.x(),
            &E= vect_j.y(),
            &H= vect_j.z(),
            &C= vect_k.x(),
            &F= vect_k.y(),
            &M= vect_k.z();


    const double detA= A*E*M+B*F*G+C*D*H-C*E*G-F*H*A-M*B*D;
    if (detA==0)
    {
        std::cout <<vect_i<<"\t"<<vect_j<<"\t"<<vect_k<<std::endl;
    }
    assert(detA !=0);
    matrix.setVal(0,0,(E*M-F*H)/detA);
    matrix.setVal(0,1,(C*H-B*M)/detA);
    matrix.setVal(0,2,(B*F-C*E)/detA);
    matrix.setVal(1,0,(F*G-D*M)/detA);
    matrix.setVal(1,1,(A*M-C*G)/detA);
    matrix.setVal(1,2,(C*D-A*F)/detA);
    matrix.setVal(2,0,(D*H-E*G)/detA);
    matrix.setVal(2,1,(B*G-A*H)/detA);
    matrix.setVal(2,2,(A*E-B*D)/detA);
}
void distribute(std::vector<CoordPoolObj>& list,const size_t count)
{
    const double offset =2./count;
    const double halfoffset=offset/2;
    const double increment=M_PI*(3.0-std::sqrt(5.0));
    CoordPoolObj val;
    for(double i=0;i < count;++i)
    {
        // Taken from stackoverflow.com/questions/9600801/evenly-distributing-n-points-on-a-sphere
        // Fibonacci spheres :
        const double y=((i*offset)-1)+halfoffset;
        const double r=std::sqrt(1.0-std::pow(y,2));
        const double phi=(((int)i+1)%120)*increment;
        const double x=std::cos(phi)*r;
        const double z=std::sin(phi)*r;
        val.obj.setxyz(x,y,z);
        // Coordinates of the vector
        list.push_back(val);
    }
}


void distributes(std::vector< protspace::Coords>& list,const size_t count)
{try{
    const double offset =2./count;
    const double halfoffset=offset/2;
    const double increment=M_PI*(3.0-std::sqrt(5.0));

     protspace::Coords val;

    for(double i=0;i < count;++i)
    {
        // Taken from stackoverflow.com/questions/9600801/evenly-distributing-n-points-on-a-sphere
        // Fibonacci spheres :
        const double y=((i*offset)-1)+halfoffset;
        const double r=std::sqrt(1.0-std::pow(y,2));
        const double phi=(((int)i+1)%120)*increment;
        const double x=std::cos(phi)*r;
        const double z=std::sin(phi)*r;
        val.setxyz(x,y,z);
        // Coordinates of the vector
        list.push_back(val);

    }
    }catch(ProtExcept &e)
    {
        std::cerr<<e.toString()<<std::endl;
    }

}

}
