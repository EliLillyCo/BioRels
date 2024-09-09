#ifndef INTERPROTLIG_H
#define INTERPROTLIG_H

#include "headers/molecule/macromole.h"
#include "headers/math/grid.h"
#include "headers/inters/interdata.h"
#include "headers/inters/inter_apolar.h"
#include "headers/statics/intertypes.h"
#include "headers/inters/inter_arom.h"
#include "headers/inters/inter_cationpi.h"
#include "headers/inters/inter_chpi.h"
#include "headers/inters/inter_halogenbond.h"
#include "headers/inters/inter_hbondda.h"
#include "headers/inters/inter_hbondad.h"
#include "headers/inters/inter_ionic.h"
#include "headers/inters/inter_weakhbond.h"
#include "headers/inters/inter_hbondhalogen.h"
#include "headers/inters/inter_halogenarom.h"
#include "headers/inters/inter_metal.h"

namespace protspace
{

class InterProtLig
{
protected:
  MacroMole& mProtein;
  std::vector<std::string> mHAcceptor;
  std::vector<std::string> mHDonor;

  Grid mGrid;


  InterHalogenBond mIhalo;

  Inter_HBond_AD       mIHBondAD;
  Inter_HBond_DA       mIHBondDA;

  InterIonic       mIIonic;
  InterWHBond      mIWeak;
  InterHalogenHBond mIXBond;
  InterMetal        mIMet;
    bool mDebugExport;

    /**
   * @brief prepareGrid
     * @throw 220401  Grid::generateCubes Bad allocation
     * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
     * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
   */
  void prepareGrid();
 void processHalo(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom);
  void processCation(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom);

  void processAnionic(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom);
  void processHBA(InterData& mData, const std::vector<MMAtom *> &list, MMAtom& atom);
  void processHBD(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom);
  void processHydrophobic(InterData& mData,const std::vector<MMAtom*>& list, MMAtom& atom);
  void processClash(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);
public:
  /**
   * @brief InterProtLig
   * @param pMole
     * @throw 220401  Grid::generateCubes Bad allocation
     * @throw 220201   Grid::createGrid            No heavy atom to consider for the grid
     * @throw 220301   Grid::calcUnitVector        Geometric center is the same as the baryCenter
   */
  InterProtLig(MacroMole& pMole);
  void calcInteractions(InterData &mData, MacroMole& pLigand);

    void prepInters(MacroMole &pLigand);

    void scanAtom(InterData &mData, MacroMole &pLigand);

    void scanRingRing(InterData &mData, MacroMole &pLigand);

    void scanHydroRingRing(InterData &mData, const MMRing &ringR, const MMRing &ringC) const;

    void scanRingAtomProt(InterData &mData, MacroMole &pLigand) const;

    void processRingAtom(InterData& mData,const MMRing &pRing,  MMAtom &pAtom) const;

    void scanRingAtomLig(InterData &mData, MacroMole &pLigand) const;
};

}

#endif // INTERPROTLIG_H

