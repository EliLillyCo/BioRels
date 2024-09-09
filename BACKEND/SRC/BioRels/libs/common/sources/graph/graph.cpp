#include <fstream>
#include "headers/graph/graph.h"
#include "headers/graph/edge.h"
#undef NDEBUG /// Active assertion in release

protspace::Graph::Graph(const size_t& nVertex, const size_t& nEdge) throw(ProtExcept)
    :Group<protspace::Vertex,protspace::Edge,protspace::Graph>(this,true,nVertex,nEdge)
{
    mId=-1;
}

protspace::Graph::Graph(const bool& owner):
    Group<protspace::Vertex,
          protspace::Edge,
          protspace::Graph>(this,owner)
{
    mId=-1;
}

protspace::Graph::Graph(const Graph& pGraph):
    Group<protspace::Vertex,
          protspace::Edge,
          protspace::Graph>(this,pGraph.mOwner)
{
    copy(pGraph);
}

protspace::Graph::~Graph(){ }

protspace::Graph& protspace::Graph::operator=(const protspace::Graph& pGraph)
{
    clear();
    copy(pGraph);
    return *this;
}

protspace::Graph& protspace::Graph::operator+=(const protspace::Graph& pGraph)
{
    copy(pGraph);
    return *this;
}



protspace::Vertex& protspace::Graph::addVertex() throw(ProtExcept)
try{
    return createDot();
}catch(ProtExcept &e)
{e.addHierarchy("Graph::addVertex");throw;}





void protspace::Graph::addVertex(const size_t& counts)throw(ProtExcept)
try{

    for(size_t i=0;i< counts;++i)createDot();
}catch(ProtExcept &e)
{
    e.addHierarchy("Graph::addVertexs");throw;
}






protspace::Edge&
protspace::Graph::addEdge(Vertex& vertex1, Vertex& vertex2) throw(ProtExcept) {
    try {
        return createLink(vertex1, vertex2, 1);
    } catch (ProtExcept &e) {

        e.addHierarchy("Edge::addEdge");
        throw;
    }
}

protspace::Edge&
protspace::Graph::addEdge(const size_t& pos1, const size_t& pos2) throw(ProtExcept)
try {
    const size_t nVe(mListDot.size());
    if (pos1>= nVe)
        throw_line("100101","Graph::addEdge",
                   "Given position 1 is above the number of vertices ("
                   +std::to_string(pos1)+"/"+std::to_string(nVe)+")");
    if (pos2>= nVe)
        throw_line("100102","Graph::addEdge",
                   "Given position 2 is above the number of vertices ("
                   +std::to_string(pos1)+"/"+std::to_string(nVe)+")");
    return createLink(*mListDot.at(pos1), *mListDot.at(pos2), 1);
} catch (ProtExcept &e) {
    assert(e.getId()!="030301" && e.getId()!="030302");
    if (e.getId()!= "100101" && e.getId()!="100102")
    {
        e.addHierarchy("Edge::addEdge");
        e.addDescription("Request vertex : "+
                         std::to_string(pos1)+" && "+
                         std::to_string(pos2));
    }
    throw;
}



protspace::Vertex& protspace::Graph::getVertex(const size_t& pos) const throw(ProtExcept)
try{
    return getDot(pos);
}catch(ProtExcept &e){e.addHierarchy("Graph::getVertex");throw;}







protspace::Edge& protspace::Graph::getEdge(const size_t& pos) const throw(ProtExcept)
try{
    return getLink(pos);
}catch(ProtExcept &e){e.addHierarchy("Graph::getEdge");throw;}






void protspace::Graph::delVertex(Vertex& vertex)throw(ProtExcept){delDot(vertex);}



void protspace::Graph::delEdge(Edge& edge)throw(ProtExcept){delLink(edge);}




void protspace::Graph::addVertex(Vertex& vertex) throw(ProtExcept)
try
{
    addDot(vertex);
}catch(ProtExcept &e){
    e.addHierarchy("Graph::addVertex");
    throw;
}




void protspace::Graph::addEdge(Edge& edge) throw(ProtExcept)
try
{
    addLink(edge);
}catch(ProtExcept &e){
    e.addHierarchy("Graph::addEdge");
    throw;
}






void protspace::Graph::copy(const Graph& graph) throw(ProtExcept)
{
    try{
        size_t nVertexs= mListDot.size();
        addVertex(graph.numVertex());
        for(size_t iVe=0;iVe< graph.numVertex();++iVe)
        {
            mListDot.at(nVertexs+iVe)->setFID(graph.mListDot.at(iVe)->getFID());
        }
        for(size_t iEd=0;iEd<graph.numEdges();++iEd)
        {
            const Edge& edge = graph.getEdge(iEd);
            Vertex& ve1=getVertex(edge.getVertex1().getMID()+nVertexs);
            Vertex& ve2=getVertex(edge.getVertex2().getMID()+nVertexs);
            Edge& newedge = addEdge(ve1,ve2);
            newedge.setFID(edge.getFID());

        }
        mName=graph.getName();
        mId=graph.getId();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("Graph::copy");
        throw;
    }
}






void protspace::Graph::serialize(std::ofstream &out) const
{
    // READING ID
    out.write((char*)&mId,sizeof(mId));

    // WRITING NAME OF GRAPH
    size_t length=mName.size();
    out.write((char*)&length,sizeof(size_t));
    out.write(mName.c_str(),mName.size());


    // LISTING VERTEX:
    length=mListDot.size();
    out.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<length;++i)
        mListDot.at(i)->serialize(out);


    length=mListLinks.size();
    out.write((char*)&length,sizeof(size_t));
    for(size_t i=0;i<length;++i)
    {
        mListLinks.at(i)->serialize(out);
    }


}





void protspace::Graph::unserialize(std::ifstream& ifs)
{
    // READING ID of molecule
    ifs.read((char*)&mId,sizeof(int));

    // READING NAME OF GRAPH:
    size_t length=0;
    ifs.read((char*)&length,sizeof(size_t));
    char* temp = new char[length+1];
    ifs.read(temp,length);
    temp[length]='\0';
    mName=temp;
    delete[]temp;

    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    addVertex(length);
    for(size_t i=0;i<length;++i)
    {
        mListDot.at(i)->unserialize(ifs);
    }

    length=0;
    ifs.read((char*)&length,sizeof(size_t));
    int type,mid,fid,dot1,dot2;
    for(size_t i=0;i<length;++i)
    {
        ifs.read((char*)&type,sizeof(int));
        ifs.read((char*)&mid,sizeof(int));
        ifs.read((char*)&fid,sizeof(int));
        ifs.read((char*)&dot1,sizeof(int));
        ifs.read((char*)&dot2,sizeof(int));
        Edge& ed=addEdge(*mListDot.at((unsigned int) dot1),
                *mListDot.at((unsigned int) dot2));
        ed.mMId=mid;
        ed.mFId=fid;
        ed.mType=type;
    }

}


