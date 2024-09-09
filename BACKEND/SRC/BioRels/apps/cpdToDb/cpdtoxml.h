//
// Created by c188973 on 10/26/16.
//

#ifndef GC3TK_CPP_CPDTOXML_H
#define GC3TK_CPP_CPDTOXML_H


#include "headers/proc/pugixml.hpp"
#include "compound.h"

class CpdToXML {

protected:
    pugi::xml_document  mDocument;
    pugi::xml_node mResidues;
    pugi::xml_node mWorkflows;


    std::string getTime()const;
    void addResidueData(const Compound& pCompound,pugi::xml_node& residue);
    void addMoleculeData(const Compound& pCompound,pugi::xml_node& residue);
    void addAtomData(const Compound& pCompound,pugi::xml_node& residue);
    void addBondData(const Compound& pCompound,pugi::xml_node& residue);
    std::map<std::string,bool> mListStructureReady;
public:

    /**
     * @brief sendToDB
     * @throw 2310201   CpdToXML::sendToDB  Unable to init curl
     * @throw 2310202   CpdToXML::sendToDB  Unable to execute request
     * @throw 2310203   CpdToXML::sendToDB  Cannot find SUCCESS in webservice answer
     */

    CpdToXML();
    void prepareHeader();
    void processEntry(const Compound& pCompound);

    /**
     * @brief exportFile
     * @param pFile
     * @throw 2310101  CpdToXML::exportFile            Unable to open file
     */
    void exportFile(const std::string& pFile)const;
    void addStructure(const std::string& pName,const bool& success)
    {
        mListStructureReady.insert(std::make_pair(pName,success));
    }

    void processStructures();
};


#endif //GC3TK_CPP_CPDTOXML_H

