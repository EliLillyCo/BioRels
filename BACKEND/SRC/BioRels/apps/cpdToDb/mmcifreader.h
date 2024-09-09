#ifndef MMCIFREADER_H
#define MMCIFREADER_H

#include <fstream>
#include "headers/statics/protExcept.h"
#include "mmcifres.h"

class MMCifReader
{
private:
    typedef void (MMCifReader::*PtrFunct) (const std::vector<std::vector<std::string>>&,
                                           MMCifRes& entry);
    ///
    /// \brief Input stream file of the cif file
    ///
    std::ifstream mIfs;


    ///
    /// \brief Filtering rules for the _chem_comp block
    ///
    /// If empty, all lines starting by _chem_comp will be considered
    /// Otherwise, only lines defined in mChemCompRules object will be
    /// considered.
    ///
    std::vector<std::string> mChemCompRules;

    std::string HETCode;

    std::map<std::string,int> mCountCheck;
    std::map<std::string,PtrFunct> mGroupFunc;
    std::map<std::string,size_t> mFilePos;
    bool mIsCat;
    bool mIsLoop;
    bool mIsReady;
    std::string removeQuote(const std::string& line)const;
    std::string getFullData(const bool& useHET=false);
    bool checkSize(const std::vector<std::vector<std::string>>& list,
                   const size_t& nSize) const;
    void loadCategory(std::vector<std::string>& categoriesList,
                      std::vector<std::string>& block, const std::string &line);
    bool findValue(const std::string& header,
                   const std::vector<std::string>& categories,
                   const std::vector<std::string>& values,
                   std::string& value)const;


    /**
     * @brief getFilePos
     * @param path
     * @throw 2330101  MMCifReader::getFilePos         Unable to open file
     */
    void getFilePos(const std::string & path);
public:
    const bool& isReady()const{return mIsReady;}
    /**
     * @brief MMCifReader
     * @param path
     * @throw 2330101  MMCifReader::getFilePos         Unable to open file
     */
    MMCifReader(const std::string& path="");

    /**
     * @brief setCifFile
     * @param path
     * @throw 2330101  MMCifReader::getFilePos         Unable to open file
     */
    void setCifFile(const std::string& path);
    /**
     * @brief loadNext
     * @param entry
     * @param selHET
     * @return
     * @throw 2330201  MMCifReader::loadNext           Unable to find HET
     * @throw 350102 MacroMole::addAtom Bad allocation
     * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
     * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     * @throw 320501     Residue::getAtom    Atom Not found
     * @throw 320502     Residue::getAtom    Atom Not found
     * @throw 350604   MacroMole::addBond          Both atoms are the same
     * @throw 030303 Bad allocation
     */
    bool loadNext(MMCifRes& entry, const std::string &selHET="");


    bool readBlock(std::string& groupHead,
                   std::vector<std::vector<std::string>>& blockres);

    void chemCompToRes(const std::vector<std::vector<std::string>>&,
                       MMCifRes& entry);

    /**
     * @brief atomToRes
     * @param entry
    * @throw 350102 MacroMole::addAtom Bad allocation
    * @throw 310101    MMAtom::setAtomicName   Given atomic name must have 1,2 or 3 characters
    * @throw 310102   MMAtom::setAtomicName   Atomic name not found
     */
    void atomToRes(const std::vector<std::vector<std::string>>&, MMCifRes& entry);

    /**
     * @brief bondToRes
     * @param entry
    * @throw 320501     Residue::getAtom    Atom Not found
    * @throw 320502     Residue::getAtom    Atom Not found
    * @throw 350604   MacroMole::addBond          Both atoms are the same
    * @throw 030303 Bad allocation
     */
    void bondToRes(const std::vector<std::vector<std::string>>&,MMCifRes& entry);
    void compDesToRes(const std::vector<std::vector<std::string>>&,   MMCifRes& entry);
    void compIdenToRes(const std::vector<std::vector<std::string>>&, MMCifRes& entry);
    void getListHET(std::vector<std::string> &)const;
};


#endif // MMCIFREADER_H
