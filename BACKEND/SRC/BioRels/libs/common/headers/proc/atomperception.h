#ifndef ATOMPERCEPTION_H
#define ATOMPERCEPTION_H
#include <vector>
#include <cstdint>
#include <string>
#include <inttypes.h>
#include "atomstat.h"
namespace protspace
{


struct LinkRule
{
    unsigned char mLinkAtom;
    uint16_t mBType;

};
struct AtomRule
{
    unsigned char mStartAtom;
    std::vector<LinkRule> mLinkRules;
    std::string mMOL2;
    bool inRing;
    bool inArRing;
    signed char charge;
};
class MacroMole;
class MMAtom;
class AtomPerception
{
protected:
    /**
     * @brief followRule
     * @param pRule
     * @param pAtom
     * @return
     * @throw * 600301   AtomPerception::followRule      Atom does not have any bonds - Please contact administrator
     */
    bool followRule(const AtomRule& pRule,const MMAtom& pAtom)const;
    std::vector<bool> mListProcessed;
    AtomStat mAtStat;
    std::vector<size_t> mCountsC,mCountsO,mCountsN;size_t mAllCounts;
    static const std::vector<AtomRule> mCarbonRules, mOxygenRules,mNitrogenRules,sSulfurRules;
    bool mIsMolecule;
    bool mGroupPerception;
    void resetMolecule(MacroMole& mole)const;
    void prepProcess(MacroMole& mole);

    /**
     * @brief assignSingleMOL2
     * @param atom
     * @return
     * @throw 600401   AtomPerception::assignSingleMOL2    Atom element not recognized
     * @throw 310801  No type given
     * @throw 310802 Unrecognized MOL2 Type
     * @throw 600201   AtomPerception::setMOL2         Atom out of range of processing list
     */
    bool assignSingleMOL2(MMAtom& atom);
    /**
     * @brief setMOL2
     * @param pAtom
     * @param pMOL2
     * @param wAtomicNum
     * @throw 310801  No type given
     * @throw 310802 Unrecognized MOL2 Type
     * @throw 600201   AtomPerception::setMOL2         Atom out of range of processing list
     */

    void setMOL2(MMAtom& pAtom, const std::string& pMOL2, const bool &wAtomicNum=true);
    bool getSpecificMOL2(MMAtom& atom);
    bool processCarbon(MMAtom& atom);
    bool isCarbamoyl(MMAtom& pAtom);
    bool processNitrogen(MMAtom& atom);
    bool isCarboxylicAcid(MMAtom &atom);
    bool isAmideTwoNitro(MMAtom& atom);
    bool isGuanidinium(MMAtom&atom);
    bool isAmide(MMAtom& atom);
    bool isAmidinium(MMAtom& atom);
    bool isNOxide(MMAtom& atom);
    bool isNitro(MMAtom& atom);
    bool isSulfonamide(MMAtom& atom);
    bool isSulfonate(MMAtom& atom);
    bool isSulfone(MMAtom& atom);
    bool isOtherSulf(MMAtom& atom);

    bool processSulfur(MMAtom& atom);

    bool processPhosphate(MMAtom& atom);
    bool perceiveSingleCarbon(MMAtom& atom);
    bool perceiveSingleOxygen(MMAtom& atom);
    /**
     * @brief perceiveSingleAtom
     * @param atom
     * @return
     * @throw 600101    AtomPerception::perceive Unable to process atom
     * @throw 600201   AtomPerception::setMOL2         Atom out of range of processing list
     * @throw 600401   AtomPerception::assignSingleMOL2    Atom element not recognized
     * @throw 310801  No type given
     * @throw 310802 Unrecognized MOL2 Type
     */
    bool perceiveSingleAtom(MMAtom& atom);
    bool perceiveSingleNitrogen(MMAtom& atom);
public:
    /**
     * @brief perceive
     * @param pMole
     * @param force
     * @return
     * @throw 600101    AtomPerception::perceive Unable to process atom
     * @throw 600201   AtomPerception::setMOL2         Atom out of range of processing list
     * @throw 600401   AtomPerception::assignSingleMOL2    Atom element not recognized
     */
    bool perceive(MacroMole& pMole,const bool& force=false);
    bool perceive(MMAtom& atom);
    void print();
    void setGroupPerception(const bool& perceive){mGroupPerception=perceive;}
    AtomPerception();
};
}

//namespace protspace
//{

//class MacroMole;
//class MMAtom;
//class AtomPerc
//{

//protected:
//    std::vector<bool> mListProcessed;
//    bool mIsMolecule;
//    bool isInRing;
//    bool isInArRing;
//    size_t nOx;
//    size_t nN;
//    size_t nC;
//    size_t nHy;
//    size_t nBd;
//    size_t nSing,nDouble,nTrip,nAr,nDe,nAmide;
//    unsigned char mAtomicNum;
//    unsigned short residue_type;
//    void getStats(const MMAtom& atom);
//    bool assignSingleMOL2(MMAtom& atom)const;
//    bool processCarbon(MMAtom& atom);
//    bool processNitrogen(MMAtom& atom);
//    bool processPhosphate(MMAtom& atom);
//    bool processSulfur(MMAtom& atom);
//    bool isCarboxylicAcid(MMAtom& atom);
//    bool isAmide(MMAtom& atom);
//    bool isAmidinium(MMAtom &atom);
//    bool isGuanidinium(MMAtom&atom);
//    bool isAmideTwoNitro(MMAtom& atom);


//    bool getSpecificMOL2(MMAtom& atom);
//    bool perceive(MMAtom& atom);
//    bool perceiveSingleAtom(MMAtom& atom);

//    bool perceiveSingleCarbon(MMAtom& atom);
//    bool perceiveSingleOxygen(MMAtom& atom);
//    bool perceiveSingleNitrogen(MMAtom& atom);
//    bool perceiveSingleSulfur(MMAtom& atom);
//public:
//    AtomPerc();
//    void  perceive(MacroMole& mole, const bool &force=false);
//    bool perceiveAtom(MMAtom& atom);

//};

//}
#endif // ATOMPERCEPTION_H

