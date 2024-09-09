#ifndef GRID_H
#define GRID_H

#include "headers/statics/grouplist.h"
#include "headers/math/coords.h"
#include "headers/math/matrix.h"
#include "headers/molecule/macromole.h"
#include "box.h"
namespace protspace
{
class Grid
{
private:

    std::vector<size_t> mListCooPos;
    std::vector<size_t> mListBoxPos;
protected:
    GroupList<Box> mListBox;
    GroupList<MacroMole> mListMolecule;
    ///
    /// \brief Length in Angstroems of each vertex of a cube
    ///
    const double mBoxLength;


    ///
    /// \brief margin in Angstroem to add over the minimal box length
    ///
    const double mMargin;

    ///
    /// \brief Rotation matrix to convert (O,x,y,z) to (O',i,j,k)
    ///
    Matrix<double> mRotMatrix;

    double mScale;

    ///
    /// \brief Distance threshold to be sure an atom is in a box.
    /// mLim[0]:
    /// Consider a cube.When putting a sphere in the cube so that the sphere
    /// as a big as possible is not going out of the cube, then the radius
    /// of the sphere is mLinMin
    /// ___
    /// |/\|
    /// |\/|
    /// ----
    ///
    ///
    /// mLim[1]
    /// Consider a cube. When creating a sphere that is as small as possible
    /// but still contains all of the cube, then the radius of the sphere is
    /// mLinMax
    ///    / /
    ///   /   /
    ///  /____ /
    /// / |   | /
    /// \ |   | /
    ///  \-----/
    ///   \   /
    ///    \ /
    ///
    const double mLim[2];

    ///
    /// \brief Vector from center representing the i,j,k axis of (O',i,j,k) orthornormal basis
    ///
    Coords mVect[3];
    ///
    /// \brief Coordinates of the geometrical center of the grid
    ///
    Coords mCenter;

    ///
    /// \brief Length in Angstroems of the grid in the (O',i,j,k) basis
    ///
    Coords mGrid_size;
    ///
    /// \brief Lower coordinates of the grid
    ///
    Coords mStart_pos;

    Coords mExtremePos[2];

    ///
    /// \brief Number of box over the i,j,k axis
    ///
    int mNumPtRange[3];

    ///
    /// \brief Total number of box in this grid
    ///
    int mMaxCubeNumber;


    bool mMoleLock;

    bool mWHydrogen;

    Matrix<int> mAtomBox;


    bool checkAtomForGrid(const bool& only_used);
    void generateVectors(const Coords& pt2,
                         const Coords& pt3);

    /**
     * @brief applyRotation
     * @param onlyUsed
     * @throw 220601   Grid::applyRotation         Unable to find box for atom;
     */
    void applyRotation(const bool& onlyUsed);
    ///
    /// \brief calcUnitVector
    /// \param only_used
    /// \throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
    ///
    void calcUnitVector(const bool& only_used) throw(ProtExcept);
    void findDelination(const bool &only_used);

public:
    void clear();
    ///
    /// \brief Standard constructor
    /// \param box_length Length in Angstroems of the edge of each box
    /// \param margin Margin around all the molecules to insure no border effect
    ///
    ///
    ///
    Grid(const double& box_length, const double& margin);


    ~Grid();

    const Coords* getVect()const {return &mVect[0];}

    ///
    /// \brief considerMolecule
    /// \param mole
    /// \throw 220101  Grid::considerMolecule Cannot add more molecules, grid already been created
    ///
    void considerMolecule(MacroMole& mole);


    /**
     * @brief createGrid
     * @param only_used
     * @throw 220401  Grid::generateCubes Bad allocation
     * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
     * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
     */
    void createGrid(const bool& only_used=false);


    const double& getBoxLength() const {return mBoxLength;}
    const int& getRangeI()const {return mNumPtRange[0];}
    const int& getRangeJ()const {return mNumPtRange[1];}
    const protspace::Coords& getStartPos()const{return mStart_pos;}
    const int& getRangeK()const {return mNumPtRange[2];}
    const int& getNumBoxes()const {return mMaxCubeNumber;}
    inline int  getBoxFromRotPos(const Coords& rotpos)const;
    int getBoxPos(const Coords& pos_ini)const;
    void getRotCoords(const Coords& pos_ini, Coords& rot_pos)const;
    void saveGridBox(const std::string& pFile);
    Box* isAtomInCube(const MMAtom& atom,
                      Box& box, const bool &goNext=true)const;
    Box* isCoordInCube(const Coords& rotpos,
                       Box& box, const bool &goNext=true)const;
    const protspace::Coords& getCenter()const{return mCenter;}
    /**
     * @brief getBox
     * @param pos
     * @return
     * @throw 220901   Grid::getBox                Position above the number of boxes
     */
    Box& getBox(const size_t& pos);


    /**
     * @brief getBox
     * @param pos
     * @return
     * @throw 220801   Grid::getBox                Position above the number of boxes
     */
    const Box& getBox(const size_t& pos)const ;
    bool findBox(const int& i, const int& j, const int& k, int& posBox)const;
    bool findBox(const MMAtom& pAtom, int &posBox)const;
    bool isSphereInCube(const MMAtom& atom,const Box&box, const double& ratio)const;
    bool isSphereInCube(const MMAtom &atom, Box&box, const double &ratio=0.9)const;
    void perceiveAdjacentBox(const double &ratio=0.9);
    /**
       * @brief generateCubes
       * @throw 220401  Grid::generateCubes Bad allocation
       */
    void generateCubes() throw(ProtExcept);


    void saveGrid(const std::string& pFile,
                  const bool &wCubeBorder=false,
                  const bool &wCenterCube=true, const bool &pOnlyUsed=false)const;

    /**
       * @brief getBox
       * @param pAtom
       * @return
       * @throw 220501   Grid::findBox                Unable to find box
       */
    Box& findBox(const MMAtom& pAtom)const;

    /**
     * @brief getPrepBox
     * @param pAtom
     * @return
     * @throw 220701   Grid::getPrepBox            No box assigned to this atom
     * @throw 220702   Grid::getPrepBox            Parent molecule is not part of this grid
     */
    Box& getPrepBox(const MMAtom& pAtom)const;

    void regenBoxAtomAssign(const bool& onlyUsed);
};
}
#endif // GRID_H
