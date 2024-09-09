#ifndef INTER_APOLAR_H
#define INTER_APOLAR_H
#include <math.h>
#include "headers/molecule/mmresidue.h"
#include "headers/inters/inter_atombase.h"
namespace protspace
{
class InterAtomApolar:public InterAtomBase
{
protected:
    bool checkProperty() const;
    double getAngle()const {return 0;}
public:
    bool checkInteraction();
    static double mMaxDist;
    InterAtomApolar(MacroMole& pMole, const bool&pIsSameMole);
};
class InterApolar
{
protected:
    InterAtomApolar mIAA;
    size_t mRefRes;
    size_t mCompRes;
    bool mkeepBestPerRefAtom;
    bool mkeepBestPerCompAtom;
    bool isFiltered(MMAtom& atom)const;
    void perceivePerAtom(InterData& data, MMResidue& resR, MMResidue& resC,const bool& isSwitched=false);
public:
    static bool   mKeepAll;


    InterApolar(MacroMole& pMole, const bool&pIsSameMole=true);
    /**
     * @brief setRefResidue
     * @param pRes
     * @throw 820101   InterApolar::setRefResidue        Given Residue is not part of the molecule
     */
    void setRefResidue(const MMResidue& pRes);

    /**
     * @brief setCompResidue
     * @param pRes
     * @throw 820201   InterApolar::setCompResidue     Given Residue is not part of the molecule
     */
    void setCompResidue(const MMResidue& pRes);


    /**
     * @brief setRefResidue
     * @param pPos
     * @throw 820301    InterApolar::setRefResidue  Given position is above the number of residue
     */
    void setRefResidue(const size_t& pPos);

    /**
     * @brief setCompResidue
     * @param pPos
     * @throw 820401   InterApolar::setCompResidue     Given position is above the number of residues
     */
    void setCompResidue(const size_t& pPos);


    void perceiveInteraction(InterData &data);
    void setMoleComp(MacroMole& pMole);
    bool getMkeepBestPerRefAtom() const;
    void setMkeepBestPerRefAtom(bool value);
    bool getMkeepBestPerCompAtom() const;
    void setMkeepBestPerCompAtom(bool value);
};
}
#endif // INTER_APOLAR_H

