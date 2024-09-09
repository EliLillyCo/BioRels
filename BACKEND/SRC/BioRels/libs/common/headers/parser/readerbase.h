#ifndef READERBASE_H
#define READERBASE_H
#include <string>
#include <fstream>
#include <cstdint>
#include "headers/statics/protExcept.h"
namespace protspace
{
class MacroMole;
class ReaderBase
{
protected:

    ///
    /// \brief The chainRule struct helps to consider molecular types
    /// for different chains
    ///
    struct chainRule
    {
      std::string mChainName;
      uint16_t mMoleType;
    };

    ///
    /// \brief Stream used to read file
    ///
    std::ifstream mIfs;

    ///
    /// \brief  boolean set to TRUE when ifs gets to the end of file
    ///
    bool mIsEOF;

    ///
    /// \brief mFposition In the case of loaded file in memory, tell the line number
    ///
    size_t mFposition;

    ///
    /// \brief Path of the file
    ///
    std::string mFilePath;

    ///
    /// \brief List of rules for chain to keep
    ///
    std::vector<chainRule> mListChainsRule;

    ///
    /// \brief When only some chains are to be kept, bonds that will be associated
    /// with atoms of filtered chain must be flagged in order to avoid adding an error
    ///
    std::vector<int> mIgnoredAtom;

    std::vector<int> mListExclusionResidue;

    size_t mLignePos;
    std::string& mLigne;

    void cleaning();
    void getChainRules();



    static bool mForceResCheck;


public:
    virtual ~ReaderBase();
    static void setForceResCheck(const bool& val);
    ReaderBase(const std::string& path="");


    ///
    /// \brief Tell whether the cursor is at the end of the file
    /// \return True when the cursor is at the end of the file,false otherwise
    ///
     bool isEOF()const{return mIfs.eof();}


     ///
     /// \brief Open the file either already in or given in parameter
     /// \param path_f Path of the file
     /// \throw 400101   ReaderBase::open      No file given
     /// \throw 400102   ReaderBase::open      Unable to open file
     void open(const std::string&path_f="")  throw(ProtExcept);

     ///
     /// \brief Get the next line of the file
     /// \param line Current file line will be saved in this parameter
     /// \return
     ///TODO consider windows end of line
     bool getLine();

     bool getLine(std::string& pLine);

     ///
     /// \brief Return the position of the file pointer
     /// \return Position of the file pointer
     ///
     size_t getFilePos();


     ///
     /// \brief List of chain to be kept
     /// \param list String with all the chain to be loaded e.g. ABD
     ///
     void setFilterChain(const std::string& list);

     void close();


     bool checkChainRule(const std::string& pChain)const;
    virtual  void load(MacroMole& pLig)=0;
     void correctName(std::string& pName)const;

     bool is_open()const{return mIfs.is_open();}
     void to_begin();
};

}
#endif // READERBASE_H

