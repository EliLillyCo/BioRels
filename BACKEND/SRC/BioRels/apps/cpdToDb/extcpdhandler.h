#ifndef EXTCPDHANDLER_H
#define EXTCPDHANDLER_H

#include "headers/statics/argcv.h"
#include "mmcifreader.h"
class Compound;
class ExtCpdHandler
{
protected:
    std::string mPublicCifFile;
    std::vector<std::string> mListInputLine;
    std::vector<std::string> mListReady;
    MMCifReader mReader;
public:
    ExtCpdHandler();

    void setCifFile(const std::string& pFile);
    /**
     * @brief proceed
     * @throw 030303    Bad allocation
     * @throw 310101    MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
     * @throw 310102    MMAtom::setAtomicName       Atomic name not found
     * @throw 320501    Residue::getAtom            Atom Not found
     * @throw 320502    Residue::getAtom            Atom Not found
     * @throw 350102    MacroMole::addAtom          Bad allocation
     * @throw 350604    MacroMole::addBond          Both atoms are the same
     * @throw 352302    MacroMole::addRingSystem    Bad allocation
     * @throw 2330201   MMCifReader::loadNext       Unable to find HET
     * @throw 2340101   ExtCpdHandler::proceed      No public cif file given
     * @throw 2340102   ExtCpdHandler::proceed      Given residue name is empty
     * @throw 2340103   ExtCpdHandler::proceed      Unable to assign atom type
     * @throw 2320101   MMCIFRes::loadRules         No C3TK_HOME Path set
     * @throw 2320201   MMCifRes::loadFile          Unable to open rule file
     * @throw 2320401  MMCifRes::GetSMILES             No SMILES FOUND
     */
    Compound &proceed(const std::string &pHET);
    /**
     * @brief proceed
     * @throw 030303    Bad allocation
     * @throw 310101    MMAtom::setAtomicName       Given atomic name must have 1,2 or 3 characters
     * @throw 310102    MMAtom::setAtomicName       Atomic name not found
     * @throw 320501    Residue::getAtom            Atom Not found
     * @throw 320502    Residue::getAtom            Atom Not found
     * @throw 350102    MacroMole::addAtom          Bad allocation
     * @throw 350604    MacroMole::addBond          Both atoms are the same
     * @throw 352302    MacroMole::addRingSystem    Bad allocation
     * @throw 2330201   MMCifReader::loadNext       Unable to find HET
     * @throw 2340101   ExtCpdHandler::proceed      No public cif file given
     * @throw 2340102   ExtCpdHandler::proceed      Given residue name is empty
     * @throw 2340103   ExtCpdHandler::proceed      Unable to assign atom type
     * @throw 2320101   MMCIFRes::loadRules         No C3TK_HOME Path set
     * @throw 2320201   MMCifRes::loadFile          Unable to open rule file
     * @throw 2320401  MMCifRes::GetSMILES             No SMILES FOUND
     */
    Compound &proceed(const size_t& pos);
    size_t numEntries()const{return mListInputLine.size();}

    void loadStream(const std::string& stream);

    /**
     * @brief loadFile
     * @param pFile
     * @throw 2340201  ExtCpdHandler::loadFile         Unable to open file
     */
    void loadFile(const std::string& pFile);

    /**
     * @brief loadFromDb
     * @throw 2340301  ExtCpdHandler::loadFromDB       Reader must be ready
     * @throw 2340302   ExtCpdHandler::loadFromDb       Failed to run query
     * @throw 2340303  ExtCpdHandler::loadFromDb       Issue while executing query
     * @throw 700301   Database::execute               Unable to execute query
     */
    void loadFromDb();
    const std::string& getName(const size_t& pos)const{return mListInputLine.at(pos);}
    bool isReady(const std::string& pName)const{return (std::find(mListReady.begin(),mListReady.end(),pName)!=mListReady.end());}

    void getExistingList(const std::string &pFile);
};

#endif // EXTCPDHANDLER_H
