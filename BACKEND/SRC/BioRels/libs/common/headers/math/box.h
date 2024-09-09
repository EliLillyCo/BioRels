#ifndef BOX_H
#define BOX_H

#include "headers/math/coords.h"
#include "headers/statics/grouplist.h"
#include "headers/molecule/mmatom.h"
namespace protspace
{
class Grid;

class Box
{
    friend class Grid;
private:
    size_t mGridPoolPos;
protected:
    Grid* mParent;
    /**
     * @brief Coordinates in the grid (O',i,j,k) orthonormal basis
     *
     * Although the orthonormal basis of the grid is (O', i,j,k), coordinates
     * objects are defined as x,y,z. Please be careful.
     */
    Coords& mGridpos;



    /**
     * @brief Id of the cube
     *
     * The id of the cube can be defined as the multiplication of gridpos value
     * with the following formula :
     *   id = gridpos.x + gridpos.y*nPtRangeI+ gridpos.z*nPtRangeI*nPtRangeJ
     *
     * where nPtRangeI is the number of box along the i axis of the grid
     * where nPtRangeJ is the number of box along the j axis of the grid
     *
     */
    size_t mId;

    /**
     * @brief Is this box part of the margin in the main grid
     */

    bool mIsMargin;

    /**
     * @brief True when box has an atom inside or their distance is below
     * the vanDerWaals radius of the atom
     */
    bool mHasProtein;

    /**
     * @brief True if a protein atom is closer than 2.5 Angstroems.
     */
    bool mCloseProtein;

    /**
     * @brief True when this box burying is above threshold and mIsMargin,
     * mHasProtein,mCloseProtein are false
     */
    bool mIsPotentialCavity;

    bool mInUse;

    GroupList<MMAtom> mCloseAtom;
    GroupList<MMAtom> mIncludeAtom;
public:
    Box();
~Box();

    void clear();
    const size_t& getMId()const {return mId;}
    /**
     * @brief Add an atom considered to be in this box:
     * @param atom to add
     */
    void addAtom(MMAtom& atom){mIncludeAtom.add(&atom);}


    size_t numAtom()const {return mIncludeAtom.size();}
    MMAtom& getAtom(const size_t& pos)const {return mIncludeAtom.get(pos);}

    Coords getOrigPos() const;
    const Coords& getGridPos() const {return mGridpos;}
    const bool& isMargin() const {return mIsMargin;}
    const bool& isProtein() const {return mHasProtein;}
    void isProtein(const bool& value) {mHasProtein=value;}

    void isCloseToProtein(const bool& value){mCloseProtein=value;}
    const bool& isCloseToProtein() const {return mCloseProtein;}
    const bool& isInUse()const{return mInUse;}
    void isInUse(const bool& value){mInUse=value;}
    void isPotentialCavity(const bool& value){mIsPotentialCavity=value;}
    const bool& isPotentialCavity() const {return mIsPotentialCavity;}

    void addCloseAtom(MMAtom& atom){mCloseAtom.add(atom);}
    bool hasCloseAtom()const {return mCloseAtom.size() > 0;}
    size_t numCloseAtom()const{return mCloseAtom.size();}
    MMAtom& getCloseAtom(const size_t& pos)const {return mCloseAtom.get(pos);}
};
}
#endif // BOX_H
