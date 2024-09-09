#ifndef INTERDATA_H
#define INTERDATA_H

#include "headers/inters/interobj.h"
#include "headers/statics/grouplist.h"
namespace protspace
{

class InterData
{
    std::vector<InterObj> mListInteractions;
    GroupList<MacroMole> mMolecules;
    std::string mName;
    std::vector<std::string> mListTextInters;

    std::string getAtomData(const MMAtom& pAtom, const bool &isSDLIG, const bool &start);
    InterData(const InterData&){}
    InterData& operator=(const InterData&);
public:
    void clear();
    InterData():mMolecules(1,true),mName(""){mListInteractions.reserve(40);}


    void addInter(const InterObj& obj){mListInteractions.push_back(obj);}


    size_t count()const {return mListInteractions.size();}


    const InterObj& getInter(const size_t& pos)const{return mListInteractions.at(pos);}


    InterObj& getInter(const size_t& pos){return mListInteractions.at(pos);}


    /**
     * @brief toMolecule
     * @param onlySelected
     * @return
     * @throw 351401    MacroMole::addChain     Bad Allocation
     * @throw 351502    MacroMole::addResidue   Bad allocation
     * @throw 350102 MacroMole::addAtom      Bad allocation
     * @throw 030303 Bad allocation
     */
    size_t toMolecule(const bool& onlySelected=false);


    void toText(const std::string &head="", const bool &isSDLIG=false, const bool &bothSide=true);


    void setName(const std::string& name){mName=name;}

    const std::string& getLine(const size_t& pos)const {return mListTextInters.at(pos);}


    size_t countLine()const {return mListTextInters.size();}


    MacroMole& getFullMole(const size_t& pos=0){return mMolecules.get(pos);}


    void saveText(const std::string& pFile, const bool &atEnd=false, const bool &wHeader=true);
    std::vector<InterObj>::iterator begin(){return mListInteractions.begin();}
    std::vector<InterObj>::iterator end(){return mListInteractions.end();}
    void erase(size_t pos);
    void unique();
};


}
#endif // INTERDATA_H

