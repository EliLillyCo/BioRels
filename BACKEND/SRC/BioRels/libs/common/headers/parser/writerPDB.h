#ifndef PDBWRITER_H
#define PDBWRITER_H

#include "headers/parser/writerbase.h"

namespace protspace
{
class WritePDB : public WriterBase
{
protected:

    void outputHeader(const MacroMole& mole);
    void outputAtom(const MacroMole &mole);
    void outputBond(const MacroMole& mole);

public:
    WritePDB();
    ~WritePDB(){}
    WritePDB(const std::string& path, const bool& onlySelected=false);

    void save(const MacroMole& mole);
};
}
#endif // PDBWRITER_H

