#ifndef BONDPERCEPTION_H
#define BONDPERCEPTION_H
#include "headers/math/grid.h"
#include "headers/statics/atomdata.h"
namespace protspace
{
class BondPerception
{
protected:
    Grid mGrid;
    bool mKeepExistingBond;
    bool mNoDeletion;
    bool mAcceptClashes;
    static bool sMatrixLoaded;
    static Matrix<double> sThresDist;
    static Matrix<double> sThresShort;
    static void prepareMatrix(const double &pShortThresFactor);
    double mMaxDev;

    /**
     * @brief prepGrid
     * @param pMole
    * @throw 220401  Grid::generateCubes Bad allocation
    * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
    * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
    * @throw
     */
    void prepGrid(MacroMole& pMole);

    bool isHeavyFiltered(const MMAtom& pAtom)const;
    /**
     * @brief createHeavyAtomBond
     * @param pMole
     * @throw 030303   Group::CreateLink           Bad allocation
     */
    void createHeavyAtomBond(MacroMole& pMole);
    bool checkCase(MMAtom& rAtm, MMAtom& cAtm)const;
    double getException(const MMAtom& rAtm, const MMAtom& cAtm)const;
    double getShorterException(const MMAtom& rAtm, const MMAtom& cAtm)const;
    void cleanAtoms(MacroMole& mole,const std::vector<MMAtom*>& HtoDel)const;

    /**
     * @brief createHydrogenBond
     * @param mole
     * @throw 030303   Group::CreateLink           Bad allocation
     */
    void createHydrogenBond(MacroMole& mole);
    bool checkHydrogenBond(MMAtom& Hyd,
                           MMAtom& heavy)const;
    void removeExtraBonds(MacroMole& mole);
    void removeCrazyBonds(protspace::MacroMole &pMole);
public:
    BondPerception(const double &pShortThresFactor=0.8);

    /**
     * @brief processMolecule
     * @param pMole
    * @throw 220401  Grid::generateCubes Bad allocation
    * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
    * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
    * @throw 610201   BondPerception::processMolecule   Molecule cannot be an alias
    * @throw 030303   Group::CreateLink           Bad allocation
     */
    void processMolecule(MacroMole& pMole);



    /**
     * @brief setDistThreshold
     * @param element1
     * @param element2
     * @param thres
     * @throw 610101   BondPerception::setDistThreshold   First atomic number is above the number of element allowed
     * @throw 610102   BondPerception::setDistThreshold   Second atomic number is above the number of element allowed
     */
    static void setDistThreshold(const aelem& element1,
                          const aelem& element2,
                                 const double& thres);
    void preProcessMolecule(MacroMole &pMole);
    void postProcessMolecule(MacroMole &pMole);
    void setNoDeletion(const bool& b){mNoDeletion=b;}
    void acceptClashesWithWarning(const bool& b){mAcceptClashes=b;}
    const protspace::Grid& getGrid()const{return mGrid;}
};
}
#endif // BONDPERCEPTION_H
