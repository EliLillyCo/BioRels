#ifndef CPDTODB_H
#define CPDTODB_H

#include "headers/statics/argcv.h"
#include "compound.h"
#include "extcpdhandler.h"
#include "cpdtoxml.h"

class CpdToDB
{

    const protspace::Argcv& mArgs;
protspace::GroupList<CpdToXML> mXML;
    ExtCpdHandler mExtProcess;
    bool mOverall;
    bool mVerbose;
    protspace::GroupList<Compound> mListCompounds;
    std::vector<size_t> mListNew;


    /**
     * @brief processInternals
     * @throw 030303    Group::createLink                   Bad allocation
     * @throw 350102    MacroMole::addAtom                  Bad allocation
     * @throw 350102    MacroMole::addAtom                  Bad allocation
     * @throw 351401    MacroMole::addChain                 Bad Allocation
     * @throw 351502    MacroMole::addResidue               Bad allocation
     * @throw 352302    MacroMole::addRingSystem            Bad allocation
     * @throw 700101   DataBase::open                  Could not establish connection
     * @throw 2300401  IntCpdHandler::loadInternalReadyFromDb    Failed to run query
     * @throw 2300402  IntCpdHandler::loadInternalReadyFromDb      Issue while executing query
     * @throw 2300301  IntCpdHandler::loadFile         Unable to open file
     * @throw 2300302  IntCpdHandler::loadFile         Line should only have 2 fields
     * @throw 2300601  IntCpdHandler::loadStream       Line should only have 2 fields
     * @throw 2300201  CpdToDbHandler::loadInternalFromDb    Failed to run query
     * @throw 2300202  CpdToDbHandler::loadInternalFromDb      Issue while executing query
     */
    void processInternals();

    /**
     * @brief processExternals
     * @throw 2330101  MMCifReader::getFilePos         Unable to open file
     * @throw 2350101  CpdToDB::processExternal        -c Option required
     * @throw 2340201  ExtCpdHandler::loadFile         Unable to open file
     * @throw 2340301  ExtCpdHandler::loadFromDB       Reader must be ready
     * @throw 030303    Bad allocation
     * @throw 350102    MacroMole::addAtom          Bad allocation
     * @throw 352302    MacroMole::addRingSystem    Bad allocation
     * @throw 2340101   ExtCpdHandler::proceed      No public cif file given
     * @throw 2320101   MMCIFRes::loadRules         No C3TK_HOME Path set
     * @throw 2320201   MMCifRes::loadFile          Unable to open rule file
     */
    void processExternals();

    /**
     * @brief processStructures
     * @throw 2350201  CpdToDB::processStructures       List of 3D structures missing
     * @throw 2350202  CpdToDB::processStructures     Unexpected number of columns
     * @throw 420301   ReadPDB::load           Unable to read file - Memory allocation issue
     */
    void processStructures();
    bool checkName(const std::string& pName)const;
public:

    /**
 * @brief outputXMLFile
 * @param pFile
 * @throw 2310101  CpdToXML::exportFile            Unable to open file
 */
    void    outputXMLFile(const std::string& pFile);
    void generateXML();


    /**
     * @brief CpdToDB
     * @param args
     * @throw 2300101  IntCpdHandler::loadIntExtMap    Unable to open internal/external name map
     *  @throw 2300102   IntCpdHandler::loadIntExtMap    C3TK_HOME not set
     */
    CpdToDB(const protspace::Argcv& args);

    /**
     * @brief run
     * @throw 700101   DataBase::open                  Could not establish connection
     * @throw 2300401  IntCpdHandler::loadInternalReadyFromDb    Failed to run query
     * @throw 2300402  IntCpdHandler::loadInternalReadyFromDb      Issue while executing query
     * @throw 2310201   CpdToXML::sendToDB  Unable to init curl
     * @throw 2310202   CpdToXML::sendToDB  Unable to execute request
     * @throw 2310203   CpdToXML::sendToDB  Cannot find SUCCESS in webservice answer
     */
    void run();

    /**
     * @brief pushCpdtoHETManager
     */
    ///TODO: Add exception
    void pushCpdtoHETManager();

    void outputSDF(const std::string& pFile);


    ///TODO : add Exception
    void outputBinaryFile(const std::string& pFile, const bool &withoutPreviousVersion);
protected:
    void sendToDB();
};

#endif // CPDTODB_H
