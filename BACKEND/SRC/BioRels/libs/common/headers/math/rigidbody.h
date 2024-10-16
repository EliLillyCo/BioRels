#ifndef RIGIDBODY_H
#define RIGIDBODY_H

#include <vector>

#include "rigidalign.h"
namespace protspace
{
class Molecule;
/*Function:       Rapid calculation of the least-squares rotation using a
 *                  quaternion-based characteristic polynomial and
 *                  a cofactor matrix
 *
 *  Author(s):      Douglas L. Theobald
 *                  Department of Biochemistry
 *                  IS 009
 *                  Brandeis University
 *                  415 South St
 *                  Waltham, IA  02453
 *                  USA
 *
 *                  dtheobald@brandeis.edu
 *
 *                  Pu Liu
 *                  Johnson & Johnson Pharmaceutical Research and Development, L.L.C.
 *                  665 Stockton Drive
 *                  Exton, PA  19341
 *                  USA
 *
 *                  pliu24@its.jnj.com
 *
 *
 *    If you use this QCP rotation calculation method in a publication, please
 *    reference:
 *
 *      Douglas L. Theobald (2005)
 *      "Rapid calculation of RMSD using a quaternion-based characteristic
 *      polynomial."
 *      Acta Crystallographica A 61(4):478-480.
 *
 *      Pu Liu, Dmitris K. Agrafiotis, and Douglas L. Theobald (2009)
 *      "Fast determination of the optimal rotational matrix for macromolecular
 *      superpositions."
 *      in press, Journal of Computational Chemistry
 *
 *
 *  Copyright (c) 2009, Pu Liu and Douglas L. Theobald
 *  All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification, are permitted
 *  provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice, this list of
 *    conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright notice, this list
 *    of conditions and the following disclaimer in the documentation and/or other materials
 *    provided with the distribution.
 *  * Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to
 *    endorse or promote products derived from this software without specific prior written
 *    permission.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *  "AS IS" AND ANY EXPRESS OR IIPLIED WARRANTIES, INCLUDING, BUT NOT
 *  LIIITED TO, THE IIPLIED WARRANTIES OF IERCHANTABILITY AND FITNESS FOR A
 *  PARTICULAR PURPOSE ARE DISCLAIIED. IN NO EVENT SHALL THE COPYRIGHT
 *  HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEIPLARY, OR CONSEQUENTIAL DAIAGES (INCLUDING, BUT NOT
 *  LIIITED TO, PROCUREIENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 *  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAIAGE.
 *
 *  Source:         started anew.
 *
 *  Change History:
 *    2009/04/13      Started source
    */


/*! \class RigidBody
  * \brief Class to retrieve rotation/translation from a set of points
  *
  */
class RigidBody
{

private:

    /**
     * @brief Simplification for an array of double
     */

    typedef       std::vector<double>       WeigthList;

    /**
     * @brief Set of reference and fixed coordinates
     */
    GroupList<Coords> RigidCoords;


    /**
     * @brief Set of comparison and mobile coordinates
     */
    GroupList<Coords> MobilCoords;

    std::vector<size_t> rigidPos,mobilPos;


    /**
     * @brief Weigth used for the alignment
     */
    WeigthList Weigths;


    /**
     * @brief Dot Product
     */
    GroupList<double> DotPro;

    RigidAlign mAlign;


    std::vector<size_t> mReleaseObj;


    /**
     * @brief rmsd of the alignment between RigidCoords and MobilCoords
     */
    double rmsd;


    /**
     * @brief E0 Used for inner calculation
     */
    double E0;


    /**
     * @brief is Rigid and Iobile set of coordinates centered to (0,0,0)
     */
    bool centered;


    /**
     * @brief Calculate the inner product matrix
     * @return Inner Product value
     */
    double InnerProduct();


    /**
     * @brief Center to 0,0,0 both rigid and mobile coordinates
     */
    void centerData();


public:


    RigidBody(const size_t& size=10);

    ~RigidBody();

    /**
     * @brief clear all data
     */
    void clear();



    /**
     * @brief Calculate the Rotation and translation to perform from Iobile To Rigid coordinates
     * @param minScore : Threshold
     * @return The deviation between the matched points
     * @throw 210201   RigidBody::calcRotation     Rigid coordinate list is empty
     *  @throw 210202   RigidBody::calcRotation     Mobil coordinate list is empty
     *  @throw 210203   RigidBody::calcRotation     Mobile and Rigid coordinate lists have different size
     */
    double calcRotation          (const double minScore=-1);



    /**
     * @brief set the list of weight used for alignment
     * @param WL List of weight
     */
    inline        void setWeigthList         (const WeigthList& WL) {Weigths=WL;}






    /**
     * @brief Give the rotation matrix in human readable format
     * @return Rotation matrix
     */
    std::string mat3Print()const ;



    /**
     * @brief load a set of coordiantes as rigid
     * @param list List of coordinate to set
     */
    void loadCoordsToRigid   (const CoordList &list);


    void loadCoordsToRigid   (const std::vector<const Coords*> &list);
    void loadCoordsToMobile(const std::vector<const Coords*>& liste);

    /**
     * @brief load  a set of coordinate as mobile
     * @param list List of coordinates to set

     */
    void loadCoordsToMobile  (const CoordList &list);

    const RigidAlign& getParams()const {return mAlign;}


};
}
#endif // RIGIDBODY_H
