#include <sstream>
#include <iomanip>
#include "headers/inters/interobj.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/macromole.h"




protspace::InterObj::InterObj(MMAtom& atom1,
                   MMAtom& atom2,
                   const unsigned char &type,
                   const double& distance):
    mAtom1(&atom1),
    mAtom2(&atom2),
    mType(type),
    mDistance(distance),
    mAngle(100000),
    mIsUsed(true),
    mRing1(nullptr),
    mRing2(nullptr),
    mIsProtSide(0),
    mDBID(-1)

{}
protspace::InterObj::InterObj(const InterObj& p):
    mAtom1(p.mAtom1),
    mAtom2(p.mAtom2),
    mType(p.mType),
    mDistance(p.mDistance),
    mAngle(p.mAngle),
    mIsUsed(p.mIsUsed),
    mRing1(p.mRing1),
    mRing2(p.mRing2),
    mIsProtSide(p.mIsProtSide),
    mDBID(p.mDBID)

{}

protspace::InterObj &protspace::InterObj::operator=(const InterObj &p)
{
    mAtom1=p.mAtom1,
    mAtom2=p.mAtom2;
    mType=p.mType;
    mDistance=p.mDistance;
    mAngle=p.mAngle;
    mIsUsed=p.mIsUsed;
    mRing1=p.mRing1;
    mRing2=p.mRing2;
    mIsProtSide=p.mIsProtSide;
    mDBID=p.mDBID;
    return *this;
}




bool  protspace::InterObj::operator ==(const InterObj& inter)
{
    return (inter.mAtom1==mAtom1 &&
            inter.mAtom2==mAtom2 &&
            inter.mDistance==mDistance &&
            inter.mAngle==mAngle &&
            inter.mType==mType &&
            inter.mIsProtSide==mIsProtSide &&
            inter.mRing1==mRing1 &&
            inter.mRing2==mRing2
            );
}


const double &protspace::InterObj::getAngle() const
{
    return mAngle;
}

void protspace::InterObj::setAngle(const double& value)
{
    mAngle = value;
}


protspace::MMResidue& protspace::InterObj::getResidue1()const
{
    return (mRing1!=nullptr)? mRing1->getResidue():mAtom1->getResidue();;
}
protspace::MMResidue& protspace::InterObj::getResidue2()const
{
    return (mRing2!=nullptr)? mRing2->getResidue():mAtom2->getResidue();
}

std::string protspace::InterObj::toString(const bool& forCSV)const
{

    std::ostringstream oss;

    const MMResidue& res1=getResidue1();
    const MMResidue& res2=getResidue2();

    if (!forCSV)
    oss << std::setw(4)<<mAtom1->getName()
        <<"(" <<std::setw(6)<<mAtom1->getFID()
       <<"/"  <<std::setw(6)<<mAtom1->getMID()
      <<")::"<<std::setw(2)<<res1.getChainName()
     << "::"<<std::setw(3)<<res1.getName()
     << " ("<<std::setw(4)<<res1.getFID()
     << "/" <<std::setw(4)<<res1.getMID()<<")\t"
     << std::setw(4)<<mAtom2->getName()
             <<"(" <<std::setw(6)<<mAtom2->getFID()
            <<"/"  <<std::setw(6)<<mAtom2->getMID()
           <<")::"<<std::setw(2)<<res2.getChainName()
          << "::"<<std::setw(3)<<res2.getName()
          << " ("<<std::setw(4)<<res2.getFID()
          << "/" <<std::setw(4)<<res2.getMID()<<")\t";
    else
        oss<<mAtom1->getName()<<"\t"<<res1.getChainName()
           <<"\t"<<res1.getName()
           <<"\t"<<res1.getFID()
           <<"\t"<<mAtom2->getName()
           <<"\t"<<res2.getChainName()
           <<"\t"<<res2.getName()
           <<"\t"<<res2.getFID()<<"\t";


    oss <<interToString();
    return oss.str();
}




const std::string& protspace::InterObj::interToString()const
{
    const auto it = INTER::typeToName.find(mType);
    assert(it!=INTER::typeToName.end());
  return (*it).second;
}

const unsigned char &protspace::InterObj::getIsProtSide() const
{
    return mIsProtSide;
}

void protspace::InterObj::setIsProtSide(const unsigned char &value)
{
    mIsProtSide = value;
}
