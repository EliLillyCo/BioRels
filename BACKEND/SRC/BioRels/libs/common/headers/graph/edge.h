#ifndef EDGE_H
#define EDGE_H


#include "headers/statics/dot.h"
#include "headers/statics/group.h"
#include "headers/statics/link.h"


namespace protspace
{
class Vertex;
class Graph;
/**
 * @brief The Edge class represent a link between two vertices in a graph
 */
class Edge:public link<Graph,Vertex >
{
    friend class Graph;
    friend class Group<Vertex,Edge,Graph>;
private:
    int mType;
public:
    /**
     * @brief Standard constructor
     * @param graph Parent graph
     * @param vertex1 First vertex associated in this edge
     * @param vertex2 Second vertex associated in this edge
     */
    Edge(Graph& graph, Vertex& vertex1, Vertex& vertex2,
         const int& id,
         const int& type)throw(ProtExcept);

    virtual ~Edge();
    /**
     * @brief Return the first vertex involved in this edge
     * @return One of the two vertices involved in this edge
     * @test testEdgeCreation
     */
    inline Vertex& getVertex1() const {return mDot1;}


    /**
     * @brief Return the second vertex involved in this edge
     * @return One of the two vertices involved in this edge
     * @test testEdgeCreation
     */
    inline Vertex& getVertex2() const {return mDot2;}


    /**
     * @brief Return a human readable description of this edge
     * @return Description of this edge
     */
    std::string toString() const{return "";}

    void serialize(std::ofstream& out) const ;

    inline const int& getType() const{return mType;}
};

}

#endif // EDGE_H
