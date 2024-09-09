#ifndef COORDS_UTILS_H
#define COORDS_UTILS_H

#include "coords.h"
#include "headers/math/matrix.h"


namespace protspace
{

/**
 * @brief Compute dihedral angle between two planes
 * @param pt1 : First Coordinates
 * @param pt2 : Second Coordinates
 * @param pt3 : Third Coordinates
 * @param pt4 : Fourth Coordinates
 * @return computed angle between 0 and PI
 * @test testCoords::testGeom
 *
 * given a  set of four pts in 3D compute the dihedral angle between the
 * plane of the first three points (pt1, pt2, pt3) and the plane of the
 * last three points (pt2, pt3, pt4)
 */
double computeDihedralAngle(const Coords &pt1, const Coords &pt2,
                            const Coords &pt3, const Coords &pt4) ;



/**
 * @brief Compute signed dihedral angle between two planes
 * @param pt1 : First Coordinates
 * @param pt2 : Second Coordinates
 * @param pt3 : Third Coordinates
 * @param pt4 : Fourth Coordinates
 * @return computed angle is between -PI and PI
 * @test testCoords::testGeom
 * @todo Make test where the sign is different between computeDihedralAngle and \
 * computeSignedDihedralAngle
 * given a  set of four pts in 3D compute the signed dihedral angle between the
 * plane of the first three points (pt1, pt2, pt3) and the plane of the
 * last three points (pt2, pt3, pt4)
 */
double computeSignedDihedralAngle(const Coords &pt1, const Coords &pt2,
                                  const Coords &pt3, const Coords &pt4) ;


double computeTripleProduct(Coords coo1,
                      Coords coo2,
                       Coords coo3);

void rotate(Coords& rotpos, const Matrix<double>&matrix);

void calcMatrixFromVector(const Coords& vect_i,
                          const Coords& vect_j,
                          const Coords& vect_k,
                          Matrix<double>& matrix);

class CoordPoolObj;
void distribute(std::vector<CoordPoolObj>& list,const size_t count=120);
void distributes(std::vector< protspace::Coords>& list,const size_t count=120);
}
#endif // COORDS_UTILS_H

