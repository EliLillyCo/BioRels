#ifndef MULTIMOLE_H
#define MULTIMOLE_H

#include "headers/molecule/macromole.h"

namespace protspace
{
class MultiMole
{
protected:
    GroupList<MacroMole> mListMolecules;
    bool mIsOwner;
public:
    /**
     * @brief Standard constructor
     * @param isOwner Tell whether this object will own the macromole
     */
    MultiMole(const bool& isOwner);

    void addStructure(MacroMole& mole);
    size_t size()const {return mListMolecules.size();}
    /**
     * @brief getMole
     * @param pos
     * @throw 040101 Position above number of entries
     * @return
     */
    MacroMole& getMole(const size_t& pos)const{return mListMolecules.get(pos);}
    size_t getPos(MacroMole& mole)const;
    void serialize(std::ofstream& ofs) const;
    void unserialize(std::ifstream& ifs);
    void clear();
    /**
     * @brief delMole
     * @param pos
     * @throw 040201 Position above number of entries
     */
    void delMole(const size_t& pos){mListMolecules.remove(pos);}
    void setOwnership(const bool& isOwner);
};
}
#endif // MULTIMOLE_H

