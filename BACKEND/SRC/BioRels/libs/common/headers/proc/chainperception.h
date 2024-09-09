#ifndef CHAINPERCEPTION_H
#define CHAINPERCEPTION_H

#include "headers/math/grid.h"

namespace protspace
{
class MacroMole;
class ChainPerception
{

protected:
    ///
    /// \brief Maximal number of Amino Acid to consider a protein chain to be a peptide
    ///
    static unsigned short mAAPeptSep;
    ///
    /// \brief Distance threshold to look around to assign chain of non-protein residues
    ///
    static double mDWatThres;

    ///
    /// \brief Grid used to speed up calculation of water assignment
    ///
    Grid mGrid;

    signed short *mGroups;

    signed short mNGroup;
    std::map<std::string,std::map<int,protspace::MMResidue*>> mFIDMap;

    void updateGroup(const MMResidue& pRes1,
                     const MMResidue& pRes2);
    void getGroups(const MMChain& pChain);

    void scanAtoms(const MMResidue& pRes);
    void assignChainType(MMChain& pChain, signed short starting);
public:
    ~ChainPerception();
    ChainPerception();

     void clear();
    static const unsigned short& getAAPeptSep() {return mAAPeptSep;}
    static void setAAPeptSep(const unsigned short &AAPeptSep){mAAPeptSep=AAPeptSep;}
    static const double& getDWatThres() {return mDWatThres;}
    static void setDWatThres(const double& dWatThres){ mDWatThres=dWatThres;}

    void process(MacroMole& pMole);

    /**
     * @brief reassignWater
     * @param pMole
    * @throw 220401  Grid::generateCubes Bad allocation
    * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
    * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
     */
    void reassignOther(MacroMole& pMole);

    void controlFID(MMChain& pChain);
    void getSingleton(protspace::MacroMole &pMole,std::vector<protspace::MMResidue *> &list) const;
    const std::map<std::string,std::map<int,protspace::MMResidue*>>& getFIDMap()const{return mFIDMap;}
    const signed short& getNGroup()const {return mNGroup;}
};

}

#endif // CHAINPERCEPTION_H

