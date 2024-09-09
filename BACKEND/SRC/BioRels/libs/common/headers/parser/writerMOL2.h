#ifndef MOL2WRITER_H
#define MOL2WRITER_H

#include "headers/parser/writerbase.h"

namespace protspace
{
class WriteMOL2 : public WriterBase
{
protected:
    bool mOutputDate;
    bool mOutputUserID;
    void outputHeader(const MacroMole& mole);
    /**
     * @brief outputAtom
     * @param mole
     * @throw 460101   WriterMOL2::outputAtom          Unable to find residue
     */
    void outputAtom(const MacroMole &mole);
    /**
     * @brief outputBond
     * @param mole
     * @throw 460201   WriteMOL2::outputBond           Unable to find atom 1
     * @throw 460202   WriteMOL2::outputBond           Unable to find atom 2
     * @throw 460203   WriteMOL2::outputBond           Unrecognized bond type
     */
    void outputBond(const MacroMole& mole);
    void outputResidue(const MacroMole& mole);
public:
    WriteMOL2();
    ~WriteMOL2(){}
    WriteMOL2(const std::string& path, const bool& onlySelected=false);

    /**
     * @brief save
     * @param mole
     * @throw 450101   WriterBase::open     No Path given
     * @throw 450102   WriterBase::open     Unable to open file
     * @throw 460101   WriterMOL2::outputAtom          Unable to find residue
     */
    void save(const MacroMole& mole);
};
}
#endif // MOL2WRITER_H
