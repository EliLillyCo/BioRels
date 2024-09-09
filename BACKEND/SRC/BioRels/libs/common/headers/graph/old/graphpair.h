#ifndef GRAPHPAIR_H
#define GRAPHPAIR_H

#include "headers/graph/vertex.h"
namespace protspace
{
/**
 * @brief The Pair struct defines a possible match between two objects
 */
template<class T>
struct Pair
{
    /**
     * @brief Object in the reference molecule
     */
    T& obj1;


    /**
     * @brief Object in the comparison molecule
     */
    T& obj2;


    /**
     * @brief Vertex in the product graph that is associated to this pair
     */
    Vertex& vertex;


    /**
     * @brief Constructor
     * @param obj1 Object in the reference
     * @param obj2 Object in the comparison
     * @param vertex Associated vertex in the product graph
     */
    Pair(T& obj1, T& obj2, Vertex& vertex):
        obj1(obj1),obj2(obj2),vertex(vertex) {}


    /**
     * @brief Copy constructor
     * @param p Pair to copy
     */
    Pair(const Pair& p):
            obj1(p.obj1),obj2(p.obj2),vertex(p.vertex)
    {

    }


    /**
     * @brief Assign a pair on another pair
     * @param other Pair to copy
     * @return Copy of the given pair
     */
    Pair & operator =  (const Pair & other)
    {
        obj1=other.obj1;
        obj2=other.obj2;
        vertex=other.vertex;
        return *this;
    }

    bool operator!=(const Pair& other)const
    {
        return (&obj1!=&other.obj1 || &obj2!=&other.obj2);
    }
};

}
#endif // GRAPHPAIR_H

