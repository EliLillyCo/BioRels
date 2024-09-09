//
// Created by c188973 on 10/12/16.
//

#include <molecule_to_query.h>
#include <headers/statics/protExcept.h>
#include "headers/iwcore/substrSearch.h"
#include "headers/iwcore/macrotoiw.h"

SubstrSearch::SubstrSearch(const std::string& SMI_QUERY)
{
    Molecule_to_Query_Specifications mQS;
    mQS.set_make_embedding(1);
    Molecule mIWMolecule;
    if (!mIWMolecule.build_from_smiles(SMI_QUERY.c_str()))
        throw_line("260101",
        "SubstrSearch::SubstrSearch",
        "Unable to construct molecule from SMILES "+SMI_QUERY);
    if (!mQuery.create_from_molecule(mIWMolecule,mQS))
        throw_line("260102",
                   "SubstrSearch::SubstrSearch",
                   "Unable to create substructure query");
    mQuery.set_find_unique_embeddings_only(1);
    ///TODO :TEST IT
    //set_do_not_perceive_symmetry_equivalent_matches
}




int SubstrSearch::compare(protspace::MacroMole& pMole, std::vector<int> *&results )
try{
    MacroToIW miw(pMole);
    Molecule& inputMole(miw.getIWMole());
    Substructure_Results s_results;

    /// Perform the substructure search and put results in s_results
    const int n_hits(mQuery.substructure_search(inputMole,s_results));
    if (n_hits==0)return 0;


    results = new std::vector<int>[n_hits];
    for(int i=0;i<n_hits;++i)
    {
        /// A results consists in a set of atom ids
        const Set_of_Atoms& set_val= *s_results.embedding(i);
        /// Fetching the converted list:
        std::vector<int> &listAtm = results[i];
        /// Filling the list:
        const size_t size_list((size_t)set_val.number_elements());
        listAtm.reserve(size_list);
        for(size_t j=0;j<size_list;++j)listAtm.push_back(set_val[j]);
    }
    return n_hits;

}catch(ProtExcept &e)
{
    e.addHierarchy("SubstrSearch::compare");
    throw;
}

int SubstrSearch::compare(protspace::MacroMole& pMole, std::vector<protspace::MMAtom*> *&results)
try{
    MacroToIW miw(pMole);
    Molecule& inputMole(miw.getIWMole());
    Substructure_Results s_results;

    /// Perform the substructure search and put results in s_results
    const int n_hits(mQuery.substructure_search(inputMole,s_results));
    if (n_hits==0)return 0;


    results = new std::vector<protspace::MMAtom*>[n_hits];
    for(int i=0;i<n_hits;++i)
    {
        /// A results consists in a set of atom ids
        const Set_of_Atoms& set_val= *s_results.embedding(i);
        /// Fetching the converted list:
        std::vector<protspace::MMAtom*> &listAtm = results[i];
        /// Filling the list:
        const size_t size_list((size_t)set_val.number_elements());
        listAtm.reserve(size_list);
        for(size_t j=0;j<size_list;++j)listAtm.push_back(&miw.getMMAtomFromAtomPos(set_val[j]));
    }
    return n_hits;

}catch(ProtExcept &e)
{
    e.addHierarchy("SubstrSearch::compare");
    throw;
}






SubstrSearch::SubstrSearch(Molecule &mole) {
    Molecule_to_Query_Specifications mQS;
    mQS.set_make_embedding(1);
    if (!mQuery.create_from_molecule(mole,mQS))
        throw_line("260201",
                   "SubstrSearch::SubstrSearch",
                   "Unable to create substructure query");
    mQuery.set_find_unique_embeddings_only(1);
    ///TODO :TEST IT
    //set_do_not_perceive_symmetry_equivalent_matches
}

