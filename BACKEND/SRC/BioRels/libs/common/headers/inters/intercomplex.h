#ifndef INTERCOMPLEX_H
#define INTERCOMPLEX_H
#include "headers/molecule/macromole.h"
#include "headers/math/grid.h"
#include "headers/inters/interdata.h"
#include "headers/inters/inter_apolar.h"
#include "headers/statics/intertypes.h"
#include "headers/inters/inter_arom.h"
#include "headers/inters/inter_cationpi.h"
#include "headers/inters/inter_chpi.h"
#include "headers/inters/inter_halogenbond.h"
#include "headers/inters/inter_hbond.h"
#include "headers/inters/inter_ionic.h"
#include "headers/inters/inter_weakhbond.h"
#include "headers/inters/inter_halogenarom.h"
#include "headers/inters/inter_metal.h"
#include "headers/inters/inter_hbondhalogen.h"
namespace protspace
{

class InterComplex
{
  protected:
    MacroMole& mMolecule;
    std::vector<std::string> mHAcceptor;
    std::vector<std::string> mHDonor;

    Grid mGrid;
    InterApolar      mIapol;
    InterAtomApolar mIAtApol;

    InterHalogenBond mIhalo;
    InterHalogenHBond mHBondX;

    InterHBond       mIHBond;
    InterMetal      mIMet;

    InterIonic       mIIonic;
    InterWHBond      mIWeak;
    bool wExportArom;
    std::vector<protspace::MMResidue*> mSelectedRes;
    void prepareGrid();
    void filter(InterData &mData);
    bool checkRes(const protspace::MMResidue &res) const;
public:
    InterComplex(MacroMole& pMole);
    void calcInteractions(InterData &mData);

    void scanAtom(InterData &mData);

    void processHBD(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);

    void processHalo(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);

    void processCation(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);

    void processAnionic(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);

    void processHBA(InterData &mData, const std::vector<MMAtom *> &list, MMAtom &atom);

    void processHydrophobic(InterData &mData);

    void scanHydroRingRing(InterData &mData, const MMRing &ringR, const MMRing &ringC) ;

    void scanRingRing(InterData &mData);

    void processRingAtom(InterData &mData, const MMRing &pRing, MMAtom &pAtom) const;

    void setSelectedRes(const std::vector<protspace::MMResidue*>& list){mSelectedRes=list;}
    void selectResAroundRes(protspace::MMResidue& pRes, const double& thres);

    void scanRingAtom(InterData &mData) ;
    void setWExportArom(bool value);
    void updateGrid();
};
}
#endif // INTERCOMPLEX_H

