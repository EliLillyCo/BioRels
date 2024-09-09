#ifndef SDREAD_H
#define SDREAD_H

#include <fstream>
#include <vector>
#include <map>
#include "headers/parser/readerbase.h"
#include "headers/math/coords.h"
namespace protspace
{

class MacroMole;
class MMResidue;

class ReadSDF:public ReaderBase
{
protected:
    size_t nExpectedAtom;
    size_t nExpectedBond;
    size_t nExpectedAtomList;
    size_t nExpectedSText;
    size_t nStep;
    size_t mPosAtomSymbol;
    std::string& mAtomSymbol;
    Coords mTmpCoords;
    size_t nFoundAtom;
    size_t nFoundBond;
    int mMassDiff;
    int mCharge;

    ///
    /// \brief loadAtom
    /// \param molecule
    /// \throw 350102 MacroMole::addAtom      Bad allocation
    /// \throw 310101 MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    /// \throw 310102 MMAtom::setAtomicName   Atomic name not found
    /// \throw 420401   ReadSDF::loadAtom               Unexpectected end of line
    void loadAtom(MacroMole& molecule);
    void loadHeader();

    /**
     * @brief loadBond
     * @param molecule
     * @throw 030401 MacroMole::getAtom Given position is above the number of dots
     * @throw 350604   MacroMole::addBond          Both atoms are the same
     * @throw 030303   MacroMole::addBond          Bad allocation
     */
    void loadBond(MacroMole& molecule);


    void addProp();
    ///
    /// \brief assignProp
    /// \param molecule
    /// \throw 030401  MacroMole::getAtom      Given position is above the number of dots
    /// \throw 420501   ReadSDF::assignProp    Unexpectected end of line
    ///
    void assignProp(MacroMole& molecule);
    std::map<std::string, std::vector<std::string>> mProps;
public:
~ReadSDF();
    ReadSDF(const std::string &path="");
    ///
    /// \brief load
    /// \param molecule
    ///
    /// \throw 030401   MacroMole::getAtom      Given position is above the number of dots
    /// \throw 420501   ReadSDF::assignProp     Unexpectected end of line
    /// \throw 350604   MacroMole::addBond      Both atoms are the same
    /// \throw 310101   MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    /// \throw 310102   MMAtom::setAtomicName   Atomic name not found
    /// \throw 420401   ReadSDF::loadAtom       Unexpectected end of line
    /// \throw 420601   ReadSDF::load           Unable to read file - Memory allocation issue
    void load(MacroMole& molecule) throw(ProtExcept);
};
}
#endif // SDREAD_H

