#ifndef MMRING_UTILS_H
#define MMRING_UTILS_H

#include <vector>
#include "headers/math/coords.h"
#include "headers/math/matrix.h"
#include "headers/statics/protpool.h"
namespace protspace
{

class MMAtom;
class MMRing;
class MacroMole;
struct ring_atm_inf
{
    DMatrix mRotMatrix;
    double mDSide;
    double mRc;
    double mTheta;
    double mHeight;
    protspace:: CoordPoolObj mCz,mCy,mCyz;
    protspace:: CoordPoolObj mVect_i, mVect_j, mVect_k;
    ring_atm_inf():
        mRotMatrix(3,3,0),
        mDSide(0),
        mRc(0),
        mTheta(0){
        mCz.obj.clear();
        mCy.obj.clear();
        mCyz.obj.clear();
        mVect_i.obj.clear();
        mVect_j.obj.clear();
        mVect_k.obj.clear();
    }
};
bool shareAtoms(const MMRing& pRing1, const MMRing& pRing2);
bool isAllAtomInRing(const MMRing&, const std::vector<const MMAtom*>& liste );
bool hasFusedAtom(const MMRing& ring, const MacroMole&mole);
void getAtomRings(const MMAtom& atom, std::vector<MMRing*>& list);
    bool isAtomCloserThan(const MMAtom& pAtom, const MMRing& pRing, const double& pDist);
bool hasOverlappingAtoms(const MMRing& ring1,
                         const MMRing& ring2,
                         std::vector<MMAtom*>& list);
void turnToAromaticBonds(MacroMole& mole);
bool isAtomInAromaticRing(const MMAtom& atom);
void fuseRingSystems(MacroMole& mole);
MMAtom& getClosestRingAtom(const Coords& atom,const MMRing& ring);
void getVectorsForRingAtom(const Coords& atom,
                           const MMRing& ring,
                           Coords& vect_i,
                           Coords& vect_j,
                           Coords& vect_k);

MMRing& getRingFromCenter(MMAtom& pAtom);
size_t getRingPosFromCenter(const MMAtom& pAtom);
void getMaskInRing(bool isInRing[], const MacroMole& mole);
void turnToAromaticRing(MMRing& ring);
void processRingAtomInfo(const MMRing& pRing,const Coords& pCoo,ring_atm_inf& data);
    double getClosestDist(const MMAtom& pAtom, const MMRing& pRing, MMAtom *&best);

    /**
     * \brief Remove all ring from molecules that are shared across multiple residues
     *
     * In a crystal structure, it is very unlikely to have a ring system across different residues
     * even more an aromatic one. If these rings are found, this function will delete them.
     */
    void cleanProteinRings(MacroMole& pMole);



    /**
     * @brief Get the distance between the center of the called ring and the given ring
     * @param ring Ring to consider
     * @return Distance between the center of this ring and the given ring
     */
    double getDistCenter(const MMRing& ring1, const protspace::MMRing &ring2);



    /**
     * @brief Get the closest distance between two rings
     * @param ring Ring to consider
     * @return Distance in Angstroems
     *
     * Perform an all-against-all atom distance to find the shortest one.
     *
     */
    double getClosestDistance(const MMRing& ring1,const MMRing& ring2);




    /**
     * @brief Get the closest atom of the ring against the given ring
     * @param ring Ring to consider
     * @return Closest atom to the given ring
     *
     * Perform an all against all atom distance to find the atom of the
     * current ring that is the closest of the given ring
     *
     */
    const MMAtom& getClosestAtom(const MMRing& ringFrom, const MMRing& ringAgainst);


}
#endif // MMRING_UTILS_H

