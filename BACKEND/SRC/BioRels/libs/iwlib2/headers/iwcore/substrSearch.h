//
// Created by c188973 on 10/12/16.
//

#ifndef GC3TK_CPP_SUBSTRSEARCH_H
#define GC3TK_CPP_SUBSTRSEARCH_H


#include <string>
#include "iwbits.h"
#include <substructure.h>
#include <headers/molecule/macromole.h>




class SubstrSearch {
protected:
    Substructure_Query mQuery;

public:
    /**
     * @brief SubstrSearch
     * @param SMI_QUERY
     * @throw 260101    SubstrSearch::SubstrSearch Unable to construct molecule from SMILES
     * @throw 260102   SubstrSearch::SubstrSearch  Unable to create substructure query
     */
    SubstrSearch(const std::string& SMI_QUERY);

    /**
     * @brief SubstrSearch
     * @param mole
     * @throw 260201    SubstrSearch::SubstrSearch  Unable to create substructure query
     */
    SubstrSearch(Molecule& mole);
    int compare(protspace::MacroMole& pMole, std::vector<int> *&results);

    int compare(protspace::MacroMole& pMole, std::vector<protspace::MMAtom*> *&results);
};


#endif //GC3TK_CPP_SUBSTRSEARCH_H
