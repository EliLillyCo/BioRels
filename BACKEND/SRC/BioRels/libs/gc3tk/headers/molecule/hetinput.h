#ifndef HETINPUT_H
#define HETINPUT_H
#include <cstdint>
#include <fstream>
#include <map>
#include "headers/statics/protExcept.h"
#include "headers/statics/protpool.h"
namespace protspace
{
class MacroMole;
class HETEntry;

class HETInputAbst
{
protected:
    struct Counts
    {
        size_t nN, nC,nO;
        uint16_t mClass;
    };
  bool mIsReady;
  bool mIsBinary;
  std::ifstream mIfs;
  std::map<std::string,std::streampos > mPositions;
  std::map<std::string,Counts> mCounts;

  /**
   * @brief Set the file position to the data corresponding to the given HET
   * @param pHET HET code
   * @throw 370101   HETInputAbst::setPosition       File is not opened
   * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
   */
  void setPosition(const std::string& pHET);
public:
  virtual void loadPositions()=0;
  virtual HETEntry* loadMole(const std::string& pHET, const bool &wMatrix=true) throw(ProtExcept)=0;
  HETInputAbst():mIsReady(false){}
  size_t size()const{return mPositions.size();}
  virtual ~HETInputAbst(){if (mIfs.is_open())mIfs.close();}
  const bool& isBinary()const{return mIsBinary;}
  const bool& isReady()const {return mIsReady;}
  const std::map<std::string,std::streampos >& getPositions()const{return mPositions;}
  void getPossibleMatch(const size_t& nC,
                        const size_t& nO,
                        const size_t& nN,
                        std::vector<protspace::StringPoolObj> &list);
  void getExactMatch(const size_t& nC,
                     const size_t& nO,
                     const size_t& nN,
                     std::vector<protspace::StringPoolObj>& list)const;
  virtual void prepForAll()=0;
  bool isInList(const std::string& HET)const{return mPositions.find(HET) != mPositions.end();}
};


class HETInputFile:public HETInputAbst
{
protected:

    /**
     * @brief perceiveMole
     * @param molecule
     * @param molf
     * @return
     * @throw 350102 Bad allocation
     * @throw 030303 Bad allocation
     */
    bool perceiveMole(MacroMole& molecule,const std::string& molf);
public:
    HETInputFile(){mIsBinary=false;}
    ~HETInputFile(){}
    /**
     * @brief loadPositions
     * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
     */
    void loadPositions();
    /**
     * @brief loadMole
     * @param pHET
     * @return
     * @throw 370403   HETInputFile::loadMole          Bad allocation
     * @throw 350102 Bad allocation
     * @throw 030303 Bad allocation
     * @throw 370101   HETInputAbst::setPosition       File is not opened
     * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
     */
    HETEntry* loadMole(const std::string& pHET, const bool &wMatrix=true) throw(ProtExcept);
    void prepForAll(){}
};


class HETInputBin:public HETInputAbst
{
protected:
    size_t nNeedAtom;
    /**
     * @brief openBinary
     * @return
     * @throw 370701    HETInputBin::openBinary  NO GC3TK_HOME parameter defined;
     */
    bool openBinary();
public:
    HETInputBin(){mIsBinary=true;}
    ~HETInputBin(){}
    /**
     * @brief loadPositions
     * @throw 370601   HETInputBin::loadPosition      NO GC3TK_HOME parameter defined
     * @throw 370701    HETInputBin::openBinary  NO GC3TK_HOME parameter defined;
     */
    void loadPositions();
void prepForAll();
    /**
     * @brief loadMole
     * @param pHET
     * @return
     * @throw 370801    HETInputBind::loadMole    Error while reading file
     * @throw 370101   HETInputAbst::setPosition       File is not opened
     * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
     */
    HETEntry* loadMole(const std::string& pHET, const bool &wMatrix=true) throw(ProtExcept);
};
}
#endif // HETINPUT_H

