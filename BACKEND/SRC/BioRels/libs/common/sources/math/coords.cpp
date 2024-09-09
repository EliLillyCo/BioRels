

#include <stdlib.h>
#include <iostream>
#include <algorithm>
#undef NDEBUG /// Active assertion in release
#include <assert.h>
#include <math.h>
#include "headers/math/coords.h"
using namespace std;




void
protspace::Coords::_default_values ()
{
  _x = _y = _z =  (0.0);

  return;
}


protspace::Coords::Coords ()
{
#ifdef DEBUG_SPACE_VECTOR
  cerr << "In default constructor " << this << endl;
#endif

  _default_values ();
}


protspace::Coords::Coords(const double& x,
                                       const double& y,
                                       const double& z,
                                       const bool& pNorm):
    _x(x),_y(y),_z(z)
{
    if (pNorm) normalise();
}

protspace::Coords::Coords(const double&val):
    _x(val),_y(val),_z(val)
    {}

protspace::Coords::Coords(const Coords& pos, const bool& pNorm):
    _x(pos.x()),_y(pos.y()),_z(pos.z())
{ if (pNorm) normalise();}




protspace::Coords::~Coords ()
{
#ifdef DEBUG_SPACE_VECTOR
  cerr << "descructor called for " << this << endl;
#endif

//_default_values ();   no reason to reset these
}


protspace::Coords &
protspace::Coords::operator = (const Coords & rhs)
{
#ifdef DEBUG_SPACE_VECTOR
  cerr << "Assignment operator between " << this << " and " << &rhs << endl;
#endif

  _x = rhs._x;
  _y = rhs._y;
  _z = rhs._z;

  return *this;
}


void
protspace::Coords::normalise ()
{
#ifdef DEBUG_SPACE_VECTOR
  cerr << "Normalising " << this << endl;
#endif

  const double mynorm = sqrt (
                        (_x) * (_x)
                      + (_y) * (_y)
                      + (_z) * (_z));

  if (mynorm > 0.0)
  {
    _x = (_x / mynorm);
    _y = (_y / mynorm);
    _z = (_z / mynorm);
  }

  return;
}


double
protspace::Coords::norm () const
{
  return (
              sqrt ( (_x) *  (_x) +
                     (_y) *  (_y) +
                     (_z) *  (_z)));


}


void
protspace::Coords::setxyz (const double&x, const double&y, const double&z)
{
  _x = x;
  _y = y;
  _z = z;

  return;
}

void
protspace::Coords::setxyz (const Coords & rhs)
{
  _x = rhs._x;
  _y = rhs._y;
  _z = rhs._z;

  return;
}


double
protspace::Coords::operator ^ (const Coords & v2) const
{
  return _x * v2._x + _y * v2._y + _z * v2._z;
}


protspace::Coords
protspace::Coords::operator * (const Coords & v2) const
{
  const Coords result (_y * v2._z - _z * v2._y,
                                _z * v2._x - _x * v2._z,
                                _x * v2._y - _y * v2._x);
  return result;
}


protspace::Coords &
protspace::Coords::operator *= (const Coords & v2)
{
  cross_product (v2);

  return *this;
}


protspace::Coords
protspace::Coords::operator + (const double& extra) const
{
  Coords result (_x + extra, _y + extra, _z + extra);

  return result;
}


void
protspace::Coords::operator += (const double&extra)
{
  _x += extra;
  _y += extra;
  _z += extra;
}


void
protspace::Coords::operator += (const Coords & v2)
{
  _x += v2._x;
  _y += v2._y;
  _z += v2._z;

  return;
}


void
protspace::Coords::add (const double& xx, const double& yy, const double& zz)
{
  _x += xx;
  _y += yy;
  _z += zz;
}


void
protspace::Coords::translate (const Coords & whereto)
{
  _x += whereto._x;
  _y += whereto._y;
  _z += whereto._z;

  return;
}


protspace::Coords
protspace::Coords::operator * (const double& factor) const
{
  const Coords result ( _x * factor, _y * factor, _z * factor);

  return result;
}


void
protspace::Coords::operator *= (const double& factor)
{
  _x *= factor;
  _y *= factor;
  _z *= factor;
}


void
protspace::Coords::operator /= (const double& factor)
{
    assert(factor !=0);
  _x /= factor;
  _y /= factor;
  _z /= factor;
}


protspace::Coords
protspace::Coords::operator / (const double&factor) const
{
  assert ( (0.0) != factor);

  const Coords result (_x / factor, _y / factor, _z / factor);

  return result;
}


protspace::Coords
protspace::Coords::operator + (const Coords & v2) const
{
  const Coords result (_x + v2._x, _y + v2._y, _z + v2._z);

  return result;
}


protspace::Coords
protspace::Coords::operator - (const double&extra) const
{
  const Coords result (_x - extra, _y - extra, _z - extra);

  return result;
}


void
protspace::Coords::operator -= (const double& extra)
{
  _x -= extra;
  _y -= extra;
  _z -= extra;

  return;
}


void
protspace::Coords::operator -= (const Coords & v2)
{
  _x -= v2._x;
  _y -= v2._y;
  _z -= v2._z;

  return;
}


protspace::Coords
protspace::Coords::operator - (const Coords & v2) const
{
  const Coords result (_x - v2._x, _y - v2._y, _z - v2._z);

  return result;
}


int
protspace::Coords::operator == (const Coords & v2) const
{
  return (_x == v2._x && _y == v2._y && _z == v2._z);
}

// not equal if any components differ


int
protspace::Coords::operator != (const Coords & v2) const
{
  return (_x != v2._x || _y != v2._y || _z != v2._z);
}


protspace::Coords
protspace::Coords::operator - () const
{
   const Coords result (-_x, -_y, -_z);

  return result;
}


double
protspace::Coords::angle_between_unit_vectors (const Coords & v1) const
{
    // the dot product
        double tmp =  (_x) *  (v1._x) +
                      (_y) *  (v1._y) +
                      (_z) *  (v1._z);

  if (fabs (tmp) <= 1.0)    // that's good
    ;
  else if (fabs (tmp) < 1.00001)
  {
//  cerr << "Space_Vector::angle_between_unit_vectors:numerical roundoff discarded " << (fabs (tmp) - 1.0) << endl;
    tmp = 1.0;
  }
  else
  {
    cerr << "Space_Vector::angle_between_unit_vectors:vector might not be a unit vector, tmp = " << tmp <<"\t"<<v1<<"\t"<<*this<< endl;
    abort ();
  }

  return static_cast<double> (acos (tmp));
}


double
protspace::Coords::angle_between (const Coords & v1) const
{
  const Coords lhs (_x, _y, _z,true);
  const Coords rhs (v1._x, v1._y, v1._z,true);

  return lhs.angle_between_unit_vectors (rhs);
}


void
protspace::Coords::cross_product (const Coords & v2)
{
  const double xorig(_x);
  const double yorig(_y);
  const double zorig(_z);

  _x = (yorig *  (v2._z) -  zorig *  (v2._y));
  _y = (zorig *  (v2._x) -  xorig *  (v2._z));
  _z = (xorig *  (v2._y) -  yorig *  (v2._x));

  return;
}


protspace::Coords
protspace::Coords::get_cross_product (const Coords & v2) const
{
    const double xorig(_x);
    const double yorig(_y);
    const double zorig(_z);
    const Coords results(
    (yorig *  (v2._z) -  zorig *  (v2._y)),
    (zorig *  (v2._x) -  xorig *  (v2._z)),
    (xorig *  (v2._y) -  yorig *  (v2._x)));

    return results;
}



double
protspace::Coords::distance (const Coords & rhs) const
{
  return (sqrt ( (_x - rhs._x) *  (_x - rhs._x) +
                               (_y - rhs._y) *  (_y - rhs._y) +
                               (_z - rhs._z) *  (_z - rhs._z)));
}

double
protspace::Coords::distance_squared (const Coords & rhs) const
{
  return ( (_x - rhs._x) *  (_x - rhs._x) +
                         (_y - rhs._y) *  (_y - rhs._y) +
                         (_z - rhs._z) *  (_z - rhs._z));
}


double
protspace::Coords::dot_product (const Coords & rhs) const
{
  return _x * rhs._x + _y * rhs._y + _z * rhs._z;
}


protspace::Coords
protspace::Coords::form_unit_vector (const Coords & rhs) const
{
#ifdef DEBUG_SPACE_VECTOR
  cerr << "In form_unit_vector from " << this << " to " << &rhs << endl;
#endif

  const Coords rc (_x - rhs._x, _y - rhs._y, _z - rhs._z,true);
  return rc;
}


ostream &
operator << (ostream & os, const protspace::Coords & qq)
{
  os << "(" << qq.x () << "," << qq.y () << "," << qq.z () << ")";

  return os;
}

#ifdef IAN_WORK
static angle_t
internal_angle_between (double a,
                        double b,
                        double c)
{
  double x = (a * a - b * b - c * c) / (2.0 * b);

  double tmp = (b + x) / a;

  if (tmp > 1.0)
    return static_cast<angle_t> (0.0);
  else if (tmp < -1.0)
    return static_cast<angle_t> (M_PI * 0.5);

  return static_cast<angle_t>(acos (tmp));
}
#endif


double
protspace::Coords::angle_between (const Coords & a1,
                                const Coords & a2) const
{
  const Coords ab (a1.x () - _x, a1.y () - _y, a1.z () - _z,true);
  const Coords ac (a2.x () - _x, a2.y () - _y, a2.z () - _z,true);


  return ab.angle_between_unit_vectors (ac);
}





bool
protspace::Coords::closer_than (const Coords & rhs, const double& d) const
{
  double sum = (0.0);

  double q = fabs(_x - rhs._x); if (q > d) return false; sum += q * q;
    q = fabs(_y - rhs._y); if (q > d) return false; sum += q * q;

  if (sqrt(sum) > d) return false;

  q = fabs(_z - rhs._z);  if (q > d) return false;  sum += q * q;

  return sqrt(sum) <= d;
}



double protspace::Coords::distance(const Coords &rhs, const double& d) const
{
    double sum = (0.0);

    double q = fabs(_x - rhs._x); if (q > d) return d; sum += q * q;
      q = fabs(_y - rhs._y); if (q > d) return d; sum += q * q;

    if (sqrt(sum) > d) return d;

    q = fabs(_z - rhs._z);  if (q > d) return d;  sum += q * q;

    return sqrt(sum);
}





void protspace::Coords::rotX(const double& rad)
{
    const double fx = _x;
    const double fy = cos(rad)*_y-sin(rad)*_z;
    const double fz = sin(rad)*_y+cos(rad)*_z;
    _x=fx;
    _y=fy;
    _z=fz;
}

void protspace::Coords::rotY(const double& rad)
{
    const double fx = cos(rad)*_x+sin(rad)*_z;
    const double fy = _y;
    const double fz = -sin(rad)*_x+cos(rad)*_z;
    _x=fx;
    _y=fy;
    _z=fz;
}


void protspace::Coords::rotZ(const double& rad)
{
    const double fx = cos(rad)*_x-sin(rad)*_y;
    const double fy = sin(rad)*_x+cos(rad)*_y;
    const double fz = _z;
    _x=fx;
    _y=fy;
    _z=fz;
}


void protspace::Coords::rotation(const double& radx,
                                  const double& rady,
                                  const double& radz)
{
    rotX(radx) ; rotY(rady); rotZ(radz);
}



void protspace::Coords::toPositive()
{
    if (_x <0) _x=-_x;
    if (_y <0) _y=-_y;
    if (_z <0) _z=-_z;

}

protspace::Coords
protspace::Coords::getNormal(const Coords& B,
                              const Coords& C)
const
{
    const Coords V1(B._x-_x,B._y-_y,B._z-_z);
    const Coords V2(C._x-_x,C._y-_y,C._z-_z);
    Coords res(V1._y*V2._z-V1._z*V2._y,
               V1._z*V2._x-V1._x*V2._z,
               V1._x*V2._y-V1._y*V2._x);
    res.normalise();
    res._x+=_x;
    res._y+=_y;
    res._z+=_z;
    return res;

}
protspace::Coords
protspace::Coords::directionVector(const Coords &coord1)
const
{
  Coords res(coord1._x - _x,
                         coord1._y - _y,
                         coord1._z - _z);
  res.normalise();
  return res;

}

double protspace::Coords::angleTo(const Coords &coord1) const {
  Coords t1(*this),
         t2(coord1);

  t1.normalise();
  t2.normalise();
  double dotProd = t1.dot_product(t2);
  // watch for roundoff error:
  if(dotProd<-1.0) dotProd = -1.0;
  else if(dotProd>1.0) dotProd = 1.0;
  return acos(dotProd);
}

void protspace::Coords::clear()
{
    _x=0;
    _y=0;
    _z=0;

}


void protspace::Coords::swap(Coords& coord)
{
    const Coords v(coord);

    coord._x=_x;
    coord._y=_y;
    coord._z=_z;
    _x=v._x;
    _y=v._y;
    _z=v._z;
}

double& protspace::Coords::operator[](int pos)
{
    switch (pos)
    {
    case 0:return _x;break;
    case 1:return _y;break;
    case 2:return _z;break;
    default:assert(1==0);break;
    }
}
