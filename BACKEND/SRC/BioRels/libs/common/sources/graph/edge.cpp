#include <sstream>
#include <fstream>
#include "headers/graph/edge.h"
#include "headers/graph/vertex.h"
#include "headers/graph/graph.h"
#undef NDEBUG /// Active assertion in release

protspace::Edge::Edge(Graph& graph,
           Vertex& vertex1,
           Vertex& vertex2,
           const int &id,
           const int &type) throw(ProtExcept)
    :protspace::link<protspace::Graph,protspace::Vertex >(graph,id,id,vertex1,vertex2),
      mType(type)
{

}


protspace::Edge::~Edge()
{

}





void protspace::Edge::serialize(std::ofstream& out) const
{
    out.write((char*)&mType,sizeof(int));
    out.write((char*)&mMId,sizeof(int));
    out.write((char*)&mFId,sizeof(int));
    out.write((char*)&mDot1.getMID(),sizeof(int));
    out.write((char*)&mDot2.getMID(),sizeof(int));
}
