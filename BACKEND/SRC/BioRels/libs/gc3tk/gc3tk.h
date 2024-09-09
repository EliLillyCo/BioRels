#ifndef GC3TK_H
#define GC3TK_H

#include <string>

namespace protspace
{



std::string getTG_DIR_HOME();
std::string getHETList();
std::string getHETListPosit();
std::string getTG_DIR_STATIC();
std::string getHETListFlat();

void setAltTG_DIR_PATH(const std::string& pPath);
std::string getAltTG_DIR_PATH();
}

#endif // GC3TK_H
