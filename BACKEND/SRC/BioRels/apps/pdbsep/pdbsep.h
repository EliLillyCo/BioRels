#ifndef PDBSEP_H
#define PDBSEP_H
#include <fstream>
#include "headers/molecule/macromole.h"
#include "headers/parser/writerPDB.h"
#include "headers/math/grid.h"
#include "headers/proc/pugixml.hpp"


struct LigEntry
{
    std::string type;
    std::vector<protspace::MMResidue*> listRes;
};


class PDBSep
{
    pugi::xml_document  mDocument;






    protspace::MacroMole& mMole;
    std::string mHEAD;
    std::vector<protspace::MMResidue*> mListSingleton;
    std::vector<protspace::MMResidue*> mWater;
    std::vector<std::vector<protspace::MMResidue*>> mListGroup;
    std::vector<LigEntry> mListLigs;
    std::map<std::string,std::string> listEntries;
    std::ofstream mConvert,mSiteMap,mVolSite,mSiteFile;
    protspace::WriterBase* wpdb;
    protspace::Grid mGrid;
    bool mWConvert, mWSiteMap,mWVolSite;
    bool mWSingleChain,mWReceptor,mWTrimer,mWPPI,mWLigand;
    short mNGroup;
    std::string mOutFormat;
    void perceiveChains();
public:
    PDBSep(protspace::MacroMole& pMole,const std::string& pHEAD);
    ~PDBSep();
    void setConvertPath(const std::string& pFile);
    void setSiteMapPath(const std::string& pFile);
    void setVolSitePath(const std::string& pFile);
    void setWSingleChain(const bool& p){mWSingleChain=p;}
    void setWReceptor   (const bool& p){mWReceptor=p;}
    void setWTrimer     (const bool& p){mWTrimer=p;}
    void setWPPI        (const bool& p){mWPPI=p;}
    void setOutFormat   (const std::string& p);
    void setWLigand     (const bool& p){mWLigand=p;}
    void proceed();
    void mol2xml(const std::string& pOutFile);
protected:
    void genReceptor();
    void genSingleChain();
    void genTrimer();
    void genPPI();
    void genLigand();
    void prepSingleReceptor(const std::string &pChainName, const std::string &pFName);
    void addVolSiteLine(const std::string &pFname);
    void addSiteMapLine(const std::string &pFnameP, const std::string &pFnameL);
    void addConvertLine(const std::string &pFname);
    void prepPPI(const std::string &pChain1, const std::string &pChain2, const std::string &pFName);
    void prepTrimer(const std::string &pChain1, const std::string &pChain2, const std::string &pChain3, const std::string &pFName);
private:
    void group();

};

#endif // PDBSEP_H
