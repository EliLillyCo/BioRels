#ifndef MATCHMOLE_H
#define MATCHMOLE_H
#include <cstdint>
#include "headers/graph/graphmatch.h"
#include "headers/molecule/mmatom.h"

namespace protspace
{
/**
 * @brief The MatchMole class align two molecules according to various rules
 */
class MatchMole
{
public:
    /**
     * @brief Use atom name as rule to match (CA, CB, OD1)
     */
    static const uint32_t ATOM_NAME;

    /**
     * @brief Use TRIPOS MOL2 type as a rule to match atoms (C.2, C.ar, N.pl3)
     */
    static const uint32_t ATOM_MOL2;

    /**
     * @brief Use Atomic Symbol as a rule (C, N, O, P)
     */
    static const uint32_t ATOM_SYMBOL;

    /**
     * @brief Use atom id (as given by the file) as a rule
     */
    static const uint32_t ATOM_ID;

    /**
     * @brief Use pharmacophoric property as a rule (Hydrophobic, H-MMBond Acceptor)
     *
     * A percentage of physico-chemical property overlap can be set by using
     * setCountPropOverlap function. setCountPropOverlap defines the minimal
     * number of properties to be in common between the two atoms.
     *
     *
     */
    static const uint32_t ATOM_PROP;

    /**
     * @brief Use the bond order of an atom to consider is similar to another atom
     *
     * To consider two atoms of two molecules as similar with that rule,
     * these two atoms must share the same number of bonds AND having the same
     * bond order.
     */
    static const uint32_t BOND_ORDER;

    static const uint32_t RESIDUE_BACKBONE;

    /**
     * @brief Use the residue name as a rule (ALA, PHE, STU...)
     */
    static const uint32_t RESIDUE_NAME;

    /**
     * @brief For to have the same number of bond and bond types
     */
    static const uint32_t BOND_LAYER;


    /**
     * @brief Don't consider hydrogen during calculation
     *
     * Really improve time
     */
    static const uint32_t NO_HYDROGEN;


    static const uint32_t W_POLAR_HYDROGEN;

    /**
     * @brief Perform a full scan (No pivot)
     */
    static const uint32_t FULL_SCAN;

    /**
     * @brief Use the euclidian distance as a rule to consider two pairs has an equal distance
     *
     * Distance threshold can be set using setDistThreshold()
     */
    static const uint32_t EUCLIDIAN_DISTANCE;


    /**
     * @brief Use the number of bonds as a rule to consider two pairs has an equal distance
     *
     * MMBond distance threshold can be set using setMMBondDistThreshold()
     *
     */
    static const uint32_t BOND_DISTANCE;

    /**
     * @brief Sort clique by the rmsd of the selected pairs
     */
    static const uint32_t SORT_BY_RMSD         ;

    /**
     * @brief Sort cliques by the number of atom matched
     */
    static const uint32_t SORT_BY_SIZE         ;


    /**
     * @brief Remove rmsd threshold on 3D mapping
     *
     * Once cliques are calculated, an 3D alignment based on this one-to-one
     * mapping is generated. When the rmsd of the 3D alignment is above the
     * value defined by rmsd_threshold (default 10Angstroems), the clique
     * is considered as wrong and therefore remove from the results list.
     * Adding TWO_DIMENSION_MATCH remove this 3D alignment and rmsd filtering
     */

    static const uint32_t TWO_DIMENSION_MATCH;

    static const uint32_t CONSIDER_RING;
private:

    /**
     * @brief Graph Matching object specific for atom
     */
    GraphMatch<MMAtom> graphmatch;



    /**
     * @brief Reference macromolecule, the one you align to
     */
    MacroMole& reference;



    /**
     * @brief Compared macromolecule, the one you want to align
     */
    MacroMole& comparison;



    /**
     * @brief Euclidian distance threshold to consider two pairs of atoms at the same distance
     */
    double eucl_dist_threshold;



    /**
     * @brief Threshold defined by the Number of bonds between two atoms  to consider two pairs of atoms at the same distance
     */
    double bond_dist_threshold;



    /**
     * @brief Maximal RMSD (in Angstreoms) to consider a clique as correct
     *
     * Once cliques are calculated, an 3D alignment based on this one-to-one
     * mapping is generated. When the rmsd of the 3D alignment is above the
     * value defined by rmsd_threshold (default 10Angstroems), the clique
     * is considered as wrong and therefore remove from the results list.
     *
     * use TWO_DIMENSION_MATCH to remove this filtering step
     */
    double rmsd_threshold;



    /**
     * @brief Minimal number of physico-chemical properties to consider atom as similar
     */
    size_t minPropOverlap;



    /**
     * @brief List of rules
     */
    uint32_t rules;



    bool runDone;

    bool mFullScan;

    bool mOnlyUsed;



    std::vector<Clique<MMAtom>*> sortedClique;



    UIntMatrix mRefMatrix;
    UIntMatrix mCompMatrix;
    std::map<int,int> mRefMapMatrix,mCompMapMatrix;
public:

    /**
     * @brief Create a new object to compare two macromolecule: reference and comparison
     * @param reference Reference macromolecule, the one you align to
     * @param comparison Compared macromolecule, the one you want to align
     */
    MatchMole(MacroMole& reference,
              MacroMole& comparison,
              const bool& onlyUsed=false);



    /**
     * @brief Add a new rule to compare atoms
     * @param rule Selected rule to add
     */
    void addRule(const uint32_t& rule);



    /**
     * @brief Remove a rule
     * @param rule Rule to remove
     */
    void removeRule(const uint32_t& rule);



    /**
     * @brief Set the minimal number of atoms to be matched to consider a clique
     * @param size Minimal number of atoms
     */
    void setMinSize(const size_t& size){ graphmatch.setMinSize(size);}

    void scoreCliques(
            const uint32_t& sortRules,
            std::multimap<double, Clique<MMAtom>*>& tempSort);
    bool checkBondLayer(const MMAtom& atomR,
                        const MMAtom& atomC)const;

    bool isFiltered(const MMAtom& pAtom)const;
    /**
     * @brief runMatch
     * @todo Implement BOND_ORDER Rule && ATOM_PROP rules
     * @throw 630101  MatchMole::linkPairs  No pairs found
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 030303 Bad allocation
     *
     * \throw 110402  GraphMatch::calcCliques No Edges defined
    * \throw 200101 Matrix - Bad allocation

     */
    void runMatch(const uint32_t& sortRules, const bool calcPairs=true)  throw(ProtExcept);


    /**
     * @brief checkBondDistance
     * @param pairR
     * @param pairC
     * @return
     * @throw 630201   MatchMole::checkBondDistance    Position out of range
     */
    bool checkBondDistance(const Pair<MMAtom>& pairR,
                           const Pair<MMAtom>& pairC)const;


    /**
     * @brief Set the minimal number of physico chemical properties that are in common between two atoms to consider them as similar
     * @param count Minimal number of properties to consider
     *
     * In order to be used, ATOM_PROP rule must be added to the list of rules.
     */
    void setCountPropOverlap(const size_t& count){minPropOverlap=count;}



    /**
     * @brief Set the maximal difference of bonds between two sets of atoms
     * @param dist Maximal difference of number of bonds
     */
    void setMMBondDistThreshold(const double& dist) {bond_dist_threshold=dist;}


    /**
     * @brief Set the maximal difference in euclidian distance between two sets of atoms
     * @param dist Maximal difference in the euclidian distance between two sets of atoms
     */
    void setDistThreshold(const double& dist) {eucl_dist_threshold=dist;}

    void setFullScan(const bool& scan){mFullScan=scan;}

    /**
     * @brief Number of clique found
     * @return The number of cliques
     * When this function is called before the runMatch() function, the program
     * will crash
     */
    size_t numCliques() const;



    /**
     * @brief Return the clique associated to the given position
     * @param pos Position of the clique in the list of clique
     * @return Clique at position pos
     *
     * Once runMatch function has been run, all cliques are stored in
     * an array and are sorted by decreasing score. The latter depends on
     * the rule you have set to. Therefore, the best clique found will be at
     * position 0. The array range from 0 to numCliques()-1
     *
     * When the given pos is above the array range, the program will crash.
     * When this function is called before the runMatch() function, the program
     * will crash
     *
     */
    const Clique<MMAtom>& getClique(const size_t& pos)const;



    /**
      * @brief Get the pair of atom in the list of potential pairs
      * @param pos : Position of the pair in the list of potential pair
      * @return A pair of atoms that can be possibly matched
      * @throw 140101 Given number is above the number of pairs

      */
    Pair<MMAtom> &getPair(const size_t &pos) throw(ProtExcept);


    /**
      * @brief force the graphMatching to only consider atom in use
      * @param isUsed : TRUE when to only consider atoms in use
      */
    void setUsed(const bool& isUsed){mOnlyUsed=isUsed;}


    /**
      * @brief Return the total number of potential atom mapping found based on your rules
      * @return Total number of potential pairs
      */
    size_t numPairs() const {return graphmatch.numPairs();}
    size_t numEdges() const {return graphmatch.numEdges();}

    bool checkClique(std::vector<size_t>& list,const bool& verbose=false)const;

    void checkPair(MMAtom& atom1, MMAtom& atom2);

    void clearRule();

    //void getGammaCliques(const double& gamma){graphmatch.getGammaCliques(gamma);}

    void prepareDistMatrix(MacroMole& molecule,
                           UIntMatrix &matrix,
                           std::map<int, int> &map
                           ) throw(ProtExcept);

    /**
     * @brief listPairs
     *  @throw 110101 GraphMatch::addPair Bad allocation
      * @throw 030201 Graph::addVertex - Bad allocation
     */
    void listPairs()throw(ProtExcept);

    /**
     * @brief linkPairs
     * @throw 630101  MatchMole::linkPairs  No pairs found
     * @throw 030303 Bad allocation
     */
    void linkPairs() throw(ProtExcept);
    void addPair(MMAtom&, MMAtom&);
    void sortResults(const uint32_t& sortRules) throw(ProtExcept);
};


}
#endif // MATCHIOLE_H


