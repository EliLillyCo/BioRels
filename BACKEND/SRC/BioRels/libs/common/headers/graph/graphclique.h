#ifndef GRAPHCLIQUE_H
#define GRAPHCLIQUE_H

#include "headers/graph/graphpair.h"
//#include "headers/math/rigidbody.h"
namespace protspace
{




/**
 * @brief The Clique struct is a list of pairs (reference & comparison object) that match your definition
 */
template<class T>
struct Clique
{

    /**
     * @brief List of pair defining the clique
     */
    std::vector<Pair<T>*> listpair;


    /**
     * @brief Score of the clique. By default 0
     */
    double score;


    /**
     * @brief rmsd of the aligned object
     */
    double rmsd;


    //RigidBody alignment;

    /**
     * @brief Constructor
     * @param size Size of the clique (used for memory allocation)
     */
    Clique(const size_t& size):score(0) {listpair.reserve(size);}
};


}
#endif // GRAPHCLIQUE_H

