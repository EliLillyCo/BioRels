#ifndef STRING_CONVERT_H
#define STRING_CONVERT_H

#include <string>

namespace protspace
{
class Vertex;
class MMBond;
class Edge;
class Graph;
}
std::string operator+(std::string out,const protspace::Vertex& ve);
std::string operator+(std::string out, const protspace::Graph &gr);
std::string operator+(std::string out,const protspace::Edge &qq);
std::string operator+(const std::string& out, const protspace::MMBond &bd);

#endif // STRING_CONVERT_H

