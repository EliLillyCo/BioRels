#include "compound.h"

Compound::Compound():
    mName(""),
    mTautomer_Id(0),
    mIsLilly(false),
    mIsCorrect(false),
    mClass(""),
    mSubClass(""),
    mSMILES(""),
    mReplaced_By("")
{

}



const std::string &Compound::getName() const
{
    return mName.get();
}

void Compound::setName(const std::string &name)
{
    mName = name;
}

int Compound::tautomer_Id() const
{
    return mTautomer_Id;
}

void Compound::setTautomer_Id(int tautomer_Id)
{
    mTautomer_Id = tautomer_Id;
}

bool Compound::isLilly() const
{
    return mIsLilly;
}

void Compound::setIsLilly(bool isLilly)
{
    mIsLilly = isLilly;
}

const std::string &Compound::getClass() const
{
    return *mClass;
}

void Compound::setClass(const std::string &pClass)
{
    mClass = pClass;
}

const std::string & Compound::getSubClass() const
{
    return *mSubClass;
}

void Compound::setSubClass(const std::string &subClass)
{
    mSubClass = subClass;
}

const std::string & Compound::getSMILES() const
{
    return *mSMILES;
}

void Compound::setSMILES(const std::string &sMILES)
{
    mSMILES = sMILES;
}

const std::string & Compound::getReplaced_By() const
{
    return *mReplaced_By;
}

void Compound::setReplaced_By(const std::string &replaced_By)
{
    mReplaced_By = replaced_By;
}
protspace::MacroMole &Compound::getMole()
{
    return mMole;
}

const protspace::MacroMole &Compound::getMole()const
{
    return mMole;
}

