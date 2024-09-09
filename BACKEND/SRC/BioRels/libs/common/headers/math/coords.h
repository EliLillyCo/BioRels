#ifndef COORDS_H
#define COORDS_H

#include <stdlib.h>
#include <ostream>


namespace protspace
{

/**
 * @brief Describe 3D coordinate object
 */
class Coords
{
protected:
  /**
   * @brief typedouble on x axis
   */
  double _x;

  /**
   * @brief typedouble on y axis
   */
  double _y;

  /**
   * @brief typedouble on z axis
   */
  double _z;

  /**
   * @brief Set the default value of _x, _y,_z to 0
   */
  void _default_values ();

public:







////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////// CONSTRUCTOR /////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////



  /**
   * @brief Constructor calling Space_Vector::_default_values()
   */
  Coords();






  ~Coords();







////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////// GETTERS ///////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  /**
   * @brief Getter returning a reference of x
   * @return reference of x
   */
    inline const double & x ()const { return _x; }

  /**
   * @brief Getter returning a reference of y
   * @return reference of y
   */
    inline  const double & y () const { return _y; }


  /**
   * @brief  Getter returning a reference of z
   * @return reference of z
   */
    inline const double & z () const{ return _z; }

  /**
   * @brief set x y z values
   * @param x value set to x
   * @param y value set to y
   * @param z value set to z
   * @test testCoords::testAssignment
   */
  void setxyz (const double& x, const double& y, const double& z);

  /**
   * @brief Copy the value given in parameter into this object
   * @param rhs value to copy
   * @test testCoords::testAssignment
   */
  void setxyz (const Coords &rhs);





  inline const double& getX()const {return _x;}
  inline const double& getY()const {return _y;}
  inline  const double& getZ() const{return _z;}



    inline void setX(const double& x){_x=x;}
    inline void setY(const double& y){_y=y;}
    inline void setZ(const double& z){_z=z;}


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// FUNCTIONS //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


  /**
   * @brief Add to each axis the given values
   * @test testCoords::testGeom
   */
  void add (const double&, const double&, const double&);

  /**
   * @brief Translate this object with the given values
   * @param tx value to translate over x axis
   * @param ty value to translate over y axis
   * @param tz value to translate over z axis
   * @test testCoords::testGeom
   */
  void translate (const double& tx,const double& ty,const double& tz)
  { Coords::add (tx, ty, tz);}



  /**
   * @brief Translate this object with the given values of rhs
   * @param rhs Vector used for translation
   * @test testCoords::testGeom
   */
  void translate (const Coords &rhs);


  /**
   * @brief normalise the vector so that is norm equal 1
   * @test testCoords::testGeom
   */
  void  normalise ();

  /**
   * @brief Return the distance between this point and the origin
   * @return a distance
   * @image html distance.png "Distance formula" width=10cm
   * @test testCoords::testGeom
   */
 double norm () const;

  /**
   * @brief Synonym of norm()
   * @return a distance
   * @deprecated redundant with norm()
   * @test testCoords::testGeom
   */
 double length () const { return norm ();}

  /**
   * @brief Return the norm squared
   * @return norm squared
   * @test testCoords::testGeom
   */
 double normsquared () const { return _x * _x + _y * _y + _z * _z;}



  /**
   * @brief give the angle between this and other. the origin is used for both vectors
   * @param other
   * @return angle between other and this
   * @todo ASK IAN
   * @test testCoords::testGeom
   */
  double     angle_between              (const Coords &other) const;

  /**
   * @brief gives the angle between this and other
   * @return angle
   * @test testCoords::testGeom
   */
  double     angle_between_unit_vectors (const Coords &) const;

  /**
   * @brief Get the distance between this coordinates and "Other" coordinates
   * @param other Space_Vector to get the distance with
   * @return distance
   * @test testCoords::testGeom
   */
 double           distance (const Coords &other ) const;

  /**
   * @brief Get the distance between this and "Other" coordinates as long as it's below the given threshold
   * @param other Space_Vector to get the distance with
   * @param thres Distance threshold
   * @return actual distance when below thres, thres otherwise
   *
   * To speed up calculation, the distance calculation can be stop if it's
   * above a given threshold.
   */
 double distance(const Coords &other, const double& thres) const;

  /**
   * @brief Give the square of the distance between this and "Other" coordinates
   * @param other Space_Vector to get the distance with
   * @return square of the distance
   */
 double           distance_squared (const Coords &  other) const;

  /**
   * @brief Make the dot product between this and "other coordinates"
   * @image html dotProduct.png "Dot Product Formula" width=200px
   * @return dot product value
   */
 double           dot_product (const Coords &) const;


  /**
   * @brief Generate the unit vector starting from origin
   * @return unit vector
   *
   * This function consider this set of coordinates as a vector starting from
   * the origin. It call Space_Vector::normalise() so that the norm of the
   * vector equals one, and return the normalise vector
   *
   */
  Coords form_unit_vector (const Coords &) const;



  /**
   * @brief Give the angle (in radian) of a1 - this = a2
   * @param a1 : First coordinates used
   * @param a2 : Second coordinates used
   * @return angle (in radian)
   * @test test_coord::testGeom
   */
  double angle_between(const Coords & a1,
                             const Coords & a2) const;

  /**
   * @brief Returns true when dist(this,other) < thres
   * @param other Set of coordinates from which the distance will be calculate
   * @param thres Distance threshold the distance must not go above
   * @return true when the distance this with other is lower than threshold
   * @test test_coord::testGeom
   */
  bool closer_than (const Coords &other , const double &thres) const;



  /**
   * @brief Generate the cross product betwen this and "other" vector
   * @param other : vector used to generate the dot product
   * @test test_coord::testGeom
   * The dot product is save in the calling object
   */
  void        cross_product (const Coords &other);


  /**
   * @brief Generate the cross product betwen this and "other" vector
   * @param other : vector used to generate the dot product
   * @test test_coord::testGeom
   * @return  the dot product
   */
  Coords get_cross_product (const Coords &other) const;



////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// OPERATORS //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
  /**
   * @brief operator =
   * @param other : Coordinates to assign this object to
   * @return a copy of ther
   */
  Coords & operator =  (const Coords & other);


  /**
   * @brief Add "other" constant to this Space_Vector
   * @param other constant to add
   * @return sum between this and the constant
   */
  Coords operator +  (const double &other) const;

  /**
   * @brief Add "Other" to this Space_Vector
   * @param other constant Space_Vector to add
   * @return sum between this and the constant
   */
  Coords operator +  (const Coords &other) const;

  /**
   * @brief operator - :Substract a constant
   * @param other constant to substract
   * @return this - "other"
   */
  Coords operator -  (const double& other) const;

  /**
   * @brief operator - : Substraction
   * @return Difference between this and "other" values
   */
  Coords operator -  (const Coords &) const;


  /**
   * @brief Iultiply this by a constant
   * @return The multiplication result
   */
  Coords operator *  (const double&) const;
  /**
   * @brief Divide this by a constant
   * @param other Constant used to divide this coordinates
   * @return results of the division
   * @warning Program will stop when other equal 0
   */
  Coords operator /  (const double& other) const;

  /**
   * @brief Iake the cross product with "other" and save the result in this coordinate
   * @return this object
   */
  Coords & operator *= (const Coords &);

  /**
   * @brief Cross product
   * @return Cross product value
   */
  Coords operator *  (const Coords &) const;

  /**
   * @brief operator - : Invert coordinate sign
   * @return An opposite sign of this 3D coordinates
   */
  Coords operator -  ()const ;

  /**
   * @brief operator +=
   * @param other : value to add on all axis
   * @return add other value to this coordinates
   */
  void        operator += (const double& other);
  /**
   * @brief operator +=
   * @param other : Coordinates to add
   * @return add other coordinates to this coordinates
   */
  void        operator += (const Coords & other);

  /**
   * @brief Value substrcted to all coordinates axis
   * @param other : value to substract
   * @return substract other value to this coordinates
   */
  void        operator -= (const double &other );

  /**
   * @brief operator -=
   * @param other : Coordinates to substract
   * @return substract other coordinates to this coordinates
   */
  void        operator -= (const Coords & other);

  /**
   * @brief operator *=
   * @param scale : value to multiply by
   * @return multiply "scale" value to this coordinates
   */
  void        operator *= (const double& scale);


  /**
   * @brief operator /=
   * @param scale : Coordinates to divide
   * @return divde this coordinates by "scale" value
   */
  void        operator /= (const double& scale);

  /**
   * @brief Generate the dot product
   * @param other Space vector used to make the dot product
   * @return doc product
   */
 double           operator ^ (const Coords &other) const;

  /**
   * @brief Is all Space_vector components equal ?
   * @param other Space_vector to compare to
   * @return true when equal, false otherwise
   */
  int         operator == (const Coords &other)const ;

  /**
   * @brief Is any Space_vector components differs ?
   * @param other Space_vector to commpare to
   * @return true when at least one component differs
   */
  int         operator != (const Coords & other)const ;

  double& operator[](int);
    /**
     * @brief Constructor with initial parameters
     * @param x : value on x axis
     * @param y : value on y axis
     * @param z : value on z axis
     * @param normalize : Normalize the vector
     * @author Ian Watson
     * @author Jeremy Desaphy
     */

    Coords(const double&x, const double& y, const double& z, const bool& normalize=false);
    /**
     * @brief Copy constructor
     * @param pos: Coords object to copy
     * @param normalize : Normalize the vector
     * @author Ian Watson
     * @test testCoords::testAssignment
     */
    Coords(const Coords& pos, const bool& normalize=false);

    /**
     * @brief Constructor setting all x,y,z values to val
     * @param val : Coordinates to set to
     * @author Ian Watson
     * @test testCoords::testAssignment
     */
    Coords(const double&val);

    /**
     * @brief Copy constructor
     * @param a : initial value to recopy
     * @author Ian Watson
     * @test testCoords::testAssignment
     */


    /**
     * @brief Rotation of rad radian over x axis
     * @param rad : value in radian to rotate
     * @author JD
     * @test testCoords::testRotation
     */
    void rotX(const double& rad);

    /**
     * @brief Rotation of rad radian over y axis
     * @param rad : value in radian to rotate
     *  @author JD
     * @test testCoords::testRotation
     */
    void rotY(const double& rad);

    /**
     * @brief Rotation of rad radian over z axis
     * @param rad : value in radian to rotate
     * @author JD
     * @test testCoords::testRotation
     */
    void rotZ(const double& rad);

    /**
     * @brief Rotation of radx radian over x axis, rady over y axis, radx over z
     * @param radx : value in radian to rotate over x axis
     * @param rady : value in radian to rotate over y axis
     * @param radz : value in radian to rotate over z axis
     * @author JD
     * @test testCoords::testRotation
     */
    void rotation(const double& radx,const double& rady,const double& radz);

    /**
     * @brief Change the coordinates so that x,y,z will be positive
     * @test testCoords::testAssignment
     */
    void toPositive();
    /**
     * @brief Return the normal vector between this object A, C and B
     * @param B : Position to consider to make a vector AB
     * @param C : Position to consder to make a vector AC
     * @return the normal vector AB AC
     * @author JD
     * @test testCoords::testGeom
     */
    Coords getNormal(const Coords& B, const Coords& C) const;


    /**
     * @brief Returns a normalized direction vector from this point to another
     * @param coord1 : Coordinates used for direction vector
     * @return normaliwed direction vector
     * @author JD
     * @test testCoords::testGeom
     */
    Coords directionVector(const Coords &coord1) const ;


    /**
     * @brief determines the angle between a vector to this point from the origin and a vector to the other point.
     * @param coord1 : Coordinates of the other point
     * @return unsigned angle between 0 and M_PI
     * @test testCoords::testGeom
     *
     */
    double angleTo(const Coords &coord1) const;


    /**
     * @brief Set all coordinates to 0
     * @test testCoords::testAssignment
     */
    void clear();

    /**
     * @brief Exchange this set of coordinate with the one given in parameter
     * @param coord : Coordinate to change with
     * @test test_coords::testAssignment
     */
    void swap(Coords& coord);



};

}
std::ostream & operator << (std::ostream & os, const protspace::Coords &qq);
#endif // COORDS_H
