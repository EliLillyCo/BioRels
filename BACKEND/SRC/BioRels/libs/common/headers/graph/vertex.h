#ifndef VERTEX_H
#define VERTEX_H
#include "headers/statics/dot.h"
#include "headers/statics/group.h"
#include "headers/graph/edge.h"

namespace protspace
{



class Graph;
class Vertex;
/**
 * @brief A vertex or node is the fundamental unit of which graphs are formed
 */
class Vertex: public dot<Graph,Vertex>
{
    friend class Group<Vertex,Edge,Graph>;
    friend class Graph;
private:

    /**
     * @brief Standard constructor
     * @param Id of the vertex
     * @param Parent graph
     * @throw 020601 Bad allocation
     */
    Vertex(const unsigned int& id, Graph* const graph);

    /**
     * @brief Add the given edge to the list of edges of this vertex
     * @param edge Edge to add
     * @throw 010201 Given edge does not involve this vertex
     */
    void addEdge(Edge& edge) throw(ProtExcept);


    /**
     * @brief delEdge Delete the given edge from this vertex
     * @param edge Edge to remove from this vertex
     * @throw 020101 Given edge does not involve this vertex
     */
    void delEdge(Edge& edge) throw(ProtExcept);


    /**
     * @brief Update the edge MID from former to newer
     * @param former Former MID of the edge
     * @param newer New MID of the edge
     */
    void setEdgeNum(const int& former, const int&newer);


    virtual ~Vertex();
public:
    /**
     * @brief getEdge give the edge at a given position of the edgelist for this vertex
     * @param pos Position of the edge in the list of edges for this vertex
     * @return The selected edge
     * @throw 120301 Given position is above the number of edges for this vertex
     * @test testVertex
     */
    Edge& getEdge(const size_t& pos) const throw(ProtExcept);


    /**
     * @brief give the vertex at a given position of the vertex list for this vertex
     * @param pos Position in the array of vertexs linked to this vertex
     * @return The vertex linked to this vertex
     * @throw 120401 Given position is above the number of vertexs linked to this vertex
     * @test testVertex
     */
    Vertex& getVertex(const size_t& pos) const throw(ProtExcept);


    /**
     * @brief Gives the parent graph of this vertex
     * @return Parent graph
     * @test testVertex
     */
    Graph& getGraph() const {return *mParent;}


    /**
     * @brief Check whether the given vertex has an edge with this vertex
     * @param vertex Given vertex to check edge existence
     * @return TRUE when an edge exists, false otherwise
     * @test testVertex
     */
    bool hasEdgeWith(const Vertex& vertex) const;



    /**
     * @brief Get the Edge existing between this vertex and the given vertex
     * @param vertex Vertex which makes the edge with
     * @return corresponding edge between this vertex and the given vertex
     * @throw 060203 Given vertex does not make any edge with this vertex
     * @test testVertex
     */
    Edge& getEdgeWith(const Vertex& vertex) const  throw(ProtExcept);

    void serialize(std::ofstream& out) const;
    void unserialize(std::ifstream& in);


};

}

#endif // VERTEX_H
