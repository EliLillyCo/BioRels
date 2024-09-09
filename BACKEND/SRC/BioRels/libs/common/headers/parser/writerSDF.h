#ifndef SDWRITER_H
#define SDWRITER_H
#include <map>
#include "headers/parser/writerbase.h"

namespace protspace
{
class WriterSDF : public WriterBase
{
protected:
    bool mOutputDate;
    bool mOutputUserID;
    void outputHeader(const MacroMole& mole);
    void outputAtom(const MacroMole &mole);
    void outputBond(const MacroMole& mole);
    std::map<std::string,std::string> maps;
    inline void saveMaps();
public:
    WriterSDF();
    ~WriterSDF(){}
    WriterSDF(const std::string& path, const bool& onlySelected=false);
    void clearMap(){maps.clear();}
    void addData(const std::string& pR, const std::string& pV){maps.insert(std::make_pair(pR,pV));}
    /**
     * @brief save
     * @param mole
     * @throw 450101   WriterBase::open     No Path given
     * @throw 450102   WriterBase::open     Unable to open file

     */
    void save(const MacroMole& mole)throw(ProtExcept);
};
}
#endif // SDWRITER_H

