#ifndef STRUTILS_H
#define STRUTILS_H

#include <string>
#include "headers/statics/objectpool.h"
namespace protspace{


void toLowercase(std::string& lowecase);



void toUppercase(std::string& lowecase);



void removeAllSpace(std::string& input);

size_t tokenReuseStr(const std::string& str,
                     std::vector<std::string> &tokens,
                     const std::string& delimiters,
                     const bool& allowEmpty=false,
                     const size_t& pStart=0);

void tokenStr(const std::string& str,
              std::vector<std::string>& tokens,
              const std::string& delimiters,
              const bool allowEmpty=false);

std::string getEnvVariable(const std::string& envVariable);


std::string removeSpaces(const std::string& input);



template<typename T> void iniArray(T* arr[],const size_t& len,const T& val)
{
    for(size_t i=0;i<len;++i)arr[i]=val;
}

bool testTokens(const std::string& str,
                std::vector<std::string>& tokens,
                const size_t& expectedSize,
                const bool& allowEmpty=false);



std::string getLongestSMILES(const std::string& input);


std::string getTime();



bool isStrDigit(const std::string& str);


std::string removeBegEndSpace(const std::string& input);

void replaceAll(std::string& str,
                const std::string& from,
                const std::string& to);

bool isInList(const std::string& pList,const std::string& pQuery);

size_t getPos(const std::vector<std::string>& pList,const std::string& pQuery);
}



#endif // STRUTILS_H

