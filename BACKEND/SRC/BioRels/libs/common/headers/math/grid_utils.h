#ifndef GRID_UTILS_H
#define GRID_UTILS_H
#include <vector>
#include "headers/statics/intertypes.h"
namespace protspace
{
class Grid;
class MacroMole;
class MMAtom;
class MMResidue;
class MMRing;
class Box;
double getOverlap(const Grid& pGrid,
                       const MacroMole& pMole1,
                       const MacroMole& pMole2,
                       const metric &pMetric,
                       const std::string& pFile="", const bool &pOnlyUsed=false);

void getCubeAround(const Box& box,
                   const double& pThres,
                   const Grid& grid,
                   std::vector<const Box*>& pListBox);
/**
 * @brief getAtomClose
 * @param list
 * @param atom
 * @param thres
 * @param grid
 * @param isGridConsidered
 * @throw 220701   Grid::getPrepBox            No box assigned to this atom
 * @throw 220702   Grid::getPrepBox            Parent molecule is not part of this grid
 */
void getAtomClose(std::vector<MMAtom *> &list,
                  const MMAtom& atom,
                  const double& thres,
                  const Grid& grid,
                  const bool& isGridConsidered);
void getResidueClose(std::vector<MMResidue *> &list,
                        const MMResidue& res,
                        const double& thres,
                        const Grid& grid,
                        const bool &higherMID=false,
                        bool isInGrid=true,
                        bool wDiffMole=false);
void getAtomClose(std::vector<MMAtom *> &list,
                         const MMResidue& res,
                         const double& thres,
                         const Grid& grid,
                         const bool& isGridConsidered);
void getAtomClose(std::vector<MMAtom *> &list,
                         const MMRing& pRing,
                         const double& thres,
                         const Grid& grid,
                         const bool& isGridConsidered);
double getSAS(protspace::MacroMole& mole,
            const double& n_obj=100,
            const bool& onlyUsed=false,
            const double& gr_step=3.5,
            const double& gr_margin=2,
            const int& step=1,
            const bool& wWaterS=false,
            const std::string& pPath=""
            );
}
#endif // GRID_UTILS_H

