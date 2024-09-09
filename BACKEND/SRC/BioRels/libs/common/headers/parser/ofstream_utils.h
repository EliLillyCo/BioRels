#ifndef OFSTREAM_UTILS_H
#define OFSTREAM_UTILS_H

#include <iostream>
#include <fstream>
#include <vector>
namespace protspace
{
class Edge;
class Vertex;
class Graph;
class MMBond;
class MacroMole;
class MMResidue;
class MMRing;
class SeqBase;
class PhysProp;
}
std::ostream & operator << (std::ostream & os, const protspace::Edge &qq);
std::ostream & operator << (std::ostream & out, const protspace::Vertex &ve);
std::ostream & operator << (std::ostream & out, const protspace::Graph &gr);
std::ostream & operator << (std::ostream & out, const protspace::MMBond &bd);
std::ostream& operator <<(std::ostream& out, const protspace::MacroMole& mole);
std::ostream& operator <<(std::ostream& out, const protspace::MMResidue& pRes);
std::ostream& operator <<(std::ostream& out, const protspace::MMRing& pRes);
std::ostream& operator<<(std::ostream& out, const protspace::SeqBase& seq);
std::ostream& operator<<(std::ostream& out, const protspace::PhysProp& seq);
void readSerializedString(std::ifstream& ifs,std::string& value);
void saveSerializedString(std::ofstream& ofs, const std::string& value);
void safeGetline(std::istream& is,std::string& line);

template<class T>void loadSerializedArray(std::ifstream& ifs,std::vector<T>& list)
{
    size_t length;
    ifs.read((char*)&length,sizeof(size_t));
    list.reserve(length);
    T valFr;
    for(size_t i=0;i<length;++i)
    {
        ifs.read((char*)&valFr,sizeof(T));
        list.push_back(valFr);
    }
}
template<class T>void writeSerializedArray(std::ofstream& ofs,const std::vector<T>& list)
{
    const size_t length=list.size();
    ofs.write((char*)&length,sizeof(size_t));
    T val;
    for(size_t i=0;i<length;++i)
    {
        val = list.at(i);
        ofs.write((char*)&val,sizeof(T));

    }
}
#endif // OFSTREAM_UTILS_H

