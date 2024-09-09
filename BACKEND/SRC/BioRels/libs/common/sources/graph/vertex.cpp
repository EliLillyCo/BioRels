#include <fstream>
#include "headers/graph/edge.h"
#include "headers/graph/graph.h"
#undef NDEBUG /// Active assertion in release
protspace::Vertex::Vertex(const unsigned int& id, Graph * const graph):
    protspace::dot<protspace::Graph,protspace::Vertex>(graph,id,id)
{}

protspace::Vertex::~Vertex(){}



void protspace::Vertex::addEdge(Edge& edge) throw(ProtExcept)
try{
    add(edge.getOther(this)->getMID(),edge.getMID());
}catch(ProtExcept &e)
{e.addHierarchy("Vertex::addEdge");throw;
}





void protspace::Vertex::delEdge(Edge& edge) throw(ProtExcept)
try
{
    delLink(edge.getMID());
}catch(ProtExcept &e) {e.addHierarchy("Vertex::delEdge");throw;}


protspace::Edge&
protspace::Vertex::getEdge(const size_t& pos)const throw(ProtExcept)
{
    if (pos >= mListLinks.size())
        throw_line("060101",
                   "Vertex::getEdge",
                   "Given position is above the number of edges for this vertex");
    return mParent->getEdge(mListLinks.at(pos));
}








protspace::Vertex&
protspace::Vertex::getVertex(const size_t& pos)const  throw(ProtExcept)
{
    if (pos >= mListDots.size())
        throw_line("060201",
                   "Vertex::getVertex",
                   "Given position is above the number of vertexs linked to this vertex");
    return mParent->getVertex(mListDots.at(pos));
}



bool protspace::Vertex::hasEdgeWith(const Vertex& vertex) const
{
    return std::find(mListDots.begin(),
                     mListDots.end(),
                     vertex.getMID()) != mListDots.end();
}




protspace::Edge&
protspace::Vertex::getEdgeWith(const Vertex& vertex) const throw(ProtExcept)
{
    for(size_t i=0;i< mListDots.size();++i)
    {
        if (mListDots.at(i)!=vertex.getMID())continue;
        return mParent->getEdge((const size_t &) mListLinks.at(i));
    }

    throw_line("060203",
               "Vertex::getEdgeWith",
               "Given vertex does not make any edge with this vertex");

}










void protspace::Vertex::setEdgeNum(const int& former, const int&newer)
{
    for (size_t i=0;i<mListLinks.size();++i)
    {
        if (mListLinks.at(i)==former)
            mListLinks.at(i)=newer;
    }

}


void protspace::Vertex::serialize(std::ofstream &out) const
{
    // READING ID
    out.write((char*)&mFId,sizeof(mFId));
    // READING ID
    out.write((char*)&mMId,sizeof(mMId));

}

void protspace::Vertex::unserialize(std::ifstream& ifs)
{
    // READING ID of molecule
    ifs.read((char*)&mFId,sizeof(int));
    ifs.read((char*)&mMId,sizeof(int));
}

