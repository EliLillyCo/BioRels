#ifndef COMPOUND_H
#define COMPOUND_H

#include "headers/molecule/macromole.h"
#include "headers/statics/protpool.h"
class Compound
{
protected:
    protspace::MacroMole mMole;
    protspace::StringPoolObj mName;
    int mTautomer_Id;
    bool mIsLilly;
    bool mIsCorrect;
    protspace::StringPoolObj mClass;
    protspace::StringPoolObj mSubClass;
    protspace::StringPoolObj mSMILES;
    protspace::StringPoolObj mReplaced_By;
    std::map<std::string, std::string> mInfo;

public:
    Compound();
    protspace::MacroMole& getMole();
    void setMole(const protspace::MacroMole &mole);
    const std::string &getName() const;
    void setName(const std::string &name);
    int tautomer_Id() const;
    void setTautomer_Id(int tautomer_Id);
    bool isLilly() const;
    void setIsLilly(bool isLilly);
    const std::string &getClass() const;
    void setClass(const std::string &pClass);
    const std::string &getSubClass() const;
    void setSubClass(const std::string &subClass);
    const std::string &getSMILES() const;
    void setSMILES(const std::string &sMILES);
    const std::string &getReplaced_By() const;
    void setReplaced_By(const std::string &replaced_By);
    void addProp(const std::string& name, const std::string& value)
    {
        mInfo.insert(std::make_pair(name,value));
    }
    bool isMIsCorrect() const {
        return mIsCorrect;
    }

    void setMIsCorrect(bool mIsCorrect) {
        Compound::mIsCorrect = mIsCorrect;
    }
    bool hasProp(const std::string& name)const {
        return mInfo.find(name)!=mInfo.end();
    }
    const std::string& getProp(const std::string& name)const{
        return mInfo.at(name);
    }
    const protspace::MacroMole &getMole()const;

};

#endif // COMPOUND_H
