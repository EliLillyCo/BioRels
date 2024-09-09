#ifndef GRAPH_H
#define GRAPH_H

#include <vector>

#include "headers/statics/protExcept.h"
#include "headers/graph/vertex.h"


namespace protspace
{


class Edge;
/**
 * @brief The Graph class is a representation of a set of objects (vertex) where some pairs of objects are connected by links (edges)
 */
class Graph:public Group<Vertex,Edge,Graph>
{
private:

    int mId;



    std::string mName;



public:
    /**
     * @brief addVertex Add the given vertex to this graph
     * @throw 030501 Cannot add a vertex own by another graph
     *
     * In order to use this function, the graph must not own the vertex,
     * meaning that when the destructor of the graph is called,
     * it will not call the destructor of its vertices and edges. The
     * graph is therefore called an alias graph. Alias graphs cannot be
     * created by users, there can only be created by a friend class of Graph.
     *
     * Therefore, this function will add a vertex to the graph but the graph
     * will not own it.
     *
     */
    void addVertex(Vertex&) throw(ProtExcept);


    /**
     * @brief addEdge Add a new edge to this graph
     * @throw 030601 Cannot add an link own by another group.
     *
     * In order to use this function, the graph must not own edges.
     */
    void addEdge(Edge& ) throw(ProtExcept);

    /**
     * @brief Standard constructor that allocate memory for the graph
     * @param nVertex Number of expected vertices
     * @param nEdge Number of expected edges
     * @throw 030101 Bad allocation
     *
     * Values given in nVertex and nEdge are not strict. You can go above or
     * below. Remember allocating enough in the case of large graph so it can
     * be faster
     */
    Graph(const size_t& nVertex=50, const size_t& nEdge=200) throw(ProtExcept);


    /**
     * @brief Creates a graph
     * @param owner When set to true Creates a graph that contains edges and vertices of other graph
     *
     * When owner is set to true, be aware that any modification on this
     * graph will be made on the other graph as well.
     *
     */
    Graph(const bool& owner);


    /**
     * @brief Constructor by copy
     * @param pGraph Graph to copy
     */
    Graph(const Graph& pGraph);

    Graph& operator=(const Graph& pGraph);

    Graph& operator+=(const Graph& pGraph);

    ~Graph();
    /**
     * @brief Create a new vertex in this graph
     * @return the newly created vertex
     * @throw 030201 - Bad allocation
     * @throw 030202   This group don't own dot, so can't create one
     * @test test_vertex
     */
    Vertex& addVertex() throw(ProtExcept);



    /**
     * @brief add multiple vertices in one time
     * @param counts Number of vertices to add
     * @throw 030201 - Bad allocation
     * @throw 030202 - This group don't own dot, so can't create one
     */
    void addVertex(const size_t& counts)throw(ProtExcept);


    /**
     * @brief Gives the name of the graph
     * @return name of the graph
     */
    const std::string& getName() const { return mName;}


    /**
     * @brief set the name of the graph
     * @param name name of the graph
     */
    void setName(const std::string& name) {mName= name;}


    /**
     * @brief set the Id of the graph
     * @param id Id of the graph
     */
    void setId(const int& id){mId=id;}


    /**
     * @brief Gives the id of the graph
     * @return Id of the graph
     */
    const int& getId()const {return mId;}


    /**
     * @brief Create and return a new edge that connects the two given vertices
     * @param vertex1 First vertex involved in this new link
     * @param vertex2 Second vertex involved in this new link
     * @return The newly created edge
     * @throw 030301 Dot 1 is not part of this Group
     * @throw 030302 Dot 2 is not part of this Group
     * @throw 030303 Bad allocation
     * @throw 030204 This group don't own link, so can't create one
     */
    Edge& addEdge(Vertex& vertex1, Vertex& vertex2) throw(ProtExcept);



    /**
     * @brief Create and return a new edge that connects the vertices associated to the given positions
     * @param pos1 Position of the first vertex
     * @param pos2 Position of the second vertex
     * @return The newly created edge
     * @throw 100101 Given position 1 is above the number of vertices
     * @throw 100102 Given position 2 is above the number of vertices
     * @throw 030303 Bad allocation
     * @throw 030204 This group don't own link, so can't create one
     */

    Edge& addEdge(const size_t& pos1, const size_t& pos2) throw(ProtExcept);

    /**
     * @brief Number of vertex in the graph
     * @return Number of vertice in the graph
     * @test test_vertex
     */
    size_t numVertex() const {return mListDot.size();}

    /**
     * @brief Get the vertex at the given position in the vertex list of this graph
     * @param pos Position in the vertex list
     * @return Vertex
     * @test test_vertex
     * @throw 030401 Given position is above the number of vertices
     */
    Vertex& getVertex(const size_t& pos) const throw(ProtExcept);

    /**
     * @brief Get the edge at the given position in the edge list of this graph
     * @param pos Position in the edge list
     * @return Corresponding edge
     * @throw 030901 Given position is above the number of edges
     * @test testEdgeCreation
     */
     Edge& getEdge(const size_t& pos) const throw(ProtExcept);



    /**
     * @brief Number of edges in the graph
     * @return Number of edges in the graph
     * @test test_edge_creation
     */
    size_t numEdges()const {return mListLinks.size();}


    /**
     * @brief Delete the given vertex from this graph
     * @param vertex Vertex to delete
     * @throw 030701 Given Dot is not part of this molecule
     * @throw 030702 Wrong Dot MID
     * @throw 030703 Dot unmatch MID
     * @throw 030801 Given Edge is not part of this graph
     * @throw 030802 Given edge is not part of this graph
     * @throw 020101 Given link is not part of this dot
     * @test testVertexDeletion
     */
    void delVertex(Vertex& vertex)throw(ProtExcept);

    /**
     * @brief delEdge Delete the given edge from the graph
     * @param edge Edge to delete
     * @throw 030801 Given Edge is not part of this graph
     * @throw 030802 Given edge is not part of this graph
     * @throw 020101 Given link is not part of this dot
     */
    inline void delEdge(Edge& edge)throw(ProtExcept);



    ///
    /// \brief Copy the given graph into the current graph
    /// \param graph Graph to be copied
    ///
    /// Copy the given graph into the current gra
    /// \throw 030401 Given position is above the number of dots
    /// \throw 030201 Bad allocation
    /// \throw 030901 Given position is above the number of edges
    /// \throw 030301 vertex1 is not part of this graph
    /// \throw 030302 vertex2 is not part of this graph
    /// \throw 030303 Bad allocation
    /// \throw 020201 Given link not found in this dot
    /// \throw 100101 Given position 1 is above the number of vertices
    /// \throw 100102 Given position 2 is above the number of vertices
    void copy(const Graph& graph) throw(ProtExcept);



    void serialize(std::ofstream &out) const;

    void unserialize(std::ifstream& ifs);


};

}


#endif // GRAPH_H
