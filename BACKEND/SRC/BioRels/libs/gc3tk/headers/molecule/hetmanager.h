#ifndef HETMANAGER_H
#define HETMANAGER_H
#include <fstream>
#include <memory>
#include "hetentry.h"
#include "hetinput.h"
#include "headers/statics/logger.h"
#include "headers/statics/protpool.h"
namespace protspace
{
class HETManager
{
    friend class DBResMap;
    typedef std::auto_ptr<HETManager> HETManagerPtr;
protected:
    static HETManagerPtr& get_instance();

    friend class std::auto_ptr<HETManager>;

    HETManager& operator=(const HETManager&);

    HETManager(const HETManager&) {}

    HETManager();

    ~HETManager();

    GroupList<HETEntry> mListEntries;

   bool mAllLoaded;

     HETInputAbst* mInput;

     /**
      * @brief open
      * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
      * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370901   HETManager::open                Unable to open files
      */
     void open();

     bool mWBinary;
     /**
      * @brief loadMolecule
      * @param HET
      * @return
      * @throw 370801    HETInputBind::loadMole    Error while reading file
      * @throw 370101   HETInputAbst::setPosition       File is not opened
      * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370403   HETInputFile::loadMole          Bad allocation
      * @throw 350102 Bad allocation
      * @throw 030303 Bad allocation
      */
     HETEntry &loadMolecule(const std::string &HET, const bool &wMatrix, const bool &wCheck=true) throw(ProtExcept);
public:
     static const std::string mHETInternals;
    static void destroy_instance(){delete get_instance().release();}
    static HETManager& Instance(){return *get_instance();}
    static const HETManager& const_instance(){return Instance();}
    void clear();
void setWBinary(const bool& b){mWBinary=b;}

    size_t numEntry()const;
    size_t numLoadedEntry()const;
    bool isInList(const std::string& HET);
    bool isLoaded(const std::string& HET, size_t& pos)const;

    /**
     * @brief getEntry
     * @param HET
     * @return
     * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
     * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
     * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
     * @throw 370901   HETManager::open                Unable to open files
      * @throw 370801    HETInputBind::loadMole    Error while reading file
      * @throw 370101   HETInputAbst::setPosition       File is not opened
      * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370403   HETInputFile::loadMole          Bad allocation
      * @throw 350102 Bad allocation
      * @throw 030303 Bad allocation
     */
    HETEntry &getEntry(std::string HET,
                       const bool &wMatrix=true,
                       const bool &check=true) throw(ProtExcept);
    HETEntry& getEntry(const size_t& pos){return mListEntries.get(pos);}

    /**
     * @brief loadAll
     * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
     * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
     * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
     * @throw 370901   HETManager::open                Unable to open files
      * @throw 370801    HETInputBind::loadMole    Error while reading file
      * @throw 370101   HETInputAbst::setPosition       File is not opened
      * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370403   HETInputFile::loadMole          Bad allocation
      * @throw 350102 Bad allocation
      * @throw 030303 Bad allocation
     */
    void loadAll();

    /**
     * @brief exportFile
     * @param pBinFile
     * @param pPosFile
     * @throw 371001   HETManager::exportFile          Unable to open binary file
     * @throw 371002   HETManager::exportFile          Unable to open position file
     */
    void exportFile(const std::string& pBinFile,
                    const std::string& pPosFile)const;

    /**
     * @brief getPossibleMatch
     * @param nC
     * @param nO
     * @param nN
     * @param list
      * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
      * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370901   HETManager::open                Unable to open files
      * @throw 370801    HETInputBind::loadMole    Error while reading file
      * @throw 370101   HETInputAbst::setPosition       File is not opened
      * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370403   HETInputFile::loadMole          Bad allocation
      * @throw 350102 Bad allocation
      * @throw 030303 Bad allocation
     */
    void getPossibleMatch(const size_t& nC,
                          const size_t& nO,
                          const size_t& nN,
                          std::vector<StringPoolObj> &list);

    /**
     * @brief getExactMatch
     * @param nC
     * @param nO
     * @param nN
     * @param list
      * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
      * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
      * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
      * @throw 370901   HETManager::open                Unable to open files
      * @throw 370801    HETInputBind::loadMole    Error while reading file
      * @throw 370101   HETInputAbst::setPosition       File is not opened
      * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
      * @throw 370403   HETInputFile::loadMole          Bad allocation
      * @throw 350102 Bad allocation
      * @throw 030303 Bad allocation
     */
    void getExactMatch(const size_t& nC,
                          const size_t& nO,
                          const size_t& nN,
                          std::vector<protspace::StringPoolObj>& list);

    /**
    * @throw 370601   HETInputBin::loadPosition       NO GC3TK_HOME parameter defined
    * @throw 370701    HETInputBin::openBinary        NO GC3TK_HOME parameter defined;
    * @throw 370301   HETInputFile::loadPositions     NO GC3TK_HOME parameter defined
    * @throw 370901   HETManager::open                Unable to open files
     * @throw 370801    HETInputBind::loadMole    Error while reading file
     * @throw 370101   HETInputAbst::setPosition       File is not opened
     * @throw 370201   HETInputAbst::setPosition       No Molecule by this name found
     * @throw 370403   HETInputFile::loadMole          Bad allocation
     * @throw 350102 Bad allocation
     * @throw 030303 Bad allocation
    */
    void assignResidueType(
            MacroMole& molecule,
            const bool &isInternal,
            const bool& setUpdatedVisible) throw(ProtExcept);

    void addEntry(HETEntry* entry){mListEntries.add(entry);}


    /**
     * @brief toHETList
     * @param pFile
     * @throw 371101   HETManager::toHETList           Unable to open file
     */
    void toHETList(const std::string& pFile);

    std::vector<protspace::StringPoolObj> getPositions();
};
}
#endif // HETMANAGER_H
