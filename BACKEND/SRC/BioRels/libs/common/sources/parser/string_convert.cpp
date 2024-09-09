#include "headers/parser/string_convert.h"
#include "headers/graph/graph.h"
#include "headers/molecule/mmbond.h"
#include "headers/molecule/mmbond_utils.h"
#undef NDEBUG /// Active assertion in release
std::string operator+(std::string out,const protspace::Edge &qq)
{
    out+= "Edge " + std::to_string(qq.getDot1().getMID())
            + " <-> "+ std::to_string(qq.getDot2().getMID())+"\n";
    return out;
}
std::string operator+(std::string out,const protspace::Vertex& ve)
{
    out +="VERTEX " +std::to_string(ve.getMID())
             +"(FID:"+std::to_string(ve.getFID())+")\n";

    for(size_t iEd=0;iEd < ve.numDot();++iEd)
        out+= "  |-> " + ve.getEdge(iEd)+ "\n";

    return out;
}


std::string operator+(std::string out, const protspace::Graph &gr)
{
    for (size_t i=0;i<gr.numVertex();++i)
        out+=""+gr.getVertex(i);
    for (size_t i=0;i<gr.numEdges();++i)
        out +=""+ gr.getEdge(i);
    return out;
}


std::string operator+(const std::string& out , const protspace::MMBond &bd)
{

    return out+"|>Bond("+std::to_string( bd.getMID())
       +"/"      +std::to_string(bd.getFID())
       +") :"    +bd.getDot1().getIdentifier()
       +"<->"    +bd.getDot2().getIdentifier()
       +"\t"     +protspace::getBondType(bd);

}
