#ifndef ERRORBASE_H
#define ERRORBASE_H

#include <string>
#undef NDEBUG
#include <assert.h>
#include <cstdint>

namespace protspace
{

class MacroMole;
class MMBond;
class MMAtom;
class MMResidue;

class ErrorResidue 
{
protected:
    const std::string mChain;
    const std::string mResName;
    const int mFId;
    const int mMId;
    std::string mErrorDescription;
    MMResidue* mResidue;

public:
    ErrorResidue(const std::string &pErrorDescription, MMResidue &pResidue) ;


    ErrorResidue(const std::string &mChain, const std::string &mResName, const int mFId, const int mMId,
                 const std::string &mErrorDescription) ;
    bool hasResidueDefined()const {return (mResidue!=nullptr);}
    const MMResidue& getResidue()const {assert(mResidue!=nullptr);return *mResidue;}
    const std::string& getDescription()const {return mErrorDescription;}
    const std::string &getChain()   const { return mChain; }
    const std::string &getResName() const { return mResName; }
    const int& getMFId() const {
        return mFId;
    }

    const int& getMMId() const {
        return mMId;
    }

};
class ErrorBond
{
protected:
    const std::string mChain1;
    const std::string mResidue1;
    const std::string mAtomName1;
    const int mResidueId1;
    const int mFId1;
    const int mMId1;

    const std::string mChain2;
    const std::string mResidue2;
    const std::string mAtomName2;
    const int mResidueId2;
    const int mFId2;
    const int mMId2;

    const uint16_t mType;
    double mDist;
    MMBond* mBond;
    std::string mErrorDescription;

public:
    ErrorBond(MMBond& bond,
              const uint16_t& errorFlag,
              const std::string& errorDescription);
    ErrorBond(const MMAtom& atom1,
              const MMAtom& atom2,
              const uint16_t& errorFlag,
              const std::string& errorDescription);
    std::string toString()const;
    bool hasBondDefined()const{return (mBond!=nullptr);}
    const MMBond& getBond()const{assert(mBond!=nullptr); return *mBond;}
    const std::string& getDescription()const{return mErrorDescription;}
    const std::string &getChain1()   const { return mChain1; }
    const std::string &getResName1() const { return mResidue1; }
    const int& getMFId1() const { return mFId1; }
    const int& getMMId1() const { return mMId1; }
    const std::string &getChain2()   const { return mChain2; }
    const std::string &getResName2() const { return mResidue2; }
    const int& getMFId2() const { return mFId2; }
    const int& getMMId2() const { return mMId2; }
};






class ErrorAtom
{
protected:
    const std::string mChain;

    const std::string mResidue;
    const std::string mAtomName;
    const int mResidueId;
    const int mFId;
    const int mMId;
    MMAtom* mAtom;
    std::string mErrorDescription;
    uint16_t mErrorFlag;
public:
    ErrorAtom(MMAtom& atom,
              const uint16_t& errorFlag,
              const std::string& errorDescription);
    ErrorAtom(const MMResidue& res,
              const std::string atmName,
              const uint16_t& errorFlag,
              const std::string& errorDescription);
    std::string toString()const;
    bool hasAtomDefined()const {return (mAtom!=nullptr);}
    const MMAtom& getAtom()const {assert(mAtom!=nullptr);return *mAtom;}
    const std::string& getDescription()const {return mErrorDescription;}
    const std::string &getMChain() const {
        return mChain;
    }

    const std::string &getMResidue() const {
        return mResidue;
    }

    const std::string &getMAtomName() const {
        return mAtomName;
    }

    const int &getMResidueId() const {
        return mResidueId;
    }

    const int &getMFId() const {
        return mFId;
    }

    const int& getMMId() const {
        return mMId;
    }

};


}

#endif // ERRORBASE_H

