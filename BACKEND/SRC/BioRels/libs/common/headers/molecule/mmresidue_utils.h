#ifndef MMRESIDUE_UTILS_H
#define MMRESIDUE_UTILS_H

#include <string>
#include <vector>


namespace protspace
{

class MMResidue;
class Coords;
class MMBond;
class MMChain;
    class MMAtom;
class MacroMole;

/**
 * @brief Convert 1 letter amino acid code to 3 letters
 * @param letter 1 Letter code
 * @return  3 Letter code
 * @throw 325001 input should be 1 letter
 * @throw 325002 No name found
 */
const std::string &residue1Lto3L(const std::string& letter);


/**
 * @brief Convert 3 letters code to 1 letter code
 * @param name 3 Letters amino acid name
 * @return 1 Letter code
 */
std::string residue3Lto1L(const std::string& name);



/**
 * @brief perceive residue class
 * @param residue Given residue to be processed
 * @param monomer_type Pre-known information
 */
void perceiveClass(MMResidue& residue,
                   const std::string& monomer_type);


const std::string &getResidueType(const MMResidue& residue);

double getWeight(const MMResidue& residue) ;
double getAvgBFactor(const MMResidue& res);

void getLinkedResidue(const MMResidue& residue,
                      std::vector<MMBond*>& list);

void getLinkedResidue(const MMResidue& residue,
                      std::vector<MMResidue*>& list);
bool areLinked(const MMResidue& res1,const MMResidue& res2);
short nLinkedResidue(const MMResidue& residue);
unsigned short numHeavyAtom(const MMResidue& residue);
double getShortestDistance(const MMResidue& res1,
                           const MMResidue& res2,
                           const double &thres=1);
    double getShortestDistance(const MMAtom& atom1,const MMResidue& res2,const double& thres=1);
double getAverageDistance(const MMResidue& res1,const MMResidue& res2);
bool isProteinChain(const MMChain& pChain);

///
/// \brief Toggle the view of the molecule around this residue
/// \param threshold Maximal length in Angstroems to toggle the view
/// \param wholeResidue TRUE when toggling the whole residue, FALSE to toggle atom
///
/// toggleView will first set use to false for the whole molecule having
/// this residue. Then, it will look at the distance between each atom
/// of the molecule and each atom of this residue. When the distance
/// is lower than the given threshold, then the atom is considered as
/// within the shell of the residue. If wholeResidue is set to true,
/// the residue of this residue see it use set to true, otherwise,
/// only the atom will have is use changed.
///
void toggleSelection(MMResidue& pRes, const double& threshold,
                                const bool& wholeResidue);


///
 /// \brief Return the geometric center of the residue
 /// \param onlySelected TRUE when only atom used are considered
 /// \param wHydrogen TRUE when Hydrogen atoms are considered
 /// \return Coordinates representing the geometric center of the residue
 ///
Coords getCenter(const MMResidue& pRes,
                 const bool& onlyUsed=false,
                 const double &wHydrogen=false);

bool getCHI1(const MMResidue& pRes, double& val);
bool getCHI2(const MMResidue& pRes, double& val);
bool getCHI3(const MMResidue& pRes, double& val);
bool getCHI4(const MMResidue& pRes, double& val);
bool getCHI5(const MMResidue& pRes, double& val);
bool getPSI(const MMResidue& pRes, double& val);
bool getPHI(const MMResidue& pRes, double& val);
    void checkResidueNumber(MacroMole& pMole);
    void getCounts(const MMResidue& pRes,size_t& nN, size_t& nC, size_t& nO,size_t& nOth);
    void delIntraBond(MMResidue& pRes);
    bool isAA(const MMResidue& pRes);
}

#endif // MMRESIDUE_UTILS_H

