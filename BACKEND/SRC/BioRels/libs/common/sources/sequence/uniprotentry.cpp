#include <streambuf>
#include <stdlib.h>
#include <fstream>
#include <sstream>
#include <iostream>
#include "headers/sequence/uniprotentry.h"
#include "headers/statics/strutils.h"
#include "headers/parser/ofstream_utils.h"
#undef NDEBUG /// Active assertion in release

protspace::UniprotEntry::UniprotEntry(const std::string& ACtoLoad) :
    mAC(ACtoLoad),
    mSequence(ACtoLoad),
    mIsNameUniID(true),
    mEC(""),
    mName(""),
    mDBID(0)
{

    removeAllSpace(mAC);
    if (mAC.empty())
        throw_line("530101",
                         "UniprotEntry::UniprotEntry",
                         "given AC must not be empty");
}


void protspace::UniprotEntry::perceiveLines(const std::vector<std::string>& fFileLines)
{
    mRawUniData.clear();
    std::string head, dataline;
    // Scanning line in file.
    // Each line start with 2 characters has headers
    // We split each line and put it in an array with key=header and
    // value=everything else in the line
    for(const std::string&line:fFileLines)\
    {
        if (line=="//" || line.length()==0)break;

        if (mIsNameUniID && line.substr(0,2)=="ID")
        {
            mName=line.substr(5,line.find_first_of(" ",5)-5);
            mSequence.setName(mName);
            mId=mName;
        }
        // SQ is the only header written once but can be used
        // accross all lines below it.
        // So when we find it, we keep it even if the first two characters
        // of the line are not the same
        if (line.substr(0,2)=="  " && head == "SQ") head ="SQ";
        else head = line.substr(0,2);
        dataline=line.substr(3);
        mRawUniData.insert(std::make_pair(head,dataline));
    }
}


void protspace::UniprotEntry::loadData(const std::string& mPath) throw(ProtExcept)
{

    std::ifstream ifs(mPath);
    if (!ifs.is_open())
        throw_line("530201",
                   "UniprotEntry::loadData",
                   "Unable to open file "+mPath);
    std::vector<std::string> fFileLines;
    std::string line="";

    while (!ifs.eof())
    {
        safeGetline(ifs,line);
        fFileLines.push_back(line);
    }
    ifs.close();



    perceiveLines(fFileLines);
    // From mRawUniData filled, we can load the sequence, the ACs and the features
    loadSequence();
    loadACs();
    loadFeatures();

    /// We reuse fFileLines for the tokenReuseStr
    for(std::string& l:fFileLines)l="";
    line="";
    if (fFileLines.size()<10)
        for(unsigned char i=0;i<10;++i)fFileLines.push_back(line);

    const auto ret = mRawUniData.equal_range("DR");
    for (filemapperit it = ret.first; it != ret.second;++it)
    {

        if (it->second.length() < 5) continue;
        if (it->second.substr(2,4) != "PDB;") continue;
        tokenReuseStr(it->second,fFileLines,"; ");
        mListPDB.push_back(fFileLines.at(1));
    }
}




void protspace::UniprotEntry::searchEC()
{
    const auto ret(mRawUniData.equal_range("DE"));
    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        const std::string& str(it->second);
        const size_t pos = str.find("EC");
        if (pos == std::string::npos)continue;
        mEC=str.substr(pos+3,str.find_last_of(";")-pos-3);
    }
}




void protspace::UniprotEntry::loadSequence()  throw(ProtExcept)
{

    const auto ret(mRawUniData.equal_range("SQ"));
    size_t nline=0;

    for (filemapperit
         it = ret.first;
         it != ret.second;++it)
    {
        nline++;
        if (nline==1)continue;

        std::string vectorLine(it->second);
        size_t pos(vectorLine.find_first_of(" "));
        while (pos != std::string::npos)
        {
            vectorLine.replace(pos,1,"");
            pos = vectorLine.find_first_of(" ");
        }
        for(const char& ch:vectorLine)mSequence.push_back(ch);

    }

}






void protspace::UniprotEntry::loadACs()
{
    const auto ret(mRawUniData.equal_range("AC"));

    std::vector<std::string> tokens;

    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        tokens.clear();
        tokenStr(it->second,tokens,"; ");
        for (size_t iTok = 0; iTok < tokens.size();++iTok)
        {
            std::string &val=tokens.at(iTok);
            const size_t pos=val.find_first_not_of(" ");
            if (pos!=0&& pos!=std::string::npos)val=val.substr(pos);
            const size_t posend=val.find_last_not_of(" ");
            if (posend != val.length()-1)val=val.substr(0,posend-1);

            if (iTok==0 && it==ret.first) mAC=val;
            else mListACs.push_back(val);
        }

    }
    if (!mIsNameUniID) mName=mAC;
}





bool protspace::UniprotEntry::isACinList(const std::string& ACtocheck)
{
    if (mListACs.empty() && mAC == "")loadACs();
    if (ACtocheck==mAC){return true;}
    for (size_t i=0; i < mListACs.size(); ++i)
    {
        if (ACtocheck == mListACs.at(i))return true;
    }
    return false;
}





const std::vector<std::string>& protspace::UniprotEntry::getPDBList() const
{
    return mListPDB;
}




void protspace::UniprotEntry::loadFeatures()
{
    if (!mListFeature.empty())return;
    const auto ret(mRawUniData.equal_range("FT"));
    size_t pos_start,pos_end;
    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        pos_start=it->second.find_first_not_of(" ",3);
        if (pos_start != 3 )continue;
        pos_end=it->second.find_first_of(" ",pos_start);
        Feature feat; feat.FeatureType=it->second.substr(pos_start-1,pos_end-pos_start+1);
        pos_start=it->second.find_first_not_of(" ",pos_end); //cout << pos_start<<endl;
        pos_end  =it->second.find_first_of(" ",pos_start);
        feat.start= (unsigned short) atoi(it->second.substr(pos_start, pos_end - pos_start).c_str());
        pos_start=it->second.find_first_not_of(" ",pos_end); //cout << pos_start<<endl;
        pos_end  =it->second.find_first_of(" ",pos_start);
        feat.end = (unsigned short) atoi(it->second.substr(pos_start, pos_end - pos_start).c_str());
        pos_start=it->second.find_first_not_of(" ",pos_end);
        if (pos_start == std::string::npos) { feat.Description="";mListFeature.push_back(feat);continue;}
        pos_end  =it->second.find_first_of(" ",pos_start);
        feat.Description=it->second.substr(pos_start,pos_end-pos_start);
        mListFeature.push_back(feat);
    }
}




const std::pair<protspace::UniprotEntry::filemapperit,
     protspace::UniprotEntry::filemapperit> protspace::UniprotEntry::getData(const std::string& header)const
{

return mRawUniData.equal_range(header);
}


std::string protspace::UniprotEntry::getUniprotID()const
{
    const auto ret(mRawUniData.equal_range("ID"));
    size_t pos_start,pos_end;
    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        pos_start=it->second.find_first_not_of(" ",3);
        if (pos_start != 3 )continue;
        pos_end=it->second.find_first_of(" ",pos_start);
        return it->second.substr(pos_start-1,pos_end-pos_start+1);
    }
    return "Unknown";
}



std::string protspace::UniprotEntry::getGeneName()const
{
    const auto ret(mRawUniData.equal_range("GN"));
    size_t pos_start,pos_end;
    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        pos_start=it->second.find_first_of("=",3);
        pos_end=it->second.find_first_of(";",pos_start);

        return it->second.substr(pos_start+1,pos_end-pos_start+1);
    }
    return "Unknown";
}



void protspace::UniprotEntry::getOrganism(std::string& val)const
{
    const auto ret(mRawUniData.equal_range("OS"));

    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        val= it->second.substr(4);
    }
    val="Unknown";
}



void protspace::UniprotEntry::getTaxID(std::string& val)const
{
    const auto  ret(mRawUniData.equal_range("OX"));
    size_t pos_start,pos_end;
    for (filemapperit it = ret.first; it != ret.second;++it)
    {
        pos_start=it->second.find_first_of("=",3);
        pos_end=it->second.find_first_of(";",pos_start);

        val= it->second.substr(pos_start+1,pos_end-pos_start+1);
    }
    val="Unknown";
}
