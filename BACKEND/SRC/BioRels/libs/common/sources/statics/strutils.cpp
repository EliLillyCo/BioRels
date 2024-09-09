#include <algorithm>
#include <stdlib.h>
#include <iostream>
#include <string.h>
#include <ctime>
#include <malloc.h>

#include "headers/statics/strutils.h"
#undef NDEBUG /// Active assertion in release
using namespace std;
namespace protspace
{
void toLowercase(std::string& lowecase)
{
    static const int diff='a'-'A';
    for(size_t i=0; i < lowecase.length();++i)
    {
        if (lowecase[i]>='A' && lowecase[i]<='Z') lowecase[i]+=diff;
    }
}


void toUppercase(std::string& lowecase)
{
    static const int diff='A'-'a';
    for(size_t i=1; i < lowecase.length();++i)
    {
        if (lowecase[i]>='a' && lowecase[i]<='z') lowecase[i]+=diff;
    }
}



void removeAllSpace(std::string& input)
{
    input.erase(remove_if(input.begin(),input.end(),::isspace),input.end());
}

std::string removeBegEndSpace(const std::string& input)
{
    const size_t posB=input.find_first_not_of(" ");
    const size_t posE=input.find_last_not_of(" ");
    if (posE==std::string::npos)return "";
    return input.substr(posB,posE-posB+1);
}



std::string removeSpaces(const std::string& input)
{
    std::string t(input);
    t.erase(remove_if(t.begin(),t.end(),::isspace),t.end());

    return t;
}
size_t getPos(const std::vector<std::string>& pList,const std::string& pQuery)
{
    const auto it =std::find(pList.begin(),pList.end(),pQuery);
    if (it ==pList.end()) return pList.size();
    return std::distance(pList.begin(),it);
}


bool isInList(const std::string& pList,const std::string& pQuery)
{
    return (pList.find(" "+pQuery+" ")!=std::string::npos);
}


size_t tokenReuseStr(const std::string& str,
                     std::vector<std::string>& tokens,
                     const std::string& delimiters,
                     const bool &allowEmpty,
                     const size_t &pStart)
{
    const size_t allowed(tokens.size());
    size_t pos=pStart;
    char *p = (char *)alloca(str.size() + 1);
    memcpy(p, str.c_str(), str.size() + 1);
    char *c = (char *)alloca(delimiters.size() + 1);
    memcpy(c, delimiters.c_str(), delimiters.size() + 1);

    do
    {
        const char *begin = p;

        while(*p != *c && *p)
            p++;
        if (!allowEmpty && p-begin ==0)continue;
        if (pos==allowed)
            throw_line("070101","tokenReuseStr","Position above the number of values");
        tokens.at(pos).assign(begin, p-begin);
        ++pos;
    } while (0 != *p++);
    for(size_t pos2=pos; pos2 < allowed;++pos2)tokens[pos2]="";
    return pos;

}




void tokenStr(const std::string& str,
              std::vector<std::string>& tokens,
              const std::string& delimiters, const bool allowEmpty)
{


    char *p = (char *)alloca(str.size() + 1);
    memcpy(p, str.c_str(), str.size() + 1);
    char *c = (char *)alloca(delimiters.size() + 1);
    memcpy(c, delimiters.c_str(), delimiters.size() + 1);

    do
    {
        const char *begin = p;

        while(*p != *c && *p)
            p++;
        if (!allowEmpty && p-begin ==0)continue;

        tokens.push_back(std::string(begin, p-begin));
    } while (0 != *p++);

    return ;

}




bool testTokens(const std::string& str,
                std::vector<std::string>& tokens,
                const size_t& expectedSize,
                const bool& allowEmpty)
{
    static const std::string delims[3]={"\t"," ",","};
    for(size_t i=0;i<3;++i)
    {
        tokens.clear();
        tokenStr(str,tokens,delims[i],allowEmpty);
        if (expectedSize==tokens.size())return true;
    }
    return false;
}



std::string getEnvVariable(const std::string& envVariable)
{

    char* pPath;
    pPath=getenv(envVariable.c_str());
    if (pPath==NULL) return "";
    else return pPath;

}







std::string getLongestSMILES(const std::string& input)
{

    if (input.find(".")==string::npos)return input;

    vector<string> toks;

    tokenStr(input,toks,".");
    if (toks.empty())return input;
    size_t max=0,posr=0;

    for(size_t it=0;it<toks.size();++it)
    {
        if (toks.at(it).length()<max)continue;
        max=toks.at(it).length();
        posr=it;
    }
    return toks.at(posr);

}



std::string getTime()
{
    const time_t now=time(0);
    struct tm tstruct;
    char buf[80];
    tstruct = *localtime(&now);
    strftime(buf,sizeof(buf),"%m/%d/%G %R:%S",&tstruct);
    return buf;
}



bool isStrDigit(const std::string& str)
{
    for(size_t i=0;i<str.length();++i)
    {
        if (!isdigit(str[i]))return false;
    }
    return true;
}




/// Taken from http://stackoverflow.com/questions/2896600/how-to-replace-all-occurrences-of-a-character-in-string
void replaceAll(std::string& str, const std::string& from, const std::string& to) {
    size_t start_pos = 0;
    while((start_pos = str.find(from, start_pos)) != std::string::npos) {
        str.replace(start_pos, from.length(), to);
        start_pos += to.length(); // Handles case where 'to' is a substring of 'from'
    }

}

}
